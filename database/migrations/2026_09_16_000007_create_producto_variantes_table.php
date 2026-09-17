<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('codigo')->nullable();
            $table->string('sabor');
            $table->integer('stock')->default(0);
            $table->integer('stock_minimo')->default(5);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'tiene_variantes')) {
                $table->boolean('tiene_variantes')->default(false)->after('tiene_empaque');
            }
        });

        Schema::table('compra_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('compra_detalles', 'producto_variante_id')) {
                $table->foreignId('producto_variante_id')->nullable()->after('producto_id')->constrained('producto_variantes')->nullOnDelete();
            }
        });

        Schema::table('venta_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('venta_detalles', 'producto_variante_id')) {
                $table->foreignId('producto_variante_id')->nullable()->after('producto_id')->constrained('producto_variantes')->nullOnDelete();
            }
        });

        Schema::table('pedido_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('pedido_detalles', 'producto_variante_id')) {
                $table->foreignId('producto_variante_id')->nullable()->after('producto_id')->constrained('producto_variantes')->nullOnDelete();
            }
        });

        Schema::table('movimientos_inventario', function (Blueprint $table) {
            if (!Schema::hasColumn('movimientos_inventario', 'producto_variante_id')) {
                $table->foreignId('producto_variante_id')->nullable()->after('producto_id')->constrained('producto_variantes')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropForeign(['producto_variante_id']);
            $table->dropColumn('producto_variante_id');
        });

        Schema::table('pedido_detalles', function (Blueprint $table) {
            $table->dropForeign(['producto_variante_id']);
            $table->dropColumn('producto_variante_id');
        });

        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->dropForeign(['producto_variante_id']);
            $table->dropColumn('producto_variante_id');
        });

        Schema::table('compra_detalles', function (Blueprint $table) {
            $table->dropForeign(['producto_variante_id']);
            $table->dropColumn('producto_variante_id');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('tiene_variantes');
        });

        Schema::dropIfExists('producto_variantes');
    }
};
