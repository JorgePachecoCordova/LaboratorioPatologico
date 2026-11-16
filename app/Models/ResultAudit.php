<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultAudit extends Model
{
    public $timestamps = false;
    public function result(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(PathologyResult::class, 'pathology_result_id')->withTrashed(); }
    protected $guarded = ['id'];
    protected function casts(): array { return ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime']; }
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('El historial no se puede modificar.'));
        static::deleting(fn () => throw new \LogicException('El historial no se puede eliminar.'));
    }
}
