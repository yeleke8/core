<?php
// api/categories.php

require_once '../core/db.php';
require_once '../core/response.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_parents':
        // Получаем только главные категории (корневые)
        $query = "SELECT cat_id, cat_name, cat_icon, cat_slug FROM categories WHERE cat_parent_id IS NULL ORDER BY cat_name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $categories = $stmt->fetchAll();
        
        sendJsonResponse(200, true, "Главные категории", ["categories" => $categories]);
        break;

    case 'get_sub':
        // Получаем подкатегории для определенной родительской категории
        $parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
        
        if (!$parent_id) {
            sendJsonResponse(400, false, "Не указан ID родительской категории (parent_id)");
        }

        $query = "SELECT cat_id, cat_name, cat_icon, cat_slug FROM categories WHERE cat_parent_id = :parent_id ORDER BY cat_name ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':parent_id', $parent_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $subcategories = $stmt->fetchAll();
        
        sendJsonResponse(200, true, "Подкатегории", ["subcategories" => $subcategories]);
        break;

    case 'tree':
        // Получаем все категории сразу в виде дерева (удобно для кэширования на клиенте)
        $query = "SELECT cat_id, cat_name, cat_icon, cat_slug, cat_parent_id FROM categories ORDER BY cat_parent_id ASC, cat_name ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $all_categories = $stmt->fetchAll();
        
        // Формируем дерево
        $tree = [];
        $sub = [];
        
        foreach ($all_categories as $cat) {
            if ($cat['cat_parent_id'] === null) {
                // Это главная категория
                $cat['children'] = [];
                $tree[$cat['cat_id']] = $cat;
            } else {
                // Это подкатегория, сохраняем во временный массив
                $sub[] = $cat;
            }
        }
        
        // Привязываем подкатегории к их родителям
        foreach ($sub as $child) {
            if (isset($tree[$child['cat_parent_id']])) {
                $tree[$child['cat_parent_id']]['children'][] = $child;
            }
        }
        
        // Сбрасываем ключи массива, чтобы JSON получился списком `[]`, а не объектом `{}`
        $tree = array_values($tree);

        sendJsonResponse(200, true, "Дерево категорий", ["categories" => $tree]);
        break;

    default:
        sendJsonResponse(400, false, "Неизвестное действие. Используйте ?action=get_parents, get_sub или tree");
        break;
}
?>