<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Helpers\ResponseHelper;

trait CheckAdmin
{
    public function checkIfAdmin()
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user || !$user->is_admin) {
            return ResponseHelper::error('Forbidden. Admin only.', 403);
        }

        return null;
    }
}
