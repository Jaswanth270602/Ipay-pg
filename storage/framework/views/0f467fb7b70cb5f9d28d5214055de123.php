
<style>
    .modal.ipay-modal-top .modal-dialog {
        margin-top: 1rem;
        margin-bottom: auto;
    }
    @media (min-width: 768px) {
        .modal.ipay-modal-top .modal-dialog {
            margin-top: 1.5rem;
        }
    }
    .modal.ipay-modal-top.show {
        display: flex !important;
        align-items: flex-start;
        justify-content: center;
        padding-top: env(safe-area-inset-top, 0);
    }
</style>
<div class="modal fade ipay-modal-top" id="ipayModalAlert" tabindex="-1" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <div id="ipayModalAlertStrip" class="w-100" style="height: 4px; background: #3b82f6;"></div>
            <div class="modal-header border-0 pb-0 align-items-center">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span id="ipayModalAlertBadge" class="badge rounded-pill px-3 py-2 bg-info">Information</span>
                    <h5 class="modal-title mb-0" id="ipayModalAlertTitle">Notice</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="ipayModalAlertBody"></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade ipay-modal-top" id="ipayModalConfirm" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <div id="ipayModalConfirmStrip" class="w-100" style="height: 4px; background: #f59e0b;"></div>
            <div class="modal-header border-0 pb-0 align-items-center">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span id="ipayModalConfirmBadge" class="badge rounded-pill px-3 py-2 bg-warning text-dark">Confirmation</span>
                    <h5 class="modal-title mb-0" id="ipayModalConfirmTitle">Please confirm</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" id="ipayModalConfirmClose" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="mb-0 text-secondary" id="ipayModalConfirmMessage"></p>
            </div>
            <div class="modal-footer border-0 pt-0 gap-2">
                <button type="button" class="btn btn-outline-secondary" id="ipayModalConfirmCancel">Cancel</button>
                <button type="button" class="btn btn-primary" id="ipayModalConfirmOk">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var alertTypes = {
        success: { strip: '#10b981', badge: 'bg-success', badgeText: 'Success', title: 'Success' },
        danger:  { strip: '#ef4444', badge: 'bg-danger', badgeText: 'Error', title: 'Error' },
        warning: { strip: '#f59e0b', badge: 'bg-warning text-dark', badgeText: 'Warning', title: 'Warning' },
        info:    { strip: '#3b82f6', badge: 'bg-info', badgeText: 'Info', title: 'Information' }
    };

    /**
     * Modal alert — use instead of window.alert.
     * @param {string} body - Plain text or JSON string
     * @param {string} type - success | danger | warning | info
     * @param {object} opts - { title: string, rawTitle: bool }
     */
    window.ipayAlert = function (body, type, opts) {
        type = type || 'info';
        opts = opts || {};
        var cfg = alertTypes[type] || alertTypes.info;
        var strip = document.getElementById('ipayModalAlertStrip');
        var badge = document.getElementById('ipayModalAlertBadge');
        var titleEl = document.getElementById('ipayModalAlertTitle');
        var bodyEl = document.getElementById('ipayModalAlertBody');
        if (!strip || !bodyEl) {
            console.warn('ipayModalAlert: modal DOM missing', body);
            return;
        }
        strip.style.background = cfg.strip;
        badge.className = 'badge rounded-pill px-3 py-2 ' + cfg.badge;
        badge.textContent = cfg.badgeText;
        titleEl.textContent = opts.title || cfg.title;

        bodyEl.innerHTML = '';
        var text = body == null ? '' : String(body);
        var looksJson = opts.forceJson || (text.trim().startsWith('{') && text.trim().endsWith('}')) || (text.trim().startsWith('[') && text.trim().endsWith(']'));
        if (looksJson) {
            try {
                var parsed = JSON.parse(text);
                text = JSON.stringify(parsed, null, 2);
            } catch (e) { /* keep as-is */ }
        }
        if (looksJson || opts.preformatted || text.indexOf('\n') !== -1) {
            var pre = document.createElement('pre');
            pre.className = 'mb-0 small text-start rounded border bg-light p-3';
            pre.style.whiteSpace = 'pre-wrap';
            pre.style.maxHeight = '60vh';
            pre.textContent = text;
            bodyEl.appendChild(pre);
        } else {
            var div = document.createElement('div');
            div.className = 'text-body';
            div.textContent = text;
            bodyEl.appendChild(div);
        }

        var el = document.getElementById('ipayModalAlert');
        var m = bootstrap.Modal.getOrCreateInstance(el);
        m.show();
    };

    /**
     * Modal confirm — use instead of window.confirm. Returns a Promise resolving true/false.
     * @param {string} message
     * @param {string} type - warning (default, yellow) | danger (red, destructive) | info | success
     * @param {object} opts - { okText, cancelText, title }
     */
    window.ipayConfirm = function (message, type, opts) {
        type = type || 'warning';
        opts = opts || {};
        return new Promise(function (resolve) {
            var el = document.getElementById('ipayModalConfirm');
            var msgEl = document.getElementById('ipayModalConfirmMessage');
            var strip = document.getElementById('ipayModalConfirmStrip');
            var badge = document.getElementById('ipayModalConfirmBadge');
            var titleEl = document.getElementById('ipayModalConfirmTitle');
            var btnOk = document.getElementById('ipayModalConfirmOk');
            var btnCancel = document.getElementById('ipayModalConfirmCancel');
            var btnClose = document.getElementById('ipayModalConfirmClose');
            if (!el || !msgEl || !btnOk || !btnCancel) {
                resolve(window.confirm(message));
                return;
            }

            var cfg = alertTypes[type] || alertTypes.warning;
            strip.style.background = cfg.strip;
            if (type === 'danger') {
                badge.className = 'badge rounded-pill px-3 py-2 bg-danger';
                badge.textContent = 'Action required';
                titleEl.textContent = opts.title || 'Confirm';
                btnOk.className = 'btn btn-danger';
            } else if (type === 'info') {
                badge.className = 'badge rounded-pill px-3 py-2 bg-info';
                badge.textContent = 'Please confirm';
                titleEl.textContent = opts.title || 'Confirm';
                btnOk.className = 'btn btn-primary';
            } else if (type === 'success') {
                badge.className = 'badge rounded-pill px-3 py-2 bg-success';
                badge.textContent = 'Confirm';
                titleEl.textContent = opts.title || 'Confirm';
                btnOk.className = 'btn btn-success';
            } else {
                badge.className = 'badge rounded-pill px-3 py-2 bg-warning text-dark';
                badge.textContent = 'Please confirm';
                titleEl.textContent = opts.title || 'Confirm';
                btnOk.className = 'btn btn-primary';
            }

            msgEl.textContent = message;
            btnOk.textContent = opts.okText || 'Confirm';
            btnCancel.textContent = opts.cancelText || 'Cancel';

            var m = bootstrap.Modal.getOrCreateInstance(el);
            var settled = false;

            function done(val) {
                if (settled) return;
                settled = true;
                btnOk.onclick = null;
                btnCancel.onclick = null;
                btnClose.onclick = null;
                el.removeEventListener('hidden.bs.modal', onBackdrop);
                resolve(val);
            }

            function onBackdrop() {
                if (!settled) done(false);
            }

            btnOk.onclick = function () {
                m.hide();
                done(true);
            };
            btnCancel.onclick = function () {
                m.hide();
                done(false);
            };
            btnClose.onclick = function () {
                m.hide();
                done(false);
            };
            el.addEventListener('hidden.bs.modal', onBackdrop, { once: true });

            m.show();
        });
    };
})();
</script>
<?php /**PATH C:\Users\dell\Downloads\Ipay-pg\resources\views/components/ipay-modals.blade.php ENDPATH**/ ?>