<?php

namespace App\Helpers;

use App\Models\AuditLog;

class Logger
{
    public static function action(string $action, string $details = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }
}
