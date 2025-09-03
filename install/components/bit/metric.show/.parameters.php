<?php 
$arComponentParameters = array(
    "GROUPS" => array(
        "ACCESS" => array(
            "NAME" => "Права доступа",
        ),
    ), 

    "PARAMETERS" => array(
        "MAIN_COLOR" => array(
            "PARENT" => "BASE",
            "NAME" => "Основной цвет",
            "TYPE" => "COLORPICKER",
            "DEFAULT" => "#DD046A"
        ),

        "DATA_RETENTION_PERIOD" => array(
            "PARENT" => "BASE",
            "NAME" => "Период хранения данных",
            "TYPE" => "STRING",
            "DEFAULT" => "30", // По умолчанию хранить 30 дней
            "DESCRIPTION" => "Количество дней для хранения данных в базе данных."
        ),

        "ACCESS_LEVEL" => array( 
            "PARENT" => "ACCESS",
            "NAME" => "Уровень доступа",
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => array(
                "ADMIN" => "Полный доступ",
                "EDIT" => "Изменение",
                "VIEW" => "Редактирование",
                "CLOSE" => "Доступ закрыт"
            ),
            "DEFAULT" => "ADMIN",
        ),  
    ),
);