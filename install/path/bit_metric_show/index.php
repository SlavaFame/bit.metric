<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle("Аналитика");?> 

<?php
$APPLICATION->IncludeComponent(
    'bit:metric.show',
    '.default',
);?>

<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>