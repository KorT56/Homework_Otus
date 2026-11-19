// запускаем скрипт
console.log('если нашелся файл');

// создаем объект
var Dreamsite = {};

//  функция
Dreamsite.all = function() {
    
    // функция окна
    Dreamsite.showPopup = function() {
        
        // проверяем наличие 
        if (BX.PopupWindowManager.isPopupExists('custom-workday-popup')) return;

        // создаем попап
        var popup = BX.PopupWindowManager.create("custom-workday-popup", null, {
            // настройки окна
            width: 400,
            closeByEsc: true,
            closeIcon: true,
            titleBar: 'Рабочий день',
            content: BX.create('div', {
                // текст окна - вопрос
                html: '<p>Вы уверены, что хотите запустить или возобновить рабочий день?</p>'
            }),
            // настройка кнопок
            buttons: [
                // кнопка подтвердить
                new BX.PopupWindowButton({
                    text: 'Подтвердить',
                    className: 'popup-window-button-accept',
                    events: {
                        // ловим клик
                        click: function() {
                            // отправляем запрос
                            BX.ajax.post('/local/ajax/workday.php', {}, function(response) {
                                // парсим ответ
                                try {
                                    var data = JSON.parse(response);
                                    // если успех
                                    if (data.status === 'success') {
                                        // сообщение успеха, там нужно нажать на  ok
                                        alert('Рабочий день запущен.');
                                        // закрываем окно
                                        popup.close();
                                        // перезагружаем страницу, не получется без перезагрузки
                                        location.reload();
                                    } else {
                                        // вывод ошибки, потом убрать
                                        alert('Ошибка: ' + data.message);
                                    }
                                // если ошибка json, потом убрать
                                } catch (e) {
                                    // текст ошибки
                                    alert('Неверный ответ сервера: ' + response);
                                }
                            });
                        }
                    }
                }),
                // кнопка отмена
                new BX.PopupWindowButton({
                    text: 'Отмена',
                    events: {
                        // ловим клик, окно должно закрыться
                        click: function() {
                            // закрываем окно
                            popup.close();
                            // удаляем окно
                            popup.destroy();
                        }
                    }
                })
            ]
        });
        // показываем окно
        popup.show();
    };

    // перехват кликов
    document.addEventListener('click', function(e) {
        // ищем кнопку
        var btn = e.target.closest('button, a');
        // если нет кнопки
        if (!btn) return;

        // получаем текст, ищем по тексту кнопки
        var text = btn.textContent.trim().toLowerCase();
        
        // проверяем текст, т.к. как кнопка с таким названием одна
        if (text.includes('возобновить') || text.includes('продолжить') || text.includes('начать рабочий день')) {
            // пишем в консоль, можео удалить позже
            console.log('ПЕРЕХВАЧЕН КЛИК: ' + text);
            
            // отменяем действие
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            // показываем окно
            Dreamsite.showPopup();
        }
    // фаза захвата
    }, true); 
};

// запуск после загрузки
BX.ready(function() {
    // вызываем функцию
    Dreamsite.all();
});
