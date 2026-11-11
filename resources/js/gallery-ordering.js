import Sortable from 'sortablejs';

export function galleryImageOrder(container) {
    return [...container.querySelectorAll(':scope > [data-gallery-image]')].map((item) => Number(item.dataset.galleryImage));
}

if (typeof document !== 'undefined') {
    const instances = new Map();
    function initialize() {
        for (const [element, sortable] of instances) {
            if (!element.isConnected) { sortable.destroy(); instances.delete(element); }
        }
        document.querySelectorAll('[data-gallery-sort]').forEach((container) => {
            if (instances.has(container)) return;
            let pending = false;
            const sortable = Sortable.create(container, {
                handle: '[data-gallery-drag]', animation: 150,
                onStart() { if (pending) sortable.option('disabled', true); },
                async onEnd() {
                    const component = container.closest('[wire\\:id]');
                    const wire = component && window.Livewire?.find(component.getAttribute('wire:id'));
                    if (!wire || pending) return;
                    pending = true;
                    sortable.option('disabled', true);
                    try { await wire.reorderImages(galleryImageOrder(container)); }
                    catch { await wire.$refresh(); }
                    finally { pending = false; sortable.option('disabled', false); }
                },
            });
            instances.set(container, sortable);
        });
    }
    let registered;
    function start() {
        if (window.Livewire && registered !== window.Livewire) {
            registered = window.Livewire;
            registered.hook('morphed', initialize);
        }
        initialize();
    }
    document.addEventListener('livewire:init', start);
    document.addEventListener('livewire:initialized', start);
    document.addEventListener('livewire:navigated', start);
    start();
}
