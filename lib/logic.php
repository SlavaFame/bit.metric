<?php
namespace Bit\Metric;

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Application;
use \Bitrix\Main\SystemException;
use \Bitrix\Main\Type;

class Logic{

    public static function getUID() {

        if (!empty($_COOKIE['BX_USER_ID']) && preg_match('/^[0-9a-f]{32}$/', $_COOKIE['BX_USER_ID'])) {

            $uid = $_COOKIE['BX_USER_ID'];

        }else{

            if (!empty($_SESSION['BIT_BX_USER_ID'])) {

                $_COOKIE['BIT_BX_USER_ID'] = $_SESSION['BIT_BX_USER_ID'];
            }

            if (!empty($_COOKIE['BIT_BX_USER_ID'])) {

                $uid = $_COOKIE['BIT_BX_USER_ID'];
            }else{

                $uid = self::existUserUID();
                if(!$uid){
                    $uid = "bx" . md5(strval(time()) . \randString(40));
                }
            }
        }

        $_SESSION["BIT_BX_USER_ID"] = $uid;

        return $uid;

    }

    public static function updateDateVisitEnd( $uid ) {

        $arStat = \Bit\Metric\StatisticTable::getList([
            'filter'=>[
                'UID'=> $uid,
            ],
            'order'=>['ID'=>'DESC'],
            'limit' => 1,
        ])->fetch();
        if(!empty($arStat)){
            \Bit\Metric\StatisticTable::Update($arStat['ID'],[
                'DATE_VISIT_END' => new Type\Datetime(date('d.m.Y H:i:s'))
            ]);
        }
    }

    public static function existUserUID( $date_visit='', $ip='', $agent='' ) {

        $server = Application::getInstance()->getContext()->getServer();
        if(empty($ip)) {

            $ip = $server["REMOTE_ADDR"];
        }
        if(empty($agent)) {

            $agent = $server["HTTP_USER_AGENT"];
        }

        $arRow = \Bit\Metric\UserTable::getList([
            'filter' => [
                'DATE_VISIT' => new Type\Date(date('d.m.Y')),
                'IP' => $ip,
                'INFO' => $agent,
            ]
        ])->fetch();

        return $arRow["UID"];

    }

    public static function getPageID( $url, $siteId="", $title="" ) {

        if(empty($siteId)){

            $siteId = SITE_ID;
        }

        $arRow = \Bit\Metric\Pagestable::getList([
            'filter' =>[
                'LID' => $siteId,
                'URL' => $url,
                ]
        ])->fetch();

        return $arRow["ID"];

    }
}