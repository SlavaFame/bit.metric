<?php

use Bitrix\Main\Web\Json;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
\Bitrix\Main\UI\Extension::load('ui.stepprocessing');
?>
<script>
    BX.UI.StepProcessing.ProcessManager.create(
        {
            'id': 'stat_import',
            'controller': 'bit:metric.import',
            'messages': {
                // Для всех сообщений уже имеются фразы по-умолчанию.
                // Переопределение фразы необходимо для кастомизации под конкретную задачу.
                'DialogTitle': "Обработка исторических данных", // Заголовок диалога
                'DialogSummary': "Заполните дату по шаблону и нажмите кнопку 'Импорт'", // Аннотация на стартовом диалоге
                'DialogStartButton': "Импорт", // Кнопка старт
                'DialogStopButton': "Стоп", // Кнопка стоп
                'DialogCloseButton': "Закрыть", // Кнопка закрыть
                'RequestCanceling': "Прерываю...", // Аннотация, выводимая п прерывании процесса. По-умолчанию: 'Прерываю...'"
                'RequestCanceled': "Процесс прерван пользователем", // Аннотация, выводимая если процесс прерван
                'RequestCompleted': "Импорт успешно завершен", // Текст на финальном диалоге успешного завершения
                'DialogExportDownloadButton': "Скачать результаты импорта", // Кнопка для скачивания файла
                'DialogExportClearButton': "Удалить файл", // Кнопка удаления файла
            },
            'queue': [
                {'action': 'import', 'title': 'Обработка исторических данных'},
            ],
            'optionsFields': {
                'startDate': {
                    'name': 'startDate',
                    'type': 'text',
                    'title': 'Дата начала в формате ДД.ММ.ГГГГ',
                    'obligatory': false
                }
            },
            'showButtons': {
                'start': true,
                'stop': false,
                'close': true
            }
        },
    );

</script>
<button onclick="BX.UI.StepProcessing.ProcessManager.get('stat_import').showDialog();" class="ui-btn ui-btn-primary">
    Импорт CSV
</button>