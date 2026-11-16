<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CytologyReport extends Model
{
    use HasFactory, SoftDeletes;

    public const ADEQUACY_SATISFACTORY = 'satisfactory';
    public const ADEQUACY_UNSATISFACTORY = 'unsatisfactory';

    public const CATEGORY_NEGATIVE = 'negative';
    public const CATEGORY_EPITHELIAL_ABNORMALITY = 'epithelial_abnormality';
    public const CATEGORY_OTHER = 'other';

    protected $fillable = [
        'pathology_request_id',
        'patient_id',
        'request_number',
        'request_date',
        'report_number',
        'report_date',
        'patient_name',
        'patient_age',
        'patient_sex',
        'care_type',
        'bed_number',
        'registration_number',
        'clinical_history_number',
        'hospital_service_id',
        'service',
        'requesting_physician_id',
        'physician_name',
        'sample_type',
        'clinical_diagnosis',
        'clinical_data',
        'sample_taken_at',
        'sample_received_at',
        'sample_type_code',
        'sample_type_other',
        'description',
        'conclusion',
        'note',
        'status',
        'pathology_result_id',
        'sample_adequacy',
        'sample_adequacy_notes',
        'transformation_zone_present',
        'general_category',
        'microorganisms',
        'benign_findings',
        'epithelial_abnormality',
        'epithelial_abnormality_details',
        'hormonal_evaluation',
        'recommendations',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'request_date' => 'date',
            'patient_age' => 'integer',
            'transformation_zone_present' => 'boolean',
            'microorganisms' => 'array',
            'benign_findings' => 'array',
        ];
    }

    public function pathologyResult(): BelongsTo
    {
        return $this->belongsTo(PathologyResult::class);
    }

    public function pathologyRequest(): BelongsTo
    {
        return $this->belongsTo(PathologyRequest::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function hospitalService(): BelongsTo
    {
        return $this->belongsTo(HospitalService::class);
    }

    public function requestingPhysician(): BelongsTo
    {
        return $this->belongsTo(Physician::class, 'requesting_physician_id');
    }
}
