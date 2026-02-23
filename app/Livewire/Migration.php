<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\File;

#[Layout('layouts.app')]
class Migration extends Component
{
    use WithFileUploads;

    public $sqlFile;
    public array $uploadedFiles = [];
    public ?string $selectedFile = null;
    public bool $isRunning = false;
    public bool $isComplete = false;
    public bool $hasError = false;
    public string $output = '';
    public int $branchId = 1;

    public function mount()
    {
        $this->branchId = auth()->user()->branch_id ?? 1;
        $this->loadUploadedFiles();
        $this->checkRunningProcess();
    }

    public function render()
    {
        return view('livewire.migration');
    }

    public function loadUploadedFiles(): void
    {
        $dir = storage_path('app/migrations');
        $this->uploadedFiles = [];

        if (File::isDirectory($dir)) {
            foreach (File::files($dir) as $file) {
                if (strtolower($file->getExtension()) === 'sql') {
                    $this->uploadedFiles[] = [
                        'name' => $file->getFilename(),
                        'size' => round($file->getSize() / 1024 / 1024, 2),
                        'date' => date('Y-m-d H:i', $file->getMTime()),
                    ];
                }
            }
        }
    }

    public function uploadFile(): void
    {
        $this->validate([
            'sqlFile' => 'required|file|max:102400',
        ]);

        $dir = storage_path('app/migrations');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $originalName = $this->sqlFile->getClientOriginalName();
        $dest = $dir . '/' . $originalName;

        // Move temp file directly to our migrations folder
        copy($this->sqlFile->getRealPath(), $dest);

        $this->sqlFile = null;
        $this->loadUploadedFiles();
        $this->selectedFile = $originalName;
        $this->dispatch('notify', message: 'Archivo subido correctamente', type: 'success');
    }

    public function deleteFile(string $filename): void
    {
        $path = storage_path('app/migrations/' . basename($filename));
        if (File::exists($path)) {
            File::delete($path);
            if ($this->selectedFile === $filename) {
                $this->selectedFile = null;
            }
            $this->loadUploadedFiles();
            $this->dispatch('notify', message: 'Archivo eliminado', type: 'success');
        }
    }

    public function startMigration(): void
    {
        if (!$this->selectedFile) {
            $this->dispatch('notify', message: 'Selecciona un archivo primero', type: 'error');
            return;
        }

        $filePath = storage_path('app/migrations/' . basename($this->selectedFile));
        if (!File::exists($filePath)) {
            $this->dispatch('notify', message: 'El archivo no existe', type: 'error');
            return;
        }

        $statusFile = storage_path('app/migrations/.migration_status');
        $outputFile = storage_path('app/migrations/.migration_output');
        $pidFile = storage_path('app/migrations/.migration_pid');

        // Reset state for re-runs
        $this->isRunning = true;
        $this->isComplete = false;
        $this->hasError = false;
        $this->output = '';

        File::put($statusFile, 'running');
        File::put($outputFile, '');

        // Find PHP CLI binary — PHP_BINARY may point to php-fpm in Docker
        $php = $this->findPhpCli();
        $artisan = base_path('artisan');
        $branch = (int) $this->branchId;

        $cmd = escapeshellarg($php) . ' ' . escapeshellarg($artisan)
            . ' migration:import ' . escapeshellarg($filePath)
            . " --branch={$branch} --clean --force";

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $bgCmd = "start /B cmd /C \"{$cmd} > " . escapeshellarg($outputFile) . " 2>&1 & echo done > " . escapeshellarg($statusFile) . "\"";
            pclose(popen($bgCmd, 'r'));
        } else {
            // Linux/Docker: write a shell script and launch detached
            $shellScript = storage_path('app/migrations/.migration_run.sh');
            $content = "#!/bin/sh\n"
                . "echo \$\$ > " . escapeshellarg($pidFile) . "\n"
                . "{$cmd} > " . escapeshellarg($outputFile) . " 2>&1\n"
                . "echo done > " . escapeshellarg($statusFile) . "\n"
                . "rm -f " . escapeshellarg($pidFile) . "\n";
            File::put($shellScript, $content);
            chmod($shellScript, 0755);

            // Try setsid first (fully detaches from PHP-FPM), fallback to nohup
            $hasSetsid = !empty(trim(shell_exec('command -v setsid 2>/dev/null') ?? ''));
            $script = escapeshellarg($shellScript);

            if ($hasSetsid) {
                shell_exec("setsid {$script} </dev/null >/dev/null 2>&1 &");
            } else {
                shell_exec("nohup {$script} </dev/null >/dev/null 2>&1 &");
            }
        }
    }

    public function cancelMigration(): void
    {
        $pidFile = storage_path('app/migrations/.migration_pid');
        $statusFile = storage_path('app/migrations/.migration_status');
        $outputFile = storage_path('app/migrations/.migration_output');

        // Kill the process if running
        if (File::exists($pidFile)) {
            $pid = trim(File::get($pidFile));
            if (is_numeric($pid)) {
                @exec("kill -9 {$pid} 2>/dev/null");
                // Kill children via /proc
                @exec("kill -9 $(cat /proc/{$pid}/task/{$pid}/children 2>/dev/null) 2>/dev/null");
            }
            File::delete($pidFile);
        }

        // Append cancellation message to output
        $output = File::exists($outputFile) ? File::get($outputFile) : '';
        File::put($outputFile, $output . "\n⚠️ Migración cancelada por el usuario.");
        File::put($statusFile, 'done');

        $this->isRunning = false;
        $this->hasError = true;
        $this->isComplete = false;
        $this->output = File::get($outputFile);
    }

    public function pollStatus(): void
    {
        $this->checkRunningProcess();
    }

    public function checkRunningProcess(): void
    {
        $statusFile = storage_path('app/migrations/.migration_status');
        $outputFile = storage_path('app/migrations/.migration_output');

        if (!File::exists($statusFile)) {
            return;
        }

        $status = trim(File::get($statusFile));
        $output = File::exists($outputFile) ? File::get($outputFile) : '';

        if ($status === 'running') {
            $this->isRunning = true;
            $this->output = $output;
        } elseif ($status === 'done') {
            $this->isRunning = false;
            $this->output = $output;

            if (str_contains($output, '❌') || str_contains($output, 'fueron revertidos') || str_contains($output, 'cancelada')) {
                $this->hasError = true;
                $this->isComplete = false;
            } else {
                $this->hasError = false;
                $this->isComplete = true;
            }

            File::delete($statusFile);
        }
    }

    public function resetMigration(): void
    {
        $this->isRunning = false;
        $this->isComplete = false;
        $this->hasError = false;
        $this->output = '';

        foreach (['.migration_status', '.migration_output', '.migration_pid', '.migration_run.sh'] as $f) {
            $path = storage_path('app/migrations/' . $f);
            if (File::exists($path)) File::delete($path);
        }
    }

    private function findPhpCli(): string
    {
        // In Docker, PHP_BINARY points to php-fpm. Just use 'php' from PATH
        // which works reliably in Docker containers (PHP 8.3 CLI).
        return 'php';
    }
}
