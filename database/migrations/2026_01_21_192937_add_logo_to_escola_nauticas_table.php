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
            // Adiciona a coluna para salvar o caminho da imagem
            $table->string('logo_path')->nullable()->after('cnpj');
        });
    }

    public function down(): void
    {
        Schema::table('escola_nauticas', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
