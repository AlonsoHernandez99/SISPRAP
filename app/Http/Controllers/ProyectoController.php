<?php

namespace App\Http\Controllers;

use App\Estudiante;
use App\GestionProyecto;
use App\Jobs\sendNotificationToAdmin;
use App\Notifications\NotifyAcceptProjectToStudent;
use App\Notifications\NotifyPreRegisterProject;
use App\Notifications\NotifyStudentGoToRecep;
use App\Proyecto;
use App\User;
use Carbon\Carbon;
use File;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class ProyectoController extends Controller
{
    public $anio;

    public function __construct()
    {
        $this->anio = config('app.app_year');
    }
     //listado de proyectos por proceso y busqueda
    public function index(Request $request)
    {

        $buscar = $request->buscar;
        $proceso = $request->proceso;

        $proyecto = Proyecto::with(['institucion', 'tipoProceso','carre_proy'])->whereHas('tipoProceso', function ($query) use ($proceso) {
            $query->where('proceso_id', $proceso);
        })->nombre($buscar)->year($this->anio)->where([['proyectos.estado', '1'],['proyectos.tipo_proyecto','I']])->orderBy('proyectos.id', 'desc')->paginate(10);

        return [
            'pagination' => [
                'total' => $proyecto->total(),
                'current_page' => $proyecto->currentPage(),
                'per_page' => $proyecto->perPage(),
                'last_page' => $proyecto->lastPage(),
                'from' => $proyecto->firstItem(),
                'to' => $proyecto->lastItem(),
            ],
            'proyecto' => $proyecto,
        ];
    }

    //Publicacion de proyectos verificando proceso y tipo de proyecto
    public function store(Request $request)
    {
        $date = date('Y-m-d');
        switch ($request->tipoProyecto) {
            case 'I':
            switch ($request->proceso_id) {
                case 1:
                $img_recv = $request->imagen;
                $proyecto = new Proyecto();
                $proyecto->nombre = $request->nombre;
                $proyecto->fecha = $date;
                $proyecto->actividades = $request->actividadSS;
                $proyecto->institucion_id = $request->institucion_id;
                $proyecto->horas_realizar = $request->horas;
                $proyecto->cantidades_vacantes = $request->cantidadAlumnos;
                $proyecto->tipo_proyecto = 'I';
                $proyecto->proceso_id = 1;
                $proyecto->fecha_registro = $this->anio;

                if ($img_recv) {
                    $name_img = Carbon::now()->format('Y-m-d') . 'SS' . uniqid() . '.' . explode('/', explode(':', substr($img_recv, 0, strpos($img_recv, ';')))[1])[1];
                    $proyecto->img = $name_img;
                    Image::make($request->imagen)->save(public_path('/images/img_projects/') . $name_img);
                } else {
                    $proyecto->img = $request->imageG;
                }
                $proyecto->estado = 1;
                $proyecto->save();
                break;
                case 2:
                try {
                    $actividades = json_decode($request->actividades);
                    foreach ($actividades as $key => $actividad) {
                        DB::beginTransaction();
                        $img_recv = $request->imagen;
                        $proyecto = new Proyecto();
                        $proyecto->nombre = $request->nombre;
                        $proyecto->fecha = $date;
                        $proyecto->horas_realizar = $request->horas;
                        $proyecto->cantidades_vacantes = $request->cantidadAlumnos;
                        $proyecto->actividades = $actividad->actividades;
                        $proyecto->institucion_id = $request->institucion_id;
                        $proyecto->proceso_id = 2;
                        $proyecto->fecha_registro =  $this->anio;

                        if ($img_recv) {
                            $name_img = Carbon::now()->format('Y-m-d') . 'PP' . uniqid() . '.' . explode('/', explode(':', substr($img_recv, 0, strpos($img_recv, ';')))[1])[1];
                            $proyecto->img = $name_img;
                            Image::make($request->imagen)->save(public_path('/images/img_projects/') . $name_img);
                        } else {
                            $proyecto->img = $request->imageG;
                        }
                        $proyecto->estado = 1;
                        $proyecto->save();
                        $proyecto->carre_proy()->attach($actividad->carrera_id);
                        DB::commit();

                    }

                } catch (Exception $e) {
                    DB::rollBack();
                }
                break;
            }
            break;
            case 'E':
            $proyecto = new Proyecto();
            $proyecto->nombre = $request->nombre;
            $proyecto->fecha = $date;
            $proyecto->actividades = $request->actividadSS;
            $proyecto->institucion_id = $request->institucion_id;
            $proyecto->horas_realizar = $request->horas;
            $proyecto->cantidades_vacantes = null;
            $proyecto->tipo_proyecto = 'E';
            $proyecto->proceso_id = $request->proceso_id;
            $proyecto->estado = 1;
            $proyecto->fecha_registro =  $this->anio;
            $proyecto->save();
            break;
        }
    }

    //actualizacion de proyecto
    public function update(Request $request)
    {
        $cantidad = $request->cantidadEstudiantes;
        $img_recv = $request->imagen;
        switch ($request->tipoProyecto) {
            case 'I':
            switch ($request->proceso_id) {
                case 1:
                $proyecto = Proyecto::find($request->proyecto_id);

                // Obteniendo datos que se requieren para saber el total de estudiantes que estan realizando o que estan preinscritos a este proyecto
                $dataToVancantesMethod = new Request();
                $dataToVancantesMethod->setMethod('POST');
                $dataToVancantesMethod->request->add(['process_id' => $proyecto->proceso_id]);
                $dataToVancantesMethod->request->add(['proyectoId' => $proyecto->id]);
                $dataToVancantesMethod->request->add(['carrera_id' => '']);
                $estudiantesInProy = $this->getNumeroPreinscripciones($dataToVancantesMethod);

                // Verficando si se ha bajado o subido la cantidad de vacantes para este proyecto
                if ($cantidad > $proyecto->cantidades_vacantes and $cantidad > $estudiantesInProy)
                 $proyecto->estado_vacantes = 'D';
                else if($cantidad <= $estudiantesInProy)
                 $proyecto->estado_vacantes = 'C';

                // Actualizando los demas campos del proyecto
                $proyecto->nombre = $request->nombre;
                $proyecto->actividades = $request->actividades;
                $proyecto->institucion_id = $request->institucion_id;
                $proyecto->proceso_id = $request->proceso_id;
                $proyecto->horas_realizar = $request->hrsRealizar;
                $proyecto->cantidades_vacantes = $request->cantidadEstudiantes;
                $proyecto->tipo_proyecto = 'I';
                $proyecto->estado = $request->estado;


                if ($img_recv) {
                    if($request->imagen != $proyecto->img){
                         if(file_exists(public_path('images/img_projects/').$proyecto->img))
                         {
                            unlink(public_path('images/img_projects/').$proyecto->img);
                         }
                    }
                    $name_img = Carbon::now()->format('Y-m-d') . 'SS' . uniqid() . '.' . explode('/', explode(':', substr($img_recv, 0, strpos($img_recv, ';')))[1])[1];
                    $proyecto->img = $name_img;
                    Image::make($request->imagen)->save(public_path('/images/img_projects/') . $name_img);
                } else {
                    $proyecto->img = $request->imagenG;
                }
                $proyecto->update();
                break;
                case 2:
                $proyecto = Proyecto::find($request->proyecto_id);

                // Obteniendo datos que se requieren para saber el total de estudiantes que estan realizando o que estan preinscritos a este proyecto
                $dataToVancantesMethod = new Request();
                $dataToVancantesMethod->setMethod('POST');
                $dataToVancantesMethod->request->add(['process_id' => $proyecto->proceso_id]);
                $dataToVancantesMethod->request->add(['proyectoId' => $proyecto->id]);
                //Obteniendo para que carrera es este proyecto
                $carreraToProy = $proyecto->carre_proy()->get();
                $dataToVancantesMethod->request->add(['carrera_id' => $carreraToProy[0]->id]);
                $estudiantesInProy = $this->getNumeroPreinscripciones($dataToVancantesMethod);

                // Verficando si se ha bajado o subido la cantidad de vacantes para este proyecto
                if ($cantidad > $proyecto->cantidades_vacantes and $cantidad > $estudiantesInProy)
                 $proyecto->estado_vacantes = 'D';
                else if($cantidad <= $estudiantesInProy)
                 $proyecto->estado_vacantes = 'C';

                // Actualizando los demas campos del proyecto
                $proyecto->nombre = $request->nombre;
                $proyecto->actividades = $request->actividades;
                $proyecto->institucion_id = $request->institucion_id;
                $proyecto->proceso_id = $request->proceso_id;
                $proyecto->horas_realizar = $request->hrsRealizar;
                $proyecto->cantidades_vacantes = $request->cantidadEstudiantes;
                $proyecto->tipo_proyecto = 'I';
                $proyecto->estado = $request->estado;

                if ($img_recv) {
                   if($request->imagen != $proyecto->img){
                         if(file_exists(public_path('images/img_projects/').$proyecto->img))
                         {
                            unlink(public_path('images/img_projects/').$proyecto->img);
                         }
                    }
                    $name_img = Carbon::now()->format('Y-m-d') . 'SS' . uniqid() . '.' . explode('/', explode(':', substr($img_recv, 0, strpos($img_recv, ';')))[1])[1];
                    $proyecto->img = $name_img;
                    Image::make($request->imagen)->save(public_path('/images/img_projects/') . $name_img);
                } else {
                    $proyecto->img = $request->imagenG;
                }
                $proyecto->update();
                break;
            }
            break;
            case 'E':
            $proyecto = Proyecto::findOrFail($request->proyecto_id);
            $proyecto->nombre = $request->nombre;
            $proyecto->actividades = $request->actividades;
            $proyecto->institucion_id = $request->institucion_id;
            $proyecto->horas_realizar = $request->horasRealizar;
            $proyecto->proceso_id = $request->procesoId;
            $proyecto->tipo_proyecto = 'E';
            $proyecto->update();
            break;
        }
    }

    //listado de proyectos por proceso y id
    public function GetProyectos($id)
    {
        $proceso = $id;
        $proyecto = Proyecto::select('id', 'nombre')->proceso($proceso)->year($this->anio)->where('estado',1)->orderBy('proyectos.id', 'desc')->get();
        $data = [];
        foreach ($proyecto as $key => $value) {
            $data[$key] = [
                'value' => $value->id,
                'label' => $value->nombre,
            ];
        }
        return response()->json($data);
    }

    //cambiar de estado para desactivar el proyecto
    public function desactivar(Request $request)
    {
        if (!$request->ajax())
         return redirect('/');
         $proyecto = Proyecto::findOrFail($request->proyecto_id);
         $proyecto->estado = '0';
         $proyecto->save();
    }

    //cambiar de estado para activar el proyecto
    public function activar(Request $request)
     {
        if (!$request->ajax())
            return redirect('/');
        $proyecto = Proyecto::findOrFail($request->proyecto_id);
        $proyecto->estado = '1';
        $proyecto->save();
    }

    //obtener proyectos desactivados INTERNOS
    public function getProyDes(Request $request)
    {
        $buscar = $request->buscar;
        $proceso = $request->proceso;

        $proyecto = Proyecto::whereHas('tipoProceso', function ($query) use ($proceso) {
            $query->where('proceso_id', $proceso);
        })->year($this->anio)->where([['proyectos.estado','0'],['proyectos.tipo_proyecto','I']])->nombre($buscar)->orderBy('proyectos.id', 'desc')->paginate(5);

        return [
            'pagination' => [
                'total' => $proyecto->total(),
                'current_page' => $proyecto->currentPage(),
                'per_page' => $proyecto->perPage(),
                'last_page' => $proyecto->lastPage(),
                'from' => $proyecto->firstItem(),
                'to' => $proyecto->lastItem(),
            ],
            'proyecto' => $proyecto,
        ];
    }

    // Obtener proyectos desactivados EXTERNOS
    public function getProyDesExternos(Request $request)
    {
        $buscar = $request->buscar;
        $proceso = $request->proceso;

        $proyecto = Proyecto::whereHas('tipoProceso', function ($query) use ($proceso) {
            $query->where('proceso_id', $proceso);
        })->year($this->anio)->where([['proyectos.estado','0'],['proyectos.tipo_proyecto','E']])->nombre($buscar)->orderBy('proyectos.id', 'desc')->paginate(5);

        return [
            'pagination' => [
                'total' => $proyecto->total(),
                'current_page' => $proyecto->currentPage(),
                'per_page' => $proyecto->perPage(),
                'last_page' => $proyecto->lastPage(),
                'from' => $proyecto->firstItem(),
                'to' => $proyecto->lastItem(),
            ],
            'proyectos' => $proyecto,
        ];
    }

    //Funcion que obtiene los proyectos deacuerdo ala carerra del alumno logeado y verifica si ya se preisncribio a ese proyecto no lo muestra
    public function getProjectsByCarrer(Request $request)
    {
        $carre_id = $request->estudent_carrer;
        $tp = $request->estudent_process;
        $pre_register = Auth::user()->estudiante->preinscripciones;
        $gestion = Auth::user()->estudiante->gestionProyecto()->where('tipo_gp',$tp)->select('proyecto_id')->get();
        $gestion = $gestion->pluck('proyecto_id');
        $buscar = $request->buscar;
        $date = date('Y');

        if ($tp == 1) {
            if(collect($pre_register)->isNotEmpty()){

                $proyectos = Proyecto::with(["tipoProceso", "institucion"])->whereHas('tipoProceso', function ($query) use ($tp) {
                    $query->where('proceso_id', $tp);
                })->year($date)->nombre($buscar)->orderby('id', 'desc')->where([['estado',1],['estado_vacantes','D'],['proyectos.tipo_proyecto','I']])->get();

                for ($i=0; $i < count($pre_register) ; $i++) {
                 $proyectos = $proyectos->except([$pre_register[$i]->id]);
                }
                if(count($gestion) > 0)
                {
                     for ($i=0; $i < count($gestion) ; $i++) {
                         $proyectos = $proyectos->except([$gestion[$i]]);
                     }
                }

            }else{

                $proyectos = Proyecto::with(["tipoProceso", "institucion"])->whereHas('tipoProceso', function ($query) use ($tp) {
                    $query->where('proceso_id', $tp);
                })->year($date)->nombre($buscar)->orderby('id', 'desc')->where([['estado',1],['estado_vacantes','D'],['proyectos.tipo_proyecto','I']])->get();

               if(count($gestion) > 0)
               {
                   for ($i=0; $i < count($gestion) ; $i++) {
                       $proyectos = $proyectos->except([$gestion[$i]]);
                   }
               }
            };
        }else if ($tp == 2) {

            if(collect($pre_register)->isNotEmpty()){
                $proyectos = Proyecto::with(["carre_proy", "tipoProceso", "institucion"])->whereHas('carre_proy', function ($query) use ($carre_id) {
                    $query->where('carrera_id', $carre_id);
                })->year($date)->nombre($buscar)->orderby('id', 'desc')->where([['estado',1],['estado_vacantes','D'],['proyectos.tipo_proyecto','I']])->get();

                for ($i=0; $i < count($pre_register) ; $i++) {
                    $proyectos = $proyectos->except([$pre_register[$i]->id]);
                }
                if(count($gestion) > 0)
                {
                   for ($i=0; $i < count($gestion) ; $i++) {
                       $proyectos = $proyectos->except([$gestion[$i]]);
                   }
                }

            }else{

                $proyectos = Proyecto::with(["carre_proy", "tipoProceso", "institucion"])->whereHas('carre_proy', function ($query) use ($carre_id) {
                    $query->where('carrera_id', $carre_id);
                })->year($date)->nombre($buscar)->orderby('id', 'desc')->where([['estado',1],['estado_vacantes','D'],['proyectos.tipo_proyecto','I']])->get();

                if(count($gestion) > 0)
                {
                     for ($i=0; $i < count($gestion) ; $i++) {
                         $proyectos = $proyectos->except([$gestion[$i]]);
                     }
                }

            }
        }

        $projects_ids = $proyectos->pluck('id');
        $result_paginate = Proyecto::whereIn('id', $projects_ids)->year($date)->orderby('id', 'desc')->paginate(12);
        return $result_paginate;
    }

    //listado de proyectos por proceso
    public function getProjectsByProcess(Request $request)
    {
        $process_id = $request->process_id;
        $carre_id = $request->carre_id;
        $tipoProyecto = $request->tipoProyecto;
        $data = [];
        $data[0] = [];

        switch ($tipoProyecto) {
            case 'I':
                if ($process_id == 1) {

                    $proyectos = Proyecto::with(["tipoProceso", "institucion"])->whereHas('tipoProceso', function ($query) use ($process_id) {
                     $query->where('proceso_id', $process_id);
                 })->select('id','nombre','cantidades_vacantes',DB::raw("(SELECT COUNT(id) FROM preinscripciones_proyectos WHERE proyecto_id = proyectos.id AND estado != 'R') AS solicitudes"))->year($this->anio)->orderby('id','desc')->where([['estado',1],['proyectos.tipo_proyecto','I']])->get();


                } else if ($process_id == 2){

                    $proyectos = Proyecto::with(["carre_proy", "tipoProceso", "institucion"])->whereHas('carre_proy', function ($query) use ($carre_id) {
                        $query->where('carrera_id', $carre_id);
                    })->select('id','nombre','cantidades_vacantes',DB::raw("(SELECT COUNT(id) FROM preinscripciones_proyectos WHERE proyecto_id = proyectos.id AND estado != 'R') AS solicitudes"))->year($this->anio)->orderby('id', 'desc')->where([['estado',1],['proyectos.tipo_proyecto','I']])->get();
                }
            break;
            case 'E':

                $proyectos = Proyecto::with(["tipoProceso", "institucion"])->whereHas('tipoProceso', function ($query) use ($process_id) {
                   $query->where('proceso_id', $process_id);
               })->orderby('id','desc')->year($this->anio)->where([['estado',1],['proyectos.tipo_proyecto','E']])->get();

            break;
        }

        foreach ($proyectos as $key => $value) {
            $data[$key+1] =[
                'value'   => $value->id,
                'label' => $value->nombre,
                'vacantes' => $value->cantidades_vacantes,
                'preinscripciones' => $value->solicitudes
            ];

        }
        return response()->json($data);
    }

    // Funcion para obtener el numero de preinscipciones de un proyecto
    public function getNumeroPreinscripciones(Request $request){
        $process_id = $request->process_id;
        $carre_id = $request->carrera_id;
        $proyectoId = $request->proyectoId;
        $totalVacantes = 0;

        if ($process_id == 1) {

            $proyectos = Proyecto::whereHas('tipoProceso', function ($query) use ($process_id) {
             $query->where('proceso_id', $process_id);
            })->select(DB::raw("(SELECT COUNT(id) FROM preinscripciones_proyectos WHERE proyecto_id = proyectos.id AND estado != 'R') AS solicitudesHechas"))->orderby('id','desc')->where([['estado',1],['proyectos.tipo_proyecto','I']])->find($proyectoId);

            $sql = "SELECT COUNT(id) AS procesoMarcha FROM gestion_proyectos WHERE proyecto_id = :idProy AND tipo_gp = :idProceso";
            $gestion = DB::select($sql, [ 'idProy' => $proyectoId,'idProceso' => $process_id ]);

            $totalVacantes = $proyectos->solicitudesHechas + $gestion[0]->procesoMarcha;

        } else if ($process_id == 2){

            $proyectos = Proyecto::whereHas('carre_proy', function ($query) use ($carre_id) {
                $query->where('carrera_proyectos.carrera_id', $carre_id);
            })->select(DB::raw("(SELECT COUNT(id) FROM preinscripciones_proyectos WHERE proyecto_id = proyectos.id AND estado != 'R') AS solicitudesHechas"))->orderby('id', 'desc')->where([['estado',1],['proyectos.tipo_proyecto','I']])->find($proyectoId);


            $sql = "SELECT COUNT(id) AS procesoMarcha FROM gestion_proyectos WHERE proyecto_id = :idProy AND tipo_gp = :idProceso";
            $gestion = DB::select($sql, [ 'idProy' => $proyectoId,'idProceso' => $process_id ]);

            $totalVacantes = $proyectos->solicitudesHechas + $gestion[0]->procesoMarcha;

        }
        return $totalVacantes;
    }

    //apartado de la publica, en la verificacion de la url solo se presenta el nombre del proyecto que el alumno ha seleccionado
    public function getProjectBySlug($process, $slug)
    {
        if ($process == 1) {
            $proyecto = Proyecto::with(["tipoProceso", "institucion.sectorInstitucion"])->where(
                [
                    ['proceso_id', $process],
                    ['slug',$slug],['estado',1],
                    ['proyectos.tipo_proyecto','I']
                ])->firstOrFail();
        } else if ($process == 2) {
            $proyecto = Proyecto::with(["carre_proy", "tipoProceso", "institucion.sectorInstitucion"])->where(
                [
                    ['proceso_id', $process],
                    ['slug',$slug],['estado',1],
                    ['proyectos.tipo_proyecto','I']
                ])->firstOrFail();
        }
        return view('public.viewProject', compact("proyecto"));
    }

    //apartado de la publica, preinscripcion de proyectos
    public function preRegistrationProject($estudent_id, $project_id)
    {
        $proyect = Proyecto::findOrFail($project_id);
        $proyect_name = $proyect->nombre;
        try {
            DB::beginTransaction();
            if (!$proyect->preRegistration()->attach($estudent_id,array('fecha_registro' => $this->anio))) {

                $count_pre = $proyect->preRegistration()->count();

                $arrayData = [
                    'cantidad' => $count_pre,
                    'msj' =>  "Nueva Preinscripcion al proyecto de: ".$proyect_name,
                    'fecha' => now()->toDateTimeString(),
                ];
                if($proyect->estado_vacantes == 'C'){
                    return response('completado', 200);
                }else{
                    broadcast(User::FindOrFail(0)->notify(new NotifyPreRegisterProject($arrayData)));
                    DB::commit();
                    return "true";
                }
            } else {return "false";}

        } catch (Exception $e) {
          DB::rollBack();
        }
        return $admin = User::Find(0);
    }

    //listado de estudiantes que se han preinscrito a un proyecto especificado
    public function getPreregistrationByProject(Request $request)
    {
        $proyect = Proyecto::year(date('Y'))->findOrFail($request->project_id);
        $fechaActual= date('Y-m-d');
        $buscar = $request->buscar;

        if($request->project_id != 0)
           if($buscar != "")
               $projects = $proyect->preRegistration()->where('estudiantes.nombre', 'like', '%' . $buscar . '%')->where('preinscripciones_proyectos.estado','P')->where('estudiantes.estado','1')->orderBy('created_at','desc')->paginate(5);
           else
               $projects = $proyect->preRegistration()->where('estudiantes.estado',1)->where('preinscripciones_proyectos.estado','P')->orderBy('created_at','desc')->paginate(5);

           return [
            'pagination' => [
                'total' => $projects->total(),
                'current_page' => $projects->currentPage(),
                'per_page' => $projects->perPage(),
                'last_page' => $projects->lastPage(),
                'from' => $projects->firstItem(),
                'to' => $projects->lastItem(),
            ],
            'projects' => $projects,
        ];
    }

    //Apartado publico, listado de preinscripciones por estudiante
    public function getPreregisterProjects($estudent_id,$process_id)
    {

        $cuenta = Estudiante::whereHas('proceso', function ($query) use ($process_id) {
            $query->where('proceso_id', $process_id);
        })->find($estudent_id)->preinscripciones()->count();

        if($cuenta != 0 or $cuenta != null){
             $proyectos = Estudiante::whereHas('proceso', function ($query) use ($process_id) {
                $query->where('proceso_id', $process_id);
             })->find($estudent_id)->preinscripciones()->paginate(5);
        }else{
         $proyectos = [];
        }
        return view('public.myProjects', compact("proyectos"));
    }

    //Apartado publico, eliminar preinscripcion por estudiante
    public function deletePreRegistration($estudent_id, $project_id)
    {
        $proyect = Proyecto::findOrFail($project_id);
        if ($proyect->preRegistration()->detach($estudent_id))
            return "true";
        else
            return "false";
    }

    //rechazar la solicitud del estudiante referente a la preinscripcion realizada
    public function rechazPreregistration($estudent_id,$project_id)
    {

        DB::table('preinscripciones_proyectos')->where('estudiante_id', $estudent_id)->
        where('proyecto_id',$project_id)->update(array('estado' => 'R'));
    }

    //rechazar todas las solicitudes a un proyecto
    public function deleteAllPreregistration($project_id)
    {
        DB::table('preinscripciones_proyectos')->where([
            ['proyecto_id', $project_id],
            ['estado','!=','A']
        ])->update(array('estado' => 'R'));
    }

    //aprobar la preinscripcion del estudiante
    public function aceptarPreregistration(Request $request)
    {

        $query =  DB::table('preinscripciones_proyectos')->where('estudiante_id', $request->estudent_id)->where('proyecto_id',$request->project_id)->update(array('estado' => 'A'));

        if($query){
            DB::table('preinscripciones_proyectos')->where([
                ['estudiante_id', $request->estudent_id],
                ['estado','P']
            ])->orWhere('estado','R')->delete();
        }

        $p = Proyecto::findOrFail($request->project_id);
        $arrayData = [
            'msj' =>  "Tu solictud al proyecto de ".$p->nombre." ha sido procesada, el siguiente paso es que apertures el expediente de tu proceso en recepción",
            'fecha' => now()->toDateTimeString(),
        ];
        User::FindOrFail($request->estudent_id)->notify(new NotifyStudentGoToRecep($arrayData));
    }

     //Asignacion de proyectos de tipo externo a uno o mas estudiantes del estudiante
    public function asignarProyectoExterno(Request $request)
    {

        $arrayEstudiante = explode(',',$request->estudent_id);

        for ($i=0; $i < count($arrayEstudiante); $i++) {

           $proyect = Proyecto::find($request->project_id);
           $proyect->preRegistration()->attach($arrayEstudiante[$i],array('estado' => 'A','fecha_registro' =>  $this->anio));

           $estudiante = Estudiante::findOrFail($arrayEstudiante[$i]);
           if ($estudiante->proceso[0]->pago_arancel == 0) {
              $arrayData = [
                'msj' =>  "Se te ha asignado un proyecto,el siguiente paso es que apertures el expediente de tu proceso en recepción",
                'fecha' => now()->toDateTimeString(),
               ];
               User::FindOrFail($estudiante->id)->notify(new NotifyStudentGoToRecep($arrayData));
            }
        }
    }

    //Dar acceso al llenado de perfil del proyecto al estudiante
    public function provideAccessToPerfil($estudent_id,$project_id)
    {

        DB::table('preinscripciones_proyectos')->where('estudiante_id', $estudent_id)->
        where('proyecto_id',$project_id)->update(array('estado' => 'F'));
        $p = Proyecto::findOrFail($project_id);
        $arrayData = [
            'msj' =>  "Tu solictud al proyecto de ".$p->nombre."<a class='btn btn-link' href='route{{showPerfilProy}}'> puedes completar el perfil del proyecto ahora</a>",
            'fecha' => now()->toDateTimeString(),
        ];

        User::FindOrFail($estudent_id)->notify(new NotifyPreRegisterProject($arrayData));
            //Enviar Notificacion Aqui
    }

    // Funcion para obtener los alumnos aprobados por proyecto
    public function getAllAcepted(Request $request)
    {
        $proyectoId = $request->proyectoId;
        $buscar = $request->buscar;
        $proyectos = Proyecto::year($this->anio)->find($proyectoId)->preRegistration()->whereHas('preinscripciones',function($query){
            $query->where('preinscripciones_proyectos.estado','A')->orWhere('preinscripciones_proyectos.estado','F');
        })->nombre($buscar)->paginate(10);

        return [
            'pagination' => [
                'total' => $proyectos->total(),
                'current_page' => $proyectos->currentPage(),
                'per_page' => $proyectos->perPage(),
                'last_page' => $proyectos->lastPage(),
                'from' => $proyectos->firstItem(),
                'to' => $proyectos->lastItem(),
            ],
            'proyectos' => $proyectos,
        ];
    }

    // Funcion para eliminar un proyecto aprobado
    public function deleteProyectoAprobado(Request $request)
    {
        $proyectoId = $request->proyectoId;
        $estudianteId = $request->estudianteId;
        $preinscripcion = Estudiante::find($estudianteId)->preinscripciones()->whereHas('preRegistration',function($query){
            $query->where('preinscripciones_proyectos.estado','A')->orWhere('preinscripciones_proyectos.estado','F');
        })->detach();
    }

    // Funcion que obtiene los proyectos Externos
    public function getExternalProjects(Request $request){
        $buscar = $request->buscar;
        $proceso = $request->proceso;
        $proyecto = Proyecto::with(['institucion', 'tipoProceso'])->whereHas('tipoProceso', function ($query) use ($proceso) {
            $query->where('proceso_id', $proceso);
        })->year($this->anio)->nombre($buscar)->where([['proyectos.estado', '1'],['proyectos.tipo_proyecto','E']])
        ->orderBy('proyectos.id', 'desc')->paginate(10);

        return [
            'pagination' => [
                'total' => $proyecto->total(),
                'current_page' => $proyecto->currentPage(),
                'per_page' => $proyecto->perPage(),
                'last_page' => $proyecto->lastPage(),
                'from' => $proyecto->firstItem(),
                'to' => $proyecto->lastItem(),
            ],
            'proyectos' => $proyecto,
        ];
    }
    /* Metodo que verifica la cantidad de vacantes de un proyecto */
    public function verificarEstadoVacantes($id)
    {
        $proyecto = Proyecto::select('estado_vacantes')->find($id);
        if($proyecto->estado_vacantes == 'C'){
            return response('completado', 200);
        }
    }
}
