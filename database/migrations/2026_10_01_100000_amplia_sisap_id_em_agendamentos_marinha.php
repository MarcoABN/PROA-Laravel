<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * O cidagendamento do SISAP é um token criptografado longo: não cabe em varchar(255) com folga.
     */
    public function up(): void
    {
        Schema::table('agendamentos_marinha', function (Blueprint $table) {
            $table->text('sisap_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos_marinha', function (Blueprint $table) {
            $table->string('sisap_id')->nullable()->change();
        });
    }
};
