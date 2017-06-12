<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 12/06/2017
 * Time: 10:35
 */

require_once '../JsString.php';

$str = new JsString('中文 español English हिन्दी العربية português বাংলা русский 日本語 ਪੰਜਾਬੀ 한국어 ');

foreach ($str as $char)
{
    echo $char;
}

//'中文 español English हिन्दी العربية português বাংলা русский 日本語 ਪੰਜਾਬੀ 한국어 '

echo PHP_EOL;
echo $str[1];//文
echo PHP_EOL;
echo $str[60];//語


