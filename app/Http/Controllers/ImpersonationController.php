<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /** Vuelve a la sesión del super administrador. */
    public function salir(Request $request)
    {
        $superId = $request->session()->pull('impersonator_id');

        if ($superId && ($super = User::find($superId)) && $super->is_super) {
            Auth::login($super);
            return redirect()->route('admin.dashboard')
                ->with('success', 'Volviste a tu sesión de super administrador.');
        }

        return redirect()->route('dashboard');
    }
}
