<?php
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Highloadblock\HighloadBlockTable;

Loc::loadMessages(__FILE__);

class orwo_region extends CModule
{
    public function __construct()
    {
        $this->MODULE_ID = "orwo.region";
        $arModuleVersion = array();
        include(__DIR__."/version.php");
        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
        $this->MODULE_GROUP_RIGHTS = "N";
        $this->MODULE_NAME = 'ORWO: Регионы – свой контент на алиасах';
        $this->MODULE_DESCRIPTION = 'Мета-теги, sitemap, robtos, возможность создания своих заменяемых плейсходеров';
    }

    /**
     * [doInstall Основные этапы установки]
     */
    public function doInstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;
        $this->InstallFiles();
        $iblockInstall = $this->iblockInstall();
        ModuleManager::registerModule($this->MODULE_ID);
        // Устанавливаем опции модуля
        Option::set($this->MODULE_ID, "regionIblockID", $iblockInstall['regionIblockID']);
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        // Инициализация поддоменов
        $eventManager->registerEventHandler("main", "OnBeforeProlog", $this->MODULE_ID, "\Orwo\Region\Init", "initRegions");
        // Подмена контента
        $eventManager->registerEventHandler("main", "OnEndBufferContent", $this->MODULE_ID, "\Orwo\Region\Init", "replaceBufferContent");
    }


    /**
     * [doUninstall удаление]
     */
    public function doUninstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION;
        if (Loader::includeModule('iblock')) {
            // Удаляем инфоблок
            $rsTypeIBlock = new CIBlockType;
            $rsTypeIBlock::Delete("orwo_region");
            ModuleManager::unRegisterModule($this->MODULE_ID);
        }
    }

    public function InstallFiles()
    {
        CopyDirFiles(__DIR__."/files", $_SERVER["DOCUMENT_ROOT"]."/", true, true);
        CopyDirFiles(__DIR__."/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
        return true;
    }


    // Функция добавления инфоблока и свойств
    public function IblockInstall()
    {
        // Выбираем все ID сайтов для активации инфоблока
        $rsSites = \Bitrix\Main\SiteTable::getList()->fetchAll();
        foreach ($rsSites as $key => $value) {
            $arSiteId[] = $value['LID'];
        }
        if (Loader::includeModule('iblock')) {
            // Тип инфоблока
            $iblocktype = "orwo_region";
            // Добавляем тип инфоблока
            $rsTypeIBlock = new CIBlockType;
            $arFields = array(
                "ID"        => $iblocktype,
                "SECTIONS"  => "Y",
                "SORT"      => 1,
                "LANG"      => ["ru"=>["NAME"=>"ORWO: Регионы (поддомены)"]]
              );
            // Добавляем инфоблок
            if ($rsTypeIBlock->Add($arFields)) {
                $rsIBlock = new CIBlock;
                $arFields = array(
                    "NAME"           => 'Настройка контента регионов',
                    "ACTIVE"         => "Y",
                    "IBLOCK_TYPE_ID" => $iblocktype,
                    "SITE_ID"        => $arSiteId
                  );
                if ($iblockID = $rsIBlock->Add($arFields)) {
                    // Прописываем свойства
                    $city = array(
                      "SORT" => 10,
                      "NAME" => 'Город {city}',
                      "CODE" => "city",
                      "ACTIVE" => "Y",
                      "PROPERTY_TYPE" => "S",
                      "MULTIPLE" => N,
                      "MULTIPLE_CNT" => 1,
                      "IBLOCK_ID" => $iblockID,
                    );
                    $in_city = array(
                      "SORT" => 10,
                      "NAME" => 'в Городе {in_city}',
                      "CODE" => "in_city",
                      "ACTIVE" => "Y",
                      "PROPERTY_TYPE" => "S",
                      "MULTIPLE" => N,
                      "MULTIPLE_CNT" => 1,
                      "IBLOCK_ID" => $iblockID,
                    );
                    $to_city = array(
                      "SORT" => 10,
                      "NAME" => 'в Город {to_city}',
                      "CODE" => "to_city",
                      "ACTIVE" => "Y",
                      "PROPERTY_TYPE" => "S",
                      "MULTIPLE" => N,
                      "MULTIPLE_CNT" => 1,
                      "IBLOCK_ID" => $iblockID,
                    );
                    $by_city = array(
                      "SORT" => 10,
                      "NAME" => 'по Городу {by_city}',
                      "CODE" => "by_city",
                      "ACTIVE" => "Y",
                      "PROPERTY_TYPE" => "S",
                      "MULTIPLE" => N,
                      "MULTIPLE_CNT" => 1,
                      "IBLOCK_ID" => $iblockID,
                    );

                    $rsIBlockProperty = new CIBlockProperty;
                    $rsIBlockProperty->Add($city);
                    $rsIBlockProperty->Add($in_city);
                    $rsIBlockProperty->Add($to_city);
                    $rsIBlockProperty->Add($by_city);

                    // и возвращаем массив ID нового инфоблока и каталога
                    $result['regionIblockID'] = $iblockID;
                    return $result;
                }
            }
        }
    }
}
