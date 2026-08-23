<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Permissão de acesso à tela "Usuários do Sistema".
     *
     * Antes disso a tela era aberta a qualquer pessoa do painel, o que anulava
     * na prática a permissão do financeiro: quem não tinha acesso podia entrar
     * ali e se conceder o acesso.
     *
     * Quem recebe esta permissão cadastra usuários e troca senhas, mas conceder
     * permissões continua sendo só do administrador — os campos de permissão
     * ficam somente-leitura para os demais.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('pode_gerenciar_usuarios')->default(false);
        });

        DB::table('users')
            ->where('email', User::EMAIL_ADMINISTRADOR)
            ->update(['pode_gerenciar_usuarios' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pode_gerenciar_usuarios');
        });
    }
};
