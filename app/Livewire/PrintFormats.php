<?php

namespace App\Livewire;

use App\Models\PrintFormatSetting;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PrintFormats extends Component
{
    public array $formats = [];
    public array $letterOptions = [];
    public ?string $previewFormat = null;
    public ?string $previewDocumentType = null;

    public function mount()
    {
        $this->loadFormats();
    }

    public function loadFormats()
    {
        $settings = PrintFormatSetting::all();
        $this->formats = [];
        $this->letterOptions = [];
        foreach ($settings as $setting) {
            $this->formats[$setting->document_type] = $setting->format;
            $this->letterOptions[$setting->document_type] = array_merge(
                PrintFormatSetting::DEFAULT_LETTER_OPTIONS,
                $setting->letter_options ?? []
            );
        }
    }

    public function selectFormat(string $documentType, string $format)
    {
        if (!auth()->user()->hasPermission('print_formats.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar', type: 'error');
            return;
        }

        $setting = PrintFormatSetting::where('document_type', $documentType)->first();
        if ($setting) {
            $oldValues = $setting->toArray();
            $setting->update(['format' => $format]);
            $this->formats[$documentType] = $format;

            ActivityLogService::logUpdate('print_formats', $setting, $oldValues, "Formato de impresión '{$setting->display_name}' cambiado a {$format}");
            $this->dispatch('notify', message: 'Formato actualizado correctamente', type: 'success');
        }
    }

    public function toggleLetterOption(string $documentType, string $option)
    {
        if (!auth()->user()->hasPermission('print_formats.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar', type: 'error');
            return;
        }

        $setting = PrintFormatSetting::where('document_type', $documentType)->first();
        if (!$setting) return;

        $oldValues = $setting->toArray();
        $options = array_merge(PrintFormatSetting::DEFAULT_LETTER_OPTIONS, $setting->letter_options ?? []);
        $options[$option] = !$options[$option];

        $setting->update(['letter_options' => $options]);
        $this->letterOptions[$documentType] = $options;

        $optionLabels = [
            'show_business' => 'Datos del negocio',
            'show_customer' => 'Datos del cliente',
            'show_sale_info' => 'Información de venta',
            'show_payment_info' => 'Información de pago',
            'show_amount_words' => 'Monto en letras',
            'show_footer' => 'Pie de página',
        ];

        $label = $optionLabels[$option] ?? $option;
        $state = $options[$option] ? 'activado' : 'desactivado';
        ActivityLogService::logUpdate('print_formats', $setting, $oldValues, "Opción '{$label}' {$state} en formato carta");
        $this->dispatch('notify', message: "'{$label}' {$state}", type: 'success');
    }

    public function showPreview(string $documentType, string $format)
    {
        $this->previewDocumentType = $documentType;
        $this->previewFormat = $format;
    }

    public function closePreview()
    {
        $this->previewFormat = null;
        $this->previewDocumentType = null;
    }

    public function render()
    {
        $settings = PrintFormatSetting::all();

        return view('livewire.print-formats', [
            'settings' => $settings,
        ]);
    }
}
