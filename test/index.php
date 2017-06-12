<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 12/06/2017
 * Time: 10:35
 */

require_once '../JsString.php';
$source = '中文 español English हिन्दी العربية português বাংলা русский 日本語 ਪੰਜਾਬੀ 한국어 ';
$str = new JsString($source);

$str->add($str);
echo count($str);


echo PHP_EOL;



