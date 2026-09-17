# SaaS de Ventas e Inventario

Sistema web de **ventas (POS)** e **inventario** para cualquier tipo de negocio, construido con **Laravel 11 + MySQL** y **Bootstrap/CSS** propio. Incluye login, registro, dashboard con métricas y gráficos, y un menú vertical con todos los módulos del sistema.

![Dashboard](2026-07-05_08h08_57.png)

---

## Requisitos

- PHP **8.2** o superior (con extensiones `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `ctype`, `json`, `bcmath`)
- [Composer](https://getcomposer.org/)
- MySQL 5.7+ / MariaDB 10.3+
- (Recomendado en Windows) **XAMPP**, **Laragon** o **WAMP**

---

## Instalación paso a paso

### 1. Instalar las dependencias de Laravel
Desde la carpeta del proyecto (`C:\SAAS\saas-ventas-inventario`):

```bash
composer install
```

> Esto descarga el framework Laravel y todas las librerías en la carpeta `vendor/`. Es obligatorio: el proyecto no corre sin este paso.

### 2. Crear el archivo de entorno y la clave de la app

```bash
copy .env.example .env
php artisan key:generate
```

*(En Mac/Linux usa `cp .env.example .env`.)*

### 3. Crear la base de datos
En MySQL (phpMyAdmin, HeidiSQL o consola):

```sql
CREATE DATABASE saas_ventas_inventario CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Verifica que las credenciales en `.env` coincidan con tu servidor:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=saas_ventas_inventario
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Ejecutar migraciones y datos de ejemplo

```bash
php artisan migrate --seed
```

Esto crea todas las tablas y carga datos demo (productos, clientes y ~100 ventas de los últimos 30 días) para que el dashboard muestre gráficos con información real.

### 5. Enlazar el almacenamiento (para imágenes de productos)

```bash
php artisan storage:link
```

### 6. Levantar el servidor

```bash
php artisan serve
```

Abre **http://localhost:8000** en tu navegador.

---

## Credenciales de acceso (datos demo)

| Rol       | Correo               | Contraseña |
|-----------|----------------------|------------|
| Administrador (Negocio 1) | `admin@saas.test`     | `password` |
| Vendedor (Negocio 1)      | `vendedor@saas.test`  | `password` |
| Administrador (Negocio 2) | `admin@boutique.test` | `password` |
| **Super Admin (plataforma)** | `super@saas.test`     | `password` |

> Los dos negocios son **tenants independientes**: al iniciar sesión con uno solo verás sus propios productos, ventas, clientes y reportes. Es la prueba de que el aislamiento multi-empresa funciona.

Al **Registrarte** creas un negocio nuevo (tenant) con su propio administrador; los datos quedan aislados del resto.

---

## Qué incluye esta primera entrega

- **Autenticación completa**: login, registro y cierre de sesión con validaciones en español.
- **Dashboard** con 4 tarjetas de métricas (ventas de hoy, ventas del mes, productos, clientes), aviso de stock bajo, 2 gráficos (Chart.js) y tablas de "más vendidos" y "últimas ventas".
- **Menú vertical** con todos los módulos del sistema agrupados: Ventas, Inventario, Compras, Personas, Reportes y Configuración.
- **Base de datos** completa: usuarios, categorías, marcas, productos, clientes, proveedores, ventas + detalle, compras + detalle y movimientos de inventario (kardex).
- Diseño visual fiel a la maqueta (sidebar azul, header oscuro, tarjetas de colores).

### Módulos con CRUD real ya funcional
- **Punto de Venta (POS)**: buscador de productos en vivo, carrito interactivo, cliente/comprobante/método de pago/descuento, cálculo de IGV, y al cobrar registra la venta, **descuenta el stock** y genera el movimiento de inventario dentro de una transacción. Redirige al **ticket imprimible**.
- **Ventas (historial)**: listado con filtros por fecha, estado y número; ver/imprimir ticket y **anular** venta (repone el stock automáticamente).
- **Compras**: registro de compras a proveedor con líneas de producto dinámicas; al guardar **incrementa el stock**, actualiza el costo de compra y registra el movimiento de inventario. Incluye historial, detalle y **anular** (ajusta el stock).
- **Clientes** y **Proveedores**: CRUD completo con búsqueda, validación y protección contra borrado si tienen ventas/compras asociadas.
- **Kardex**: historial de todos los movimientos de inventario (entradas, salidas y ajustes) con filtros por producto, tipo y rango de fechas, mostrando stock antes/después de cada operación.
- **Ajustes de stock**: corrige el stock por conteo físico, merma, robo, devolución o corrección (fijar/sumar/restar), dejando registro en el kardex.
- **Reportes**: **Ventas** (total, N° de ventas, ticket promedio, IGV, gráficos por día y método de pago, top productos), **Inventario** (valorización a costo y venta, unidades por categoría, productos con stock bajo) y **Ganancias** (ingresos, costo, utilidad bruta, margen, utilidad por día y por producto). Todos con filtro de fechas y opción de imprimir.
- **Usuarios y roles**: CRUD de usuarios con roles (Administrador / Gerente / Vendedor), activar/desactivar, contraseña opcional al editar. Acceso restringido solo a administradores mediante middleware `admin`; el menú de Usuarios se oculta a los demás roles. Protecciones: no puedes eliminar tu propia cuenta ni quitarte el rol de admin a ti mismo.
- **Configuración de empresa** (solo admin): nombre, RUC, dirección, teléfono, email, símbolo de moneda, **% de IGV** y logo. Estos datos se aplican en todo el sistema: el nombre y logo aparecen en el sidebar, login y ticket; el IGV configurado se usa automáticamente en el POS y en Compras (ya no está fijo en 18%).
- **Exportación a Excel**: los tres reportes (ventas, inventario, ganancias) y el listado de productos incluyen botón **Exportar** que descarga un CSV compatible con Excel (con BOM UTF-8 para acentos), respetando los filtros aplicados. No requiere librerías externas. Para PDF, usa el botón **Imprimir** → «Guardar como PDF» del navegador.
- **Productos**: alta/edición/eliminación, búsqueda por nombre o código, filtro por categoría y por stock bajo, subida de imagen, control de stock mínimo y paginación.
- **Categorías** y **Marcas**: CRUD completo con búsqueda, conteo de productos asociados y protección contra borrado si están en uso.

El resto de módulos del menú (POS, Compras, Clientes, Reportes, etc.) muestran por ahora una pantalla **"en construcción"**; sus tablas y modelos ya están listos para agregarles el CRUD en las siguientes iteraciones.

---

## Multi-tenancy (arquitectura SaaS)

El sistema es **multi-empresa (multi-tenant) con base de datos compartida y columna `empresa_id`**. Cada negocio es un *tenant* aislado:

- Todas las tablas de negocio tienen `empresa_id`. La empresa activa se resuelve del usuario autenticado en el middleware `IdentifyTenant` y se guarda en `App\Support\Tenant`.
- El trait `App\Models\Concerns\BelongsToEmpresa` aplica un *global scope* que filtra automáticamente por la empresa activa y rellena `empresa_id` al crear registros. Así ningún módulo puede ver datos de otro tenant sin cambios en cada consulta.
- Las consultas en crudo (`DB::table`) de dashboard y reportes filtran explícitamente por `empresa_id`.
- El código de producto es único **por empresa**, no global; la numeración de ventas/compras es independiente por tenant.
- El registro (`/register`) provisiona la empresa y su administrador de forma atómica.

## Planes y suscripciones

Cada empresa (tenant) tiene un **plan** y un **estado de suscripción**:

- **Catálogo de planes** (`planes`): Gratis, Emprendedor y Negocio, con precio mensual y límites (productos, usuarios, ventas/mes; `null` = ilimitado).
- **Prueba gratis**: al registrarse, el negocio inicia una **prueba de 14 días** del plan superior.
- **Feature gating**: no se pueden crear más productos ni usuarios de los que permite el plan (bloqueado en los controladores con mensaje claro).
- **Middleware `suscripcion`** (`EnsureSuscripcionActiva`): si la prueba o la suscripción vencen, la app se bloquea y redirige a la página de suscripción (deja pasar solo esa página y el logout).
- **Página de suscripción** (`/suscripcion`): muestra el plan actual, el uso vs. límites y permite cambiar de plan. La activación es **manual por ahora** (marcador de posición para la pasarela de pago).
- **Historial** en `suscripciones` para futura facturación.
- El layout muestra un **banner** con los días de prueba restantes o el aviso de suscripción vencida.

## Panel Super Admin (operador de la plataforma)

Un usuario con la bandera **`is_super`** (sin empresa asignada) accede a `/admin`, por encima del aislamiento por tenant (el middleware `superadmin` limpia el tenant para ver todos los negocios). Incluye:

- **Resumen**: total de tenants, activos / en prueba / suspendidos, usuarios, **MRR estimado**, altas por mes y pruebas por vencer.
- **Tenants**: listado con búsqueda y filtros; detalle con uso vs. límites, usuarios e historial de suscripciones; acciones para **suspender / reactivar**, **cambiar de plan** y **eliminar** (purga sus datos).
- **Impersonar**: entrar como un tenant para dar soporte, con banner para volver al panel.
- **Planes**: CRUD completo del catálogo (precio, límites, orden, activo).
- **Suscripciones**: listado global con filtros (estado, plan, negocio) y exportación a CSV.
- **Reportes de negocio**: tenants por estado, MRR total y por plan, altas e ingresos de los últimos 12 meses, exportables a CSV.
- **Administradores de plataforma**: CRUD de super admins (con protección para no quedarte sin acceso).
- **Registro de actividad**: bitácora de acciones del super admin (suspender, activar, cambiar plan, eliminar, impersonar, planes y configuración).
- **Configuración de la plataforma**: nombre y logo del SaaS, días de prueba por defecto (se aplican al registrar), correo de soporte, moneda y mensaje de bienvenida.
- Al iniciar sesión, el super admin es redirigido a `/admin`; los negocios van a su dashboard normal.

Acceso demo: `super@saas.test` / `password`.

### Lo que falta para el SaaS comercial (siguientes iteraciones)

1. **Cobro** mediante pasarela de pago + webhooks de estado (pausado por ahora; la suscripción se activa manualmente).
2. **Facturación** de la suscripción (comprobantes, reintentos, dunning).
3. **Verificación de email**, recuperación de contraseña y **landing/pricing** pública.

## Estructura del proyecto

```
app/
 ├─ Http/Controllers/     Auth (Login, Register), Dashboard, Modulo
 └─ Models/               User, Producto, Categoria, Marca, Cliente,
                          Proveedor, Venta, VentaDetalle, Compra,
                          CompraDetalle, MovimientoInventario
database/
 ├─ migrations/           Todas las tablas del sistema
 └─ seeders/              DatabaseSeeder (datos demo)
resources/views/
 ├─ layouts/app.blade.php Layout maestro (sidebar + topbar)
 ├─ auth/                 login, register
 ├─ dashboard/            index
 └─ modulos/              placeholder
public/css/app.css        Estilos del diseño
routes/web.php            Rutas del sistema
```

---

## Próximos pasos sugeridos

1. **Planes y suscripciones** ligados a cada empresa (tabla `planes` + `suscripciones`).
2. **Cobro recurrente** con Stripe/MercadoPago/Culqi y webhooks de estado.
3. **Límites por plan** aplicados con middleware/policies (feature gating).
4. **Panel super-admin** (gestión de tenants, métricas globales, suspensión por impago).
5. **Onboarding pulido**: verificación de email, landing y página de precios.
