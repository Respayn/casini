import './bootstrap';

import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import sort from '@alpinejs/sort'
import mask from '@alpinejs/mask'

Alpine.plugin(sort)
Alpine.plugin(mask)

function readSidebarOpenFromStorage() {
    return localStorage.getItem('casini.sidebarOpen') !== 'false'
}

function syncSidebarDomFromStorage() {
    const store = Alpine.store('sidebar')
    if (! store) {
        return
    }

    store.open = readSidebarOpenFromStorage()
    store.syncDom()
}

document.addEventListener('alpine:init', () => {
    Alpine.store('sidebar', {
        open: readSidebarOpenFromStorage(),

        syncDom() {
            document.documentElement.classList.toggle('sidebar-collapsed', ! this.open)
        },

        toggle() {
            document.documentElement.classList.add('sidebar-animating')
            this.open = ! this.open
            localStorage.setItem('casini.sidebarOpen', String(this.open))
            this.syncDom()
        },
    })

    document.documentElement.classList.remove('sidebar-animating')
    Alpine.store('sidebar').syncDom()
})

document.addEventListener('livewire:navigated', () => {
    document.documentElement.classList.remove('sidebar-animating')
    syncSidebarDomFromStorage()
    requestAnimationFrame(() => {
        syncSidebarDomFromStorage()
    })
})

Livewire.start()
