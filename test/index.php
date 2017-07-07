<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 12/06/2017
 * Time: 10:35
 */

require_once '../JString.php';
$source = '中文 español English हिन्दी العربية português বাংলা русский 日本語 ਪੰਜਾਬੀ 한국어';
$str = new JString($source);
echo $str;

$b = 'ਪੰਜਾਬੀ 한국어h';
echo PHP_EOL;
//var_dump($str->compare(new JString($str))) ;

$j = new JString('abc');
$j = $j->repeat(1);
echo $j;
