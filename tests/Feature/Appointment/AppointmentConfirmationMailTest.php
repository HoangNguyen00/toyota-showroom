<?php

namespace Tests\Feature\Appointment;

use App\Enums\AppointmentStatus;
use App\Enums\CarStatus;
use App\Enums\UserRole;
use App\Jobs\SendAppointmentConfirmation;
use App\Mail\AppointmentConfirmedMail;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Car;
use App\Models\Service;
use App\Models\User;
use App\Services\Appointment\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Testing\Fakes\MailFake;
use RuntimeException;
use Tests\TestCase;

/**
 * P2 đề xuất: email xác nhận lịch, gửi sau khi transaction confirm commit, chống gửi trùng.
 */
class AppointmentConfirmationMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withoutMiddleware([ThrottleRequests::class]);
        config(['queue.default' => 'sync']);
    }

    private function user(UserRole $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function pendingViewing(?User $customer, User $sales): Appointment
    {
        $car = Car::factory()->create(['name' => 'Toyota Vios 1.5G', 'code' => 'VIOS-MAIL-01', 'status' => CarStatus::Available]);

        return Appointment::create([
            'type' => 'viewing', 'car_id' => $car->id, 'customer_id' => $customer?->id,
            'contact_name' => 'Khách Demo', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDay(), 'status' => 'pending', 'assigned_to' => $sales->id,
        ]);
    }

    private function confirmAt(User $actor, Appointment $appointment, Carbon $time)
    {
        return $this->actingAs($actor)->post("/staff/appointments/{$appointment->id}/confirm", [
            'confirmed_at' => $time->format('Y-m-d H:i'),
        ]);
    }

    public function test_confirming_sends_one_mail_to_customer_account_email_after_commit(): void
    {
        Mail::fake();
        $customer = $this->user(UserRole::Customer, ['email' => 'khach@example.test']);
        $sales = $this->user(UserRole::Sales);
        $appointment = $this->pendingViewing($customer, $sales);
        $time = now()->addDays(2)->setTime(9, 30);

        $this->confirmAt($sales, $appointment, $time)->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
        Mail::assertSent(AppointmentConfirmedMail::class, 1);
        Mail::assertSent(AppointmentConfirmedMail::class, function (AppointmentConfirmedMail $mail) use ($time): bool {
            $html = $mail->render();

            return $mail->hasTo('khach@example.test')
                && str_contains($html, $time->copy()->timezone('Asia/Ho_Chi_Minh')->format('H:i'))
                && str_contains($html, $time->format('d/m/Y'))
                && str_contains($html, 'Toyota Vios 1.5G')
                && str_contains($html, 'VIOS-MAIL-01')
                && str_contains($html, config('showroom.contact.address'))
                && ! str_contains($html, '0901234567');
        });
        $this->assertSame(1, ActivityLog::where('action', 'appointment.confirmation_mail.sent')
            ->where('target_id', $appointment->id)->count());
    }

    public function test_maintenance_mail_names_the_service(): void
    {
        Mail::fake();
        $customer = $this->user(UserRole::Customer, ['email' => 'baoduong@example.test']);
        $sales = $this->user(UserRole::Sales);
        $service = Service::create([
            'name' => 'Bảo dưỡng định kỳ 10.000 km', 'slug' => 'bao-duong-10000', 'type' => 'maintenance', 'description' => 'Demo',
            'is_visible' => true, 'allow_booking' => true,
        ]);
        $appointment = Appointment::create([
            'type' => 'maintenance', 'service_id' => $service->id, 'customer_id' => $customer->id,
            'customer_car_info' => 'Vios 2020', 'contact_name' => 'Khách', 'contact_phone' => '0901234567',
            'requested_at' => now()->addDay(), 'status' => 'pending', 'assigned_to' => $sales->id,
        ]);

        $this->confirmAt($sales, $appointment, now()->addDays(2)->setTime(14, 0))->assertSessionHasNoErrors();

        Mail::assertSent(AppointmentConfirmedMail::class, fn ($mail) => str_contains($mail->render(), 'Bảo dưỡng định kỳ 10.000 km'));
    }

    public function test_pending_or_failed_confirmation_sends_nothing(): void
    {
        Queue::fake();
        $customer = $this->user(UserRole::Customer);
        $sales = $this->user(UserRole::Sales);
        $appointment = $this->pendingViewing($customer, $sales);

        // Giờ trong quá khứ: service ném lỗi trước khi ghi → không dispatch.
        $this->confirmAt($sales, $appointment, now()->subHour())->assertSessionHasErrors();
        // Sales khác: Policy chặn.
        $this->confirmAt($this->user(UserRole::Sales), $appointment, now()->addDay())->assertForbidden();

        $this->assertSame(AppointmentStatus::Pending, $appointment->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_rolled_back_transaction_does_not_send(): void
    {
        Mail::fake();
        $customer = $this->user(UserRole::Customer);
        $sales = $this->user(UserRole::Sales);
        $appointment = $this->pendingViewing($customer, $sales);

        try {
            DB::transaction(function () use ($sales, $appointment): void {
                app(AppointmentService::class)->confirm($sales, $appointment, now()->addDays(2)->format('Y-m-d H:i:s'));
                throw new RuntimeException('rollback after confirm');
            });
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(AppointmentStatus::Pending, $appointment->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_repeated_confirmation_and_duplicate_job_send_only_once(): void
    {
        Mail::fake();
        $customer = $this->user(UserRole::Customer);
        $sales = $this->user(UserRole::Sales);
        $appointment = $this->pendingViewing($customer, $sales);
        $time = now()->addDays(2)->setTime(10, 0);

        $this->confirmAt($sales, $appointment, $time)->assertSessionHasNoErrors();
        // Bấm xác nhận lần nữa: lịch không còn pending nên bị từ chối, không gửi thêm.
        $this->confirmAt($sales, $appointment, $time)->assertForbidden();
        // Job bị đưa lại hàng đợi (retry/at-least-once): bản ghi "sent" chặn gửi lặp.
        (new SendAppointmentConfirmation($appointment->id, $appointment->fresh()->confirmed_at->getTimestamp()))->handle();

        Mail::assertSent(AppointmentConfirmedMail::class, 1);
        $this->assertSame(1, ActivityLog::where('action', 'appointment.confirmation_mail.sent')->count());
    }

    public function test_guest_without_account_email_is_skipped_and_recorded(): void
    {
        Mail::fake();
        $sales = $this->user(UserRole::Sales);
        $appointment = $this->pendingViewing(null, $sales);

        $this->confirmAt($sales, $appointment, now()->addDays(2))->assertSessionHasNoErrors();

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
        Mail::assertNothingSent();
        $log = ActivityLog::where('action', 'appointment.confirmation_mail.skipped')->sole();
        $this->assertSame('no_account_email', $log->payload['reason']);
    }

    public function test_stale_job_after_cancel_or_reconfirm_sends_nothing(): void
    {
        Mail::fake();
        $customer = $this->user(UserRole::Customer);
        $sales = $this->user(UserRole::Sales);
        $appointment = $this->pendingViewing($customer, $sales);
        $appointment->forceFill(['status' => AppointmentStatus::Confirmed, 'confirmed_at' => now()->addDays(2)])->save();
        $staleTimestamp = $appointment->confirmed_at->getTimestamp() - 3600;

        (new SendAppointmentConfirmation($appointment->id, $staleTimestamp))->handle();
        $appointment->forceFill(['status' => AppointmentStatus::Cancelled])->save();
        (new SendAppointmentConfirmation($appointment->id, $appointment->confirmed_at->getTimestamp()))->handle();
        (new SendAppointmentConfirmation(999999, time()))->handle();

        Mail::assertNothingSent();
    }

    public function test_mail_failure_keeps_appointment_confirmed_records_failure_and_retry_sends_once(): void
    {
        $customer = $this->user(UserRole::Customer, ['email' => 'loi@example.test']);
        $sales = $this->user(UserRole::Sales);
        $appointment = $this->pendingViewing($customer, $sales);
        $appointment->forceFill(['status' => AppointmentStatus::Confirmed, 'confirmed_at' => now()->addDays(2)])->save();
        $job = new SendAppointmentConfirmation($appointment->id, $appointment->confirmed_at->getTimestamp());

        $realManager = app('mail.manager');
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP down'));
        try {
            $job->handle();
            $this->fail('Transport error must bubble up so the queue retries.');
        } catch (RuntimeException $error) {
            $job->failed($error);
        }

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
        $failure = ActivityLog::where('action', 'appointment.confirmation_mail.failed')->sole();
        $this->assertSame(RuntimeException::class, $failure->payload['error']);
        $this->assertStringNotContainsString('loi@example.test', json_encode($failure->payload));

        // Lần retry: nhà cung cấp hoạt động lại → gửi đúng một thư, lần chạy lặp bị chặn.
        Mail::swap(new MailFake($realManager));
        $job->handle();
        $job->handle();
        Mail::assertSent(AppointmentConfirmedMail::class, 1);
    }

    public function test_enqueue_failure_after_commit_keeps_confirm_response_successful_and_records_failure(): void
    {
        Mail::fake();
        Log::spy();
        // Hàng đợi không đẩy được (kết nối DB của queue không tồn tại): lỗi xảy ra sau khi lịch đã commit.
        config([
            'queue.connections.broken' => ['driver' => 'database', 'connection' => 'missing-queue-db', 'table' => 'jobs', 'queue' => 'default'],
            'queue.default' => 'broken',
        ]);
        $customer = $this->user(UserRole::Customer, ['email' => 'hang-doi@example.test']);
        $sales = $this->user(UserRole::Sales);
        $appointment = $this->pendingViewing($customer, $sales);

        $this->confirmAt($sales, $appointment, now()->addDays(2)->setTime(10, 0))
            ->assertRedirect()->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Đã xác nhận lịch hẹn.');

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        Mail::assertNothingSent();
        $failure = ActivityLog::where('action', SendAppointmentConfirmation::FAILED)->where('target_id', $appointment->id)->sole();
        $this->assertSame('enqueue_failed', $failure->payload['reason']);
        $this->assertSame($appointment->confirmed_at->getTimestamp(), $failure->payload['confirmed_at']);
        $this->assertStringNotContainsString('hang-doi@example.test', json_encode($failure->payload));
        Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context): bool => $message === 'Appointment confirmation mail could not be queued'
            && $context['appointment_id'] === $appointment->id
            && ! str_contains(json_encode($context), 'hang-doi@example.test'))->once();

        // Khóa unique đã nhả: khi hàng đợi hoạt động lại, xếp lại đúng mốc xác nhận vẫn gửi được một thư.
        config(['queue.default' => 'sync']);
        SendAppointmentConfirmation::queueAfterCommit($appointment->id, $appointment->confirmed_at->getTimestamp());
        Mail::assertSent(AppointmentConfirmedMail::class, 1);
    }

    public function test_job_payload_has_ids_only_and_is_unique_per_confirmation(): void
    {
        $job = new SendAppointmentConfirmation(42, 1_800_000_000);

        $this->assertSame('appointment-confirmation:42:1800000000', $job->uniqueId());
        // Hàng đợi chỉ giữ ID + mốc xác nhận, không giữ email/điện thoại khách.
        $this->assertSame(42, $job->appointmentId);
        $this->assertSame(1_800_000_000, $job->confirmedAt);
        $this->assertStringNotContainsString('@', serialize($job));
        $this->assertGreaterThanOrEqual(3, $job->tries);
    }
}
