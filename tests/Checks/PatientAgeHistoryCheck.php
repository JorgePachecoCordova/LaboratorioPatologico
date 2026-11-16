<?php
require __DIR__.'/ProfileCheck.php';
use App\Models\{Patient,PathologyRequest,PathologyResult};
use Illuminate\Support\Carbon;
function ageCheck($ok,$message){if(!$ok)throw new RuntimeException($message);}
$patient=Patient::create(['identity_document'=>'AGE-HISTORY','full_name'=>'PACIENTE EDAD','birth_date'=>'1952-08-02','is_active'=>true]);
Carbon::setTestNow('2026-10-28');ageCheck($patient->current_age===74,'Current age 2026');
Carbon::setTestNow('2031-10-28');ageCheck($patient->fresh()->current_age===79,'Current age after five years');
ageCheck($patient->ageAt('2026-10-27')===74,'Historical age changed');
ageCheck($patient->ageAt('2026-08-01')===73 && $patient->ageAt('2026-08-02')===74,'Birthday boundary');
ageCheck((new Patient())->ageAt('2026-10-28')===null,'Unknown birthday fabricated');
ageCheck($patient->ageAt('1951-01-01')===null,'Negative age');
foreach(['R','U','G'] as $module){
    $request=PathologyRequest::create(['patient_id'=>$patient->id,'module'=>$module,'sequence_type'=>$module,'sequence_year'=>2026,'sequence_number'=>1,'request_number'=>'000001-'.$module,'request_date'=>'2026-10-27','sample_quantity'=>1]);
    $sample=$request->samples()->create(['sample_number'=>1,'sample_type'=>'CITOLOGIA','status'=>'received']);
    $result=PathologyResult::create(['sample_id'=>$sample->id,'result_date'=>'2026-10-27','status'=>'validated','diagnosis'=>'PRUEBA']);
    $result->diagnoses()->create(['position'=>1,'code'=>'TEST','description'=>'PRUEBA']);
    $html=view('resultados.informe',['pathologyResult'=>$result])->render();
    ageCheck($module==='U'?str_contains($html,'<strong>Edad:</strong> 74'):str_contains($html,'<td>74</td>'),'Historical print age '.$module);
    $path=app(App\Support\PathologyResultExcelExporter::class)->create($result);
    $zip=new ZipArchive();$zip->open($path);$xml=$zip->getFromName('xl/worksheets/sheet1.xml');$zip->close();unlink($path);
    ageCheck((bool)preg_match('/Edad<\/t>.*?74<\/t>/s',$xml),'Historical Excel age '.$module);
    $result->update(['result_date'=>'2031-10-28']);
    $html=view('resultados.informe',['pathologyResult'=>$result->fresh()])->render();
    ageCheck($module==='U'?str_contains($html,'<strong>Edad:</strong> 79'):str_contains($html,'<td>79</td>'),'New attendance age '.$module);
}
Carbon::setTestNow();
echo "PASS: current search age, five-year return, historical ages, birthday boundaries, missing birthdate, and print/Excel in R/U/G.\n";
