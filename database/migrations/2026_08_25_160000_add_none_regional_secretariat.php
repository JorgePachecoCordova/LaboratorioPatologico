<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('regional_secretariats')) {
            return;
        }

        $existing = DB::table('regional_secretariats')
            ->where('normalized_name', 'NINGUNO')
            ->first();

        $values = [
            'access_code' => 'NINGUNO',
            'name' => 'NINGUNO',
            'normalized_name' => 'NINGUNO',
            'is_active' => true,
            'deleted_at' => null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('regional_secretariats')
                ->where('id', $existing->id)
                ->update($values);

            return;
        }

        DB::table('regional_secretariats')->insert($values + [
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Se conserva el valor para no invalidar solicitudes que ya lo utilicen.
    }
};
