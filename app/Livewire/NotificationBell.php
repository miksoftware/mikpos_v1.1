<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Sale;
use Livewire\Component;

class NotificationBell extends Component
{
    public array $notifications = [];
    public bool $isOpen = false;
    public ?string $lastCheckedAt = null;
    public int $unreadCount = 0;

    public function mount()
    {
        $this->lastCheckedAt = now()->toDateTimeString();
        $this->loadNotifications();
    }

    public function poll()
    {
        $ecommerceEnabled = Branch::where('is_active', true)->where('ecommerce_enabled', true)->exists();
        if (!$ecommerceEnabled) {
            return;
        }

        $newOrders = Sale::where('source', 'ecommerce')
            ->where('status', 'pending_approval')
            ->where('created_at', '>', $this->lastCheckedAt)
            ->with('customer')
            ->orderByDesc('created_at')
            ->get();

        if ($newOrders->isNotEmpty()) {
            foreach ($newOrders as $order) {
                $customerName = $order->customer
                    ? $order->customer->full_name
                    : 'Cliente';

                array_unshift($this->notifications, [
                    'id' => $order->id,
                    'message' => "Nuevo pedido #{$order->invoice_number}",
                    'customer' => $customerName,
                    'total' => $order->total,
                    'time' => $order->created_at->diffForHumans(),
                    'read' => false,
                ]);
            }

            $this->unreadCount = collect($this->notifications)->where('read', false)->count();
            $this->lastCheckedAt = now()->toDateTimeString();
            $this->dispatch('new-order-notification');
        }
    }

    public function loadNotifications()
    {
        $ecommerceEnabled = Branch::where('is_active', true)->where('ecommerce_enabled', true)->exists();
        if (!$ecommerceEnabled) {
            return;
        }

        $pendingOrders = Sale::where('source', 'ecommerce')
            ->where('status', 'pending_approval')
            ->with('customer')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $this->notifications = $pendingOrders->map(fn($order) => [
            'id' => $order->id,
            'message' => "Pedido #{$order->invoice_number}",
            'customer' => $order->customer ? $order->customer->full_name : 'Cliente',
            'total' => $order->total,
            'time' => $order->created_at->diffForHumans(),
            'read' => true,
        ])->toArray();

        $this->unreadCount = 0;
    }

    public function toggleDropdown()
    {
        $this->isOpen = !$this->isOpen;
    }

    public function markAllRead()
    {
        foreach ($this->notifications as &$notification) {
            $notification['read'] = true;
        }
        $this->unreadCount = 0;
    }

    public function dismissNotification(int $index)
    {
        if (isset($this->notifications[$index])) {
            if (!$this->notifications[$index]['read']) {
                $this->unreadCount = max(0, $this->unreadCount - 1);
            }
            array_splice($this->notifications, $index, 1);
        }
    }

    public function goToOrders()
    {
        $this->markAllRead();
        $this->isOpen = false;
        return $this->redirect('/ecommerce-orders', navigate: true);
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
