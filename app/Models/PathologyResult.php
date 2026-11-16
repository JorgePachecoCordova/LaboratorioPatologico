<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PathologyResult extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const READERS = [
        'ELENA' => '#79502e',
        'MARIELA' => '#185abd',
        'JAQUE' => '#8055ab',
        'JHONY' => '#bc347b',
        'JUSTA' => '#000000',
        'GABRIELA' => '#197343',
    ];
    public const STATUS_REVIEW = 'review';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_DELIVERED = 'delivered';

    protected $fillable = [
        'sample_id',
        'pathologist_id',
        'recorded_by',
        'validated_by',
        'result_date',
        'macroscopic_description',
        'microscopic_description',
        'diagnosis',
        'is_positive',
        'reader',
        'visit_number',
        'insurance_number',
        'rural_report_number',
        'notes',
        'status',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'result_date' => 'date',
            'validated_at' => 'datetime',
            'is_positive' => 'boolean',
        ];
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function pathologist(): BelongsTo
    {
        return $this->belongsTo(Physician::class, 'pathologist_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function cytologyReport(): HasOne
    {
        return $this->hasOne(CytologyReport::class);
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(ResultDiagnosis::class)->orderBy('position');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(ResultAudit::class)->orderByDesc('revision');
    }

    public function auditSnapshot(): array
    {
        return ['result' => $this->attributesToArray(), 'diagnoses' => $this->diagnoses()->get(['position', 'code', 'description'])->toArray(), 'cytology' => $this->cytologyReport()->first()?->attributesToArray()];
    }
}
