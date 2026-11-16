<?php

use App\Support\AnatomopathologyCapProtocol;
use App\Models\CapProtocolField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cap_protocol_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('field_key', 60)->nullable()->unique();
            $table->string('name', 180);
            $table->string('normalized_name', 180)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'name']);
        });
        foreach (AnatomopathologyCapProtocol::FIELDS as $key => $name) {
            DB::table('cap_protocol_fields')->insert([
                'field_key' => $key, 'name' => $name,
                'normalized_name' => CapProtocolField::normalize($name),
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cap_protocol_fields');
    }
};
