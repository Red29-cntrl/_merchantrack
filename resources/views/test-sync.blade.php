<!DOCTYPE html>
<html>
<head>
    <title>Sync Test Page</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        .log { background: #f5f5f5; padding: 10px; margin: 5px 0; border-left: 3px solid #007bff; }
        .success { border-left-color: #28a745; }
        .error { border-left-color: #dc3545; }
        .warning { border-left-color: #ffc107; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Sync Test Page</h1>
    <p>This page tests if sync polling and events are working.</p>
    
    <button onclick="testSyncEndpoint()">Test Sync Endpoint</button>
    <button onclick="testEvents()">Test Events</button>
    <button onclick="startPolling()">Start Polling</button>
    <button onclick="stopPolling()">Stop Polling</button>
    <button onclick="clearLogs()">Clear Logs</button>
    
    <div id="logs"></div>
    
    <script>
        let pollingInterval = null;
        let lastSyncTime = new Date(Date.now() - 60000).toISOString();
        
        function log(message, type = 'log') {
            const logs = document.getElementById('logs');
            const div = document.createElement('div');
            div.className = 'log ' + type;
            div.textContent = new Date().toLocaleTimeString() + ' - ' + message;
            logs.appendChild(div);
            logs.scrollTop = logs.scrollHeight;
            console.log(message);
        }
        
        function clearLogs() {
            document.getElementById('logs').innerHTML = '';
        }
        
        async function testSyncEndpoint() {
            log('Testing sync endpoint...', 'warning');
            try {
                const url = '{{ route("sync.changes") }}?last_sync=' + encodeURIComponent(lastSyncTime);
                log('Fetching: ' + url);
                const response = await fetch(url);
                log('Response status: ' + response.status);
                const data = await response.json();
                log('✅ Sync endpoint working! Sales: ' + (data.sales?.length || 0) + ', Products: ' + (data.products?.length || 0), 'success');
                log('Data: ' + JSON.stringify(data, null, 2));
            } catch (error) {
                log('❌ Error: ' + error.message, 'error');
            }
        }
        
        function testEvents() {
            log('Testing events...', 'warning');
            
            // Test sync:newSales
            const saleEvent = new CustomEvent('sync:newSales', {
                detail: [{
                    id: 999,
                    sale_number: 'TEST-SALE',
                    total: 100,
                    created_at: new Date().toISOString(),
                    cashier_name: 'Test',
                    payment_method: 'cash'
                }]
            });
            window.dispatchEvent(saleEvent);
            log('✅ Dispatched sync:newSales event', 'success');
            
            // Test sync:productUpdates
            const productEvent = new CustomEvent('sync:productUpdates', {
                detail: [{
                    id: 999,
                    name: 'TEST-PRODUCT',
                    quantity: 100,
                    reorder_level: 50,
                    unit: 'pcs'
                }]
            });
            window.dispatchEvent(productEvent);
            log('✅ Dispatched sync:productUpdates event', 'success');
        }
        
        function startPolling() {
            if (pollingInterval) {
                log('⚠️ Polling already running', 'warning');
                return;
            }
            
            log('🔄 Starting polling...', 'warning');
            lastSyncTime = new Date(Date.now() - 60000).toISOString();
            
            // First check immediately
            fetch('{{ route("sync.changes") }}?last_sync=' + encodeURIComponent(lastSyncTime))
                .then(r => r.json())
                .then(data => {
                    log('✅ First check - Sales: ' + (data.sales?.length || 0) + ', Products: ' + (data.products?.length || 0), 'success');
                    if (data.timestamp) lastSyncTime = data.timestamp;
                })
                .catch(err => log('❌ Error: ' + err.message, 'error'));
            
            // Then every 3 seconds
            pollingInterval = setInterval(() => {
                log('🔄 Polling check...');
                fetch('{{ route("sync.changes") }}?last_sync=' + encodeURIComponent(lastSyncTime))
                    .then(r => r.json())
                    .then(data => {
                        if (data.sales?.length > 0) {
                            log('📊 New sales: ' + data.sales.length, 'success');
                            window.dispatchEvent(new CustomEvent('sync:newSales', { detail: data.sales }));
                        }
                        if (data.products?.length > 0) {
                            log('📦 Product updates: ' + data.products.length, 'success');
                            window.dispatchEvent(new CustomEvent('sync:productUpdates', { detail: data.products }));
                        }
                        if (data.timestamp) lastSyncTime = data.timestamp;
                    })
                    .catch(err => log('❌ Error: ' + err.message, 'error'));
            }, 3000);
            
            log('✅ Polling started', 'success');
        }
        
        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
                log('✅ Polling stopped', 'success');
            } else {
                log('⚠️ Polling not running', 'warning');
            }
        }
        
        // Listen to events
        window.addEventListener('sync:newSales', function(e) {
            log('📊 sync:newSales event received! Count: ' + (e.detail?.length || 0), 'success');
        });
        
        window.addEventListener('sync:productUpdates', function(e) {
            log('📦 sync:productUpdates event received! Count: ' + (e.detail?.length || 0), 'success');
        });
        
        // Auto-start on load
        window.addEventListener('load', function() {
            log('Page loaded. Click "Start Polling" to begin testing.', 'warning');
        });
    </script>
</body>
</html>
