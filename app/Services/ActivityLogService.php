<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    public static function log(
        string $module,
        string $action,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): ActivityLog {
        $user = Auth::user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'branch_id' => $user?->branch_id,
            'module' => $module,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function logCreate(string $module, Model $model, string $description): ActivityLog
    {
        return self::log($module, 'create', $description, $model, null, $model->toArray());
    }

    public static function logUpdate(string $module, Model $model, array $oldValues, string $description): ActivityLog
    {
        return self::log($module, 'update', $description, $model, $oldValues, $model->toArray());
    }

    public static function logDelete(string $module, Model $model, string $description): ActivityLog
    {
        return self::log($module, 'delete', $description, $model, $model->toArray(), null);
    }

    public static function logView(string $module, Model $model, string $description): ActivityLog
    {
        return self::log($module, 'view', $description, $model);
    }

    public static function logLogin(): ActivityLog
    {
        $user = Auth::user();
        return self::log('auth', 'login', "Usuario {$user->name} inició sesión");
    }

    public static function logLogout(): ActivityLog
    {
        $user = Auth::user();
        return self::log('auth', 'logout', "Usuario {$user->name} cerró sesión");
    }
}
