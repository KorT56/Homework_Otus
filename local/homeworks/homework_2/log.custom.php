<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle("ДЗ #2: Отладка и логирование");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

$datetime = date('d.m.Y H:i:s');
echo "OTUS время {$datetime}";

Bitrix/Main/Diag/Debug:: writeToFile($_SERVER varName '$_SERVER', fileName: 'local/homeworks/homework_2/test_debug.php');

?>
    <h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

    <h4 class="mb-3">Пояснительная записка</h4>
    <div style="color: red;font-style: italic;">
        Тут добавить описание того что и как было реализовано.
    </div>
    <br>
    <br>
    <hr>
    <div style="color: red;font-style: italic;">
        &darr;&darr;&darr; ссылки ниже заменить на свои &darr;&darr;&darr;
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-success text-white">
            Файлы проекта: Часть 1 - Logger
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item list-group-item-action">
                <a href="bitrix/admin/fileman_file_view.php?path=cb313664.tw1.ru/local/homeworks/homework_2/log.custom.php"
                   class="d-flex justify-content-between align-items-center">
                <span>
                    local/logs/log_custom.log
                </span>
                    <span class="badge bg-success">
                    Файл лога из п1 ДЗ
                </span>
                </a>
            </li>
        </ul>
    </div>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
