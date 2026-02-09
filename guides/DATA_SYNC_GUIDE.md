# Data Synchronization Across Devices - Complete Guide

## Overview

Your MerchantTrack application now has **real-time data synchronization** across all devices. When data changes on one device, it automatically updates on all other connected devices without requiring manual refresh.

## What Data Syncs Automatically

The following data types sync in real-time across devices:

1. **Sales** - New sales created on any device
2. **Products** - Product creation, updates, deletions, price changes, stock changes
3. **Inventory** - Inventory adjustments (stock in/out/adjustments)
4. **Categories** - Category creation, updates, and deletions

## How It Works

The system uses **WebSocket connections** (via Laravel WebSockets) to push real-time updates to all connected devices:

1. **Backend**: When data changes, Laravel broadcasts an event
2. **WebSocket Server**: Receives the broadcast and pushes it to all connected clients
3. **Frontend**: JavaScript listeners receive the update and refresh the UI automatically

## Setup Requirements

### 1. Environment Configuration

Make sure your `.env` file has these settings:

```env
# Broadcasting Configuration
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=merchantrack
PUSHER_APP_KEY=merchantrack-key
PUSHER_APP_SECRET=merchantrack-secret
WEBSOCKET_HOST=127.0.0.1
WEBSOCKET_PORT=6001

# Frontend Configuration (for compiled assets)
MIX_PUSHER_APP_KEY=merchantrack-key
MIX_WEBSOCKET_PORT=6001
```

### 2. Start Both Servers

You need to run **two servers** simultaneously:

1. **Laravel Server** (port 8000):
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

2. **WebSocket Server** (port 6001):
   ```bash
   php artisan websockets:serve --host=0.0.0.0 --port=6001
   ```

**Or use the batch file:**
```bash
start-servers.bat
```

### 3. Firewall Configuration

Make sure ports 8000 and 6001 are open in Windows Firewall:

```powershell
.\configure-firewall.ps1
```

### 4. Rebuild Frontend Assets (if needed)

If you changed `.env` values, rebuild the frontend:

```bash
npm run dev
# or for production
npm run production
```

## How to Verify Sync is Working

### Method 1: Browser Console Check

1. Open the application on two different devices
2. Open browser DevTools (F12) on both devices
3. Go to the Console tab
4. You should see: `Laravel Echo is available. Real-time updates enabled.`
5. If you see errors about WebSocket connection, check the WebSocket server is running

### Method 2: Test Real-Time Updates

1. **Test Sales Sync:**
   - Device A: Create a new sale in POS
   - Device B: Should automatically see the sale in dashboard (or refresh if on sales page)

2. **Test Product Sync:**
   - Device A: Edit a product (change price or stock)
   - Device B: Open POS page - product card should update automatically

3. **Test Inventory Sync:**
   - Device A: Adjust inventory (stock in/out)
   - Device B: Product quantity should update in real-time on POS page

4. **Test Category Sync:**
   - Device A: Create or edit a category
   - Device B: Category list should refresh automatically

### Method 3: Network Tab Check

1. Open DevTools → Network tab
2. Filter by "WS" (WebSocket)
3. You should see a WebSocket connection to `ws://[server-ip]:6001`
4. Connection status should be "101 Switching Protocols" (connected)

## Troubleshooting

### Sync Not Working?

1. **Check WebSocket Server is Running:**
   - Look for a command window running `websockets:serve`
   - Should show: "Starting the WebSocket server on host 0.0.0.0:6001..."

2. **Check Browser Console:**
   - Open DevTools (F12) → Console
   - Look for WebSocket connection errors
   - Common errors:
     - `WebSocket connection failed` → WebSocket server not running
     - `401 Unauthorized` → Authentication issue
     - `Connection refused` → Firewall blocking port 6001

3. **Verify .env Configuration:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Check Broadcasting Driver:**
   ```bash
   php artisan tinker
   >>> config('broadcasting.default')
   # Should return: "pusher"
   ```

5. **Test WebSocket Connection:**
   - Open browser console
   - Type: `window.Echo`
   - Should return an Echo object (not undefined)
   - Check connection: `window.Echo.connector.pusher.connection.state`
   - Should be: "connected"

### Common Issues

**Issue:** "Laravel Echo is not available"
- **Solution:** Make sure `npm run dev` or `npm run production` was run after setting up `.env`
- **Solution:** Check that `public/js/app.js` exists and includes Echo configuration

**Issue:** WebSocket connects but no updates received
- **Solution:** Check that `BROADCAST_DRIVER=pusher` in `.env`
- **Solution:** Verify events are being broadcast (check Laravel logs)

**Issue:** Updates work on same device but not across devices
- **Solution:** Make sure WebSocket server is started with `--host=0.0.0.0` (not `127.0.0.1`)
- **Solution:** Check firewall allows port 6001 from other devices
- **Solution:** Verify both devices are on the same network

**Issue:** Updates are delayed
- **Solution:** This is normal - there's a small delay (usually < 1 second) for WebSocket propagation
- **Solution:** Check network latency between devices

## Technical Details

### Events Being Broadcast

1. **SaleCreated** → Channel: `sales`, Event: `sale.created`
2. **ProductUpdated** → Channel: `products`, Event: `product.updated`
3. **InventoryUpdated** → Channel: `inventory`, Event: `inventory.updated`
4. **CategoryUpdated** → Channel: `categories`, Event: `category.updated`

### Frontend Listeners

- **POS Page** (`pos/index.blade.php`): Listens to all channels for real-time product/inventory updates
- **Dashboard** (`dashboard.blade.php`): Listens to sales, products, inventory, and categories
- **Layout** (`layouts/app.blade.php`): Global listener that refreshes pages when data changes

### Offline Support

The system also supports offline sales:
- Sales made while offline are stored in IndexedDB
- When connection is restored, sales are automatically synced
- This works independently of real-time WebSocket sync

## Performance Notes

- WebSocket connections are lightweight and persistent
- Each browser tab maintains one WebSocket connection
- Updates are pushed instantly (no polling)
- Minimal server load - only broadcasts when data actually changes

## Security

- All channels require authentication (users must be logged in)
- WebSocket connections use the same session authentication as HTTP
- Only authenticated users receive updates
- No sensitive data is exposed in broadcast payloads

## Next Steps

1. ✅ Verify `.env` configuration
2. ✅ Start both servers (`start-servers.bat`)
3. ✅ Test on two devices
4. ✅ Check browser console for connection status
5. ✅ Test creating/updating data on one device and watching it sync on another

---

**Need Help?** Check the browser console (F12) for detailed error messages. Most sync issues are related to WebSocket server not running or firewall blocking connections.
