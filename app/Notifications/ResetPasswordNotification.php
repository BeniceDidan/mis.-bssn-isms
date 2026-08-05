<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Replaces Laravel's default Illuminate\Auth\Notifications\ResetPassword
 * (which renders via the Markdown mail theme, pulling in league/commonmark)
 * with a plain Blade view. The Markdown render path was timing out
 * (Maximum execution time exceeded inside CommonMark's ListBlockRenderer)
 * on this environment — a plain view has no Markdown parsing in it at all,
 * so there's nothing left that can hang there.
 */
class ResetPasswordNotification extends Notification
{
    public function __construct(private readonly string $token) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Atur Ulang Kata Sandi — BSSN ISMS')
            ->view('emails.reset-password', ['url' => $url]);
    }
}
