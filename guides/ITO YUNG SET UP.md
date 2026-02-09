# Security Setup Guide for MerchantTrack

This guide implements the security recommendations from `ACCESS_FROM_OTHER_DEVICE.md`:
- ✅ Use HTTPS
- ✅ Implement proper authentication
- ✅ Use a reverse proxy (nginx/Apache)
- ✅ Configure proper firewall rules

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [SSL Certificate Setup](#ssl-certificate-setup)
3. [Nginx Reverse Proxy Setup](#nginx-reverse-proxy-setup)
4. [Apache Reverse Proxy Setup](#apache-reverse-proxy-setup)
5. [Laravel Configuration](#laravel-configuration)
6. [Firewall Configuration](#firewall-configuration)
7. [Authentication Enhancement](#authentication-enhancement)
8. [Testing](#testing)
9. [Production Checklist](#production-checklist)

---

## Prerequisites

- Windows Server or Windows 10/11
- Administrator access
- Nginx or Apache web server installed
- OpenSSL (for certificate generation) - [Download here](https://slproweb.com/products/Win32OpenSSL.html)
- PHP and Laravel application running

---

## SSL Certificate Setup

### Option 1: Self-Signed Certificate (Development/Testing)

For local network or development use:

```powershell
cd _merchantrack
.\generate-ssl-cert.ps1
```

This will:
- Create an `ssl` directory
- Generate a self-signed certificate valid for 1 year
- Create `cert.pem` and `key.pem` files

**Note:** Browsers will show a security warning for self-signed certificates. This is normal for development.

### Option 2: Let's Encrypt (Production)

For production use, obtain a free SSL certificate from Let's Encrypt:

1. Install Certbot: https://certbot.eff.org/
2. Run Certbot to obtain certificate:
   ```bash
   certbot certonly --standalone -d yourdomain.com
   ```
3. Certificates will be saved to:
   - Certificate: `C:\Certbot\live\yourdomain.com\fullchain.pem`
   - Private Key: `C:\Certbot\live\yourdomain.com\privkey.pem`

### Option 3: Commercial SSL Certificate

Purchase an SSL certificate from a trusted CA (DigiCert, GlobalSign, etc.) and follow their installation instructions.

---

## Nginx Reverse Proxy Setup

### Step 1: Install Nginx

Download and install Nginx for Windows: http://nginx.org/en/download.html

### Step 2: Configure Nginx

1. Copy `nginx.conf` to your Nginx configuration directory:
   - Default: `C:\nginx\conf\nginx.conf` or
   - Create: `C:\nginx\conf\sites-available\merchantrack.conf`

2. Edit the configuration file and update:
   - `server_name`: Replace `_` with your domain or IP
   - `ssl_certificate`: Path to your certificate file
   - `ssl_certificate_key`: Path to your private key file
   - `root`: Path to `_merchantrack\public` directory
   - `fastcgi_pass`: PHP-FPM socket (if using PHP-FPM)

3. Example configuration paths:
   ```nginx
   server_name 192.168.0.144;  # or yourdomain.com
   ssl_certificate C:/path/to/merchantrack/ssl/cert.pem;
   ssl_certificate_key C:/path/to/merchantrack/ssl/key.pem;
   root C:/path/to/merchantrack/public;
   ```

### Step 3: Test and Start Nginx

```powershell
# Test configuration
nginx -t

# Start Nginx
nginx

# Reload configuration (after changes)
nginx -s reload
```

### Step 4: Verify

- HTTP (port 80) should redirect to HTTPS
- HTTPS (port 443) should serve your application
- WebSocket connections should work on `/app/` path

---

## Apache Reverse Proxy Setup

### Step 1: Install Apache

Download and install Apache for Windows: https://httpd.apache.org/download.cgi

### Step 2: Enable Required Modules

Edit `C:\Apache24\conf\httpd.conf` and uncomment:

```apache
LoadModule ssl_module modules/mod_ssl.so
LoadModule proxy_module modules/mod_proxy.so
LoadModule proxy_http_module modules/mod_proxy_http.so
LoadModule proxy_wstunnel_module modules/mod_proxy_wstunnel.so
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
```

### Step 3: Configure Apache

1. Copy `apache.conf` content to:
   - `C:\Apache24\conf\extra\httpd-vhosts.conf` or
   - Include it in `httpd.conf`

2. Edit the configuration and update:
   - `ServerName`: Replace `_` with your domain or IP
   - `SSLCertificateFile`: Path to your certificate file
   - `SSLCertificateKeyFile`: Path to your private key file
   - `DocumentRoot`: Path to `_merchantrack\public` directory

3. Example:
   ```apache
   ServerName 192.168.0.144
   SSLCertificateFile "C:/path/to/merchantrack/ssl/cert.pem"
   SSLCertificateKeyFile "C:/path/to/merchantrack/ssl/key.pem"
   DocumentRoot "C:/path/to/merchantrack/public"
   ```

### Step 4: Test and Start Apache

```powershell
# Test configuration
httpd -t

# Start Apache (as Administrator)
httpd -k start

# Restart Apache (after changes)
httpd -k restart
```

---

## Laravel Configuration

### Step 1: Update .env File

Add or update these variables in `.env`:

```env
# Use HTTPS
APP_URL=https://192.168.0.144
# or
APP_URL=https://yourdomain.com

# Force HTTPS (recommended for production)
APP_FORCE_HTTPS=true

# Session security (for HTTPS)
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Trust proxy headers (important for reverse proxy)
TRUSTED_PROXIES=*
```

### Step 2: Update TrustProxies Middleware

The `TrustProxies` middleware has been updated to trust all proxies when behind a reverse proxy. This is already configured in `app/Http/Middleware/TrustProxies.php`.

### Step 3: Force HTTPS in Application

Create or update `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\URL;

public function boot()
{
    if (env('APP_FORCE_HTTPS', false)) {
        URL::forceScheme('https');
    }
}
```

### Step 4: Clear Configuration Cache

```powershell
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## Firewall Configuration

### Enhanced Firewall Setup

Run the enhanced firewall configuration script:

```powershell
cd _merchantrack
.\configure-firewall.ps1
```

Select option:
- **1**: Development (HTTP only - ports 8000, 6001)
- **2**: Production with Reverse Proxy (HTTPS - ports 443, 80, 6001)
- **3**: Both (Development + Production)

### Manual Firewall Rules

If you prefer to configure manually:

```powershell
# HTTPS (Production)
New-NetFirewallRule -DisplayName "MerchantTrack - HTTPS" -Direction Inbound -LocalPort 443 -Protocol TCP -Action Allow

# HTTP Redirect
New-NetFirewallRule -DisplayName "MerchantTrack - HTTP Redirect" -Direction Inbound -LocalPort 80 -Protocol TCP -Action Allow

# WebSocket (if not behind reverse proxy)
New-NetFirewallRule -DisplayName "MerchantTrack - WebSocket" -Direction Inbound -LocalPort 6001 -Protocol TCP -Action Allow
```

### Security Recommendations

1. **Restrict Laravel Port (8000)**: When using reverse proxy, only allow localhost access to port 8000:
   ```powershell
   New-NetFirewallRule -DisplayName "MerchantTrack - Laravel (Localhost Only)" `
       -Direction Inbound -LocalPort 8000 -Protocol TCP -Action Allow `
       -RemoteAddress 127.0.0.1
   ```

2. **IP Whitelisting** (Optional): Restrict access to specific IP ranges:
   ```powershell
   New-NetFirewallRule -DisplayName "MerchantTrack - HTTPS (Whitelist)" `
       -Direction Inbound -LocalPort 443 -Protocol TCP -Action Allow `
       -RemoteAddress 192.168.0.0/24
   ```

---

## Authentication Enhancement

### Current Authentication

The application already has:
- ✅ Login/logout functionality
- ✅ Session-based authentication
- ✅ Middleware protection (`auth`, `admin`, `staff`)
- ✅ Password hashing

### Additional Security Measures

#### 1. Enable Two-Factor Authentication (2FA)

Consider implementing 2FA using packages like:
- `pragmarx/google2fa-laravel`
- `laravel/fortify` (includes 2FA)

#### 2. Password Policy

Ensure strong passwords:
- Minimum 8 characters
- Mix of uppercase, lowercase, numbers, symbols
- Password expiration (optional)

#### 3. Rate Limiting

Already configured in Laravel. Verify in `app/Http/Kernel.php`:

```php
'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
```

Apply to login routes:
```php
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
```

#### 4. Session Security

Ensure in `config/session.php`:
- `secure => true` (for HTTPS)
- `http_only => true` (prevent JavaScript access)
- `same_site => 'lax'` or `'strict'`

#### 5. CSRF Protection

Laravel includes CSRF protection by default. Ensure all forms include:
```php
@csrf
```

---

## Testing

### 1. Test HTTPS Connection

```powershell
# Test HTTPS connection
curl -k https://192.168.0.144

# Test from browser
# Navigate to: https://192.168.0.144
```

### 2. Test HTTP Redirect

```powershell
# Should redirect to HTTPS
curl -I http://192.168.0.144
```

### 3. Test WebSocket

Open browser console (F12) and check for WebSocket connection:
- Should connect to `wss://192.168.0.144/app/...`
- No connection errors

### 4. Test Authentication

1. Try accessing protected routes without login (should redirect to login)
2. Test login functionality
3. Verify session persists
4. Test logout

### 5. Security Headers

Check security headers:
```powershell
curl -I https://192.168.0.144
```

Should see:
- `Strict-Transport-Security`
- `X-Frame-Options`
- `X-Content-Type-Options`
- `X-XSS-Protection`

---

## Production Checklist

Before deploying to production:

### SSL/TLS
- [ ] Use trusted SSL certificate (Let's Encrypt or commercial)
- [ ] Certificate is valid and not expired
- [ ] SSL configuration uses strong ciphers (TLS 1.2+)
- [ ] HTTP redirects to HTTPS

### Reverse Proxy
- [ ] Nginx/Apache is properly configured
- [ ] Proxy headers are correctly set
- [ ] WebSocket connections work through proxy
- [ ] Static files are served efficiently

### Firewall
- [ ] Only necessary ports are open
- [ ] Laravel port (8000) is restricted to localhost
- [ ] HTTPS (443) and HTTP (80) are open
- [ ] WebSocket port (6001) is configured appropriately

### Laravel Configuration
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` uses HTTPS
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Configuration cache is enabled: `php artisan config:cache`

### Authentication
- [ ] Strong password policy enforced
- [ ] Rate limiting on login routes
- [ ] Session timeout configured
- [ ] CSRF protection enabled
- [ ] Consider implementing 2FA

### Security Headers
- [ ] HSTS header is set
- [ ] X-Frame-Options is set
- [ ] X-Content-Type-Options is set
- [ ] X-XSS-Protection is set

### Monitoring
- [ ] Error logging is configured
- [ ] Access logs are monitored
- [ ] Failed login attempts are logged
- [ ] Regular security updates are applied

---

## Troubleshooting

### SSL Certificate Issues

**Problem:** Browser shows "Not Secure" warning
- **Solution:** For self-signed certificates, this is expected. Click "Advanced" → "Proceed to site"

**Problem:** Certificate not trusted
- **Solution:** Use Let's Encrypt or commercial certificate for production

### Reverse Proxy Issues

**Problem:** 502 Bad Gateway
- **Solution:** Check that Laravel server is running on port 8000
- **Solution:** Verify proxy_pass URL in nginx/apache config

**Problem:** WebSocket not connecting
- **Solution:** Check WebSocket proxy configuration
- **Solution:** Verify WebSocket server is running on port 6001
- **Solution:** Check firewall rules for port 6001

### Laravel Issues

**Problem:** Mixed content warnings (HTTP resources on HTTPS page)
- **Solution:** Ensure `APP_URL` uses HTTPS
- **Solution:** Update all asset URLs to use HTTPS

**Problem:** Session not persisting
- **Solution:** Check `SESSION_SECURE_COOKIE` setting
- **Solution:** Verify cookie domain is correct

---

## Additional Resources

- [Laravel Security Documentation](https://laravel.com/docs/security)
- [Nginx SSL Configuration](https://nginx.org/en/docs/http/configuring_https_servers.html)
- [Apache SSL Configuration](https://httpd.apache.org/docs/2.4/ssl/)
- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)
- [OWASP Security Guidelines](https://owasp.org/www-project-top-ten/)

---

## Support

If you encounter issues:
1. Check the troubleshooting section
2. Review server logs (nginx/apache error logs)
3. Check Laravel logs: `storage/logs/laravel.log`
4. Verify firewall rules are active
5. Test connectivity from client device

---

**Last Updated:** 2024
**Version:** 1.0
