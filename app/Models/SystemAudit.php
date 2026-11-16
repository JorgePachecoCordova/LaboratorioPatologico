<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class SystemAudit extends Model {
    public $timestamps = false;
    protected $guarded = ['id'];
    protected function casts(): array { return ['before'=>'array','after'=>'array','metadata'=>'array','created_at'=>'datetime']; }
    protected static function booted(): void {
        static::updating(fn()=>throw new \LogicException('El historial no se modifica.'));
        static::deleting(fn()=>throw new \LogicException('El historial no se elimina.'));
    }
}
