# php-js-like-string

##In develop

###php-js-like-string with support of multibyte string(UTF-8)


```require_once '../JsString.php';
   
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


   "
   
```