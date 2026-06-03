<p align="center">
  <img src="assets/logo.png" width="220">
</p>

<h1 align="center">
Sistema de Monitoreo para Cámaras Frigoríficas
</h1>

<p align="center">
Monitoreo en tiempo real de temperatura utilizando ESP32, PHP y MySQL.
</p>

---

## ¿Qué es este proyecto?

Este proyecto nació como parte de las Prácticas Profesionalizantes.

La idea es simple: tener un sistema capaz de recibir temperaturas desde varias cámaras frigoríficas, almacenarlas en una base de datos y mostrarlas desde una interfaz web clara y fácil de usar.

El sistema permite:

- Monitorear temperaturas en tiempo real
- Registrar lecturas históricas
- Detectar temperaturas fuera de rango
- Visualizar estadísticas por cámara
- Centralizar toda la información desde una única interfaz

---

## Tecnologías utilizadas

| Tecnología | Uso |
|------------|-----|
| HTML | Interfaz |
| CSS | Diseño |
| JavaScript | Interactividad |
| PHP | Backend |
| MySQL | Base de datos |
| ESP32 | Captura y envío de datos |

---

## Cómo funciona

```text
ESP32
   │
   ▼
API PHP
   │
   ▼
MySQL
   │
   ▼
Interfaz Web
```

El ESP32 envía temperaturas mediante peticiones HTTP.

La API recibe los datos, los almacena en MySQL y la interfaz web los muestra en tiempo real.

---

## Lo que vas a encontrar en el repo

```text
📂 Frontend 1.0/
Etapa terminada del frontend, contiene el Inicio, Historial y Temperaturas.

📂 Backend 1.0/
Etapa terminada del Backend, contiene los metodos post y get.

📂 DB 1.0/
Etapa terminda de la DB, contiene credenciales de MySQL y funciones CORS y utilidades (no tocar).

📄 LEE.ME.html
Todo lo que necesitas saber del funcionamiento del proyecto, parte por parte.

📄 schema.sql
Script para crear la base de datos.
```

---

## Vistas principales

### Inicio

Pantalla de bienvenida del sistema con acceso rápido a las distintas secciones.

### Temperaturas

Visualización de:

- Temperatura actual
- Máxima
- Mínima
- Estadísticas de las últimas 24 horas

### Historial

Registro completo de lecturas almacenadas.

Las temperaturas fuera del rango permitido se resaltan automáticamente para facilitar su detección.

---

## API

### Registrar temperatura

```http
POST /api/post_datos.php
```

Ejemplo:

```json
{
  "Camara": 1,
  "Temp": 3.5
}
```

---

### Obtener estadísticas

```http
GET /api/get_stats.php
```

---

### Obtener estadísticas por cámara

```http
GET /api/get_stats_camaras.php
```

---

### Obtener últimas lecturas

```http
GET /api/get_latest.php?n=100
```

---

## Instalación rápida

1. Clonar el repositorio

```bash
git clone https://github.com/mistercapo3012/Proyecto-Practicas-Profesionalizantes.git
```

2. Copiar la carpeta en:

```text
xampp/htdocs/
```

3. Ejecutar:

```text
schema.sql
```

desde phpMyAdmin.

4. Configurar las credenciales en:

```text
config/db.php
```

5. Iniciar Apache y MySQL.

6. Abrir:

```text
http://localhost/camaras/
```

---

## Objetivo del proyecto

Más allá de ser una práctica académica, el objetivo fue desarrollar una solución real para el monitoreo de cámaras frigoríficas utilizando tecnologías accesibles y hardware de bajo costo.

---

<p align="center">
Desarrollado para Prácticas Profesionalizantes - ccmrc • 2026
</p>
