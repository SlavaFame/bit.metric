<?php
namespace Bit\Metric;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Type;

class PagesTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'chelbit_metric_pages';
    }

    public static function getMap()
    {
        return array(
            //ID
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            //Сайт
            new Entity\StringField('LID', array(
                'required' => true,
            )),
            //URL
            new Entity\StringField('URL', array(
                'required' => true,
            )),
            //Заголовок
            new Entity\StringField('TITLE', array(
                'required' => true,
            )),
            //Тип
            new Entity\StringField('TYPE', array(
            )),
        );
    }
}