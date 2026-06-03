<?php
// ══════════════════════════════════════════════════════════════════════════════
// config/helpers.php — Funciones compartidas por todos los endpoints de la API
// ══════════════════════════════════════════════════════════════════════════════
//
// Este archivo hace tres cosas automáticamente al ser incluido con require_once:
//   1. Configura los headers HTTP (tipo de contenido + CORS)
//   2. Responde las peticiones OPTIONS (preflight CORS) y termina
//   3. Define las funciones get_json_body() y respond() para usarlas en la API
//
// ── ¿Qué es CORS y por qué lo necesitamos? ───────────────────────────────────
// CORS (Cross-Origin Resource Sharing) es un mecanismo de seguridad del navegador.
// Cuando un HTML abierto en http://localhost/camaras/Temperaturas.html hace un
// fetch() a http://localhost/camaras/api/get_stats.php, el navegador considera
// que son "orígenes iguales" (same origin) y lo permite sin problema.
//
// Sin embargo, si el HTML estuviera en otro servidor o puerto, el navegador
// bloquearía la petición. Con Access-Control-Allow-Origin: * le decimos al
// navegador que cualquier origen puede leer la respuesta de nuestra API.


// ── 1. Headers de respuesta ───────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');  // Siempre respondemos JSON
header('Access-Control-Allow-Origin: *');                  // Permite CORS desde cualquier origen
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');


// ── 2. Responder preflight OPTIONS ───────────────────────────────────────────
// Antes de hacer un POST con JSON, los navegadores modernos envían primero
// una petición OPTIONS preguntando "¿tengo permiso para hacer esto?".
// Respondemos 204 (éxito sin contenido) y terminamos.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


// ── 3. Funciones helper ───────────────────────────────────────────────────────

/**
 * get_json_body()
 *
 * Lee el cuerpo de la petición HTTP, lo interpreta como JSON y lo devuelve
 * como array asociativo de PHP.
 *
 * Ejemplo:
 *   El ESP32 envía el body:  {"Camara": 1, "Temp": 3.5}
 *   Esta función devuelve:   ['Camara' => 1, 'Temp' => 3.5]
 *
 * Si el JSON está mal formado (sintaxis incorrecta), responde error 400 y termina.
 */
function get_json_body(): array {
    // php://input es el stream de PHP para leer el body crudo de la petición HTTP
    $raw  = file_get_contents('php://input');

    // json_decode(..., true) convierte el JSON a array de PHP (true = array, false = objeto)
    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        // El JSON era inválido (llaves sin cerrar, comas de más, etc.)
        http_response_code(400);
        echo json_encode(['error' => 'El cuerpo de la petición no es JSON válido: ' . json_last_error_msg()]);
        exit;
    }

    // Si el body estaba vacío, $data es null; devolvemos array vacío para evitar errores
    return $data ?? [];
}


/**
 * respond()
 *
 * Convierte $data a JSON con el código HTTP indicado, lo imprime y termina el script.
 * Es el equivalente de "return JSONResponse(data, status_code)" en FastAPI.
 *
 * @param mixed $data  Cualquier valor PHP (array, int, string, null...)
 * @param int   $code  Código HTTP: 200 OK, 201 Created, 404 Not Found, 422 Unprocessable...
 */
function respond(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |  // No escapa ñ/acentos como \u00f1, los deja tal cual
        JSON_PRETTY_PRINT         // Indenta el JSON para que sea legible en el navegador
    );
    exit;
}
