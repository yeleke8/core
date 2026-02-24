<?php
// core/response.php

/**
 * Функция для отправки стандартизированного JSON ответа.
 * Это очень поможет при парсинге в Retrofit на стороне Android.
 *
 * @param int $statusCode HTTP статус код (200, 400, 401, 404, 500 и т.д.)
 * @param bool $success Успешен ли запрос (true/false)
 * @param string $message Текстовое сообщение для пользователя или логов
 * @param mixed $data Дополнительные данные (массив, объект), по умолчанию null
 */
function sendJsonResponse($statusCode, $success, $message, $data = null) {
    // Устанавливаем HTTP код ответа
    http_response_code($statusCode);
    
    // Заголовки для CORS (полезно, если будете тестировать с веб-клиента) 
    // и указание типа контента
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    
    // Если это OPTIONS запрос (Preflight), просто завершаем выполнение
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }

    // Формируем структуру ответа
    $response = [
        "success" => $success,
        "message" => $message
    ];
    
    // Если есть данные (например, список постов или данные юзера), добавляем их
    if ($data !== null) {
        $response["data"] = $data;
    }
    
    // Выводим JSON, сохраняя кириллицу неэкранированной
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit; // Останавливаем выполнение скрипта
}
?>