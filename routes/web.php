<?php
///////////////////////// ADMIN /////////////////////////////////

Route::group(['middleware' => 'roles'], function () {
    Route::get('/main', function () {
      return view('admin.index');
    })->name('main');
});

/* Broadcast::routes(['middleware' => ['auth:api']]); */

//INSTITUCION
Route::get('/institucion', 'InstitucionController@index')->name('listInstituciones');
Route::post('/institucion/registrar', 'InstitucionController@store')->name('registrarInstitucion');
Route::put('/institucion/actualizar/{id}', 'InstitucionController@update')->name('update');
Route::put('/institucion/desactivar/{id}', 'InstitucionController@desactivar')->name('desactivar');
Route::put('/institucion/activar/{id}', 'InstitucionController@activar')->name('activar');
Route::get('GetInstituciones/{id}', 'InstitucionController@GetInstituciones')->name('getInstitucionById');
Route::get('/institucion/desactivadas', 'InstitucionController@getInstiDes')->name('institucionesDesactivadas');
Route::get('getProyectosByInstitucion', 'InstitucionController@getProyectosByInstitucion')->name('proyectosByinstitucion');
Route::get('/institucion/validate', 'InstitucionController@validateInstitucion')->name('validateInstitucion');
Route::get('getInstitucionesByProcess', 'InstitucionController@getInstitucionesByProcess')->name('getInstitucionesByProcess');
//REPORTE INSTITUCION
Route::get('institucion/getHojaSupervision','InstitucionController@getHojaSupervision')->name('getHojaSupervision');
Route::get('institucion/reporteGen','InstitucionController@getReportInstituciones')->name('generalInstitucion');
Route::get('institucion/getSupervisiones','InstitucionController@getSupervisiones')->name('getReporteSupervisiones');
Route::get('institucion/regSupervision','InstitucionController@regSupervision')->name('regSupervision');

//SUPERVISORES
Route::get('institucion/supervisor/index', 'SupervisorController@index')->name('getSupervisores');
Route::post('institucion/supervisor/save', 'SupervisorController@store')->name('saveSupervisor');
Route::get('/institucion/supervisor/validate','SupervisorController@validateSupervisor')->name('validateSupervisor');
Route::put('/institucion/supervisor/eliminar/{id}', 'SupervisorController@delete')->name('deleteSupervisor');
Route::put('institucion/supervisor/update', 'SupervisorController@update')->name('updSupervisor');


//SECTOR INSTITUCION
Route::get('sector/selectSectores', 'SectorInstitucionController@selectSectores')->name('selectSectores');
Route::get('/sector', 'SectorInstitucionController@index')->name('sectoresList');
Route::put('/sector/actualizar/{id}', 'SectorInstitucionController@update')->name('actualizarSector');
Route::get('/sector/eliminar/{id}', 'SectorInstitucionController@delete')->name('eliminarSector');
Route::get('/sector/validate','SectorInstitucionController@validateSector')->name('validateSector');
Route::get('sector/selectSectores', 'SectorInstitucionController@selectSectores')->name('selectSectores');
Route::post('sector/registrar','SectorInstitucionController@store')->name('registrarSector');

//MUNICIPIO
Route::get('GetDepartamentos', 'MunicipioController@GetDepartamentos')->name('getDepartamentos');
Route::get('GetMunicipios/{id}', 'MunicipioController@GetMunicipios')->name('getMunicipios');

//CARRERA
Route::get('carreras/GetCarreras', 'CarreraController@GetCarreras')->name('GetCarreras');
Route::get('/carrera', 'CarreraController@index')->name('carreraList');
Route::put('/carrera/actualizar/{id}', 'CarreraController@update')->name('actualizarCarrera');
Route::get('/carrera/validate','CarreraController@validateCarrera')->name('validateCarrera');
Route::put('/carrera/desactivar/{id}', 'CarreraController@desactivar')->name('desactivarCarrera');
Route::put('/carrera/activar/{id}', 'CarreraController@activar')->name('activarCarrera');
Route::get('/getCarreraDes', 'CarreraController@GetCarreraDes')->name('carrerasDesactivadas');

//ADMINISTRATIVA PROYECTO
Route::get('/proyecto', 'ProyectoController@index')->name('getAllProyectosInternos');
Route::get('/proyectos', 'ProyectoController@getExternalProjects')->name('getAllProyectosExternos');
Route::post('proyecto/registrar', 'ProyectoController@store')->name('saveProyectosInternos');
Route::post('/proyecto/actualizar', 'ProyectoController@update')->name('updateProyectos');
Route::get('GetProyectos/{id}', 'ProyectoController@GetProyectos')->name('getProyectosById');
Route::put('/proyecto/desactivar', 'ProyectoController@desactivar')->name('desactivarProyectosInternos');
Route::get('/proyecto/desactivadas', 'ProyectoController@getProyDes')->name('getAllProyectosInternosDesactivados');
Route::get('/proyecto/desactivados/externos', 'ProyectoController@getProyDesExternos')->name('getAllProyectosExternosDesactivados');
Route::put('/proyecto/activar', 'ProyectoController@activar')->name('activarProyectosInternos');
Route::get('proyectos/externos/asignar', 'ProyectoController@asignarProyectoExterno')->name('asignarProyectoExterno');
Route::get('proyectos/getNumeroPreinscripciones', 'ProyectoController@getNumeroPreinscripciones')->name('getNumeroPreinscripciones');
Route::get('GetProjectsByProcess', 'ProyectoController@getProjectsByProcess')->name('getProyectosByProcess');
Route::get('getPreregistrationByProject','ProyectoController@getPreregistrationByProject')->name('getPreinscripcionesByProyecto');
Route::get('/proyectos/obtenerAprobados', 'ProyectoController@getAllAcepted')->name('allAcepted');
Route::get('/proyectos/deleteAprobacion', 'ProyectoController@deleteProyectoAprobado')->name('deleteProyAceptted');
Route::get('/proyecto/vacantes/{id}','ProyectoController@verificarEstadoVacantes')->name('verificarEstadoVacantes');

///////PUBLICA PROYECTO///////
Route::get('/viewProject/{process}/{slug}', 'ProyectoController@getProjectBySlug')->name('viewProject');
Route::get('/preRegister/{studentId}/{projectId}', 'ProyectoController@preRegistrationProject')->name('preRegister');
Route::get('/my-pre-register/{studentId}/{proceso_id}', 'ProyectoController@getPreregisterProjects')->name('myPreregister');
Route::get('/deletePreRegister/{studentId}/{projectId}', 'ProyectoController@deletePreRegistration')->name('deletePreRegister');
Route::get('/destroyPreregister/{sId}/{pId}','ProyectoController@rechazPreregistration')->name('rechazarPreinscripcion');
Route::get('acceptPreregister','ProyectoController@aceptarPreregistration')->name("preregister");
Route::post('/admin/provideAccessToPerfil/{sId}/{pId}','ProyectoController@provideAccessToPerfil')->name('darAccesoPerfil');
Route::get('deleteAllPreregister/{pId}','ProyectoController@deleteAllPreregistration')->name('deleteAllPreinscripciones');

//SUPERVISIONES
Route::post('proyecto/registrar/supervision', 'SupervisionController@store')->name('saveSupervision');

/* FALTAN SUPERVISIONES */
Route::get('/proyecto/allProjects', 'ProyectoController@getProjectsByCarrer')->name('getProjectsByEstudiante');
Route::put('/supervision/actualizar','SupervisionController@update')->name('updateSupervision');

Route::get('GetSupervision/{id}', 'SupervisionController@GetSupervision')->name('getSupervisionById');
Route::get('imgSuperv/{id}', 'SupervisionController@imgSuperv')->name('getImagenesBySupervision');
Route::get('proyectos/getNumeroPreinscripciones', 'ProyectoController@getNumeroPreinscripciones')->name('getNumeroPreinscripciones');
Route::get('getFullInfo', 'GestionProyectoController@getFullDataByGestion')->name('getFullDataByGestion');
Route::get('/supervision/deteleImg/{id}', 'SupervisionController@delete')->name('deleteImgSupervision');

//LOGIN
Route::get('/', 'Auth\LoginController@showLoginForm')->name("showLogin");
Route::post('/login', 'Auth\LoginController@login')->name('login');
Route::post('/logout', 'Auth\LoginController@logout')->name('logout');

//ESTUDIANTE
Route::get('stundentById/{id}','EstudianteController@getStudentById')->name('getFullInfoEstudiante');
Route::get('/estudiantes/otherOptions', 'EstudianteController@getEstudianteToOtherOpctions')->name('getEstudianteToOtherOpctions');
Route::get('stundentByCarrer','EstudianteController@getStudensByCarrerAndProcess')->name('getEstudiantesByCarrera');
Route::post('/estudiante/changeNivel','EstudianteController@changeNivel')->name('changeNivelToEstudiante');
Route::put('/estudiante/desactivar/{estudiante_id}','EstudianteController@desactivarEstudiante')->name('desactivarEstudiante');
Route::post('/estudiante/activar/{estudiante_id}','EstudianteController@activarEstudiante')->name('activarEstudiante');
Route::get('/estudiantes/otherOptions/desactivados', 'EstudianteController@getEstudianteToOtherOpctionsDesactivados')->name('getEstudianteToOtherOpctionsDesactivados');
///
Route::get('/recepcion/getAllStudents','EstudianteController@getStudentsToRecepcion')->name('getEstudiantesToRecepcion');
//RECEPCION ESTUDIANTE
Route::get('/admin/studentsHasPayArancel','EstudianteController@getStudentsHasPayArancel')->name('accessToPerfil');

//PARTE PUBLICA, PERFIL DEL PROYECTO
Route::get('/perfil_proy',function(){exec('php artisan view:clear');return view('public.perfilProject');})->name('show_perfil');
Route::get('/public', 'PublicController@index')->name('public');

//GESTION PROYECTO
Route::get('public/downloadDocs','GestionProyectoController@downloadDocs')->name('downloadDocs');
Route::get('getActualGestionProyectos', 'GestionProyectoController@getActualGestionProyectos')->name('getActualGestionProyectos');
Route::get('changeFechaInicio', 'GestionProyectoController@cambiarFechaInicio')->name('changeFechaInicio');
Route::get('/gestionproyectos','GestionProyectoController@index')->name('getGestionByCarrera');
Route::get('/gestionproyectos/constancias','GestionProyectoController@constancias')->name('getEstudiantesToConstacias');
Route::get('/getMoreInfoGP/{id}','GestionProyectoController@getInfoGpById')->name('getFullInfoGestion');
Route::get('/getCostancia','GestionProyectoController@generateConstancia')->name('getConstancia');
Route::get('/my_projects_now/{id}','GestionProyectoController@getGestionProyectoByStudent')->name('proyects_now');
Route::get('/gestionproyectos/delete','GestionProyectoController@deleteProyectoEnMarcha')->name('deleteGestionProyecto');
Route::get('/gestion/getMinDateInicio/{proyectoId}/{procesoId}', 'GestionProyectoController@getMinDateInicio')->name('getMinDateInicio');

//PUBLICA GESTION PROYECTO
Route::get('/my-proyect/saveDataOfPerfil','GestionProyectoController@initGestionProyecto')->name('save_perfil');
Route::get('/gestion_proy/{perfil}','GestionProyectoController@generatePerfil')->name('generate_perfil');
//REPORTE GESTION PROYECTO
Route::get('/gestionProy/reportes/initialprocess','GestionProyectoController@getInitialProcessReporte')->name('reporteIniProd');
Route::get('/gestionProy/reportes/pendienteInicio', 'GestionProyectoController@getPendientesIniProcessReporte')->name('reportePenIni');
Route::get('/gestionProy/reportes/pendienteFin', 'GestionProyectoController@getPendientesFinProcessReporte')->name('reportePenFin');
Route::get('/gestionProy/reportes/procesosCulminados', 'GestionProyectoController@getProcesosCulminadosReporte')->name('reporteProcesosCulminados');
//DOCUMENTO GESTION PROYECTO
Route::get('/closeProyect','GestionProyectoController@closeProy')->name('close_proyect');

//DOCUMENTOS
Route::get('/getDocuments','DocumentoController@getDocumentsByStudent')->name('getDocumentosByEstudiante');
Route::get('/saveDoc','DocumentoController@addDocToStudent')->name('savedoc');

//NOTIFICACIONES
Route::get('notifications/get', 'NotificationController@get')->name('getNotificationsAdmin');
Route::post('notifications/admin/setReadNotificacions', 'NotificationController@setReadNotificacion')->name('setReadNotificacions');

//////PARTE DE RECEPCION ////////
Route::post('/recepcion/payArancel','PagoArancelController@payArancel')->name('cancelarArancel');
Route::get('/recepcion/payArancel/validate/{no_factura}','PagoArancelController@validateIfExiste')->name('validateIfExisteArancel');

//Usuarios
Route::post('users/update','UsuarioController@update')->name('updateUsuario');
Route::get('users/changeYear/{year}','UsuarioController@changeYearApp')->name('changeYearApp');
Route::get('estudents/getCurrenthMonth', 'UsuarioController@getCurrentMonth')->name('getCurrentMonth');
Route::post('estudents/changeMonth', 'UsuarioController@changeCurrentMonth')->name('updateMonth');
Route::get('users/getAll','UsuarioController@index')->name('getUsers');
Route::put('users/delete/{id}','UsuarioController@delete')->name('deleteUser');
Route::post('users/create','UsuarioController@create')->name('createUser');


// RUTAS PARA CHAT
Route::get('/private-messages/{user}', 'MessageController@privateMessages')->name('privateMessages');
Route::post('/user/messages/store', 'MensajeController@sendPrivateMessage')->name('messages.store');
Route::get('/user/getMessages', 'MensajeController@getMessagesToStudent')->name('getMessagesStudent');
Route::get('/user/getMessages/users', 'MensajeController@getListOfMessagesAdmin')->name('getMessagesUsers');
Route::get('/user/getMessages/admin/{usuario_id}', 'MensajeController@getRecordsMessagesByUser')->name('getRecordsMessagesByUser');
Route::post('/user/messages/admin/setRead/{usuario_id}', 'MensajeController@setReadMessageAdmin')->name('setReadMessageAdmin');
Route::post('/user/messages/setRead', 'MensajeController@setReadMessageEstudent')->name('setReadMessageEstudent');
Route::get('/user/getMessages/count', 'MensajeController@getCountOfUnreadMessages')->name('getCountOfUnreadMessages');
Route::post('/user/messages/delete/{usuario_id}', 'MensajeController@deleteConversation')->name('deleteConversation');


