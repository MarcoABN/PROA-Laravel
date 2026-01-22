<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Adiciona campos bancários na Escola Náutica
        Schema::table('escola_nauticas', function (Blueprint $table) {
            $table->string('banco')->nullable();
            $table->string('agencia')->nullable();
            $table->string('conta_corrente')->nullable();
            $table->string('chave_pix')->nullable();
        });

        // 2. Adiciona campo JSON para as parcelas na Proposta
        Schema::table('propostas', function (Blueprint $table) {
            $table->json('parcelas')->nullable(); // Guardará o array de parcelas
        });
    }

    public function down(): void
    {
        Schema::table('escola_nauticas', function (Blueprint $table) {
            $table->dropColumn(['banco', 'agencia', 'conta_corrente', 'chave_pix']);
        });
        Schema::table('propostas', function (Blueprint $table) {
            $table->dropColumn('parcelas');
        });
    }
};
