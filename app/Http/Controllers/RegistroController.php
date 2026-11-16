<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RegistroController extends Controller
{
    /**
     * Mantiene operativa la ruta histórica /registros.
     *
     * La numeración U, R y G ahora se asigna de forma transaccional al guardar
     * la solicitud mediante RequestNumberService. Este método evita que una
     * llamada antigua intente utilizar las tablas españolas eliminadas.
     */
    public function store(Request $request): RedirectResponse
    {
        return redirect()
            ->route('solicitudes.index')
            ->with(
                'info',
                'El número correlativo se asignará automáticamente al guardar la solicitud.'
            );
    }
}