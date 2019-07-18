<?php
// Проверяем объявлен ли класс
if (class_exists('\Orwo\Region\Init')) {
  if($arParams['GEOIP'] == 'Y' ){
    $arResult['IP_REGION'] = \Orwo\Region\Init::getRegionIP();
  }
  $arResult['REGIONS'] = \Orwo\Region\Init::getRegions();
  $arResult['MAIN_REGION'] = \Orwo\Region\Init::getRegions(true);
}
$this->IncludeComponentTemplate();
?>
