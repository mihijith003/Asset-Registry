<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

/**
 * NEW: Admin-password authorization helper.
 *
 * Replaces the old free-text "Authorized By" field. Sensitive actions (asset sales
 * and asset modifications) must now be approved by typing an Admin's password.
 */
class AdminAuthorization
{
    /**
     * Return the Admin user whose password matches $password, or null if none match.
     *
     * Only the password is asked for (no email), so it is compared against every user
     * holding the "Admin" role. The matched admin is returned so the controller can
     * record who actually authorized the action.
     */
    public static function findAdminByPassword(?string $password): ?User
    {
        if ($password === null || $password === '') {
            return null;
        }

        try {
            $admins = User::role('Admin')->get();
        } catch (RoleDoesNotExist $e) {
            // The "Admin" role hasn't been seeded yet - nobody can authorize.
            return null;
        }

        foreach ($admins as $admin) {
            if (Hash::check($password, $admin->password)) {
                return $admin;
            }
        }

        return null;
    }
}
