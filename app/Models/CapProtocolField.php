<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CapProtocolField extends Model
{
    protected $fillable = ['field_key', 'name', 'normalized_name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function normalize(string $name): string
    {
        return preg_replace('/\s+/u', ' ', mb_strtoupper(Str::ascii(trim($name)))) ?? '';
    }
}
