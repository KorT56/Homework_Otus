<?
AddEventHandler("main", "OnProlog", "CloseAccessForUnauthorized");

function CloseAccessForUnauthorized()
{
    global $USER, $APPLICATION;

    // если мы в административном разделе — ничего не делаем
    if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
        return;
    }

    // если пользователь не авторизован и не на странице авторизации
    if (!$USER->IsAuthorized() && !in_array($APPLICATION->GetCurPage(), array("/auth/"))) {
        // создаём объект Uri для страницы авторизации
        $uri = new \Bitrix\Main\Web\Uri("/auth/");
        // добавляем параметр back_url с текущей страницей
        $uri->addParams([
            "back_url" => $APPLICATION->GetCurPage()
        ]);
        // получаем готовую строку URL
        $redirectUrl = $uri->getUri();

        // перенаправляем на страницу входа
        LocalRedirect($redirectUrl);
        die;
    }
}