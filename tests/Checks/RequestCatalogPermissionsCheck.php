<?php
require __DIR__.'/ProfileCheck.php';
use App\Models\User;
use App\Support\AccessRoles;
use Illuminate\Http\Request;
foreach(['reception','laboratory_office','administrator','result_reader','cytology_office','anatomopathology_office'] as $role){
    $user=User::create(['name'=>$role,'username'=>'quick_'.$role,'role'=>$role,'is_active'=>true,'password'=>'Prueba_123']);
    $allowed=in_array($role,['reception','laboratory_office','administrator'],true);
    if(AccessRoles::allows($user,'solicitudes.catalogos.store')!==$allowed)throw new RuntimeException('Wrong catalog permission');
    $r=Request::create('/','POST',['type'=>'municipality','name'=>'MUNICIPIO '.$role]);$r->setUserResolver(fn()=>$user);
    try{$response=app(App\Http\Controllers\SolicitudController::class)->storeCatalogOption($r);if(!$allowed||$response->getStatusCode()!==201)throw new RuntimeException('Unexpected catalog create');}
    catch(Symfony\Component\HttpKernel\Exception\HttpException $e){if($allowed||$e->getStatusCode()!==403)throw $e;}
}
echo "PASS: reception and administrator can add request catalogs; other roles remain restricted.\n";
