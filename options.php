<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bit\Metric\CModuleOptions;

\Bitrix\Main\UI\Extension::load("ui.buttons");

Loc::loadMessages(__FILE__); 

$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);
Loader::includeModule($module_id);

global $APPLICATION;
if ($APPLICATION->GetGroupRight($module_id) < 'W') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$arTabs = array(
    array(
        'DIV' => 'general',
        'TAB' => Loc::getMessage('BIT_METRICA_OPTIONS_TAB_GENERAL'),
        'ICON' => '',
        'TITLE' => Loc::getMessage('BIT_METRICA_OPTIONS_TAB_GENERAL')
    )
);

$arGroups = array(
    'MAIN_SETTINGS' => ['TITLE' => Loc::getMessage('BIT_METRICA_OPTIONS_TAB_COLOR'), 'TAB' => 0],
    'ACCESS' => ['TITLE' => Loc::getMessage('BIT_METRICA_OPTIONS_TAB_DATE'), 'TAB' => 0]
);

$arOptions = [
    'color_picker' => array(
        'GROUP' => 'MAIN_SETTINGS',
        'TITLE' => Loc::getMessage('BIT_METRICA_MAINCOLOR'),
        'TYPE' => 'COLORPICKER',
        'SORT' => '1',
        'DEFAULT' => '#dd046a'
    ),

    'date' => array(
        'GROUP' => 'ACCESS',
        'TITLE' => Loc::getMessage('BIT_METRICA_PERIOD'), 
        'TYPE' => 'STRING',
        'SORT' => '1',
        'SIZE' => 2,
        'DEFAULT' => '' 
    )
];

$opt = new CModuleOptions($module_id, $arTabs, $arGroups, $arOptions, true);
$opt->ShowHTML();