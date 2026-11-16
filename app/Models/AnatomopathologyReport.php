<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnatomopathologyReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'report_number', 'registration_number', 'report_date', 'patient_name', 'patient_age',
        'hospital_service_id', 'service', 'requesting_physician_id', 'physician_name',
        'clinical_diagnosis', 'anatomical_data', 'important_data', 'macroscopic_description',
        'microscopic_description', 'diagnosis', 'notes', 'status', 'cap_protocol_enabled', 'cap_protocol',
    ];

    protected function casts(): array
    {
        return ['report_date' => 'date', 'patient_age' => 'integer', 'cap_protocol_enabled' => 'boolean', 'cap_protocol' => 'array'];
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
