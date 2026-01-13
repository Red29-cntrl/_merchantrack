<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StaffOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isStaff()) {
            abort(403, 'Unauthorized action. Staff access required.');
        }

        return $next($request);
    }
}

