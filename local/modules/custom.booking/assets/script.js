javascript
(function() {
    
    // --- 1. ФУНКЦИЯ ОТКРЫТИЯ ОКНА ---
    function openBookingDialog(procedureId, procedureName) {
        let formHtml = `
            <div class="booking-form-container" style="padding: 10px;">
                <p style="margin-bottom: 15px;">Вы записываете клиента на: <b>${procedureName}</b></p>
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px;">ФИО клиента:</label>
                    <input type="text" id="popup-booking-fio" class="ui-ctl-element" style="width:100%;" placeholder="Иванов Иван Иванович">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px;">Время записи:</label>
                    <input type="datetime-local" id="popup-booking-time" class="ui-ctl-element" style="width:100%;">
                </div>
            </div>
        `;

        BX.UI.Dialogs.MessageBox.show({
            message: formHtml,
            title: "Новая запись клиента",
            buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
            okCaption: "Записать",
            cancelCaption: "Отмена",
            onOk: function (messageBox) {
                let fio = document.getElementById('popup-booking-fio').value;
                let time = document.getElementById('popup-booking-time').value;

                if (!fio || !time) {
                    alert("Пожалуйста, заполните ФИО и время.");
                    return; 
                }

                let okBtn = messageBox.getOkButton();
                if(okBtn) okBtn.classList.add('ui-btn-wait');

                BX.ajax.post(
                    '/local/modules/custom.booking/ajax.php', 
                    { fio: fio, time: time, procedureId: procedureId },
                    function (result) {
                        if(okBtn) okBtn.classList.remove('ui-btn-wait');
                        try {
                            let response = JSON.parse(result);
                            if (response.status === 'success') {
                                messageBox.close();
                                alert("Клиент успешно записан!");
                            } else {
                                alert("Ошибка: " + response.message);
                            }
                        } catch (e) {
                            alert("Ошибка при чтении ответа сервера");
                        }
                    }
                );
            }
        });
    }

    // --- 2. ОБРАБОТЧИК КЛИКОВ ---
    document.addEventListener('click', function (e) {
        let target = e.target.closest('.custom-booking-link');
        if (target) {
            e.preventDefault();
            e.stopPropagation();
            let procedureId = target.getAttribute('data-procedure-id');
            let procedureName = target.getAttribute('data-procedure-name');
            openBookingDialog(procedureId, procedureName);
        }
    });

    // --- 3. АВТОЗАМЕНА (УМНЫЙ АЛГОРИТМ) ---
    function processGridCells() {
        // Ищем все строки в таблице
        let rows = document.querySelectorAll('tr');
        
        rows.forEach(function(row) {
            let cells = row.querySelectorAll('td');
            if (cells.length < 6) return; // Пропускаем пустые строки или шапку

            // Нам нужно найти в строке ячейку, где есть только цифры (это наша колонка)
            let idCell = null;
            let procName = "";

            // Ищем ячейку с цифрами (ID процедуры)
            for (let i = 0; i < cells.length; i++) {
                let text = cells[i].innerText.trim();
                // Если это цифра (например, 30) и в ячейке нет ссылки
                if (/^\d+$/.test(text) && !cells[i].querySelector('.custom-booking-link')) {
                    idCell = cells[i];
                    break; // Нашли ячейку с ID
                }
            }

            if (idCell) {
                // Теперь найдем название процедуры. Оно обычно в колонке "Процедуры" (слева от ID).
                let idCellIndex = Array.from(cells).indexOf(idCell);
                
                // Проверяем ячейки слева от ID, чтобы найти текст "УЗИ", "ЭЭГ" и т.д.
                for (let j = idCellIndex - 1; j >= 0; j--) {
                    let pText = cells[j].innerText.trim();
                    // Если нашли текст, похожий на название процедуры (не цифры, не "Нет данных")
                    if (pText && !/^\d+$/.test(pText) && pText !== 'Нет данных' && pText !== '—' && !pText.includes('+')) {
                        procName = pText;
                        break;
                    }
                }

                // Если название не нашли, используем заглушку
                if (!procName) procName = "Процедура";

                // Берем ID из ячейки
                let procId = idCell.innerText.trim();

                // Заменяем цифру на красивую ссылку
                idCell.innerHTML = `<a href="javascript:void(0)" class="custom-booking-link" 
                                        data-procedure-id="${procId}" 
                                        data-procedure-name="${procName}" 
                                        style="cursor:pointer; text-decoration:underline; color:#2fc7f7; font-weight:bold;">
                                        📝 Записать: ${procName}
                                      </a>`;
            }
        });
    }

    // --- 4. ЗАПУСК (Ищем таблицу 10 секунд) ---
    let attempts = 0;
    let interval = setInterval(function() {
        processGridCells();
        attempts++;
        if (attempts > 10) clearInterval(interval); // Останавливаем через 10 секунд
    }, 1000);

})();