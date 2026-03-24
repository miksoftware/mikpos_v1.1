<div wire:poll.10s="poll"
    x-data="{
        open: @entangle('isOpen'),
        audioCtx: null,
        ensureAudio() {
            if (!this.audioCtx) {
                this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }
            return this.audioCtx;
        },
        playSound() {
            try {
                const ctx = this.ensureAudio();
                const now = ctx.currentTime;

                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.frequency.value = 800;
                osc1.type = 'sine';
                gain1.gain.setValueAtTime(0.3, now);
                gain1.gain.exponentialRampToValueAtTime(0.01, now + 0.5);
                osc1.start(now);
                osc1.stop(now + 0.5);

                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.frequency.value = 1000;
                osc2.type = 'sine';
                gain2.gain.setValueAtTime(0, now);
                gain2.gain.setValueAtTime(0.3, now + 0.25);
                gain2.gain.exponentialRampToValueAtTime(0.01, now + 0.75);
                osc2.start(now + 0.25);
                osc2.stop(now + 0.75);
            } catch (e) {
                console.log('Audio not available:', e);
            }
        }
    }"
    x-init="
        document.addEventListener('click', () => { ensureAudio(); }, { once: true });
        document.addEventListener('touchstart', () => { ensureAudio(); }, { once: true });
    "
    @click.away="open = false"
    @play-notification-sound.window="playSound()"
    class="relative">

    {{-- Bell Button --}}
    <button @click="open = !open; if(open) $wire.markAllRead()"
        class="p-2 rounded-lg relative transition-colors"
        :class="$wire.unreadCount > 0 ? 'text-[#ff7261] hover:bg-red-50' : 'text-slate-500 hover:bg-slate-100'">
        <svg class="w-5 h-5 transition-transform"
            :class="$wire.unreadCount > 0 ? 'animate-[bell-ring_1s_ease-in-out_3]' : ''"
            fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        @if($unreadCount > 0)
        <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center px-1 text-[10px] font-bold text-white bg-[#ff7261] rounded-full animate-pulse">
            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
        </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 overflow-hidden"
        style="display: none;">

        {{-- Header --}}
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Notificaciones</h3>
            @if(count($notifications) > 0)
            <button wire:click="goToOrders" class="text-xs text-[#a855f7] hover:text-[#9333ea] font-medium">
                Ver pedidos
            </button>
            @endif
        </div>

        {{-- Notifications List --}}
        <div class="max-h-80 overflow-y-auto">
            @forelse($notifications as $index => $notification)
            <div class="px-4 py-3 hover:bg-slate-50 transition-colors border-b border-slate-50 {{ !$notification['read'] ? 'bg-orange-50/50' : '' }}">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center mt-0.5">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900">{{ $notification['message'] }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $notification['customer'] }} · ${{ number_format($notification['total'], 0, ',', '.') }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $notification['time'] }}</p>
                    </div>
                    <button wire:click="dismissNotification({{ $index }})" class="flex-shrink-0 p-1 text-slate-300 hover:text-slate-500 rounded transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center">
                <svg class="w-10 h-10 text-slate-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <p class="text-sm text-slate-400">Sin notificaciones</p>
            </div>
            @endforelse
        </div>

        {{-- Footer --}}
        @if(count($notifications) > 0)
        <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50">
            <button wire:click="goToOrders" class="w-full text-center text-xs font-medium text-[#a855f7] hover:text-[#9333ea] transition-colors">
                Ir a Pedidos Tienda →
            </button>
        </div>
        @endif
    </div>

    {{-- Bell ring animation --}}
    <style>
        @keyframes bell-ring {
            0%, 100% { transform: rotate(0deg); }
            10% { transform: rotate(14deg); }
            20% { transform: rotate(-14deg); }
            30% { transform: rotate(10deg); }
            40% { transform: rotate(-8deg); }
            50% { transform: rotate(4deg); }
            60% { transform: rotate(0deg); }
        }
    </style>

    @script
    <script>
        $wire.on('new-order-notification', () => {
            // Dispatch custom event so Alpine picks it up and plays sound
            window.dispatchEvent(new CustomEvent('play-notification-sound'));

            // Browser notification
            if (Notification.permission === 'granted') {
                new Notification('Nuevo pedido en tienda', {
                    body: 'Se ha recibido un nuevo pedido desde la tienda en línea',
                    icon: '/favicon.ico'
                });
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission();
            }
        });
    </script>
    @endscript
</div>
