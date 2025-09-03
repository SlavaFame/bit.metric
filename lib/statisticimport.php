<?php
namespace Bit\Metric;

use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Service\GeoIp\Manager;
use CSession;
use Bitrix\Main\Type;
use CUser;

class StatisticImport
{
    public static function run()
    {
        global $APPLICATION;

        $sessions = CSession::GetList();
        while ($session = $sessions->Fetch()) {
            $url = parse_url($session["URL_LAST"]);
            if (str_contains($url['path'], '/bitrix/admin')) {
                continue;
            }
            $title = $url['path'];
            $url = $url['path'] . '?' . $url['query'];
            $agent = $session['USER_AGENT'];
            $ip = $session['IP_LAST'];
            $pageID = Logic::getPageID($url, SITE_ID, $title);
            $userID = $session['USER_ID'];
            $user = \Bitrix\Main\UserTable::getById($userID)->fetch();
            $userName = implode(' ', [$user['LAST_NAME'], $user['NAME'], $user['SECOND_NAME']]);
            $userEmail = $user['EMAIL'];
            $uid = $user['BX_USER_ID'] ?? Logic::getUID();

            for ($sessionCounter = 0; $sessionCounter < $session['HITS']; $sessionCounter++) {
                if (empty($existUserUID)) {
                    $isNewUser = UserTable::getList([
                        'filter' => [
                            'IP' => $ip,
                            'INFO' => $agent,
                        ]
                    ])->fetch();

                    if (!empty($UserUID) && $UserUID !== 0) {
                        UserTable::add([
                            'LID' => SITE_ID,
                            'UID' => $uid,
                            'IP' => $ip,
                            'DATE_VISIT' => $session['DATE_LAST'],
                            'ISNEW' => empty($isNewUser) ? '1' : '-1',
                            'USER_ID_CRM' => $userID,
                            'INFO' => $agent,
                            'COUNTRY' => 'Россия',
                        ]);
                    }
                }
                $from = new Type\DateTime($session['DATE_FIRST']);
                $to = new Type\DateTime($session['DATE_LAST']);

                $CheckAddUser = UserDetailTable::GetList([
                    'filter' => [
                        'USER_KEY' => $userEmail,
                        '>=DATE_CREATE' => $from,
                        '<=DATE_CREATE' => $to,
                    ],
                ])->fetch();

                if (!empty($CheckAddUser)) {
                    UserDetailTable::update($CheckAddUser['ID'], [
                        'DATE_CREATE' => $to,
                    ]);
                } else {
                    UserDetailTable::add([
                        'USER_KEY' => $userEmail,
                        'USER_ID' => $userID,
                        'NAME' => $userName,
                        'EMAIL' => $userEmail,
                        'PHONE' => '',
                        'SETTINGS' => SITE_ID,
                        'DATE_CREATE' => $to,
                        'UID' => $uid,
                    ]);
                }

                if (empty($idPage)) {
                    PagesTable::add([
                        'LID' => SITE_ID,
                        'URL' => $url,
                        'TITLE' => $title,
                        'TYPE' => 'Страница',
                    ]);
                    $idPage = Logic::getPageID($url);
                }

                if (!(strpos($url, 'jax') >= 1 || strpos($url, 'rest') >= 1 || strpos($url, 'pload') >= 1)) {
                    \Bit\Metric\StatisticTable::add([
                        'UID' => $uid,
                        'PAGE_ID' => $idPage,
                        'DATE_VISIT_BEGIN' => $from,
                        'DATE_VISIT_END' => $to,
                        'COME_FROM' => $ip,
                        'SETTINGS' => SITE_ID,
                    ]);
                }
            }
        }
    }
}