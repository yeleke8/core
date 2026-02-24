<?php
// api/posts.php

require_once '../core/db.php';
require_once '../core/response.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'feed':
        // Получаем параметры для пагинации
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : null;
        
        $offset = ($page - 1) * $limit;
        
        // Если передана категория, фильтруем через связующую таблицу s_categories
        if ($cat_id) {
            $query = "SELECT p.* FROM post p
                      JOIN s_categories sc ON p.post_id = sc.post_id
                      WHERE p.status = 1 AND sc.cat_id = :cat_id
                      ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':cat_id', $cat_id, PDO::PARAM_INT);
        } else {
            // Обычная лента всех активных постов
            $query = "SELECT * FROM post WHERE status = 1 ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $db->prepare($query);
        }
        
        // PDO требует явного указания PDO::PARAM_INT для LIMIT и OFFSET
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll();
        
        // Для удобства Android-разработчика можно парсить JSON поле attributes сразу в объект
        foreach ($posts as &$post) {
            if (!empty($post['attributes'])) {
                $post['attributes'] = json_decode($post['attributes'], true);
            }
        }
        
        sendJsonResponse(200, true, "Лента заведений", [
            "posts" => $posts, 
            "page" => $page,
            "limit" => $limit
        ]);
        break;

    case 'popular':
        // Вывод популярных заведений (например, для горизонтального скролла на главном экране)
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        
        $query = "SELECT * FROM post WHERE status = 1 ORDER BY views DESC LIMIT :limit";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $posts = $stmt->fetchAll();
        
        foreach ($posts as &$post) {
            if (!empty($post['attributes'])) {
                $post['attributes'] = json_decode($post['attributes'], true);
            }
        }
        
        sendJsonResponse(200, true, "Популярные заведения", ["posts" => $posts]);
        break;

    case 'detail':
        // Детальная карточка заведения
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            sendJsonResponse(400, false, "Не указан ID заведения (?id=...)");
        }

        // 1. Получаем основную информацию
        $query = "SELECT * FROM post WHERE post_id = :id AND status = 1 LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendJsonResponse(404, false, "Заведение не найдено или скрыто");
        }
        
        $post = $stmt->fetch();
        if (!empty($post['attributes'])) {
            $post['attributes'] = json_decode($post['attributes'], true);
        }

        // 2. Получаем расписание работы
        $hours_query = "SELECT day_of_week, open_time, close_time, is_closed FROM post_working_hours WHERE post_id = :id";
        $hours_stmt = $db->prepare($hours_query);
        $hours_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $hours_stmt->execute();
        
        $post['working_hours'] = $hours_stmt->fetchAll();

        // 3. Увеличиваем счетчик просмотров (работает асинхронно для пользователя)
        $update_views = "UPDATE post SET views = views + 1 WHERE post_id = :id";
        $db->prepare($update_views)->execute([':id' => $id]);

        sendJsonResponse(200, true, "Детали заведения", ["post" => $post]);
        break;

    default:
        sendJsonResponse(400, false, "Неизвестное действие. Используйте ?action=feed, popular или detail");
        break;
}
?>