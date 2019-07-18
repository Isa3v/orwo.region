<?php
namespace Orwo\Region;

/**
 * [Custom - Кастомные функции]
 * Используется для дописания функционала, которого не хватает в стандартном модуле
 */
class Custom extends \Orwo\Region\Init
{
    /**
     * [patternEvent - массив сгенерированных паттернов, перед заменой в контенте или строке]
     * @return [array]          [Возвращает паттерны для замены в виде ключ - значение (паттерн - замена)]
     */
    public function patternEvent($pattern = [])
    {
        return $pattern;
    }

    /**
     * [regionEvent - массив сгенерированных регионов, перед отправкой в кеш или вывод]
     * @return [array]
     */
    public function regionEvent($arRegions = [])
    {
        return $arRegions;
    }

    /**
     * [exampleCustomFunction пример функции которая не относится к родителю (Init)]
     */
    public function exampleCustomFunction()
    {
        $arRegion = parent::getRegions();
        if (!empty($arRegion)) {
            foreach ($arRegion as $key => $region) {
                if ($_SERVER['SERVER_NAME'] == $region['href']) {
                    return $region;
                }
            }
        }
    }
}
