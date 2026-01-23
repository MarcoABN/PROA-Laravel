<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <--- NÃO ESQUEÇA DE IMPORTAR ISSO

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. LIMPEZA DOS DADOS ANTIGOS (Solução do Erro)
        // Como estamos trocando de User para Cliente, os dados antigos perderiam a referência.
        // Limpar a tabela evita o erro de "Not Null" ao tentar criar a coluna em linhas existentes.
        DB::table('simulado_resultados')->truncate(); 

        Schema::table('simulado_resultados', function (Blueprint $table) {
            // 2. Remove a relação antiga
            // Verifica se a coluna existe antes de tentar dropar (por segurança)
            if (Schema::hasColumn('simulado_resultados', 'user_id')) {
                $table->dropForeign(['user_id']); 
                $table->dropColumn('user_id');
            }

            // 3. Cria a nova relação com a tabela clientes
            $table->foreignId('cliente_id')
                  ->after('id') 
                  ->constrained('clientes')
                  ->onDelete('cascade');

            // 4. Adiciona a coluna de erros
            $table->integer('erros')->after('acertos')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simulado_resultados', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropColumn(['cliente_id', 'erros']);

            // Recria a coluna antiga (nullable pois não teremos os dados de volta)
            $table->foreignId('user_id')->nullable()->constrained('users');
        });
    }
};