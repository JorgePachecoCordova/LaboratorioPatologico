<?php
require __DIR__.'/ProfileCheck.php';
use App\Support\AccessRoles;
use App\Models\User;
use Spatie\Permission\Models\Permission;
$permission = Permission::findOrCreate(AccessRoles::CONSULT_LABORATORY, 'web');
foreach (['result_reader', 'pathologist'] as $role) {
    $reader = User::create(['name'=>'Prueba lecturante', 'username'=>'check_'.$role, 'password'=>'Prueba_123', 'role'=>$role, 'is_active'=>true]);
    $reader->givePermissionTo($permission);
    foreach (['solicitudes.index', 'solicitudes.show', 'solicitudes.registro-diario', 'citologia.index', 'anatomopatologia.index', 'configuracion.index'] as $routeName) {
        if (AccessRoles::allows($reader, $routeName)) throw new RuntimeException('Acceso indebido: '.$routeName);
        $r = Illuminate\Http\Request::create('/');
        $r->setUserResolver(fn () => $reader);
        $route = new Illuminate\Routing\Route('GET', '/', fn () => null);
        $route->name($routeName); $r->setRouteResolver(fn () => $route);
        try {
            (new App\Http\Middleware\ModuleAccess())->handle($r, fn () => response('ok'));
            throw new RuntimeException('Middleware permitió '.$routeName);
        } catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($e->getStatusCode() !== 403) throw $e;
        }
    }
    foreach (['resultados.index', 'resultados.create', 'resultados.store', 'resultados.show', 'resultados.imprimir', 'perfil.edit', 'logout'] as $routeName) {
        if (!AccessRoles::allows($reader, $routeName)) throw new RuntimeException('Acceso necesario bloqueado: '.$routeName);
    }
}
echo "PASS: lecturantes limitados a Resultados, incluso con permiso adicional previo; acceso directo bloqueado por middleware.\n";
