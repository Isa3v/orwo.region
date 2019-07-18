<?php
namespace Orwo\Region;
/**
* @author ORWO: Isa3v
* [OrRegions description]
* @var replaceStrRegion заменяет строку с вхожденияеми
* @var getRegion получает переменные региона. Если метод вызвать с значением true, вернутся только текущий регион
* @var arRegions  - массив с городами и значениями
*
* [Sitemap & Robots description]
* @var sitemapRegion - возвращает оригинальную карту сайта с измененным доменом
* @param file - путь к оригинальному sitemap.xml
*
* @var robotsRegion - возвращает оригинальную robots.txt с измененным доменом
* @param file - путь к оригинальному robots.txt
*
* [.htaccess config]
* RewriteRule ^robots.txt$ /robots.php [L]
* RewriteRule ^sitemap.xml$ /sitemap.php [L]
*/
class Init
{
    // Массив с регионами (Задается при вызове)
    public function regionIblockID()
    {
        return \Bitrix\Main\Config\Option::get("orwo.region", "regionIblockID");
    }

    public static function initRegions()
    {
        $arRegions = [];
        if (\CModule::IncludeModule("iblock")) {
            // Что бы получить все свойства нужны ID и IBLOCK_ID
            $arSelect = array("ID", "IBLOCK_ID", "SORT", "NAME","PROPERTY_*");
            // Получаем из инфоблока ID 5 элементы
            $arFilter = array("IBLOCK_ID"=>IntVal(self::regionIblockID()), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
            $res = \CIBlockElement::GetList(array("SORT"=>"ASC"), $arFilter, false, array(), $arSelect);
            while ($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
                // Добавляем ключ региона по названию элемента
                $arRegions[$arFields['NAME']] = [];
                $arRegions[$arFields['NAME']]['ID'] = $arFields['ID'];
                foreach ($ob->GetProperties() as $key => $prop) {
                  if($prop['PROPERTY_TYPE'] == 'S'){
                    // Свойства типа "строка" делаем плейсхолдерами
                      if (!is_array($prop['VALUE']) && (!is_object($prop['VALUE']) || method_exists($prop['VALUE'], '__toString'))){
                          $arRegions[$arFields['NAME']][$key] = $prop['VALUE'];
                      }
                    }
                }
            }
            $arRegions = \Orwo\Region\Custom::regionEvent($arRegions);
        }
        return $arRegions;
    }

    /**
     * [getRegions - массив с регионами]
     * @param  boolean $mainReturn [При значении true - отдает только ключ текущего домена]
     */
    public static function getRegions($mainReturn = false)
    {
        /**
         * [$arRegions получаем из кеша или инфоблока регионы]
         */
        $obCache = \Bitrix\Main\Data\Cache::createInstance();
        if ($obCache->initCache(86000, "region_cache", 'orwo_regions')) {
            $arCache = $obCache->getVars();
            $arRegions = $arCache["region_cache"];
        } else {
            $arRegions = self::initRegions();
        }
        if ($obCache->startDataCache()) {
            $obCache->endDataCache(array("region_cache" => $arRegions));
        }

        // После получения массива регионов отдаем или все регионы или только текущий ($mainReturn = true)
        if (is_array($arRegions) && !empty($arRegions)) {
            // Если метод вернул true, отдаем только ключ текущего домена
            if ($mainReturn == true) {
                // Бывают два случая неверного размения алиасов на хостингах example.ru:443 и когда алиасы равны корневому домену.
                if (!empty($arRegions[$_SERVER['HTTP_HOST']])) {
                    $arRegions[$_SERVER['HTTP_HOST']]['href'] = $_SERVER['HTTP_HOST'];
                    return $arRegions[$_SERVER['HTTP_HOST']];
                } elseif (!empty($arRegions[$_SERVER['SERVER_NAME']])) {
                    $arRegions[$_SERVER['SERVER_NAME']]['href'] = $_SERVER['SERVER_NAME'];
                    return $arRegions[$_SERVER['SERVER_NAME']];
                } else {
                    // Если не найден регион, то отдаем первый из массива
                    $arRegionsTmp = reset($arRegions);
                    $arRegionsTmp['href'] = key(reset($arRegions));
                    return $arRegionsTmp;
                }
            }

            // Выбираем из массива текущий домен (если это не корневой) и добавляем ссылку
            foreach ($arRegions as $domain => $arResult) {
                // Добавляем ключ href - ссылку
                $arRegions[$domain]['href'] = $domain;
            }
            return $arRegions;
        } else {
            return 'Домен не инициализирован! Проверьте настройки инфоблока региональнов (function initRegions() empty)';
        }
    }

    /**
     * [getRegionIP Ищем регион через GeoIP]
     * @return [array] [Возвращает массив региона, если найден и запись в куки]
     */
    public static function getRegionIP()
    {
        $arRegions = self::getRegions();
        // Если в куках есть регион то просто возвращаем
        $cookieValue = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getCookie("getRegionIP");
        if (!empty($cookieValue)) {
            return $arRegions[$cookieValue];
        }
        if (class_exists('\Bitrix\Main\Service\GeoIp\Manager')) {
            $obCache = \Bitrix\Main\Data\Cache::createInstance();
            $ipAddress = \Bitrix\Main\Service\GeoIp\Manager::getRealIp();
            \Bitrix\Main\Service\GeoIp\Manager::useCookieToStoreInfo(true);
            $dataGeo = \Bitrix\Main\Service\GeoIp\Manager::getDataResult($ipAddress, 'ru');
            if ($dataGeo->isSuccess()) {
                $dataGeoResult = $dataGeo->getGeoData();
            }
            foreach ($arRegions as $key => $arRegion) {
                if (in_array($dataGeoResult->cityName, $arRegion) || in_array($dataGeoResult->regionName, $arRegion) || in_array($dataGeoResult->regionCode, $arRegion)) {
                    // Запишем в куки ключ чтоб каждый раз не получать все данные
                    $cookie = new \Bitrix\Main\Web\Cookie("getRegionIP", $key);
                    // На все алиасы распостроняем
                    $cookie->setSpread(\Bitrix\Main\Web\Cookie::SPREAD_DOMAIN);
                    // Настройки для работы и на http
                    $cookie->setHttpOnly(false);
                    $cookie->setSecure(false);
                    // Получаем домен наш
                    $cookie->setDomain(\Bitrix\Main\Application::getInstance()->getContext()->getServer()->getHttpHost());
                    $context = \Bitrix\Main\Application::getInstance()->getContext();
                    // Записываем
                    $context->getResponse()->addCookie($cookie);
                    // Почему-то нужно сбросить заголовки
                    $context->getResponse()->flush("");
                    // Возвращаем регион
                    return $arRegion;
                }
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * [replaceStrRegion - замена паттернов на переменные города]
     * PSR-3: 1.2 Message
     * @param  [sting] $str [Строка содержащая {паттерны}]
     */
    public static function replaceStrRegion($str)
    {
        // Получаем регионы
        $arRegion = self::getRegions(true);
        $pattern = [];
        if (is_array($arRegion) && !empty($arRegion)) {
            $arPattern = array_keys($arRegion);
            // Все кроме ID
            foreach ($arPattern as $key => $val) {
                if ($key != 'ID') {
                    $pattern["{" . $val . "}"] = $arRegion[$val];
                }
            }
            // Дополнительная обработка и добавление кастомных паттернов
            // По умолчанию возвращает тот же массив
            $pattern = \Orwo\Region\Custom::patternEvent($pattern);
            if (!is_array($str) && (!is_object($str) || method_exists($str, '__toString'))) {
                // Заменяем в строке наши плейсхолдеры
                return strtr($str, $pattern);
            } else {
                return 'Error: is not string!';
            }
        } else {
            return $str;
        }
    }

    /**
     * [replaceBufferContent - подмена плейсходеров (событие)]
     */
    public function replaceBufferContent(&$content)
    {
        global $APPLICATION;
        // Если включен режим правки или мы находимся в разделе администрирования, то не подменяем контент
        if ($APPLICATION->GetShowIncludeAreas() == false && strpos($APPLICATION->GetCurDir(), '/bitrix/') === false) {
            $content = self::replaceStrRegion($content);
        }
    }

    /**
     * [sitemapRegion - подмена карты сайта]
     * @param  [xml] $file [оригинальный файл карты сайта]
     */
    public static function sitemapRegion($file)
    {
        // Очищаем буфер
        ob_end_clean();
        header("Content-Type: text/xml");
        if (file_exists($file)) {
            $arRegion = self::getRegions(true);
            if (is_array($arRegion) && !empty($arRegion)) {
                $sitemap = json_decode(json_encode(simplexml_load_file($file)), true);
                $xml = new \SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml"/>');
                // Паттерн получающий (//sub.domain.ru или //domain.ru) не учитывая http и https
                $patternReg = '/(?:https|http)(?:\:\/\/)+(?:[a-z0-9](?:[a-z0-9-]+[a-z0-9])?\.)+(?:[a-z]+|[a-z0-9])/i';
                foreach ($sitemap['url'] as $key => $siteMapItem) {
                    // Создаем к каждому ключу обьект xml ссылки
                    $xmlUrl = $xml->addChild('url');
                    //Добавляем ключи в ссылку и меняем домен
                    foreach ($siteMapItem as $nameParam => $valueParam) {
                        if ($nameParam == 'loc') {
                            $xmlUrl->addChild($nameParam, preg_replace($patternReg, (!empty($_SERVER["HTTPS"]) ? "https://" : "http://") . $arRegion['href'], $valueParam));
                        } else {
                            $xmlUrl->addChild($nameParam, $valueParam);
                        }
                    }
                }
                echo $xml->asXML();
            } else {
                echo 'Error: getRegions(true) empty!';
            }
        } else {
            echo 'Error: ' . $file . ' not found!';
        }
        // Заканчиваем выполнение
        die();
    }
    /**
     * [robotsRegion подмена robots]
     * @param  [txt] $file [robot.txt - оригинал]
     */
    public static function robotsRegion($file)
    {
        // Очищаем буфер
        ob_end_clean();
        header("Content-Type: text/plain");
        if (file_exists($file)) {
            $arRegion = self::getRegions(true);
            if (is_array($arRegion) && !empty($arRegion)) {
                // Паттерн получающий (//sub.domain.ru или //domain.ru) не учитывая http и https
                $patternReg = '/(?:https|http)(?:\:\/\/)+(?:[a-z0-9](?:[a-z0-9-]+[a-z0-9])?\.)+(?:[a-z]+|[a-z0-9])/i';
                $robots = file_get_contents($file);
                $robots = preg_replace($patternReg, (!empty($_SERVER["HTTPS"]) ? "https://" : "http://") . $arRegion['href'], $robots);
                echo $robots;
            } else {
                echo 'Error: getRegions(true) empty!';
            }
        } else {
            echo 'Error: ' . $file . ' not found!';
        }
        // Заканчиваем выполнение
        die();
    }
}
