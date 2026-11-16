<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ResultDiagnosis extends Model
{
    protected $fillable = ['pathology_result_id', 'position', 'code', 'description'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function pathologyResult(): BelongsTo
    {
        return $this->belongsTo(PathologyResult::class);
    }
}
