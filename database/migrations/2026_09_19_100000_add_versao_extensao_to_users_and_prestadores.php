<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Última versão da extensão do Chrome usada com cada token (de usuário ou de procurador).
     * Serve para o PROA avisar quem está com a extensão desatualizada.
     */
    public function up(): void
    {
        foreach (['users', 'prestadores'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->string('sisap_extensao_versao', 20)->nullable();
                $table->timestamp('sisap_extensao_vista_em')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['users', 'prestadores'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropColumn(['sisap_extensao_versao', 'sisap_extensao_vista_em']);
            });
        }
    }
};
