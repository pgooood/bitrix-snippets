<?php

class forumedia_lk extends CModule{
	function __construct(){
		$arModuleVersion = [];
		include(__DIR__.'/version.php');
		$this->MODULE_ID = 'forumedia.lk';
		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		$this->MODULE_NAME = 'Личный кабинет';
		$this->MODULE_DESCRIPTION = 'Управление личным кабинетом';
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