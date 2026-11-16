<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestSequence extends Model
{
    use HasFactory;

    public const URBAN = 'U';
    public const RURAL = 'R';
    public const GYNECOLOGICAL = 'G';

    protected $fillable = [
        'sequence_type',
        'sequence_year',
        'last_number',
    ];

    protected function casts(): array
    {
        return [
            'sequence_year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
