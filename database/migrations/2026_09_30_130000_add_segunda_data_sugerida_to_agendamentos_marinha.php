<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * 2ª data sugerida (opcional): tentada quando a 1ª não comporta os dois agendamentos no período.
     * Os agendamentos existentes ficam sem ela e continuam com a regra de antes.
     */
    public function up(): void
    {
        Schema::table('agendamentos_marinha', function (Blueprint $table) {
            $table->date('segunda_data_sugerida')->nullable()->after('data_sugerida');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos_marinha', function (Blueprint $table) {
            $table->dropColumn('segunda_data_sugerida');
        });
    }
};
