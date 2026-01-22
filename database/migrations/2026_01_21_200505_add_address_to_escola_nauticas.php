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
        Schema::table('escola_nauticas', function (Blueprint $table) {
            $table->string('cidade')->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('site')->nullable();
            $table->string('email_contato')->nullable();
            $table->string('telefone_responsavel')->nullable(); // Para o campo ${contato_proponente}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('escola_nauticas', function (Blueprint $table) {
            //
        });
    }
};
