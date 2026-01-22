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
    Schema::create('motores', function (Blueprint $table) {
        $table->id();
        // Relacionamento com a tabela 'embarcacoes'
        $table->foreignId('embarcacao_id')->constrained('embarcacoes')->cascadeOnDelete();
        
        $table->string('marca');
        $table->string('num_serie');
        $table->decimal('potencia', 10, 2)->nullable();
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motors');
    }
};
