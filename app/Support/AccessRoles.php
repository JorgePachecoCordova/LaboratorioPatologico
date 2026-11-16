<?php

namespace App\Support;

use App\Models\User;

final class AccessRoles
{
    public const CONSULT_LABORATORY = 'consult laboratory reports';
    public const LABELS = [
        'administrator' => 'Administrador',
        'reception' => 'Recepción / Solicitudes y consulta de resultados',
        'result_reader' => 'Lecturante / Llenado de resultados',
        'cytology_office' => 'Informes citológicos',
        'anatomopathology_office' => 'Anatomopatológico',
    ];

    public static function admin(?User $user): bool { return $user?->role === 'administrator'; }
    public static function reception(?User $user): bool { return in_array($user?->role, ['reception', 'laboratory_office'], true); }
    public static function addsRequestCatalogs(?User $user): bool { return $user?->is_active && (self::admin($user) || self::reception($user)); }
    public static function writesResults(?User $user): bool { return self::admin($user) || self::reception($user) || in_array($user?->role, ['result_reader', 'pathologist'], true); }
    public static function consultsLaboratory(?User $user): bool { return $user && !in_array($user->role, ['result_reader', 'pathologist'], true) && $user->permissions()->where('name', self::CONSULT_LABORATORY)->exists(); }
    public static function readsResults(?User $user): bool { return self::writesResults($user) || in_array($user?->role, ['cytology_office', 'anatomopathology_office'], true) || self::consultsLaboratory($user); }
    public static function label(?User $user): string { return self::LABELS[$user?->role] ?? match ($user?->role) { 'laboratory_office' => self::LABELS['reception'], 'pathologist' => self::LABELS['result_reader'], default => 'Sin acceso asignado' }; }

    public static function allows(?User $user, string $route): bool
    {
        if (!$user || !$user->is_active) return false;
        if (self::admin($user)) return true;
        if (in_array($route, ['inicio', 'logout', 'perfil.edit', 'perfil.update', 'perfil.password'], true)) return true;
        if (in_array($route, ['resultados.create', 'resultados.store', 'resultados.diagnosticos.store'], true)) return self::writesResults($user);
        if (str_starts_with($route, 'resultados.')) return in_array($route, ['resultados.index', 'resultados.show', 'resultados.imprimir', 'resultados.excel', 'resultados.excel.consolidado', 'resultados.positivos.mensual'], true) && self::readsResults($user);
        if (str_starts_with($route, 'citologia.')) return $user->role === 'cytology_office';
        if (str_starts_with($route, 'anatomopatologia.')) return $user->role === 'anatomopathology_office';
        if ($user->role === 'cytology_office' && str_starts_with($route, 'solicitudes.')) return false;
        if (str_starts_with($route, 'solicitudes.')) return (self::reception($user) && !in_array($route, ['solicitudes.destroy'], true)) || (in_array($route, ['solicitudes.index', 'solicitudes.show', 'solicitudes.registro-diario'], true) && self::consultsLaboratory($user));
        if (str_starts_with($route, 'muestras.') || str_starts_with($route, 'registros.') || str_starts_with($route, 'ubicacion.') || str_starts_with($route, 'pacientes.')) return self::reception($user);
        return false;
    }
}
