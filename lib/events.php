<?php
namespace Bit\Metric; 

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

class Events {

    public static function OnAfterEpilog()
    {
        try
        {
            \Bit\Metric\Module::addMetric();
        }
        catch (SystemException $e)
        {
            return false;
        }
    }
    public static function OnEpilog()
    {
    }   
}