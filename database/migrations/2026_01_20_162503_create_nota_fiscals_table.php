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
    Schema::create('nota_fiscals', function (Blueprint $table) {
        $table->id();
        // Relacionamento com a tabela 'embarcacoes'
        $table->foreignId('embarcacao_id')->constrained('embarcacoes')->cascadeOnDelete();
        
        $table->string('cnpj_vendedor')->nullable();
        $table->string('razao_social')->nullable();
        $table->date('dt_venda')->nullable();
        $table->string('local')->nullable();
        $table->string('numero_nota')->nullable();
        $table->string('pdf_path')->nullable(); // Caminho do arquivo

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nota_fiscals');
    }
};
