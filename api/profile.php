<?php
// api/profile.php

require_once '../core/db.php';
require_once '../core/response.php';

$database = new Database();
$db = $database->getConnection();

// --- ПРОВЕРКА АВТОРИЗАЦИИ ---
$headers = apache_request_headers();
$api_token = '';

if (isset($headers['Authorization'])) {
    $api_token = str_replace('Bearer ', '', $headers['Authorization']);
} elseif (isset($_GET['token'])) {
    $api_token = $_GET['token'];
}

if (empty($api_token)) {
    sendJsonResponse(401, false, "Необходима авторизация. Передайте api_token.");
}

// Ищем пользователя по токену и получаем все его базовые данные
$auth_query = "SELECT user_id, phone, name, avatar, user_type, created_at FROM users WHERE api_token = :token LIMIT 1";
$auth_stmt = $db->prepare($auth_query);
$auth_stmt->execute([':token' => $api_token]);

if ($auth_stmt->rowCount() == 0) {
    sendJsonResponse(401, false, "Неверный или устаревший токен. Пожалуйста, авторизуйтесь заново.");
}

$current_user = $auth_stmt->fetch();
$current_user_id = $current_user['user_id'];
// ------------------------------

$action = isset($_GET['action']) ? $_GET['action'] : 'get';
$data = json_decode(file_get_contents("php://input"));

switch ($action) {
    case 'get':
        // Возвращаем данные профиля текущего пользователя
        sendJsonResponse(200, true, "Данные профиля", ["user" => $current_user]);
        break;

    case 'update':
        // Обновление профиля (пока разрешим менять только имя)
        $new_name = isset($data->name) ? htmlspecialchars(strip_tags($data->name)) : $current_user['name'];
        
        // Опционально: если в будущем вы добавите передачу ссылки на аватарку
        $new_avatar = isset($data->avatar) ? htmlspecialchars(strip_tags($data->avatar)) : $current_user['avatar'];

        if (empty($new_name)) {
            sendJsonResponse(400, false, "Имя не может быть пустым.");
        }

        $update_query = "UPDATE users SET name = :name, avatar = :avatar, updated_at = NOW() WHERE user_id = :user_id";
        $stmt = $db->prepare($update_query);
        
        if ($stmt->execute([
            ':name' => $new_name,
            ':avatar' => $new_avatar,
            ':user_id' => $current_user_id
        ])) {
            // Обновляем данные в текущем массиве для ответа
            $current_user['name'] = $new_name;
            $current_user['avatar'] = $new_avatar;
            
            sendJsonResponse(200, true, "Профиль успешно обновлен.", ["user" => $current_user]);
        } else {
            sendJsonResponse(500, false, "Ошибка при обновлении профиля.");
        }
        break;

    default:
        sendJsonResponse(400, false, "Неизвестное действие. Используйте ?action=get или ?action=update");
        break;
}
?>