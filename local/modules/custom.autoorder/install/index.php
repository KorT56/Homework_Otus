<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class custom_autoorder extends CModule
{
    public $MODULE_ID = 'custom.autoorder';
    public $MODULE_VERSION = '1.0.0';
    public $MODULE_VERSION_DATE = '2026-11-02';
    public $MODULE_NAME = 'Автозаказ';
    public $MODULE_DESCRIPTION = 'Модуль для автозаказа';
    public $PARTNER_NAME = 'custom';
    public $PARTNER_URI = 'https://site.ru';

    public function __construct() {}

    public function DoInstall()
    {
        global $APPLICATION;
        RegisterModule($this->MODULE_ID);
        $this->InstallDB();
        RegisterModuleDependences('main', 'OnBuildGlobalMenu', $this->MODULE_ID, 'custom_autoorder', 'OnBuildGlobalMenu');
        CAdminMessage::ShowNote('Модуль "Автозаказ" установлен!');
    }

    public function DoUninstall()
    {
        global $APPLICATION;
        UnRegisterModuleDependences('main', 'OnBuildGlobalMenu', $this->MODULE_ID, 'custom_autoorder', 'OnBuildGlobalMenu');
        UnRegisterModule($this->MODULE_ID);
        $this->UnInstallDB();
        CAdminMessage::ShowNote('Модуль "Автозаказ" удален!');
    }

    public function InstallDB()
    {
        global $DB;
        $DB->Query("CREATE TABLE IF NOT EXISTS `custom_autoorder_drafts` (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `PRODUCT_ID` int(11) NOT NULL,
            `PRODUCT_NAME` varchar(255) NOT NULL,
            `MIN_STOCK` float NOT NULL,
            `CURRENT_STOCK` float NOT NULL,
            `CREATED_AT` datetime NOT NULL,
            PRIMARY KEY (`ID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    public function UnInstallDB()
    {
        global $DB;
        $DB->Query("DROP TABLE IF EXISTS `custom_autoorder_drafts`");
    }

    public static function OnBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu)
    {
        // Добавляем в раздел "Настройки -> Настройки модулей"
        $aMenu = [
            "parent_menu" => "global_menu_settings", 
            "section" => "custom.autoorder",
            "sort" => 100,
            "text" => "Автозаказ",
            "title" => "Автозаказ",
            "url" => "settings.php?mid=custom.autoorder&lang=" . LANGUAGE_ID,
        ];
        $aModuleMenu[] = $aMenu;
    }
}
?>