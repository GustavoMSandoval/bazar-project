<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AutoLoginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (!Auth::check()) {
            // Autentica automaticamente o primeiro usuário
            $user = User::first();
            if (!$user) {
                $user = User::create([
                    'name' => 'admin',
                    'email' => 'admin@example.com',
                    'password' => bcrypt('12345678'), // senha nunca usada diretamente
                ]);
            }
            Auth::login($user);
        }

        return $next($request);
    }
}
