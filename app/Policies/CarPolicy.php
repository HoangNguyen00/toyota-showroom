<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Car;
use App\Models\User;

/**
 * Catalog is admin-only for writes (H03: CRUD dòng/xe/ảnh = Admin, không khóa quyền theo role web).
 * Public reading is not a Gate; it is handled by the publiclyVisible scope.
 */
class CarPolicy
{
    public function viewAny(User $actor): bool
    {
        return $this->isAdmin($actor);
    }

    public function view(User $actor, Car $car): bool
    {
        return $this->isAdmin($actor);
    }

    public function create(User $actor): bool
    {
        return $this->isAdmin($actor);
    }

    public function update(User $actor, Car $car): bool
    {
        return $this->isAdmin($actor);
    }

    public function delete(User $actor, Car $car): bool
    {
        return $this->isAdmin($actor);
    }

    public function uploadImage(User $actor, Car $car): bool
    {
        return $this->isAdmin($actor);
    }

    public function deleteImage(User $actor, Car $car): bool
    {
        return $this->isAdmin($actor);
    }

    private function isAdmin(User $actor): bool
    {
        return $actor->is_active && $actor->role === UserRole::Admin;
    }
}
