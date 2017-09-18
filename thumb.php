<?php
    ini_set('display_errors', 0);
    #error_reporting(E_ALL);

    include_once 'config.php';

    header("Accept-Ranges: bytes");
    header("Content-type: image/jpeg");

    $src    = addslashes($_GET['src']);
    $source = $img_patch.'/'.$src; //наш исходник
    if (!file_exists($source)) return; // проверка существование файла

    $height = 200; //параметр высоты превью
    $width  = 200; //параметр ширины превью
    $rgb    = 0xffffff; //цвет заливки несоответствия

    $size   = getimagesize($source);//узнаем размеры картинки (дает нам масив size)
    $format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1)); //определяем тип файла
    $icfunc = "imagecreatefrom" . $format;   //определение функции соответственно типу файла
    if (!function_exists($icfunc)) return false;  //если нет такой функции прекращаем работу скрипта

    $img = imagecreatetruecolor($width,$height); //создаем вспомогательное изображение пропорциональное превью
    imagefill($img, 0, 0, $rgb); //заливаем его…
    $photo = $icfunc($source); //достаем наш исходник

    # кроп в квадрат с заполнением белым
    #$x_ratio = $width / $size[0]; //пропорция ширины будущего превью
    #$y_ratio = $height / $size[1]; //пропорция высоты будущего превью
    #$ratio       = min($x_ratio, $y_ratio);
    #$use_x_ratio = ($x_ratio == $ratio); //соотношения ширины к высоте
    #$new_width   = $use_x_ratio  ? $width  : floor($size[0] * $ratio); //ширина превью 
    #$new_height  = !$use_x_ratio ? $height : floor($size[1] * $ratio); //высота превью
    #$new_left    = $use_x_ratio  ? 0 : floor(($width - $new_width) / 2); //расхождение с заданными параметрами по ширине
    #$new_top     = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2); //расхождение с заданными параметрами по высоте
    #imagecopyresampled($img, $photo, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]); //копируем на него нашу превью с учетом расхождений

    # кроп в квадрат с обрезанием сторон
    $min_cube = min($size[0], $size[1]);
    $new_left    = floor(($size[0] - $min_cube) / 2); //расхождение с заданными параметрами по ширине
    $new_top     = floor(($size[1] - $min_cube) / 2); //расхождение с заданными параметрами по высоте
    imagecopyresampled($img, $photo, 0, 0, $new_left, $new_top, $width, $height, $min_cube, $min_cube); //копируем на него нашу превью с учетом расхождений

    imagejpeg($img); //выводим результат (превью картинки)
    imagejpeg($img, $img_preview.'/'.$src); //записываем в файл

    // Очищаем память после выполнения скрипта
    imagedestroy($img);
    imagedestroy($photo);
?>