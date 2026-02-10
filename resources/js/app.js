import './bootstrap';

// Bundle Livewire with Vite to avoid 404 on /livewire/livewire.min.js
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

Livewire.start();
