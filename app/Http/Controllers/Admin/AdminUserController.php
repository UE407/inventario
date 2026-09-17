<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActividadLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Gestión de super administradores de la plataforma.
 */
class AdminUserController extends Controller
{
    public function index()
    {
        $admins = User::where('is_super', true)->orderBy('name')->get();
        return view('admin.admins.index', compact('admins'));
    }

    public function create()
    {
        return view('admin.admins.form', ['admin' => new User(['activo' => true])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], $this->mensajes());

        $admin = User::create([
            'empresa_id' => null,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'rol' => 'admin',
            'is_super' => true,
            'activo' => $request->boolean('activo', true),
        ]);

        ActividadLog::registrar('admin_crear', "Creó al super admin «{$admin->name}»");

        return redirect()->route('admin.admins.index')->with('success', 'Administrador creado.');
    }

    public function edit(User $admin)
    {
        abort_unless($admin->is_super, 404);
        return view('admin.admins.form', compact('admin'));
    }

    public function update(Request $request, User $admin)
    {
        abort_unless($admin->is_super, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'password' => ['nullable', 'confirmed', Password::min(6)],
        ], $this->mensajes());

        // No puedes desactivarte a ti mismo.
        $activo = $admin->id === Auth::id() ? true : $request->boolean('activo');

        $admin->name = $data['name'];
        $admin->email = $data['email'];
        $admin->activo = $activo;
        if (! empty($data['password'])) {
            $admin->password = $data['password'];
        }
        $admin->save();

        ActividadLog::registrar('admin_editar', "Editó al super admin «{$admin->name}»");

        return redirect()->route('admin.admins.index')->with('success', 'Administrador actualizado.');
    }

    public function destroy(User $admin)
    {
        abort_unless($admin->is_super, 404);

        if ($admin->id === Auth::id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        if (User::where('is_super', true)->where('activo', true)->count() <= 1) {
            return back()->with('error', 'Debe existir al menos un super administrador activo.');
        }

        $nombre = $admin->name;
        $admin->delete();

        ActividadLog::registrar('admin_eliminar', "Eliminó al super admin «{$nombre}»");

        return back()->with('success', 'Administrador eliminado.');
    }

    private function mensajes(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }
}
