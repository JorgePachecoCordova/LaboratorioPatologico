<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sample extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_RECEIVED = 'received';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'pathology_request_id',
        'sample_number',
        'sample_type',
        'organ',
        'collection_date',
        'received_at',
        'delivered_by',
        'received_by',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sample_number' => 'integer',
            'collection_date' => 'date',
            'received_at' => 'datetime',
        ];
    }

    public function pathologyRequest(): BelongsTo
    {
        return $this->belongsTo(PathologyRequest::class);
    }

    public function pathologyResult(): HasOne
    {
        return $this->hasOne(PathologyResult::class);
    }
}
