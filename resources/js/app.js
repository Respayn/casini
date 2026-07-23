import './bootstrap';

import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import sort from '@alpinejs/sort'
import mask from '@alpinejs/mask'

Alpine.plugin(sort)
Alpine.plugin(mask)
 
Livewire.start()
