# 🧊 Sistema de Monitoreo — Cámaras Frigoríficas
## Instrucciones de instalación para XAMPP

---

## Paso 1 — Copiar archivos a XAMPP

Copiá **toda esta carpeta** (`camaras/`) dentro de `C:\xampp\htdocs\`

Al terminar debe quedar así:
```
C:\xampp\htdocs\
    └── camaras\
        ├── LEEME.md
        ├── schema.sql
        ├── config\
        ├── api\
        ├── Inicio.html
        └── ...
```

---

## Paso 2 — Crear la base de datos

1. Abrí tu navegador y entrá a: **http://localhost/phpmyadmin**
2. Hacé clic en la pestaña **"SQL"** (arriba a la derecha)
3. Copiá TODO el contenido del archivo `schema.sql` y pegalo ahí
4. Hacé clic en **"Continuar"** o **"Ejecutar"**

✅ Si salió bien: vas a ver `camaras_db` en el panel izquierdo de phpMyAdmin.

---

## Paso 3 — Configurar las credenciales

Abrí `config/db.php` con cualquier editor (Notepad, VS Code…) y editá estas líneas:

```php
define('DB_USER', 'root');   // Usuario de MySQL — en XAMPP por defecto es 'root'
define('DB_PASS', '');       // Contraseña — en XAMPP suele estar vacía ('')
```

Si no le pusiste contraseña a MySQL, dejala vacía. Si le pusiste una, escribila entre las comillas.

---

## Paso 4 — Iniciar XAMPP

Abrí el **Panel de Control de XAMPP** y hacé clic en **Start** junto a:
- **Apache** (el servidor web que corre el PHP)
- **MySQL** (la base de datos)

---

## Paso 5 — Probar que funciona

Abrí en tu navegador: **http://localhost/camaras/Inicio.html**

Deberías ver la pantalla de inicio del sistema.

Para probar la API directamente:
- http://localhost/camaras/api/get_stats_camaras.php
  → Devuelve `[]` (array vacío) hasta que el ESP32 empiece a enviar datos. ✅ Eso es correcto.

---

## Paso 6 — Configurar el ESP32

El ESP32 debe hacer peticiones **HTTP POST** a:
```
http://<IP-de-tu-PC>/camaras/api/post_datos.php
```

Para saber la IP de tu PC en la red local, abrí CMD y escribí `ipconfig`.
Buscá la línea que dice "Dirección IPv4", por ejemplo: `192.168.1.105`

El ESP32 debe enviar este JSON en el body:
```json
{ "Camara": 1, "Temp": 3.5 }
```

Ejemplo básico en Arduino (ESP32):
```cpp
#include <WiFi.h>
#include <HTTPClient.h>

void enviarTemperatura(int camara, float temp) {
    HTTPClient http;
    http.begin("http://192.168.1.105/camaras/api/post_datos.php");
    http.addHeader("Content-Type", "application/json");
    
    String body = "{\"Camara\": " + String(camara) + ", \"Temp\": " + String(temp, 1) + "}";
    int code = http.POST(body);
    http.end();
}
```

---

## Estructura de archivos completa

```
camaras/
├── LEEME.md               ← este archivo
├── schema.sql             ← ejecutar UNA VEZ en phpMyAdmin
│
├── config/
│   ├── db.php             ← credenciales de MySQL (¡editá DB_PASS!)
│   └── helpers.php        ← funciones CORS y utilidades (no tocar)
│
├── api/
│   ├── post_datos.php     ← POST: recibe datos del ESP32
│   ├── get_stats.php      ← GET:  estadísticas globales (?horas=24)
│   ├── get_stats_camaras.php  ← GET: estadísticas + temp actual por cámara
│   └── get_latest.php     ← GET:  últimas N lecturas (?n=100)
│
├── Inicio.html            ← Pantalla de bienvenida
├── Temperaturas.html      ← Monitoreo en tiempo real
├── Historial.html         ← Registro histórico con colores de alerta
├── General.css            ← Estilos del menú lateral (todas las páginas)
├── Inicio.css
├── Temperaturas.css
└── Historial.css
```

---

## Referencia rápida de la API

| Endpoint | Método | Qué devuelve |
|---|---|---|
| `api/post_datos.php` | **POST** | Guarda una lectura del ESP32 |
| `api/get_stats.php` | GET | Máx, Mín y Promedio global |
| `api/get_stats.php?horas=24` | GET | Ídem, solo últimas 24 hs |
| `api/get_stats_camaras.php` | GET | Máx, Mín, Promedio y temp actual por cámara |
| `api/get_latest.php?n=100` | GET | Últimas 100 lecturas de todas las cámaras |

---

## Colores de alerta en el Historial

| Color | Significado |
|---|---|
| 🟢 **Verde** | Temperatura entre **0°C y 10°C** (rango normal) |
| 🔴 **Rojo** | Temperatura **menor a 0°C** o **mayor a 10°C** (fuera de rango) |
