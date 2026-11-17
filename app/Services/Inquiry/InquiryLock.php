<?php

namespace App\Services\Inquiry;

use App\Models\Inquiry;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InquiryLock
{
    /** Lock the snapshot's users in order, then inquiry; retry if assignment changed while waiting. */
    public function run(int $id, array $additionalUserIds, Closure $callback): mixed
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $snapshot = Inquiry::findOrFail($id);
            $ids = array_values(array_unique(array_filter(array_map('intval', [
                ...$additionalUserIds, $snapshot->customer_id, $snapshot->assigned_to,
            ]))));
            sort($ids);
            try {
                return DB::transaction(function () use ($id, $ids, $callback) {
                    $users = User::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                    $inquiry = Inquiry::lockForUpdate()->findOrFail($id);
                    foreach (array_filter([$inquiry->customer_id, $inquiry->assigned_to]) as $required) {
                        if (! in_array($required, $ids, true)) {
                            throw new AssignmentChanged;
                        }
                    }

                    return $callback($inquiry, $users);
                }, 5);
            } catch (AssignmentChanged) {
                // Rollback before acquiring a different user set; never append locks out of order.
            }
        }
        abort(409, 'Phân công vừa thay đổi. Vui lòng thử lại.');
    }
}

class AssignmentChanged extends RuntimeException {}
