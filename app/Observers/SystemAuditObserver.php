<?php
namespace App\Observers;
use Illuminate\Database\Eloquent\Model;
use App\Support\SystemAuditor;
final class SystemAuditObserver {
    public function created(Model $model): void { SystemAuditor::record('created',$model->getTable(),$model->getKey(),null,$model->getAttributes()); }
    public function updated(Model $model): void {
        $changes=$model->getChanges();unset($changes['updated_at']);
        if(!$changes)return;
        $before=array_intersect_key($model->getRawOriginal(),$changes);
        $metadata=[];
        if(array_key_exists('password',$changes))$metadata['security_event']='password_changed';
        SystemAuditor::record('updated',$model->getTable(),$model->getKey(),$before,$changes,$metadata);
    }
    public function deleted(Model $model): void { SystemAuditor::record('deleted',$model->getTable(),$model->getKey(),$model->getAttributes()); }
    public function restored(Model $model): void { SystemAuditor::record('restored',$model->getTable(),$model->getKey(),null,$model->getAttributes()); }
}
