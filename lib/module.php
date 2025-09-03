<?php
namespace Bit\Metric;

use Bitrix\Main\Engine\CurrentUser;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Application;
use Bitrix\Main\Service\GeoIp\Manager;
use Bitrix\Main\Type;
use \Bitrix\Main;

Loc::loadMessages(__FILE__);

class Module
{

    const MODULE_ID = 'bit.metric';
    const MODULE_ROOT_DIR = 'bitrix';

    public static function addMetric()
    {
        global $APPLICATION;

        $request = Application::getInstance()->getContext()->getRequest();

        if ($request->isAdminSection()) {
            return;
        }

        $server = Application::getInstance()->getContext()->getServer();

        $url = $APPLICATION->GetCurPage(true);
        $title = $APPLICATION->GetTitle();
        $agent = $server["HTTP_USER_AGENT"];
        $idPage = Logic::getPageID($url, SITE_ID, $title);
        $uid = Logic::getUID();

        if (class_exists('\Bitrix\Main\Service\GeoIp\Manager')) {
            $ip = Manager::getRealIp();
        } else {
            $ip = $server["REMOTE_ADDR"];
        }

        $day = new Type\Date(date('d.m.Y'));
        $existUserUID = Logic::existUserUID();
        $UserUID = CurrentUser::get()->getId();
        $UserName = CurrentUser::get()->getFormattedName();
        $UserEmail = CurrentUser::get()->getEmail();

        if (empty($existUserUID)) {
            $isNewUser = UserTable::getList([
                'filter' => [
                    'IP' => $ip,
                    'INFO' => $agent,
                ]
            ])->fetch();
            if (empty($isNewUser)) {
                $markerNewUser = '1';
            }
            if (!empty($isNewUser)) {
                $markerNewUser = '-1';
            }
            if (!empty($UserUID) && $UserUID !== 0) {
                UserTable::add([
                    'LID' => SITE_ID,
                    'UID' => $uid,
                    'IP' => $ip,
                    'DATE_VISIT' => new Type\Date(date('d.m.Y')),
                    'ISNEW' => $markerNewUser,
                    'USER_ID_CRM' => $UserUID,
                    'INFO' => $agent,
                    'COUNTRY' => 'Россия',
                ]);
            }
        }

        $todayFrom = date("d.m.Y 00:00:00");
        $todayTo = date("d.m.Y 23:59:50");

        $CheckAddUser = UserDetailTable::GetList([
            'filter' => [
                'USER_KEY' => $UserEmail,
                '>=DATE_CREATE' => $todayFrom,
                '<=DATE_CREATE' => $todayTo,
            ],
        ])->fetch();

        if (!empty($CheckAddUser)) {
            UserDetailTable::update($CheckAddUser['ID'], [
                'DATE_CREATE' => new Type\Datetime(date('d.m.Y H:i:s')),
            ]);
        } else {
            UserDetailTable::add([
                'USER_KEY' => $UserEmail,
                'USER_ID' => $UserUID,
                'NAME' => $UserName,
                'EMAIL' => $UserEmail,
                'PHONE' => '',
                'SETTINGS' => SITE_ID,
                'DATE_CREATE' => new Type\Datetime(date('d.m.Y H:i:s')),
                'UID' => $uid,
            ]);
        }

        Logic::updateDateVisitEnd($uid);
        if (empty($idPage)) {
            \Bit\Metric\PagesTable::add([
                'LID' => SITE_ID,
                'URL' => $url,
                'TITLE' => $title,
                'TYPE' => 'Страница',
            ]);
            $idPage = Logic::getPageID($url);
        }

        if (strpos($url, 'jax') >= 1 ||
            strpos($url, 'rest') >= 1 ||
            strpos($url, 'pload') >= 1) {
            //УРЛ не прошел валидацию, в статиску не записываем
        } else {
            \Bit\Metric\StatisticTable::add([
                'UID' => $uid,
                'PAGE_ID' => $idPage,
                'DATE_VISIT_BEGIN' => new Type\Datetime(date('d.m.Y H:i:s')),
                'DATE_VISIT_END' => new Type\Datetime(date('d.m.Y H:i:s')),
                'COME_FROM' => $ip,
                'SETTINGS' => SITE_ID,
            ]);
        }

    }
}