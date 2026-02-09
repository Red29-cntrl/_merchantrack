# Access MerchantTrack from Another Device

## Quick Setup Guide

### Your Server Information:
- **Server IP Address:** `192.168.0.144`
- **Client Device IP:** `192.168.0.146`
- **Access URL:** `http://192.168.0.144:8000`

---

## Step 1: Configure Firewall (Run Once)

Open PowerShell **as Administrator** and run:

```powershell
cd _merchantrack
.\configure-firewall.ps1
```

This will allow connections on ports 8000 and 6001.

---

## Step 2: Update Configuration (If Needed)

The `.env` file should have:
```env
APP_URL=http://192.168.0.144:8000
```

If you need to update it, run:
```powershell
cd _merchantrack
.\setup-remote-access.ps1
```
Then select option `2` (for IP 192.168.0.144)

---

## Step 3: Start the Servers

Double-click `start-servers.bat` or run:
```bash
cd _merchantrack
start-servers.bat
```

This will start:
- Laravel server on port 8000
- WebSocket server on port 6001

---

## Step 4: Access from Other Device (192.168.0.146)

On the device with IP `192.168.0.146`, open a web browser and go to:

```
http://192.168.0.144:8000
```

**Important:** Make sure both devices are on the same network (192.168.0.x).

---

## Troubleshooting

### Can't Access from Other Device?

1. **Check Firewall:**
   - Make sure you ran `configure-firewall.ps1` as Administrator
   - Verify ports 8000 and 6001 are allowed in Windows Firewall

2. **Check Servers are Running:**
   - Look for two command windows:
     - "Laravel Server" 
     - "WebSocket Server"
   - Both should be running without errors

3. **Check Network:**
   - Both devices must be on the same network
   - Try pinging from the other device:
     ```bash
     ping 192.168.0.144
     ```

4. **Check .env Configuration:**
   - Make sure `APP_URL=http://192.168.0.144:8000` in `.env`
   - Restart servers after changing `.env`

5. **Check Browser:**
   - Try different browsers
   - Clear browser cache
   - Try incognito/private mode

### Auto-Sync (No Manual Refresh)

Data syncs automatically across devices when something changes (new sale, product/inventory update). To enable it:

- Set in `.env`: `BROADCAST_DRIVER=pusher`
- Keep the WebSocket server running (port 6001) via `start-servers.bat`
- Then any open page on other devices will refresh automatically when data changes

### Real-Time Sync Not Working? (Checklist)

1. **`.env` for broadcasting and WebSockets** (all required for sync):
   ```env
   BROADCAST_DRIVER=pusher
   PUSHER_APP_ID=merchantrack
   PUSHER_APP_KEY=merchantrack-key
   PUSHER_APP_SECRET=merchantrack-secret
   WEBSOCKET_HOST=127.0.0.1
   WEBSOCKET_PORT=6001
   ```
   (So Laravel can send broadcasts to the local WebSocket server.)

2. **Frontend key** – so the browser can connect to the same app:
   ```env
   MIX_PUSHER_APP_KEY=merchantrack-key
   MIX_WEBSOCKET_PORT=6001
   ```
   Then run **`npm run dev`** (or `npm run production`) so the built `public/js/app.js` has these values. Restart or refresh after rebuilding.

3. **Both servers running**
   - Laravel: `php artisan serve --host=0.0.0.0 --port=8000`
   - WebSocket: `php artisan websockets:serve --host=0.0.0.0 --port=6001`  
   (Or use `start-servers.bat` so both run.)

4. **Firewall** – port 6001 must be allowed so other devices can open the WebSocket (e.g. run `configure-firewall.ps1`).

5. **Browser** – open DevTools (F12) → Console. You should see no WebSocket errors to `ws://...:6001`. If the key or port is wrong, the connection will fail there.

### WebSocket Not Working?

If real-time updates don't work:
- Make sure WebSocket server is running (port 6001)
- Set `BROADCAST_DRIVER=pusher` in `.env` (not `null` or `log`)
- Check browser console for WebSocket connection errors
- Use `WEBSOCKET_HOST=127.0.0.1` in `.env` (Laravel talks to the local WebSocket server); the WebSocket server itself is started with `--host=0.0.0.0` so other devices can connect

---

## Quick Test

From the other device (192.168.0.146), test connectivity:

**Windows:**
```cmd
ping 192.168.0.144
telnet 192.168.0.144 8000
```

**If ping works but telnet doesn't, firewall is blocking the port.**

---

## Security Note

⚠️ **Important:** This setup is for local network access only. For production use, you should:
- Use HTTPS
- Implement proper authentication
- Use a reverse proxy (nginx/Apache)
- Configure proper firewall rules

---

## Need Help?

If you're still having issues:
1. Check the server console windows for error messages
2. Check browser console (F12) on the client device
3. Verify both devices can ping each other
4. Make sure no antivirus is blocking the connection
