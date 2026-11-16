<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\SystemAuditor;
use Symfony\Component\HttpFoundation\Response;
final class AuditSystemActivity {
    public function handle(Request $request,Closure $next): Response {
        $request->attributes->set('audit_web_request',true);
        $request->attributes->set('audit_pending',[]);
        if(Auth::check()){
            $u=Auth::user();$request->attributes->set('audit_actor',['id'=>$u->id,'name'=>$u->name,'username'=>$u->username,'role'=>$u->role]);
        }
        $execute=function()use($request,$next){
            $response=$next($request);
            foreach($request->attributes->get('audit_pending',[]) as $mutation) {
                SystemAuditor::record('table_mutation',$mutation['table'],null,null,null,['operation'=>$mutation['operation'],'bindings'=>$mutation['bindings']]);
            }
            $request->attributes->set('audit_pending',[]);
            if($response->getStatusCode()>=400) return $response;
            if($request->attributes->get('audit_actor') || Auth::check()){
                $subjects=[];
                foreach($request->route()?->parameters()??[] as $key=>$value){
                    if($value instanceof \Illuminate\Database\Eloquent\Model)$subjects[$key]=['table'=>$value->getTable(),'id'=>$value->getKey()];
                }
                $route=(string)$request->route()?->getName();
                $action=$request->isMethod('GET') ? (str_contains($route,'excel')?'exported':(str_contains($route,'imprimir')?'print_requested':'accessed')) : 'request_completed';
                SystemAuditor::record($action,null,null,null,null,['method'=>$request->method(),'status'=>$response->getStatusCode(),'subjects'=>$subjects]);
            }
            return $response;
        };
        // Same transaction for a mutation and its model/pivot audit trail.
        // GET requests are also wrapped: legacy screens can repair missing samples.
        return DB::transaction($execute,1);
    }
}
