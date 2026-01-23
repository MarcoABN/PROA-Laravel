<?php

namespace Database\Seeders;

use App\Models\Questao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportarSimuladoSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa a tabela para evitar duplicados e lixo de execuções anteriores
        DB::table('questoes')->truncate();

        $caminho = storage_path('app/simulado_completo.csv');
        $arquivo = fopen($caminho, 'r');
        
        $primeiraLinha = true;
        $letras = ['a', 'b', 'c', 'd', 'e'];

        while (($linha = fgetcsv($arquivo, 0, ";")) !== FALSE) {
            if ($primeiraLinha) {
                $primeiraLinha = false;
                continue;
            }

            // Mapeamento: 
            // $linha[3] = a, [4] = b, [5] = c, [6] = d, [7] = e
            // $linha[8] = texto da resposta correta
            
            $textoRespostaCorreta = trim($linha[8]);
            $letraIdentificada = 'a'; // Valor padrão caso não encontre

            foreach ($letras as $index => $letra) {
                // Compara o texto da alternativa atual com o texto da resposta correta
                // Usamos trim() para ignorar espaços em branco acidentais
                if (trim($linha[3 + $index]) === $textoRespostaCorreta) {
                    $letraIdentificada = $letra;
                    break;
                }
            }

            Questao::create([
                'categoria'        => 'Arrais Amador',
                'assunto'          => 'Geral',
                'enunciado'        => $linha[2],
                'alternativa_a'    => $linha[3],
                'alternativa_b'    => $linha[4],
                'alternativa_c'    => $linha[5],
                'alternativa_d'    => $linha[6],
                'alternativa_e'    => $linha[7],
                'resposta_correta' => $letraIdentificada, // Agora salva apenas 'a', 'b', etc.
                'ativo'            => true,
            ]);
        }
        fclose($arquivo);
    }
}