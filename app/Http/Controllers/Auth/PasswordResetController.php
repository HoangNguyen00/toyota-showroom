<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\Account\ResetPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * P2 đề xuất — Quên mật khẩu bằng Laravel Password Broker (bảng password_reset_tokens có sẵn).
 * Phản hồi công khai giống nhau cho email có/không có tài khoản hoặc tài khoản bị khóa.
 */
class PasswordResetController extends Controller
{
    public const GENERIC_SENT = 'Nếu email này có tài khoản đang hoạt động, chúng tôi đã gửi liên kết đặt lại mật khẩu. Vui lòng kiểm tra hộp thư trong 60 phút.';

    private const INVALID_LINK = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu liên kết mới.';

    private const MAX_REQUESTS = 3;

    private const DECAY_SECONDS = 600;

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        // Chỉ chuẩn hóa chuỗi; mảng/kiểu khác để rule 'string' trả lỗi validation thay vì lỗi ép kiểu.
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }
        $request->validate(
            ['email' => ['required', 'string', 'email', 'max:255']],
            ['email.required' => 'Vui lòng nhập email.', 'email.email' => 'Email không hợp lệ.'],
        );
        $email = $request->string('email')->toString();

        // Giới hạn theo IP + email, áp dụng y hệt cho email có hay không có tài khoản.
        $key = 'password-reset:'.sha1($request->ip().'|'.$email);
        if (RateLimiter::tooManyAttempts($key, self::MAX_REQUESTS)) {
            throw ValidationException::withMessages(['email' => 'Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau ít phút.']);
        }
        RateLimiter::hit($key, self::DECAY_SECONDS);

        // Tài khoản bị khóa không nhận liên kết; kết quả broker (gửi/không có user/throttle) không lộ ra ngoài.
        $user = User::query()->where('email', $email)->first();
        if ($user?->is_active) {
            Password::broker()->sendResetLink(['email' => $email]);
        }

        return back()->with('status', self::GENERIC_SENT);
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => is_string($request->query('email')) ? $request->query('email') : '',
        ]);
    }

    public function update(ResetPasswordRequest $request, ResetPassword $resetPassword): RedirectResponse
    {
        $data = $request->validated();

        // Kiểm tra + tiêu thụ token, ghi mật khẩu và thu hồi phiên trong một transaction có khóa user.
        if (! $resetPassword->handle($data['email'], $data['token'], $data['password'])) {
            throw ValidationException::withMessages(['email' => self::INVALID_LINK]);
        }

        return redirect()->route('login')->with('status', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập bằng mật khẩu mới.');
    }
}
