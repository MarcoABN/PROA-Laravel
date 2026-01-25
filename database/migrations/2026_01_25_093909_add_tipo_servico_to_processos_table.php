<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            // Adiciona o campo Tipo de Serviço após o título
            $table->string('tipo_servico')->nullable()->after('titulo');
        });
    }

    public function down(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->dropColumn('tipo_servico');
        });
    }
};