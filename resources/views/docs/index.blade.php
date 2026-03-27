<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ipay API Documentation</title>
    <style>
        :root {
            --bg: #0b1020;
            --panel: #121a2f;
            --text: #d8e0ff;
            --muted: #95a2d3;
            --accent: #6ea8fe;
            --border: #263152;
            --ok: #35c28b;
            --warn: #ffcc66;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, Segoe UI, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        .layout { display: grid; grid-template-columns: 270px 1fr; min-height: 100vh; }
        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow: auto;
            background: #0d1428;
            border-right: 1px solid var(--border);
            padding: 20px 16px;
        }
        .brand { font-size: 18px; font-weight: 700; margin-bottom: 16px; }
        .base-url {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #0f1730;
        }
        .nav a {
            display: block;
            color: var(--text);
            text-decoration: none;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 4px;
            font-size: 14px;
        }
        .nav a:hover { background: #17213f; }
        .content { padding: 32px; }
        section { margin-bottom: 44px; }
        h1 { margin: 0 0 12px; font-size: 32px; }
        h2 {
            margin: 0 0 12px;
            font-size: 24px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
        }
        h3 { margin-top: 24px; margin-bottom: 8px; font-size: 18px; }
        p, li { color: var(--muted); line-height: 1.6; }
        code, pre {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        }
        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-top: 14px;
        }
        .row { display: grid; grid-template-columns: 160px 1fr; gap: 12px; margin: 6px 0; }
        .label { color: #b8c4ef; font-weight: 600; }
        .pill {
            display: inline-block;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 999px;
            border: 1px solid var(--border);
            margin-right: 6px;
            color: var(--muted);
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 8px;
            border: 1px solid var(--border);
        }
        .badge-get { color: var(--ok); }
        .badge-post { color: var(--warn); }
        .code-wrap { position: relative; margin-top: 10px; }
        pre {
            margin: 0;
            padding: 14px;
            border-radius: 8px;
            background: #0a1328;
            border: 1px solid var(--border);
            overflow: auto;
            font-size: 12px;
            color: #d5defd;
        }
        .copy-btn {
            position: absolute;
            right: 8px;
            top: 8px;
            border: 1px solid var(--border);
            background: #17213f;
            color: var(--text);
            border-radius: 6px;
            font-size: 12px;
            padding: 4px 8px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }
        th, td {
            border: 1px solid var(--border);
            padding: 10px;
            text-align: left;
        }
        th { background: #16213c; }
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .content { padding: 18px; }
            .row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">Ipay API Docs</div>
        <div class="base-url">
            Base URL<br>
            <code>{{ $baseUrl }}/api/v1</code>
        </div>
        <nav class="nav">
            <a href="#introduction">Introduction</a>
            <a href="#authentication">Authentication</a>
            <a href="#environments">Environments</a>
            <a href="#api-keys">API Keys</a>
            <a href="#apis">APIs</a>
            <a href="#webhooks">Webhooks</a>
            <a href="#errors">Error Codes</a>
            <a href="#postman">Postman Testing Guide</a>
        </nav>
    </aside>

    <main class="content">
        <section id="introduction">
            <h1>Ipay Payment Gateway API</h1>
            <p>Production-ready API documentation generated from the current Laravel codebase routes and controllers. These docs cover real endpoints under <code>/api</code> and <code>/api/v1</code>.</p>
        </section>

        <section id="authentication">
            <h2>Authentication</h2>
            <p>Authenticated API routes use <code>App\Http\Middleware\AuthenticateApiKey</code>. You can send either header format:</p>
            <div class="card">
                <div class="row"><div class="label">Primary Header</div><div><code>X-API-KEY: pk_test_xxx / pk_live_xxx</code></div></div>
                <div class="row"><div class="label">Alternative</div><div><code>Authorization: Bearer pk_test_xxx / pk_live_xxx</code></div></div>
            </div>
            <p>The middleware validates key status, expiry, and mode compatibility with merchant mode before allowing access.</p>
        </section>

        <section id="environments">
            <h2>Environments (Test & Live)</h2>
            <div class="card">
                <h3>Test Mode</h3>
                <ul>
                    <li>Use <code>pk_test_...</code> keys.</li>
                    <li>Returns/filters only test-mode entities for orders, transactions, and refunds.</li>
                    <li>Settlement endpoints return empty or reject in test mode as per controller logic.</li>
                </ul>
                <h3>Live Mode</h3>
                <ul>
                    <li>Use <code>pk_live_...</code> keys.</li>
                    <li>Processes real flows tied to live merchant mode.</li>
                    <li>Settlements API is available only in live mode.</li>
                </ul>
            </div>
        </section>

        <section id="api-keys">
            <h2>API Keys System</h2>
            <p>API keys are stored in the <code>api_keys</code> table with real fields: <code>key</code>, <code>secret</code>, <code>mode</code>, <code>status</code>, plus usage/expiry metadata.</p>
            <div class="card">
                <h3>Generate keys</h3>
                <p>Merchant dashboard routes:</p>
                <ul>
                    <li><code>POST /merchant/api-keys</code> with <code>name</code> and <code>mode</code> (<code>test</code>|<code>live</code>)</li>
                    <li><code>DELETE /merchant/api-keys/{id}</code> to revoke</li>
                    <li><code>POST /merchant/api-keys/{id}/regenerate-secret</code> to rotate secret</li>
                </ul>
                <p>Generated format in model: <code>pk_{mode}_{random}</code> and <code>sk_{mode}_{random}</code>.</p>
            </div>
        </section>

        <section id="apis">
            <h2>APIs</h2>
            @foreach($apis as $api)
                <div class="card">
                    <h3>
                        {{ $api['name'] }}
                        <span class="badge {{ $api['method'] === 'GET' ? 'badge-get' : 'badge-post' }}">{{ $api['method'] }}</span>
                    </h3>
                    <p>{{ $api['description'] ?? '' }}</p>
                    <div>
                        <span class="pill">Auth: {{ !empty($api['auth_required']) ? 'Required' : 'Public' }}</span>
                    </div>

                    <div class="row">
                        <div class="label">Endpoint</div>
                        <div><code>{{ $baseUrl . $api['endpoint'] }}</code></div>
                    </div>

                    <div class="row">
                        <div class="label">Headers</div>
                        <div>
                            @foreach($api['headers'] as $header)
                                <div><code>{{ $header }}</code></div>
                            @endforeach
                        </div>
                    </div>

                    @if(!empty($api['path_params']))
                        <div class="label">Path Parameters</div>
                        <table>
                            <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                            <tbody>
                            @foreach($api['path_params'] as $field)
                                <tr>
                                    <td><code>{{ $field['name'] }}</code></td>
                                    <td>{{ $field['type'] }}</td>
                                    <td>{{ $field['description'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if(!empty($api['query_params']))
                        <div class="label">Query Parameters</div>
                        <table>
                            <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                            <tbody>
                            @foreach($api['query_params'] as $field)
                                <tr>
                                    <td><code>{{ $field['name'] }}</code></td>
                                    <td>{{ $field['type'] }}</td>
                                    <td>{{ $field['description'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if(!empty($api['body_fields']))
                        <div class="label">Request Fields</div>
                        <table>
                            <thead><tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                            <tbody>
                            @foreach($api['body_fields'] as $field)
                                <tr>
                                    <td><code>{{ $field['name'] }}</code></td>
                                    <td>{{ $field['type'] }}</td>
                                    <td>{{ isset($field['required']) ? ($field['required'] ? 'Yes' : 'No') : 'No' }}</td>
                                    <td>{{ $field['description'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif

                    @if($api['request'])
                        <div class="label">Request Body (JSON)</div>
                        <div class="code-wrap">
                            <button class="copy-btn" data-target="request-{{ $loop->index }}">Copy</button>
                            <pre id="request-{{ $loop->index }}">{{ json_encode($api['request'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endif

                    <div class="label">Success Response</div>
                    <div class="code-wrap">
                        <button class="copy-btn" data-target="success-{{ $loop->index }}">Copy</button>
                        <pre id="success-{{ $loop->index }}">{{ json_encode($api['success'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    <div class="label">Sample cURL</div>
                    <div class="code-wrap">
                        <button class="copy-btn" data-target="curl-{{ $loop->index }}">Copy</button>
                        <pre id="curl-{{ $loop->index }}">curl -X {{ $api['method'] }} "{{ $baseUrl . $api['endpoint'] }}{{ !empty($api['query_params']) ? '?per_page=10' : '' }}" \
-H "X-API-KEY: pk_test_xxx" \
-H "Content-Type: application/json"{{ $api['request'] ? " \\\n-d '" . json_encode($api['request'], JSON_UNESCAPED_SLASHES) . "'" : '' }}</pre>
                    </div>

                    @if(!empty($api['response_fields']))
                        <div class="label">Response Fields</div>
                        <table>
                            <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                            <tbody>
                            @foreach($api['response_fields'] as $field)
                                <tr>
                                    <td><code>{{ $field['name'] }}</code></td>
                                    <td>{{ $field['type'] }}</td>
                                    <td>{{ $field['description'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif

                    <div class="label">Error Responses</div>
                    @foreach($api['errors'] as $error)
                        <div class="code-wrap">
                            <pre>{{ json_encode(['http_status' => $error['status'], 'response' => $error['body']], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </section>

        <section id="webhooks">
            <h2>Webhooks</h2>
            <div class="card">
                <h3>Incoming endpoints</h3>
                <ul>
                    <li><code>POST {{ $baseUrl }}/api/webhooks/receive</code> - generic receiver</li>
                    <li><code>POST {{ $baseUrl }}/api/webhooks/acquirer</code> - unified acquirer callback</li>
                </ul>

                <h3>Supported event types in callback processor</h3>
                <ul>
                    <li><code>payment.success</code>, <code>payment.captured</code>, <code>payment.failed</code>, <code>payment.authorized</code></li>
                    <li><code>refund.created</code>, <code>refund.success</code>, <code>settlement.processed</code></li>
                    <li><code>dispute.created</code>, <code>dispute.resolved</code></li>
                </ul>

                <h3>Signature verification</h3>
                <p>Acquirer callback extracts signatures from <code>X-Razorpay-Signature</code>, <code>X-Signature</code>, or <code>signature</code> payload field and verifies via provider adapter. Invalid signatures return <code>401</code>.</p>
            </div>
        </section>

        <section id="errors">
            <h2>Error Codes</h2>
            <table>
                <thead>
                <tr>
                    <th>Code</th>
                    <th>HTTP</th>
                    <th>Meaning</th>
                </tr>
                </thead>
                <tbody>
                @foreach($errorCodes as $error)
                    <tr>
                        <td><code>{{ $error['code'] }}</code></td>
                        <td>{{ $error['http'] }}</td>
                        <td>{{ $error['message'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="code-wrap" style="margin-top: 12px;">
                <pre>{
  "status": "error",
  "message": "Invalid API Key"
}</pre>
            </div>
        </section>

        <section id="postman">
            <h2>Postman Testing Guide</h2>
            <div class="card">
                <ol>
                    <li>Create a Postman request for a real endpoint, for example <code>POST {{ $baseUrl }}/api/v1/payment</code>.</li>
                    <li>Add header <code>X-API-KEY: pk_test_xxx</code>.</li>
                    <li>Add JSON body and send request.</li>
                    <li>Verify response and use returned IDs/tokens for follow-up APIs (verify/status/refund).</li>
                    <li>Test webhooks with <code>POST /api/webhooks/acquirer</code> payloads.</li>
                    <li>After successful test-mode validation, switch to <code>pk_live_xxx</code> and re-test in live mode.</li>
                </ol>
                <h3>Recommended sequence</h3>
                <p><code>Create Payment</code> -> <code>Transaction Status</code> -> <code>Create Refund</code> -> <code>Refund Status</code>.</p>
            </div>
        </section>
    </main>
</div>

<script>
    document.querySelectorAll('.copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = btn.getAttribute('data-target');
            var text = document.getElementById(targetId).innerText;
            navigator.clipboard.writeText(text).then(function () {
                var old = btn.innerText;
                btn.innerText = 'Copied';
                setTimeout(function () { btn.innerText = old; }, 1200);
            });
        });
    });
</script>
</body>
</html>

