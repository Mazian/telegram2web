<?php
// Токен бота - обязательно для указания
define('BOT_TOKEN', 'tokenID');
// Адрес до токен-сервера телеграм
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');
// userinfo канала источника (если не указан - с любых каналов где добавлен бот)
define('CHANNEL_NAME', '');
// Адрес до файлов на токен-сервер телеграм
define('API_FILE_URL', 'https://api.telegram.org/file/bot'.BOT_TOKEN.'/');
define('WEBHOOK_URL', '');
// Имя бота
$BOT_NAME = 'GetBot';

// Адрес и название папки куда сохранять файлы
$img_patch   = 'images';
$img_preview = 'preview';

// Подключаемся к БД
$db_hostname = 'localhost'; 
$db_username = 'userDB'; // Логин
$db_password = 'passDB'; // Пароль
$db_database = 'nameDB'; // Имя БД

$utable = 'channel_log'; // Имя Таблицы


// Блокировка файла (для выполнения быстрого cron-вызова скрипта без дубликатов)
// Желательно использовать только для режима без сертификата HTTPS
$lockFile = __FILE__.'.lock'; // Имя файла
$hasFile = file_exists($lockFile); // Проверка существования
$lockFp = fopen($lockFile, 'w'); // Открываем для ограничения запуска


/*
// формат таблицы в MySQL
CREATE TABLE `channel_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `img` varchar(50) NOT NULL,
  `channel` varchar(20) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/

?>