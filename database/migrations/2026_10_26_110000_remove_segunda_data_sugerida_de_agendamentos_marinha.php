<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A 2ª data sugerida saiu do cadastro: quando a data sugerida não comporta, o PROA procura a data
     * mais próxima com horário no período escolhido.
     */
    public function up(): void
    {
        Schema::table('agendamentos_marinha', function (Blueprint $table) {
            $table->dropColumn('segunda_data_sugerida');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos_marinha', function (Blueprint $table) {
            $table->date('segunda_data_sugerida')->nullable()->after('data_sugerida');
        });
    }
};
