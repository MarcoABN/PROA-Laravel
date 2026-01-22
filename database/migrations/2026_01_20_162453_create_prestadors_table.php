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
    Schema::create('prestadores', function (Blueprint $table) {
        $table->id();
        $table->string('nome');
        $table->string('cpfcnpj')->unique();
        $table->string('rg')->nullable();
        $table->string('org_emissor')->nullable();
        $table->date('dt_emissao')->nullable();
        $table->string('nacionalidade')->nullable();
        $table->string('estado_civil')->nullable();
        $table->string('profissao')->nullable();
        $table->string('telefone')->nullable();
        $table->string('celular')->nullable();
        $table->string('email')->nullable();
        
        // Endereço
        $table->string('cep')->nullable();
        $table->string('logradouro')->nullable();
        $table->string('numero')->nullable();
        $table->string('complemento')->nullable();
        $table->string('bairro')->nullable();
        $table->string('cidade')->nullable();
        $table->string('uf', 2)->nullable();
        
        $table->string('estabelecimento')->nullable();

        // CHA
        $table->string('cha_numero')->nullable();
        $table->string('cha_categoria')->nullable();
        $table->date('cha_dtemissao')->nullable();

        // Flags
        $table->boolean('is_instrutor')->default(false);
        $table->boolean('is_procurador')->default(false);
        $table->enum('tipo_procuracao', ['COMPLETO', 'REDUZIDO'])->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestadors');
    }
};
