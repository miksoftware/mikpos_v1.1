<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SystemToggleController extends Controller
{
    private string $lockFile;

    public function __construct()
    {
        $this->lockFile = storage_path('system.disabled');
    }

    /**
     * Toggle or set the system enabled/disabled status.
     * Requires a valid SYSTEM_ADMIN_TOKEN in the request.
     */
    public function toggle(Request $request)
    {
        $token = env('SYSTEM_ADMIN_TOKEN');

        if (empty($token) || $request->input('token') !== $token) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado.',
            ], 401);
        }

        $action = $request->input('action'); // 'enable', 'disable', or null (toggle)

        if ($action === 'disable') {
            $this->disableSystem();
        } elseif ($action === 'enable') {
            $this->enableSystem();
        } else {
            // Toggle
            File::exists($this->lockFile) ? $this->enableSystem() : $this->disableSystem();
        }

        $isDisabled = File::exists($this->lockFile);

        return response()->json([
            'success' => true,
            'status'  => $isDisabled ? 'disabled' : 'enabled',
            'message' => $isDisabled
                ? 'Sistema deshabilitado correctamente.'
                : 'Sistema habilitado correctamente.',
        ]);
    }

    /**
     * Returns the current status of the system.
     */
    public function status(Request $request)
    {
        $token = env('SYSTEM_ADMIN_TOKEN');

        if (empty($token) || $request->input('token') !== $token) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado.',
            ], 401);
        }

        $isDisabled = File::exists($this->lockFile);

        return response()->json([
            'success' => true,
            'status'  => $isDisabled ? 'disabled' : 'enabled',
        ]);
    }

    private function disableSystem(): void
    {
        if (!File::exists($this->lockFile)) {
            File::put($this->lockFile, now()->toIso8601String());
        }
    }

    private function enableSystem(): void
    {
        if (File::exists($this->lockFile)) {
            File::delete($this->lockFile);
        }
    }
}
