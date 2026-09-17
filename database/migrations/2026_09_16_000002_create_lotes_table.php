<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('codigo_lote');
            $table->date('fecha_vencimiento');
            $table->integer('stock_inicial')->default(0);
            $table->integer('stock_actual')->default(0);
            $table->string('estado', 20)->default('VIGENTE'); // VIGENTE, POR_VENCER, VENCIDO, AGOTADO
            $table->timestamps();
        });

        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'es_perecedero')) {
                $table->boolean('es_perecedero')->default(false)->after('activo');
                $table->integer('dias_alerta_vencimiento')->default(30)->after('es_perecedero');
            }
        });

        Schema::table('venta_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('venta_detalles', 'lote_id')) {
                $table->foreignId('lote_id')->nullable()->after('producto_id')->constrained('lotes')->nullOnDelete();
            }
        });

        Schema::table('compra_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('compra_detalles', 'lote_id')) {
                $table->foreignId('lote_id')->nullable()->after('producto_id')->constrained('lotes')->nullOnDelete();
            }
        });

        Schema::table('movimientos_inventario', function (Blueprint $table) {
            if (!Schema::hasColumn('movimientos_inventario', 'lote_id')) {
                $table->foreignId('lote_id')->nullable()->after('producto_id')->constrained('lotes')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            if (Schema::hasColumn('movimientos_inventario', 'lote_id')) {
                $table->dropForeign(['lote_id']);
                $table->dropColumn('lote_id');
            }
        });

        Schema::table('compra_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('compra_detalles', 'lote_id')) {
                $table->dropForeign(['lote_id']);
                $table->dropColumn('lote_id');
            }
        });

        Schema::table('venta_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('venta_detalles', 'lote_id')) {
                $table->dropForeign(['lote_id']);
                $table->dropColumn('lote_id');
            }
        });

        Schema::table('productos', function (Blueprint $table) {
            if (Schema::hasColumn('productos', 'es_perecedero')) {
                $table->dropColumn(['es_perecedero', 'dias_alerta_vencimiento']);
            }
        });

        Schema::dropIfExists('lotes');
    }
};
