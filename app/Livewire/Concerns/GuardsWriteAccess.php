<?php

namespace App\Livewire\Concerns;

/**
 * Server-side backstop for User::canWrite() — a hidden button is not
 * access control, so every save()/archive()/restore()/import() call goes
 * through this too, not just the @if(auth()->user()->canWrite()) wrappers
 * in each module's Blade views. Both roles (admin/user) can currently
 * write, so this always passes; it stays wired up so a future read-only
 * role only needs to change User::canWrite(), not every module.
 */
trait GuardsWriteAccess
{
    protected function ensureCanWrite(): void
    {
        abort_unless(auth()->user()?->canWrite(), 403, 'Akun ini hanya dapat melihat data, tidak dapat mengubahnya.');
    }
}
