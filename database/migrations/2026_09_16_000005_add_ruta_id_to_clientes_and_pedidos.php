<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('ruta_id')->nullable()->after('empresa_id')->constrained('rutas')->nullOnDelete();
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreignId('ruta_id')->nullable()->after('cliente_id')->constrained('rutas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['ruta_id']);
            $table->dropColumn('ruta_id');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['ruta_id']);
            $table->dropColumn('ruta_id');
        });
    }
};
