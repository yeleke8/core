-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Фев 24 2026 г., 20:21
-- Версия сервера: 10.4.26-MariaDB
-- Версия PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `zhaugashty`
--

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `cat_id` int(11) UNSIGNED NOT NULL,
  `cat_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cat_icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Путь к иконке или изображению категории',
  `cat_slug` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
  `cat_parent_id` int(11) UNSIGNED DEFAULT NULL,
  `cat_sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`cat_id`, `cat_name`, `cat_icon`, `cat_slug`, `cat_parent_id`, `cat_sort_order`) VALUES
(1, 'Автосервисы (СТО)', NULL, 'auto-service', NULL, 0),
(2, 'Магазин запчастей', NULL, 'auto-shops', NULL, 0),
(3, 'Мойки и Детейлинг', NULL, 'car-wash-detailing', NULL, 0),
(4, 'Шиномонтаж', NULL, 'tires-wheels', NULL, 0),
(5, 'Автоуслуги', NULL, 'auto-services-other', NULL, 0),
(6, 'Замены масла', NULL, 'oil-change', 1, 0),
(7, 'Автоэлектрика и диагностика', NULL, 'auto-electrician', 1, 0),
(8, 'Ремонт ходовой части', NULL, 'chassis-repair', 1, 0),
(9, 'Кузовной ремонт и покраска', NULL, 'body-repair', 1, 0),
(10, 'Ремонт двигателей и КПП', NULL, 'engine-repair', 1, 0),
(11, 'Установка ГБО', NULL, 'gbo-install', 1, 0),
(12, 'Развал-схождение (Геометрия)', NULL, 'wheel-alignment', 1, 0),
(13, 'Автозапчасти', NULL, 'auto-parts', 2, 0),
(14, 'Автомасла и химия', NULL, 'auto-oil-chemistry', 2, 0),
(15, 'Аккумуляторы', NULL, 'car-batteries', 2, 0),
(16, 'Автозвук и аксессуары', NULL, 'car-audio', 2, 0),
(17, 'Автомойки', NULL, 'car-wash', 3, 0),
(18, 'Детейлинг и полировка', NULL, 'detailing', 3, 0),
(19, 'Химчистка салона', NULL, 'dry-cleaning-car', 3, 0),
(20, 'Услуги шиномонтажа', NULL, 'tire-fitting', 4, 0),
(21, 'Продажа шин и дисков', NULL, 'tires-sale', 2, 0),
(22, 'Прокатка и ремонт дисков', NULL, 'disk-repair', 4, 0),
(23, 'Эвакуаторы', NULL, 'tow-truck', 5, 0),
(24, 'Техосмотр', NULL, 'vehicle-inspection', 5, 0),
(25, 'Автострахование', NULL, 'car-insurance', 5, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 5,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 - на модерации, 1 - одобрен',
  `created_at` datetime DEFAULT current_timestamp(),
  `owner_reply` text COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ответ представителя заведения',
  `reply_created_at` datetime DEFAULT NULL COMMENT 'Дата ответа'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Триггеры `comments`
--
DELIMITER $$
CREATE TRIGGER `recalc_rating_delete` AFTER DELETE ON `comments` FOR EACH ROW BEGIN
    UPDATE `post`
    SET
        rating_count = (SELECT COUNT(*) FROM `comments` WHERE post_id = OLD.post_id AND is_approved = 1),
        rating_avg = (SELECT COALESCE(AVG(rating), 0) FROM `comments` WHERE post_id = OLD.post_id AND is_approved = 1)
    WHERE post_id = OLD.post_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `recalc_rating_insert` AFTER INSERT ON `comments` FOR EACH ROW BEGIN
    UPDATE `post`
    SET
        rating_count = (SELECT COUNT(*) FROM `comments` WHERE post_id = NEW.post_id AND is_approved = 1),
        rating_avg = (SELECT COALESCE(AVG(rating), 0) FROM `comments` WHERE post_id = NEW.post_id AND is_approved = 1)
    WHERE post_id = NEW.post_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `recalc_rating_update` AFTER UPDATE ON `comments` FOR EACH ROW BEGIN
    UPDATE `post`
    SET
        rating_count = (SELECT COUNT(*) FROM `comments` WHERE post_id = NEW.post_id AND is_approved = 1),
        rating_avg = (SELECT COALESCE(AVG(rating), 0) FROM `comments` WHERE post_id = NEW.post_id AND is_approved = 1)
    WHERE post_id = NEW.post_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `post`
--

CREATE TABLE `post` (
  `post_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'Название',
  `psevdonim` varchar(500) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL COMMENT 'Описание',
  `address` varchar(500) NOT NULL COMMENT 'Адрес',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `photo` varchar(255) DEFAULT 'uploads/default.jpg' COMMENT 'главное фото',
  `views` int(11) DEFAULT 0 COMMENT 'Количество просмотров',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'Создан',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Изменен',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 - модерация/черновик, 1 - опубликовано, 2 - удален',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 - проверено (галочка), 0 - нет',
  `rating_avg` decimal(3,2) DEFAULT 0.00 COMMENT 'Рейтинг',
  `rating_count` int(11) DEFAULT 0 COMMENT 'Количество оценок',
  `owner_id` int(11) DEFAULT 2 COMMENT 'ID владельца (users.user_id)',
  `attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Специфичные характеристики (JSON)' CHECK (json_valid(`attributes`)),
  `phone` varchar(255) NOT NULL,
  `whatsapp` varchar(255) NOT NULL,
  `instagram` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `post_working_hours`
--

CREATE TABLE `post_working_hours` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '1=Понедельник, 2=Вторник... 7=Воскресенье',
  `open_time` time DEFAULT NULL COMMENT 'Время открытия (напр. 09:00:00)',
  `close_time` time DEFAULT NULL COMMENT 'Время закрытия (напр. 18:00:00)',
  `is_day_off` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 - выходной, время игнорируется',
  `is_24_7` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 - круглосуточно, время игнорируется'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `s_categories`
--

CREATE TABLE `s_categories` (
  `post_id` int(11) NOT NULL,
  `cat_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `s_favorites`
--

CREATE TABLE `s_favorites` (
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `api_token` varchar(64) DEFAULT NULL,
  `user_type` enum('admin','user','owner') NOT NULL DEFAULT 'user' COMMENT 'Тип пользователя',
  `user_name` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT 'uploads/avatars/default.png' COMMENT 'Аватар профиля',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'Дата регистраций',
  `last_online` datetime DEFAULT current_timestamp() COMMENT 'В сети',
  `google_id` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `policy_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`user_id`, `phone`, `email`, `password`, `api_token`, `user_type`, `user_name`, `avatar`, `created_at`, `last_online`, `google_id`, `api_key`, `refresh_token`, `policy_accepted`, `status`, `updated_at`, `reset_token`) VALUES
(1, '+77011111111', NULL, '$2y$10$7YPtXgOd.MvRPiUZXEQJoOFjzwMnswHOns40R68vSAxoEJrofQwwy', NULL, 'admin', 'Админ', 'https://images.icon-icons.com/1378/PNG/512/avatardefault_92824.png', '2026-02-06 10:32:26', '2026-02-06 10:32:26', NULL, '', NULL, 0, 0, '0000-00-00 00:00:00', ''),
(2, '+77012222222', NULL, '$2y$10$7YPtXgOd.MvRPiUZXEQJoOFjzwMnswHOns40R68vSAxoEJrofQwwy', NULL, 'owner', 'Бизнесмен', 'https://images.icon-icons.com/1378/PNG/512/avatardefault_92824.png', '2026-02-11 10:20:18', '2026-02-14 19:15:26', NULL, '', NULL, 0, 0, '0000-00-00 00:00:00', ''),
(3, '+77013333333', NULL, '$2y$10$7YPtXgOd.MvRPiUZXEQJoOFjzwMnswHOns40R68vSAxoEJrofQwwy', NULL, 'user', 'Пользователь', 'https://images.icon-icons.com/1378/PNG/512/avatardefault_92824.png', '2026-02-11 10:20:18', '2026-02-14 19:01:42', NULL, '', NULL, 0, 0, '0000-00-00 00:00:00', '');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`cat_id`),
  ADD UNIQUE KEY `idx_cat_slug` (`cat_slug`),
  ADD KEY `idx_categories_parent_id` (`cat_parent_id`);

--
-- Индексы таблицы `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD UNIQUE KEY `unique_user_review` (`user_id`,`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_post_id` (`post_id`);

--
-- Индексы таблицы `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`post_id`),
  ADD UNIQUE KEY `idx_post_slug` (`slug`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `idx_post_rating` (`rating_avg`),
  ADD KEY `idx_post_views` (`views`),
  ADD KEY `idx_post_status` (`status`),
  ADD KEY `idx_status_rating` (`status`,`rating_avg`),
  ADD KEY `idx_status_views` (`status`,`views`);
ALTER TABLE `post` ADD FULLTEXT KEY `title` (`title`,`psevdonim`);

--
-- Индексы таблицы `post_working_hours`
--
ALTER TABLE `post_working_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_search_open` (`day_of_week`,`open_time`,`close_time`);

--
-- Индексы таблицы `s_categories`
--
ALTER TABLE `s_categories`
  ADD PRIMARY KEY (`post_id`,`cat_id`),
  ADD KEY `cat_id` (`cat_id`);

--
-- Индексы таблицы `s_favorites`
--
ALTER TABLE `s_favorites`
  ADD PRIMARY KEY (`user_id`,`post_id`),
  ADD KEY `idx_post_id` (`post_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `idx_google_id` (`google_id`),
  ADD UNIQUE KEY `idx_phone` (`phone`),
  ADD UNIQUE KEY `idx_email` (`email`),
  ADD UNIQUE KEY `api_token` (`api_token`),
  ADD KEY `idx_api_key` (`api_key`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `cat_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT для таблицы `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `post`
--
ALTER TABLE `post`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `post_working_hours`
--
ALTER TABLE `post_working_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_parent` FOREIGN KEY (`cat_parent_id`) REFERENCES `categories` (`cat_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `post_owner_fk` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `post_working_hours`
--
ALTER TABLE `post_working_hours`
  ADD CONSTRAINT `fk_work_post` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `s_categories`
--
ALTER TABLE `s_categories`
  ADD CONSTRAINT `s_categories_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `s_categories_ibfk_2` FOREIGN KEY (`cat_id`) REFERENCES `categories` (`cat_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `s_favorites`
--
ALTER TABLE `s_favorites`
  ADD CONSTRAINT `s_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `s_favorites_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
