<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Str;

class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('perfil.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:180'],
            'username' => ['required', 'string', 'max:80', 'regex:/^[\pL\pM\pN _-]+$/u', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'mobile' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'address' => ['nullable', 'string', 'max:255'],
        ], [], ['name' => 'nombres y apellidos', 'username' => 'usuario', 'email' => 'correo electrónico', 'phone' => 'teléfono', 'mobile' => 'celular', 'address' => 'dirección']);
        foreach ($data as $key => $value) $data[$key] = is_string($value) ? trim($value) : $value;
        if ($user->email !== ($data['email'] ?? null)) $user->email_verified_at = null;
        $user->fill($data)->save();
        return redirect()->route('perfil.edit')->with('profile_success', 'Sus datos se actualizaron correctamente.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'string', 'min:8', new \App\Rules\PasswordCharacters(), 'confirmed', 'different:current_password'],
        ], ['current_password.current_password' => 'La contraseña actual es incorrecta.', 'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.', 'password.confirmed' => 'La confirmación no coincide con la nueva contraseña.']);
        $request->user()->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        $request->session()->regenerate();
        return redirect()->route('perfil.edit')->with('password_success', 'Su contraseña se actualizó correctamente.');
    }
}
