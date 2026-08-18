<?php

namespace App\Applications\Helpers;

class RolePermissionHelper
{
    public static function HasRole($role)
    {
        return auth()->user()->hasRole($role);
    }
}
