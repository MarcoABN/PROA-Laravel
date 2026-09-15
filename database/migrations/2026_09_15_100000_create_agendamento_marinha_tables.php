<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Agendamento eletrônico na Marinha (SISAP).
     *
     * Solicitação = um serviço de um cliente (CPF + GRU + serviço). Cada GRU ocupa uma vaga.
     * Agendamento = o que o procurador marca no SISAP: até N vagas (varia por capitania),
     * limitado a X agendamentos por procurador, por capitania, por mês.
     */
    public function up(): void
    {
        Schema::table('capitanias', function (Blueprint $table) {
            // Código da OM no SISAP (ex.: CFGO = 136). Sem ele a extensão não sabe qual OM abrir.
            $table->unsignedInteger('sisap_nidom')->nullable();
            $table->unsignedTinyInteger('sisap_vagas_por_agendamento')->default(3);
            $table->unsignedTinyInteger('sisap_agendamentos_por_mes')->default(2);
        });

        Schema::table('prestadores', function (Blueprint $table) {
            // Guardamos só o hash: o token aparece uma única vez, na geração.
            $table->string('sisap_token_hash', 64)->nullable()->unique();
            $table->timestamp('sisap_token_gerado_em')->nullable();
        });

        Schema::create('sisap_servicos', function (Blueprint $table) {
            $table->id();
            $table->string('sigla');           // Como aparece nas listas: INSC EMB, RNV EMB...
            $table->string('descricao_sisap'); // Texto exato da opção no SISAP
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('agendamentos_marinha', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestador_id')->constrained('prestadores');
            $table->foreignId('capitania_id')->constrained('capitanias');
            $table->date('competencia'); // Sempre o dia 1º do mês do atendimento

            // Sem UNIQUE: um agendamento cancelado libera a cota e o próximo recebe a ordem seguinte.
            $table->unsignedTinyInteger('ordem');
            $table->string('status');

            $table->dateTime('data_hora')->nullable();
            $table->string('numero')->nullable();
            $table->string('chave')->nullable();
            $table->string('sisap_id')->nullable();
            $table->text('comprovante_link')->nullable();
            $table->text('erro')->nullable();

            $table->timestamps();

            $table->index(['capitania_id', 'competencia']);
            $table->index(['prestador_id', 'capitania_id', 'competencia']);
        });

        Schema::create('agendamento_solicitacoes', function (Blueprint $table) {
            $table->id();

            // Avulsa: basta CPF, GRU e serviço. Cliente e processo são vínculos opcionais.
            $table->string('cliente_cpf', 11);
            $table->string('cliente_nome')->nullable();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('processo_id')->nullable()->constrained('processos')->nullOnDelete();

            $table->string('gru', 20)->unique();
            $table->foreignId('sisap_servico_id')->constrained('sisap_servicos');
            $table->foreignId('capitania_id')->constrained('capitanias');
            $table->date('competencia');

            $table->foreignId('agendamento_marinha_id')->nullable()->constrained('agendamentos_marinha')->nullOnDelete();
            $table->string('status');
            $table->text('observacoes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['capitania_id', 'competencia', 'status']);
            $table->index('cliente_cpf');
        });

        $agora = now();
        DB::table('sisap_servicos')->insert(array_map(fn(array $s) => $s + [
            'ativo' => true, 'created_at' => $agora, 'updated_at' => $agora,
        ], [
            ['sigla' => 'INSC EMB', 'descricao_sisap' => 'TIE (TITULO DE INSCRICAO DE EMBARCACAO) - EMBARCACAO COM COMPRIMENTO IGUAL OU MENOR QUE 12 METROS - INSCRICAO'],
            ['sigla' => 'RNV EMB', 'descricao_sisap' => 'TIE (TITULO DE INSCRICAO DE EMBARCACAO) - RENOVAÇÃO'],
            ['sigla' => 'TRANSF EMB', 'descricao_sisap' => 'TIE - TRANSFERENCIA DE PROPRIEDADE DE EMBARCACAO - ESPORTE E RECREIO - INSCRITA NA CP/DL/AG'],
            ['sigla' => 'INSC MOTO', 'descricao_sisap' => 'TIE (TITULO DE INSCRICAO DE EMBARCACAO) - MOTO AQUATICA - INSCRICAO'],
            ['sigla' => 'RNV MOTO', 'descricao_sisap' => 'TIE (TITULO DE INSCRICAO DE EMBARCACAO) - MOTO AQUATICA - RENOVACAO'],
            ['sigla' => 'TRANSF MOTO', 'descricao_sisap' => 'TIE - TRANSFERENCIA DE PROPRIEDADE DE MOTO AQUATICA - INSCRITA NA CP/DL/AG'],
        ]));

        // Código da CFGO levantado no mapeamento do SISAP.
        DB::table('capitanias')->where('sigla', 'CFGO')->update(['sisap_nidom' => 136]);
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamento_solicitacoes');
        Schema::dropIfExists('agendamentos_marinha');
        Schema::dropIfExists('sisap_servicos');

        Schema::table('prestadores', function (Blueprint $table) {
            $table->dropUnique(['sisap_token_hash']);
            $table->dropColumn(['sisap_token_hash', 'sisap_token_gerado_em']);
        });

        Schema::table('capitanias', function (Blueprint $table) {
            $table->dropColumn(['sisap_nidom', 'sisap_vagas_por_agendamento', 'sisap_agendamentos_por_mes']);
        });
    }
};
