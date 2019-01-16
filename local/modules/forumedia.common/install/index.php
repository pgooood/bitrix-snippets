<?php

class forumedia_common extends CModule{
	function __construct(){
		$arModuleVersion = [];
		include(__DIR__.'/version.php');
		$this->MODULE_ID = 'forumedia.common';
		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		$this->MODULE_NAME = 'Основные инструменты';
		$this->MODULE_DESCRIPTION = '';
		$this->PARTNER_NAME = 'Forumedia';
		$this->PARTNER_URI = 'http://forumedia.ru/';
	}
	
	function DoInstall(){
		\Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
	}
	
	function DoUninstall(){
		\Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
	}
}