<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'identity_document',
        'full_name',
        'birth_date',
        'sex',
        'phone',
        'email',
        'address',
        'is_active',
    ];

    protected $appends = ['current_age'];

    public function ageAt(\DateTimeInterface|string|null $date = null): ?int
    {
        if (!$this->birth_date) return null;
        $reference = $date === null ? now() : \Illuminate\Support\Carbon::parse($date);
        $reference = $reference->copy()->startOfDay();
        $birth = $this->birth_date->copy()->startOfDay();
        return $birth->gt($reference) ? null : (int) $birth->diffInYears($reference);
    }

    public function getCurrentAgeAttribute(): ?int { return $this->ageAt(); }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function pathologyRequests(): HasMany
    {
        return $this->hasMany(PathologyRequest::class);
    }

    public function cytologyReports(): HasMany
    {
        return $this->hasMany(CytologyReport::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
