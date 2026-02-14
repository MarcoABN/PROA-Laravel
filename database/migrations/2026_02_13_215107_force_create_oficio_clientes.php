<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verifica se a tabela NÃO existe antes de criar
        if (!Schema::hasTable('oficio_clientes')) {
            Schema::create('oficio_clientes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('oficio_id')->constrained('oficios')->cascadeOnDelete();
                $table->foreignId('cliente_id')->constrained('clientes');
                $table->string('categoria')->default('ARA/MTA');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('oficio_clientes');
    }
};