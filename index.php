<html>
<style>
.photo {
    display:inline-block;
    float:left;
    width:200px;
    border:2px solid #eee;

}

</style>
<body>

<p>last 10 pics</p>

<?php
// Ошибки
ini_set('display_errors', 0);
#error_reporting(E_ALL);
set_time_limit(0); 
//ignore_user_abort(1);   // Игнорировать обрыв связи с браузером 
// Устанавливаем временную зону Москвы
date_default_timezone_set('Europe/Moscow');
include_once 'config.php';

// работаем с БД
$db_server = mysql_connect($db_hostname, $db_username, $db_password);
if (!$db_server) die("Unable to connect to MySQL: " . mysql_error());
mysql_select_db($db_database, $db_server)
or die("Unable to select database: " . mysql_error());

// Кодировка для синхронизации
mysql_query("SET NAMES 'utf8';");
mysql_query("SET CHARACTER SET 'utf8';");
mysql_query("SET SESSION collation_connection = 'utf8_general_ci';");

// Выводим все сохраненные данные из БД
$where = "1";
if (CHANNEL_NAME) { $where = "channel = '".CHANNEL_NAME."'"; }

$dataz = mysql_query("SELECT * FROM ".$utable.' WHERE '.$where.' ORDER BY id DESC LIMIT 10');
	if (mysql_num_rows($dataz) > 0){
		while($datas = mysql_fetch_assoc($dataz)){
			echo '<a href="'.$img_patch.'/'.$datas['img'].'" class="photo" date="'.$datas['date'].'"><img alt="'.$datas['text'].'" src="'.$img_preview.'/'.$datas['img'].'"></a>';
		}
	}

?>

</body>