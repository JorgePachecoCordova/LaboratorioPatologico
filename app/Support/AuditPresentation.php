<?php
namespace App\Support;
use App\Models\SystemAudit;
final class AuditPresentation {
    public const ACTIONS=['created'=>'Creó un registro','updated'=>'Modificó datos','deleted'=>'Eliminó o archivó','restored'=>'Restauró un registro','login'=>'Inició sesión','login_failed'=>'Intento de acceso fallido','logout'=>'Cerró sesión','accessed'=>'Consultó una pantalla','print_requested'=>'Solicitó una impresión','exported'=>'Descargó un archivo','table_mutation'=>'Actualizó datos relacionados','request_completed'=>'Procesó una operación'];
    public const TABLES=['users'=>'Usuarios','patients'=>'Pacientes','pathology_requests'=>'Solicitudes','samples'=>'Muestras','pathology_results'=>'Resultados','result_diagnoses'=>'Diagnósticos del resultado','cytology_reports'=>'Informes citológicos','anatomopathology_reports'=>'Anatomopatología','areas'=>'Áreas','districts'=>'Distritos','municipalities'=>'Municipios','regional_secretariats'=>'Secretarías regionales','sectors'=>'Sectores','health_facilities'=>'Establecimientos','hospital_services'=>'Servicios','physicians'=>'Médicos','pathology_types'=>'Tipos de estudio','cytology_diagnoses'=>'Catálogo de diagnósticos','cap_protocol_fields'=>'Protocolos CAP','cytology_catalog_options'=>'Catálogos citológicos','model_has_roles'=>'Roles de usuarios','model_has_permissions'=>'Permisos de usuarios','role_has_permissions'=>'Permisos de roles','roles'=>'Roles','permissions'=>'Permisos'];
    public const FIELDS=['id'=>'Identificador','full_name'=>'Nombre y apellidos','name'=>'Nombre','username'=>'Usuario','email'=>'Correo','role'=>'Rol','is_active'=>'Cuenta o registro activo','birth_date'=>'Fecha de nacimiento','identity_document'=>'Documento de identidad','sex'=>'Sexo','phone'=>'Teléfono','mobile'=>'Celular','address'=>'Dirección','request_number'=>'Número de solicitud','request_date'=>'Fecha de solicitud','result_date'=>'Fecha del resultado','report_date'=>'Fecha del informe','report_number'=>'Número de informe','rural_report_number'=>'Número de informe rural','reader'=>'Lector','diagnosis'=>'Diagnóstico','description'=>'Descripción','conclusion'=>'Conclusión','notes'=>'Comentario','note'=>'Nota','status'=>'Estado','is_positive'=>'Clasificación positiva','revision'=>'Versión','sample_id'=>'Identificador de muestra','patient_id'=>'Identificador de paciente','pathology_request_id'=>'Identificador de solicitud','sample_quantity'=>'Cantidad de muestras','sample_number'=>'Número de muestra','sample_type'=>'Tipo de muestra','insurance_number'=>'Número de asegurado','visit_number'=>'Vez','code'=>'Código','position'=>'Orden','module'=>'Área de atención','created_at'=>'Fecha de registro','updated_at'=>'Última modificación','deleted_at'=>'Fecha de archivo','validated_at'=>'Fecha de validación','recorded_by'=>'Identificador del autor','validated_by'=>'Identificador del validador','created_by'=>'Identificador del autor','updated_by'=>'Identificador de quien modificó','pathologist_id'=>'Identificador del patólogo','clinical_diagnosis'=>'Diagnóstico presuntivo','macroscopic_description'=>'Descripción macroscópica','microscopic_description'=>'Descripción microscópica','type'=>'Tipo'];
    public static function module(SystemAudit $audit): string {
        $route=(string)$audit->route;
        if(str_starts_with($route,'configuracion.auditoria'))return 'Auditoría';
        if(str_starts_with($route,'configuracion.'))return 'Configuración';
        return match(explode('.',$route)[0]){'inicio'=>'Inicio','resultados'=>'Resultados','solicitudes'=>'Solicitudes / Muestras','pacientes'=>'Pacientes','muestras'=>'Muestras','citologia'=>'Informes citológicos','anatomopatologia'=>'Anatomopatología','perfil'=>'Mi perfil','login','logout'=>'Acceso al sistema',default=>self::TABLES[$audit->subject_type]??'Sistema'};
    }
    public static function field(string $key): string {return self::FIELDS[$key]??ucfirst(str_replace('_',' ',$key));}
    public static function value($value, string $field=''): string {
        if($value===null||$value==='')return 'Sin dato';
        if(is_bool($value)||in_array($field,['is_active','is_positive'],true))return $value?'Sí':'No';
        if($field==='role')return AccessRoles::LABELS[$value]??(string)$value;
        if($field==='module')return ['R'=>'Rural','U'=>'Urbano','G'=>'Ginecología'][$value]??(string)$value;
        if($field==='status')return ['registered'=>'Registrada','received'=>'Recibida','processing'=>'En proceso','reported'=>'Resultado registrado','validated'=>'Validado','delivered'=>'Entregado','cancelled'=>'Anulada','requested'=>'Solicitado','draft'=>'Borrador','completed'=>'Completado'][$value]??(string)$value;
        if(is_array($value))return implode('; ',array_map(fn($v)=>is_array($v)?self::value($v):(string)$v,$value))?:'Sin datos';
        return (string)$value;
    }
    public static function changes(SystemAudit $audit): array {
        $before=$audit->before??[];$after=$audit->after??[];$rows=[];
        foreach(array_unique(array_merge(array_keys($before),array_keys($after))) as $key){
            if(in_array($key,['created_at','updated_at'],true))continue;
            $rows[]=['field'=>self::field($key),'before'=>self::value($before[$key]??null,$key),'after'=>self::value($after[$key]??null,$key)];
        }
        return $rows;
    }
}
