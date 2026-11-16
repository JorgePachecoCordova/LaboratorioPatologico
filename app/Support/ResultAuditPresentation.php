<?php
namespace App\Support;
use App\Models\ResultAudit;
final class ResultAuditPresentation {
    public static function changes(ResultAudit $audit): array {
        $before=$audit->before??[];$after=$audit->after??[];$rows=[];
        $keys=['result_date','reader','rural_report_number','visit_number','insurance_number','diagnosis','is_positive','notes','status','macroscopic_description','microscopic_description','pathologist_id'];
        foreach($keys as $key){
            // The coded rows below already explain the generated diagnosis summary.
            if($key==='diagnosis' && !empty($after['diagnoses']))continue;
            $old=$before['result'][$key]??null;$new=$after['result'][$key]??null;
            if($old===$new)continue;
            $rows[]=['field'=>AuditPresentation::field($key),'before'=>AuditPresentation::value($old,$key),'after'=>AuditPresentation::value($new,$key)];
        }
        $format=fn($items)=>implode("\n",array_map(fn($item)=>trim(($item['code']??'').' · '.($item['description']??'')),$items));
        $old=$format($before['diagnoses']??[]);$new=$format($after['diagnoses']??[]);
        if($old!==$new)$rows[]=['field'=>'Diagnósticos seleccionados','before'=>$old?:'Sin datos','after'=>$new?:'Sin datos'];
        $old=$before['cytology']??[];$new=$after['cytology']??[];
        foreach(array_unique(array_merge(array_keys($old),array_keys($new))) as $key){
            if(in_array($key,['id','pathology_result_id','created_at','updated_at'],true)||($old[$key]??null)===($new[$key]??null))continue;
            $rows[]=['field'=>'Anexo citológico: '.AuditPresentation::field($key),'before'=>AuditPresentation::value($old[$key]??null,$key),'after'=>AuditPresentation::value($new[$key]??null,$key)];
        }
        return $rows;
    }
}
