<?php
namespace Bit\Metric;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

use \Bitrix\Main\Entity;
use \Bitrix\Main\Type;

class StatisticTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'chelbit_metric_statistic';
    }

    public static function getMap()
    {
        return array(
            //ID
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            //Метка
            new Entity\StringField('UID', array(
                'required' => true,
            )),
            //ID страницы
            new Entity\IntegerField('PAGE_ID', array(
                'required' => true,
            )),
            //Дата начала
            new Entity\DatetimeField('DATE_VISIT_BEGIN', array(
                'required' => true,
            )),
            //Дата окончания
            new Entity\DatetimeField('DATE_VISIT_END', array(
                'required' => true,
            )),
            //Откуда пришли
            new Entity\StringField('COME_FROM', array(
            )),
            //Настройка
            new Entity\StringField('SETTINGS'),
            //Сколько времени на странице
            new Entity\ExpressionField('TIME_ON_PAGE',
                'TIMESTAMPDIFF(SECOND,%s, %s)', ['DATE_VISIT_BEGIN','DATE_VISIT_END']
            ),
        );
    }
}