<?php
namespace Bit\Metric;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\SystemException;
use \Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

class Agents
{
    private $moduleId;
    private $dateOption; 

    public function __construct($moduleId)
    {
        $this->moduleId = $moduleId;
        $this->dateOption = Option::get($this->moduleId, 'date', '0');
    }

    private function getDateOptionStr()
    {
        return ($this->dateOption == 0) ? "3 months" : "{$this->dateOption} days";
    }

    public static function DelOldData()
    {
        try {
            $now = new \DateTime('now');
            $now = $now->format('d.m.Y');
            $nowToDB = date('d.m.Y', strtotime('-' . $this->getDateOptionStr()));

            $arStatistic = \Bit\Metric\StatisticTable::getList([
                'filter' => [
                    '<=DATE_VISIT_BEGIN' => $nowToDB . ' 00:00:00',
                ],
            ]);

            while ($arr = $arStatistic->fetch()) {
                \Bit\Metric\StatisticTable::Delete($arr['ID']);
            }
        } catch (SystemException $e) {

        } finally {
            return "\Bit\Metric\Agents::DelOldData();";
        }
    }

    public static function importUserStat() {
        set_time_limit(600);

        try {
            $dateLeft = (new DateTime())->add('-1D');
            $dateRight = new DateTime();
            print_r('dateLeft: ' . $dateLeft->format('d.m.Y') . PHP_EOL);
            print_r('dateRight: ' . $dateRight->format('d.m.Y') . PHP_EOL);
            $importer = new UserStat($dateLeft, $dateRight);
            $importer->update();
        } catch (SystemException $e) {

        } finally {
            return "\Bit\Metric\Agents::importUserStat();";
        }

    }
}

$agent = new Agent('bit.metric');