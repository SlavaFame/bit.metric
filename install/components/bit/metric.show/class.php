<?php
use Bit\Metric\UserStatTable;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config as Conf;
use \Bitrix\Main\Config\Option;
use Bitrix\Main\Entity;
use Bitrix\Main\Loader;
use \Bitrix\Main\Entity\Base;
use \Bitrix\Main\Application;
use \Bitrix\Main;
use \Bitrix\Main\Type;
use Bitrix\Main\DB\SqlExpression;
use \Bitrix\Main\Data\Cache;

require_once $_SERVER['DOCUMENT_ROOT'].'/local/modules/bit.metric/include.php';  

class BitMetricShowComponent extends CBitrixComponent 
{
    private $helper;

    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->helper = new BitMetricHelper();
    }

    public function executeComponent()
    {
        $this->includeComponentLang('calendar.php');
        $this->includeComponentLang('template.php');

        if ($this->helper->checkModules()) {
            $request = \Bitrix\Main\Context::getCurrent()->getRequest();

            $date_from = new DateTime($request->get('date_from'));
            $date_to = new DateTime($request->get('date_to'));

            $this->arResult['DATA_FROM'] = $date_from->format('d.m.Y');
            $this->arResult['DATA_TO'] = $date_to->format('d.m.Y');
            $this->arResult['USER_DETAIL'] = $this->helper->getUserDetailForPages($this->arResult['DATA_FROM'], $this->arResult['DATA_TO']);
            $this->arResult['DAY_ARR'] = $this->helper->getDates($this->arResult['DATA_FROM'], $this->arResult['DATA_TO']);
            $this->arResult['USER_CNT_FOR_CHARTS'] = $this->helper->getCountUserForChart($this->arResult['DAY_ARR']);
            $this->arResult['NEW_USER_CNT'] = $this->helper->getNewUser($this->arResult['DATA_FROM'], $this->arResult['DATA_TO']);
            $this->arResult['ALL_USER_CNT'] = $this->helper->getAllUserCount($this->arResult['DATA_FROM'], $this->arResult['DATA_TO']);
            $this->arResult['STATICTIC_DATA'] = $this->helper->getCountStatistic($this->arResult['DATA_FROM'], $this->arResult['DATA_TO']);
            $this->arResult['USER_STATISTIC'] = $this->helper->getUserStatistic($this->arResult['DATA_FROM'], $this->arResult['DATA_TO']);

            $cntPage = $this->arResult['STATICTIC_DATA']['CNT_PAGE'] ?: 1;
            $cntSession = $this->arResult['STATICTIC_DATA']['CNT_SESSION'] ?: 1;
            $cntUser = $this->arResult['ALL_USER_CNT'] ?: 1;

            $this->arResult['ANALITIK_PAGES'] = $this->helper->getStatisticPages($this->arResult['DATA_FROM'], $this->arResult['DATA_TO'], $this->arResult['STATICTIC_DATA']['CNT']);
            $this->arResult['SESSION_ON_USER'] = $this->helper->CalculateSessionOnUser($cntSession, $cntUser); 
            $this->arResult['PAGES_FOR_SESSION'] = $this->helper->calculatePageForSession($cntPage, $cntSession);

            $this->includeComponentTemplate();
        }
    }
}