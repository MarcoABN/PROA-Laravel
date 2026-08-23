<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Trilha de auditoria da Gestão Financeira.
     *
     * Um log de auditoria só vale se sobreviver ao que ele audita: por isso não há
     * chave estrangeira para o registro nem cascata no usuário. Guardamos o id solto
     * mais um retrato em texto (quem fez, o que era o registro), de modo que apagar
     * um lançamento ou um usuário não apaga nem descaracteriza o histórico.
     */
    public function up(): void
    {
        Schema::create('financeiro_logs', function (Blueprint $table) {
            $table->id();

            // Registro auditado, sem FK de propósito (ver acima).
            $table->string('registro_tipo');            // classe do model
            $table->unsignedBigInteger('registro_id');
            $table->string('registro_descricao');       // retrato legível do registro

            $table->string('evento');                   // criado | atualizado | excluido

            // Autor. O id vira nulo se o usuário for removido, mas o nome permanece.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_nome');

            // Campo a campo: [{campo, rotulo, de, para}], já formatado para leitura.
            $table->json('alteracoes')->nullable();

            $table->string('ip')->nullable();

            $table->timestamps();

            $table->index(['registro_tipo', 'registro_id']);
            $table->index('created_at');
            $table->index('user_id');
            $table->index('evento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financeiro_logs');
    }
};
