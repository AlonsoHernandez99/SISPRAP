<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConstanciaEntregada extends Model
{
    protected $table = 'constancias_entregadas';

    public function gestion_proy()
    {
        return $this->belongsTo(GestionProyecto::class, 'gestion_proyecto_id');
    }
    public function scopeYear($query,$year){
        return $query->where('fecha_registro',$year);
    }

}
