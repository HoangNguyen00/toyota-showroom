<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * P2 đề xuất — email đặt lại mật khẩu tiếng Việt. Gửi đồng bộ (không xếp hàng) để token
 * thô không nằm trong bảng jobs; DB chỉ lưu bản băm trong password_reset_tokens.
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Đặt lại mật khẩu — '.config('app.name'))
            ->greeting('Xin chào '.$notifiable->name.',')
            ->line('Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.')
            ->action('Đặt lại mật khẩu', $this->resetUrl($notifiable))
            ->line('Liên kết có hiệu lực trong '.$minutes.' phút và chỉ dùng được một lần.')
            ->line('Nếu bạn không yêu cầu, hãy bỏ qua email này; mật khẩu hiện tại vẫn giữ nguyên.')
            ->salutation('Toyota Showroom (đồ án môn học)');
    }

    /**
     * Link luôn dựng từ APP_URL, không từ Host header của request (chống password reset poisoning).
     */
    protected function resetUrl($notifiable): string
    {
        return rtrim((string) config('app.url'), '/').route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false);
    }
}
