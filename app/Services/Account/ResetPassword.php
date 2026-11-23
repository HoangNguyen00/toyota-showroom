<?php

namespace App\Services\Account;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * P2 đề xuất — đặt lại mật khẩu bằng token của Password Broker.
 *
 * Broker::reset() kiểm tra token trước callback và xóa token sau callback, không có khóa chung,
 * nên hai yêu cầu đồng thời có thể cùng qua bước kiểm tra. Ở đây khóa hàng users trước, rồi kiểm tra,
 * ghi mật khẩu, thu hồi phiên và xóa token trong cùng một transaction: yêu cầu thứ hai chờ khóa và
 * chỉ thấy token sau khi yêu cầu thứ nhất đã xóa nó.
 */
class ResetPassword
{
    /** Giống thời lượng timebox mặc định của PasswordBroker (micro giây). */
    private const TIMEBOX_MICROSECONDS = 200_000;

    public function handle(string $email, #[\SensitiveParameter] string $token, #[\SensitiveParameter] string $password): bool
    {
        $broker = Password::broker();

        // Timebox như broker: thời gian phản hồi thất bại không lộ email có tài khoản hay không.
        $user = $broker->getTimebox()->call(function ($timebox) use ($broker, $email, $token, $password): ?User {
            $user = DB::transaction(function () use ($broker, $email, $token, $password): ?User {
                $user = User::query()->where('email', $email)->lockForUpdate()->first();

                // Tài khoản khóa không đặt lại được; không mở khóa hay đổi role.
                if (! $user || ! $user->is_active || ! $broker->tokenExists($user, $token)) {
                    return null;
                }

                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
                $broker->deleteToken($user);

                return $user;
            }, 5);

            if ($user) {
                $timebox->returnEarly();
            }

            return $user;
        }, self::TIMEBOX_MICROSECONDS);

        if (! $user) {
            return false;
        }

        event(new PasswordReset($user));

        return true;
    }
}
