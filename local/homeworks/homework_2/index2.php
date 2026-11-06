<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle("ДЗ #2: Отладка и логирование");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>
    <h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

    <h4 class="mb-3">Пояснительная записка</h4>
    <p>
        В рамках домашнего задания была реализована система логирования.
        <br><br>
        <b>Часть 1 (Logger):</b> Создан простой логгер, который записывает события в файл <code>custom.log</code>. 
        Для предотвращения переполнения файла реализована проверка: если размер лога превышает 2 МБ, он автоматически переименовывается в <code>.old</code>, а запись начинается в новый файл. 
        Логирование поддерживает передачу как строк, так и массивов (для массивов используется <code>print_r</code>). Запись производится с блокировкой файла, чтобы избежать конфликтов при одновременных запросах.
        <br><br>
        <b>Часть 2 (Exception):</b> Реализован механизм перехвата исключений. При возникновении ошибки (Exception) её текст, имя файла и номер строки записываются в отдельный лог <code>exceptions.log</code>. 
        Это позволяет разработчику оперативно находить и исправлять ошибки в коде, не выводя их на экран пользователю.
    </p>
    <br>
    <br>
    <hr>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-success text-white">
            Файлы проекта: Часть 1 - Logger
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item list-group-item-action">
                <a href="/local/homeworks/homework_2/log.custom.php"
                   class="d-flex justify-content-between align-items-center">
                <span>
					local/log_custom.log
                </span>
                    <span class="badge bg-success">
                    Файл лога из п1 ДЗ
                </span>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="/local/homeworks/homework_2/writelog.php"
                   class="d-flex justify-content-between align-items-center">
                <span>
                    writelog.php
                </span>
                    <span class="badge bg-secondary">
                    Добавление в лог из п1 ДЗ
                </span>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="/local/homeworks/homework_2/clearlog.php"
                   class="d-flex justify-content-between align-items-center">
                <span>
                    clearlog.php
                </span>
                    <span class="badge bg-warning">
                    Очистить лог из п1 ДЗ
                </span>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="/local/homeworks/homework_2/logger.php"
                   class="d-flex justify-content-between align-items-center">
                <span>
                    Файл с классом кастомного логгера
                </span>
                    <span class="badge bg-primary">
                    класс логгера в админке
                </span>
                </a>
            </li>

        </ul>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-success text-white">
            Файлы проекта: Часть 2 - Exception
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item list-group-item-action">
                <a href="/local/homeworks/homework_2/exceptions.php"
                   class="d-flex justify-content-between align-items-center">
                <span>
                    local/exceptions.log
                </span>
                    <span class="badge bg-primary">
                    Файл лога из п2 ДЗ
                </span>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="/local/homeworks/homework_2/writeexception.php"
                   class="d-flex justify-content-between align-items-center">
                <span>
                    writeexception.php
                </span>
                    <span class="badge bg-success">
                    Добавление в лог из п2 ДЗ
                </span>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="/local/homeworks/homework_2/clearexception.php"
                   class="d-flex justify-content-between align-items-center">
                <span>
                    clearexception.php
                </span>
                    <span class="badge bg-secondary">
                    Очистить лог из п2 ДЗ
                </span>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <a href="/local/homeworks/homework_2/writeexception.php"
                   class="d-flex justify-content-between align-items-center">
                <span>
                    Файл с классом системного исключений
                </span>
                    <span class="badge bg-warning">
                    класс логгера в админке
                </span>
                </a>
            </li>
        </ul>
    </div>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>