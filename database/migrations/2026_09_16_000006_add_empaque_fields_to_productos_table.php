<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('tiene_empaque')->default(false)->after('unidad');
            $table->string('nombre_empaque')->nullable()->after('tiene_empaque');
            $table->integer('cant_por_empaque')->nullable()->after('nombre_empaque');
            $table->decimal('precio_venta_empaque', 12, 2)->nullable()->after('cant_por_empaque');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['tiene_empaque', 'nombre_empaque', 'cant_por_empaque', 'precio_venta_empaque']);
        });
    }
};
