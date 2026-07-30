<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role'])]
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
     * Only two roles exist: 'admin' verifies submitted data (see
     * AdminVerificationQueue) and 'user' submits it for review. Both can
     * create/edit/archive/import everywhere — they only differ on whether
     * a save needs a second pair of eyes before it counts as final (see
     * AssetFormModal::save() and friends, keyed off isAdmin()).
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
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
}
