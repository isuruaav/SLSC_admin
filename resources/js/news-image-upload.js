const MAX_IMAGES = 30;
const MAX_IMAGE_BYTES = 1024 * 1024;

export function validateNewsImages(files, existing = 0, pending = 0) {
    if (files.length + existing + pending > MAX_IMAGES) {
        throw new Error(`This article can contain 30 images. You can select ${Math.max(0, MAX_IMAGES - existing - pending)} more.`);
    }
    for (const file of files) {
        if (/[A-Z]/.test(file.name)) {
            throw new Error(`Rename "${file.name}" using lowercase letters, for example "${file.name.toLowerCase()}", then select it again.`);
        }
        if (!/\.(jpe?g|png|webp)$/.test(file.name) || (file.type && !['image/jpeg', 'image/png', 'image/webp'].includes(file.type))) {
            throw new Error(`"${file.name}" must be a JPG, JPEG, PNG or WEBP image.`);
        }
        if (file.size === 0) throw new Error(`"${file.name}" is empty. Select a different image.`);
        if (file.size > MAX_IMAGE_BYTES) throw new Error(`"${file.name}" exceeds 1 MB. Resize or compress it before uploading.`);
    }
}

export function newsImageUpload(wire) {
    return {
        uploading: false,
        progress: 0,
        error: '',
        ready: false,
        slow: false,
        needsRefresh: false,
        _slowTimer: null,
        _deadlineTimer: null,
        _stopIntercepting: null,

        get readyCount() {
            return Array.isArray(wire.galleryUploads) ? wire.galleryUploads.length : 0;
        },

        init() {
            // A failed _startUpload/_finishUpload request may never emit a file
            // upload error event. Release the UI while keeping Livewire's error visible.
            this._stopIntercepting = wire.$interceptRequest(({ onError, onFailure }) => {
                if (!this.uploading) return;
                const interrupted = (status) => {
                    this.needsRefresh = true;
                    const detail = Number.isInteger(status) ? ` (HTTP ${status})` : '';
                    this.failed(`Image preparation stopped before the server confirmed it${detail}. Save any article text, then refresh this page before selecting the images again.`);
                };
                onError(({ response } = {}) => interrupted(response?.status));
                onFailure(() => interrupted());
            });
        },

        validateSelection(event) {
            if (this.uploading || this.needsRefresh) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }
            const input = event.target;
            const files = Array.from(input.files ?? []);
            if (files.length === 0) return;
            try {
                validateNewsImages(files, Number(input.dataset.existingImageCount) || 0, this.readyCount);
                this.error = '';
                this.ready = false;
            } catch (error) {
                event.preventDefault();
                event.stopImmediatePropagation();
                input.value = '';
                this.error = error.message;
            }
        },

        started(event) {
            if (event.detail.property !== 'galleryUploads') return;
            this.uploading = true;
            this.progress = 0;
            this.error = '';
            this.ready = false;
            this.slow = false;
            clearTimeout(this._slowTimer);
            clearTimeout(this._deadlineTimer);
            this._slowTimer = setTimeout(() => { this.slow = true; }, 30000);
            this._deadlineTimer = setTimeout(() => this.timedOut(), 120000);
        },

        advanced(event) {
            if (event.target?.getAttribute('wire:model') !== 'galleryUploads') return;
            this.progress = Math.max(0, Math.min(100, Number(event.detail.progress) || 0));
        },

        finished(event) {
            if (event.detail.property !== 'galleryUploads') return;
            this.resetBusy();
            this.needsRefresh = false;
            this.error = '';
            this.ready = true;
        },

        failed(message = 'The selected images could not be prepared. Check the file size and connection, then select them again.') {
            this.resetBusy();
            this.ready = false;
            this.error = message;
        },

        cancelled(event) {
            if (event.detail.property !== 'galleryUploads') return;
            this.failed('Image preparation cancelled. You can select the files again.');
        },

        timedOut() {
            if (!this.uploading) return;
            this.needsRefresh = true;
            this.failed('The server has not confirmed the images after two minutes. Save any article text, then refresh the page and try one image under 1 MB. Check the existing image list before uploading again.');
        },

        cancel() {
            // Cancel only during the actual transfer. Cancelling a pending
            // signed-URL or finalization request can race Livewire's upload queue.
            if (!this.uploading || this.progress <= 0 || this.progress >= 100) return;
            wire.cancelUpload('galleryUploads');
        },

        guardSubmit(event) {
            if (!this.uploading) return;
            event.preventDefault();
            event.stopImmediatePropagation();
        },

        resetBusy() {
            clearTimeout(this._slowTimer);
            clearTimeout(this._deadlineTimer);
            this._slowTimer = null;
            this._deadlineTimer = null;
            this.uploading = false;
            this.slow = false;
        },

        destroy() {
            this.resetBusy();
            this._stopIntercepting?.();
        },
    };
}

if (typeof window !== 'undefined') window.awcmsNewsImageUpload = newsImageUpload;
