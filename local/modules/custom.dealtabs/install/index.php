<?php
use Bitrix\Main\ModuleManager;

class custom_dealtabs extends CModule
{
    public $MODULE_ID = 'custom.dealtabs';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME = 'Вкладка Список товаров (Остатки)';
    public $MODULE_DESCRIPTION = 'Добавляет вкладку со списком товаров и остатков в карточку CRM';

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . "/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function DoUninstall()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}