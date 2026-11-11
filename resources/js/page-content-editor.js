import { portableSitePath, resolveSitePath, resolveEditorPaths, storeEditorPaths } from './site-paths.js';

window.awcmsPageContentEditor = function awcmsPageContentEditor(
    initialContent = '',
    initialMode = 'visual',
    editorOptions = {},
) {
    return {
        mode: initialMode === 'html' ? 'html' : 'visual',
        content: typeof initialContent === 'string' ? initialContent : '',
        fullscreen: false,
        advancedWarning: false,
        savedRange: null,
        history: [],
        historyIndex: -1,
        restoringHistory: false,

        // Options (URLs, base paths, CSRF)
        options: {
            basePath: '',
            baseUrl: '',
            uploadUrl: '',
            recentImagesUrl: '',
            csrfToken: '',
            ...editorOptions,
        },

        // Image management state
        imageModalOpen: false,
        imageTab: 'library', // 'upload' | 'library' | 'url'
        modalSelectedFile: null,
        modalFilePreview: '',
        modalImageTitle: '',
        modalImageAlt: '',
        modalImagePosition: 'left', // 'left' | 'center' | 'right' | 'full'
        uploadingImage: false,
        uploadError: '',

        libraryImages: [],
        loadingLibrary: false,
        librarySearch: '',
        selectedLibraryImage: null,

        manualImageUrl: '',
        manualImageAlt: '',
        manualImagePreviewSrc: '',
        manualPreviewLoaded: false,
        manualPreviewError: false,

        // Image in-editor manipulation state
        selectedImage: null,
        draggedImage: null,
        imageAlign: 'left',
        floatingToolbarPos: { top: 0, left: 0 },

        init() {
            this.$nextTick(() => {
                this.$refs.visual.innerHTML = this.content;
                this.setupImagesInVisual();
                this.setupDragAndDrop();
                this.$refs.code.value = this.prettyFormatHtml(this.content);
                this.pushHistory(this.content, true);
                this.syncLivewireInputs();
            });
        },

        destroy() {
            document.documentElement.style.overflow = '';
        },

        pathOptions() {
            return { ...this.options, origin: window.location.origin };
        },

        resolveMediaUrl(url) {
            return resolveSitePath(url, this.pathOptions(), true);
        },

        canonicalMediaSrc(url) {
            return portableSitePath(url, this.pathOptions(), true);
        },

        setupImagesInVisual() {
            if (!this.$refs.visual) {
                return;
            }

            resolveEditorPaths(this.$refs.visual, this.pathOptions());
            const images = this.$refs.visual.querySelectorAll('img');
            images.forEach((img) => {
                img.setAttribute('draggable', 'true');
                img.classList.add('awcms-draggable-image');
                if (!img.style.maxWidth && !img.style.width) {
                    img.style.maxWidth = '100%';
                }
            });
        },

        setupDragAndDrop() {
            const editor = this.$refs.visual;
            if (!editor || editor._awcmsDndInit) {
                return;
            }
            editor._awcmsDndInit = true;

            // Dragstart on image
            editor.addEventListener('dragstart', (e) => {
                if (e.target && e.target.tagName === 'IMG') {
                    this.draggedImage = e.target;
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', e.target.getAttribute('src') || '');
                    e.target.classList.add('awcms-image-dragging');
                    this.deselectImage();
                }
            });

            // Dragover editor
            editor.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';

                const range = this.caretRangeFromPoint(e.clientX, e.clientY);
                if (range && editor.contains(range.startContainer)) {
                    this.showDropIndicator(range);
                }
            });

            // Dragleave editor
            editor.addEventListener('dragleave', (e) => {
                if (!editor.contains(e.relatedTarget)) {
                    this.hideDropIndicator();
                }
            });

            // Drop on editor
            editor.addEventListener('drop', (e) => {
                e.preventDefault();
                this.hideDropIndicator();

                // 1. Files dropped from OS
                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    const files = Array.from(e.dataTransfer.files).filter((f) =>
                        f.type.startsWith('image/'),
                    );
                    if (files.length > 0) {
                        const dropRange = this.caretRangeFromPoint(e.clientX, e.clientY);
                        this.uploadAndInsertDroppedFile(files[0], dropRange);
                        return;
                    }
                }

                // 2. Existing image moved within content
                if (this.draggedImage) {
                    const dropRange = this.caretRangeFromPoint(e.clientX, e.clientY);
                    if (dropRange && editor.contains(dropRange.startContainer)) {
                        dropRange.insertNode(this.draggedImage);
                        this.draggedImage.classList.remove('awcms-image-dragging');
                        this.selectImage(this.draggedImage);
                        this.syncFromVisual();
                    } else {
                        this.draggedImage.classList.remove('awcms-image-dragging');
                    }
                    this.draggedImage = null;
                }
            });

            // Dragend
            editor.addEventListener('dragend', () => {
                if (this.draggedImage) {
                    this.draggedImage.classList.remove('awcms-image-dragging');
                    this.draggedImage = null;
                }
                this.hideDropIndicator();
            });

            // Click inside editor
            editor.addEventListener('click', (e) => {
                if (e.target && e.target.tagName === 'IMG') {
                    e.stopPropagation();
                    this.selectImage(e.target);
                } else {
                    this.deselectImage();
                }
            });

            // Paste image from clipboard
            editor.addEventListener('paste', (e) => {
                const items = e.clipboardData ? e.clipboardData.items : null;
                if (!items) {
                    return;
                }
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type.indexOf('image') !== -1) {
                        const file = items[i].getAsFile();
                        if (file) {
                            e.preventDefault();
                            this.uploadAndInsertDroppedFile(file, this.currentRange());
                            break;
                        }
                    }
                }
            });

            // Scroll / resize repositions floating toolbar
            window.addEventListener('resize', () => {
                if (this.selectedImage) {
                    this.positionFloatingToolbar();
                }
            });
            editor.addEventListener('scroll', () => {
                if (this.selectedImage) {
                    this.positionFloatingToolbar();
                }
            });
        },

        caretRangeFromPoint(x, y) {
            if (document.caretRangeFromPoint) {
                return document.caretRangeFromPoint(x, y);
            }
            if (document.caretPositionFromPoint) {
                const pos = document.caretPositionFromPoint(x, y);
                if (pos) {
                    const range = document.createRange();
                    range.setStart(pos.offsetNode, pos.offset);
                    range.collapse(true);
                    return range;
                }
            }
            return null;
        },

        showDropIndicator(range) {
            if (!range || !this.$refs.dropIndicator) {
                return;
            }
            const shellRect = this.$el.getBoundingClientRect();
            const rects = range.getClientRects();

            if (rects.length > 0) {
                const r = rects[0];
                this.$refs.dropIndicator.style.display = 'block';
                this.$refs.dropIndicator.style.top = `${r.top - shellRect.top}px`;
                this.$refs.dropIndicator.style.left = `${r.left - shellRect.left}px`;
                this.$refs.dropIndicator.style.width = '180px';
            } else {
                const container =
                    range.startContainer.nodeType === Node.ELEMENT_NODE
                        ? range.startContainer
                        : range.startContainer.parentElement;
                if (container) {
                    const cr = container.getBoundingClientRect();
                    this.$refs.dropIndicator.style.display = 'block';
                    this.$refs.dropIndicator.style.top = `${cr.top - shellRect.top}px`;
                    this.$refs.dropIndicator.style.left = `${cr.left - shellRect.left}px`;
                    this.$refs.dropIndicator.style.width = `${Math.min(cr.width, 300)}px`;
                }
            }
        },

        hideDropIndicator() {
            if (this.$refs.dropIndicator) {
                this.$refs.dropIndicator.style.display = 'none';
            }
        },

        selectImage(img) {
            if (this.selectedImage && this.selectedImage !== img) {
                this.selectedImage.classList.remove('awcms-image-selected');
            }

            this.selectedImage = img;
            img.classList.add('awcms-image-selected');

            // Determine active alignment
            const fl = img.style.float;
            const disp = img.style.display;
            const w = img.style.width;

            if (disp === 'block' && w === '100%') {
                this.imageAlign = 'full';
            } else if (fl === 'left') {
                this.imageAlign = 'left';
            } else if (fl === 'right') {
                this.imageAlign = 'right';
            } else if (disp === 'block' || img.style.margin.includes('auto')) {
                this.imageAlign = 'center';
            } else {
                this.imageAlign = 'left';
            }

            this.$nextTick(() => {
                this.positionFloatingToolbar();
            });
        },

        deselectImage() {
            if (this.selectedImage) {
                this.selectedImage.classList.remove('awcms-image-selected');
                this.selectedImage = null;
            }
        },

        positionFloatingToolbar() {
            if (!this.selectedImage || !this.$refs.floatingToolbar) {
                return;
            }
            const imgRect = this.selectedImage.getBoundingClientRect();
            const shellRect = this.$el.getBoundingClientRect();
            const tb = this.$refs.floatingToolbar;

            let top = imgRect.top - shellRect.top - 46;
            if (top < 10) {
                top = imgRect.bottom - shellRect.top + 8;
            }
            let left = imgRect.left - shellRect.left + imgRect.width / 2 - tb.offsetWidth / 2;
            if (left < 10) {
                left = 10;
            }
            if (left + tb.offsetWidth > shellRect.width - 10) {
                left = shellRect.width - tb.offsetWidth - 10;
            }

            this.floatingToolbarPos = { top, left };
        },

        setImageAlign(align) {
            if (!this.selectedImage) {
                return;
            }
            this.imageAlign = align;

            switch (align) {
                case 'left':
                    this.selectedImage.style.float = 'left';
                    this.selectedImage.style.margin = '0 1.25rem 1rem 0';
                    this.selectedImage.style.display = 'inline-block';
                    this.selectedImage.style.clear = 'none';
                    if (!this.selectedImage.style.maxWidth || this.selectedImage.style.maxWidth === '100%') {
                        this.selectedImage.style.maxWidth = '50%';
                    }
                    break;
                case 'center':
                    this.selectedImage.style.float = 'none';
                    this.selectedImage.style.margin = '1.25rem auto';
                    this.selectedImage.style.display = 'block';
                    this.selectedImage.style.clear = 'both';
                    break;
                case 'right':
                    this.selectedImage.style.float = 'right';
                    this.selectedImage.style.margin = '0 0 1rem 1.25rem';
                    this.selectedImage.style.display = 'inline-block';
                    this.selectedImage.style.clear = 'none';
                    if (!this.selectedImage.style.maxWidth || this.selectedImage.style.maxWidth === '100%') {
                        this.selectedImage.style.maxWidth = '50%';
                    }
                    break;
                case 'full':
                    this.selectedImage.style.float = 'none';
                    this.selectedImage.style.margin = '1.25rem 0';
                    this.selectedImage.style.display = 'block';
                    this.selectedImage.style.width = '100%';
                    this.selectedImage.style.maxWidth = '100%';
                    this.selectedImage.style.clear = 'both';
                    break;
            }

            this.syncFromVisual();
            this.$nextTick(() => this.positionFloatingToolbar());
        },

        setImageWidth(percent) {
            if (!this.selectedImage) {
                return;
            }
            this.selectedImage.style.width = `${percent}%`;
            this.selectedImage.style.maxWidth = '100%';
            this.selectedImage.style.height = 'auto';
            this.syncFromVisual();
            this.$nextTick(() => this.positionFloatingToolbar());
        },

        deleteSelectedImage() {
            if (!this.selectedImage) {
                return;
            }
            this.selectedImage.remove();
            this.selectedImage = null;
            this.syncFromVisual();
        },

        // Image Modal & Uploads
        openImageModal() {
            this.rememberSelection();
            this.imageModalOpen = true;
            this.uploadError = '';
            this.manualPreviewError = false;
            this.manualPreviewLoaded = false;
            if (this.imageTab === 'library' && this.libraryImages.length === 0) {
                this.loadRecentImages();
            }
        },

        closeImageModal() {
            this.imageModalOpen = false;
            this.modalSelectedFile = null;
            this.modalFilePreview = '';
            this.uploadingImage = false;
            this.uploadError = '';
        },

        handleModalFileSelect(e) {
            const files = e.target.files;
            if (files && files[0]) {
                this.setModalFile(files[0]);
            }
        },

        handleModalFileDrop(e) {
            const files = e.dataTransfer.files;
            if (files && files[0] && files[0].type.startsWith('image/')) {
                this.setModalFile(files[0]);
            }
        },

        setModalFile(file) {
            this.modalSelectedFile = file;
            this.uploadError = '';
            if (!this.modalImageTitle) {
                this.modalImageTitle = file.name.replace(/\.[^/.]+$/, '');
            }
            if (!this.modalImageAlt) {
                this.modalImageAlt = this.modalImageTitle;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                this.modalFilePreview = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        clearModalFile() {
            this.modalSelectedFile = null;
            this.modalFilePreview = '';
            if (this.$refs.imageFileInput) {
                this.$refs.imageFileInput.value = '';
            }
        },

        formatBytes(bytes, decimals = 1) {
            if (!bytes || bytes === 0) return '0 B';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        },

        async uploadAndInsertDroppedFile(file, targetRange = null) {
            try {
                const data = await this.performFileUpload(file);
                this.insertImageElement(
                    data.url,
                    data.alt || data.name,
                    data.path,
                    'left',
                    targetRange,
                );
            } catch (err) {
                alert('Upload failed: ' + (err.message || 'Unknown error'));
            }
        },

        async submitModalUpload() {
            if (!this.modalSelectedFile || this.uploadingImage) {
                return;
            }

            this.uploadingImage = true;
            this.uploadError = '';

            try {
                const data = await this.performFileUpload(
                    this.modalSelectedFile,
                    this.modalImageTitle,
                    this.modalImageAlt,
                );
                this.insertImageElement(
                    data.url,
                    this.modalImageAlt || data.alt || data.name,
                    data.path,
                    this.modalImagePosition,
                );
                this.closeImageModal();
            } catch (err) {
                this.uploadError = err.message || 'Image upload failed. Please try again.';
            } finally {
                this.uploadingImage = false;
            }
        },

        async performFileUpload(file, title = '', altText = '') {
            if (!this.options.uploadUrl) {
                throw new Error('Upload endpoint is not configured.');
            }

            const formData = new FormData();
            formData.append('file', file);
            if (title) {
                formData.append('title', title);
            }
            if (altText) {
                formData.append('alt_text', altText);
            }

            const headers = {
                Accept: 'application/json',
            };
            if (this.options.csrfToken) {
                headers['X-CSRF-TOKEN'] = this.options.csrfToken;
            }

            const response = await fetch(this.options.uploadUrl, {
                method: 'POST',
                headers,
                body: formData,
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Upload failed');
                throw new Error(msg);
            }

            return data;
        },

        async loadRecentImages() {
            if (!this.options.recentImagesUrl) {
                return;
            }
            this.loadingLibrary = true;
            try {
                const url = new URL(this.options.recentImagesUrl, window.location.origin);
                if (this.librarySearch.trim() !== '') {
                    url.searchParams.set('q', this.librarySearch.trim());
                }
                const response = await fetch(url.toString(), {
                    headers: { Accept: 'application/json' },
                });
                const data = await response.json();
                if (data.success && Array.isArray(data.images)) {
                    this.libraryImages = data.images;
                }
            } catch (err) {
                console.error('Failed to load recent images', err);
            } finally {
                this.loadingLibrary = false;
            }
        },

        submitModalLibrary() {
            if (!this.selectedLibraryImage) {
                return;
            }
            this.insertImageElement(
                this.selectedLibraryImage.url,
                this.modalImageAlt || this.selectedLibraryImage.alt || this.selectedLibraryImage.title,
                this.selectedLibraryImage.path,
                this.modalImagePosition,
            );
            this.closeImageModal();
        },

        updateManualPreview() {
            if (!this.manualImageUrl || this.manualImageUrl.trim() === '') {
                this.manualImagePreviewSrc = '';
                this.manualPreviewLoaded = false;
                this.manualPreviewError = false;
                return;
            }
            this.manualPreviewLoaded = false;
            this.manualPreviewError = false;
            this.manualImagePreviewSrc = this.resolveMediaUrl(this.manualImageUrl.trim());
        },

        submitModalUrl() {
            if (!this.manualImageUrl || this.manualImageUrl.trim() === '') {
                return;
            }
            const src = this.manualImageUrl.trim();
            const resolved = this.resolveMediaUrl(src);
            const canonical = this.canonicalMediaSrc(src);

            this.insertImageElement(
                resolved,
                this.manualImageAlt.trim(),
                canonical,
                this.modalImagePosition,
            );
            this.closeImageModal();
        },

        insertImageElement(resolvedSrc, alt = '', canonicalPath = '', align = 'left', targetRange = null) {
            const img = document.createElement('img');
            img.setAttribute('src', resolvedSrc);
            img.setAttribute('alt', alt || '');
            img.setAttribute('data-canonical-src', canonicalPath || this.canonicalMediaSrc(resolvedSrc));
            img.setAttribute('draggable', 'true');
            img.setAttribute('loading', 'lazy');
            img.classList.add('awcms-draggable-image');

            switch (align) {
                case 'left':
                    img.style.float = 'left';
                    img.style.margin = '0 1.25rem 1rem 0';
                    img.style.maxWidth = '50%';
                    img.style.display = 'inline-block';
                    break;
                case 'center':
                    img.style.float = 'none';
                    img.style.margin = '1.25rem auto';
                    img.style.display = 'block';
                    img.style.clear = 'both';
                    break;
                case 'right':
                    img.style.float = 'right';
                    img.style.margin = '0 0 1rem 1.25rem';
                    img.style.maxWidth = '50%';
                    img.style.display = 'inline-block';
                    break;
                case 'full':
                    img.style.float = 'none';
                    img.style.margin = '1.25rem 0';
                    img.style.display = 'block';
                    img.style.width = '100%';
                    img.style.maxWidth = '100%';
                    img.style.clear = 'both';
                    break;
                default:
                    img.style.maxWidth = '100%';
            }

            const range = targetRange || this.currentRange();
            if (range && this.$refs.visual.contains(range.startContainer)) {
                range.deleteContents();
                range.insertNode(img);

                const spacer = document.createElement('br');
                img.after(spacer);
            } else {
                this.$refs.visual.appendChild(img);
            }

            this.setupImagesInVisual();
            this.selectImage(img);
            this.syncFromVisual();
        },

        // Legacy compatibility
        insertImage() {
            this.openImageModal();
        },

        // Core visual editor methods
        syncLivewireInputs() {
            this.$refs.contentInput.value = this.content;
            this.$refs.contentInput.dispatchEvent(new Event('input', { bubbles: true }));

            this.$refs.modeInput.value = this.mode;
            this.$refs.modeInput.dispatchEvent(new Event('input', { bubbles: true }));
        },

        syncFromVisual(recordHistory = true) {
            this.normaliseVisualMarkup();

            // Clone to sanitize temporary editor DOM state
            const clone = this.$refs.visual.cloneNode(true);
            clone.querySelectorAll('img').forEach((img) => {
                img.classList.remove(
                    'awcms-image-selected',
                    'awcms-image-dragging',
                    'awcms-draggable-image',
                );
                if (img.classList.length === 0) {
                    img.removeAttribute('class');
                }
                img.removeAttribute('draggable');
            });

            storeEditorPaths(clone, this.pathOptions());
            this.content = clone.innerHTML.trim();
            this.$refs.code.value = this.prettyFormatHtml(this.content);

            if (recordHistory && !this.restoringHistory) {
                this.pushHistory(this.content);
            }

            this.syncLivewireInputs();
        },

        syncFromCode() {
            this.content = this.$refs.code.value;
            this.syncLivewireInputs();
        },

        switchMode(targetMode) {
            const target = targetMode === 'html' ? 'html' : 'visual';

            if (target === this.mode) {
                return;
            }

            if (this.mode === 'visual') {
                this.syncFromVisual();
                this.deselectImage();
            } else {
                this.syncFromCode();
            }

            if (target === 'visual') {
                const advanced = this.hasAdvancedMarkup(this.content);

                if (
                    advanced &&
                    !window.confirm(
                        'Advanced HTML/Tailwind markup was detected. Opening it in the Visual Editor may change complex layout markup if you edit and save it.\n\nChoose OK to open visually, or Cancel to stay in HTML + Tailwind.',
                    )
                ) {
                    return;
                }

                this.advancedWarning = advanced;
                this.$refs.visual.innerHTML = this.content;
                this.setupImagesInVisual();
                this.pushHistory(this.content, true);
            } else {
                this.advancedWarning = false;
                this.content = this.prettyFormatHtml(this.content);
                this.$refs.code.value = this.content;
            }

            this.mode = target;
            this.syncLivewireInputs();

            this.$nextTick(() => {
                if (this.mode === 'visual') {
                    this.$refs.visual.focus();
                    this.rememberSelection();
                } else {
                    this.$refs.code.focus();
                }
            });
        },

        hasAdvancedMarkup(html) {
            if (typeof html !== 'string' || html.trim() === '') {
                return false;
            }

            return (
                /\bclass\s*=\s*["'][^"']+["']/i.test(html) ||
                /<(section|main|article|header|footer|nav|aside)\b/i.test(html) ||
                /\b(?:sm|md|lg|xl|2xl):[\w\-\[\]\/.:]+/i.test(html)
            );
        },

        selectionIsInsideEditor(selection = window.getSelection()) {
            if (!selection || selection.rangeCount === 0) {
                return false;
            }

            const anchor = selection.anchorNode;
            const focus = selection.focusNode;

            return Boolean(
                anchor &&
                    focus &&
                    this.$refs.visual.contains(anchor) &&
                    this.$refs.visual.contains(focus),
            );
        },

        captureToolbarSelection() {
            if (this.mode !== 'visual') {
                return;
            }

            const selection = window.getSelection();

            if (this.selectionIsInsideEditor(selection)) {
                this.savedRange = selection.getRangeAt(0).cloneRange();
            }
        },

        rememberSelection() {
            if (this.mode !== 'visual') {
                return;
            }

            const selection = window.getSelection();

            if (!this.selectionIsInsideEditor(selection)) {
                return;
            }

            this.savedRange = selection.getRangeAt(0).cloneRange();
        },

        restoreSelection() {
            this.$refs.visual.focus({ preventScroll: true });

            if (!this.savedRange) {
                return false;
            }

            const selection = window.getSelection();

            if (!selection) {
                return false;
            }

            try {
                selection.removeAllRanges();
                selection.addRange(this.savedRange);

                return true;
            } catch (_error) {
                this.savedRange = null;

                return false;
            }
        },

        currentRange() {
            this.restoreSelection();

            const selection = window.getSelection();

            if (!this.selectionIsInsideEditor(selection)) {
                return null;
            }

            return selection.getRangeAt(0);
        },

        selectNodeContents(node) {
            const selection = window.getSelection();

            if (!selection) {
                return;
            }

            const range = document.createRange();
            range.selectNodeContents(node);
            selection.removeAllRanges();
            selection.addRange(range);
            this.savedRange = range.cloneRange();
        },

        runNativeCommand(command, value = null) {
            this.restoreSelection();

            try {
                return document.execCommand(command, false, value);
            } catch (_error) {
                return false;
            }
        },

        runCommand(command, value = null) {
            switch (command) {
                case 'bold':
                    this.applyInlineTag('strong', 'bold');
                    return;
                case 'italic':
                    this.applyInlineTag('em', 'italic');
                    return;
                case 'underline':
                    this.applyInlineTag('u', 'underline');
                    return;
                case 'justifyLeft':
                    this.applyAlignment('left');
                    return;
                case 'justifyCenter':
                    this.applyAlignment('center');
                    return;
                case 'justifyRight':
                    this.applyAlignment('right');
                    return;
                case 'justifyFull':
                    this.applyAlignment('justify');
                    return;
                case 'insertUnorderedList':
                    this.applyList('ul', 'insertUnorderedList');
                    return;
                case 'insertOrderedList':
                    this.applyList('ol', 'insertOrderedList');
                    return;
                case 'undo':
                    this.undoVisual();
                    return;
                case 'redo':
                    this.redoVisual();
                    return;
                default:
                    this.runNativeCommand(command, value);
                    this.finishVisualCommand();
            }
        },

        applyInlineTag(tagName, nativeCommand) {
            const range = this.currentRange();

            if (!range) {
                return;
            }

            if (range.collapsed) {
                this.runNativeCommand(nativeCommand);
                this.finishVisualCommand();
                return;
            }

            const fragment = range.cloneContents();
            const containsBlock = Array.from(fragment.childNodes).some(
                (node) =>
                    node.nodeType === Node.ELEMENT_NODE &&
                    this.isBlockElement(node),
            );

            if (containsBlock) {
                this.runNativeCommand(nativeCommand);
                this.finishVisualCommand();
                return;
            }

            const wrapper = document.createElement(tagName);
            wrapper.appendChild(range.extractContents());
            range.insertNode(wrapper);
            this.selectNodeContents(wrapper);
            this.finishVisualCommand();
        },

        formatBlock(tagName) {
            const allowed = ['P', 'H1', 'H2', 'H3', 'BLOCKQUOTE'];
            const tag = String(tagName || 'P').toUpperCase();

            if (!allowed.includes(tag)) {
                return;
            }

            const range = this.currentRange();

            if (!range) {
                return;
            }

            const blocks = this.blocksForRange(range).filter((block) =>
                ['P', 'H1', 'H2', 'H3', 'BLOCKQUOTE'].includes(block.tagName),
            );

            if (blocks.length === 0) {
                this.runNativeCommand('formatBlock', `<${tag.toLowerCase()}>`);
                this.finishVisualCommand();
                return;
            }

            let lastReplacement = null;

            blocks.forEach((block) => {
                if (block.tagName === tag) {
                    lastReplacement = block;
                    return;
                }

                const replacement = document.createElement(tag.toLowerCase());

                Array.from(block.attributes).forEach((attribute) => {
                    replacement.setAttribute(attribute.name, attribute.value);
                });

                while (block.firstChild) {
                    replacement.appendChild(block.firstChild);
                }

                block.replaceWith(replacement);
                lastReplacement = replacement;
            });

            if (lastReplacement) {
                this.selectNodeContents(lastReplacement);
            }

            this.finishVisualCommand();
        },

        setTextColor(colour) {
            if (!/^#[0-9a-f]{6}$/i.test(colour)) {
                return;
            }

            const range = this.currentRange();

            if (!range) {
                return;
            }

            this.applyInlineStyleFallback(range, 'color', colour);
            this.finishVisualCommand();
        },

        setBackgroundColor(colour) {
            if (!/^#[0-9a-f]{6}$/i.test(colour)) {
                return;
            }

            const range = this.currentRange();

            if (!range) {
                return;
            }

            this.applyInlineStyleFallback(range, 'backgroundColor', colour);
            this.finishVisualCommand();
        },

        applyInlineStyleFallback(range, property, value) {
            if (range.collapsed) {
                const span = document.createElement('span');
                span.style[property] = value;
                span.appendChild(document.createTextNode('\u200b'));
                range.insertNode(span);

                const selection = window.getSelection();
                const nextRange = document.createRange();
                nextRange.setStart(span.firstChild, 1);
                nextRange.collapse(true);

                if (selection) {
                    selection.removeAllRanges();
                    selection.addRange(nextRange);
                }

                this.savedRange = nextRange.cloneRange();
                return;
            }

            const fragment = range.cloneContents();
            const containsBlock = Array.from(fragment.childNodes).some(
                (node) =>
                    node.nodeType === Node.ELEMENT_NODE &&
                    this.isBlockElement(node),
            );

            if (!containsBlock) {
                const span = document.createElement('span');
                span.style[property] = value;
                span.appendChild(range.extractContents());
                range.insertNode(span);
                this.selectNodeContents(span);
                return;
            }

            this.blocksForRange(range).forEach((block) => {
                block.style[property] = value;
            });
        },

        applyAlignment(alignment) {
            if (!['left', 'center', 'right', 'justify'].includes(alignment)) {
                return;
            }

            const range = this.currentRange();

            if (!range) {
                return;
            }

            const blocks = this.blocksForRange(range);

            if (blocks.length === 0) {
                const command = {
                    left: 'justifyLeft',
                    center: 'justifyCenter',
                    right: 'justifyRight',
                    justify: 'justifyFull',
                }[alignment];

                this.runNativeCommand(command);
                this.finishVisualCommand();
                return;
            }

            blocks.forEach((block) => {
                block.style.textAlign = alignment;
            });

            this.finishVisualCommand();
        },

        applyList(listTag, nativeCommand) {
            const range = this.currentRange();

            if (!range) {
                return;
            }

            if (this.runNativeCommand(nativeCommand)) {
                this.finishVisualCommand();
                return;
            }

            const block = this.closestBlock(range.startContainer);

            if (
                !block ||
                !['P', 'DIV', 'H1', 'H2', 'H3', 'BLOCKQUOTE'].includes(
                    block.tagName,
                )
            ) {
                return;
            }

            const list = document.createElement(listTag);
            const item = document.createElement('li');

            while (block.firstChild) {
                item.appendChild(block.firstChild);
            }

            list.appendChild(item);
            block.replaceWith(list);
            this.selectNodeContents(item);
            this.finishVisualCommand();
        },

        createLink() {
            this.restoreSelection();

            const url = window.prompt(
                'Enter the link URL. You may use https://, mailto:, tel: or a relative website path.',
                'https://',
            );

            if (!url || url.trim() === '') {
                return;
            }

            const cleanUrl = portableSitePath(url, this.pathOptions());
            const range = this.currentRange();

            if (range && !range.collapsed) {
                const anchor = document.createElement('a');
                anchor.href = cleanUrl;
                anchor.appendChild(range.extractContents());
                range.insertNode(anchor);
                this.selectNodeContents(anchor);
            } else {
                const anchor = document.createElement('a');
                anchor.href = cleanUrl;
                anchor.textContent = cleanUrl;
                this.insertElement(anchor, false);
            }

            this.finishVisualCommand();
        },

        insertDocument() {
            this.restoreSelection();

            const url = window.prompt(
                'Paste the document path copied from Document Uploads or the Media Library.',
                '',
            );

            if (!url || url.trim() === '') {
                return;
            }

            const label = window.prompt(
                'Enter the document link text.',
                'View document',
            );

            if (label === null) {
                return;
            }

            const anchor = document.createElement('a');
            anchor.setAttribute('href', this.canonicalMediaSrc(url));
            anchor.textContent = label.trim() || 'View document';

            this.insertElement(anchor, false);
            this.finishVisualCommand();
        },

        insertTable() {
            this.restoreSelection();

            const rowsInput = window.prompt('Number of rows (1-20):', '3');
            if (rowsInput === null) return;

            const columnsInput = window.prompt('Number of columns (1-10):', '3');
            if (columnsInput === null) return;

            const rows = Math.min(20, Math.max(1, Number.parseInt(rowsInput, 10) || 1));
            const columns = Math.min(10, Math.max(1, Number.parseInt(columnsInput, 10) || 1));

            const table = document.createElement('table');
            const body = document.createElement('tbody');

            for (let rowIndex = 0; rowIndex < rows; rowIndex += 1) {
                const row = document.createElement('tr');

                for (let columnIndex = 0; columnIndex < columns; columnIndex += 1) {
                    const cell = document.createElement(rowIndex === 0 ? 'th' : 'td');
                    cell.textContent =
                        rowIndex === 0
                            ? `Heading ${columnIndex + 1}`
                            : `Cell ${rowIndex + 1}.${columnIndex + 1}`;
                    row.appendChild(cell);
                }

                body.appendChild(row);
            }

            table.appendChild(body);
            this.insertElement(table, false);
            this.finishVisualCommand();
        },

        insertElement(element, sync = true) {
            const range = this.currentRange();
            const selection = window.getSelection();

            if (!range || !selection) {
                this.$refs.visual.appendChild(element);
                if (sync) {
                    this.finishVisualCommand();
                }
                return;
            }

            range.deleteContents();
            range.insertNode(element);

            const spacer = document.createElement('br');
            element.after(spacer);

            range.setStartAfter(spacer);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
            this.savedRange = range.cloneRange();

            if (sync) {
                this.finishVisualCommand();
            }
        },

        clearFormatting() {
            const range = this.currentRange();

            if (!range) {
                return;
            }

            if (this.runNativeCommand('removeFormat')) {
                this.normaliseVisualMarkup();
                this.finishVisualCommand();
                return;
            }

            if (!range.collapsed) {
                const text = range.toString();
                range.deleteContents();
                const textNode = document.createTextNode(text);
                range.insertNode(textNode);
                this.selectNodeContents(textNode);
                this.finishVisualCommand();
            }
        },

        finishVisualCommand() {
            this.normaliseVisualMarkup();
            this.syncFromVisual();
            this.rememberSelection();
        },

        isBlockElement(node) {
            return (
                node instanceof Element &&
                /^(ADDRESS|ARTICLE|ASIDE|BLOCKQUOTE|DIV|FIGCAPTION|FIGURE|FOOTER|H1|H2|H3|H4|H5|H6|HEADER|HR|LI|MAIN|NAV|OL|P|PRE|SECTION|TABLE|TBODY|TD|TFOOT|TH|THEAD|TR|UL)$/.test(
                    node.tagName,
                )
            );
        },

        closestBlock(node) {
            let current = node instanceof Element ? node : node?.parentElement;

            while (current && current !== this.$refs.visual) {
                if (this.isBlockElement(current)) {
                    return current;
                }
                current = current.parentElement;
            }

            return null;
        },

        blocksForRange(range) {
            const selector = 'p,h1,h2,h3,blockquote,li,td,th';
            const blocks = Array.from(this.$refs.visual.querySelectorAll(selector));
            const selected = blocks.filter((block) => {
                try {
                    return range.intersectsNode(block);
                } catch (_error) {
                    return false;
                }
            });

            if (selected.length > 0) {
                return selected;
            }

            const current = this.closestBlock(range.startContainer);

            return current ? [current] : [];
        },

        normaliseVisualMarkup() {
            const editor = this.$refs.visual;
            if (!editor) return;

            editor.querySelectorAll('font').forEach((font) => {
                const span = document.createElement('span');
                const colour = font.getAttribute('color');
                const background = font.style.backgroundColor;

                if (colour) span.style.color = colour;
                if (background) span.style.backgroundColor = background;

                while (font.firstChild) {
                    span.appendChild(font.firstChild);
                }

                font.replaceWith(span);
            });

            editor.querySelectorAll('[align]').forEach((element) => {
                const alignment = element.getAttribute('align');

                if (
                    alignment &&
                    ['left', 'center', 'right', 'justify'].includes(
                        alignment.toLowerCase(),
                    )
                ) {
                    element.style.textAlign = alignment.toLowerCase();
                }

                element.removeAttribute('align');
            });

            editor.querySelectorAll('b').forEach((element) => {
                const strong = document.createElement('strong');
                strong.innerHTML = element.innerHTML;
                element.replaceWith(strong);
            });

            editor.querySelectorAll('i').forEach((element) => {
                const emphasis = document.createElement('em');
                emphasis.innerHTML = element.innerHTML;
                element.replaceWith(emphasis);
            });

            editor.querySelectorAll('[style=""]').forEach((element) => {
                element.removeAttribute('style');
            });
        },

        pushHistory(html, reset = false) {
            const snapshot = typeof html === 'string' ? html : '';

            if (reset) {
                this.history = [snapshot];
                this.historyIndex = 0;
                return;
            }

            if (this.history[this.historyIndex] === snapshot) {
                return;
            }

            this.history = this.history.slice(0, this.historyIndex + 1);
            this.history.push(snapshot);

            if (this.history.length > 100) {
                this.history.shift();
            }

            this.historyIndex = this.history.length - 1;
        },

        undoVisual() {
            if (this.historyIndex <= 0) {
                return;
            }

            this.historyIndex -= 1;
            this.restoreHistorySnapshot();
        },

        redoVisual() {
            if (this.historyIndex >= this.history.length - 1) {
                return;
            }

            this.historyIndex += 1;
            this.restoreHistorySnapshot();
        },

        restoreHistorySnapshot() {
            const snapshot = this.history[this.historyIndex] ?? '';
            this.restoringHistory = true;
            this.content = snapshot;
            this.$refs.visual.innerHTML = snapshot;
            this.setupImagesInVisual();
            this.$refs.code.value = this.prettyFormatHtml(snapshot);
            this.syncLivewireInputs();
            this.restoringHistory = false;
            this.$refs.visual.focus({ preventScroll: true });
            this.savedRange = null;
        },

        formatCode() {
            this.syncFromCode();
            this.content = this.prettyFormatHtml(this.content);
            this.$refs.code.value = this.content;
            this.syncLivewireInputs();
        },

        prettyFormatHtml(html) {
            if (typeof html !== 'string' || html.trim() === '') {
                return '';
            }

            const template = document.createElement('template');
            template.innerHTML = html.trim();

            return Array.from(template.content.childNodes)
                .map((node) => this.serializePrettyNode(node, 0))
                .filter((line) => line !== '')
                .join('\n')
                .trim();
        },

        serializePrettyNode(node, depth) {
            const indent = '    '.repeat(depth);

            if (node.nodeType === Node.COMMENT_NODE) {
                return `${indent}<!--${node.textContent ?? ''}-->`;
            }

            if (node.nodeType === Node.TEXT_NODE) {
                const text = (node.textContent ?? '').trim();
                return text === '' ? '' : `${indent}${text}`;
            }

            if (!(node instanceof Element)) {
                return '';
            }

            const tag = node.tagName.toLowerCase();
            const opening = this.openingTag(node);
            const voidTags = new Set([
                'area',
                'base',
                'br',
                'col',
                'embed',
                'hr',
                'img',
                'input',
                'link',
                'meta',
                'param',
                'source',
                'track',
                'wbr',
            ]);

            if (voidTags.has(tag)) {
                return `${indent}${opening}`;
            }

            const blockTags = new Set([
                'article',
                'aside',
                'blockquote',
                'div',
                'figcaption',
                'figure',
                'footer',
                'form',
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'h6',
                'header',
                'li',
                'main',
                'nav',
                'ol',
                'p',
                'pre',
                'section',
                'table',
                'tbody',
                'td',
                'tfoot',
                'th',
                'thead',
                'tr',
                'ul',
            ]);

            const hasBlockChild = Array.from(node.children).some((child) =>
                blockTags.has(child.tagName.toLowerCase()),
            );

            if (!hasBlockChild) {
                return `${indent}${opening}${node.innerHTML.trim()}</${tag}>`;
            }

            const children = Array.from(node.childNodes)
                .map((child) => this.serializePrettyNode(child, depth + 1))
                .filter((line) => line !== '')
                .join('\n');

            if (children === '') {
                return `${indent}${opening}</${tag}>`;
            }

            return `${indent}${opening}\n${children}\n${indent}</${tag}>`;
        },

        openingTag(element) {
            const clone = element.cloneNode(false);
            const tag = element.tagName.toLowerCase();
            const outer = clone.outerHTML;
            const closing = `</${tag}>`;

            return outer.endsWith(closing)
                ? outer.slice(0, -closing.length)
                : outer;
        },

        toggleFullscreen() {
            this.fullscreen = !this.fullscreen;
            document.documentElement.style.overflow = this.fullscreen
                ? 'hidden'
                : '';

            this.$nextTick(() => {
                if (this.mode === 'visual') {
                    this.$refs.visual.focus();
                } else {
                    this.$refs.code.focus();
                }
            });
        },
    };
};
