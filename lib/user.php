<?php
namespace Bit\Metric;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Type;

class UserTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'chelbit_metric_user';
    }

    public static function getMap()
    {
        return array(
            //ID
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            //ID сайта
            new Entity\StringField('LID', array(
                'required' => true,
            )),
            //UID
            new Entity\StringField('UID', array(
                'required' => true,
            )),
            //IP
            new Entity\StringField('IP', array(
                'required' => true,
            )),
            //Дата визита
            new Entity\DateField('DATE_VISIT', array(
                'required' => true,
            )),
            //Дата визита
            new Entity\StringField('USER_ID_CRM', array(
            )),
            //Проверка на нового
            new Entity\StringField('ISNEW', array(
                'required' => true,
            )),
            //Страна
            new Entity\StringField('INFO', array(
                'required' => true,
            )),
            //Страна
            new Entity\StringField('COUNTRY', array(
            )),
            //Город
            new Entity\StringField('CITY', array(
            )),
            //ИД сайта
            new Entity\StringField('LANG')
        );
    }
}