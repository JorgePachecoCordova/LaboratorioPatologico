<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnatomopathologyReportController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CytologyReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MuestraController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\ResultadoController;
use App\Http\Controllers\SolicitudController;

/*
|--------------------------------------------------------------------------
| INICIO
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('inicio')
        : redirect()->route('login');
})->name('principal');

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1')
    ->name('login.post');

/*
|--------------------------------------------------------------------------
| SISTEMA PROTEGIDO
|--------------------------------------------------------------------------
*/

Route::middleware(['patologia.auth', 'patologia.modules'])->group(function(){
Route::get('/configuracion/auditoria', [\App\Http\Controllers\SystemAuditController::class, 'index'])->middleware('patologia.admin')->name('configuracion.auditoria');

Route::get('/mi-perfil', [\App\Http\Controllers\PerfilController::class, 'edit'])->name('perfil.edit');
Route::patch('/mi-perfil', [\App\Http\Controllers\PerfilController::class, 'update'])->name('perfil.update');
Route::put('/mi-perfil/contrasena', [\App\Http\Controllers\PerfilController::class, 'password'])->middleware('throttle:6,1')->name('perfil.password');

/*
|--------------------------------------------------------------------------
| INICIO
|--------------------------------------------------------------------------
*/

Route::get('/inicio',[DashboardController::class, 'index'])
->name('inicio');

/*
|--------------------------------------------------------------------------
| PACIENTES
|--------------------------------------------------------------------------
*/

Route::get(
'/pacientes/buscar-ci',
[SolicitudController::class,'buscarPacienteCI']
)
->name('pacientes.buscar.ci');

Route::get(
'/pacientes/buscar',
[SolicitudController::class,'buscarPaciente']
)
->name('pacientes.buscar');

Route::post(
'/pacientes',
[PacienteController::class,'store']
)
->name('pacientes.store');

/*
|--------------------------------------------------------------------------
| SOLICITUDES
|--------------------------------------------------------------------------
*/

Route::get(
'/solicitudes',
[SolicitudController::class,'index']
)
->name('solicitudes.index');

Route::get('/solicitudes/registro-diario', \App\Http\Controllers\DailyRegisterController::class)
    ->name('solicitudes.registro-diario');

Route::get(
'/solicitudes/buscar',
[SolicitudController::class,'buscar']
)
->name('solicitudes.buscar');

Route::get(
'/solicitudes/crear/{registro}',
[SolicitudController::class,'create']
)
->whereNumber('registro')
->name('solicitudes.create');

Route::post(
'/solicitudes',
[SolicitudController::class,'store']
)
->name('solicitudes.store');

Route::post(
'/solicitudes/catalogos',
[SolicitudController::class,'storeCatalogOption']
)
->name('solicitudes.catalogos.store');

Route::get(
'/solicitudes/{solicitud}',
[SolicitudController::class,'show']
)
->whereNumber('solicitud')
->name('solicitudes.show');

Route::delete(
'/solicitudes/{solicitud}',
[SolicitudController::class,'destroy']
)
->whereNumber('solicitud')
->name('solicitudes.destroy');

/*
|--------------------------------------------------------------------------
| HISTORIAL PACIENTE
|--------------------------------------------------------------------------
*/

Route::get(
'/historial-pacientes',
[SolicitudController::class,'historial']
)
->name('historial.index');

Route::get(
'/pacientes/{paciente}/solicitudes',
[SolicitudController::class,'historialPaciente']
)
->whereNumber('paciente')
->name('pacientes.historial');

/*
|--------------------------------------------------------------------------
| AJAX UBICACIÓN GEOGRÁFICA
|--------------------------------------------------------------------------
*/

Route::get(
'/ubicacion/municipios/{secretaria}',
[SolicitudController::class,'municipios']
)
->whereNumber('secretaria')
->name('ubicacion.municipios');

Route::get(
'/ubicacion/distritos/{municipio}',
[SolicitudController::class,'distritos']
)
->whereNumber('municipio')
->name('ubicacion.distritos');

Route::get(
'/ubicacion/areas/{distrito}',
[SolicitudController::class,'areas']
)
->whereNumber('distrito')
->name('ubicacion.areas');

Route::get(
'/ubicacion/sectores/{area}',
[SolicitudController::class,'sectores']
)
->whereNumber('area')
->name('ubicacion.sectores');

Route::get(
    '/ubicacion/establecimientos',
    [SolicitudController::class,'establecimientos']
)
->name('ubicacion.establecimientos');
/*
|--------------------------------------------------------------------------
| REGISTROS
|--------------------------------------------------------------------------
*/

Route::post(
'/registros',
[RegistroController::class,'store']
)
->name('registros.store');

/*
|--------------------------------------------------------------------------
| MUESTRAS
|--------------------------------------------------------------------------
*/

Route::post(
'/solicitudes/{solicitud}/muestras',
[MuestraController::class,'store']
)
->whereNumber('solicitud')
->name('muestras.store');

/*
|--------------------------------------------------------------------------
| RESULTADOS
|--------------------------------------------------------------------------
*/

Route::redirect('/anatomia-patologica', '/anatomopatologico');
Route::get('/anatomopatologico', [AnatomopathologyReportController::class, 'index'])->name('anatomopatologia.index');
Route::post('/informes-citologicos/catalogos', [CytologyReportController::class, 'storeCatalogOption'])
    ->name('citologia.catalogos.store');
Route::post('/anatomopatologico/catalogos', [AnatomopathologyReportController::class, 'storeCatalogOption'])
    ->name('anatomopatologia.catalogos.store');
Route::post('/anatomopatologico', [AnatomopathologyReportController::class, 'store'])->name('anatomopatologia.store');
Route::put('/anatomopatologico/{anatomopatologia}', [AnatomopathologyReportController::class, 'update'])->whereNumber('anatomopatologia')->name('anatomopatologia.update');
Route::get('/anatomopatologico/{anatomopatologia}/imprimir', [AnatomopathologyReportController::class, 'print'])->whereNumber('anatomopatologia')->name('anatomopatologia.imprimir');

Route::get(
'/resultados',
[ResultadoController::class,'index']
)
->name('resultados.index');

Route::get(
'/informes-citologicos',
[CytologyReportController::class,'index']
)
->name('citologia.index');

Route::post('/informes-citologicos', [CytologyReportController::class, 'store'])
    ->name('citologia.store');

Route::put('/informes-citologicos/{citologia}', [CytologyReportController::class, 'updateRequest'])
    ->whereNumber('citologia')
    ->name('citologia.update');

Route::put('/informes-citologicos/{citologia}/informe', [CytologyReportController::class, 'updateReport'])
    ->whereNumber('citologia')
    ->name('citologia.informe.update');

Route::get('/informes-citologicos/{citologia}/imprimir', [CytologyReportController::class, 'print'])
    ->whereNumber('citologia')
    ->name('citologia.imprimir');

Route::get('/informes-citologicos/{citologia}/excel', [CytologyReportController::class, 'excel'])
    ->whereNumber('citologia')
    ->name('citologia.excel');

Route::get('/informes-citologicos/{citologia}/imprimir-solicitud', [CytologyReportController::class, 'printRequest'])
    ->whereNumber('citologia')
    ->name('citologia.solicitud.imprimir');

Route::post('/resultados/diagnosticos', [\App\Http\Controllers\ResultDiagnosisController::class, 'store'])->name('resultados.diagnosticos.store');

Route::get(
'/resultados/{solicitud}/crear',
[ResultadoController::class,'create']
)
->whereNumber('solicitud')
->name('resultados.create');

Route::post(
'/solicitudes/{solicitud}/resultado',
[ResultadoController::class,'store']
)
->whereNumber('solicitud')
->name('resultados.store');

Route::get(
'/resultados/{resultado}',
[ResultadoController::class,'show']
)
->whereNumber('resultado')
->name('resultados.show');

Route::get(
'/resultados/{resultado}/imprimir',
[ResultadoController::class,'imprimir']
)
->whereNumber('resultado')
->name('resultados.imprimir');

Route::get('/resultados/{resultado}/excel', [ResultadoController::class, 'excel'])
->whereNumber('resultado')
->name('resultados.excel');
Route::get('/reportes/resultados/excel', [ResultadoController::class, 'consolidatedExcel'])->name('resultados.excel.consolidado');
Route::get('/reportes/resultados/positivos-mensual', [ResultadoController::class, 'monthlyPositiveExcel'])->name('resultados.positivos.mensual');
Route::get('/reportes/citologia/estadistico', [ResultadoController::class, 'cytologyStatisticalExcel'])->name('citologia.reportes.estadistico');

Route::delete(
'/resultados/{resultado}',
[ResultadoController::class,'destroy']
)
->whereNumber('resultado')
->name('resultados.destroy');

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN
|--------------------------------------------------------------------------
*/

Route::middleware('patologia.admin')->group(function(){
Route::redirect('/configuracion/numeracion', '/configuracion')->name('configuracion.numeracion');

Route::post('/configuracion/protocolos', [ConfiguracionController::class, 'storeProtocolo'])
    ->name('configuracion.protocolos.store');
Route::patch('/configuracion/protocolos/{protocolo}/estado', [ConfiguracionController::class, 'toggleProtocolo'])
    ->whereNumber('protocolo')->name('configuracion.protocolos.estado');

Route::get(
'/configuracion',
[ConfiguracionController::class,'index']
)
->name('configuracion.index');

Route::post(
'/configuracion/usuarios',
[ConfiguracionController::class,'storeUsuario']
)
->name('configuracion.usuarios.store');

Route::put(
'/configuracion/usuarios/{usuario}',
[ConfiguracionController::class,'updateUsuario']
)
->whereNumber('usuario')
->name('configuracion.usuarios.update');

Route::patch(
'/configuracion/usuarios/{usuario}/estado',
[ConfiguracionController::class,'toggleUsuario']
)
->whereNumber('usuario')
->name('configuracion.usuarios.estado');

Route::post(
'/configuracion/medicos',
[ConfiguracionController::class,'storeMedico']
)
->name('configuracion.medicos.store');

Route::put(
'/configuracion/medicos/{medico}',
[ConfiguracionController::class,'updateMedico']
)
->whereNumber('medico')
->name('configuracion.medicos.update');

Route::patch(
'/configuracion/medicos/{medico}/estado',
[ConfiguracionController::class,'toggleMedico']
)
->whereNumber('medico')
->name('configuracion.medicos.estado');

Route::post(
'/configuracion/municipios',
[ConfiguracionController::class,'storeMunicipio']
)
->name('configuracion.municipios.store');

Route::put(
'/configuracion/municipios/{municipio}',
[ConfiguracionController::class,'updateMunicipio']
)
->whereNumber('municipio')
->name('configuracion.municipios.update');

Route::patch(
'/configuracion/municipios/{municipio}/estado',
[ConfiguracionController::class,'toggleMunicipio']
)
->whereNumber('municipio')
->name('configuracion.municipios.estado');

Route::post(
'/configuracion/distritos',
[ConfiguracionController::class,'storeDistrito']
)
->name('configuracion.distritos.store');

Route::put(
'/configuracion/distritos/{distrito}',
[ConfiguracionController::class,'updateDistrito']
)
->whereNumber('distrito')
->name('configuracion.distritos.update');

Route::patch(
'/configuracion/distritos/{distrito}/estado',
[ConfiguracionController::class,'toggleDistrito']
)
->whereNumber('distrito')
->name('configuracion.distritos.estado');

Route::post(
'/configuracion/areas',
[ConfiguracionController::class,'storeArea']
)
->name('configuracion.areas.store');

Route::put(
'/configuracion/areas/{area}',
[ConfiguracionController::class,'updateArea']
)
->whereNumber('area')
->name('configuracion.areas.update');

Route::patch(
'/configuracion/areas/{area}/estado',
[ConfiguracionController::class,'toggleArea']
)
->whereNumber('area')
->name('configuracion.areas.estado');

Route::post(
'/configuracion/sectores',
[ConfiguracionController::class,'storeSector']
)
->name('configuracion.sectores.store');

Route::put(
'/configuracion/sectores/{sector}',
[ConfiguracionController::class,'updateSector']
)
->whereNumber('sector')
->name('configuracion.sectores.update');

Route::patch(
'/configuracion/sectores/{sector}/estado',
[ConfiguracionController::class,'toggleSector']
)
->whereNumber('sector')
->name('configuracion.sectores.estado');

Route::post(
'/configuracion/establecimientos',
[ConfiguracionController::class,'storeEstablecimiento']
)
->name('configuracion.establecimientos.store');

Route::put(
'/configuracion/establecimientos/{establecimiento}',
[ConfiguracionController::class,'updateEstablecimiento']
)
->whereNumber('establecimiento')
->name('configuracion.establecimientos.update');

Route::patch(
'/configuracion/establecimientos/{establecimiento}/estado',
[ConfiguracionController::class,'toggleEstablecimiento']
)
->whereNumber('establecimiento')
->name('configuracion.establecimientos.estado');

Route::post(
'/configuracion/tipos-patologia',
[ConfiguracionController::class,'storeTipoPatologia']
)
->name('configuracion.patologias.store');

Route::put(
'/configuracion/tipos-patologia/{patologia}',
[ConfiguracionController::class,'updateTipoPatologia']
)
->whereNumber('patologia')
->name('configuracion.patologias.update');

Route::patch(
'/configuracion/tipos-patologia/{patologia}/estado',
[ConfiguracionController::class,'toggleTipoPatologia']
)
->whereNumber('patologia')
->name('configuracion.patologias.estado');

});

/*
|--------------------------------------------------------------------------
| CERRAR SESIÓN
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

});
