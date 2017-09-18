<?php
# crontab:
# */5 * * * *     wget -O /dev/null http://DOMAIN.COM/telegram2web/bot.php >/dev/null 2>&1

// Ошибки
ini_set('display_errors', 0);
//error_reporting(E_ALL);
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

function apiRequestWebhook($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;

  header("Content-Type: application/json");
  echo json_encode($parameters);
  return true;
}

function exec_curl_request($handle) {
  $response = curl_exec($handle);

  if ($response === false) {
    $errno = curl_errno($handle);
    $error = curl_error($handle);
    error_log("Curl returned error $errno: $error\n");
    curl_close($handle);
    return false;
  }

  $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
  curl_close($handle);

  if ($http_code >= 500) {
    // do not wat to DDOS server if something goes wrong
    sleep(10);
    return false;
  } else if ($http_code != 200) {
    $response = json_decode($response, true);
    error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
    if ($http_code == 401) {
      throw new Exception('Invalid access token provided');
    }
    return false;
  } else {
    $response = json_decode($response, true);
    if (isset($response['description'])) {
      error_log("Request was successfull: {$response['description']}\n");
    }
    $response = $response['result'];
  }

  return $response;
}

function apiRequest($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  foreach ($parameters as $key => &$val) {
    // encoding to JSON array parameters, for example reply_markup
    if (!is_numeric($val) && !is_string($val)) {
      $val = json_encode($val);
    }
  }
   if(strpos($method, 'sendDocument') !== false || strpos($method, 'sendPhoto') !== false || strpos($method, 'sendVoice') !== false) $url = API_URL.$method;
   else $url = API_URL.$method.'?'.http_build_query($parameters);
   
  $handle = curl_init($url);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  if(strpos($method, 'sendDocument') !== false || strpos($method, 'sendPhoto') !== false || strpos($method, 'sendVoice') !== false) {
	  curl_setopt($handle, CURLOPT_POST, true);
	  curl_setopt($handle, CURLOPT_POSTFIELDS, $parameters);
	  curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
  }
  return exec_curl_request($handle);
}

function apiRequestJson($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;

  $handle = curl_init(API_URL);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
  curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

  return exec_curl_request($handle);
}



// Основной цикл получения -> отправки

// Основной цикл HTTPS работы бота
if(isset($_GET['setHttps']) && is_numeric($_GET['setHttps'])){
//$path_parts = pathinfo($_SERVER['SCRIPT_NAME']); // определяем директорию скрипта
//$patch_to_bot = 'https://'.$_SERVER['HTTP_HOST'].$path_parts['dirname'].'/bot.php?botnumber='.$cnt;

    // if run from setHttps, set or delete webhook
    echo 'url:'.WEBHOOK_URL.'<br>';
    echo 'Status: '.apiRequest('setWebhook', array('url' => isset($_GET['setHttps']) && $_GET['setHttps'] == 0 ? '' : WEBHOOK_URL), API_URL).'<br>';
    file_put_contents('set', $_GET['setHttps']);
    exit;
}
$fSet = file_get_contents(dirname(__FILE__)."/set");

if($fSet == 0 || empty($fSet)){
// Основной цикл получения -> постинга
// Если блокировку получить не удалось, значит старый скрипт еще работает
    if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
        die('Sorry, script is running!');
    }
    $fileTest = file_get_contents(dirname(__FILE__)."/data");
if(!$fileTest) $fileTest=0;
    $content = file_get_contents(API_URL."getUpdates?offset=".$fileTest);//
} else {
    $content = file_get_contents('php://input');
}


$update = json_decode($content, TRUE);
print_r($update);

if (!$update) {
    echo 'NO INPUT DATA!';
    exit;
}


//echo $updat['update_id'].'<br>';
//processMessage($updat["message"]);
//file_put_contents(dirname(__FILE__).'/data', $update['update_id']+1);

if(isset($update['result'])){
    $ttmp='';
    foreach($update['result'] as $dd => $updat){

        if(isset($updat['callback_query'])) {
            processMessage($updat["callback_query"]);
            $ttmp = $updat['update_id'];
        } else if (isset($updat["message"])) {
            processMessage($updat["message"]);
            $ttmp = $updat['update_id'];
        } else if (isset($updat["channel_post"])) {
            processMessage($updat["channel_post"]);
            $ttmp = $updat['update_id'];
		}
if(($fSet == 0 || empty($fSet)) && (!empty($ttmp))) file_put_contents(dirname(__FILE__).'/data', $ttmp+1);
    }
} else {
    if(isset($update['callback_query'])) processMessage($update["callback_query"]);
    else {
        processMessage($update["message"]);
        $ttmp = $update['update_id'];
    }
}

if(($fSet == 0 || empty($fSet)) && (!empty($ttmp))) {
    file_put_contents(dirname(__FILE__).'/data', $ttmp+1);
    // По окончании работы необходимо снять блокировку и удалить файл
    register_shutdown_function(function() use ($lockFp, $lockFile) {
        flock($lockFp, LOCK_UN);
        unlink($lockFile);
    });
}

die();
  


// Главный цикл обработки сообщений
function processMessage($message) {
    global $BOT_NAME, $id_admin, $mess_lenght, $img_patch, $utable;


    if(isset($message['data'])) {
        $chat_id = ( $message['message']['chat']['username'] ? $message['message']['chat']['username'] : $message['message']['chat']['id'] );
        $message_id = $message['message']['message_id'];
        $last_name = $message['message']['chat']['last_name'];
        $first_name = $message['message']['chat']['first_name'];
        $username = $message['message']['chat']['username'];
        $flg_m = 1;
    } else {
        // process incoming message
        $message_id = $message['message_id'];
        $chat_id = ( $message['chat']['username'] ? $message['chat']['username'] : $message['chat']['id'] );
        $last_name = $message['chat']['last_name']; // %last_name%
        $first_name = $message['chat']['first_name']; // %first_name%
        $username = $message['chat']['username']; // %username%
        $flg_m = 0;
   	}
	
#    if (isset($message['text']) || isset($message['data'])) {
#        // Это текст - он на не нужен - пропускаем
#        if($flg_m == 0) $text = trim($message['text']);
#    	else $text = $message['data'];
#    	return;
#	} else { // Это картинки либо музон. Сохраняем любой файл
#	    $ttext = $ddate = $im_name = '';
#	    if($message['document']['file_id']) {
#           ...
#    	} 

    // берем только посты-фотографии
    if (isset($message['photo'])) { 
        if($message['photo'][0]['file_id']) {

    	    $ttext = $ddate = $im_name = '';
            $num = count($message['photo']);
            $img = apiRequest("getFile", array('file_id' => $message['photo'][($num-1)]['file_id']));
	  
            if($img['file_path']) {
                // Проверяем наличие названия
                if(isset($message['caption'])) $ttext = $message['caption'];

                $ddate = date('Y-m-d H:i:s', $message['date']);
                $im_name = download_photo( file_get_contents(API_FILE_URL.$img['file_path']), $img_patch, $img['file_path'], $message['date'] );
     
                // Вставляем в БД
                $updatedata = mysql_query("INSERT INTO `".$utable."` (date, text, img, channel) VALUES ('$ddate', '$ttext', '$im_name', '$chat_id')") or die("Unable to write to database: " . mysql_error());
          } 
        }
    }
}



function download_photo($filedata, $img_dir, $img_path, $post_time) 
{
    $path_parts = pathinfo($img_path);

    # проверим есть ли каталог
    if (!file_exists($img_dir)) mkdir($img_dir, 0777, true);

    # задания имени файл по дате поста и проверка на существоание файла с тами именем
    $im_name = "photo_".date('Y-m-d_H-i-s', $post_time);
    $i = 0;
    while( file_exists($im_name.($i>0?'_'.$i:'').'.'.$path_parts['extension']) ) { $i++; }
    $im_name = $im_name.($i>0?'_'.$i:'').'.'.$path_parts['extension'];

    file_put_contents($img_dir.'/'.$im_name, $filedata);
    return $im_name;
}



// !!!!!!! Универсальная функция Curl-запросов к любым ресурсам
function gotourl($url='', $userAgent='Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)', $proxy='', $auth='', $post='', $referer='', $cookiesfile='', $httpopt='', $followlocation=1, $header_answer=0)
{
	$cl = curl_init();
	curl_setopt($cl, CURLOPT_URL, $url);
	if($header_answer==1) curl_setopt($cl, CURLOPT_HEADER, 1);
	else curl_setopt($cl, CURLOPT_HEADER, 0);

	curl_setopt($cl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($cl, CURLOPT_TIMEOUT, 60);
	curl_setopt($cl, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($cl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($cl, CURLOPT_SSL_VERIFYHOST, false);


	curl_setopt($cl, CURLOPT_USERAGENT, $userAgent);
	if(empty($cookiesfile)){
		curl_setopt($cl, CURLOPT_COOKIEJAR, dirName(__FILE__)."/cookie.txt");
		curl_setopt($cl, CURLOPT_COOKIEFILE, dirName(__FILE__)."/cookie.txt");
	} else {
		curl_setopt($cl, CURLOPT_COOKIEJAR, $cookiesfile);
		curl_setopt($cl, CURLOPT_COOKIEFILE, $cookiesfile);
	}
	if($httpopt) curl_setopt($cl, CURLOPT_HTTPHEADER, $httpopt);
	if($followlocation) curl_setopt($cl, CURLOPT_FOLLOWLOCATION, 1);
	if (!empty($post)) {
		curl_setopt($cl, CURLOPT_POST, 1);
		curl_setopt($cl, CURLOPT_POSTFIELDS, $post);
	} else {
		curl_setopt($cl, CURLOPT_POST, 0);
	}
	if (!empty($proxy)) {
		//curl_setopt($cl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
		//curl_setopt($cl, CURLOPT_HTTPPROXYTUNNEL, 1);
		//curl_setopt($cl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		curl_setopt($cl, CURLOPT_PROXY, $proxy);
		if (!empty($auth)) curl_setopt($cl, CURLOPT_PROXYUSERPWD, $auth);
	}
	if (!empty($referer)) curl_setopt($cl, CURLOPT_REFERER, $referer);
	$ex=curl_exec($cl);
	if (curl_error($cl)) {
		$error = curl_error($cl);
		curl_close($cl);
		return "Curl error: $error<br>";
	} else { 
	curl_close($cl); 
	return $ex; 
	}
}
?>