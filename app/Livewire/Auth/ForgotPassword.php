<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $status = null;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    /**
     * Always reports success regardless of whether the email matches an
     * account — a "no such email" error here would let anyone probe which
     * addresses are registered in a national cyber-security agency's own
     * system, which is a worse trade than a slightly less specific message.
     */
    public function sendResetLink(): void
    {
        $this->validate();

        Password::sendResetLink(['email' => $this->email]);

        $this->status = 'Jika email tersebut terdaftar, tautan atur ulang kata sandi sudah dikirim.';
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
