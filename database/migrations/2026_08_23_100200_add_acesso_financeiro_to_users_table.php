<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Permissão de acesso ao menu Gestão Financeira.
     *
     * O sistema não tem papéis/roles, então o controle é uma flag por usuário,
     * marcada na tela de Usuários do Sistema.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('pode_acessar_financeiro')->default(false);
        });

        // Libera o administrador, senão ninguém consegue abrir a tela recém-criada
        // (inclusive para marcar a permissão dos demais).
        DB::table('users')
            ->where('email', 'marcoanunes23@gmail.com')
            ->update(['pode_acessar_financeiro' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pode_acessar_financeiro');
        });
    }
};
