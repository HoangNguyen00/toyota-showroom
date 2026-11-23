<?php

namespace App\Jobs;

use App\Enums\AppointmentStatus;
use App\Mail\AppointmentConfirmedMail;
use App\Models\ActivityLog;
use App\Models\Appointment;
use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * P2 đề xuất — gửi email xác nhận lịch sau khi transaction confirm đã commit.
 *
 * Chống gửi trùng: (1) ShouldBeUnique theo appointment + mốc confirmed_at khi xếp hàng;
 * (2) khóa cache quanh kiểm-gửi-ghi; (3) activity_logs "sent" cho đúng mốc confirmed_at
 * được kiểm trước khi gửi. Giới hạn còn lại: nếu SMTP đã nhận thư nhưng tiến trình chết
 * trước khi ghi "sent", lần retry sẽ gửi lại (at-least-once), không thể loại trừ ở tầng ứng dụng.
 */
class SendAppointmentConfirmation implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public const SENT = 'appointment.confirmation_mail.sent';

    public const SKIPPED = 'appointment.confirmation_mail.skipped';

    public const FAILED = 'appointment.confirmation_mail.failed';

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public int $uniqueFor = 3600;

    // Payload chỉ gồm ID và mốc xác nhận; email/điện thoại đọc lại từ DB khi chạy.
    public function __construct(public int $appointmentId, public int $confirmedAt) {}

    /**
     * Xếp hàng sau khi transaction xác nhận commit. Lúc này lịch đã lưu là confirmed, nên lỗi đưa vào
     * hàng đợi (queue/cache hỏng) không được làm thao tác xác nhận trả về thất bại: ghi nhận lỗi không kèm
     * PII, nhả khóa unique để lần xếp hàng sau vẫn chạy được, rồi để request trả về bình thường.
     */
    public static function queueAfterCommit(int $appointmentId, int $confirmedAt): void
    {
        DB::afterCommit(function () use ($appointmentId, $confirmedAt): void {
            try {
                static::dispatch($appointmentId, $confirmedAt);
            } catch (Throwable $error) {
                (new static($appointmentId, $confirmedAt))->recordEnqueueFailure($error);
            }
        });
    }

    public function uniqueId(): string
    {
        return 'appointment-confirmation:'.$this->appointmentId.':'.$this->confirmedAt;
    }

    public function handle(): void
    {
        $lock = Cache::lock($this->uniqueId().':send', 120);
        if (! $lock->get()) {
            // Một worker khác đang gửi đúng thư này; thử lại sau thay vì gửi song song.
            $this->release(30);

            return;
        }

        try {
            $this->send();
        } finally {
            $lock->release();
        }
    }

    private function send(): void
    {
        $appointment = Appointment::query()->with(['customer', 'car', 'service'])->find($this->appointmentId);
        // Lịch đã hủy/đổi mốc xác nhận sau khi xếp hàng: thư cũ không còn đúng, bỏ qua.
        if (! $appointment || $appointment->status !== AppointmentStatus::Confirmed
            || $appointment->confirmed_at?->getTimestamp() !== $this->confirmedAt) {
            return;
        }

        if ($this->alreadyLogged(self::SENT) || $this->alreadyLogged(self::SKIPPED)) {
            return;
        }

        $email = $appointment->customer?->email;
        if ($appointment->customer === null || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Khách vãng lai: form lịch không thu email, không đoán theo số điện thoại.
            $this->record(self::SKIPPED, ['reason' => 'no_account_email']);

            return;
        }

        Mail::to($email)->send(new AppointmentConfirmedMail($appointment));
        $this->record(self::SENT);
    }

    public function failed(?Throwable $error): void
    {
        $this->record(self::FAILED, ['error' => $error ? $error::class : null]);
        Log::warning('Appointment confirmation mail failed', [
            'appointment_id' => $this->appointmentId,
            'error' => $error ? $error::class : null,
        ]);
    }

    private function recordEnqueueFailure(Throwable $error): void
    {
        Log::warning('Appointment confirmation mail could not be queued', [
            'appointment_id' => $this->appointmentId,
            'error' => $error::class,
        ]);

        // Mỗi bước dọn dẹp tự bắt lỗi: hệ thống hỏng thêm cũng không biến xác nhận đã lưu thành lỗi 500.
        try {
            (new UniqueLock(app(CacheRepository::class)))->release($this);
        } catch (Throwable) {
        }
        try {
            $this->record(self::FAILED, ['reason' => 'enqueue_failed', 'error' => $error::class]);
        } catch (Throwable) {
        }
    }

    private function alreadyLogged(string $action): bool
    {
        return ActivityLog::query()
            ->where('action', $action)
            ->where('target_type', Appointment::class)
            ->where('target_id', $this->appointmentId)
            ->get(['payload'])
            ->contains(fn (ActivityLog $log): bool => ($log->payload['confirmed_at'] ?? null) === $this->confirmedAt);
    }

    private function record(string $action, array $payload = []): void
    {
        ActivityLog::create([
            'action' => $action,
            'target_type' => Appointment::class,
            'target_id' => $this->appointmentId,
            'payload' => ['confirmed_at' => $this->confirmedAt] + $payload,
        ]);
    }
}
