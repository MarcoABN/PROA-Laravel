<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('clientes', function (Blueprint $table) {
        $table->id();
        $table->string('nome');
        $table->string('cpfcnpj')->unique(); // Esta é a coluna que está faltando!
        $table->string('rg')->nullable();;
        $table->string('org_emissor')->nullable();;
        $table->date('dt_emissao')->nullable();;
        $table->date('data_nasc')->nullable();;
        $table->string('nacionalidade')->nullable();;
        $table->string('naturalidade')->nullable();
        $table->string('telefone')->nullable();
        $table->string('celular')->nullable();
        $table->string('email')->nullable();
        
        // Endereço
        $table->string('cep');
        $table->string('logradouro');
        $table->string('numero')->nullable();;
        $table->string('complemento')->nullable();
        $table->string('bairro');
        $table->string('cidade');
        $table->string('uf', 2);

        // CHA
        $table->string('cha_numero')->nullable();
        $table->string('cha_categoria')->nullable();
        $table->date('cha_dtemissao')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
