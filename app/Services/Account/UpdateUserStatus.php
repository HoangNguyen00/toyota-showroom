<?php

namespace App\Services\Account;

use App\Models\Inquiry;
use App\Models\User;
use App\Services\Inquiry\PublicationGate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateUserStatus
{
    public function handle(User $actor, User $target, bool $active): void
    {
        app(PublicationGate::class)->run(fn () => DB::transaction(function () use ($actor, $target, $active): void {
            // Shared protocol: users in ID order, then affected inquiries.
            $users = User::whereIn('id', [$actor->id, $target->id])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $lockedActor = $users->get($actor->id);
            $lockedTarget = $users->get($target->id);
            abort_unless($lockedActor && $lockedTarget, 404);
            Gate::forUser($lockedActor)->authorize('updateStatus', $lockedTarget);

            if ($lockedTarget->is_active === $active) {
                return; // Retrying the same desired state must not bump chat versions again.
            }

            $lockedTarget->forceFill(['is_active' => $active])->save();

            if (! $active) {
                Inquiry::where(function ($query) use ($lockedTarget): void {
                    $query->where('customer_id', $lockedTarget->id)
                        ->orWhere('assigned_to', $lockedTarget->id);
                })->increment('access_version');
            }
        }, 5));
    }
}
