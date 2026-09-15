<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Permissão de acesso ao Agendamento Marinha: telas de agendamento, Serviços do SISAP,
     * token da extensão dos procuradores e dados SISAP das capitanias.
     *
     * Mesmo modelo do financeiro: flag por usuário, concedida pelo administrador na tela de Usuários.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('pode_acessar_agendamento')->default(false);
        });

        DB::table('users')
            ->where('email', User::EMAIL_ADMINISTRADOR)
            ->update(['pode_acessar_agendamento' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pode_acessar_agendamento');
        });
    }
};
