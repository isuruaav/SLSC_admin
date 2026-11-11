import './page-content-editor.js';
import './copy-path.js';
import './admin-confirmations.js';
import './admin-editor-drafts.js';
import './gallery-ordering.js';
import './admin-responsive-tables.js';
import './publication-pdf-upload.js';
import './navigation-editor.js';
import './footer-editor.js';
import './news-image-upload.js';
import Trix from 'trix';

import Sortable from 'sortablejs';

window.Sortable = Sortable;

/*
 * AWCMS Rich Text Editor
 *
 * Trix uses "heading1" internally.
 * Render it as <h2> so article/page content does not
 * introduce a second page-level <h1>.
 */
Trix.config.blockAttributes.heading1.tagName = 'h2';

/*
 * File attachments are disabled.
 *
 * Images and documents must be managed through
 * the controlled AWCMS Media Library.
 */
document.addEventListener('trix-file-accept', (event) => {
    event.preventDefault();
});
