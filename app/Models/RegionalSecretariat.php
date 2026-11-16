<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegionalSecretariat extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
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

    public function municipalities(): HasMany
    {
        return $this->hasMany(Municipality::class);
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
