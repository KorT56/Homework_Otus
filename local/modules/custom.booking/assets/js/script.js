BX.ready(function() {
    console.log('Custom Booking JS Loaded!');

    document.addEventListener('click', function(e) {
        let target = e.target.closest('.custom-booking-link');
        
        if (target) {
            e.preventDefault();
            e.stopPropagation();
            
            // собираем все данные из атрибутов кнопки
            let procedureId = target.getAttribute('data-procedure-id') || 0;
            let targetIblock = target.getAttribute('data-target-iblock') || 0;
            let propFio = target.getAttribute('data-prop-fio') || '';
            let propDate = target.getAttribute('data-prop-date') || '';
            let propProc = target.getAttribute('data-prop-proc') || '';
            
            let procedureName = target.textContent.trim().replace('Записаться: ', '');

            let today = new Date();
            let dd = String(today.getDate()).padStart(2, '0');
            let mm = String(today.getMonth() + 1).padStart(2, '0');
            let yyyy = today.getFullYear();
            let formattedDate = dd + '.' + mm + '.' + yyyy + ' 12:00';

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

            BX.PopupWindowManager.create('booking-popup-' + Math.random(), target, {
                content: popupContent,
                width: 400,
                autoHide: true,
                titleBar: 'Запись на процедуру',
                closeIcon: true,
                buttons: [
                    new BX.PopupWindowButton({
                        text: 'Записаться',
                        className: 'ui-btn ui-btn-success',
                        events: { 
                            click: function() { 
                                let fio = document.getElementById('popup-fio').value.trim();
                                let dateTime = document.getElementById('popup-datetime').value;
                                let resultDiv = document.getElementById('popup-result');

                                if (!fio) {
                                    resultDiv.innerHTML = '<span style="color:red;">Введите ФИО</span>';
                                    return;
                                }
                                
                                if (!dateTime) {
                                    resultDiv.innerHTML = '<span style="color:red;">Выберите дату и время</span>';
                                    return;
                                }

                                resultDiv.innerHTML = '';

                                // отправляем ajax
                                BX.ajax({
                                    url: '/local/modules/custom.booking/ajax.php', 
                                    method: 'POST',
                                    data: { 
                                        fio: fio, 
                                        time: dateTime,
                                        procedureId: procedureId,
                                        targetIblock: targetIblock, // передаем целевой инфоблок
                                        propFio: propFio,           // передаем код поля фио
                                        propDate: propDate,         // передаем код поля даты
                                        propProc: propProc          // передаем код поля процедуры
                                    },
                                    dataType: 'json',
                                    onsuccess: function(response) {
                                        if (response.status === 'success') {
                                            resultDiv.innerHTML = '<span style="color:green;">' + response.message + '</span>';
                                            
                                            if (response.redirect) {
                                                setTimeout(function() {
                                                    window.location.href = response.redirect;
                                                }, 1000); 
                                            }

                                            let popup = this;
                                            setTimeout(function() { popup.popupWindow.close(); }, 1000);
                                        } else {
                                            resultDiv.innerHTML = '<span style="color:red;">' + (response.message || 'Ошибка') + '</span>';
                                        }
                                    }.bind(this),
                                    onfailure: function() {
                                        resultDiv.innerHTML = '<span style="color:red;">Ошибка соединения</span>';
                                    }
                                });
                            } 
                        }
                    }),
                    new BX.PopupWindowButton({
                        text: 'Отмена',
                        className: 'ui-btn ui-btn-link',
                        events: { click: function() { this.popupWindow.close(); } }
                    })
                ]
            }).show();
        }
    }, true);
});