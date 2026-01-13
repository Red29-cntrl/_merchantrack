<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrustProxies
{
    /**
     * The trusted proxies for this application.
     *
     * @var array|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->setTrustedProxyHeaderNames($request);
        $this->setTrustedProxyIpAddresses($request);

        return $next($request);
    }

    /**
     * Set the trusted proxy header names based on the application configuration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function setTrustedProxyHeaderNames(Request $request)
    {
        $request->setTrustedProxies([], $this->headers);
    }

    /**
     * Set the trusted proxy IP addresses.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function setTrustedProxyIpAddresses(Request $request)
    {
        $trustedIps = $this->proxies;

        if ($trustedIps === '*' || $trustedIps === '**') {
            return $this->setTrustedProxyIpAddressesToAll($request);
        }

        if (empty($trustedIps)) {
            // Default: trust all proxies (for development)
            return $this->setTrustedProxyIpAddressesToAll($request);
        }

        $trustedIps = is_string($trustedIps) ? explode(',', $trustedIps) : $trustedIps;

        if (is_array($trustedIps)) {
            return $this->setTrustedProxyIpAddressesToSpecificIps($request, $trustedIps);
        }
    }

    /**
     * Set the trusted proxy IP addresses to all IPs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function setTrustedProxyIpAddressesToAll(Request $request)
    {
        $request->setTrustedProxies(['*'], $this->headers);
    }

    /**
     * Set the trusted proxy IP addresses to specific IPs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $trustedIps
     * @return void
     */
    protected function setTrustedProxyIpAddressesToSpecificIps(Request $request, array $trustedIps)
    {
        $trustedIps = array_map('trim', $trustedIps);
        $request->setTrustedProxies($trustedIps, $this->headers);
    }
}
