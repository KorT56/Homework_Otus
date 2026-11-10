<?
// задаем кодировку
header('Content-Type: text/html; charset=utf-8');

// подключаем стили
use Bitrix\Main\Page\Asset;
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

// задаем заголовок
$APPLICATION->SetTitle("ДЗ #2: Отладка и логирование");

// грузим бутстрап
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

// грузим классы
\Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    'Otus\\Debug\\Log' => '/local/App/Debug/Log.php',
    'Otus\\Debug\\ExceptionHandler' => '/local/App/Debug/ExceptionHandler.php',
]);

// читаем действие
$action = $_GET['action'] ?? '';

// пишем в лог
if ($action === 'write') {
    \Otus\Debug\Log::write('Открыта страница writelog.php');
    echo '<div style="padding: 20px; color: green;">Запись добавлена. Закройте эту вкладку. Обновите стартовую страницу.</div>';
    die;
}
// чистим лог
if ($action === 'clear') {
    \Otus\Debug\Log::clear();
    echo '<div style="padding: 20px; color: green;">Лог очищен. Закройте эту вкладку. Обновите стартовую страницу.</div>';
    die;
}
// пишем ошибку
if ($action === 'exception') {
    try {
        throw new \RuntimeException('Тестовое исключение');
    } catch (\Throwable $e) {
        $handler = new \Otus\Debug\ExceptionHandler();
        $handler->write($e, 1);
    }
    echo '<div style="padding: 20px; color: green;">Ошибка записана. Закройте эту вкладку. Обновите стартовую страницу.</div>';
    die;
}
// чистим ошибки
if ($action === 'clear_exception') {
    \Otus\Debug\ExceptionHandler::clear();
    echo '<div style="padding: 20px; color: green;">Лог ошибок очищен. Закройте эту вкладку. Обновите стартовую страницу.</div>';
    die;
}
?>
    <!-- выводим заголовок -->
    <h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

   <!-- блок инструкции -->
    <h4 class="mt-4 mb-3" style="color: #000;">Описание для пользователя</h4>
    <ol style="color: #000;">
        <li>нажмите на ссылку для записи очистки или скачивания логи.</li>
        <li>закройте новую вкладку и <b>обновите эту страницу</b>, чтобы увидеть запись лога внизу;</li>
    </ol>

    <!-- блок реализации -->
    <h4 class="mt-4 mb-3" style="color: #000;">Описание по реализации</h4>
    <div style="color: #000;">
        <ul class="mb-0">
            <li>класс Log в папке App/Debug пишет текст в log_custom.log;</li>
            <li>страница writelog.php вызывает метод записи и сохраняет сообщение об открытии;</li>
            <li>страница clearlog.php вызывает метод очистки и стирает содержимое log_custom.log;</li>
            <li>класс ExceptionHandler в папке App/Debug перехватывает ошибки и пишет их в exceptions.log;</li>
            <li>страница writeexception.php специально вызывает ошибку, чтобы проверить работу;</li>
            <li>страница clearexception.php вызывает метод очистки и стирает содержимое exceptions.log;</li>
            <li>на этой странице PHP читает файлы логов через file_get_contents и выводит их в блоке ниже.</li>
        </ul>
    </div>
    
    <br><hr>

    <!-- блок просмотра (перенесен наверх) -->
    <div class="card shadow-sm mt-4 mb-4">
        <div class="card-header bg-dark text-white">
            Содержимое логов
        </div>
        <div class="card-body">
            <h5>Лог log_custom.log:</h5>
            <?php
            // путь к логу
            $logFile = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/log_custom.log';
            // проверяем файл
            if (file_exists($logFile) && filesize($logFile) > 0) {
                // читаем файл
                $content = file_get_contents($logFile);
                // меняем кодировку
                $content = mb_convert_encoding($content, 'UTF-8', 'auto');
                // выводим текст
                echo '<pre style="background: #f8f9fa; padding: 10px; border: 1px solid #ddd; max-height: 200px; overflow-y: auto;">';
                echo htmlspecialchars($content);
                echo '</pre>';
            } else {
                // если пусто
                echo '<p class="text-muted">Лог пуст.</p>';
            }
            ?>
            
            <hr>
            
            <h5>Лог exceptions.log:</h5>
            <?php
            // путь к логу
            $excFile = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/exceptions.log';
            // проверяем файл
            if (file_exists($excFile) && filesize($excFile) > 0) {
                // читаем файл
                $content = file_get_contents($excFile);
                // меняем кодировку
                $content = mb_convert_encoding($content, 'UTF-8', 'auto');
                // выводим текст
                echo '<pre style="background: #f8f9fa; padding: 10px; border: 1px solid #ddd; max-height: 200px; overflow-y: auto;">';
                echo htmlspecialchars($content);
                echo '</pre>';
            } else {
                // если пусто
                echo '<p class="text-muted">Лог пуст.</p>';
            }
            ?>
        </div>
    </div>

    <br><hr>

    <!-- первая карточка -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-success text-white">
            Файлы проекта: Часть 1 - Logger
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item list-group-item-action">
                <!-- скачать лог -->
                <a href="/local/logs/log_custom.log" download target="_blank" class="text-decoration-none d-flex justify-content-between align-items-center w-100">
                    <span class="text-primary">local/logs/log_custom.log</span>
                    <span class="badge bg-success">Скачать файл лога</span>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <!-- добавить запись -->
                <a href="?action=write" target="_blank" class="text-decoration-none d-flex justify-content-between align-items-center w-100">
                    <span class="text-primary">writelog.php</span>
                    <span class="badge bg-secondary">Добавление в лог</span>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <!-- очистить лог -->
                <a href="?action=clear" target="_blank" class="text-decoration-none d-flex justify-content-between align-items-center w-100">
                    <span class="text-primary">clearlog.php</span>
                    <span class="badge bg-warning">Очистить лог</span>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <!-- посмотреть код -->
                <a href="/bitrix/admin/fileman_file_view.php?path=/local/App/Debug/Log.php" target="_blank" class="text-decoration-none d-flex justify-content-between align-items-center w-100">
                    <span class="text-primary">Файл с классом кастомного логгера</span>
                    <span class="badge bg-primary">класс логгера</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- вторая карточка -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-success text-white">
            Файлы проекта: Часть 2 - Exception
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item list-group-item-action">
                <!-- скачать лог -->
                <a href="/local/logs/exceptions.log" download target="_blank" class="text-decoration-none d-flex justify-content-between align-items-center w-100">
                    <span class="text-primary">local/logs/exceptions.log</span>
                    <span class="badge bg-primary">Скачать файл лога</span>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <!-- добавить ошибку -->
                <a href="?action=exception" target="_blank" class="text-decoration-none d-flex justify-content-between align-items-center w-100">
                    <span class="text-primary">writeexception.php</span>
                    <span class="badge bg-success">Добавление ошибки</span>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <!-- очистить лог -->
                <a href="?action=clear_exception" target="_blank" class="text-decoration-none d-flex justify-content-between align-items-center w-100">
                    <span class="text-primary">clearexception.php</span>
                    <span class="badge bg-secondary">Очистить ошибки</span>
                </a>
            </li>
            <li class="list-group-item list-group-item-action">
                <!-- посмотреть код -->
                <a href="/bitrix/admin/fileman_file_view.php?path=/local/App/Debug/ExceptionHandler.php" target="_blank" class="text-decoration-none d-flex justify-content-between align-items-center w-100">
                    <span class="text-primary">Файл с классом системного исключений</span>
                    <span class="badge bg-warning">класс логгера</span>
                </a>
            </li>
        </ul>
    </div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>