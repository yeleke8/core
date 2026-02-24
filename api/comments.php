<?php
// api/comments.php

require_once '../core/db.php';
require_once '../core/response.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$data = json_decode(file_get_contents("php://input"));

switch ($action) {
    case 'get_by_post':
        // Получение списка одобренных отзывов для конкретного поста
        $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
        
        if (!$post_id) {
            sendJsonResponse(400, false, "Не указан post_id");
        }

        // Получаем все одобренные комментарии к посту (и главные отзывы, и ответы)
        $query = "SELECT c.comment_id, c.rating, c.comment_text, c.created_at, c.parent_comment_id, 
                         u.name as user_name, u.avatar as user_avatar, u.user_type
                  FROM comments c
                  JOIN users u ON c.user_id = u.user_id
                  WHERE c.post_id = :post_id AND c.is_approved = 1
                  ORDER BY c.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $all_comments = $stmt->fetchAll();
        
        $top_level_comments = [];
        $replies = [];

        // Разделяем отзывы на главные и ответы
        foreach ($all_comments as $comment) {
            if ($comment['parent_comment_id'] === null) {
                // Это главный отзыв, создаем для него пустой массив ответов
                $comment['replies'] = [];
                $top_level_comments[$comment['comment_id']] = $comment;
            } else {
                // Это ответ на отзыв
                $replies[] = $comment;
            }
        }

        // Переворачиваем массив ответов, чтобы старые ответы были сверху (логично для чтения)
        $replies = array_reverse($replies);

        // Прикрепляем ответы к их родительским отзывам
        foreach ($replies as $reply) {
            $parent_id = $reply['parent_comment_id'];
            if (isset($top_level_comments[$parent_id])) {
                $top_level_comments[$parent_id]['replies'][] = $reply;
            }
        }

        // Сбрасываем ключи массива, чтобы получить корректный JSON-список
        $final_comments_list = array_values($top_level_comments);
        
        sendJsonResponse(200, true, "Отзывы", ["comments" => $final_comments_list]);
        break;

    case 'add':
        // Добавление нового отзыва или ответа (ТРЕБУЕТ АВТОРИЗАЦИИ)
        
        // --- ПРОВЕРКА АВТОРИЗАЦИИ ---
        $headers = apache_request_headers();
        $api_token = '';
        if (isset($headers['Authorization'])) {
            $api_token = str_replace('Bearer ', '', $headers['Authorization']);
        }
        
        if (empty($api_token)) {
            sendJsonResponse(401, false, "Необходима авторизация. Передайте api_token.");
        }
        
        $auth_query = "SELECT user_id, user_type FROM users WHERE api_token = :token LIMIT 1";
        $auth_stmt = $db->prepare($auth_query);
        $auth_stmt->execute([':token' => $api_token]);
        
        if ($auth_stmt->rowCount() == 0) {
            sendJsonResponse(401, false, "Неверный или устаревший токен.");
        }
        $current_user = $auth_stmt->fetch();
        $current_user_id = $current_user['user_id'];
        $current_user_type = $current_user['user_type'];
        // ------------------------------

        // Получаем данные из JSON
        $post_id = isset($data->post_id) ? (int)$data->post_id : 0;
        $comment_text = isset($data->comment_text) ? htmlspecialchars(strip_tags($data->comment_text)) : '';
        $parent_comment_id = isset($data->parent_comment_id) ? (int)$data->parent_comment_id : null;
        
        // Рейтинг обязателен только для главных отзывов, для ответов он может быть NULL
        $rating = isset($data->rating) ? (int)$data->rating : null;

        if (!$post_id || empty($comment_text)) {
            sendJsonResponse(400, false, "Не заполнены обязательные поля (post_id, comment_text)");
        }
        
        if ($parent_comment_id === null && ($rating < 1 || $rating > 5)) {
            sendJsonResponse(400, false, "Для нового отзыва рейтинг должен быть от 1 до 5");
        }

        // По умолчанию отзывы скрыты до проверки модератором
        // Но если отвечает владелец заведения (owner), можно сразу одобрять (по желанию, пока оставим 0)
        $is_approved = 0; 

        $insert_query = "INSERT INTO comments (post_id, user_id, rating, comment_text, parent_comment_id, is_approved) 
                         VALUES (:post_id, :user_id, :rating, :comment_text, :parent_comment_id, :is_approved)";
        $stmt = $db->prepare($insert_query);
        
        if ($stmt->execute([
            ':post_id' => $post_id,
            ':user_id' => $current_user_id,
            ':rating' => $parent_comment_id === null ? $rating : null, // Обнуляем рейтинг, если это просто ответ
            ':comment_text' => $comment_text,
            ':parent_comment_id' => $parent_comment_id,
            ':is_approved' => $is_approved
        ])) {
            $msg = $parent_comment_id ? "Ответ успешно отправлен на модерацию." : "Отзыв успешно отправлен и ожидает модерации.";
            sendJsonResponse(201, true, $msg);
        } else {
            sendJsonResponse(500, false, "Ошибка при сохранении комментария.");
        }
        break;

    default:
        sendJsonResponse(400, false, "Неизвестное действие. Используйте ?action=get_by_post или add");
        break;
}
?>