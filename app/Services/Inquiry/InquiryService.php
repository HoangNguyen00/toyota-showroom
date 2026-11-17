<?php

namespace App\Services\Inquiry;

use App\Enums\CarStatus;
use App\Enums\InquiryCloseResult;
use App\Enums\InquiryStatus;
use App\Enums\UserRole;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class InquiryService
{
    public function create(?User $actor, array $data): Inquiry
    {
        return DB::transaction(function () use ($actor, $data) {
            $actor = $actor ? User::lockForUpdate()->findOrFail($actor->id) : null;
            Gate::forUser($actor)->authorize('create', Inquiry::class);
            if (! empty($data['car_id'])) {
                // Category -> car, both locking reads: ordinary Catalog UPDATEs must wait
                // until the inquiry commits. Never lock a second category if the car moved.
                $categoryId = Car::whereKey($data['car_id'])->value('category_id');
                $category = CarCategory::sharedLock()->find($categoryId);
                $car = Car::sharedLock()->find($data['car_id']);
                if (! $car || ! $category || $car->category_id !== $category->id
                    || ! $car->is_visible || ! $category->is_visible || $car->status === CarStatus::Sold) {
                    throw ValidationException::withMessages(['car_id' => 'Xe không nhận tư vấn.']);
                }
            }

            return Inquiry::create([
                'customer_id' => $actor?->id,
                'car_id' => $data['car_id'] ?? null,
                'contact_name' => $data['name'], 'contact_phone' => $data['phone'],
                'contact_email' => $data['email'] ?? null, 'content' => $data['content'],
                'source' => 'form', 'status' => InquiryStatus::New,
            ]);
        }, 5);
    }

    public function update(User $actor, Inquiry $inquiry, string $action, array $data = []): Inquiry
    {
        return app(PublicationGate::class)->run(fn () => app(InquiryLock::class)->run($inquiry->id, [$actor->id, $data['assigned_to'] ?? null], function ($locked, $users) use ($actor, $action, $data) {
            $actor = $users->get($actor->id);
            abort_unless($actor, 403);
            Gate::forUser($actor)->authorize($action === 'assign' ? 'assign' : 'manage', $locked);
            if ($locked->status === InquiryStatus::Closed) {
                throw ValidationException::withMessages(['status' => 'Hồ sơ đã đóng, chỉ được đọc.']);
            }
            if ($action === 'assign') {
                $sales = $users->get($data['assigned_to']);
                if (! $sales || ! $sales->is_active || $sales->role !== UserRole::Sales) {
                    throw ValidationException::withMessages(['assigned_to' => 'Chọn nhân viên sales đang hoạt động.']);
                }
                if ($locked->assigned_to !== $sales->id) {
                    $locked->assigned_to = $sales->id;
                    $locked->access_version++;
                }
                if ($locked->status === InquiryStatus::New) {
                    $locked->status = InquiryStatus::Assigned;
                }
            } elseif ($action === 'start') {
                if ($locked->status !== InquiryStatus::Assigned) {
                    throw ValidationException::withMessages(['status' => 'Chỉ bắt đầu hồ sơ đã phân công.']);
                }
                $locked->status = InquiryStatus::InProgress;
            } elseif ($action === 'note') {
                $locked->internal_note = $data['internal_note'] ?? null;
            } elseif ($action === 'close') {
                if ($locked->status !== InquiryStatus::InProgress) {
                    throw ValidationException::withMessages(['status' => 'Bắt đầu xử lý trước khi đóng hồ sơ.']);
                }
                $locked->close_result = InquiryCloseResult::from($data['close_result'])->value;
                $locked->status = InquiryStatus::Closed;
            } else {
                abort(404);
            }
            $locked->save();

            return $locked;
        }));
    }
}
