<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

class custom_booking extends CModule
{
    public $MODULE_ID = "custom.booking";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("CUSTOM_BOOKING_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("CUSTOM_BOOKING_MODULE_DESC");
        $this->PARTNER_NAME = "Custom Developer";
        $this->PARTNER_URI = "https://example.com";
    }

    public function DoInstall()
    {
        // Регистрируем модуль в системе
        ModuleManager::registerModule($this->MODULE_ID);
        
        // Копируем файлы (если нужно)
        $this->InstallFiles();
    }

    public function DoUninstall()
    {
        // Удаляем файлы
        $this->UnInstallFiles();
        
        // Удаляем модуль из системы
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function InstallFiles()
    {
        // Если вы захотите хранить скрипты в /bitrix/js/, можно раскомментировать:
        // CopyDirFiles(__DIR__ . "/js", Application::getDocumentRoot() . "/bitrix/js/" . $this->MODULE_ID, true, true);
    }

    public function UnInstallFiles()
    {
        // DeleteDirFilesEx("/bitrix/js/" . $this->MODULE_ID);
    }
}