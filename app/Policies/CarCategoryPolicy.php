<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CarCategory;
use App\Models\User;

class CarCategoryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $this->isAdmin($actor);
    }

    public function create(User $actor): bool
    {
        return $this->isAdmin($actor);
    }

    public function update(User $actor, CarCategory $category): bool
    {
        return $this->isAdmin($actor);
    }

    public function delete(User $actor, CarCategory $category): bool
    {
        return $this->isAdmin($actor) && $category->cars()->doesntExist();
    }

    private function isAdmin(User $actor): bool
    {
        return $actor->is_active && $actor->role === UserRole::Admin;
    }
}
