@php
    $laboratorio = asset('img/laboratorio.png');
    $logoHospital = asset('img/logo-hospital.png');
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Patología</title>
    <link rel="stylesheet" href="{{ asset('css/ui-icons.css') }}?v={{ filemtime(public_path('css/ui-icons.css')) }}">

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #edf1f2;
        }

        body {
            min-height: 100vh;
            overflow: hidden;
        }

        .login-page {
            width: 100vw;
            min-height: 100vh;
            background: #edf1f2;
        }

        .login-layout {
            width: 100vw;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 58% 42%;
            overflow: hidden;
        }

        /* =========================
           PANEL IZQUIERDO
        ========================= */
        .login-left {
            position: relative;
            min-height: 100vh;
            background-image: url('{{ $laboratorio }}');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 60% center;
            overflow: hidden;
        }

        .login-left::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: 100%;
            background: linear-gradient(to right, rgba(247,248,248,0) 0%, #f7f8f8 88%);
            pointer-events: none;
        }

        /* =========================
           PANEL DERECHO
        ========================= */
        .login-right {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f7f8f8;
            padding: 30px 48px;
        }

        .login-box {
            width: 100%;
            max-width: 560px;
            text-align: center;
            margin-top: -10px;
        }

        .logo-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
            padding: 10px 14px;
            border-radius: 26px;
            background: rgba(255, 255, 255, 0.82);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.04);
        }

        .login-logo {
            width: 260px;
            max-width: 100%;
            height: auto;
            display: block;
            border-radius: 18px;
        }

        .login-title {
            margin: 10px 0 0 0;
            font-size: clamp(36px, 3vw, 56px);
            font-weight: 700;
            line-height: 1.08;
            color: #1d314c;
        }

        .title-line {
            width: 165px;
            height: 5px;
            border-radius: 999px;
            margin: 18px auto 32px auto;
            background: linear-gradient(to right, #0c9a4d 0%, #eef0ee 48%, #d92e2e 100%);
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-control {
            width: 100%;
            height: 72px;
            border: 1px solid #d4dce2;
            border-radius: 22px;
            outline: none;
            background: #ffffff;
            padding: 0 24px;
            font-size: 17px;
            color: #22344d;
            transition: all 0.2s ease;
        }

        .form-control::placeholder {
            color: #7d8690;
        }

        .form-control:focus {
            border-color: #119c4f;
            box-shadow: 0 0 0 3px rgba(17, 156, 79, 0.08);
        }

        .login-btn {
            width: 100%;
            height: 68px;
            border: none;
            border-radius: 22px;
            background: #079546;
            color: #ffffff;
            font-size: 19px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .login-btn:hover {
            background: #067f3c;
        }

        .error-text {
            margin-top: 8px;
            padding-left: 5px;
            text-align: left;
            font-size: 13px;
            color: #d93025;
        }

        /* =========================
           RESPONSIVE
        ========================= */
        @media (max-width: 1400px) {
            .login-layout {
                grid-template-columns: 57% 43%;
            }

            .login-left {
                background-position: 61% center;
            }

            .login-logo {
                width: 240px;
            }
        }

        @media (max-width: 1200px) {
            .login-layout {
                grid-template-columns: 55% 45%;
            }

            .login-left {
                background-position: 62% center;
            }

            .login-right {
                padding: 24px 36px;
            }

            .login-logo {
                width: 220px;
            }

            .login-title {
                font-size: 42px;
            }

            .form-control {
                height: 66px;
                font-size: 16px;
            }

            .login-btn {
                height: 64px;
                font-size: 18px;
            }
        }

        @media (max-width: 992px) {
            html,
            body {
                overflow: auto;
            }

            .login-layout {
                grid-template-columns: 1fr;
                grid-template-rows: 40vh auto;
                min-height: 100vh;
            }

            .login-left {
                min-height: 40vh;
                background-position: center center;
            }

            .login-left::after {
                display: none;
            }

            .login-right {
                min-height: auto;
                padding: 32px 22px 36px;
            }

            .login-box {
                max-width: 520px;
                margin-top: 0;
            }

            .logo-wrap {
                padding: 8px 12px;
                border-radius: 22px;
                margin-bottom: 10px;
            }

            .login-logo {
                width: 190px;
            }

            .login-title {
                font-size: 34px;
            }

            .title-line {
                width: 120px;
                margin: 14px auto 22px auto;
            }

            .form-control {
                height: 60px;
                border-radius: 18px;
                font-size: 15px;
            }

            .login-btn {
                height: 60px;
                border-radius: 18px;
                font-size: 17px;
            }
        }

        @media (max-width: 576px) {
            .login-layout {
                grid-template-rows: 32vh auto;
            }

            .login-left {
                min-height: 32vh;
            }

            .login-right {
                padding: 24px 16px 28px;
            }

            .login-logo {
                width: 155px;
            }

            .login-title {
                font-size: 28px;
                white-space: normal;
            }

            .title-line {
                width: 100px;
                height: 4px;
            }

            .form-control {
                height: 56px;
                padding: 0 18px;
                border-radius: 16px;
            }

            .login-btn {
                height: 56px;
                border-radius: 16px;
                font-size: 16px;
            }
        }

        /* Composición final: acceso centrado, compacto y de jerarquía institucional. */
        .login-layout {
            grid-template-columns: minmax(0, 54%) minmax(420px, 46%);
        }

        .login-left {
            background-position: center;
        }

        .login-right {
            position: relative;
            padding: 36px;
            background:
                radial-gradient(circle at 85% 12%, rgba(12,154,77,.08), transparent 30%),
                #f4f7f5;
        }

        .login-box {
            max-width: 440px;
            margin: 0;
            padding: 34px 38px 38px;
            border: 1px solid rgba(17,120,70,.12);
            border-radius: 28px;
            background: rgba(255,255,255,.96);
            box-shadow: 0 22px 60px rgba(25,61,43,.12);
        }

        .logo-wrap {
            width: 126px;
            height: 126px;
            margin: 0 auto 20px;
            padding: 8px;
            overflow: hidden;
            border: 1px solid #e6eee9;
            border-radius: 30% / 27%;
            background: #fff;
            box-shadow: 0 10px 26px rgba(21,89,54,.10);
        }

        .login-logo {
            width: 100%;
            height: 100%;
            border-radius: 24% / 21%;
            object-fit: cover;
        }

        .login-eyebrow {
            margin: 0 0 8px;
            color: #10824b;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .login-title {
            margin: 0;
            color: #173b2b;
            font-size: 31px;
            line-height: 1.15;
        }

        .login-subtitle {
            margin: 9px 0 24px;
            color: #718078;
            font-size: 14px;
            line-height: 1.45;
        }

        .title-line { display: none; }

        .login-form { text-align: left; }

        .form-group { margin-bottom: 16px; }

        .form-label {
            display: block;
            margin: 0 0 7px 3px;
            color: #385346;
            font-size: 13px;
            font-weight: 600;
        }

        .form-control {
            height: 54px;
            padding: 0 17px;
            border-color: #d7e2dc;
            border-radius: 14px;
            font-size: 15px;
        }

        .form-control:focus {
            border-color: #168750;
            box-shadow: 0 0 0 4px rgba(22,135,80,.10);
        }

        .login-btn {
            height: 54px;
            margin-top: 4px;
            border-radius: 14px;
            font-size: 15px;
            letter-spacing: .025em;
            box-shadow: 0 10px 20px rgba(7,149,70,.18);
        }

        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 13px 24px rgba(7,149,70,.24);
        }

        @media (max-width: 992px) {
            body { overflow: auto; }
            .login-layout {
                grid-template-columns: 1fr;
                grid-template-rows: 30vh minmax(70vh, auto);
            }
            .login-left { min-height: 30vh; }
            .login-right { min-height: 70vh; padding: 28px 18px; }
            .login-box { width: 100%; max-width: 430px; padding: 28px 30px 32px; }
            .logo-wrap { width: 108px; height: 108px; margin-bottom: 16px; }
            .login-title { font-size: 28px; }
        }

        @media (max-width: 576px) {
            .login-layout { grid-template-rows: 24vh minmax(76vh, auto); }
            .login-left { min-height: 24vh; }
            .login-right { min-height: 76vh; padding: 20px 14px; }
            .login-box { padding: 24px 20px 26px; border-radius: 22px; }
            .logo-wrap { width: 92px; height: 92px; }
            .login-title { font-size: 25px; }
            .login-subtitle { margin-bottom: 20px; }
            .form-control, .login-btn { height: 52px; border-radius: 13px; }
        }

        /* Variante seleccionada: fotografía completa y acceso centrado. */
        body { overflow: hidden; }

        .login-layout {
            position: relative;
            isolation: isolate;
            display: grid;
            grid-template-columns: 1fr;
            grid-template-rows: 1fr;
            min-height: 100vh;
        }

        .login-left {
            position: absolute;
            z-index: -2;
            inset: 0;
            min-height: 100%;
            background-size: cover;
            background-position: center 48%;
            transform: scale(1.015);
        }

        .login-left::after {
            inset: 0;
            width: auto;
            height: auto;
            background:
                radial-gradient(circle at center, rgba(2,39,24,.22), rgba(1,42,25,.54)),
                linear-gradient(135deg, rgba(0,91,51,.42), rgba(1,31,20,.52));
            backdrop-filter: blur(1.5px);
        }

        .login-right {
            z-index: 1;
            min-height: 100vh;
            padding: 28px 20px;
            background: transparent;
        }

        .login-box {
            width: min(440px, 100%);
            max-width: 440px;
            padding: 34px 38px 38px;
            border-color: rgba(255,255,255,.62);
            background: rgba(255,255,255,.95);
            box-shadow:
                0 28px 75px rgba(0,30,18,.34),
                0 1px 0 rgba(255,255,255,.85) inset;
            backdrop-filter: blur(14px);
        }

        @media (max-width: 992px) {
            body { overflow: auto; }
            .login-layout { grid-template-rows: 1fr; }
            .login-left { min-height: 100%; background-position: 42% center; }
            .login-right { min-height: 100vh; padding: 24px 16px; }
            .login-box { max-width: 430px; padding: 28px 30px 32px; }
        }

        @media (max-width: 576px) {
            .login-layout { grid-template-rows: 1fr; }
            .login-left { min-height: 100%; }
            .login-right { min-height: 100vh; padding: 16px 12px; }
            .login-box { padding: 22px 20px 24px; }
        }
        /* Allow scrolling on short screens and while the mobile keyboard is open. */
        body { overflow: auto; }
        .login-page, .login-layout { width: 100%; min-height: 100dvh; }
        .login-right { min-height: 100dvh; }
        .form-control { font-size: 16px; }
        .login-password-field { position: relative; }
        .login-password-field .form-control { padding-right: 58px; }
        .login-password-toggle { position: absolute; right: 9px; top: 50%; transform: translateY(-50%); display: grid; place-items: center; width: 42px; height: 42px; padding: 9px; border: 0; border-radius: 10px; background: transparent; color: #526c60; cursor: pointer; }
        .login-password-toggle:hover { background: #edf5f0; color: #087d45; }
        .login-password-toggle:focus-visible { outline: 2px solid #119c4f; outline-offset: 2px; }
        .login-password-toggle svg { width: 22px; height: 22px; }
        .login-password-toggle .eye-slash { display: none; }
        .login-password-toggle[aria-pressed="true"] .eye-slash { display: block; }
        #password::-ms-reveal { display: none; }

    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-layout">

            <div class="login-left"></div>

            <div class="login-right">
                <div class="login-box">
                    <div class="logo-wrap">
                        <img src="{{ $logoHospital }}" alt="Hospital Daniel Bracamonte" class="login-logo">
                    </div>

                    <p class="login-eyebrow">Sistema de Patología</p>
                    <h1 class="login-title">Laboratorio de Patología</h1>
                    <p class="login-subtitle">Ingrese sus credenciales para acceder al sistema.</p>
                    <div class="title-line"></div>

                    <form method="POST" action="{{ route('login.post') }}" class="login-form">
                        @csrf

                        <div class="form-group">
                            <label for="username" class="form-label"><span class="ui-field-caption"><x-ui-icon name="user" /> Usuario</span></label>
                            <input
                                id="username"
                                type="text"
                                name="username"
                                class="form-control"
                                placeholder="Usuario"
                                autocomplete="username"
                                value="{{ old('username') }}"
                                required
                                autofocus
                            >
                            @error('username')
                                <div class="error-text">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label"><span class="ui-field-caption"><x-ui-icon name="lock" /> Contraseña</span></label>
                            <div class="login-password-field">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Contraseña"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="login-password-toggle" id="login-password-toggle" aria-label="Mostrar contraseña" title="Mostrar contraseña" aria-controls="password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="eye-slash" d="M3 3l18 18"/></svg>
                            </button>
                            </div>
                            @error('password')
                                <div class="error-text">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="login-btn"><x-ui-icon name="lock" /> INICIAR SESIÓN</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
    <script>
        const passwordField = document.getElementById('password');
        const passwordToggle = document.getElementById('login-password-toggle');
        passwordToggle.addEventListener('click', () => {
            const show = passwordField.type === 'password';
            passwordField.type = show ? 'text' : 'password';
            passwordToggle.setAttribute('aria-pressed', String(show));
            passwordToggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
            passwordToggle.title = show ? 'Ocultar contraseña' : 'Mostrar contraseña';
        });
    </script>
</body>
</html>
