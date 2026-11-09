BX.ready(function() {
    // выводим сообщение в консоль
    console.log('Custom Booking');

    // слушаем клики по документу
    document.addEventListener('click', function(e) {
        // ищем кнопку
        let target = e.target.closest('.custom-booking-link');
        
        // если нашли кнопку
        if (target) {
            // отменяем стандартное поведение
            e.preventDefault();
            // останавливаем всплытие
            e.stopPropagation();
            
            // id процедуры
            let procedureId = target.getAttribute('data-procedure-id') || 0;
            // id целевого инфоблока
            let targetIblock = target.getAttribute('data-target-iblock') || 0;
            // поле фио
            let propFio = target.getAttribute('data-prop-fio') || '';
            // поле даты
            let propDate = target.getAttribute('data-prop-date') || '';
            // поле процедуры
            let propProc = target.getAttribute('data-prop-proc') || '';
            
            // название процедуры
            let procedureName = target.textContent.trim().replace('Записаться: ', '');

            // текущая дата
            let today = new Date();
            // день
            let dd = String(today.getDate()).padStart(2, '0');
            // месяц
            let mm = String(today.getMonth() + 1).padStart(2, '0');
            // год
            let yyyy = today.getFullYear();
            // дата и время по умолчанию
            let formattedDate = dd + '.' + mm + '.' + yyyy + ' 12:00';

            // html окна
            let popupContent = `
                <div style="padding: 15px; font-family: Arial, sans-serif;">
                    <div style="margin-bottom: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
                        <b>Процедура:</b> ${procedureName} <br>
                        <span style="color: #666; font-size: 12px;">(ID: <span id="popup-procedure-id">${procedureId}</span>)</span>
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <label style="font-weight: bold;">ФИО:</label><br>
                        <input type="text" id="popup-fio" style="width: 100%; padding: 5px; margin-top: 3px; border: 1px solid #ccc;">
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <label style="font-weight: bold;">Дата и время:</label><br>
                        <input type="text" id="popup-datetime" value="${formattedDate}" 
                               onclick="BX.calendar({node: this, field: this, bTime: true});" 
                               style="width: 100%; padding: 5px; border: 1px solid #ccc; cursor: pointer;" 
                               readonly placeholder="Выберите дату и время">
                    </div>
                    
                    <div id="popup-result" style="margin-top: 10px; font-weight: bold;"></div>
                </div>
            `;

            // создаем окно
            BX.PopupWindowManager.create('booking-popup-' + Math.random(), target, {
                // контент
                content: popupContent,
                // ширина
                width: 400,
                // закрывать при клике вне
                autoHide: true,
                // заголовок
                titleBar: 'Запись на процедуру',
                // крестик
                closeIcon: true,
                // кнопки
                buttons: [
                    new BX.PopupWindowButton({
                        // текст кнопки
                        text: 'Записаться',
                        // класс кнопки
                        className: 'ui-btn ui-btn-success',
                        // события
                        events: { 
                            // по клику
                            click: function() { 
                                // получаем фио
                                let fio = document.getElementById('popup-fio').value.trim();
                                // получаем дату
                                let dateTime = document.getElementById('popup-datetime').value;
                                // блок результата
                                let resultDiv = document.getElementById('popup-result');

                                // проверяем фио
                                if (!fio) {
                                    // выводим ошибку
                                    resultDiv.innerHTML = '<span style="color:red;">Введите ФИО</span>';
                                    // прерываем
                                    return;
                                }
                                
                                // проверяем дату
                                if (!dateTime) {
                                    // выводим ошибку
                                    resultDiv.innerHTML = '<span style="color:red;">Выберите дату и время</span>';
                                    // прерываем
                                    return;
                                }

                                // очищаем ошибки
                                resultDiv.innerHTML = '';

                                // отправляем ajax
                                BX.ajax({
                                    // путь
                                    url: '/local/modules/custom.booking/ajax.php', 
                                    // метод
                                    method: 'POST',
                                    // данные
                                    data: { 
                                        // фио
                                        fio: fio, 
                                        // время
                                        time: dateTime,
                                        // id процедуры
                                        procedureId: procedureId,
                                        // целевой инфоблок
                                        targetIblock: targetIblock,
                                        // поле фио
                                        propFio: propFio,
                                        // поле даты
                                        propDate: propDate,
                                        // поле процедуры
                                        propProc: propProc
                                    },
                                    // тип данных
                                    dataType: 'json',
                                    // при успехе
                                    onsuccess: function(response) {
                                        // если успешно
                                        if (response.status === 'success') {
                                            // выводим сообщение
                                            resultDiv.innerHTML = '<span style="color:green;">' + response.message + '</span>';
                                            
                                            // если есть редирект
                                            if (response.redirect) {
                                                // ждем секунду
                                                setTimeout(function() {
                                                    // перенаправляем
                                                    window.location.href = response.redirect;
                                                }, 1000); 
                                            }

                                            // сохраняем контекст
                                            let popup = this;
                                            // ждем секунду
                                            setTimeout(function() { 
                                                // закрываем окно
                                                popup.popupWindow.close(); 
                                            }, 1000);
                                        } else {
                                            // выводим ошибку
                                            resultDiv.innerHTML = '<span style="color:red;">' + (response.message || 'Ошибка') + '</span>';
                                        }
                                    }.bind(this),
                                    // при ошибке
                                    onfailure: function() {
                                        // выводим ошибку
                                        resultDiv.innerHTML = '<span style="color:red;">Ошибка соединения</span>';
                                    }
                                });
                            } 
                        }
                    }),
                    new BX.PopupWindowButton({
                        // текст
                        text: 'Отмена',
                        // класс
                        className: 'ui-btn ui-btn-link',
                        // события
                        events: { 
                            // по клику
                            click: function() { 
                                // закрываем окно
                                this.popupWindow.close(); 
                            } 
                        }
                    })
                ]
            }).show();
        }
    }, true);
});