<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
{
    use Queueable;

    /**
     * The reset token.
     *
     * @var string
     */
    protected $resetToken;

    /**
     * The recipient email.
     *
     * @var string
     */
    protected $recipientEmail;

    /**
     * Create a new notification instance.
     *
     * @param string $resetToken
     * @param string $recipientEmail
     */
    public function __construct(string $resetToken, string $recipientEmail)
    {
        $this->resetToken = $resetToken;
        $this->recipientEmail = $recipientEmail;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->line('Your password reset token is: ' . $this->resetToken)
            ->line('If you did not request a password reset, no further action is required.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [];
    }
}
