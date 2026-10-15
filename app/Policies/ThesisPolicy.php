<?php

namespace App\Policies;

use App\Models\Thesis;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ThesisPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view theses');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Thesis $thesis): bool
    {
        // Admin can view any thesis.
        if ($user->hasRole('admin')) {
            return $user->can('view theses');
        }

        // Lecturer can view only assigned theses.
        if ($user->hasRole('lecturer')) {
            return $user->can('view theses')
                && $thesis->supervisor_id === $user->id;
        }

        // Student can view only their own thesis.
        if ($user->hasRole('student')) {
            return $user->can('view theses')
                && $thesis->student_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //  return $user->can('submit thesis');
        return $user->hasRole('student')
            && $user->can('submit thesis');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Thesis $thesis): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('student')
            && $user->can('submit thesis')
            && $thesis->student_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Thesis $thesis): bool
    {
        return $user->hasRole('student')
            && $user->can('delete theses')
            && $thesis->student_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Thesis $thesis): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Thesis $thesis): bool
    {
        return false;
    }

    public function download(User $user, Thesis $thesis): bool
    {
        return $user->can('download thesis')
            && $this->view($user, $thesis);
    }

    public function review(User $user, Thesis $thesis): bool
    {
        return $user->hasRole('lecturer')
            && $user->can('review thesis')
            && $thesis->supervisor_id === $user->id;
    }
}
