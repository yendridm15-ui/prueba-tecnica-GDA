<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vida del token
    |--------------------------------------------------------------------------
    |
    | Minutos que dura el token después del login. Los vencidos los rechaza
    | el middleware de autenticación y se limpian en el próximo login.
    |
    */

    'token_ttl_minutes' => (int) env('API_TOKEN_TTL_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Logs de respuesta
    |--------------------------------------------------------------------------
    |
    | Define si se guardan los logs de salida en el canal "api". Los de
    | entrada se guardan siempre. En producción los de salida se apagan
    | solos, sin importar este valor.
    |
    */

    'log_responses' => (bool) env('API_LOG_RESPONSES', true),

];
