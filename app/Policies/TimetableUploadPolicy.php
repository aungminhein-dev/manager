<?php

namespace App\Policies;

use App\Models\TimetableUpload;
use App\Models\User;

class TimetableUploadPolicy
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
    public function view(User $user, TimetableUpload $timetableUpload): bool
    {
        return $user->id === $timetableUpload->user_id;
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
    public function update(User $user, TimetableUpload $timetableUpload): bool
    {
        return $user->id === $timetableUpload->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TimetableUpload $timetableUpload): bool
    {
        return $user->id === $timetableUpload->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TimetableUpload $timetableUpload): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TimetableUpload $timetableUpload): bool
    {
        return false;
    }
}
