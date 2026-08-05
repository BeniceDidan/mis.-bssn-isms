<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'admin_module'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Two roles exist: 'admin' verifies submitted data (see
     * AdminVerificationQueue) and 'user' submits it for review. Both can
     * create/edit/archive/import everywhere — they only differ on whether
     * a save needs a second pair of eyes before it counts as final. There
     * is deliberately no blanket "super admin" — an admin's verification
     * power is scoped to exactly one module via admin_module (see
     * canAutoVerify() and App\Support\AdminModules).
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Whether a save in $moduleKey (one of App\Support\AdminModules::LABELS)
     * made by this user should be immediately final ('tervalidasi') instead
     * of going into that module's verification queue. Every FormModal's
     * save() calls this with its own hardcoded module key instead of the
     * old blanket isAdmin() check.
     */
    public function canAutoVerify(string $moduleKey): bool
    {
        return $this->isAdmin() && $this->admin_module === $moduleKey;
    }

    public function adminModuleLabel(): ?string
    {
        return \App\Support\AdminModules::label($this->admin_module);
    }

    /**
     * Whether this user may create/edit/archive/import data anywhere in
     * the system. Currently always true — kept as the single choke point
     * every module's Livewire component already calls (directly or via
     * GuardsWriteAccess), so a future read-only role only needs to change
     * this one method instead of every call site.
     */
    public function canWrite(): bool
    {
        return true;
    }

    /**
     * Overrides CanResetPassword's default, which sends Laravel's built-in
     * Markdown-rendered notification — see ResetPasswordNotification for
     * why that path is avoided here.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
