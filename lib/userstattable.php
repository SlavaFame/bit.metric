<?php
namespace Bit\Metric;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DateField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\SystemException;

class UserStatTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'chelbit_metric_user_stat';
    }

    /**
     * @throws SystemException
    */
    public static function getMap(): array
    {
        return [
            new IntegerField('USER_ID', [
                'primary' => true,
                'required' => true,
            ]),
            new DateField('DAY', [
                'primary' => true,
                'required' => true,
            ]),
            new IntegerField('LIKES', ['default_value' => 0]),
            new IntegerField('COMMENTS', ['default_value' => 0]),
            new IntegerField('POSTS', ['default_value' => 0]),
            new IntegerField('POSTS_VIEWS', ['default_value' => 0]),
            new IntegerField('TASKS', ['default_value' => 0]),
            new IntegerField('TASKS_FINISHED', ['default_value' => 0]),
            new IntegerField('EVENTS', ['default_value' => 0]),
            new IntegerField('SURVEYS', ['default_value' => 0]),
            new IntegerField('MESSAGES', ['default_value' => 0]),
            new IntegerField('GROUPS', ['default_value' => 0]),
        ];
    }
}