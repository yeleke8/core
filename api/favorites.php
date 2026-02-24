<?php
// api/favorites.php

require_once '../core/db.php';
require_once '../core/response.php';

$database = new Database();
$db = $database->getConnection();

// --- 1. ПРОВЕРКА АВТОРИЗАЦИИ (Ищем токен) ---
$headers = apache_request_headers();
$api_token = '';

// Ищем токен в заголовке Authorization (стандарт для REST API)
// Формат: "Authorization: Bearer ВАШ_ТОКЕН"
if (isset($headers['Authorization'])) {
    $api_token = str_replace('Bearer ', '', $headers['Authorization']);
} elseif (isset($_GET['token'])) {
    // Резервный вариант, если передали через URL
    $api_token = $_GET['token'];
}

if (empty($api_token)) {
    sendJsonResponse(401, false, "Необходима авторизация. Передайте api_token.");
}

// Ищем пользователя по токену
$auth_query = "SELECT user_id FROM users WHERE api_token = :token LIMIT 1";
$auth_stmt = $db->prepare($auth_query);
$auth_stmt->bindParam(':token', $api_token);
$auth_stmt->execute();

if ($auth_stmt->rowCount() == 0) {
    sendJsonResponse(401, false, "Неверный или устаревший токен. Пожалуйста, авторизуйтесь заново.");
}

// Получаем ID текущего авторизованного пользователя
$current_user_id = $auth_stmt->fetch()['user_id'];
// ------------------------------------------

$action = isset($_GET['action']) ? $_GET['action'] : '';
$data = json_decode(file_get_contents("php://input"));

switch ($action) {
    case 'toggle':
        // Добавление или удаление из избранного
        $post_id = isset($data->post_id) ? (int)$data->post_id : 0;
        
        if (!$post_id) {
            sendJsonResponse(400, false, "Не указан post_id");
        }

        // Проверяем, есть ли уже этот пост в закладках пользователя
        $check_query = "SELECT * FROM s_favorites WHERE user_id = :user_id AND post_id = :post_id LIMIT 1";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([':user_id' => $current_user_id, ':post_id' => $post_id]);

        if ($check_stmt->rowCount() > 0) {
            // Если есть - удаляем (снимаем лайк)
            $delete_query = "DELETE FROM s_favorites WHERE user_id = :user_id AND post_id = :post_id";
            $db->prepare($delete_query)->execute([':user_id' => $current_user_id, ':post_id' => $post_id]);
            
            sendJsonResponse(200, true, "Удалено из избранного", ["is_favorite" => false]);
        } else {
            // Если нет - добавляем
            $insert_query = "INSERT INTO s_favorites (user_id, post_id) VALUES (:user_id, :post_id)";
            $db->prepare($insert_query)->execute([':user_id' => $current_user_id, ':post_id' => $post_id]);
            
            sendJsonResponse(201, true, "Добавлено в избранное", ["is_favorite" => true]);
        }
        break;

    case 'my_favorites':
        // Получение списка всех закладок текущего пользователя
        $query = "SELECT p.* FROM post p 
                  JOIN s_favorites sf ON p.post_id = sf.post_id 
                  WHERE p.status = 1 AND sf.user_id = :user_id 
                  ORDER BY sf.post_id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $current_user_id]);
        
        $favorites = $stmt->fetchAll();
        
        // Декодируем attributes для Android
        foreach ($favorites as &$post) {
            if (!empty($post['attributes'])) {
                $post['attributes'] = json_decode($post['attributes'], true);
            }
        }
        
        sendJsonResponse(200, true, "Мои закладки", ["posts" => $favorites]);
        break;

    default:
        sendJsonResponse(400, false, "Неизвестное действие. Используйте ?action=toggle или my_favorites");
        break;
}
?>