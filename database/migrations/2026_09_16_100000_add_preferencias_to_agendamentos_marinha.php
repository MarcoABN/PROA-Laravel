<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Preferência de data e período do procurador para os agendamentos de uma capitania/mês.
     * Fica em cada agendamento do grupo; AlocaSolicitacao mantém os irmãos iguais.
     */
    public function up(): void
    {
        Schema::table('agendamentos_marinha', function (Blueprint $table) {
            $table->date('data_sugerida')->nullable();
            $table->string('periodo', 10)->nullable(); // manha | tarde | null (indiferente)
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos_marinha', function (Blueprint $table) {
            $table->dropColumn(['data_sugerida', 'periodo']);
        });
    }
};
