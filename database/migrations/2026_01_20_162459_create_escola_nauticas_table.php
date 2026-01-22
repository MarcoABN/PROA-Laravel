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
    Schema::create('escola_nauticas', function (Blueprint $table) {
        $table->id();
        $table->string('razao_social');
        $table->string('cnpj')->unique();
        
        // Relacionamentos com a tabela de Prestadores
        // instrutor_id e responsavel_id podem ser nulos (nullable)
        // Se o prestador for deletado, setamos como null (nullOnDelete)
        $table->foreignId('instrutor_id')->nullable()->constrained('prestadores')->nullOnDelete();
        $table->foreignId('responsavel_id')->nullable()->constrained('prestadores')->nullOnDelete();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escola_nauticas');
    }
};
