<?php

namespace Werk365\JwtAuthRoles\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Werk365\JwtAuthRoles\Exceptions\AuthException;

class RoleMiddleware
{
    public function handle($request, Closure $next, $role)
    {
        $roles = is_array($role)
            ? $role
            : explode('|', $role);
        $user_roles = array_map('strtolower', Auth::user()->roles);
        $same = (array_intersect($roles, $user_roles));

        if (empty($same)) {
            throw AuthException::auth('401', 'User does not have right roles');
        }

        return $next($request);
    }
}
