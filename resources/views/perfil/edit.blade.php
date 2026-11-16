@extends('layouts.app')
@section('title', 'Mi perfil')
@section('content')
@php
    $roleName = \App\Support\AccessRoles::label($user);
    $initials = collect(preg_split('/\s+/u', trim($user->name)))->take(2)->map(fn($word) => mb_strtoupper(mb_substr($word,0,1)))->implode('');
@endphp
<link rel="stylesheet" href="{{ asset('css/profile.css') }}?v={{ filemtime(public_path('css/profile.css')) }}">
<div class="profile-page">
    <header class="profile-heading"><h1>Mi perfil</h1><p>Actualice sus datos personales y la contraseña de su cuenta.</p></header>
    <div class="profile-layout">
        <aside class="profile-summary">
            <div class="profile-cover"><span class="profile-initials">{{ $initials }}</span></div>
            <div class="profile-identity"><h2>{{ $user->name }}</h2><p>{{ '@'.$user->username }}</p><span class="profile-role">{{ $roleName }}</span></div>
            <dl><div><dt><x-ui-icon name="hospital" /> Institución</dt><dd>Hospital Daniel Bracamonte</dd></div><div><dt><x-ui-icon name="file" /> Servicio</dt><dd>Laboratorio de Patología</dd></div><div><dt><x-ui-icon name="calendar" /> Miembro desde</dt><dd>{{ $user->created_at?->format('d/m/Y') ?? 'Sin registro' }}</dd></div></dl>
        </aside>
        <div class="profile-forms">
            <section class="profile-card" aria-labelledby="profile-data-title">
                <h2 id="profile-data-title">Datos personales</h2>
                @if(session('profile_success'))<p class="profile-message" role="status">{{ session('profile_success') }}</p>@endif
                @if($errors->profile->any())<div class="profile-error" role="alert">@foreach($errors->profile->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                <form method="POST" action="{{ route('perfil.update') }}">
                    @csrf @method('PATCH')
                    <div class="profile-fields">
                        <label><span class="ui-field-caption"><x-ui-icon name="user" /> Nombres y apellidos *</span><input name="name" autocomplete="name" maxlength="180" required value="{{ old('name', $user->name) }}"></label>
                        <label><span class="ui-field-caption"><x-ui-icon name="user" /> Usuario *</span><input name="username" autocomplete="username" maxlength="80" required value="{{ old('username', $user->username) }}"><small>Utilice este usuario para iniciar sesión.</small></label>
                        <label><span class="ui-field-caption"><x-ui-icon name="phone" /> Teléfono</span><input name="phone" type="tel" autocomplete="tel-national" maxlength="30" placeholder="Ej. 2622600" value="{{ old('phone', $user->phone) }}"></label>
                        <label><span class="ui-field-caption"><x-ui-icon name="mobile" /> Celular</span><input name="mobile" type="tel" autocomplete="tel" maxlength="30" placeholder="Ej. 70000000" value="{{ old('mobile', $user->mobile) }}"></label>
                        <label class="profile-full"><span class="ui-field-caption"><x-ui-icon name="mail" /> Correo electrónico</span><input name="email" type="email" autocomplete="email" maxlength="180" placeholder="correo@ejemplo.com" value="{{ old('email', $user->email) }}"></label>
                        <label class="profile-full"><span class="ui-field-caption"><x-ui-icon name="location" /> Dirección</span><input name="address" autocomplete="street-address" maxlength="255" placeholder="Dirección domiciliaria" value="{{ old('address', $user->address) }}"></label>
                    </div>
                    <footer><button type="submit" class="profile-save"><x-ui-icon name="save" /> Guardar datos</button></footer>
                </form>
            </section>
            <section class="profile-card" aria-labelledby="profile-password-title">
                <h2 id="profile-password-title">Cambiar contraseña</h2>
                @if(session('password_success'))<p class="profile-message" role="status">{{ session('password_success') }}</p>@endif
                @if($errors->password->any())<div class="profile-error" role="alert">@foreach($errors->password->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                <form method="POST" action="{{ route('perfil.password') }}" autocomplete="off">
                    @csrf @method('PUT')
                    <div class="profile-fields profile-password-fields">
                        @foreach(['current_password' => 'Contraseña actual', 'password' => 'Nueva contraseña', 'password_confirmation' => 'Confirmar contraseña'] as $field => $label)
                            <label><span class="ui-field-caption"><x-ui-icon name="lock" /> {{ $label }} *</span><span class="profile-password"><input id="{{ $field }}" type="password" name="{{ $field }}" autocomplete="off" @if($field !== 'current_password') minlength="8" @endif required><button type="button" data-password-toggle="{{ $field }}" aria-controls="{{ $field }}" aria-label="Mostrar {{ mb_strtolower($label) }}" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></span>@if($field === 'password')<small>8+ caracteres · Mayúscula · Símbolo (. _)</small>@endif</label>
                        @endforeach
                    </div>
                    <footer><button type="submit" class="profile-save"><x-ui-icon name="save" /> Actualizar contraseña</button></footer>
                </form>
            </section>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('[data-password-toggle]').forEach(button => button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.passwordToggle);
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(show));
    button.setAttribute('aria-label', `${show ? 'Ocultar' : 'Mostrar'} contraseña`);
}));
</script>
@endsection
