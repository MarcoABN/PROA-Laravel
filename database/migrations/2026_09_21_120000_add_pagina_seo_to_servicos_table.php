<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Campos da página pública de cada serviço (/servicos/{slug}).
     */
    public function up(): void
    {
        Schema::table('servicos', function (Blueprint $table) {
            $table->string('titulo_seo')->nullable()->after('descricao');
            $table->string('meta_descricao', 170)->nullable()->after('titulo_seo');
            $table->longText('conteudo')->nullable()->after('meta_descricao');
            $table->json('faq')->nullable()->after('conteudo');
        });
    }

    public function down(): void
    {
        Schema::table('servicos', function (Blueprint $table) {
            $table->dropColumn(['titulo_seo', 'meta_descricao', 'conteudo', 'faq']);
        });
    }
};
