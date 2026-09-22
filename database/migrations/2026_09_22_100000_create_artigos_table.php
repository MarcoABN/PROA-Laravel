<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Artigos do blog público (/blog/{slug}).
     */
    public function up(): void
    {
        Schema::create('artigos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->text('resumo');
            $table->longText('conteudo');
            $table->string('titulo_seo')->nullable();
            $table->string('meta_descricao', 170)->nullable();
            $table->json('faq')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamp('publicado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artigos');
    }
};
