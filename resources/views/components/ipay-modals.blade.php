{{-- Global alert & confirm modals (replaces window.alert / confirm) --}}
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

    /* Readable “request payload” summary inside alert modal */
    .ipay-kv-intro {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 1rem;
        line-height: 1.45;
    }
    .ipay-kv-detail {
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        background: linear-gradient(180deg, #ffffff 0%, #fafafa 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }
    .ipay-kv-detail table {
        margin-bottom: 0;
    }
    .ipay-kv-detail tbody tr:first-child th,
    .ipay-kv-detail tbody tr:first-child td {
        border-top: none;
    }
    .ipay-kv-detail th {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #4b5563;
        padding: 0.75rem 1rem !important;
        vertical-align: middle;
        background: #f3f4f6 !important;
        border-color: #e5e7eb !important;
    }
    .ipay-kv-detail td {
        padding: 0.75rem 1rem !important;
        font-size: 0.9375rem;
        color: #111827;
        border-color: #e5e7eb !important;
        vertical-align: middle;
    }
    .ipay-kv-value-id {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.8125rem;
        line-height: 1.5;
        word-break: break-all;
        color: #1f2937;
    }
    .ipay-kv-value-amount {
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.02em;
    }
    .ipay-kv-badge-mode {
        display: inline-flex;
        align-items: center;
        padding: 0.28rem 0.65rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }
    .ipay-kv-badge-test {
        background: #fef3c7;
        color: #b45309;
        border: 1px solid #fcd34d;
    }
    .ipay-kv-badge-live {
        background: #d1fae5;
        color: #047857;
        border: 1px solid #6ee7b7;
    }

    /* Wider detail modal for key-value payloads */
    #ipayModalAlert .modal-dialog.modal-xl.ipay-kv-dialog {
        max-width: min(960px, calc(100vw - 2rem));
    }
    @media (min-width: 1200px) {
        #ipayModalAlert .modal-dialog.modal-xl.ipay-kv-dialog {
            max-width: min(1080px, calc(100vw - 3rem));
        }
    }
    #ipayModalAlert .ipay-kv-detail th {
        width: 26%;
        max-width: 280px;
    }
</style>
<div class="modal fade ipay-modal-top" id="ipayModalAlert" tabindex="-1" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg overflow-hidden rounded-4">
            <div id="ipayModalAlertStrip" class="w-100" style="height: 4px; background: #3b82f6;"></div>
            <div class="modal-header border-0 pb-2 px-4 pt-4 align-items-center">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span id="ipayModalAlertBadge" class="badge rounded-pill px-3 py-2 bg-info">Information</span>
                    <h5 class="modal-title mb-0" id="ipayModalAlertTitle">Notice</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2 pb-3 px-4" id="ipayModalAlertBody"></div>
            <div class="modal-footer border-0 pt-0 pb-3 px-4">
                <button type="button" class="btn btn-primary px-4 rounded-pill" data-bs-dismiss="modal">OK</button>
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

        var dlg = document.querySelector('#ipayModalAlert .modal-dialog');
        if (dlg) {
            dlg.className = 'modal-dialog modal-lg modal-dialog-scrollable';
        }

        strip.style.background = cfg.strip;
        badge.className = 'badge rounded-pill px-3 py-2 ' + cfg.badge;
        badge.textContent = cfg.badgeText;
        titleEl.textContent = opts.title || cfg.title;

        bodyEl.innerHTML = '';
        bodyEl.className = 'pt-2 pb-3 px-4';
        var text = body == null ? '' : String(body);

        /** Readable label from snake_case keys */
        function ipayHumanizeKey(key) {
            return String(key)
                .replace(/_/g, ' ')
                .replace(/\b([a-z])/gi, function (m) { return m.toUpperCase(); });
        }
        function ipayRawToString(rawVal) {
            if (rawVal === null || rawVal === undefined) return '\u2014';
            if (typeof rawVal === 'object') return JSON.stringify(rawVal);
            return String(rawVal);
        }
        /** Populate td with richer formatting based on field key */
        function ipayAppendKVValue(td, key, rawVal) {
            td.innerHTML = '';
            var keyLower = String(key).toLowerCase();
            var strVal = ipayRawToString(rawVal);

            if (strVal === '\u2014') {
                td.className = 'text-break text-muted';
                td.textContent = strVal;
                return;
            }

            if (keyLower === 'mode' && (strVal.toLowerCase() === 'test' || strVal.toLowerCase() === 'live')) {
                td.className = 'text-break';
                var badge = document.createElement('span');
                var isTest = strVal.toLowerCase() === 'test';
                badge.className = 'ipay-kv-badge-mode ' + (isTest ? 'ipay-kv-badge-test' : 'ipay-kv-badge-live');
                badge.textContent = strVal.toUpperCase();
                td.appendChild(badge);
                return;
            }

            if (keyLower.endsWith('_id') || keyLower === 'txn_id') {
                td.className = 'text-break';
                var code = document.createElement('div');
                code.className = 'ipay-kv-value-id';
                code.textContent = strVal;
                td.appendChild(code);
                return;
            }

            if (keyLower === 'amount') {
                td.className = 'text-break';
                var num = Number(rawVal);
                var amt = document.createElement('span');
                amt.className = 'ipay-kv-value-amount';
                amt.textContent = isFinite(num)
                    ? num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : strVal;
                td.appendChild(amt);
                return;
            }

            td.className = 'text-break';
            td.textContent = strVal;
        }

        // Plain-object payloads → summary card + table (approval "changes", etc.)
        if (opts.keyValueLayout) {
            try {
                var trimmed = text.trim();
                var kvObj = trimmed ? JSON.parse(trimmed) : null;
                if (kvObj !== null && typeof kvObj === 'object' && !Array.isArray(kvObj)) {
                    if (dlg) {
                        dlg.className = 'modal-dialog modal-xl modal-dialog-scrollable ipay-kv-dialog';
                    }
                    bodyEl.className = 'pt-3 pb-4 px-4 px-xl-5';

                    var intro = document.createElement('p');
                    intro.className = 'ipay-kv-intro mb-3';
                    intro.textContent = opts.kvIntro || 'Review the details submitted with this request.';
                    bodyEl.appendChild(intro);

                    var wrap = document.createElement('div');
                    wrap.className = 'ipay-kv-detail table-responsive';
                    var tbl = document.createElement('table');
                    tbl.className = 'table table-sm align-middle mb-0';
                    var tbody = document.createElement('tbody');
                    Object.keys(kvObj).forEach(function (key) {
                        var tr = document.createElement('tr');
                        var th = document.createElement('th');
                        th.scope = 'row';
                        th.textContent = ipayHumanizeKey(key);
                        var td = document.createElement('td');
                        ipayAppendKVValue(td, key, kvObj[key]);
                        tr.appendChild(th);
                        tr.appendChild(td);
                        tbody.appendChild(tr);
                    });
                    tbl.appendChild(tbody);
                    wrap.appendChild(tbl);
                    bodyEl.appendChild(wrap);
                    var elKv = document.getElementById('ipayModalAlert');
                    var mKv = bootstrap.Modal.getOrCreateInstance(elKv);
                    mKv.show();
                    return;
                }
            } catch (e) { /* fall through to JSON pre / plain text */ }
        }

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
