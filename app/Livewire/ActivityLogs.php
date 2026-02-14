<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ActivityLogs extends Component
{
    use WithPagination;

    // Filters
    public string $dateRange = 'today';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;
    public ?int $selectedUserId = null;
    public string $selectedModule = '';
    public string $selectedAction = '';
    public string $search = '';

    // Detail modal
    public bool $isDetailModalOpen = false;
    public ?array $detailLog = null;

    public function mount()
    {
        $this->startDate = now()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');

        $user = auth()->user();
        if (!$user->isSuperAdmin() && $user->branch_id) {
            $this->selectedBranchId = $user->branch_id;
        }
    }

    public function updatedDateRange($value)
    {
        switch ($value) {
            case 'today':
                $this->startDate = now()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->startDate = now()->subDay()->format('Y-m-d');
                $this->endDate = now()->subDay()->format('Y-m-d');
                break;
            case 'week':
                $this->startDate = now()->startOfWeek()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'month':
                $this->startDate = now()->startOfMonth()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'last_month':
                $this->startDate = now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'custom':
                break;
        }
        $this->resetPage();
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedSelectedBranchId() { $this->resetPage(); }
    public function updatedSelectedUserId() { $this->resetPage(); }
    public function updatedSelectedModule() { $this->resetPage(); }
    public function updatedSelectedAction() { $this->resetPage(); }

    public function viewDetail(int $id)
    {
        $log = ActivityLog::with(['user', 'branch'])->find($id);
        if (!$log) return;

        $this->detailLog = [
            'id' => $log->id,
            'user_name' => $log->user->name ?? 'Sistema',
            'branch_name' => $log->branch->name ?? '-',
            'module' => $log->module,
            'action' => $log->action,
            'description' => $log->description,
            'model_type' => $log->model_type ? class_basename($log->model_type) : '-',
            'model_id' => $log->model_id ?? '-',
            'ip_address' => $log->ip_address ?? '-',
            'user_agent' => $log->user_agent ?? '-',
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'created_at' => $log->created_at->format('d/m/Y H:i:s'),
        ];
        $this->isDetailModalOpen = true;
    }

    public function closeDetail()
    {
        $this->isDetailModalOpen = false;
        $this->detailLog = null;
    }

    public function clearFilters()
    {
        $this->dateRange = 'today';
        $this->startDate = now()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->selectedUserId = null;
        $this->selectedModule = '';
        $this->selectedAction = '';
        $this->search = '';
        if (auth()->user()->isSuperAdmin()) {
            $this->selectedBranchId = null;
        }
        $this->resetPage();
    }

    private function getActionLabel(string $action): string
    {
        return match ($action) {
            'create' => 'Crear',
            'update' => 'Editar',
            'delete' => 'Eliminar',
            'view' => 'Ver',
            'login' => 'Inicio Sesión',
            'logout' => 'Cierre Sesión',
            'toggle_status' => 'Cambio Estado',
            'reprint' => 'Reimpresión',
            default => ucfirst($action),
        };
    }

    private function getActionColor(string $action): string
    {
        return match ($action) {
            'create' => 'bg-green-100 text-green-700',
            'update' => 'bg-blue-100 text-blue-700',
            'delete' => 'bg-red-100 text-red-700',
            'login' => 'bg-emerald-100 text-emerald-700',
            'logout' => 'bg-slate-100 text-slate-700',
            'toggle_status' => 'bg-amber-100 text-amber-700',
            'reprint' => 'bg-purple-100 text-purple-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public function render()
    {
        $query = ActivityLog::with(['user', 'branch']);

        // Branch filter
        if ($this->selectedBranchId) {
            $query->where('activity_logs.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where('activity_logs.branch_id', auth()->user()->branch_id);
        }

        // Date filter
        if ($this->startDate) {
            $query->whereDate('activity_logs.created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('activity_logs.created_at', '<=', $this->endDate);
        }

        // User filter
        if ($this->selectedUserId) {
            $query->where('activity_logs.user_id', $this->selectedUserId);
        }

        // Module filter
        if ($this->selectedModule) {
            $query->where('activity_logs.module', $this->selectedModule);
        }

        // Action filter
        if ($this->selectedAction) {
            $query->where('activity_logs.action', $this->selectedAction);
        }

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('activity_logs.description', 'like', "%{$this->search}%")
                    ->orWhereHas('user', function ($uq) {
                        $uq->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        $logs = $query->orderByDesc('activity_logs.created_at')->paginate(20);

        // Get filter options
        $branches = auth()->user()->isSuperAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        $users = User::orderBy('name')->get();

        $modules = ActivityLog::select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        $actions = ActivityLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('livewire.activity-logs', [
            'logs' => $logs,
            'branches' => $branches,
            'users' => $users,
            'modules' => $modules,
            'actions' => $actions,
            'isSuperAdmin' => auth()->user()->isSuperAdmin(),
            'actionLabels' => fn($a) => $this->getActionLabel($a),
            'actionColors' => fn($a) => $this->getActionColor($a),
        ]);
    }
}
