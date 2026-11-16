<?php
require __DIR__.'/ProfileCheck.php';
use App\Models\User;
use App\Support\AccessRoles;
use Illuminate\Http\Request;
$reports=['resultados.index','resultados.show','resultados.imprimir','resultados.excel','resultados.excel.consolidado','resultados.positivos.mensual'];
foreach(['cytology_office','anatomopathology_office','reception','result_reader'] as $role){
    $user=User::create(['name'=>$role,'username'=>'result_permission_'.$role,'role'=>$role,'is_active'=>true,'password'=>'Prueba_123']);
    $writer=in_array($role,['reception','result_reader'],true);
    foreach(array_merge($reports,['resultados.create','resultados.store']) as $name){
        $expected=in_array($name,$reports,true)||$writer;
        if(AccessRoles::allows($user,$name)!==$expected)throw new RuntimeException('Wrong access '.$role.' '.$name);
        $r=Request::create('/');$r->setUserResolver(fn()=>$user);$route=new Illuminate\Routing\Route('GET','/',fn()=>null);$route->name($name);$r->setRouteResolver(fn()=>$route);
        try{(new App\Http\Middleware\ModuleAccess())->handle($r,fn()=>response('ok'));if(!$expected)throw new RuntimeException('Direct route allowed');}
        catch(Symfony\Component\HttpKernel\Exception\HttpException $e){if($expected||$e->getStatusCode()!==403)throw $e;}
    }
    if(!$writer){
        $r=Request::create('/','POST');$r->setUserResolver(fn()=>$user);
        try{app(App\Http\Controllers\ResultadoController::class)->store($r,new App\Models\PathologyRequest());throw new RuntimeException('Read-only user wrote a result');}
        catch(Symfony\Component\HttpKernel\Exception\HttpException $e){if($e->getStatusCode()!==403)throw $e;}
    }
}
echo "PASS: cytology/anatomopathology reports are read-only; reception/readers can edit; middleware and direct writes enforce permissions.\n";
