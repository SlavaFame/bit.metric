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

class BitMetricHelper
{
    public function checkModules()
    {
        if (!Loader::includeModule('bit.metric')) {
            ShowError(Loc::getMessage('chelbit_metricA_MODULE_NOT_INSTALLED'));
            return false;
        }
        return true;
    } 

    public function GetUserDetailForPages($DateFrom, $DateTo) 
    { 
        $UserArr = \Bit\Metric\UserDetailTable::getList([
            'select' => ['DATE_CREATE', 'NAME', 'EMAIL', 'USER'],
            'filter' => [
                '>=DATE_CREATE' => $DateFrom . ' 00:00:00',
                '<=DATE_CREATE' => $DateTo . ' 23:59:59',
                '=SETTINGS' => SITE_ID,
                '!USER.EXTERNAL_AUTH_ID' => false
            ],
            'runtime' => [
                "USER" => new Bitrix\Main\Entity\ReferenceField(
                    'USER',
                    Bitrix\Main\UserTable::getEntity(),
                    Bitrix\Main\ORM\Query\Join::on('ref.ID', 'this.USER_ID'),
                    ['join_type' => Bitrix\Main\ORM\Query\Join::TYPE_LEFT]
                )
            ]
        ])->fetchAll();

        return $UserArr;
    }

    public function GetNewUser($DateFrom, $DateTo)
    {
        $NewUserAr = \Bit\Metric\UserDetailTable::getList([
            'select' => ['CNT', 'USER_EXTERNAL_AUTH_ID' => 'USER.EXTERNAL_AUTH_ID'],
            'filter' => [
                '>=DATE_CREATE' => $DateFrom . ' 00:00:01',
                '<=DATE_CREATE' => $DateTo . ' 23:59:59',
                '=SETTINGS' => SITE_ID,
                '!USER_EXTERNAL_AUTH_ID' => false
            ],
            'runtime' => [
                new Main\Entity\ExpressionField('CNT', 'COUNT(DISTINCT `USER_ID`)'),
                new Bitrix\Main\Entity\ReferenceField(
                    'USER',
                    Bitrix\Main\UserTable::getEntity(),
                    Bitrix\Main\ORM\Query\Join::on('ref.ID', 'this.USER_ID'),
                    ['join_type' => Bitrix\Main\ORM\Query\Join::TYPE_LEFT]
                )
            ],
        ])->fetch();

        return $NewUserAr['CNT'];
    }

    public function GetCountStatistic($DateFrom, $DateTo)
    {
        $TodayInFilter = false;

        $cacheTtl = 3600;
        $cacheId = md5(serialize('Statistic' . $DateFrom . $DateTo . SITE_ID));
        $cacheDir = '/bitmetric';

        $now = new DateTime('now');
        $now = $now->format('d.m.Y');

        if ($DateTo == $now && $DateFrom != $now) {
            //Если в фильтре присутствует сегодняшний день
            $DateTo = date_modify(new DateTime($DateTo), '-1 day');
            $DateTo = date_format($DateTo, 'd.m.Y');
            $TodayInFilter = true;
        }

        $cache = Cache::createInstance();
        $taggedCache = Application::getInstance()->getTaggedCache();

        $PagesCount = [];

        if ($cache->initCache($cacheTtl, $cacheId, $cacheDir)) {
            $PagesCount = $cache->getVars();
            if ($TodayInFilter) {
                $DateFrom = date_format(new DateTime($now), "d.m.Y") . ' 03:00:00';
                $DateTo = date_format(new DateTime($now), "d.m.Y") . ' 23:59:59';

                $PagesCountToday = \Bit\Metric\StatisticTable::getList([
                    'select' => ['CNT_PAGE', 'CNT_SESSION', 'CNT'],
                    'filter' => [
                        '>=DATE_VISIT_BEGIN' => $DateFrom . ' 00:00:00',
                        '<=DATE_VISIT_END' => $DateTo . ' 23:59:59',
                        'SETTINGS' => SITE_ID
                    ],
                    'runtime' => [
                        new Main\Entity\ExpressionField('CNT_PAGE', 'COUNT(DISTINCT `PAGE_ID`)'),
                        new Main\Entity\ExpressionField('CNT_SESSION', 'COUNT(DISTINCT `UID`)'),
                        new Main\Entity\ExpressionField('CNT', 'COUNT(*)'),
                    ],
                    'cache' => [
                        'ttl' => 3600,
                        'cache_joins' => true,
                    ],
                ])->fetch();
                $PagesCount['CNT_PAGE'] += $PagesCountToday['CNT_PAGE'];
                $PagesCount['CNT_SESSION'] += $PagesCountToday['CNT_SESSION'];
                $PagesCount['CNT'] += $PagesCountToday['CNT'];
            }

            return $PagesCount;
        } elseif ($cache->startDataCache()) {

            $taggedCache->startTagCache($cacheDir);
            $taggedCache->registerTag('myTag');

            $PagesCount = \Bit\Metric\StatisticTable::getList([
                'select' => ['CNT_PAGE', 'CNT_SESSION', 'CNT'],
                'filter' => [
                    '>=DATE_VISIT_BEGIN' => $DateFrom . ' 00:00:00',
                    '<=DATE_VISIT_END' => $DateTo . ' 23:59:59',
                    'SETTINGS' => SITE_ID,
                ],
                'runtime' => [
                    new Main\Entity\ExpressionField('CNT_PAGE', 'COUNT(DISTINCT `PAGE_ID`)'),
                    new Main\Entity\ExpressionField('CNT_SESSION', 'COUNT(DISTINCT `UID`)'),
                    new Main\Entity\ExpressionField('CNT', 'COUNT(*)'),
                ],
                'cache' => [
                    'ttl' => 3600,
                    'cache_joins' => true,
                ],
            ])->fetch();

            if ($TodayInFilter) {
                $DateFrom = date_format(new DateTime($now), "d.m.Y") . ' 03:00:00';
                $DateTo = date_format(new DateTime($now), "d.m.Y") . ' 23:59:59';

                $PagesCountToday = \Bit\Metric\StatisticTable::getList([
                    'select' => ['CNT_PAGE', 'CNT_SESSION', 'CNT'],
                    'filter' => [
                        '>=DATE_VISIT_BEGIN' => $DateFrom . ' 00:00:00',
                        '<=DATE_VISIT_END' => $DateTo . ' 23:59:59',
                        'SETTINGS' => SITE_ID
                    ],
                    'runtime' => [
                        new Main\Entity\ExpressionField('CNT_PAGE', 'COUNT(DISTINCT `PAGE_ID`)'),
                        new Main\Entity\ExpressionField('CNT_SESSION', 'COUNT(DISTINCT `UID`)'),
                        new Main\Entity\ExpressionField('CNT', 'COUNT(*)'),
                    ],
                    'cache' => [
                        'ttl' => 3600,
                        'cache_joins' => true,
                    ],
                ])->fetch();
                $PagesCount['CNT_PAGE'] += $PagesCountToday['CNT_PAGE'];
                $PagesCount['CNT_SESSION'] += $PagesCountToday['CNT_SESSION'];
                $PagesCount['CNT'] += $PagesCountToday['CNT'];
            }
            $taggedCache->endTagCache();
            $cache->endDataCache($PagesCount);
        }

        return $PagesCount;
    }

    public function getUserStatistic(string $dateFrom, string $dateTo)
    {
        $dateFrom = new DateTime($dateFrom);
        $dateTo = new DateTime($dateTo);

        $cacheParams = [
            'ttl' => 3600,
            'id' => md5(serialize('userstat' . $dateFrom->format('Ymd') . $dateTo->format('Ymd') . SITE_ID)),
            'dir' => '/bitmetric'
        ];

        $cache = Cache::createInstance();
        $taggedCache = Application::getInstance()->getTaggedCache();

        if ($cache->initCache($cacheParams['ttl'], $cacheParams['id'], $cacheParams['dir'])) {
            return $cache->getVars();
        } elseif ($cache->startDataCache()) {
            $taggedCache->startTagCache($cacheParams['dir']);
            $taggedCache->registerTag('userstat');

            $userStats = UserStatTable::getList([
                'select' => [
                    new Entity\ExpressionField('LIKES_SUM', 'SUM(%s)', 'LIKES'),
                    new Entity\ExpressionField('POSTS_SUM', 'SUM(%s)', 'POSTS'),
                    new Entity\ExpressionField('POSTS_VIEWS_SUM', 'SUM(%s)', 'POSTS_VIEWS'),
                    new Entity\ExpressionField('COMMENTS_SUM', 'SUM(%s)', 'COMMENTS'),
                    new Entity\ExpressionField('TASKS_SUM', 'SUM(%s)', 'TASKS'),
                    new Entity\ExpressionField('TASKS_FINISHED_SUM', 'SUM(%s)', 'TASKS_FINISHED'),
                    new Entity\ExpressionField('EVENTS_SUM', 'SUM(%s)', 'EVENTS'),
                    new Entity\ExpressionField('SURVEYS_SUM', 'SUM(%s)', 'SURVEYS'),
                    new Entity\ExpressionField('MESSAGES_SUM', 'SUM(%s)', 'MESSAGES'),
                    new Entity\ExpressionField('GROUPS_SUM', 'SUM(%s)', 'GROUPS'),
                ],
                'filter' => [
                    '>=DAY' => ConvertTimeStamp($dateFrom->getTimestamp()),
                    '<=DAY' => ConvertTimeStamp($dateTo->getTimestamp()),
                    '!USER.EXTERNAL_AUTH_ID' => false
                ],
                'runtime' => [
                    "USER" => new Bitrix\Main\Entity\ReferenceField(
                        'USER',
                        Bitrix\Main\UserTable::getEntity(),
                        Bitrix\Main\ORM\Query\Join::on('ref.ID', 'this.USER_ID'),
                        ['join_type' => Bitrix\Main\ORM\Query\Join::TYPE_LEFT]
                    )

                ]
            ])->fetch();

            $taggedCache->endTagCache();
            $cache->endDataCache($userStats);
        }

        return $userStats;
    }

    public function GetAllUserCount($DateFrom, $DateTo)
    {
        $UserCount = \Bit\Metric\UserTable::getList([
            'select' => ['CNT'],
            'filter' => [
                '>=DATE_VISIT' => $DateFrom,
                '<=DATE_VISIT' => $DateTo,
                '=LID' => SITE_ID,
                '!USER.EXTERNAL_AUTH_ID' => false
            ],
            'runtime' => [
                new Main\Entity\ExpressionField('CNT', 'COUNT(*)'),
                new Bitrix\Main\Entity\ReferenceField(
                    'USER',
                    Bitrix\Main\UserTable::getEntity(),
                    Bitrix\Main\ORM\Query\Join::on('ref.ID', 'this.USER_ID_CRM'),
                    ['join_type' => Bitrix\Main\ORM\Query\Join::TYPE_LEFT]
                )
            ],
        ])->fetch();

        return $UserCount['CNT'];
    }

    public function GetCountUserForChart($DateArr)
    {
        foreach ($DateArr as $DateTime) {
            $UserCountForCharts = \Bit\Metric\UserTable::getList([
                'select' => ['CNT'],
                'order' => ['DATE_VISIT' => 'ASC'],
                'filter' => [
                    '=DATE_VISIT' => $DateTime,
                    '=LID' => SITE_ID
                ],
                'runtime' => [
                    new Main\Entity\ExpressionField('CNT', 'COUNT(*)')
                ],
            ])->fetch();
            if (empty($UserCountForCharts['CNT'])) {
                $UserCountForCharts['CNT'] = 0;
            }
            $ChartsData[] = $UserCountForCharts['CNT'];
        }

        return $ChartsData;
    }

    public function GetStatisticPages($DateFrom, $DateTo, $CntPage)
    { 
        $cacheTtl = 3600;
        $cacheId = md5(serialize('Analitik' . $DateFrom . $DateTo . SITE_ID));
        $cacheDir = '/bitmetric';

        $TodayInFilter = false;
        $idSite = SITE_ID;
        $now = new DateTime('now');
        $now = $now->format('d.m.Y');

        if ($DateTo == $now && $DateFrom != $now) { //Если в фильтре присутствует сегодняшний день

            $DateTo = date_modify(new DateTime($DateTo), '-1 day');
            $DateTo = date_format($DateTo, 'Y-m-d');
            $TodayInFilter = true;
        }


        $DateFrom = date_format(new DateTime($DateFrom), "Y-m-d") . ' 00:00:00';
        $DateTo = date_format(new DateTime($DateTo), "Y-m-d") . ' 23:59:59';

        $cache = Cache::createInstance();
        $taggedCache = Application::getInstance()->getTaggedCache();

        if ($cache->initCache($cacheTtl, $cacheId, $cacheDir)) {

            $AnalitikData = $cache->getVars();
            if ($TodayInFilter) {
                $DateFrom = date_format(new DateTime($now), "Y-m-d") . ' 03:00:00';
                $DateTo = date_format(new DateTime($now), "Y-m-d") . ' 23:59:59'; 

                $AnalitikDataToday = Main\Application::getConnection()->query('
                    SELECT chelbit_metric_pages.TITLE, chelbit_metric_pages.URL, COUNT(DISTINCT chelbit_metric_statistic.UID) AS USER_CNT_ON_PAGE, 
                           TIMESTAMPDIFF(SECOND, chelbit_metric_statistic.DATE_VISIT_BEGIN,chelbit_metric_statistic.DATE_VISIT_END) AS TIME_ON_PAGE
                    FROM chelbit_metric_statistic
                    LEFT JOIN chelbit_metric_pages ON chelbit_metric_pages.ID = chelbit_metric_statistic.PAGE_ID 
                    WHERE chelbit_metric_statistic.DATE_VISIT_BEGIN >= ' . "'$DateFrom'" . '
                        AND chelbit_metric_statistic.DATE_VISIT_END <= ' . "'$DateTo'" . '
                        AND chelbit_metric_statistic.SETTINGS = ' . "'$idSite'" . '
                    GROUP BY chelbit_metric_pages.ID, chelbit_metric_statistic.PAGE_ID
                    ')->fetchAll();

                $cnt = 0;
                $totalTime = 0;
                foreach ($AnalitikDataToday as $PageData) {
                    $AnalitikDataToday[$cnt]['TITLE'] = $PageData['TITLE'];
                    $AnalitikDataToday[$cnt]['URL'] = $PageData['URL'];
                    $AnalitikDataToday[$cnt]['USER_CNT_ON_PAGE'] = $PageData['USER_CNT_ON_PAGE'];
                    $totalTime += $PageData['TIME_ON_PAGE'];
                    $cnt += 1;
                }

                $AVG_TIME_TODAY = intval($totalTime / $CntPage);

                $AnalitikData = array_merge($AnalitikData, $AnalitikDataToday);
            }

            return $AnalitikData;

        } elseif ($cache->startDataCache()) {

            $taggedCache->startTagCache($cacheDir);
            $taggedCache->registerTag('myTag');

            $AnalitikData = Main\Application::getConnection()->query('
                    SELECT chelbit_metric_pages.TITLE, chelbit_metric_pages.URL, COUNT(DISTINCT chelbit_metric_statistic.UID) AS USER_CNT_ON_PAGE, 
                           TIMESTAMPDIFF(SECOND, chelbit_metric_statistic.DATE_VISIT_BEGIN,chelbit_metric_statistic.DATE_VISIT_END) AS TIME_ON_PAGE
                    FROM chelbit_metric_statistic
                    LEFT JOIN chelbit_metric_pages ON chelbit_metric_pages.ID = chelbit_metric_statistic.PAGE_ID 
                    WHERE chelbit_metric_statistic.DATE_VISIT_BEGIN >= ' . "'$DateFrom'" . '
                        AND chelbit_metric_statistic.DATE_VISIT_END <= ' . "'$DateTo'" . '
                        AND chelbit_metric_statistic.SETTINGS = ' . "'$idSite'" . '
                    GROUP BY chelbit_metric_pages.ID, chelbit_metric_statistic.PAGE_ID
                    ')->fetchAll();

            $cnt = 0;
            $totalTime = 0;
            foreach ($AnalitikData as $PageData) {
                $AnalitikData[$cnt]['TITLE'] = $PageData['TITLE'];
                $AnalitikData[$cnt]['URL'] = $PageData['URL'];
                $AnalitikData[$cnt]['USER_CNT_ON_PAGE'] = $PageData['USER_CNT_ON_PAGE'];
                $totalTime += $PageData['TIME_ON_PAGE'];
                $cnt += 1;
            }

            if ($CntPage != 0) {
                $AVG_TIME_CASHE = intval($totalTime / $CntPage);
            } else {
                $AVG_TIME_CASHE = intval($totalTime / 30); 
            }

            if ($TodayInFilter) {

                $DateFrom = date_format(new DateTime($now), "Y-m-d") . ' 00:00:00';
                $DateTo = date_format(new DateTime($now), "Y-m-d") . ' 23:59:59';

                $AnalitikDataToday = Main\Application::getConnection()->query('
                    SELECT chelbit_metric_pages.TITLE, chelbit_metric_pages.URL, COUNT(DISTINCT chelbit_metric_statistic.UID) AS USER_CNT_ON_PAGE, 
                           TIMESTAMPDIFF(SECOND, chelbit_metric_statistic.DATE_VISIT_BEGIN,chelbit_metric_statistic.DATE_VISIT_END) AS TIME_ON_PAGE
                    FROM chelbit_metric_statistic
                    LEFT JOIN chelbit_metric_pages ON chelbit_metric_pages.ID = chelbit_metric_statistic.PAGE_ID 
                    WHERE chelbit_metric_statistic.DATE_VISIT_BEGIN >= ' . "'$DateFrom'" . '
                        AND chelbit_metric_statistic.DATE_VISIT_END <= ' . "'$DateTo'" . '
                        AND chelbit_metric_statistic.SETTINGS = ' . "'$idSite'" . '
                    GROUP BY chelbit_metric_pages.ID, chelbit_metric_statistic.PAGE_ID
                    ')->fetchAll();

                $cnt = 0;
                $totalTime = 0;
                foreach ($AnalitikDataToday as $PageData) {
                    $AnalitikDataToday[$cnt]['TITLE'] = $PageData['TITLE'];
                    $AnalitikDataToday[$cnt]['URL'] = $PageData['URL'];
                    $AnalitikDataToday[$cnt]['USER_CNT_ON_PAGE'] = $PageData['USER_CNT_ON_PAGE'];
                    $totalTime += $PageData['TIME_ON_PAGE'];
                    $cnt += 1;
                }

                $AVG_TIME_TODAY = intval($totalTime / $CntPage);

                $AnalitikData = array_merge($AnalitikData, $AnalitikDataToday);

            }
            $ALL_AVG_TIME = $AVG_TIME_TODAY + $AVG_TIME_CASHE;
            $AnalitikData['AVG_TIME'] = gmdate("H:i:s", intval($ALL_AVG_TIME));


            $taggedCache->endTagCache();
            $cache->endDataCache($AnalitikData);
        }

        return $AnalitikData;
    }

    public function CalculateSessionOnUser($CntSission = 1, $CntUser = 1)
    {
        return number_format(floatval($CntSission) / floatval($CntUser), 2);
    }

    public function CalculatePageForSession($CntPages = 1, $CntSession = 1)
    {
        if ($CntSession == 0) {
            // Обработка случая деления на ноль
            return 0; // Или любое другое значение, которое имеет смысл в вашем контексте
        }
        return number_format(floatval($CntPages) / floatval($CntSession), 2); 
    } 

    public function getDates($startTime, $endTime)
    {
        $day = 86400;
        $format = 'd.m.Y';
        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime);
        $numDays = round(($endTime - $startTime) / $day) + 1;

        $days = array();

        for ($i = 0; $i < $numDays; $i++) {
            $days[] = date($format, ($startTime + ($i * $day)));
        }

        return $days;

    }
 
}