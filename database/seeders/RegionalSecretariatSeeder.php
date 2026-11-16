<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RegionalSecretariat;

class RegionalSecretariatSeeder extends Seeder
{

    public function run(): void
    {

        $data = [

            [
                'name'=>'POTOSI'
            ],

            [
                'name'=>'NINGUNO'
            ]

        ];


        foreach($data as $item)
        {

            RegionalSecretariat::firstOrCreate(
                [
                    'name'=>$item['name']
                ],
                $item
            );

        }

    }

}