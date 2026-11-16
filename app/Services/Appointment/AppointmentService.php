<?php

namespace App\Services\Appointment;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Car;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    /**
     * Quy ước demo: một sales chỉ nhận 1 lịch confirmed trong cửa sổ 60 phút.
     * Cách đúng 60 phút là hợp lệ; chênh < 60 phút bị chặn.
     */
    public const SLOT_MINUTES = 60;

    /**
     * Khóa các hàng appointment liên quan theo ID tăng dần để tránh deadlock,
     * rồi kiểm tra overlap < 60 phút giữa các lịch confirmed của cùng sales.
     */
    private function assertNoSlotOverlap(int $staffId, string $confirmedAt, ?int $ignoreId = null): void
    {
        $windowStart = Carbon::parse($confirmedAt)->subMinutes(self::SLOT_MINUTES);
        $windowEnd = Carbon::parse($confirmedAt)->addMinutes(self::SLOT_MINUTES);

        $query = Appointment::query()
            ->where('assigned_to', $staffId)
            ->where('status', AppointmentStatus::Confirmed->value)
            ->where('confirmed_at', '>=', $windowStart)
            ->where('confirmed_at', '<=', $windowEnd)
            ->orderBy('id');
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        // FOR UPDATE: giữ lock tới khi transaction ngoài commit.
        $conflicts = $query->lockForUpdate()->get();

        foreach ($conflicts as $conflict) {
            $minutes = $conflict->confirmed_at->diffInMinutes(Carbon::parse($confirmedAt));
            if ($minutes <= self::SLOT_MINUTES) {
                throw ValidationException::withMessages([
                    'assigned_to' => 'Nhân viên này đã có lịch xác nhận trong vòng 60 phút. Vui lòng chọn thời gian hoặc nhân viên khác.',
                ]);
            }
        }
    }

    /**
     * Chặn đặt/đổi trùng khung giờ (±60 phút) với lịch hiện có của cùng người —
     * đối chiếu customer_id (đã đăng nhập) hoặc contact_phone (cả guest),
     * tránh tạo lịch trùng rồi phải hủy.
     */
    public function assertNoCustomerOverlap(?int $customerId, string $requestedAt, ?string $phone = null, ?int $ignoreId = null): void
    {
        if ($customerId === null && ($phone === null || trim($phone) === '')) {
            return; // Không có danh tính nào để đối chiếu.
        }

        $time = Carbon::parse($requestedAt);
        $conflicts = Appointment::query()
            ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value])
            ->where('requested_at', '>=', $time->copy()->subMinutes(self::SLOT_MINUTES))
            ->where('requested_at', '<=', $time->copy()->addMinutes(self::SLOT_MINUTES))
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where(function ($q) use ($customerId, $phone): void {
                if ($customerId !== null) {
                    $q->where('customer_id', $customerId);
                }
                if ($phone !== null && trim($phone) !== '') {
                    $q->orWhere('contact_phone', trim($phone));
                }
            })
            ->get();

        foreach ($conflicts as $conflict) {
            if ($conflict->requested_at->diffInMinutes($time) <= self::SLOT_MINUTES) {
                throw ValidationException::withMessages([
                    'requested_at' => 'Số này đã có lịch hẹn trong vòng 60 phút ('.$conflict->requested_at->format('H:i d/m').'). Vui lòng chọn cách hơn 1 tiếng hoặc hủy lịch cũ trước.',
                ]);
            }
        }
    }

    /**
     * Admin phân công sales cho một lịch pending.
     */
    public function assign(User $actor, Appointment $appointment, ?int $staffId): Appointment
    {
        return DB::transaction(function () use ($appointment, $staffId): Appointment {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== AppointmentStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ lịch pending mới được phân công.',
                ]);
            }

            if ($staffId !== null) {
                $staff = User::query()->whereKey($staffId)->lockForUpdate()->first();
                if (! $staff || $staff->role !== UserRole::Sales || ! $staff->is_active) {
                    throw ValidationException::withMessages([
                        'assigned_to' => 'Nhân viên phụ trách không hợp lệ hoặc đã bị khóa.',
                    ]);
                }
            }

            $locked->assigned_to = $staffId;
            $locked->save();

            return $locked;
        });
    }

    /**
     * Xác nhận lịch: khóa appointment rồi sales, đọc lại pending và quyền,
     * kiểm tra giờ/xe/service và overlap < 60 phút rồi ghi.
     */
    public function confirm(User $actor, Appointment $appointment, string $confirmedAt): Appointment
    {
        return DB::transaction(function () use ($actor, $appointment, $confirmedAt): Appointment {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== AppointmentStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ lịch pending mới được xác nhận.',
                ]);
            }

            if ($actor->role === UserRole::Sales && $locked->assigned_to !== $actor->id) {
                throw ValidationException::withMessages([
                    'status' => 'Bạn không được giao lịch này.',
                ]);
            }

            $target = Carbon::parse($confirmedAt);
            if ($target->isPast()) {
                throw ValidationException::withMessages([
                    'confirmed_at' => 'Không thể xác nhận lịch vào thời điểm trong quá khứ.',
                ]);
            }

            $staffId = $locked->assigned_to;
            if ($staffId === null) {
                throw ValidationException::withMessages([
                    'assigned_to' => 'Lịch chưa được phân công nhân viên phụ trách.',
                ]);
            }

            $staff = User::query()->whereKey($staffId)->lockForUpdate()->first();
            if (! $staff || $staff->role !== UserRole::Sales || ! $staff->is_active) {
                throw ValidationException::withMessages([
                    'assigned_to' => 'Nhân viên phụ trách không hợp lệ hoặc đã bị khóa.',
                ]);
            }

            // Khóa sales rồi đọc lại lịch confirmed cùng sales để kiểm overlap.
            $this->assertNoSlotOverlap($staffId, $target->format('Y-m-d H:i:s'));

            if ($locked->type === AppointmentType::Viewing && $locked->car_id !== null) {
                $car = Car::query()->find($locked->car_id);
                if (! $car || ! $car->is_visible) {
                    throw ValidationException::withMessages([
                        'car_id' => 'Xe không còn hiển thị, không thể xác nhận lịch.',
                    ]);
                }
            }

            if ($locked->type === AppointmentType::Maintenance && $locked->service_id !== null) {
                $service = Service::query()->find($locked->service_id);
                if (! $service || ! $service->is_visible || ! $service->allow_booking) {
                    throw ValidationException::withMessages([
                        'service_id' => 'Dịch vụ không còn nhận đặt lịch, không thể xác nhận.',
                    ]);
                }
            }

            $locked->status = AppointmentStatus::Confirmed;
            $locked->confirmed_at = $target;
            $locked->save();

            return $locked;
        });
    }

    /**
     * Customer hủy lịch của mình còn pending/confirmed và chưa tới giờ.
     */
    public function cancelByCustomer(User $actor, Appointment $appointment, string $reason): Appointment
    {
        return DB::transaction(function () use ($actor, $appointment, $reason): Appointment {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($locked->customer_id !== $actor->id) {
                abort(403);
            }

            if (! in_array($locked->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ lịch pending hoặc confirmed mới hủy được.',
                ]);
            }

            $now = now();
            $reference = $locked->confirmed_at ?? $locked->requested_at;
            if ($reference !== null && $reference->lessThanOrEqualTo($now)) {
                throw ValidationException::withMessages([
                    'status' => 'Lịch đã qua thời gian hẹn, không thể hủy.',
                ]);
            }

            $locked->status = AppointmentStatus::Cancelled;
            $locked->cancel_reason = mb_substr($reason, 0, 500);
            $locked->save();

            return $locked;
        });
    }

    /**
     * Customer đổi lịch của mình: chỉ pending và chưa tới giờ, giữ trạng thái pending.
     */
    public function rescheduleByCustomer(User $actor, Appointment $appointment, string $requestedAt): Appointment
    {
        return DB::transaction(function () use ($actor, $appointment, $requestedAt): Appointment {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($locked->customer_id !== $actor->id) {
                abort(403);
            }

            if ($locked->status !== AppointmentStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ lịch pending mới đổi được. Vui lòng hủy và đặt lại lịch đã xác nhận.',
                ]);
            }

            $reference = $locked->confirmed_at ?? $locked->requested_at;
            if ($reference !== null && $reference->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'status' => 'Lịch đã qua thời gian hẹn, không thể đổi.',
                ]);
            }

            $newTime = Carbon::parse($requestedAt);
            $this->assertNoCustomerOverlap($actor->id, $newTime->format('Y-m-d H:i:s'), $locked->contact_phone, $locked->id);

            $locked->requested_at = $newTime;
            $locked->save();

            return $locked;
        });
    }

    /**
     * Nhân viên/admin đổi thời gian lịch pending của mình (sales) hoặc bất kỳ (admin).
     * Lịch confirmed phải hủy rồi đặt lại để đảm bảo kiểm tra slot lại từ đầu.
     */
    public function rescheduleByStaff(User $actor, Appointment $appointment, string $requestedAt): Appointment
    {
        return DB::transaction(function () use ($actor, $appointment, $requestedAt): Appointment {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($actor->role === UserRole::Sales && $locked->assigned_to !== $actor->id) {
                abort(403);
            }

            if ($locked->status !== AppointmentStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ lịch pending mới đổi được. Vui lòng hủy lịch confirmed rồi đặt lại.',
                ]);
            }

            $newTime = Carbon::parse($requestedAt);
            $this->assertNoCustomerOverlap($locked->customer_id, $newTime->format('Y-m-d H:i:s'), $locked->id);

            $locked->requested_at = $newTime;
            $locked->save();

            return $locked;
        });
    }

    /**
     * Nhân viên/admin hủy kèm lý do; lịch confirmed đã tới giờ vẫn hủy được (mất khách).
     */
    public function cancelByStaff(User $actor, Appointment $appointment, string $reason): Appointment
    {
        return DB::transaction(function () use ($actor, $appointment, $reason): Appointment {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($actor->role === UserRole::Sales && $locked->assigned_to !== $actor->id) {
                abort(403);
            }

            if (! in_array($locked->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ lịch pending hoặc confirmed mới hủy được.',
                ]);
            }

            $locked->status = AppointmentStatus::Cancelled;
            $locked->cancel_reason = mb_substr($reason, 0, 500);
            $locked->save();

            return $locked;
        });
    }

    /**
     * Hoàn tất hoặc no_show: chỉ sau giờ confirmed, không sửa trạng thái cuối.
     */
    public function conclude(User $actor, Appointment $appointment, bool $noShow): Appointment
    {
        return DB::transaction(function () use ($actor, $appointment, $noShow): Appointment {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($actor->role === UserRole::Sales && $locked->assigned_to !== $actor->id) {
                abort(403);
            }

            if ($locked->status !== AppointmentStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ lịch confirmed mới chuyển được sang hoàn tất hoặc no_show.',
                ]);
            }

            if ($locked->confirmed_at === null || $locked->confirmed_at->greaterThan(now())) {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ có thể kết thúc sau giờ xác nhận của lịch.',
                ]);
            }

            $locked->status = $noShow ? AppointmentStatus::NoShow : AppointmentStatus::Completed;
            $locked->save();

            return $locked;
        });
    }
}
