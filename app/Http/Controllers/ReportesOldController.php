<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReportesOldController extends Controller
{
   
    public function index() //composer require "ifsnop/mysqldump-php"
    {
        /*
           $conn_access = odbc_connect("AccessDB", 'sistemas', 'sistemas') or die('Error al conectar a la base de datos...');
       $result = odbc_tables($conn_access);

       $tables = array();
       while (odbc_fetch_row($result)){
         if(odbc_result($result,"TABLE_TYPE")=="TABLE")
           echo"<br>".odbc_result($result,"TABLE_NAME");
    
       }
     */

  
       /*$conn_access = odbc_connect("AccessDB", 'sistemas', 'sistemas') or die('Error al conectar a la base de datos...');
       $query="SELECT * FROM AREAS";
       $test=odbc_exec($conn_access, $query);
       while ( $dnisMS=odbc_fetch_array($test) ){
        echo "<tr><td>$dnisMS[Codigo]</td>  <td>$dnisMS[Descripcion]</td><tr><br>";
       }*/
       /*
       $query="SELECT `DNI` FROM pedidos WHERE IdPedido='32453154'";
        $test=odbc_exec($conMSAccess, $query);
        while ( $dnisMS=odbc_fetch_array($test) ){   WHERE Descripcion = 'POTOSI'
            echo "<tr><td>$dnisMS[DNI]</td><tr>";
        }
     */
        //dd($conn_access);
       /* $conn = odbc_connect("AccessDB", "sistemas", "sistemas") or die('Error al conectar a la base de datos...');

       $tablelist = odbc_tables($conn);
       dd($tablelist);*/
     /*  $results = DB::connection('AccessDB')->table('Areas')->get();  // Usando la conexión de Access
       dd($results);*/

          
     
     //$results = DB::connection("AccessDB", 'sistemas', 'sistemas')->select('SELECT * FROM your_table');
    // dd($results);
       // dd(phpinfo());  
      // $mdbFilename = realpath('C:/Users/prueb/Documents/LPat2025.mdb'); 
       //$conn_access = odbc_connect("DRIVER={Microsoft Access Driver (*.mdb)};Dbq=$mdbFilename", 'sistemas', 'sistemas')or die ( "Connection failed: ");



        return view('reportesOld.index');
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
        /*
         class Cliente extends Model
        {
            protected $connection = 'odbc';  // Conexión secundaria (Access)
        }
         */
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
