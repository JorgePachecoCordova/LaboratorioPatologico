<?php
namespace App\Support;
use App\Models\SystemAudit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
final class SystemAuditor {
    public static function clean(array $data): array {
        $safe=[];
        foreach($data as $key=>$value) {
            if(preg_match('/password|token|secret|credential|cookie|authorization|session|api.?key/i',(string)$key)) continue;
            $safe[$key]=is_array($value)?self::clean($value):$value;
        }
        return $safe;
    }
    public static function record(string $action,?string $type=null,$id=null,?array $before=null,?array $after=null,array $metadata=[]): void {
        $request=app()->bound('request')?request():null;
        $actor=$request?->attributes->get('audit_actor');
        if(!$actor && Auth::check()) {
            $user=Auth::user();
            $actor=['id'=>$user->id,'name'=>$user->name,'username'=>$user->username,'role'=>$user->role];
        }
        // CLI imports and migrations are deliberately not attributed to a web user.
        if(!$request || (!$actor && !$request->attributes->get('audit_web_request'))) return;
        $requestId=$request->attributes->get('audit_request_id') ?? (string)Str::uuid();
        $request->attributes->set('audit_request_id',$requestId);
        SystemAudit::create([
            'request_id'=>$requestId,'actor_id'=>$actor['id']??null,'actor_name'=>$actor['name']??null,
            'actor_username'=>$actor['username']??null,'actor_role'=>$actor['role']??null,
            'action'=>$action,'subject_type'=>$type,'subject_id'=>$id===null?null:(string)$id,
            'route'=>$request->route()?->getName(),
            'before'=>$before===null?null:self::clean($before),'after'=>$after===null?null:self::clean($after),
            'metadata'=>self::clean($metadata),'created_at'=>now(),
        ]);
    }
}
