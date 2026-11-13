<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class Login extends Component
{
    public string $login = '';

    public string $password = '';

    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->validate();

        $throttleKey = Str::transliterate(Str::lower($this->login) . '|' . request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->addError('login', "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.");

            return;
        }

        // Accepts either the email or the shorter username in the same
        // field — whichever it looks like determines which column Auth
        // checks against.
        //
        // Keyboard HP sering membuat huruf pertama kapital ("Admin.risiko")
        // atau menambah spasi di ujung, sedangkan pencocokan di Postgres
        // membedakan huruf besar/kecil — jadi cari nilai yang tersimpan tanpa
        // memedulikan huruf besar/kecil, lalu pakai nilai itu untuk login.
        $login = trim($this->login);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $stored = User::whereRaw("lower({$field}) = ?", [Str::lower($login)])->value($field) ?? $login;

        if (! Auth::attempt([$field => $stored, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);

            $this->addError('login', 'Email/username atau kata sandi salah.');

            return;
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
