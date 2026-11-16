<?php
namespace App\Http\Controllers;
use App\Models\ResultAudit;
use App\Support\AccessRoles;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
final class SystemAuditController extends Controller {
    public function index(Request $request): View {
        abort_unless(AccessRoles::admin($request->user()),403);
        $data=$request->validate(['actor'=>['nullable','string','max:80'],'action'=>['nullable',Rule::in(['created','corrected'])],'date'=>['nullable','date_format:Y-m-d','regex:/^\\d{4}-\\d{2}-\\d{2}$/','after_or_equal:0001-01-01','before_or_equal:9999-12-31']]);
        $audits=ResultAudit::query()->with('result.sample.pathologyRequest.patient')
            ->when(!empty($data['actor']),fn($q)=>$q->where('actor_username',$data['actor']))
            ->when(!empty($data['action']),fn($q)=>$q->where('action',$data['action']))
            ->when(!empty($data['date']),fn($q)=>$q->whereDate('created_at',$data['date']))->orderByDesc('id')->paginate(30)->withQueryString();
        return view('configuracion.auditoria',compact('audits'));
    }
}
