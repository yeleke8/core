<?php
// api/auth.php

// Подключаем наши базовые файлы
require_once '../core/db.php';
require_once '../core/response.php';

// Инициализируем подключение
$database = new Database();
$db = $database->getConnection();

// Получаем действие из URL, например: api/auth.php?action=login
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Получаем тело запроса (Retrofit отправляет данные в формате JSON)
$data = json_decode(file_get_contents("php://input"));

switch ($action) {
    case 'register':
        // Проверяем, что переданы все необходимые данные
        if (empty($data->phone) || empty($data->password) || empty($data->name)) {
            sendJsonResponse(400, false, "Заполните все обязательные поля: phone, password, name.");
        }

        $phone = htmlspecialchars(strip_tags($data->phone));
        $name = htmlspecialchars(strip_tags($data->name));
        $password = $data->password;

        // Проверяем, существует ли уже пользователь с таким телефоном
        $check_query = "SELECT user_id FROM users WHERE phone = :phone LIMIT 1";
        $stmt = $db->prepare($check_query);
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            sendJsonResponse(409, false, "Пользователь с таким номером телефона уже зарегистрирован.");
        }

        // Хэшируем пароль для безопасности
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // По умолчанию делаем тип пользователя 'user'
        $user_type = 'user';

        // Генерируем уникальный API токен
        $api_token = bin2hex(random_bytes(32));

        // Добавляем пользователя
        $insert_query = "INSERT INTO users (phone, password, name, user_type, api_token, created_at, updated_at) 
                         VALUES (:phone, :password, :name, :user_type, :api_token, NOW(), NOW())";
        $stmt = $db->prepare($insert_query);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':user_type', $user_type);
        $stmt->bindParam(':api_token', $api_token);

        if ($stmt->execute()) {
            $user_id = $db->lastInsertId();
            sendJsonResponse(201, true, "Регистрация прошла успешно.", [
                "user_id" => $user_id,
                "api_token" => $api_token
            ]);
        } else {
            sendJsonResponse(500, false, "Ошибка при регистрации пользователя.");
        }
        break;

    case 'login':
        // Проверка входных данных
        if (empty($data->phone) || empty($data->password)) {
            sendJsonResponse(400, false, "Укажите телефон и пароль.");
        }

        $phone = htmlspecialchars(strip_tags($data->phone));
        $password = $data->password;

        // Ищем пользователя по телефону
        $query = "SELECT * FROM users WHERE phone = :phone LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();

            // Проверяем совпадение пароля
            if (password_verify($password, $user['password'])) {
                
                // Генерируем новый токен при каждом входе для безопасности
                $api_token = bin2hex(random_bytes(32));

                // Обновляем время последнего входа и токен
                $update_login = "UPDATE users SET login_time = NOW(), api_token = :api_token WHERE user_id = :user_id";
                $update_stmt = $db->prepare($update_login);
                $update_stmt->bindParam(':api_token', $api_token);
                $update_stmt->bindParam(':user_id', $user['user_id']);
                $update_stmt->execute();

                // Убираем хэш пароля из ответа для безопасности
                unset($user['password']);
                // Добавляем актуальный токен в массив пользователя для ответа
                $user['api_token'] = $api_token;

                // Отправляем успешный ответ с данными пользователя
                sendJsonResponse(200, true, "Успешный вход.", [
                    "user" => $user
                ]);
            } else {
                sendJsonResponse(401, false, "Неверный пароль.");
            }
        } else {
            sendJsonResponse(404, false, "Пользователь с таким номером не найден.");
        }
        break;

    default:
        sendJsonResponse(400, false, "Неизвестное действие (action). Используйте ?action=register или ?action=login");
        break;
}
?>