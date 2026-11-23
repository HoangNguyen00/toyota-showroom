<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * P2 đề xuất: quên mật khẩu qua Laravel Password Broker + bảng password_reset_tokens có sẵn.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const GENERIC = 'Nếu email này có tài khoản đang hoạt động, chúng tôi đã gửi liên kết đặt lại mật khẩu. Vui lòng kiểm tra hộp thư trong 60 phút.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function user(array $attributes = [], UserRole $role = UserRole::Customer): User
    {
        $user = User::factory()->create(['password' => 'old-password-123'] + $attributes);
        $user->forceFill(['role' => $role])->save();

        return $user->fresh();
    }

    public function test_login_links_to_forgot_password_and_forms_render(): void
    {
        $this->get('/dang-nhap')->assertOk()->assertSee(route('password.request'), false);
        $this->get('/quen-mat-khau')->assertOk()->assertSee('Quên mật khẩu')->assertSee('name="email"', false);
        $this->get('/dat-lai-mat-khau/some-token?email=a%40example.test')->assertOk()
            ->assertSee('name="token"', false)->assertSee('name="password_confirmation"', false);
    }

    public function test_response_is_identical_for_existing_missing_and_locked_accounts(): void
    {
        Notification::fake();
        $active = $this->user(['email' => 'active@example.test']);
        $locked = $this->user(['email' => 'locked@example.test', 'is_active' => false]);

        foreach (['active@example.test', 'missing@example.test', 'locked@example.test'] as $email) {
            $this->from('/quen-mat-khau')->post('/quen-mat-khau', ['email' => $email])
                ->assertRedirect('/quen-mat-khau')
                ->assertSessionHas('status', self::GENERIC)
                ->assertSessionHasNoErrors();
        }

        Notification::assertSentTo($active, ResetPasswordNotification::class);
        Notification::assertNotSentTo($locked, ResetPasswordNotification::class);
        Notification::assertCount(1);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'locked@example.test']);
    }

    public function test_requests_are_rate_limited_the_same_way_for_any_email(): void
    {
        Notification::fake();
        $this->user(['email' => 'active@example.test']);

        foreach (['active@example.test', 'missing@example.test'] as $email) {
            for ($i = 0; $i < 3; $i++) {
                $this->post('/quen-mat-khau', ['email' => $email])->assertSessionHas('status', self::GENERIC);
            }
            $this->post('/quen-mat-khau', ['email' => $email])
                ->assertSessionHasErrors(['email' => 'Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau ít phút.']);
        }
        // Broker cũng chỉ tạo 1 token/60 giây cho tài khoản thật; phản hồi công khai vẫn như cũ.
        Notification::assertCount(1);
    }

    public function test_reset_link_uses_app_url_and_token_is_stored_hashed(): void
    {
        Notification::fake();
        config(['app.url' => 'http://showroom.test']);
        $user = $this->user(['email' => 'link@example.test']);

        // Host header giả mạo không được lọt vào link trong email.
        $this->post('http://evil.test/quen-mat-khau', ['email' => 'link@example.test']);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user): bool {
            $mail = $notification->toMail($user);
            $stored = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');

            return str_starts_with($mail->actionUrl, 'http://showroom.test/dat-lai-mat-khau/')
                && str_contains($mail->actionUrl, 'email=link%40example.test')
                && $stored !== $notification->token
                && Hash::check($notification->token, $stored)
                && str_contains(implode(' ', $mail->outroLines), '60 phút');
        });
    }

    public function test_valid_token_resets_password_once_revokes_sessions_and_keeps_role_and_status(): void
    {
        $user = $this->user(['email' => 'reset@example.test'], UserRole::Sales);
        $oldRemember = $user->remember_token;
        DB::table('sessions')->insert([
            ['id' => 'old-session-1', 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'x', 'last_activity' => time()],
            ['id' => 'other-user', 'user_id' => null, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'x', 'last_activity' => time()],
        ]);
        $token = Password::broker()->createToken($user);

        $this->post('/dat-lai-mat-khau', [
            'token' => $token, 'email' => 'reset@example.test',
            'password' => 'brand-new-pass-456', 'password_confirmation' => 'brand-new-pass-456',
            'role' => 'admin', 'is_active' => '0',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-pass-456', $user->password));
        $this->assertSame(UserRole::Sales, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotSame($oldRemember, $user->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session-1']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-user']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'reset@example.test']);

        // Dùng lại token: bị từ chối, mật khẩu giữ nguyên.
        $this->post('/dat-lai-mat-khau', [
            'token' => $token, 'email' => 'reset@example.test',
            'password' => 'another-pass-789', 'password_confirmation' => 'another-pass-789',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('brand-new-pass-456', $user->fresh()->password));

        // Đăng nhập: mật khẩu mới được, mật khẩu cũ không.
        $this->post('/dang-nhap', ['email' => 'reset@example.test', 'password' => 'old-password-123'])->assertSessionHasErrors();
        $this->assertGuest();
        $this->post('/dang-nhap', ['email' => 'reset@example.test', 'password' => 'brand-new-pass-456'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_expired_or_mismatched_input_is_rejected_without_changing_password(): void
    {
        $user = $this->user(['email' => 'guard@example.test']);
        $token = Password::broker()->createToken($user);
        $payload = fn (array $overrides = []) => array_merge([
            'token' => $token, 'email' => 'guard@example.test',
            'password' => 'brand-new-pass-456', 'password_confirmation' => 'brand-new-pass-456',
        ], $overrides);

        $this->post('/dat-lai-mat-khau', $payload(['token' => 'wrong-token']))->assertSessionHasErrors('email');
        $this->post('/dat-lai-mat-khau', $payload(['password_confirmation' => 'different-456']))->assertSessionHasErrors('password');
        $this->post('/dat-lai-mat-khau', $payload(['password' => 'short', 'password_confirmation' => 'short']))->assertSessionHasErrors('password');
        $this->post('/dat-lai-mat-khau', $payload(['email' => 'missing@example.test']))->assertSessionHasErrors('email');

        $this->travel(61)->minutes();
        $this->post('/dat-lai-mat-khau', $payload())->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
    }

    public function test_locked_account_cannot_reset_even_with_a_valid_token(): void
    {
        $user = $this->user(['email' => 'was-active@example.test']);
        $token = Password::broker()->createToken($user);
        $user->forceFill(['is_active' => false])->save();

        $this->post('/dat-lai-mat-khau', [
            'token' => $token, 'email' => 'was-active@example.test',
            'password' => 'brand-new-pass-456', 'password_confirmation' => 'brand-new-pass-456',
        ])->assertSessionHasErrors('email');

        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertTrue(Hash::check('old-password-123', $user->password));
    }

    public function test_array_email_returns_validation_errors_instead_of_failing(): void
    {
        Notification::fake();
        $user = $this->user(['email' => 'array@example.test']);
        $token = Password::broker()->createToken($user);

        $this->from('/quen-mat-khau')->post('/quen-mat-khau', ['email' => ['array@example.test']])
            ->assertRedirect('/quen-mat-khau')->assertSessionHasErrors('email');
        $this->from('/dat-lai-mat-khau/'.$token)->post('/dat-lai-mat-khau', [
            'token' => $token, 'email' => ['array@example.test'],
            'password' => 'brand-new-pass-456', 'password_confirmation' => 'brand-new-pass-456',
        ])->assertRedirect('/dat-lai-mat-khau/'.$token)->assertSessionHasErrors('email');

        Notification::assertNothingSent();
        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'array@example.test']);
    }

    public function test_token_is_checked_and_consumed_inside_one_transaction_holding_the_user_lock(): void
    {
        $user = $this->user(['email' => 'race@example.test']);
        $token = Password::broker()->createToken($user);
        $baseLevel = DB::transactionLevel();
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = ['sql' => str_replace(['"', '`'], '', strtolower($query->sql)), 'level' => DB::transactionLevel()];
        });

        $this->post('/dat-lai-mat-khau', [
            'token' => $token, 'email' => 'race@example.test',
            'password' => 'brand-new-pass-456', 'password_confirmation' => 'brand-new-pass-456',
        ])->assertRedirect(route('login'));

        $find = function (string $needle) use ($queries): int {
            foreach ($queries as $index => $query) {
                if (str_contains($query['sql'], $needle)) {
                    return $index;
                }
            }
            $this->fail("Missing query: {$needle}");
        };
        $lock = $find('from users');
        $check = $find('select * from password_reset_tokens');
        $consume = $find('delete from password_reset_tokens');

        // Khóa user trước, rồi kiểm tra và xóa token trong cùng transaction: yêu cầu thứ hai chờ khóa
        // và chỉ thấy token sau khi yêu cầu thứ nhất đã xóa nó.
        $this->assertTrue($lock < $check && $check < $consume);
        foreach ([$lock, $check, $consume] as $index) {
            $this->assertGreaterThan($baseLevel, $queries[$index]['level']);
        }
        if (DB::connection()->getDriverName() === 'mysql') {
            $this->assertStringContainsString('for update', $queries[$lock]['sql']);
        }
    }

    public function test_failed_password_write_rolls_back_and_keeps_the_token(): void
    {
        $user = $this->user(['email' => 'rollback@example.test']);
        $token = Password::broker()->createToken($user);
        User::saving(fn () => throw new \RuntimeException('write failed'));

        $this->withoutExceptionHandling();
        try {
            $this->post('/dat-lai-mat-khau', [
                'token' => $token, 'email' => 'rollback@example.test',
                'password' => 'brand-new-pass-456', 'password_confirmation' => 'brand-new-pass-456',
            ]);
            $this->fail('Expected the write failure to propagate.');
        } catch (\RuntimeException $e) {
            $this->assertSame('write failed', $e->getMessage());
        }

        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
        $this->assertTrue(Password::broker()->tokenExists($user->fresh(), $token));
    }

    public function test_signed_in_users_are_redirected_away_from_reset_pages(): void
    {
        $this->actingAs($this->user())->get('/quen-mat-khau')->assertRedirect();
    }
}
