<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Token da extensão por usuário do PROA.
     *
     * Um navegador só atende vários procuradores: a extensão manda o CPF logado no SISAP e o PROA
     * carrega os agendamentos daquele procurador. O token deixa de valer se o usuário perder a
     * permissão de agendamento. Guardamos só o hash.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sisap_token_hash', 64)->nullable()->unique();
            $table->timestamp('sisap_token_gerado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['sisap_token_hash']);
            $table->dropColumn(['sisap_token_hash', 'sisap_token_gerado_em']);
        });
    }
};
