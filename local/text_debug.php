<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle("ДЗ Запись в лог");

Bitrix/Main/Diag/Debug:: writeToFile($_SERVER varName '$_SERVER', fileName: 'local/homeworks/homework_2/writelog.php');