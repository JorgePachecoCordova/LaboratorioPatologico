<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PathologyRequest extends Model
{
    use HasFactory, SoftDeletes;


    /*
    |--------------------------------------------------------------------------
    | Tipos de correlativo
    |--------------------------------------------------------------------------
    */

    public const SEQUENCE_URBAN = 'U';
    public const SEQUENCE_RURAL = 'R';
    public const SEQUENCE_GYNECOLOGICAL = 'G';



    /*
    |--------------------------------------------------------------------------
    | Estados de solicitud
    |--------------------------------------------------------------------------
    */

    public const STATUS_REGISTERED = 'registered';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_REPORTED = 'reported';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';



    /*
    |--------------------------------------------------------------------------
    | Campos permitidos
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'reception_batch_id',

        // Paciente
        'patient_id',
        'reader',


        /*
        | Datos geográficos
        */

        'regional_secretariat_id',

        'district_id',

        'municipality_id',

        'area_id',

        'sector_id',

        'health_facility_id',


        /*
        | Ginecología
        */

        'residence_municipality_id',
        'patient_category',



        /*
        | Médico solicitante
        */

        'requesting_physician_id',

        'requesting_physician_name',



        /*
        | Usuarios sistema
        */

        'created_by',

        'updated_by',



        /*
        | Correlativo automático
        */

        'sequence_type',

        'sequence_year',

        'sequence_number',

        'request_number',



        /*
        | Módulo
        | U = Urbano
        | R = Rural
        | G = Ginecología
        */

        'module',



        /*
        | Datos solicitud
        */

        'request_type',

        'study_type',

        'service',

        'requesting_unit',


        'sample_quantity',


        'request_date',

        'sample_collection_date',


        'status',


        'notes',

    ];




    /*
    |--------------------------------------------------------------------------
    | Conversión automática de datos
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [

            'sequence_year' => 'integer',

            'sequence_number' => 'integer',

            'sample_quantity' => 'integer',


            'request_date' => 'date',

            'sample_collection_date' => 'date',

        ];
    }

    public function receptionBatch(): BelongsTo
    {
        return $this->belongsTo(ReceptionBatch::class);
    }




    /*
    |--------------------------------------------------------------------------
    | Valores iniciales
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();


        static::creating(function ($request) {

            if (!$request->status) {

                $request->status =
                    self::STATUS_REGISTERED;

            }

        });

    }





    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */


    public function patient(): BelongsTo
    {
        return $this->belongsTo(
            Patient::class
        );
    }




    public function regionalSecretariat(): BelongsTo
    {
        return $this->belongsTo(
            RegionalSecretariat::class
        );
    }




    public function district(): BelongsTo
    {
        return $this->belongsTo(
            District::class
        );
    }




    public function municipality(): BelongsTo
    {
        return $this->belongsTo(
            Municipality::class
        );
    }





    /*
    |--------------------------------------------------------------------------
    | Municipio residencia - Ginecología
    |--------------------------------------------------------------------------
    */

    public function residenceMunicipality(): BelongsTo
    {
        return $this->belongsTo(
            Municipality::class,
            'residence_municipality_id'
        );
    }





    public function area(): BelongsTo
    {
        return $this->belongsTo(
            Area::class
        );
    }




    public function sector(): BelongsTo
    {
        return $this->belongsTo(
            Sector::class
        );
    }




    public function healthFacility(): BelongsTo
    {
        return $this->belongsTo(
            HealthFacility::class
        );
    }





    public function requestingPhysician(): BelongsTo
    {
        return $this->belongsTo(
            Physician::class,
            'requesting_physician_id'
        );
    }





    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }





    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }





    /*
    |--------------------------------------------------------------------------
    | Muestras asociadas
    |--------------------------------------------------------------------------
    */

    public function samples(): HasMany
    {
        return $this->hasMany(
            Sample::class
        );
    }




    /*
    |--------------------------------------------------------------------------
    | Métodos auxiliares
    |--------------------------------------------------------------------------
    */


    public function isUrban(): bool
    {
        return $this->module === self::SEQUENCE_URBAN;
    }



    public function isRural(): bool
    {
        return $this->module === self::SEQUENCE_RURAL;
    }



    public function isGynecological(): bool
    {
        return $this->module === self::SEQUENCE_GYNECOLOGICAL;
    }

    public function isCytology(): bool
    {
        if ($this->isGynecological()) {
            return true;
        }

        $description = mb_strtolower(implode(' ', array_filter([
            $this->request_type,
            $this->study_type,
        ])));

        return str_contains($description, 'citolog');
    }



}
