<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prestador extends Model
{
    use HasFactory;

    // AVISA O LARAVEL QUE O NOME DA TABELA É EM PORTUGUÊS
    protected $table = 'prestadores'; 

    protected $guarded = [];
}