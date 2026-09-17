<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ActividadController as AdminActividadController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ConfiguracionController as AdminConfiguracionController;
use App\Http\Controllers\Admin\ReporteController as AdminReporteController;
use App\Http\Controllers\Admin\SuscripcionController as AdminSuscripcionController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Web - SaaS Ventas e Inventario
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check() && auth()->user()->is_super) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('dashboard');
});

// ---------- Autenticación ----------
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register']);
});

Route::post('logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// ---------- Área protegida ----------
Route::middleware(['auth', 'suscripcion'])->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Suscripción (siempre accesible dentro del área protegida)
    Route::get('suscripcion', [SuscripcionController::class, 'index'])->name('suscripcion.index');
    Route::post('suscripcion/cambiar', [SuscripcionController::class, 'cambiar'])
        ->middleware('admin')->name('suscripcion.cambiar');

    /*
     | Módulos del sistema. Por ahora muestran una pantalla "en construcción".
     | En las siguientes iteraciones cada uno tendrá su CRUD completo
     | (Route::resource('productos', ProductoController::class), etc.).
     */

    // Ventas / POS
    Route::get('ventas/pos', [VentaController::class, 'pos'])->name('ventas.pos');
    Route::get('ventas/create', [VentaController::class, 'create'])->name('ventas.create');
    Route::get('ventas/buscar-productos', [VentaController::class, 'buscarProductos'])->name('ventas.buscar');
    Route::post('ventas', [VentaController::class, 'store'])->name('ventas.store');
    Route::get('ventas', [VentaController::class, 'index'])->name('ventas.index');
    Route::get('ventas/{venta}', [VentaController::class, 'show'])->name('ventas.show');
    Route::post('ventas/{venta}/anular', [VentaController::class, 'anular'])->name('ventas.anular');

    // Pedidos de Ruta y Rutas de Entrega
    Route::resource('rutas', RutaController::class)->except('show');
    Route::get('pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('pedidos/create', [PedidoController::class, 'create'])->name('pedidos.create');
    Route::post('pedidos', [PedidoController::class, 'store'])->name('pedidos.store');
    Route::get('pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
    Route::post('pedidos/{pedido}/convertir', [PedidoController::class, 'convertir'])->name('pedidos.convertir');
    Route::post('pedidos/{pedido}/cancelar', [PedidoController::class, 'cancelar'])->name('pedidos.cancelar');

    // Representación impresa del comprobante electrónico
    Route::get('ventas/{venta}/comprobante', [FacturacionController::class, 'comprobante'])->name('facturacion.comprobante');
    Route::get('ventas/{venta}/ticket', [FacturacionController::class, 'ticket'])->name('facturacion.ticket');
    // Bandeja de comprobantes electrónicos
    Route::get('comprobantes', [FacturacionController::class, 'comprobantes'])->name('comprobantes.index');
    Route::post('comprobantes/reenviar-pendientes', [FacturacionController::class, 'reenviarPendientes'])->name('facturacion.reenviar.pendientes');
    Route::post('comprobantes/{venta}/reenviar', [FacturacionController::class, 'reenviar'])->name('facturacion.reenviar');
    Route::get('comprobantes/{venta}/xml', [FacturacionController::class, 'descargarXml'])->name('facturacion.xml');
    Route::get('comprobantes/{venta}/cdr', [FacturacionController::class, 'descargarCdr'])->name('facturacion.cdr');
    Route::post('comprobantes/{venta}/email', [FacturacionController::class, 'enviarCorreo'])->name('facturacion.email');
    Route::post('comprobantes/resumen-diario', [FacturacionController::class, 'enviarResumenDiario'])->name('facturacion.resumen');

    // Compras
    Route::resource('compras', CompraController::class)->except(['edit', 'update']);
    Route::post('compras/{compra}/anular', [CompraController::class, 'anular'])->name('compras.anular');

    // Inventario (CRUD real)
    Route::get('productos/export', [ProductoController::class, 'export'])->name('productos.export');
    Route::resource('productos', ProductoController::class)->except('show');
    Route::resource('categorias', CategoriaController::class)->except('show');
    Route::resource('marcas', MarcaController::class)->except('show');
    Route::get('inventario/kardex', [InventarioController::class, 'kardex'])->name('inventario.kardex');
    Route::get('inventario/ajustes', [InventarioController::class, 'ajustes'])->name('inventario.ajustes');
    Route::post('inventario/ajustes', [InventarioController::class, 'guardarAjuste'])->name('inventario.ajustes.guardar');
    Route::get('lotes', [LoteController::class, 'index'])->name('lotes.index');
    Route::post('lotes', [LoteController::class, 'store'])->name('lotes.store');

    // Personas
    Route::resource('clientes', ClienteController::class)->except('show');
    Route::resource('proveedores', ProveedorController::class)->except('show');

    // Reportes
    Route::get('reportes/ventas', [ReporteController::class, 'ventas'])->name('reportes.ventas');
    Route::get('reportes/ventas/export', [ReporteController::class, 'exportVentas'])->name('reportes.ventas.export');
    Route::get('reportes/inventario', [ReporteController::class, 'inventario'])->name('reportes.inventario');
    Route::get('reportes/inventario/export', [ReporteController::class, 'exportInventario'])->name('reportes.inventario.export');
    Route::get('reportes/ganancias', [ReporteController::class, 'ganancias'])->name('reportes.ganancias');
    Route::get('reportes/ganancias/export', [ReporteController::class, 'exportGanancias'])->name('reportes.ganancias.export');

    // Configuración
    Route::resource('usuarios', UsuarioController::class)->except('show')->middleware('admin');
    Route::get('configuracion/empresa', [EmpresaController::class, 'edit'])->name('configuracion.empresa')->middleware('admin');
    Route::put('configuracion/empresa', [EmpresaController::class, 'update'])->name('configuracion.empresa.update')->middleware('admin');

    // Facturación Electrónica (SUNAT · Perú)
    Route::middleware('admin')->group(function () {
        Route::get('facturacion/configuracion', [FacturacionController::class, 'edit'])->name('facturacion.config');
        Route::put('facturacion/configuracion', [FacturacionController::class, 'update'])->name('facturacion.config.update');
        Route::post('facturacion/probar-conexion', [FacturacionController::class, 'probarConexion'])->name('facturacion.probar');
    });
});


// ---------- Salir de impersonación (el usuario ya es del tenant) ----------
Route::post('impersonar/salir', [ImpersonationController::class, 'salir'])
    ->middleware('auth')->name('impersonar.salir');

// ---------- Super Admin (operador de la plataforma) ----------
Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Tenants (empresas)
    Route::get('tenants', [AdminTenantController::class, 'index'])->name('tenants.index');
    Route::get('tenants/{tenant}', [AdminTenantController::class, 'show'])->name('tenants.show');
    Route::post('tenants/{tenant}/suspender', [AdminTenantController::class, 'suspender'])->name('tenants.suspender');
    Route::post('tenants/{tenant}/activar', [AdminTenantController::class, 'activar'])->name('tenants.activar');
    Route::put('tenants/{tenant}/plan', [AdminTenantController::class, 'cambiarPlan'])->name('tenants.plan');
    Route::post('tenants/{tenant}/impersonar', [AdminTenantController::class, 'impersonar'])->name('tenants.impersonar');
    Route::delete('tenants/{tenant}', [AdminTenantController::class, 'destroy'])->name('tenants.destroy');

    // Planes (catálogo)
    Route::resource('planes', AdminPlanController::class)
        ->parameters(['planes' => 'plan'])->except('show');

    // Suscripciones (listado global)
    Route::get('suscripciones', [AdminSuscripcionController::class, 'index'])->name('suscripciones.index');
    Route::get('suscripciones/export', [AdminSuscripcionController::class, 'export'])->name('suscripciones.export');

    // Reportes de negocio
    Route::get('reportes', [AdminReporteController::class, 'index'])->name('reportes.index');
    Route::get('reportes/export', [AdminReporteController::class, 'export'])->name('reportes.export');

    // Administradores de plataforma
    Route::resource('admins', AdminUserController::class)->except('show');

    // Registro de actividad
    Route::get('actividad', [AdminActividadController::class, 'index'])->name('actividad.index');

    // Configuración de la plataforma
    Route::get('configuracion', [AdminConfiguracionController::class, 'edit'])->name('configuracion.edit');
    Route::put('configuracion', [AdminConfiguracionController::class, 'update'])->name('configuracion.update');
});
