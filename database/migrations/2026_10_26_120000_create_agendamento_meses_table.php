<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Opções por mês de atendimento. "Exibir na extensão" libera na extensão os agendamentos de um mês
     * que não aparecem sozinhos (o do mês seguinte, o principal, aparece sempre).
     */
    public function up(): void
    {
        Schema::create('agendamento_meses', function (Blueprint $table) {
            $table->id();
            $table->date('competencia')->unique();
            $table->boolean('exibir_na_extensao')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamento_meses');
    }
};
