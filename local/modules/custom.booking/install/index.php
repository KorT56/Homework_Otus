<?php
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class custom_booking extends CModule
{
    public $MODULE_ID = 'custom.booking';
    public $MODULE_VERSION = '1.0.0';
    public $MODULE_VERSION_DATE = '2026-11-06';
    public $MODULE_NAME = 'Модуль записи (custom.booking)';
    public $MODULE_DESCRIPTION = 'Кастомное поле для записи клиента';

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallFiles();
        $this->InstallEvents();
    }

    public function DoUninstall()
    {
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function InstallFiles()
    {
        // Копируем JS из модуля в публичную папку /bitrix/js/
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . $this->MODULE_ID . "/assets/js/", 
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/" . $this->MODULE_ID . "/",               
            true, 
            true
        );
    }

    public function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/js/" . $this->MODULE_ID . "/");
    }

    public function InstallEvents()
    {
        $includeFile = $_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . $this->MODULE_ID . "/include.php";
        if (file_exists($includeFile)) {
            include($includeFile);
        }
    }

    public function UnInstallEvents()
    {
        UnRegisterModuleDependences("iblock", "OnIBlockPropertyBuildList", $this->MODULE_ID, "\Custom\Booking\BookingField", "GetUserTypeDescription");
    }
}
?>