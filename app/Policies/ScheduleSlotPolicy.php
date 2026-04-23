<?php

namespace App\Policies;

use App\Models\ScheduleSlot;
use App\Models\User;

class ScheduleSlotPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ScheduleSlot $scheduleSlot): bool
    {
        return $user->id === $scheduleSlot->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ScheduleSlot $scheduleSlot): bool
    {
        return $user->id === $scheduleSlot->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ScheduleSlot $scheduleSlot): bool
    {
        return $user->id === $scheduleSlot->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ScheduleSlot $scheduleSlot): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ScheduleSlot $scheduleSlot): bool
    {
        return false;
    }
}
