<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StatementCorrection;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatementCorrectionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the list of corrections.
     */
    public function viewAny(User $user): bool
    {
        // Client role_id = 2 is strictly forbidden
        return (int)$user->role_id !== 2 && in_array((int)$user->role_id, [1, 3, 4, 5, 6, 7, 8]);
    }

    /**
     * Determine whether the user can request a correction (Operator).
     */
    public function create(User $user): bool
    {
        // Opérateur: Backoffice (6), KAM (3), Manager (4), Admin (1, 8)
        return (int)$user->role_id !== 2 && in_array((int)$user->role_id, [1, 3, 4, 6, 8]);
    }

    /**
     * Determine whether the user can validate or reject a correction (Controller).
     * Rule: Must have controller role AND cannot be the same operator who created the request.
     */
    public function validateOrReject(User $user, StatementCorrection $correction): bool
    {
        if ((int)$user->role_id === 2) {
            return false;
        }

        // Must not be the same person who requested the correction
        if ((int)$user->id === (int)$correction->operator_id) {
            return false;
        }

        // Authorized controllers: Compliance (5), Backoffice (6), Manager (4), DG (7), Admin (1, 8)
        return in_array((int)$user->role_id, [1, 4, 5, 6, 7, 8]);
    }

    /**
     * Determine whether the user can view audit history.
     */
    public function viewAudit(User $user): bool
    {
        return (int)$user->role_id !== 2;
    }
}
