<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('oficios', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->foreignId('escola_nautica_id')->constrained('escola_nauticas');
            $table->foreignId('capitania_id')->constrained('capitanias');
            $table->foreignId('instrutor_id')->constrained('prestadores');

            // Numeração Lógica
            $table->integer('sequencial');
            $table->integer('ano');
            // REMOVIDO: $table->string('numero_oficio')->virtualAs... (Causava o erro no Postgres)

            // Campos Editáveis
            $table->string('assunto')->default('Comunicado de aulas práticas');
            $table->date('data_aula');
            $table->string('periodo_aula')->default('07:00 às 14:00');
            $table->string('local_aula')->nullable();
            $table->string('cidade_aula')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Garante unicidade (não pode ter dois ofícios 001/2026)
            $table->unique(['sequencial', 'ano']);
        });

        // Tabela Pivô (Mantém igual)
        Schema::create('cliente_oficio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oficio_id')->constrained('oficios')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->string('categoria')->default('ARA/MTA');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_oficio');
        Schema::dropIfExists('oficios');
    }
};
