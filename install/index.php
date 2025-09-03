<?php
use Bit\Metric\StatisticImport;
use Bit\Metric\UserStatTable;
use Bit\Metric\UserStat;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config as Conf;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Entity\Base;
use \Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

Class bit_metric extends CModule
{
    var $exclusionAdminFiles;

    function __construct()
    {
        $arModuleVersion = array();
        include(__DIR__."/version.php");

        $this->exclusionAdminFiles=array(
            '..',
            '.',
            'menu.php',
            'operation_description.php',
            'task_description.php'
        );

        $this->MODULE_ID = 'bit.metric';
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = Loc::getMessage("BIT_METRICA_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("BIT_METRICA_MODULE_DESC");

        $this->PARTNER_NAME = Loc::getMessage("BIT_METRICA_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("BIT_METRICA_PARTNER_URI");

        $this->MODULE_SORT = 1;
        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS='Y';
        $this->MODULE_GROUP_RIGHTS = "Y";
    }
    
    //Определяем место размещения модуля
    public function GetPath($notDocumentRoot=false)
    {
        if($notDocumentRoot)
            return str_ireplace(Application::getDocumentRoot(),'',dirname(__DIR__));
        else
            return dirname(__DIR__);
    }

    //Проверяем что система поддерживает D7
    public function isVersionD7()
    {
        return CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'), '14.00.00');
    }

    function InstallDB()
    {
        Loader::includeModule($this->MODULE_ID);

        $tables = [
            '\Bit\Metric\PagesTable',
            '\Bit\Metric\StatisticTable',
            '\Bit\Metric\UserDetailTable',
            '\Bit\Metric\UserTable',
            UserStatTable::class,
        ];

        foreach ($tables as $tableClass) {
            $tableName = Base::getInstance($tableClass)->getDBTableName();
            $newTableName = 'chelbit_' . substr($tableName, strpos($tableName, '_') + 1);

            if (!Application::getConnection($tableClass::getConnectionName())->isTableExists($newTableName)) {
                if (Application::getConnection($tableClass::getConnectionName())->isTableExists($tableName)) {
                    // Проверяем, есть ли старая таблица без добавления 'chel'
                    Application::getConnection($tableClass::getConnectionName())->queryExecute('RENAME TABLE ' . $tableName . ' TO ' . $newTableName);
                } else {
                    // Создаем новую таблицу с именем 'chelbit_metric_'
                    $entity = Base::getInstance($tableClass);
                    $entity->createDbTable($newTableName);
                }
            }
        }
    }

    function UnInstallDB()
    {
        Loader::includeModule($this->MODULE_ID);

        $tables = [
            '\Bit\Metric\PagesTable',
            '\Bit\Metric\StatisticTable',
            '\Bit\Metric\UserDetailTable',
            '\Bit\Metric\UserTable',
            UserStatTable::class,
        ];

        foreach ($tables as $tableClass) { 
            $tableName = 'chelbit_' . substr(Base::getInstance($tableClass)->getDBTableName(), strpos(Base::getInstance($tableClass)->getDBTableName(), '_') + 1);

            Application::getConnection($tableClass::getConnectionName())->queryExecute('drop table if exists ' . $tableName);

            // Проверяем, есть ли старая таблица без добавления 'chel'
            $oldTableName = Base::getInstance($tableClass)->getDBTableName();
            if (Application::getConnection($tableClass::getConnectionName())->isTableExists($oldTableName)) {
                Application::getConnection($tableClass::getConnectionName())->queryExecute('drop table if exists ' . $oldTableName);
            }
        }

        Option::delete($this->MODULE_ID);
    } 
    
    function InstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler("main", "OnAfterEpilog", $this->MODULE_ID, "\\Bit\\Metric\\Events", "OnAfterEpilog", 10000);
    }


    function UnInstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->unRegisterEventHandler("main", "OnAfterEpilog", $this->MODULE_ID, "\\Bit\\Metric\\Events", "OnAfterEpilog", 10000);
        $eventManager->unRegisterEventHandler("main", "OnEpilog", $this->MODULE_ID, "\\Bit\\Metric\\Events", "OnEpilog", 10000);
    }

    function InstallFiles($arParams = array())
    {
        $path=$this->GetPath()."/install/components";
        $path2=$this->GetPath()."/install/path";

        CopyDirFiles($path, $_SERVER["DOCUMENT_ROOT"]."/local/components", true, true);
        CopyDirFiles($path2, $_SERVER["DOCUMENT_ROOT"]."/", true, true);

        return true;
    }

    function UnInstallFiles()
    {
        \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . '/local/components/bit/metric.show');
        \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . '/bit_metric_show');

        return true;
    }

    function InstallAgents()
    {
		\CAgent::AddAgent(
            "\Bit\Metric\Agents::DelOldData();", 
            $this->MODULE_ID, 
            "N", 
            86400,
			'',
			'Y'
        );

        \CAgent::AddAgent(
            "\Bit\Metric\Agents::importUserStat();",
            $this->MODULE_ID,
            "N", // Агент не критичен к количеству запусков
            86400, // Интервал запуска в секундах (например, раз в день)
			'', // Дата первой проверки на запуск
			'Y' // Агент активен
        );
    }

    function UnInstallAgents()
    {
        \CAgent::RemoveModuleAgents($this->MODULE_ID);
    }

    function DoInstall()
    {
        global $APPLICATION;
        if($this->isVersionD7())
        {
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);

            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();
            $this->InstallAgents();
            
        }
        else
        {
            $APPLICATION->ThrowException(Loc::getMessage("BIT_METRICA_INSTALL_ERROR_VERSION"));
        }

        $APPLICATION->IncludeAdminFile(Loc::getMessage("BIT_METRICA_INSTALL_TITLE"), $this->GetPath()."/install/step.php");

    }

    function DoUninstall()
    {
        global $APPLICATION;

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        if($request["step"]<2)
        {
            $APPLICATION->IncludeAdminFile(Loc::getMessage("BIT_METRICA_UNINSTALL_TITLE"), $this->GetPath()."/install/unstep1.php");
        }
        elseif($request["step"]==2)
        {
            $this->UnInstallFiles();
            $this->UnInstallEvents();
            $this->UnInstallAgents();

            if($request["savedata"] != "Y")
                $this->UnInstallDB();

            \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(Loc::getMessage("BIT_METRICA_UNINSTALL_TITLE"), $this->GetPath()."/install/unstep2.php");
        }
    }

}