# Facturación Electrónica (SUNAT · Perú)

Módulo de emisión de comprobantes electrónicos (boletas, facturas) bajo UBL 2.1.

## Puesta en marcha

1. **Ejecutar migraciones** (crean `facturacion_configs` y añaden campos `fe_*` a `ventas`):

   ```bash
   php artisan migrate
   ```

2. **Entrar al módulo:** menú lateral → *Configuración → Facturación Electrónica*
   (`/facturacion/configuracion`, solo administradores).

3. **Configurar:** activar la FE, elegir driver y entorno, completar datos del emisor
   y credenciales SUNAT. Botón **Probar conexión con SUNAT** para validar.

## Instalación asistida (recomendado)

Con Greenter ya instalado, prepara carpetas y un certificado de prueba en un paso:

```bash
php artisan facturacion:instalar
```

Crea `storage/app/facturacion/{xml,cdr,pdf,resumen,pe}`, genera un **certificado
autofirmado** válido para Beta en `storage/app/facturacion/pe/certificate.pem` y
verifica las librerías. Copia esa ruta en *Configuración → Facturación Electrónica*.

Luego valida la conexión emitiendo una boleta real de prueba a SUNAT Beta:

```bash
php artisan facturacion:emitir-prueba --empresa=1
```

Debe responder con el CDR aceptado. Si funciona, tu configuración está lista.

## Emisión real ante SUNAT (driver Greenter)

La emisión efectiva usa la librería [Greenter](https://greenter.dev). Instalar:

```bash
composer require greenter/greenter
```

Requisitos:

- **Certificado digital** `.pem` válido. En **Beta** se usa el certificado de
  homologación de SUNAT. Indicar su ruta absoluta en la configuración.
- **Credenciales Clave SOL.** En Beta: RUC `20000000001`, usuario y clave `MODDATOS`.

Mientras Greenter no esté instalado, las ventas se numeran y quedan en estado
**PENDIENTE** (no se rompe el flujo del POS).

## Cómo funciona

- Al cerrar una venta (BOLETA/FACTURA) con la FE **habilitada** y **emisión
  automática** activa, se asigna serie/correlativo y se envía a SUNAT.
- Sin emisión automática → se numera y queda **PENDIENTE**.
- TICKET o FE deshabilitada → **NO_APLICA**.
- El estado SUNAT se muestra en el detalle de cada venta. El XML firmado y el
  CDR se guardan en `storage/app/facturacion/`.

## Representación impresa (A4 y ticket)

Desde el detalle de una venta con comprobante electrónico:

- **Comprobante A4** (`/ventas/{venta}/comprobante`): formato carta con datos del
  emisor, cliente, ítems, totales, importe en letras, hash y **código QR** SUNAT.
- **Ticket SUNAT** (`/ventas/{venta}/ticket`): formato 80 mm para impresora térmica.

Ambos incluyen botón *Imprimir / PDF* (usa el diálogo del navegador: "Guardar como PDF").

## Anulación electrónica

Al anular una venta cuyo comprobante fue emitido (ENVIADO/ACEPTADO), se genera
automáticamente el documento SUNAT según el tipo:

- **Factura → Comunicación de Baja (RA):** se guarda el *ticket* de SUNAT en `fe_baja_ticket`.
- **Boleta → Nota de Crédito (07):** motivo "01 – Anulación de la operación";
  se numera con la serie de NC (`BC01` / `FC01`) y guarda XML/CDR.

El motivo se solicita al usuario al pulsar *Anular*.

## Bandeja de comprobantes

Menú *Ventas → Comprobantes* (`/comprobantes`). Muestra tarjetas de resumen
(aceptados, enviados, pendientes, rechazados/error) y un listado con filtros por
tipo, estado, fecha y búsqueda. Por cada comprobante:

- Ver **A4** / **Ticket**.
- Descargar **XML** firmado y **CDR** de SUNAT.
- **Reenviar** a SUNAT (solo PENDIENTE/ERROR), individual o en bloque con
  *Reenviar pendientes*.

El reenvío es idempotente: conserva la serie y el correlativo ya asignados.

## Reintento automático (programado)

Comando que reenvía los comprobantes PENDIENTE/ERROR de todas las empresas:

```bash
php artisan facturacion:reintentar            # todas las empresas
php artisan facturacion:reintentar --empresa=5 # una empresa
```

Ya está programado cada hora en `routes/console.php`. Para que corra solo, activa
el scheduler del sistema (cron):

```
* * * * * cd /ruta/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

## Envío por correo al cliente

Desde la bandeja o el detalle de venta, botón **Enviar por correo** (visible si el
cliente tiene email y el comprobante fue emitido). Adjunta el **XML** firmado, el
**CDR** y, si está instalado `dompdf/dompdf`, también el **PDF**:

```bash
composer require dompdf/dompdf
```

Requiere configurar el correo saliente (SMTP) en el `.env` (`MAIL_*`).

## Modo de declaración de boletas

En *Configuración → Facturación Electrónica*, campo **Declaración de boletas**:

- **Individual:** cada boleta se envía a SUNAT al emitirse (útil en Beta/pruebas).
- **Por Resumen Diario (RC):** las boletas se numeran y quedan PENDIENTE; se
  declaran juntas una vez al día. **Recomendado para producción** (evita la doble
  declaración). Las facturas siempre se envían individualmente.

## Resumen diario de boletas (RC)

En producción SUNAT recibe las boletas mediante el Resumen Diario. 

- Manual: bandeja → **Resumen de boletas** (procesa el día anterior).
- Automático: `php artisan facturacion:resumen-boletas` (programado a la 01:00).
  Opciones `--fecha=Y-m-d` y `--empresa=ID`.

El resultado se guarda en `facturacion_resumenes` (ticket, estado, CDR) y cada
boleta incluida queda marcada con `fe_resumen_id` / `fe_resumen_estado`.

## Estados del comprobante (`ventas.fe_estado`)

`NO_APLICA` · `PENDIENTE` · `ENVIADO` · `ACEPTADO` · `RECHAZADO` · `ERROR`

## Archivos del módulo

- `database/migrations/2026_08_11_000001_create_facturacion_configs_table.php`
- `database/migrations/2026_08_11_000002_add_facturacion_electronica_to_ventas_table.php`
- `app/Models/FacturacionConfig.php`
- `app/Services/Facturacion/` (Manager, contrato, DTO, drivers Null y Greenter)
- `app/Http/Controllers/FacturacionController.php`
- `resources/views/facturacion/configuracion.blade.php`
- Rutas en `routes/web.php`; ítem de menú en `resources/views/layouts/app.blade.php`.
