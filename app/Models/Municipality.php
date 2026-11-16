<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipality extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'regional_secretariat_id',
        'access_code',
        'name',
        'normalized_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function regionalSecretariat(): BelongsTo
    {
        return $this->belongsTo(RegionalSecretariat::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function healthFacilities(): HasMany
    {
        return $this->hasMany(HealthFacility::class);
    }

    public function pathologyRequests(): HasMany
    {
        return $this->hasMany(PathologyRequest::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
