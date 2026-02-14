<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove o instrutor único da tabela de oficios (agora são múltiplos)
        Schema::table('oficios', function (Blueprint $table) {
            // Dropa a chave estrangeira e a coluna antiga
            $table->dropForeign(['instrutor_id']); 
            $table->dropColumn('instrutor_id');
            // MANTEMOS 'periodo_aula' AQUI, pois ele é global da turma
        });

        // 2. Cria a tabela para vincular múltiplos instrutores
        Schema::create('oficio_instrutores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oficio_id')->constrained('oficios')->cascadeOnDelete();
            $table->foreignId('prestador_id')->constrained('prestadores');
            $table->boolean('is_principal')->default(false); // Define quem assina
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oficio_instrutores');
        
        Schema::table('oficios', function (Blueprint $table) {
            $table->foreignId('instrutor_id')->nullable()->constrained('prestadores');
        });
    }
};