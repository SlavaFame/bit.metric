<?php
namespace Bit\Metric;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Type;

class UserDetailTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'chelbit_metric_user_detail'; 
    }

    public static function getMap()
    {
        return array(
            //ID
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            new Entity\StringField('USER_KEY', array(
                'required' => true,
            )),
            //UID
            new Entity\StringField('USER_ID', array(
                'required' => true,
            )),
            //IP
            new Entity\StringField('NAME', array(
                'required' => true,
            )),
            new Entity\StringField('EMAIL', array(
            )),
            new Entity\StringField('PHONE', array(
            )),
            new Entity\StringField('SETTINGS', array(
            )),
            new Entity\DateTimeField('DATE_CREATE', array(
                'required' => true,
            )),
            new Entity\StringField('UID', array(
                'required' => true,
            ))
        );
    }
}