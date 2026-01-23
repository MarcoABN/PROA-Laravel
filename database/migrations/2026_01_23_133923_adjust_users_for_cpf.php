<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf')->unique()->nullable();
            $table->string('email')->nullable()->change(); // CPF será o login principal
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('simulado_resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->integer('acertos');
            $table->integer('total_questoes')->default(40);
            $table->decimal('porcentagem', 5, 2);
            $table->boolean('aprovado');
            $table->timestamps();
        });
    }
};
