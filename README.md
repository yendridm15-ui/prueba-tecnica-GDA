# API de Customers — Prueba Técnica FullStack PHP para GDALab

Solución a la prueba técnica de la empresa **GDALab**: API RESTful en **Laravel 13** para registrar, consultar y eliminar lógicamente customers, con autenticación por token, validaciones vía middlewares y logging de entrada/salida con IP de origen.

Todas las respuestas usan el mismo formato:

```json
{
    "success": true,
    "message": "Descripción del resultado",
    "data": { }
}
```

---

## Requerimientos mínimos

**Opción A — Local:**

| Requisito | Versión |
|---|---|
| PHP | >= 8.3 (extensiones: `pdo_mysql`, `openssl`, `mbstring`, `curl`, `fileinfo`, `zip`) |
| Composer | >= 2.x |
| MySQL | >= 8.0 |

**Opción B — Docker:** solo Docker Desktop / Docker Engine con Compose v2.

---

## Instalación

### Local

```bash
git clone <repo-url> && cd prueba-tecnica
composer install
cp .env.example .env                          # en Windows: copy .env.example .env
php artisan key:generate
mysql -u root -p -e "CREATE DATABASE mydb"    # crea la base antes de migrar
php artisan migrate --seed
php artisan serve                             # http://127.0.0.1:8000
```

El `.env.example` ya viene apuntando a **MySQL** local (base `mydb`, usuario `root` sin clave). Si tu instalación usa otras credenciales, ajusta en `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mydb
DB_USERNAME=root
DB_PASSWORD=
```

### Docker (entorno local)

> El `docker-compose.yml` está pensado para levantar el **entorno local** completo sin instalar PHP, Composer ni MySQL en la máquina. No es una configuración de producción.

```bash
docker compose up --build     # API en http://localhost:8000
```

Eso es todo — no hace falta crear el `.env` ni generar key: la configuración va en el propio `docker-compose.yml` y el contenedor genera el `APP_KEY` automáticamente si no viene definido.

Levanta tres contenedores: **nginx** (puerto 8000), **php-fpm** con la aplicación, y **MySQL** (expuesto en el puerto **3306** del host, base `mydb`, usuario `mydb_user` / clave `secret`). La app espera a que MySQL esté sano, ejecuta migraciones + seeders automáticamente y queda lista.

> Si ya tienes un MySQL corriendo en tu máquina en el 3306, detenlo o cambia el puerto publicado en el `docker-compose.yml` para que no choquen.

---

## Configuración (.env)

| Variable | Default | Descripción |
|---|---|---|
| `API_TOKEN_TTL_MINUTES` | `60` | Minutos de vida del token de autenticación. |
| `API_LOG_RESPONSES` | `true` | Si es `false`, no se guardan los logs de salida (los de entrada siempre se guardan). |
| `APP_ENV` | `local` | En `production` los logs de salida se desactivan automáticamente. |
| `APP_DEBUG` | `false` | Siempre debe estar en `false` (requerimiento de la prueba). |

### Datos que crean los seeders

**Usuario predeterminado para el login:**

| Campo | Valor |
|---|---|
| Email | `test@example.com` |
| Clave | `password` |

**Regiones y comunas** (estados y ciudades de Venezuela) más 5 customers de ejemplo:

| id_reg | Región (estado) | Comunas (ciudades) |
|---|---|---|
| 1 | Distrito Capital | Caracas, El Valle, La Vega, Catia |
| 2 | Miranda | Chacao, Baruta, Petare, Los Teques |
| 3 | Zulia | Maracaibo, Cabimas, Ciudad Ojeda |
| 4 | Carabobo | Valencia, Puerto Cabello, Guacara |

---

## Autenticación

Se obtiene un token vía `POST /api/login` usando el usuario predeterminado que crean los seeders: **`test@example.com`** con clave **`password`**. El token es un **SHA1 único** compuesto por `email + fecha/hora del login + random(200–500)`, con expiración configurable. Los tokens vencidos son rechazados.

Envíalo en cada request protegido con cualquiera de estos headers:

```
Authorization: Bearer <token>
X-Api-Token: <token>
```

---

## Definición de servicios

> Todos los endpoints aceptan y retornan JSON (`Content-Type: application/json`). Solo se permiten los métodos definidos; cualquier otro retorna `405 — "Método no permitido"`.

### 1. Login — `POST /api/login`

| Campo (body) | Reglas |
|---|---|
| `email` | requerido, email válido |
| `password` | requerido |

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

**200 OK**
```json
{
    "success": true,
    "message": "Inicio de sesión exitoso",
    "data": {
        "token": "6b89acd6a2cec30944f5b18618840f8b7bc3157f",
        "expires_at": "2026-07-18 05:00:00"
    }
}
```

Errores: `401` credenciales inválidas · `422` validación · `429` rate limit (10/min por IP).

### 2. Registrar customer — `POST /api/customers` 🔒

| Campo (body) | Reglas |
|---|---|
| `dni` | requerido, máx 45, único |
| `email` | requerido, email, máx 120, único |
| `name` / `last_name` | requeridos, máx 45 |
| `address` | opcional, máx 255 |
| `id_reg` | requerido, debe existir y estar activa |
| `id_com` | requerido, debe existir, estar activa **y pertenecer a la región `id_reg`** |

```bash
curl -X POST http://localhost:8000/api/customers \
  -H "Authorization: Bearer <token>" -H "Content-Type: application/json" \
  -d '{"dni":"11111111-1","id_reg":1,"id_com":1,"email":"juan@example.com","name":"Juan","last_name":"Perez","address":"Calle Falsa 123"}'
```

**201 Created**
```json
{
    "success": true,
    "message": "Customer registrado con éxito",
    "data": {
        "dni": "11111111-1",
        "email": "juan@example.com",
        "name": "Juan",
        "last_name": "Perez",
        "address": "Calle Falsa 123",
        "date_reg": "2026-07-18 03:51:52",
        "status": "A",
        "region": "Distrito Capital",
        "commune": "Caracas"
    }
}
```

Errores: `422` validación (incluye comuna que no pertenece a la región) · `401` sin token/token vencido.

### 3. Consultar customer — `GET /api/customers?dni=...` o `?email=...` 🔒

Solo retorna customers **activos (`A`)**. Debe enviarse `dni` o `email` como query param.

```bash
curl "http://localhost:8000/api/customers?dni=11111111-1" -H "Authorization: Bearer <token>"
```

**200 OK**
```json
{
    "success": true,
    "message": "Customer encontrado",
    "data": {
        "name": "Juan",
        "last_name": "Perez",
        "address": "Calle Falsa 123",
        "region": "Distrito Capital",
        "commune": "Caracas"
    }
}
```

Si el customer no tiene dirección, `address` retorna `null`.

Errores: `404` no encontrado (incluye inactivos y eliminados) · `422` sin dni/email · `401` sin token.

### 4. Eliminar customer (lógico) — `DELETE /api/customers?dni=...` o `?email=...` 🔒

Solo elimina customers en estado `A` o `I` (pasan a `T`). Si ya está eliminado o no existe:

**404**
```json
{ "success": false, "message": "Registro no existe", "data": null }
```

**200 OK**
```json
{
    "success": true,
    "message": "Customer eliminado con éxito",
    "data": { "dni": "11111111-1", "email": "juan@example.com", "status": "T" }
}
```

---

## Documentación interactiva y Postman

- **Swagger UI:** con la app corriendo, abre [http://localhost:8000/docs](http://localhost:8000/docs) — documentación interactiva generada desde [docs/openapi.yaml](docs/openapi.yaml). Con el botón **Authorize** pegas el token y puedes probar los endpoints directo desde el navegador.
- **Postman:** importa [docs/prueba-tecnica-GDA.postman_collection.json](docs/prueba-tecnica-GDA.postman_collection.json). Corre primero el request de **Login**: el token se guarda solo en la colección y el resto de los requests lo usan automáticamente.

---

## Borrado lógico

Ningún registro se elimina físicamente. El campo `status` usa el enum:

| Valor | Significado |
|---|---|
| `A` | Activo |
| `I` | Inactivo / desactivado |
| `T` | Trash (eliminado lógicamente) |

---

## Notas para el desarrollador

- **Arquitectura:** Controller (delgado) → **Service** (lógica de negocio: `app/Services`) → Model. Las validaciones previas (campos obligatorios, existencia, relaciones) viven en **middlewares** (`app/Http/Middleware`), igual que la autenticación por token.
- **SQL Injection:** todo el acceso a datos usa Eloquent con bindings; no hay SQL crudo concatenado.
- **Logs:** middleware `LogApiRequests` sobre todo el grupo `api`. Escribe en `storage/logs/api-YYYY-MM-DD.log` (canal diario, 14 días de retención) con IP, método, URI, payload y respuesta. Campos sensibles (`password`, `token`) se enmascaran. En producción (o con `API_LOG_RESPONSES=false`) solo se guardan logs de entrada.
- **Errores:** cualquier excepción en `/api/*` responde con el mismo envelope `{success: false, ...}` (404, 405, 422, 500).
- **Pruebas:** el proyecto trae pruebas automatizadas con Pest que cubren el login, los 3 servicios de customers y el logging:

```bash
php artisan test --compact
```
