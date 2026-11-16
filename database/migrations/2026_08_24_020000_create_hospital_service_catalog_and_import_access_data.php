<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_services', function (Blueprint $table) {
            $table->id();
            $table->string('access_code', 30)->nullable()->unique();
            $table->string('name', 150);
            $table->string('normalized_name', 150)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('cytology_reports', function (Blueprint $table) {
            $table->foreignId('hospital_service_id')->nullable()->after('patient_age')
                ->constrained('hospital_services')->nullOnDelete();
            $table->foreignId('requesting_physician_id')->nullable()->after('service')
                ->constrained('physicians')->nullOnDelete();
        });

        $now = now();
        $services = [
            ['1', 'GINECOLOGIA'], ['10', 'OBSTETRICIA'], ['11', 'TRAUMATOLOGIA'],
            ['12', 'NEUMOLOGIA'], ['13', 'MEDICINA INTERNA'], ['14', 'EMERGENCIA'],
            ['15', 'CIRUGIA'], ['17', 'PEDIATRIA'], ['18', 'NEONATOLOGIA'],
            ['19', 'U.T.I.'], ['2', 'UROLOGIA'], ['20', 'NEUROLOGIA'],
            ['21', 'NEUROCIRUGIA'], ['22', 'PSIQUIATRIA'], ['23', 'ENDOSCOPIA'],
            ['24', 'DERMATOLOGIA'], ['25', 'OFTALMOLOGIA'], ['26', 'ODONTOLOGIA'],
            ['27', 'OTORRINOLARINGOLOGIA'], ['3', 'PARTICULAR'], ['4', 'HEMODIALISIS'],
            ['41', 'MAXILOFACIAL'], ['42', 'INFECTOLOGIA'], ['5', 'ENDOCRINOLOGIA'],
            ['6', 'ONCOLOGIA'], ['7', 'GENETICA'], ['8', 'REUMATOLOGIA'], ['9', 'CARDIOLOGIA'],
        ];

        DB::table('hospital_services')->insert(array_map(
            fn (array $item): array => [
                'access_code' => $item[0], 'name' => $item[1],
                'normalized_name' => $item[1], 'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
            $services
        ));

        $physicians = [
            ['1','BETZABE ORTEGA'], ['10','DULFREDO PINTO'], ['11','FRANCISCO DELGADILLO'],
            ['12','HERNAN CAMARGO'], ['13','JOSE CALLE'], ['14','NINGUNO'],
            ['15','NORMA OLIVARES'], ['16','ROY HUMEREZ'], ['17','VICTOR FERNANDEZ'],
            ['18','VICTOR LEON'], ['19','FRANCISCO TIRADO'], ['2','FRANCISCO GARABITO'],
            ['20','HUMBERTO BRACAMONTE'], ['21','MAURO ARCE'], ['22','NELSON GUTIERREZ'],
            ['23','RICARDO QUISBERTH'], ['24','WALTER SALINAS'], ['25','GONZALO BURGOS'],
            ['26','HERNAN VENEGAS'], ['27','JULIO VARGAS'], ['28','ORLANDO MICHEL'],
            ['29','MARIA ELENA IPORRE'], ['3','ALFREDO CHOQUE'], ['30','CARLOS MENDIZABAL'],
            ['31','DIEGO CANAVIRI'], ['32','JOSE CARO'], ['33','MARIO VERA'],
            ['34','RAUL FLORES'], ['35','SERGIO GUTIERREZ'], ['37','HUGO SECKO'],
            ['38','ARTURO GOYTIA'], ['39','ALBERTO FLORES'], ['4','ANA JANCKO'],
            ['40','BETTY ARISMENDI'], ['41','CONSUELO MURILLO'], ['42','CINTHIA RUIZ'],
            ['43','CARLOS ARANDO'], ['44','GROVER CORTEZ'], ['45','JORGE BARRIGA'],
            ['46','JULIO AGREDA'], ['47','WENDDY CHOQUE'], ['49','WILMA QUIROZ'],
            ['5','RAUL VELASQUEZ'], ['50','GONZALO MACHICADO'], ['51','NOEMI ALIAGA'],
            ['52','WALTER OLIVAREZ'], ['53','JHONNY ESTRADA'], ['54','TERESA TIRADO'],
            ['56','FILEMON VIEZAGA'], ['57','ROGER HERRERA'], ['59','VLADIMIR PALMA'],
            ['6','IVES GUERRERO'], ['60','IVAN ZELAYA OPORTO'], ['61','DR. BELLIDO'],
            ['62','JAMIL BUSTAMANTE'], ['63','ALICIA CUEVAS'], ['64','LIZBETH SUNAGUA'],
            ['65','FABIO SOLANO'], ['66','MARLENE FUERTES'], ['67','FABIOLA ROMERO'],
            ['68','RAMIRO ARANDO'], ['69','EVELYN CONDORI'], ['7','WILSON CHOQUE'],
            ['70','NARDA CORONADO'],
        ];

        DB::table('physicians')->insert(array_map(
            fn (array $item): array => [
                'access_code' => $item[0], 'full_name' => $item[1],
                'professional_license' => null, 'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
            $physicians
        ));
    }

    public function down(): void
    {
        Schema::table('cytology_reports', function (Blueprint $table) {
            $table->dropForeign(['hospital_service_id']);
            $table->dropForeign(['requesting_physician_id']);
            $table->dropColumn(['hospital_service_id', 'requesting_physician_id']);
        });
        Schema::dropIfExists('hospital_services');
    }
};
