<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'regimen_tributario')) {
                $table->string('regimen_tributario', 50)->default('GENERAL')->after('igv'); // GENERAL (12%) | PEQUENO_CONTRIBUYENTE (5%)
            }
        });

        Schema::table('facturacion_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('facturacion_configs', 'fel_certificador')) {
                $table->string('fel_certificador', 50)->default('infile')->after('pais'); // infile | digifact | guatefacturas | megaprint | null
                $table->string('fel_usuario')->nullable()->after('fel_certificador');
                $table->text('fel_llave')->nullable()->after('fel_usuario');
                $table->text('fel_token')->nullable()->after('fel_llave');
            }
        });

        Schema::table('ventas', function (Blueprint $table) {
            if (!Schema::hasColumn('ventas', 'fel_uuid')) {
                $table->string('fel_uuid', 64)->nullable()->after('total');
                $table->string('fel_serie', 50)->nullable()->after('fel_uuid');
                $table->string('fel_numero', 50)->nullable()->after('fel_serie');
                $table->timestamp('fel_fecha_certificacion')->nullable()->after('fel_numero');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (Schema::hasColumn('empresas', 'regimen_tributario')) {
                $table->dropColumn('regimen_tributario');
            }
        });

        Schema::table('facturacion_configs', function (Blueprint $table) {
            if (Schema::hasColumn('facturacion_configs', 'fel_certificador')) {
                $table->dropColumn(['fel_certificador', 'fel_usuario', 'fel_llave', 'fel_token']);
            }
        });

        Schema::table('ventas', function (Blueprint $table) {
            if (Schema::hasColumn('ventas', 'fel_uuid')) {
                $table->dropColumn(['fel_uuid', 'fel_serie', 'fel_numero', 'fel_fecha_certificacion']);
            }
        });
    }
};
