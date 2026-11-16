<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'district_id',
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

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function sectors(): HasMany
    {
        return $this->hasMany(Sector::class);
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
