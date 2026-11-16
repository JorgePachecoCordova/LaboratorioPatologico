@extends('layouts.app')

@section('content')

<div class="sol-modern-wrapper">


@if(session('success'))

<div class="alert alert-success">
    {{ session('success') }}
</div>

@endif



<h2>
    Registro de Solicitud / Muestra
</h2>



@if($registroModelo)


<div class="card">

<h3>Datos del paciente</h3>


<p>
<strong>Paciente:</strong>

{{ $registroModelo->paciente->nombres ?? '' }}

{{ $registroModelo->paciente->apellidos ?? '' }}

</p>


<p>
<strong>Establecimiento:</strong>

{{ $registroModelo->establecimiento->nombre ?? '' }}

</p>


</div>



@endif






<form method="POST" action="{{ route('solicitudes.store') }}">

@csrf



@if($registroModelo)

<input type="hidden"
name="registro_id"
value="{{ $registroModelo->id }}">


<input type="hidden"
name="paciente_id"
value="{{ $registroModelo->paciente_id }}">


<input type="hidden"
name="establecimiento_id"
value="{{ $registroModelo->establecimiento_id }}">


@endif





<h3>
Datos de solicitud
</h3>



<label>
Médico solicitante *
</label>


<select name="medico_solicitante_id"
required>


<option value="">
Seleccione médico
</option>


@foreach($medicos as $medico)


<option value="{{ $medico->id }}">

{{ $medico->nombre }}

</option>


@endforeach


</select>






<label>
Servicio
</label>

<input type="text"
name="servicio"
value="{{ old('servicio') }}">





<label>
Unidad solicitante
</label>

<input type="text"
name="unidad_solicitante"
value="{{ old('unidad_solicitante') }}">





<label>
Diagnóstico clínico
</label>


<textarea name="diagnostico_clinico">{{ old('diagnostico_clinico') }}</textarea>






<label>
Cantidad de muestras *
</label>


<input type="number"
name="cantidad_muestras"
value="1"
min="1"
required>






<label>
Fecha toma muestra *
</label>


<input type="date"
name="fecha_toma_muestra"
value="{{ date('Y-m-d') }}"
required>







<hr>



<h3>
Ubicación institucional
</h3>




<label>
Secretaría Regional *
</label>


<select

id="secretaria_regional"

name="secretaria_regional_id"

required>


<option value="">
Seleccione Secretaría
</option>



@foreach($secretarias as $secretaria)


<option value="{{ $secretaria->id }}">

{{ $secretaria->nombre }}

</option>


@endforeach



</select>







<label>
Municipio *
</label>


<select

id="municipio"

name="municipio_id"

required>


<option>
Seleccione Municipio
</option>


</select>







<label>
Distrito *
</label>


<select

id="distrito"

name="distrito_id"

required>


<option>
Seleccione Distrito
</option>


</select>







<label>
Área *
</label>


<select

id="area"

name="area_id"

required>


<option>
Seleccione Área
</option>


</select>







<label>
Sector *
</label>


<select

id="sector"

name="sector_id"

required>


<option>
Seleccione Sector
</option>


</select>







<br><br>


<button type="submit">

Guardar solicitud

</button>



</form>



</div>



@endsection






@push('scripts')


<script>


document.addEventListener(
'DOMContentLoaded',
()=>{


const secretaria =
document.getElementById(
'secretaria_regional'
);


const municipio =
document.getElementById(
'municipio'
);


const distrito =
document.getElementById(
'distrito'
);


const area =
document.getElementById(
'area'
);


const sector =
document.getElementById(
'sector'
);





function limpiar(select,texto)
{

select.innerHTML =
`
<option value="">
${texto}
</option>
`;

}





function cargar(url,select,texto)
{


fetch(url)

.then(r=>r.json())

.then(data=>{


limpiar(
select,
texto
);



data.forEach(item=>{


let option =
document.createElement('option');

option.value=item.id;


option.textContent =
item.nombre ??
item.descripcion;


select.appendChild(option);


});


});


}







secretaria.addEventListener(
'change',
function(){


limpiar(municipio,'Seleccione Municipio');

limpiar(distrito,'Seleccione Distrito');

limpiar(area,'Seleccione Área');

limpiar(sector,'Seleccione Sector');



if(this.value)
{


cargar(

`{{ url('/ubicacion/municipios') }}/${this.value}`,

municipio,

'Seleccione Municipio'

);


}


});








municipio.addEventListener(
'change',
function(){


limpiar(distrito,'Seleccione Distrito');

limpiar(area,'Seleccione Área');

limpiar(sector,'Seleccione Sector');



if(this.value)
{


cargar(

`{{ url('/ubicacion/distritos') }}/${this.value}`,

distrito,

'Seleccione Distrito'

);


}


});








distrito.addEventListener(
'change',
function(){


limpiar(area,'Seleccione Área');

limpiar(sector,'Seleccione Sector');



if(this.value)
{


cargar(

`{{ url('/ubicacion/areas') }}/${this.value}`,

area,

'Seleccione Área'

);


}


});








area.addEventListener(
'change',
function(){


limpiar(sector,'Seleccione Sector');



if(this.value)
{


cargar(

`{{ url('/ubicacion/sectores') }}/${this.value}`,

sector,

'Seleccione Sector'

);


}


});




});


</script>


@endpush