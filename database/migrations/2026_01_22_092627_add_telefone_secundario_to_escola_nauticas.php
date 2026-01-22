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
            $table->string('telefone_secundario')->nullable()->after('telefone_responsavel');
        });
    }

    public function down(): void
    {
        Schema::table('escola_nauticas', function (Blueprint $table) {
            $table->dropColumn('telefone_secundario');
        });
    }
};
