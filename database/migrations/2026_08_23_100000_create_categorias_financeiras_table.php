<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tipos de entrada e de despesa cadastrados pelo usuário.
     *
     * É o que permite responder "quais são os maiores gastos" no consolidado:
     * sem categoria os lançamentos seriam só texto livre e não agrupariam.
     */
    public function up(): void
    {
        Schema::create('categorias_financeiras', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            // 'entrada' ou 'saida' — a categoria já nasce amarrada ao tipo de
            // lançamento, evitando classificar uma receita como despesa.
            $table->string('tipo');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['nome', 'tipo']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_financeiras');
    }
};
