<?php
// ══════════════════════════════════════════════════════════════════════════════
// api/get_stats_camaras.php — Estadísticas y temperatura actual por cámara
// ══════════════════════════════════════════════════════════════════════════════
//
// Método HTTP: GET
// URL:         http://localhost/camaras/api/get_stats_camaras.php
//
// Usado por: Temperaturas.html (grid de cámaras) y Historial.html (cards resumen)
//
// Respuesta — un objeto por cada cámara que tiene al menos una lectura:
// [
//   {
//     "Camara": 1,
//     "actual": 3.5,                       ← temperatura de la ÚLTIMA lectura
//     "max": 8.2,                           ← temperatura más alta registrada
//     "min": -0.5,                          ← temperatura más baja registrada
//     "avg": 2.8,                           ← promedio de todas las lecturas
//     "lecturas": 240,                      ← cuántas lecturas tiene esta cámara
//     "ultima_lectura": "2024-06-01 14:30:00"  ← fecha/hora de la última lectura
//   },
//   { "Camara": 2, ... },
//   ...
// ]
//
// Si la base de datos está vacía, devuelve [] (array vacío).

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['error' => 'Este endpoint solo acepta peticiones GET'], 405);
}

$pdo = get_conn();


// ── Consulta principal ────────────────────────────────────────────────────────
// Esta consulta hace dos cosas a la vez:
//   1. GROUP BY Camara: agrupa todas las filas de cada cámara
//   2. Subconsultas correlacionadas: para cada grupo (cámara), busca la temp y fecha
//      de la lectura más reciente
//
// ¿Qué es una subconsulta correlacionada?
// Es un SELECT dentro de otro SELECT que referencia la fila externa (d.Camara).
// La base de datos ejecuta la subconsulta UNA VEZ por cada cámara encontrada.
// Ejemplo: cuando procesa la fila de Camara=1, la subconsulta busca en Datos
// donde sub.Camara = 1, ordena por fecha DESC y toma solo el primer resultado.

$rows = $pdo->query("
    SELECT
        d.Camara,

        /* ── Temperatura de la lectura más reciente ─────────────────────────
           Subconsulta: busca en la misma tabla (alias 'sub') la temperatura
           de la lectura más reciente para ESTA cámara específica */
        (
            SELECT  sub.Temp
            FROM    Datos sub
            WHERE   sub.Camara = d.Camara      -- 'ata' la subconsulta a la cámara actual
            ORDER   BY sub.FechaHora DESC      -- la más reciente primero
            LIMIT   1                          -- solo una fila
        ) AS actual,

        /* ── Fecha/hora de esa lectura más reciente ──────────────────────── */
        (
            SELECT  sub.FechaHora
            FROM    Datos sub
            WHERE   sub.Camara = d.Camara
            ORDER   BY sub.FechaHora DESC
            LIMIT   1
        ) AS ultima_lectura,

        /* ── Estadísticas históricas (de TODAS las lecturas de esta cámara) ─ */
        MAX(d.Temp)           AS max,          -- temperatura máxima histórica
        MIN(d.Temp)           AS min,          -- temperatura mínima histórica
        ROUND(AVG(d.Temp), 1) AS avg,          -- promedio con 1 decimal
        COUNT(*)              AS lecturas       -- total de registros

    FROM   Datos d
    GROUP  BY d.Camara     -- una fila de resultado por cada cámara diferente
    ORDER  BY d.Camara     -- ordenar cámara 1, 2, 3...
")->fetchAll();


// ── Formatear tipos para JSON limpio ─────────────────────────────────────────
// PHP a veces devuelve números como strings cuando vienen de la BD.
// Hacemos cast explícito para que el JSON tenga: "actual": 3.5 (no "actual": "3.5")
$resultado = array_map(fn($r) => [
    'Camara'         => (int)   $r['Camara'],
    'actual'         => (float) $r['actual'],
    'max'            => (float) $r['max'],
    'min'            => (float) $r['min'],
    'avg'            => (float) $r['avg'],
    'lecturas'       => (int)   $r['lecturas'],
    'ultima_lectura' => $r['ultima_lectura'],   // string: "2024-06-01 14:30:00"
], $rows);

respond($resultado);
