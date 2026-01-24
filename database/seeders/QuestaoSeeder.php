<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestaoSeeder extends Seeder
{
    public function run(): void
    {
        // Se já tiver dados, não insere duplicado (opcional)
        // DB::table('questoes')->truncate(); 

        DB::table('questoes')->insert([
            [
                'categoria' => 'Arrais',
                'enunciado' => 'Qual o significado da boia encarnada?',
                'alternativa_a' => 'Águas Seguras',
                'alternativa_b' => 'Perigo Isolado',
                'alternativa_c' => 'Limita canal a bombordo',
                'alternativa_d' => 'Canal Preferencial',
                'resposta_correta' => 'c',
                'ativo' => true,
            ],
            [
                'categoria' => 'Arrais',
                'enunciado' => 'O que é RIPEAM?',
                'alternativa_a' => 'Regulamento Internacional para Evitar Abalroamentos no Mar',
                'alternativa_b' => 'Regra Interna de Portos',
                'alternativa_c' => 'Registro Internacional',
                'alternativa_d' => 'Nenhuma das anteriores',
                'resposta_correta' => 'a',
                'ativo' => true,
            ]
        ]);
    }
}