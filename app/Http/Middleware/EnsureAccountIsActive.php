<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Models\Staff;
use App\Support\AccountDirectory;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach (array_keys(AccountDirectory::guardModelMap()) as $guard) {
            $account = Auth::guard($guard)->user();

            if (($account instanceof Customer || $account instanceof Staff) && !$account->is_active) {
                Auth::guard($guard)->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->withErrors(['email' => 'This account is inactive. Please contact the administrator.']);
            }
        }

        return $next($request);
    }
}
