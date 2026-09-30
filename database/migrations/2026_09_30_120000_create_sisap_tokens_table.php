<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Vários tokens da extensão por usuário/procurador: um por navegador (perfil do Chrome), cada um
     * com nome e revogável sozinho. Os tokens atuais vêm para cá e continuam funcionando.
     */
    public function up(): void
    {
        Schema::create('sisap_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('dono');
            $table->string('nome', 100);
            $table->string('hash', 64)->unique();
            $table->string('extensao_versao', 20)->nullable();
            $table->timestamp('usado_em')->nullable();
            $table->timestamps();
        });

        foreach (['users' => 'App\\Models\\User', 'prestadores' => 'App\\Models\\Prestador'] as $tabela => $classe) {
            DB::table($tabela)->whereNotNull('sisap_token_hash')->orderBy('id')->each(function ($dono) use ($classe) {
                DB::table('sisap_tokens')->insert([
                    'dono_type' => $classe,
                    'dono_id' => $dono->id,
                    'nome' => 'Token anterior',
                    'hash' => $dono->sisap_token_hash,
                    'extensao_versao' => $dono->sisap_extensao_versao,
                    'usado_em' => $dono->sisap_extensao_vista_em,
                    'created_at' => $dono->sisap_token_gerado_em ?? now(),
                    'updated_at' => now(),
                ]);
            });

            Schema::table($tabela, function (Blueprint $table) {
                $table->dropUnique(['sisap_token_hash']);
                $table->dropColumn(['sisap_token_hash', 'sisap_token_gerado_em']);
            });
        }
    }

    public function down(): void
    {
        foreach (['users' => 'App\\Models\\User', 'prestadores' => 'App\\Models\\Prestador'] as $tabela => $classe) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->string('sisap_token_hash', 64)->nullable()->unique();
                $table->timestamp('sisap_token_gerado_em')->nullable();
            });

            // Só cabe um por dono na estrutura antiga: fica o mais recente.
            DB::table('sisap_tokens')->where('dono_type', $classe)->orderBy('id')->each(function ($token) use ($tabela) {
                DB::table($tabela)->where('id', $token->dono_id)->update([
                    'sisap_token_hash' => $token->hash,
                    'sisap_token_gerado_em' => $token->created_at,
                ]);
            });
        }

        Schema::drop('sisap_tokens');
    }
};
