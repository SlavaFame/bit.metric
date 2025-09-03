<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

$this->addExternalJS($templateFolder."/lib/daterangepicker.min.js");
$this->addExternalCss($templateFolder."/lib/daterangepicker.css");

$module_id = 'bit.metric';

$colorOption = Option::get($module_id, 'color_picker', '#dd046a'); // Значение по умолчанию, если не установлено 

$dateOption  = Option::get($module_id, 'date', '0'); // Значение по умолчанию, если не установлено 
$dateOptionStr = ($dateOption == 0) ? "3 months" : "$dateOption days"; // Если не указано сколько дней, то делаем интервал = 3 месяца
?>

<div class="calendar-range">
    <input type="text" class="ui-btn" name="daterange" value="">
    <span></span>
</div>

<script>
    $(function() {
        let minDate = "<?=date('d.m.Y', strtotime('-' . $dateOptionStr . '', strtotime(date('d.m.Y'))));?>", 
            maxDate = "<?=date('d.m.Y');?>";

        $('input[name="daterange"]').daterangepicker({
            "applyButtonClasses": "ui-btn ui-btn-sm ui-btn-success",
            "cancelClass": "ui-btn ui-btn-sm ui-btn-link", 

            "startDate": "<?=$arResult["DATA_FROM"];?>", 
            "endDate": "<?=$arResult["DATA_TO"];?>",  
            "minDate": minDate,
            "maxDate": maxDate,

            "showDropdowns": true,
            "showWeekNumbers": false,
            "showISOWeekNumbers": false,
            "locale": {
                "format": "DD.MM.YYYY",
                "separator": " - ",
                "applyLabel": "<?= Loc::getMessage('BIT_METRICA_CALENDAR_M1') ?>",
                "cancelLabel": "<?= Loc::getMessage('BIT_METRICA_CALENDAR_M2') ?>",
                "fromLabel": "<?= Loc::getMessage('BIT_METRICA_CALENDAR_M3') ?>",
                "toLabel": "<?= Loc::getMessage('BIT_METRICA_CALENDAR_M4') ?>",
                "customRangeLabel": "<?= Loc::getMessage('BIT_METRICA_CALENDAR_M5') ?>",
                "weekLabel": "�",
                "daysOfWeek": [
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_W1') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_W2') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_W3') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_W4') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_W5') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_W6') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_W7') ?>"
                ],
                "monthNames": [
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_MH1') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_MH2') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_MH3') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_MH4') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_MH5') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_MH6') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_MH7') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_MH8') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_MH9') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_MH10') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_MH11') ?>",
                    "<?= Loc::getMessage('BIT_METRICA_CALENDAR_MH12') ?>"
                ],
                "firstDay": 1
            },
            "linkedCalendars": false,
            "showCustomRangeLabel": false,
            "alwaysShowCalendars": true
        },
         
        function(start, end, label) {
            link = '<?=$APPLICATION->GetCurPageParam("", array("date_from", "date_to"));?>';
            location.href = link + (link.indexOf('?') === -1 ? '?' : '&') + 'date_from=' + start.format('YYYY-MM-DD') + '&date_to=' + end.format('YYYY-MM-DD');
        });
    });
</script>