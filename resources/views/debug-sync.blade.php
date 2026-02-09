@extends('layouts.app')

@section('title', 'Sync Debug')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Real-Time Sync Debug</h2>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Connection Status</h5>
        </div>
        <div class="card-body">
            <div id="connection-status" class="alert alert-info">
                Checking connection...
            </div>
            <div id="connection-details"></div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Test Events</h5>
        </div>
        <div class="card-body">
            <button class="btn btn-primary" onclick="testSaleEvent()">Test Sale Event</button>
            <button class="btn btn-success" onclick="testProductEvent()">Test Product Event</button>
            <button class="btn btn-warning" onclick="testInventoryEvent()">Test Inventory Event</button>
            <div id="test-results" class="mt-3"></div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Event Log</h5>
        </div>
        <div class="card-body">
            <div id="event-log" style="max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                <div class="text-muted">Waiting for events...</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusEl = document.getElementById('connection-status');
    const detailsEl = document.getElementById('connection-details');
    const logEl = document.getElementById('event-log');
    
    function log(message, type = 'info') {
        const time = new Date().toLocaleTimeString();
        const color = type === 'error' ? 'red' : type === 'success' ? 'green' : 'blue';
        const logEntry = document.createElement('div');
        logEntry.style.color = color;
        logEntry.innerHTML = `[${time}] ${message}`;
        logEl.insertBefore(logEntry, logEl.firstChild);
        
        // Keep only last 50 entries
        while (logEl.children.length > 50) {
            logEl.removeChild(logEl.lastChild);
        }
    }
    
    // Check Echo availability
    if (typeof window.Echo === 'undefined') {
        statusEl.className = 'alert alert-danger';
        statusEl.textContent = '✗ Laravel Echo is not available. Make sure npm run production was run.';
        log('Echo is undefined', 'error');
        return;
    }
    
    statusEl.className = 'alert alert-success';
    statusEl.textContent = '✓ Laravel Echo is available';
    log('Echo is available', 'success');
    
    // Check Pusher connection
    try {
        const pusher = window.Echo.connector.pusher;
        const connection = pusher.connection;
        
        function updateConnectionStatus() {
            const state = connection.state;
            const isConnected = state === 'connected';
            
            detailsEl.innerHTML = `
                <strong>Connection State:</strong> <span class="badge ${isConnected ? 'bg-success' : 'bg-warning'}">${state}</span><br>
                <strong>Socket ID:</strong> ${connection.socket_id || 'Not connected'}<br>
                <strong>Host:</strong> ${pusher.config.wsHost || 'unknown'}<br>
                <strong>Port:</strong> ${pusher.config.wsPort || 'unknown'}
            `;
            
            if (isConnected) {
                log('WebSocket connected', 'success');
            } else {
                log(`WebSocket state: ${state}`, 'error');
            }
        }
        
        connection.bind('state_change', updateConnectionStatus);
        updateConnectionStatus();
        
        // Listen to all channels for testing
        log('Subscribing to channels...', 'info');
        
        // Sales channel
        window.Echo.channel('sales')
            .listen('.sale.created', (e) => {
                log(`Sale created: ${JSON.stringify(e.sale)}`, 'success');
            });
        log('Subscribed to sales channel', 'success');
        
        // Products channel
        window.Echo.channel('products')
            .listen('.product.updated', (e) => {
                log(`Product updated: ${JSON.stringify(e.product)}`, 'success');
            });
        log('Subscribed to products channel', 'success');
        
        // Inventory channel
        window.Echo.channel('inventory')
            .listen('.inventory.updated', (e) => {
                log(`Inventory updated: ${JSON.stringify(e.product)}`, 'success');
            });
        log('Subscribed to inventory channel', 'success');
        
        // Categories channel
        window.Echo.channel('categories')
            .listen('.category.updated', (e) => {
                log(`Category updated: ${JSON.stringify(e.category)}`, 'success');
            });
        log('Subscribed to categories channel', 'success');
        
    } catch (e) {
        statusEl.className = 'alert alert-danger';
        statusEl.textContent = '✗ Error checking connection: ' + e.message;
        log('Error: ' + e.message, 'error');
        console.error(e);
    }
});

function testSaleEvent() {
    const resultsEl = document.getElementById('test-results');
    resultsEl.innerHTML = '<div class="alert alert-info">Creating test sale...</div>';
    
    // This would need a backend endpoint to trigger a test event
    fetch('{{ route("pos.processSale") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            items: [{product_id: 1, quantity: 1, unit: 'pcs', unit_price: 10, subtotal: 10}],
            subtotal: 10,
            tax: 0,
            discount: 0,
            total: 10,
            payment_method: 'cash'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultsEl.innerHTML = '<div class="alert alert-success">Test sale created! Check event log above.</div>';
        } else {
            resultsEl.innerHTML = '<div class="alert alert-warning">Sale creation failed: ' + (data.message || 'Unknown error') + '</div>';
        }
    })
    .catch(error => {
        resultsEl.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
    });
}

function testProductEvent() {
    const resultsEl = document.getElementById('test-results');
    resultsEl.innerHTML = '<div class="alert alert-info">This would trigger a product update event. Create/update a product in another tab to test.</div>';
}

function testInventoryEvent() {
    const resultsEl = document.getElementById('test-results');
    resultsEl.innerHTML = '<div class="alert alert-info">This would trigger an inventory update event. Adjust inventory in another tab to test.</div>';
}
</script>
@endsection
