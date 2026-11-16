<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Physician extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'access_code',
        'full_name',
        'professional_license',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function requestedPathologyRequests(): HasMany
    {
        return $this->hasMany(PathologyRequest::class, 'requesting_physician_id');
    }

    public function pathologyResults(): HasMany
    {
        return $this->hasMany(PathologyResult::class, 'pathologist_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
