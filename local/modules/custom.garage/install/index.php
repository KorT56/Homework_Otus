<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class custom_garage extends CModule
{
    public $MODULE_ID = 'custom.garage';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');
        
        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }
        
        $this->MODULE_NAME = 'Гараж (вкладка в сделке)';
        $this->MODULE_DESCRIPTION = 'Добавляет вкладку Гараж в карточку сделки';
        $this->PARTNER_NAME = 'Custom';
        $this->PARTNER_URI = '';
    }

    function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
    }

    function DoUninstall()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}