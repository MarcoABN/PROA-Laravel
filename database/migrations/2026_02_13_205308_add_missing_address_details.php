<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Atualizando Capitanias (Adicionando endereço e comando)
        Schema::table('capitanias', function (Blueprint $table) {
            // Comando
            $table->string('capitao_nome')->nullable()->after('padrao');
            $table->string('capitao_patente')->nullable()->after('capitao_nome');
            
            // Endereço
            $table->string('cep', 9)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero')->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            // Obs: 'uf' já existe na criação da tabela
        });

        // 2. Atualizando Escolas Náuticas (Apenas o que falta)
        Schema::table('escola_nauticas', function (Blueprint $table) {
            $table->string('cep', 9)->nullable()->after('cnpj');
            $table->string('logradouro')->nullable()->after('cep');
            $table->string('numero')->nullable()->after('logradouro');
            $table->string('complemento')->nullable()->after('numero');
            $table->string('bairro')->nullable()->after('complemento');
            
            // Obs: cidade, uf, site, telefones já existem nas migrations anteriores
        });
    }

    public function down(): void
    {
        Schema::table('capitanias', function (Blueprint $table) {
            $table->dropColumn(['capitao_nome', 'capitao_patente', 'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade']);
        });

        Schema::table('escola_nauticas', function (Blueprint $table) {
            $table->dropColumn(['cep', 'logradouro', 'numero', 'complemento', 'bairro']);
        });
    }
};