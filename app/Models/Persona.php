<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    /**
	 * @var string
	 */
	protected $table = 'personas';

	/**
	 * @var \Illuminate\Database\Eloquent\Concerns\GuardsAttributes
	 */
	protected $fillable = [
		'ci', 'extension', 'nombres', 'apellidos', 'genero', 'direccion', 'telefono', 'celular', 'fecha_nacimiento', 'estado_civil', 'foto','resgister'
	];

    /**
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'fecha_nacimiento'
    ];
    //protected $dateFormat = 'Ymd h:i:s'; // o el formato que te sirva

    /**
     * @var array
     */
    protected $appends = [
        'full_name', 'avatar', 'hash'
    ];
    
    public function usuario() {
    	return $this->hasOne(App\Models\Login\users::class, 'persona_id');
    }
    

}