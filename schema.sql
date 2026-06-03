-- ============================================================
--  PASO 1: Ejecutá este archivo UNA SOLA VEZ en phpMyAdmin
--  Cómo: phpMyAdmin > pestaña "SQL" > pegar todo esto > Ejecutar
-- ============================================================

-- Crear la base de datos si todavía no existe
CREATE DATABASE IF NOT EXISTS camaras_db
    CHARACTER SET utf8mb4          -- soporta acentos, ñ y emojis
    COLLATE utf8mb4_unicode_ci;

-- Decirle a MySQL que use esa base de datos para todo lo que sigue
USE camaras_db;

-- ── Tabla principal: guarda CADA lectura de temperatura ──────────────────────
-- Cada vez que el ESP32 envía un dato, se agrega UNA FILA a esta tabla.
-- Con 6 cámaras enviando cada 10 minutos, son ~864 filas por día.

CREATE TABLE IF NOT EXISTS Datos (

    -- id: número único generado automáticamente (1, 2, 3, 4...)
    id        INT AUTO_INCREMENT PRIMARY KEY,

    -- Camara: qué cámara envió la lectura (1 al 6)
    -- TINYINT alcanza hasta 127, suficiente para 6 cámaras
    Camara    TINYINT        NOT NULL,

    -- Temp: temperatura con 1 decimal. Ej: -2.5, 0.0, 3.7, 10.2
    -- DECIMAL(4,1) significa: hasta 4 dígitos en total, 1 después del punto
    -- Rango posible: -999.9 a 999.9 (más que suficiente para una cámara)
    -- Usamos DECIMAL y no INT para no perder el ",5" de "3,5°C"
    Temp      DECIMAL(4,1)   NOT NULL,

    -- FechaHora: cuándo se registró la lectura
    -- DEFAULT CURRENT_TIMESTAMP: si el ESP32 no manda la fecha, se usa la hora
    -- actual del servidor XAMPP automáticamente
    FechaHora DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- ── Índices: como un índice de un libro, aceleran las búsquedas ──
    -- Sin índice, MySQL lee TODA la tabla para encontrar algo.
    -- Con índice, va directo al lugar correcto. Muy importante cuando hay
    -- miles de filas.

    INDEX idx_camara       (Camara),               -- buscar "solo cámara 3"
    INDEX idx_fechahora    (FechaHora),             -- ordenar por fecha/hora
    INDEX idx_camara_fecha (Camara, FechaHora)      -- filtrar cámara + ordenar: más eficiente

);

-- ── Verificación ─────────────────────────────────────────────────────────────
-- Después de ejecutar esto, podés verificar con:
--   DESCRIBE Datos;
-- Deberías ver las 4 columnas: id, Camara, Temp, FechaHora
