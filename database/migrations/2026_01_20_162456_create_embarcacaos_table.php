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
    Schema::create('embarcacoes', function (Blueprint $table) {
        $table->id();
        // Relacionamento com Cliente
        $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
        
        $table->string('nome_embarcacao')->nullable();
        $table->string('num_casco')->nullable();
        $table->string('num_inscricao')->nullable();
        $table->string('tipo_embarcacao'); 
        $table->string('tipo_atividade');
        $table->string('area_navegacao');
        $table->date('dt_construcao')->nullable();
        $table->date('dt_inscricao')->nullable();
        
        // Características
        $table->decimal('cap_armazenamento', 10, 2)->nullable();
        $table->string('mat_casco')->nullable();
        $table->integer('qtd_tripulantes')->nullable();
        $table->integer('lotacao')->nullable();
        $table->string('tipo_propulsao')->nullable();
        $table->integer('qtd_motores')->default(0);
        $table->decimal('potencia_motor', 10, 2)->nullable();
        $table->string('mat_superestrutura')->nullable();
        $table->string('construtor')->nullable();
        $table->string('cor_predominante')->nullable();

        // Medidas
        $table->decimal('comp_total', 10, 2)->nullable();
        $table->decimal('comp_perpendicular', 10, 2)->nullable();
        $table->decimal('arqueacao_bruta', 10, 2)->nullable();
        $table->decimal('arqueacao_liquida', 10, 2)->nullable();
        $table->decimal('boca_moldada', 10, 2)->nullable();
        $table->decimal('contorno', 10, 2)->nullable();
        $table->decimal('porte_bruto', 10, 2)->nullable();
        $table->decimal('calado', 10, 2)->nullable();
        $table->decimal('pontal_moldado', 10, 2)->nullable();
        $table->decimal('borda_livre', 10, 2)->nullable();

        // Endereço da embarcação
        $table->string('cep')->nullable();
        $table->string('logradouro')->nullable();
        $table->string('numero')->nullable();
        $table->string('complemento')->nullable();
        $table->string('bairro')->nullable();
        $table->string('cidade')->nullable();
        $table->string('uf', 2)->nullable();

        $table->decimal('valor', 15, 2)->nullable();
        $table->date('dt_seguro')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('embarcacaos');
    }
};
