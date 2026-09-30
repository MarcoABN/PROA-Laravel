<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * O cliente do agendamento pode ser pessoa física (CPF, 11 dígitos) ou jurídica (CNPJ, 14 dígitos).
     */
    public function up(): void
    {
        Schema::table('agendamento_solicitacoes', function (Blueprint $table) {
            $table->renameColumn('cliente_cpf', 'cliente_documento');
        });

        Schema::table('agendamento_solicitacoes', function (Blueprint $table) {
            $table->string('cliente_documento', 14)->change();
        });
    }

    public function down(): void
    {
        Schema::table('agendamento_solicitacoes', function (Blueprint $table) {
            $table->string('cliente_documento', 11)->change();
        });

        Schema::table('agendamento_solicitacoes', function (Blueprint $table) {
            $table->renameColumn('cliente_documento', 'cliente_cpf');
        });
    }
};
