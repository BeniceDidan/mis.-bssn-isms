import { startTour, resumeTourIfActive } from './tour';

// Exposed globally so the topbar's plain onclick="startTour()" can reach it
// without needing its own Alpine component. Module scripts run after HTML
// parsing (like defer), so DOMContentLoaded may have already fired by the
// time this executes — check readyState instead of blindly listening.
window.startTour = startTour;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', resumeTourIfActive);
} else {
    resumeTourIfActive();
}

// Material-style click "wave" for icon buttons — usage: add x-ripple to any
// element. Registered on Livewire's bundled Alpine instance, so no separate
// Alpine import is needed here.
document.addEventListener('alpine:init', () => {
    Alpine.directive('ripple', (el) => {
        el.classList.add('relative', 'overflow-hidden');

        el.addEventListener('click', (event) => {
            const rect = el.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);

            const circle = document.createElement('span');
            circle.className = 'ripple-effect';
            circle.style.width = `${size}px`;
            circle.style.height = `${size}px`;
            circle.style.left = `${event.clientX - rect.left - size / 2}px`;
            circle.style.top = `${event.clientY - rect.top - size / 2}px`;

            el.appendChild(circle);
            setTimeout(() => circle.remove(), 550);
        });
    });

    // A single global iOS-style confirmation sheet, shared by every
    // destructive/important action across all 6 modules — replaces the
    // browser's native wire:confirm() popup (which looks "jadul" and
    // breaks the liquid-glass theme). Usage in Blade:
    //   x-on:click="if (await $store.confirm.ask('Judul', 'Pesan', 'danger')) $wire.archive(1)"
    // ask() resolves the returned promise with true/false once the user
    // picks a button in the <x-confirm-sheet /> mounted in layouts/app.
    Alpine.store('confirm', {
        open: false,
        title: '',
        message: '',
        tone: 'default',
        _resolve: null,
        ask(title, message, tone = 'default') {
            this.title = title;
            this.message = message;
            this.tone = tone;
            this.open = true;

            return new Promise((resolve) => {
                this._resolve = resolve;
            });
        },
        confirm() {
            this.open = false;
            this._resolve?.(true);
        },
        cancel() {
            this.open = false;
            this._resolve?.(false);
        },
    });

    // Full-viewport canvas behind the login card: soft drifting dots that
    // gently get pushed aside by the cursor and link up with thin lines when
    // close together — a quiet nod to the "jaringan/konektivitas" theme
    // without competing with the form for attention.
    Alpine.data('interactiveBackground', () => ({
        init() {
            const canvas = this.$refs.canvas;
            const ctx = canvas.getContext('2d');
            const mouse = { x: -9999, y: -9999 };
            let width, height, particles;

            const colors = ['14,165,233', '99,102,241', '45,212,191'];

            const resize = () => {
                width = canvas.width = window.innerWidth;
                height = canvas.height = window.innerHeight;
            };

            const spawn = () => {
                const count = Math.min(100, Math.floor((width * height) / 14000));
                particles = Array.from({ length: count }, () => ({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    vx: (Math.random() - 0.5) * 0.25,
                    vy: (Math.random() - 0.5) * 0.25,
                    r: Math.random() * 1.8 + 1,
                    color: colors[Math.floor(Math.random() * colors.length)],
                }));
            };

            resize();
            spawn();
            window.addEventListener('resize', () => { resize(); spawn(); });
            window.addEventListener('pointermove', (e) => { mouse.x = e.clientX; mouse.y = e.clientY; });

            const step = () => {
                ctx.clearRect(0, 0, width, height);

                particles.forEach((p) => {
                    p.x += p.vx;
                    p.y += p.vy;
                    if (p.x < 0 || p.x > width) p.vx *= -1;
                    if (p.y < 0 || p.y > height) p.vy *= -1;

                    const dx = mouse.x - p.x;
                    const dy = mouse.y - p.y;
                    const dist = Math.hypot(dx, dy);

                    if (dist < 150) {
                        const force = (150 - dist) / 150;
                        p.x -= dx * force * 0.03;
                        p.y -= dy * force * 0.03;
                    }

                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(${p.color},0.45)`;
                    ctx.fill();
                });

                for (let i = 0; i < particles.length; i++) {
                    for (let j = i + 1; j < particles.length; j++) {
                        const dx = particles[i].x - particles[j].x;
                        const dy = particles[i].y - particles[j].y;
                        const dist = Math.hypot(dx, dy);

                        if (dist < 130) {
                            ctx.beginPath();
                            ctx.moveTo(particles[i].x, particles[i].y);
                            ctx.lineTo(particles[j].x, particles[j].y);
                            ctx.strokeStyle = `rgba(14,165,233,${0.16 * (1 - dist / 130)})`;
                            ctx.lineWidth = 1;
                            ctx.stroke();
                        }
                    }
                }

                requestAnimationFrame(step);
            };

            step();
        },
    }));

    // Mouse-driven parallax for the floating network/signal icons on the
    // login page — mx/my range roughly -0.5..0.5 across the viewport, and
    // each icon multiplies that by its own "depth" (written inline in the
    // Blade template) so icons nearer the viewer drift further than ones
    // further back. The CSS icon-float bob runs independently on an inner
    // wrapper so the two motions don't fight over the same transform.
    Alpine.data('parallaxField', () => ({
        mx: 0,
        my: 0,

        onMove(e) {
            this.mx = e.clientX / window.innerWidth - 0.5;
            this.my = e.clientY / window.innerHeight - 0.5;
        },
    }));

    // Subtle 3D tilt on the login card, following the cursor within its own
    // bounds — cheap depth cue that makes the glass panel feel physical.
    Alpine.data('tiltCard', () => ({
        rx: 0,
        ry: 0,

        onMove(e) {
            const rect = this.$el.getBoundingClientRect();
            const px = (e.clientX - rect.left) / rect.width;
            const py = (e.clientY - rect.top) / rect.height;

            this.ry = (px - 0.5) * 8;
            this.rx = (0.5 - py) * 8;
        },

        onLeave() {
            this.rx = 0;
            this.ry = 0;
        },
    }));
});
