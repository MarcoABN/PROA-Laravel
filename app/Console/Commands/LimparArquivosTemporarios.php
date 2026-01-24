<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LimparArquivosTemporarios extends Command
{
    // O nome que você usará no terminal ou no Schedule
    protected $signature = 'limpar:temporarios';

    protected $description = 'Remove arquivos DOCX e PDF com mais de 24 horas nos diretórios temporários';

    public function handle()
    {
        // Lista dos diretórios que você informou
        $diretorios = [
            'anexos_pdf',
            'documentos_gerados',
            'temp'
        ];

        foreach ($diretorios as $dir) {
            // Verifica se o diretório existe para não dar erro
            if (!Storage::disk('public')->exists($dir)) {
                continue;
            }

            $files = Storage::disk('public')->allFiles($dir);
            $contagem = 0;

            foreach ($files as $file) {
                // Timestamp de 24 horas atrás
                $limite = Carbon::now()->subDay()->getTimestamp();
                
                if (Storage::disk('public')->lastModified($file) < $limite) {
                    Storage::disk('public')->delete($file);
                    $contagem++;
                }
            }

            $this->info("Diretório '{$dir}': {$contagem} arquivos removidos.");
        }
    }
}