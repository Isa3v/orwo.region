<?
/**
 *   $arResult['IP_REGION'] = \Orwo\Region\Init::getRegionIP(); // По IP получаем регион пользвателя
 *   $arResult['REGIONS'] = \Orwo\Region\Init::getRegions(); // Все регионы
 *   $arResult['MAIN_REGION'] = \Orwo\Region\Init::getRegions(true); // Текущий регион
 */
?>
<div class="region">
  <span class="region__name">
    <span><?=$arResult['MAIN_REGION']['city']?></span>
    <span class="region_angle"><</span>
  </span>

  <ul class="region__dropdown">
    <?foreach ($arResult['REGIONS'] as $key => $region) {?>
      <?if($region['href'] == $arResult['MAIN_REGION']['href']){?>
        <li>
          <span><?=$region['city']?></span>
        </li>
      <?}else{?>
        <li>
          <a href="//<?=$region['href'].$_SERVER['REQUEST_URI']?>"><?=$region['city']?></a>
        </li>
      <?}?>

    <?}?>
  </ul>
  <?if(!empty($arResult['IP_REGION']) && $arResult['IP_REGION']['href'] != $arResult['MAIN_REGION']['href']){?>
    <div class="region__ip">
      <div class="region__ip_name">Ваш город: <?=$arResult['IP_REGION']['city']?>?</div>
      <a href="//<?=$arResult['IP_REGION']['href']?>">Да</a> <a href="javascript::void(0)" class="region__ip_disable">Закрыть</a>
    </div>
  <?}?>
</div>
