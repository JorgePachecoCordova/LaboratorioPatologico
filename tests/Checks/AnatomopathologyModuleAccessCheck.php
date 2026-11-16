<?php
require __DIR__.'/ProfileCheck.php';
use App\Models\User;
use App\Support\AccessRoles;
$user = User::create(['name'=>'Anatomopatología', 'username'=>'anatomy_scope_check', 'role'=>'anatomopathology_office', 'is_active'=>true, 'password'=>'Prueba_123']);
$user->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate(AccessRoles::CONSULT_LABORATORY, 'web'));
$count = 0;
foreach (app('router')->getRoutes() as $route) {
    $name = $route->getName();
    if (!$name || !str_starts_with($name, 'citologia.')) continue;
    if (AccessRoles::allows($user, $name)) throw new RuntimeException('Citología permitida: '.$name);
    $request = Illuminate\Http\Request::create('/');
    $request->setUserResolver(fn () => $user);
    $request->setRouteResolver(fn () => $route);
    try {
        (new App\Http\Middleware\ModuleAccess())->handle($request, fn () => response('ok'));
        throw new RuntimeException('Acceso directo permitido: '.$name);
    } catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() !== 403) throw $e;
    }
    $count++;
}
if (!$count) throw new RuntimeException('Sin rutas verificadas');
foreach (['anatomopatologia.index', 'anatomopatologia.store', 'anatomopatologia.imprimir', 'resultados.index', 'resultados.show', 'resultados.imprimir', 'resultados.excel'] as $name) {
    if (!AccessRoles::allows($user, $name)) throw new RuntimeException('Acceso necesario bloqueado: '.$name);
}
foreach (['resultados.create', 'resultados.store'] as $name) {
    if (AccessRoles::allows($user, $name)) throw new RuntimeException('Escritura de resultados permitida');
}
echo "PASS: $count rutas de citología bloqueadas para Anatomopatológico; módulo propio y reportes de resultados conservados.\n";