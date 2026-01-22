<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Descobre o nome correto da tabela (prestadores ou prestadors)
        $tableName = Schema::hasTable('prestadores') ? 'prestadores' : 'prestadors';

        Schema::table($tableName, function (Blueprint $table) {
            
            // 2. Só adiciona 'cha_numero' se não existir
            if (!Schema::hasColumn($table->getTable(), 'cha_numero')) {
                $table->string('cha_numero')->nullable();
            }

            // 3. Só adiciona 'cha_categoria' se não existir
            if (!Schema::hasColumn($table->getTable(), 'cha_categoria')) {
                $table->string('cha_categoria')->nullable();
            }

            // 4. Só adiciona 'cha_dtemissao' se não existir
            if (!Schema::hasColumn($table->getTable(), 'cha_dtemissao')) {
                $table->date('cha_dtemissao')->nullable();
            }

            // 5. Só adiciona 'cha_validade' se não existir
            if (!Schema::hasColumn($table->getTable(), 'cha_validade')) {
                $table->date('cha_validade')->nullable();
            }
        });
    }

    public function down(): void
    {
        $tableName = Schema::hasTable('prestadores') ? 'prestadores' : 'prestadors';

        Schema::table($tableName, function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn($table->getTable(), 'cha_numero')) $columnsToDrop[] = 'cha_numero';
            if (Schema::hasColumn($table->getTable(), 'cha_categoria')) $columnsToDrop[] = 'cha_categoria';
            if (Schema::hasColumn($table->getTable(), 'cha_dtemissao')) $columnsToDrop[] = 'cha_dtemissao';
            if (Schema::hasColumn($table->getTable(), 'cha_validade')) $columnsToDrop[] = 'cha_validade';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};