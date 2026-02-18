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
        $this->sqlFile->storeAs('migrations', $originalName, 'local');

        // Livewire stores in app/private/migrations via local disk, move to app/migrations
        $source = storage_path('app/private/migrations/' . $originalName);
        $dest = storage_path('app/migrations/' . $originalName);

        if (File::exists($source)) {
            File::move($source, $dest);
        }

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

        $this->isRunning = true;
        $this->isComplete = false;
        $this->hasError = false;
        $this->output = '';

        $statusFile = storage_path('app/migrations/.migration_status');
        $outputFile = storage_path('app/migrations/.migration_output');
        File::put($statusFile, 'running');
        File::put($outputFile, '');

        $php = PHP_BINARY ?: 'php';
        $artisan = base_path('artisan');
        $branch = (int) $this->branchId;
        $escaped = str_replace("'", "'\\''", $filePath);

        // Create a small PHP wrapper that runs the command and writes status on finish
        $runner = storage_path('app/migrations/.migration_runner.php');
        $script = "<?php\n"
            . "ob_start();\n"
            . "\$code = 0;\n"
            . "try {\n"
            . "    passthru('" . addslashes($php) . " " . addslashes($artisan) . " migration:import " . escapeshellarg($filePath) . " --branch={$branch} --clean --force 2>&1', \$code);\n"
            . "} catch (\\Throwable \$e) {\n"
            . "    echo \"Error: \" . \$e->getMessage();\n"
            . "    \$code = 1;\n"
            . "}\n"
            . "\$out = ob_get_clean();\n"
            . "file_put_contents('" . addslashes($outputFile) . "', \$out);\n"
            . "file_put_contents('" . addslashes($statusFile) . "', 'done');\n";

        File::put($runner, $script);

        // Launch in background (cross-platform)
        $runnerEscaped = escapeshellarg($runner);
        $phpEscaped = escapeshellarg($php);

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B \"migration\" {$phpEscaped} {$runnerEscaped}", 'r'));
        } else {
            exec("nohup {$phpEscaped} {$runnerEscaped} > /dev/null 2>&1 &");
        }
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

            if (str_contains($output, '❌') || str_contains($output, 'fueron revertidos')) {
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

        $statusFile = storage_path('app/migrations/.migration_status');
        $outputFile = storage_path('app/migrations/.migration_output');
        if (File::exists($statusFile)) File::delete($statusFile);
        if (File::exists($outputFile)) File::delete($outputFile);
    }
}
