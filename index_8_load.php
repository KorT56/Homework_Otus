<?
use Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle("Врачи");

if (!Loader::includeModule('iblock')) {
    echo 'Модуль iblock не подключён';
    return;
}

// ИБлоки Врачи и процедуры
const IBLOCK_DOCTORS    = 16;
const IBLOCK_PROCEDURES = 17;

// ID свойств  16
const PROP_TELEFON_ID          = 65;   // Телефон
const PROP_PROCEDURE_ID        = 66;   // Процедуры (E, LINK_IB=17)
const PROP_SPETSIALIZATSIYA_ID = 67;   // Специализация

// Коды свойств 
const PROP_TELEFON_CODE          = 'TELEFON';
const PROP_PROCEDURE_CODE        = 'PROCEDURE';
const PROP_SPETSIALIZATSIYA_CODE = 'SPETSIALIZATSIYA';

const DEBUG_MODE = true;

$message     = '';
$messageType = 'success';

// Получаю свойства
function loadElementProps($iblockId, $elementId)
{
    $result = [
        'PHONE'    => '',
        'SPEC'     => '',
        'PROC_IDS' => [],
    ];

    $rs = CIBlockElement::GetPropertyValues(
        $iblockId,
        ['ID' => $elementId],
        false,
        false
    );

    $row = $rs->Fetch();
    if (!$row) return $result;

    // ---- Телефон (одиночное) ----
    if (isset($row[PROP_TELEFON_ID])) {
        $v = $row[PROP_TELEFON_ID];
        if (is_array($v)) $v = reset($v);
        $result['PHONE'] = trim((string)$v);
    }

    // ---- Специализация (одиночное) ----
    if (isset($row[PROP_SPETSIALIZATSIYA_ID])) {
        $v = $row[PROP_SPETSIALIZATSIYA_ID];
        if (is_array($v)) $v = reset($v);
        $result['SPEC'] = trim((string)$v);
    }

    // ---- Процедуры (множественное, массив ID) ----
    if (isset($row[PROP_PROCEDURE_ID])) {
        $ids = $row[PROP_PROCEDURE_ID];
        if (!is_array($ids)) $ids = [$ids];
        foreach ($ids as $pid) {
            $pid = (int)$pid;
            if ($pid > 0) $result['PROC_IDS'][] = $pid;
        }
    }

    return $result;
}

// Добавить врача
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_doctor'])) {
    $lastName   = trim($_POST['last_name']      ?? '');
    $firstName  = trim($_POST['first_name']     ?? '');
    $middleName = trim($_POST['middle_name']    ?? '');
    $phone      = trim($_POST['phone']          ?? '');
    $spec       = trim($_POST['specialization'] ?? '');
    $procedures = $_POST['procedures']          ?? [];

    if (!is_array($procedures)) $procedures = [];
    $procedures = array_values(array_filter(array_map('intval', $procedures)));

    if ($lastName === '' || $firstName === '') {
        $message     = 'Заполните обязательные поля: Фамилия и Имя.';
        $messageType = 'error';
    } else {
        $fullName = $lastName . ' ' . $firstName . ($middleName !== '' ? ' ' . $middleName : '');

        $el    = new CIBlockElement;
        $newId = $el->Add([
            'IBLOCK_ID'       => IBLOCK_DOCTORS,
            'NAME'            => $fullName,
            'ACTIVE'          => 'Y',
            'PROPERTY_VALUES' => [
                PROP_TELEFON_ID          => $phone,
                PROP_SPETSIALIZATSIYA_ID => $spec,
                PROP_PROCEDURE_ID        => $procedures,
            ],
        ]);

        if ($newId) {
            $message = 'Врач «' . htmlspecialcharsbx($fullName) . '» сохранён.';
            $_POST   = [];
        } else {
            $message     = 'Ошибка сохранения врача: ' . $el->LAST_ERROR;
            $messageType = 'error';
        }
    }
}

// Добавить процедуру
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_procedure'])) {
    $procName = trim($_POST['procedure_name'] ?? '');

    if ($procName === '') {
        $message     = 'Введите название процедуры.';
        $messageType = 'error';
    } else {
        $el    = new CIBlockElement;
        $newId = $el->Add([
            'IBLOCK_ID' => IBLOCK_PROCEDURES,
            'NAME'      => $procName,
            'ACTIVE'    => 'Y',
        ]);

        if ($newId) {
            $message = 'Процедура «' . htmlspecialcharsbx($procName) . '» добавлена.';
        } else {
            $message     = 'Ошибка добавления процедуры: ' . $el->LAST_ERROR;
            $messageType = 'error';
        }
    }
}

// Убрать врача
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doctor'])) {
    $doctorId = (int)($_POST['doctor_id'] ?? 0);

    if ($doctorId <= 0) {
        $message     = 'Не указан ID врача.';
        $messageType = 'error';
    } else {
        $nameRes = CIBlockElement::GetList(
            [], ['ID' => $doctorId, 'IBLOCK_ID' => IBLOCK_DOCTORS],
            false, false, ['ID', 'NAME']
        );
        $doctorName = ($row = $nameRes->Fetch()) ? $row['NAME'] : ('#' . $doctorId);

        if (CIBlockElement::Delete($doctorId)) {
            $message = 'Врач «' . htmlspecialcharsbx($doctorName) . '» удалён.';
        } else {
            global $APPLICATION;
            $message     = 'Ошибка удаления врача: ' . $APPLICATION->GetException();
            $messageType = 'error';
        }
    }
}

// Убрать процедуру
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_procedure'])) {
    $procId = (int)($_POST['procedure_id'] ?? 0);

    if ($procId <= 0) {
        $message     = 'Не указан ID процедуры.';
        $messageType = 'error';
    } else {
        $nameRes = CIBlockElement::GetList(
            [], ['ID' => $procId, 'IBLOCK_ID' => IBLOCK_PROCEDURES],
            false, false, ['ID', 'NAME']
        );
        $procName = ($row = $nameRes->Fetch()) ? $row['NAME'] : ('#' . $procId);

        
        $usedByCount = 0;
        $checkRes = CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID'                      => IBLOCK_DOCTORS,
                'PROPERTY_' . PROP_PROCEDURE_ID  => $procId,
            ],
            false, false, ['ID']
        );
        while ($checkRes->Fetch()) $usedByCount++;

        if (CIBlockElement::Delete($procId)) {
            $message = 'Процедура «' . htmlspecialcharsbx($procName) . '» удалена.'
                     . ($usedByCount > 0
                        ? ' (была привязана к ' . $usedByCount . ' врач.)'
                        : '');
        } else {
            global $APPLICATION;
            $message     = 'Ошибка удаления процедуры: ' . $APPLICATION->GetException();
            $messageType = 'error';
        }
    }
}

// Провека
$debugDoctorsProps = [];
if (DEBUG_MODE) {
    $dbProps = CIBlockProperty::GetList(
        ['SORT' => 'ASC'],
        ['IBLOCK_ID' => IBLOCK_DOCTORS]
    );
    while ($p = $dbProps->Fetch()) {
        $debugDoctorsProps[] = [
            'ID'       => (int)$p['ID'],
            'CODE'     => $p['CODE'],
            'NAME'     => $p['NAME'],
            'TYPE'     => $p['PROPERTY_TYPE'],
            'MULTIPLE' => $p['MULTIPLE'],
            'LINK_IB'  => $p['LINK_IBLOCK_ID'],
            'ACTIVE'   => $p['ACTIVE'],
        ];
    }
}

// Procedure
$proceduresMap  = [];
$proceduresList = [];

$res = CIBlockElement::GetList(
    ['SORT' => 'ASC', 'NAME' => 'ASC'],
    ['IBLOCK_ID' => IBLOCK_PROCEDURES, 'ACTIVE' => 'Y'],
    false,
    false,
    ['ID', 'NAME']
);
while ($ob = $res->GetNext()) {
    $id = (int)$ob['ID'];
    $proceduresMap[$id] = $ob['NAME'];
    $proceduresList[]   = ['ID' => $id, 'NAME' => $ob['NAME']];
}


$doctors = [];

$res = CIBlockElement::GetList(
    ['SORT' => 'ASC', 'NAME' => 'ASC'],
    ['IBLOCK_ID' => IBLOCK_DOCTORS, 'ACTIVE' => 'Y'],
    false,
    false,
    ['ID', 'NAME']   // свойства тянем отдельно, не через GetProperties
);

while ($ob = $res->GetNextElement()) {
    $arFields = $ob->GetFields();
    $docId    = (int)$arFields['ID'];

    // Все свойства — через GetPropertyValues
    $props = loadElementProps(IBLOCK_DOCTORS, $docId);

    // Имена процедур по ID
    $procNames = [];
    foreach ($props['PROC_IDS'] as $pid) {
        if (isset($proceduresMap[$pid])) {
            $procNames[] = $proceduresMap[$pid];
        }
    }

    $doctors[] = [
        'ID'         => $docId,
        'NAME'       => $arFields['NAME'],
        'SPEC'       => $props['SPEC'],
        'PHONE'      => $props['PHONE'],
        'PROCEDURES' => $procNames,
    ];
}

$doctorsJson = json_encode(
    $doctors,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

// Значения формы при ошибке
$formLastName   = htmlspecialcharsbx($_POST['last_name']      ?? '');
$formFirstName  = htmlspecialcharsbx($_POST['first_name']     ?? '');
$formMiddleName = htmlspecialcharsbx($_POST['middle_name']    ?? '');
$formPhone      = htmlspecialcharsbx($_POST['phone']          ?? '');
$formSpec       = htmlspecialcharsbx($_POST['specialization'] ?? '');
$formProcedures = array_map('intval', $_POST['procedures'] ?? []);
$formProcName   = htmlspecialcharsbx($_POST['procedure_name'] ?? '');
?>

<!-- стили скачала -->
<style>
    .doctors-page * { 
    box-sizing: border-box; 
    }
    .doctors-page { 
    max-width: 1100px; margin: 40px auto 60px; 
    padding: 0 20px;
    font-family: 'Segoe UI', Arial, sans-serif; color: #333; 
    }
    .doctors-page .page-title { 
    text-align: center; 
    color: #4a8fc7; font-size: 34px;
        font-weight: 400; 
        margin: 0 0 40px; 
        }
    .doctors-page .action-buttons { 
    display: flex; 
    gap: 12px; 
    margin-bottom: 30px; 
    }
    .doctors-page .btn { 
    display: inline-block; 
    padding: 9px 18px; 
    font-size: 14px; 
    color: #333;
        background-color: #f4f5f7; 
        border: 1px solid #d9dce0; 
        border-radius: 4px;
        cursor: pointer; 
        font-family: inherit; 
        transition: background-color .15s, border-color .15s; 
        }
    .doctors-page .btn:hover { 
    background-color: #e9ebee; 
    border-color: #c4c8cd; 
    }

    .doctors-page .doctor-info { 
    background: #f5f6f8; border: 1px solid #e3e5e8;
        border-left: 5px solid #5b3ce0; border-radius: 6px; padding: 24px 28px;
        margin-bottom: 30px; animation: dpFadeIn .25s ease; 
        }
    @keyframes dpFadeIn {
        from { opacity: 0; transform: translateY(-6px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .doctors-page .doctor-info .info-name { color: #4a8fc7; font-size: 20px;
        font-weight: 600; margin: 0 0 16px; }
    .doctors-page .doctor-info .info-grid { display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px 30px; }
    .doctors-page .doctor-info .info-row { font-size: 15px; color: #555; line-height: 1.5; }
    .doctors-page .doctor-info .info-row .label { display: block; font-size: 12px;
        text-transform: uppercase; letter-spacing: .5px; color: #8a8f96; margin-bottom: 3px; }
    .doctors-page .doctor-info .info-row .value { color: #222; }
    .doctors-page .doctor-info ul { margin: 4px 0 0 18px; padding: 0; color: #222; }
    .doctors-page .doctor-info ul li { line-height: 1.5; margin-bottom: 2px; }
    .doctors-page .doctor-info .info-placeholder { color: #8a8f96;
        font-size: 15px; font-style: italic; }

    .doctors-page .doctors-grid { display: grid;
        grid-template-columns: repeat(3, 1fr); gap: 30px 25px; }
    .doctors-page .card { position: relative; background-color: #f5f6f8;
        border: 1px solid #e3e5e8; border-radius: 4px; padding: 38px 30px;
        text-align: center; color: #4a8fc7; font-size: 15px; font-weight: 500;
        line-height: 1.45; cursor: pointer; box-shadow: 0 5px 0 #5b3ce0;
        transition: transform .15s, box-shadow .15s, background-color .15s;
        user-select: none; min-height: 100px; display: flex;
        align-items: center; justify-content: center; }
    .doctors-page .card:hover { transform: translateY(-2px);
        box-shadow: 0 7px 0 #4a2ecb; background-color: #f0f1f4; }
    .doctors-page .card.active { background-color: #eceefb; border-color: #5b3ce0;
        box-shadow: 0 5px 0 #4a2ecb; }
    .doctors-page .card:active { transform: translateY(2px);
        box-shadow: 0 3px 0 #4a2ecb; }

    .doctors-page .card-delete {
        position: absolute; top: 8px; right: 8px;
        width: 24px; height: 24px; padding: 0; line-height: 1;
        border: none; border-radius: 50%;
        background: transparent; color: #b6b6b6;
        font-size: 18px; font-family: Arial, sans-serif;
        cursor: pointer; display: flex;
        align-items: center; justify-content: center;
        opacity: 0; transition: opacity .15s, background .15s, color .15s;
    }
    .doctors-page .card:hover .card-delete { opacity: 1; }
    .doctors-page .card-delete:hover { background: #ff5c5c; color: #fff; }

    .doctors-page .no-data { grid-column: 1 / -1; text-align: center; color: #888;
        padding: 40px; font-size: 15px; }

    .doctors-page .message { padding: 12px 16px; margin-bottom: 20px;
        border-radius: 5px; font-size: 14px; line-height: 1.5; }
    .doctors-page .message.success { background: #e7f5ea; color: #1d6b30;
        border: 1px solid #a7d9b4; }
    .doctors-page .message.error { background: #fdecea; color: #a01919;
        border: 1px solid #f0b0aa; }

    .doctors-page .modal-overlay { display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.45); align-items: center; justify-content: center;
        z-index: 9999; padding: 20px; }
    .doctors-page .modal-overlay.active { display: flex; }
    .doctors-page .modal-content { background: #fff; padding: 32px; border-radius: 8px;
        width: 100%; max-width: 480px; position: relative;
        box-shadow: 0 12px 32px rgba(0,0,0,.22); animation: dpFadeIn .2s ease;
        max-height: 90vh; overflow-y: auto; }
    .doctors-page .modal-close { position: absolute; top: 12px; right: 16px;
        background: none; border: none; font-size: 26px; line-height: 1;
        color: #888; cursor: pointer; }
    .doctors-page .modal-close:hover { color: #333; }
    .doctors-page .modal-content h2 { color: #333; margin: 0 0 24px; font-size: 22px;
        font-weight: 700; text-align: center; padding-right: 20px; }

    .doctors-page .form-group { margin-bottom: 16px; }
    .doctors-page .form-control { width: 100%; padding: 13px 15px; font-size: 15px;
        color: #333; background: #fff; border: 2px solid #b8b8b8; border-radius: 6px;
        outline: none; font-family: inherit;
        transition: border-color .15s, box-shadow .15s; }
    .doctors-page .form-control:focus { border-color: #5b3ce0;
        box-shadow: 0 0 0 3px rgba(91,60,224,.15); }
    .doctors-page select.form-control[multiple] { height: 130px; padding: 6px; }
    .doctors-page select.form-control[multiple] option { padding: 4px 6px;
        border-radius: 3px; margin-bottom: 2px; }
    .doctors-page select.form-control[multiple] option:checked {
        background: #d9d9d9 linear-gradient(0deg, #d9d9d9 0%, #d9d9d9 100%);
        color: #333; }
    .doctors-page .form-hint { font-size: 12px; color: #888; margin-top: 4px; }
    .doctors-page .btn-save { display: block; width: 100%; padding: 13px 20px;
        margin-top: 14px; font-size: 15px; color: #333; background-color: #f0f2f5;
        border: 2px solid #b8b8b8; border-radius: 6px; cursor: pointer;
        font-family: inherit; font-weight: 500;
        transition: background-color .15s, border-color .15s; }
    .doctors-page .btn-save:hover { background-color: #e4e6eb; border-color: #5b3ce0; }

    .doctors-page .procedures-manage { margin-top: 24px; padding-top: 20px;
        border-top: 1px solid #eee; }
    .doctors-page .procedures-manage h3 { font-size: 15px; font-weight: 600;
        color: #555; margin: 0 0 12px; }
    .doctors-page .procedures-manage ul { list-style: none; padding: 0; margin: 0;
        max-height: 200px; overflow-y: auto; }
    .doctors-page .procedures-manage li { display: flex; align-items: center;
        justify-content: space-between; padding: 8px 10px; border-radius: 4px;
        font-size: 14px; color: #333; transition: background .15s; }
    .doctors-page .procedures-manage li:hover { background: #f6f7f9; }
    .doctors-page .procedures-manage .proc-empty { font-size: 13px; color: #999;
        font-style: italic; padding: 6px 0; }
    .doctors-page .proc-delete { width: 22px; height: 22px; padding: 0; line-height: 1;
        border: none; border-radius: 50%; background: transparent; color: #c4c4c4;
        font-size: 16px; cursor: pointer; display: flex; align-items: center;
        justify-content: center; transition: background .15s, color .15s; }
    .doctors-page .proc-delete:hover { background: #ff5c5c; color: #fff; }

    .debug-box { background: #1e1e1e; color: #d4d4d4; padding: 16px 20px;
        border-radius: 6px; font-family: Consolas, Monaco, monospace;
        font-size: 12.5px; line-height: 1.5; margin-bottom: 24px;
        white-space: pre-wrap; word-break: break-word;
        max-height: 500px; overflow-y: auto; }
    .debug-box .ok   { color: #6ddc6d; }
    .debug-box .warn { color: #ffc857; }
    .debug-box .err  { color: #ff6b6b; }
    .debug-box .key  { color: #7ec8ff; }
    .debug-box .muted { color: #888; }

    @media (max-width: 820px) { .doctors-page .doctors-grid {
        grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 520px) {
        .doctors-page .doctors-grid { grid-template-columns: 1fr; }
        .doctors-page .action-buttons { flex-direction: column; }
        .doctors-page .page-title { font-size: 26px; }
    }
</style>

<!-- Отображение -->
<div class="doctors-page">

    <h1 class="page-title">Врачи</h1>

    <?php if (DEBUG_MODE): ?>
        <div class="debug-box">
<strong>=== ДИАГНОСТИКА ===</strong>

<span class="key">Инфоблок врачей:</span> <?= IBLOCK_DOCTORS ?>
<span class="key">Инфоблок процедур:</span> <?= IBLOCK_PROCEDURES ?>

<strong>--- Свойства инфоблока <?= IBLOCK_DOCTORS ?> ---</strong>
<?php
if (empty($debugDoctorsProps)) {
    echo '<span class="err">СВОЙСТВА НЕ НАЙДЕНЫ!</span>' . "\n";
} else {
    foreach ($debugDoctorsProps as $p) {
        $mark = '';
        if ($p['ID'] === PROP_TELEFON_ID)          $mark .= ' <span class="ok">← TELEFON</span>';
        if ($p['ID'] === PROP_PROCEDURE_ID)        $mark .= ' <span class="ok">← PROCEDURE</span>';
        if ($p['ID'] === PROP_SPETSIALIZATSIYA_ID) $mark .= ' <span class="ok">← SPETSIALIZATSIYA</span>';

        echo 'ID=<span class="key">' . $p['ID'] . '</span>'
           . ' | CODE=' . htmlspecialcharsbx($p['CODE'])
           . ' | NAME=' . htmlspecialcharsbx($p['NAME'])
           . ' | TYPE=' . htmlspecialcharsbx($p['TYPE'])
           . ' | MULTIPLE=' . htmlspecialcharsbx($p['MULTIPLE'])
           . ' | LINK_IB=' . (int)$p['LINK_IB']
           . $mark . "\n";
    }
}
?>
<strong>--- Данные врачей (всего: <?= count($doctors) ?>) ---</strong>
<?php
if (empty($doctors)) {
    echo '<span class="warn">Активных врачей нет.</span>' . "\n";
} else {
    foreach ($doctors as $d) {
        $hasData = ($d['PHONE'] !== '' || $d['SPEC'] !== '' || !empty($d['PROCEDURES']));
        $flag = $hasData ? '<span class="ok">[OK]</span>' : '<span class="warn">[пусто]</span>';

        echo $flag . ' ID ' . $d['ID'] . ' — ' . htmlspecialcharsbx($d['NAME']) . "\n";
        echo '     Телефон:   "' . htmlspecialcharsbx($d['PHONE']) . '"' . "\n";
        echo '     Спец.:     "' . htmlspecialcharsbx($d['SPEC'])  . '"' . "\n";
        echo '     Процедуры: ' . (empty($d['PROCEDURES'])
                ? '<span class="muted">нет</span>'
                : htmlspecialcharsbx(implode(', ', $d['PROCEDURES']))) . "\n";
    }
}
?>
        </div>
    <?php endif; ?>

    <?php if ($message !== ''): ?>
        <div class="message <?= $messageType === 'success' ? 'success' : 'error' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- ============ ИНФО О ВРАЧЕ ============ -->
    <div class="doctor-info" id="doctorInfo">
        <div class="info-placeholder" id="doctorInfoPlaceholder">
            Нажмите на карточку врача, чтобы увидеть информацию.
        </div>
        <div id="doctorInfoBody" style="display:none;">
            <div class="info-name" id="infoName"></div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="label">Специализация</span>
                    <span class="value" id="infoSpec"></span>
                </div>
                <div class="info-row">
                    <span class="label">Телефон</span>
                    <span class="value" id="infoPhone"></span>
                </div>
                <div class="info-row" style="grid-column: 1 / -1;">
                    <span class="label">Процедуры</span>
                    <span class="value" id="infoProc"></span>
                </div>
            </div>
        </div>
    </div>

   
    <!-- Врачи список -->
    <div class="doctors-grid" id="doctorsGrid">
        <?php if (empty($doctors)): ?>
            <div class="no-data">Добавьте врача.</div>
        <?php else: ?>
            <?php foreach ($doctors as $doctor): ?>
                <div class="card" data-id="<?= (int)$doctor['ID'] ?>">
                    <button type="button"
                            class="card-delete"
                            data-id="<?= (int)$doctor['ID'] ?>"
                            data-name="<?= htmlspecialcharsbx($doctor['NAME']) ?>"
                            title="Удалить врача">&times;</button>
                    <?= htmlspecialcharsbx($doctor['NAME']) ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
 <!-- Для добавление процедур и врачей, сами кнопки пока не работат -->
    <div class="action-buttons">
        <button type="button" class="btn" id="openAddDoctor">Добавить врача</button>
        <button type="button" class="btn" id="openAddProcedure">Добавить процедуру</button>
    </div>

<!-- добавляет врача, пока без стилей -->
<div class="modal-overlay" id="doctorFormOverlay">
    <div class="modal-content">
        <button class="modal-close" type="button" data-close="doctorFormOverlay">&times;</button>
        <h2>Данные врача</h2>
        <form method="post">
            <div class="form-group">
                <input type="text" name="last_name" class="form-control"
                       placeholder="Фамилия" required value="<?= $formLastName ?>">
            </div>
            <div class="form-group">
                <input type="text" name="first_name" class="form-control"
                       placeholder="Имя" required value="<?= $formFirstName ?>">
            </div>
            <div class="form-group">
                <input type="text" name="middle_name" class="form-control"
                       placeholder="Отчество" value="<?= $formMiddleName ?>">
            </div>
            <div class="form-group">
                <input type="text" name="phone" class="form-control"
                       placeholder="Телефон" value="<?= $formPhone ?>">
            </div>
            <div class="form-group">
                <input type="text" name="specialization" class="form-control"
                       placeholder="Специализация" value="<?= $formSpec ?>">
            </div>
            <div class="form-group">
                <?php if (empty($proceduresList)): ?>
                    <div class="form-hint">Сначала добавьте хотя бы одну процедуру.</div>
                <?php else: ?>
                    <select name="procedures[]" class="form-control" multiple size="5">
                        <?php foreach ($proceduresList as $proc): ?>
                            <option value="<?= (int)$proc['ID'] ?>"
                                <?= in_array((int)$proc['ID'], $formProcedures, true) ? 'selected' : '' ?>>
                                <?= htmlspecialcharsbx($proc['NAME']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint">Удерживайте Ctrl (Cmd) для выбора нескольких.</div>
                <?php endif; ?>
            </div>
            <button type="submit" name="save_doctor" value="1" class="btn-save">Сохранить</button>
        </form>
    </div>
</div>

<!--добавляет процедуру, стилей нет-->
<div class="modal-overlay" id="procedureFormOverlay">
    <div class="modal-content">
        <button class="modal-close" type="button" data-close="procedureFormOverlay">&times;</button>
        <h2>Добавить процедуру</h2>
        <form method="post">
            <div class="form-group">
                <input type="text" name="procedure_name" class="form-control" required
                       placeholder="Название процедуры" value="<?= $formProcName ?>">
            </div>
            <button type="submit" name="add_procedure" value="1" class="btn-save">Сохранить</button>
        </form>

        <div class="procedures-manage">
            <h3>Существующие процедуры (<?= count($proceduresList) ?>)</h3>
            <?php if (empty($proceduresList)): ?>
                <div class="proc-empty">Пока ни одной процедуры не добавлено.</div>
            <?php else: ?>
                <ul>
                    <?php foreach ($proceduresList as $proc): ?>
                        <li>
                            <span><?= htmlspecialcharsbx($proc['NAME']) ?></span>
                            <button type="button"
                                    class="proc-delete"
                                    data-id="<?= (int)$proc['ID'] ?>"
                                    data-name="<?= htmlspecialcharsbx($proc['NAME']) ?>"
                                    title="Удалить процедуру">&times;</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!--крестик справа, сверху удалит -->
<form id="deleteForm" method="post" style="display:none;"></form>

<!-- ==================== JS ==================== -->
<script>
(function () {
    'use strict';

    const DOCTORS = <?= $doctorsJson ?>;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
        });
    }

    function openOverlay(id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('active');
    }
    function closeOverlay(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('active');
    }

    function deleteItem(type, id, name) {
        const kind = (type === 'doctor') ? 'врача' : 'процедуру';
        if (!confirm('Удалить ' + kind + ' «' + name + '»?\nДействие необратимо.')) return;

        const form = document.getElementById('deleteForm');
        form.innerHTML =
            '<input type="hidden" name="' + (type === 'doctor' ? 'delete_doctor' : 'delete_procedure') + '" value="1">' +
            '<input type="hidden" name="' + (type === 'doctor' ? 'doctor_id'     : 'procedure_id')   + '" value="' + id + '">';
        form.submit();
    }

    function showDoctorInfo(doctor) {
        document.getElementById('doctorInfoPlaceholder').style.display = 'none';
        document.getElementById('doctorInfoBody').style.display        = 'block';

        document.getElementById('infoName').textContent  = doctor.NAME  || '—';
        document.getElementById('infoSpec').textContent  = doctor.SPEC  || '—';
        document.getElementById('infoPhone').textContent = doctor.PHONE || '—';

        const procBox = document.getElementById('infoProc');
        if (Array.isArray(doctor.PROCEDURES) && doctor.PROCEDURES.length) {
            let html = '<ul>';
            doctor.PROCEDURES.forEach(function (p) { html += '<li>' + esc(p) + '</li>'; });
            html += '</ul>';
            procBox.innerHTML = html;
        } else {
            procBox.textContent = '—';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {

        document.querySelectorAll('#doctorsGrid .card').forEach(function (card) {
            card.addEventListener('click', function (e) {
                if (e.target.closest('.card-delete')) return;

                const id = parseInt(this.dataset.id, 10);
                const doctor = DOCTORS.find(function (d) {
                    return parseInt(d.ID, 10) === id;
                });
                if (!doctor) return;

                document.querySelectorAll('#doctorsGrid .card').forEach(function (c) {
                    c.classList.remove('active');
                });
                this.classList.add('active');

                showDoctorInfo(doctor);
                document.getElementById('doctorInfo').scrollIntoView({
                    behavior: 'smooth', block: 'start'
                });
            });
        });

        document.querySelectorAll('.card-delete').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                deleteItem('doctor', this.dataset.id, this.dataset.name);
            });
        });

        document.querySelectorAll('.proc-delete').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                deleteItem('procedure', this.dataset.id, this.dataset.name);
            });
        });

        document.getElementById('openAddDoctor').addEventListener('click', function () {
            openOverlay('doctorFormOverlay');
        });
        document.getElementById('openAddProcedure').addEventListener('click', function () {
            openOverlay('procedureFormOverlay');
        });

        document.querySelectorAll('[data-close]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                closeOverlay(this.dataset.close);
            });
        });

        document.querySelectorAll('.modal-overlay').forEach(function (ov) {
            ov.addEventListener('click', function (e) {
                if (e.target === this) this.classList.remove('active');
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(function (ov) {
                    ov.classList.remove('active');
                });
            }
        });

        <?php if ($messageType === 'error' && isset($_POST['save_doctor'])): ?>
            openOverlay('doctorFormOverlay');
        <?php elseif ($messageType === 'error' && isset($_POST['add_procedure'])): ?>
            openOverlay('procedureFormOverlay');
        <?php endif; ?>
    });
})();
</script>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>