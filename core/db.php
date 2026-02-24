<?php
// core/db.php

class Database {
    // Укажите ваши реальные данные для подключения к БД
    private $host = "localhost;port=3306";
    private $db_name = "eddakz12_ss";
    private $username = "eddakz12_ss"; 
    private $password = "1zqa2xws@@";     
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Используем utf8mb4 для поддержки всех символов, включая эмодзи
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Настраиваем PDO на выброс исключений при ошибках
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // По умолчанию возвращаем данные в виде ассоциативного массива
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $exception) {
            // Если БД недоступна, сразу отдаем понятный JSON ответ
            http_response_code(500);
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode([
                "success" => false, 
                "message" => "Ошибка подключения к базе данных: " . $exception->getMessage()
            ]);
            exit;
        }

        return $this->conn;
    }
}
?>