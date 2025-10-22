<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsTeamLead
{
    public function handle(Request $request, Closure $next): Response
{
    // Ahora la lógica es mucho más clara y centralizada
    if (auth()->check() && auth()->user()->isManager()) {
        return $next($request);
    }

    abort(403, 'Acción no autorizada.');
}
}