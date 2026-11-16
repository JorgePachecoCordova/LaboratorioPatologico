<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceptionBatch extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['received_date' => 'date', 'year' => 'integer', 'number' => 'integer'];
    }

    public function healthFacility(): BelongsTo { return $this->belongsTo(HealthFacility::class); }
    public function requests(): HasMany { return $this->hasMany(PathologyRequest::class); }
    public function getDisplayNumberAttribute(): string { return $this->number.'-'.$this->module.'/'.$this->year; }
}
