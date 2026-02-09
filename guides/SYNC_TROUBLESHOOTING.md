
# Real-Time Sync Troubleshooting Guide

## Quick Fix Checklist

### ✅ Step 1: Run the Diagnostic
```bash
cd _merchantrack
php test-sync.php
```

This will tell you exactly what's wrong.

### ✅ Step 2: Start Both Servers

You **MUST** run **TWO** servers simultaneously:

**Option A: Use the batch file (Easiest)**
```bash
cd _merchantrack
start-servers.bat
```

**Option B: Manual start (Two separate terminals)**

**Terminal 1 - Laravel Server:**
```bash
cd _merchantrack
php artisan serve --host=0.0.0.0 --port=8000
```

**Terminal 2 - WebSocket Server (REQUIRED for sync):**
```bash
cd _merchantrack
php artisan websockets:serve --host=0.0.0.0 --port=6001
```

### ✅ Step 3: Check Browser Console

1. Open your app in browser
2. Press **F12** to open DevTools
3. Go to **Console** tab
4. Look for these messages:

**✅ Good signs:**
- `✓ Laravel Echo is available`
- `✓ WebSocket connected successfully!`
- `Connection state: connected`

**❌ Bad signs:**
- `❌ Laravel Echo is not available!`
- `✗ WebSocket disconnected`
- `Connection refused`
- `WebSocket connection failed`

### ✅ Step 4: Test the Connection

1. Go to: `http://your-server-ip:8000/debug-sync`
2. This page will show you:
   - Connection status
   - WebSocket state
   - Live event log
   - Test buttons

## Common Issues & Solutions

### Issue 1: "Laravel Echo is not available"

**Symptoms:**
- Console shows: `❌ Laravel Echo is not available!`

**Solutions:**
1. Rebuild assets:
   ```bash
   cd _merchantrack
   npm run production
   ```

2. Clear browser cache (Ctrl+Shift+Delete)

3. Check that `public/js/app.js` exists and is not empty

### Issue 2: "WebSocket server is not running"

**Symptoms:**
- Console shows: `✗ WebSocket disconnected`
- Diagnostic shows: `WebSocket server reachable: ✗ No`

**Solutions:**
1. **Start the WebSocket server:**
   ```bash
   php artisan websockets:serve --host=0.0.0.0 --port=6001
   ```

2. **Check if port 6001 is in use:**
   ```powershell
   netstat -ano | findstr :6001
   ```
   If something is using it, kill that process or use a different port

3. **Check firewall:**
   ```powershell
   cd _merchantrack
   .\configure-firewall.ps1
   ```

### Issue 3: "Connection refused" or "Connection failed"

**Symptoms:**
- WebSocket tries to connect but fails
- Console shows connection errors

**Solutions:**
1. **Verify WebSocket server is running:**
   - Look for a terminal window running `websockets:serve`
   - Should show: `Starting the WebSocket server on host 0.0.0.0:6001...`

2. **Check .env configuration:**
   ```env
   WEBSOCKET_HOST=127.0.0.1
   WEBSOCKET_PORT=6001
   MIX_WEBSOCKET_PORT=6001
   ```

3. **For remote devices:**
   - Make sure WebSocket server uses `--host=0.0.0.0` (not `127.0.0.1`)
   - Check firewall allows port 6001
   - Verify both devices are on same network

### Issue 4: "Events not received"

**Symptoms:**
- WebSocket connects successfully
- But no updates appear when data changes

**Solutions:**
1. **Check BROADCAST_DRIVER:**
   ```bash
   php artisan tinker
   >>> config('broadcasting.default')
   # Should return: "pusher"
   ```

2. **Clear config cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   Look for broadcast errors

4. **Test with debug page:**
   - Go to `/debug-sync`
   - Create a sale or update a product
   - Watch the event log

### Issue 5: "Works on same device but not across devices"

**Symptoms:**
- Updates work when testing on same computer
- But don't sync to other devices

**Solutions:**
1. **WebSocket server must use 0.0.0.0:**
   ```bash
   php artisan websockets:serve --host=0.0.0.0 --port=6001
   ```
   NOT `--host=127.0.0.1`

2. **Check firewall:**
   ```powershell
   .\configure-firewall.ps1
   ```

3. **Verify network:**
   - Both devices must be on same network
   - Test connectivity: `ping [server-ip]`

4. **Check browser console on remote device:**
   - Should show WebSocket connecting to server IP
   - Not `localhost` or `127.0.0.1`

## Verification Steps

### 1. Check Configuration
```bash
php test-sync.php
```
All checks should pass.

### 2. Check Browser Console
Open DevTools (F12) → Console:
- Should see: `✓ Laravel Echo is available`
- Should see: `✓ WebSocket connected successfully!`
- Connection state should be: `connected`

### 3. Test Real-Time Updates
1. Open app on **Device A**
2. Open app on **Device B** (or another tab)
3. On **Device A**: Create a sale
4. On **Device B**: Should see sale appear automatically (no refresh)

### 4. Use Debug Page
Go to: `http://your-server:8000/debug-sync`
- Shows connection status
- Shows live event log
- Can test events

## Still Not Working?

1. **Run diagnostic:**
   ```bash
   php test-sync.php
   ```

2. **Check browser console** (F12) for errors

3. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Verify both servers are running:**
   - Laravel: `http://your-server:8000` should load
   - WebSocket: Check terminal for `websockets:serve` process

5. **Test WebSocket connection manually:**
   - Open browser console
   - Type: `window.Echo.connector.pusher.connection.state`
   - Should return: `"connected"`

## Quick Reference

**Required .env settings:**
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_KEY=merchantrack-key
PUSHER_APP_SECRET=merchantrack-secret
PUSHER_APP_ID=merchantrack
WEBSOCKET_HOST=127.0.0.1
WEBSOCKET_PORT=6001
MIX_PUSHER_APP_KEY=merchantrack-key
MIX_WEBSOCKET_PORT=6001
```

**Required commands:**
```bash
# Terminal 1
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2 (REQUIRED for sync!)
php artisan websockets:serve --host=0.0.0.0 --port=6001
```

**Or use:**
```bash
start-servers.bat
```
