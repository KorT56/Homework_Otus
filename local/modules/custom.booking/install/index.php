<?php
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;

// подключаем языковые файлы
Loc::loadMessages(__FILE__);

// описываем класс модуля
class custom_booking extends CModule
{
    // id модуля
    public $MODULE_ID = 'custom.booking';
    // версия модуля
    public $MODULE_VERSION = '1.0.0';
    // дата версии
    public $MODULE_VERSION_DATE = '2026-11-06';
    // название модуля
    public $MODULE_NAME = 'Модуль записи (custom.booking)';
    // описание модуля
    public $MODULE_DESCRIPTION = 'Кастомное поле для записи клиента';

    // метод установки
    public function DoInstall()
    {
        // регистрируем модуль в системе
        ModuleManager::registerModule($this->MODULE_ID);
        // копируем файлы
        $this->InstallFiles();
        // подключаем события
        $this->InstallEvents();
    }

    // метод удаления
    public function DoUninstall()
    {
        // отключаем события
        $this->UnInstallEvents();
        // удаляем файлы
        $this->UnInstallFiles();
        // удаляем модуль из системы
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    // копирование файлов при установке
    public function InstallFiles()
    {
        // копируем js из модуля в публичную папку
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . $this->MODULE_ID . "/assets/js/", 
            $_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/" . $this->MODULE_ID . "/",               
            true, 
            true
        );
    }

    // удаление файлов
    public function UnInstallFiles()
    {
        // удаляем папку с js
        DeleteDirFilesEx("/bitrix/js/" . $this->MODULE_ID . "/");
    }

    // подключение событий
    public function InstallEvents()
    {
        // путь к include.php
        $includeFile = $_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . $this->MODULE_ID . "/include.php";
        // если файл существует - подключаем
        if (file_exists($includeFile)) {
            include($includeFile);
        }
    }

    // отключение событий
    public function UnInstallEvents()
    {
        // удаляем обработчик события инфоблока
        UnRegisterModuleDependences("iblock", "OnIBlockPropertyBuildList", $this->MODULE_ID, "\Custom\Booking\BookingField", "GetUserTypeDescription");
    }
}
?>