<?php
// api/search.php

require_once '../core/db.php';
require_once '../core/response.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : 'search'; // По умолчанию действие - search

switch ($action) {
    case 'search':
        // Получаем поисковой запрос
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        
        // Очищаем от лишних тегов для безопасности
        $q = htmlspecialchars(strip_tags($q));

        if (empty($q)) {
            sendJsonResponse(400, false, "Введите поисковой запрос (параметр ?q=...)");
        }

        // Параметры для пагинации
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // Подготавливаем строку для SQL оператора LIKE
        $search_term = "%{$q}%";

        // Ищем по названию (title) или псевдониму (psevdonim), только среди активных постов
        $query = "SELECT * FROM post 
                  WHERE status = 1 AND (title LIKE :search OR psevdonim LIKE :search)
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";
                  
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        $posts = $stmt->fetchAll();
        
        // Декодируем поле attributes (JSON) в массив для удобства Android-разработчика
        foreach ($posts as &$post) {
            if (!empty($post['attributes'])) {
                $post['attributes'] = json_decode($post['attributes'], true);
            }
        }
        
        $message = count($posts) > 0 ? "Результаты поиска" : "По вашему запросу ничего не найдено";

        sendJsonResponse(200, true, $message, [
            "query" => $q,
            "posts" => $posts,
            "page" => $page,
            "limit" => $limit
        ]);
        break;

    default:
        sendJsonResponse(400, false, "Неизвестное действие. Используйте ?action=search&q={запрос}");
        break;
}
?>