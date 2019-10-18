<?php
// Служебная часть битрикса
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
\Bitrix\Main\Loader::includeModule("orwo.region");
if(class_exists('\Orwo\Region\Init')){
  // Ищем в этой дериктории robots.txt и преобразовываем
  \Orwo\Region\Init::sitemapRegion(__DIR__ . "/sitemap.xml");
}else{
  header('Content-Type: text/xml');
  echo file_get_contents(__DIR__ . "/sitemap.xml");
}
