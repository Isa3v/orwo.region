<?php
$module_id = "orwo.seotag";
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

$moduleAccess = $APPLICATION->GetGroupRight($module_id);
if ($moduleAccess >= "W"):
    Loader::includeModule($module_id);
    IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
    $aTabs = array(
        array("DIV" => "edit1", "TAB" => Loc::getMessage("I_TAB_EDIT1"), "ICON" => "", "TITLE" => Loc::getMessage("I_TAB_EDIT1")),
        array("DIV" => "edit2", "TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"), "ICON" => "", "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS"))
    );
    $tabControl = new CAdminTabControl("tabControl", $aTabs);
?>
	<?$tabControl->BeginNextTab();?>
	<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
	<?$tabControl->Buttons();?>
		<input type="submit" name="Update" value="<?=Loc::getMessage("MAIN_SAVE")?>" title="<?=Loc::getMessage("MAIN_OPT_SAVE_TITLE")?>" class="adm-btn-save">
		<?=bitrix_sessid_post();?>
		<?if (strlen($_REQUEST["back_url_settings"]) > 0):?>
			<input type="button" name="Cancel" value="<?=Loc::getMessage("MAIN_OPT_CANCEL")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
			<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
		<?endif;?>
	<?$tabControl->End();?>
	</form>

<?endif;?>
