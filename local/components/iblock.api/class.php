<?php
/**
 * @author: Pavel Khoroshkov <me@pgood.space>
 */ 

class pgoodIblockApi extends \CBitrixComponent{
	protected $request;

	function executeComponent(){
		if(!CModule::IncludeModule('forumedia.common'))
			throw new \Exception('forumedia.common module required');
		$this->request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$this->initParams();
		$this->arResult = [];
		
		// парсим урл
		$arVariables = [];
		$arUrlTemplates = \CComponentEngine::MakeComponentUrlTemplates(
			[
				'list' => '?ib=#IBLOCK_CODE#'
				,'detail' => '?ib=#IBLOCK_CODE#&el=#ELEMENT_ID#'
				,'groups' => '?ib=#IBLOCK_CODE#'
			]
			,$this->arParams['SEF_URL_TEMPLATES']);
		$page = \CComponentEngine::ParseComponentPath(
			$this->arParams['SEF_FOLDER']
			,$arUrlTemplates
			,$arVariables);
		
		// получаем данные
		$error = null;
		try{
			$this->auth();
			$arFilter = ['ACTIVE' => 'Y'];
			$ib = new \forumedia\common\iblock($arVariables['IBLOCK_CODE']);
			$this->checkPermission($ib);
			switch($page){
				case 'detail':
					if(1 > ($id = intval($arVariables['ELEMENT_ID'])))
						throw new \Exception('Invalid element id');
					$arFilter['ID'] = $id;
					$arRes = $this->itemList($ib,$arFilter);
					if(empty($arRes['items']))
						throw new \Exception('element ['.$id.'] not found');
					$this->arResult = array_shift($arRes['items']);
					break;
				case 'groups':
					$this->arResult = $this->groupsTree($ib);
					break;
				default:
					$this->arResult = $this->itemList($ib,$arFilter);
					break;
			}
		}catch(\Exception $ex){
			$error = $ex->getMessage();
		}
		
		\forumedia\common\utils::jsonResponse($this->arResult,$error);
	}
	
	protected function groupsTree(\forumedia\common\iblock $ib){
		$arResult = ['items' => []];
		$arFields = ['ID','CODE','IBLOCK_SECTION_ID','NAME','DESCRIPTION','DEPTH_LEVEL','ACTIVE'];
		$rs = \CIBlockSection::GetTreeList(['IBLOCK_ID' => $ib->id()],$arFields);
		while($r = $rs->Fetch()){
			$arItem = [];
			foreach($arFields as $name)
				if(strlen($r[$name]))
					$arItem[$name] = $r[$name];
			$arResult['items'][$r['ID']] = $arItem;
		}
		return $arResult;
	}
	
	protected function itemList(\forumedia\common\iblock $ib,$arFilter = []){
		$arResult = ['items' => []];
		$arSort = ['SORT' => 'ASC','ID' => 'ASC'];
		$arFieldsToGet = ['ID','CODE','NAME','DATE_CREATE','ACTIVE','ACTIVE_FROM','ACTIVE_TO'
			,'PREVIEW_TEXT','PREVIEW_TEXT_TYPE','DETAIL_TEXT','DETAIL_TEXT_TYPE'
			,'DETAIL_PICTURE','PREVIEW_PICTURE','TAGS'];
		$arSelect = array_merge($arFieldsToGet,['PROPERTY_*']);
		
		$rs = \CIBlockProperty::GetList(['sort'=>'asc'],['IBLOCK_ID' => $ib->id()]);
		while($r = $rs->Fetch())
			if('E' === $r['PROPERTY_TYPE'] && 'N' === $r['MULTIPLE']){
				$arSelect[] = 'PROPERTY_'.$r['CODE'].'.NAME';
				$arFieldsToGet[] = 'PROPERTY_'.$r['CODE'].'_NAME';
			}
		if(is_array($this->arParams['ADDITIONAL_FIELDS'])){
			$m = null;
			foreach($this->arParams['ADDITIONAL_FIELDS'] as $field)
				if(preg_match('/^[A-Z0-9_\.]+$/',$field)){
					$arSelect[] = $field;
					if(preg_match('/^(PROPERTY_.+)\.(PROPERTY_.+)$/',$field,$m))
						$arFieldsToGet[] = $m[1].'_'.$m[2].'_VALUE';
					else
						$arFieldsToGet[] = str_replace('.','_',$field);
				}
		}
		$rs = $ib->getList($arFilter,$arSelect,[
			'nav' => [
					'nPageSize' => $this->arParams['PAGE_SIZE']
					,'iNumPage' => $this->arParams['PAGEN']
				]
			,'sort' => $arSort
		]);
		$lastRow = null;
		while($r = $rs->GetNextElement())
			$arResult['items'][] = $this->itemData($lastRow = $r,$arFieldsToGet,[]);

		$arResult['numRows'] = intval($rs->NavRecordCount);
		$arResult['numPages'] = $rs->NavPageCount;
		$arResult['page'] = $rs->NavPageNomer;
		$arResult['pageSize'] = $rs->NavPageSize;
		$arResult['properties'] = $lastRow ? $this->itemProperties($lastRow,$arPropsToGet) : [];
		return $arResult;
	}
	
	protected function itemProperties(\_CIBElement $r,$arPropsToGet = []){
		$arResult = [];
		$arProps = $r->GetProperties();
		foreach($arProps as $name => $arProp)
			if(!$arPropsToGet || in_array($name,$arPropsToGet))
				$arResult[$arProp['CODE']] = [
					'ID' => $arProp['ID']
					,'CODE' => $arProp['CODE']
					,'NAME' => $arProp['NAME']
					,'PROPERTY_TYPE' => $arProp['PROPERTY_TYPE']
					,'MULTIPLE' => $arProp['MULTIPLE']
				];
		return $arResult;
	}
	
	protected function itemData(\_CIBElement $r,$arFieldsToGet = [],$arPropsToGet = []){
		$arItem = [];
		$m = null;
		$arFields = $r->GetFields();
		/*?><pre><?print_r($arFields);?></pre><?die;*/
		foreach($arFields as $name => $value)
			if(preg_match('/^~(.+)$/',$name,$m) && in_array($m[1],$arFieldsToGet))
				switch($m[1]){
					case 'DETAIL_PICTURE':
					case 'PREVIEW_PICTURE':
						if($value)
							$arItem[$m[1]] = \CFile::GetPath($value);
						break;
					default:
						$arItem[$m[1]] = $value;
						break;
				}
		
		$rsGroups = $r->GetGroups();
		if($rsGroups->SelectedRowsCount()){
			$arItem['GROUPS'] = [];
			while($arGroup = $rsGroups->Fetch()){
				$arItem['GROUPS'][] = [
					'ID' => $arGroup['ID']
					,'IBLOCK_SECTION_ID' => $arGroup['IBLOCK_SECTION_ID']
					,'NAME' => $arGroup['NAME']
					,'ACTIVE' => $arGroup['ACTIVE']
					,'DEPTH_LEVEL' => $arGroup['DEPTH_LEVEL']
				];
			}
		}
		$arProps = $r->GetProperties();
		foreach($arProps as $name => $arProp)
			if(!$arPropsToGet || in_array($name,$arPropsToGet))
				switch($arProp['PROPERTY_TYPE']){
					case 'F':
						if($arProp['~VALUE']){
							if('Y' === $arProp['MULTIPLE']){
								$arItem['PROPERTY_'.$arProp['CODE']] = [];
								foreach($arProp['~VALUE'] as $fileId)
									$arItem['PROPERTY_'.$arProp['CODE']][] = \CFile::GetPath($fileId);
							}else
								$arItem['PROPERTY_'.$arProp['CODE']] = \CFile::GetPath($arProp['~VALUE']);
						}
						break;
					case 'E':
						$arItem['PROPERTY_'.$arProp['CODE']] = $arProp['~VALUE'];
						break;
					default:
						$arItem['PROPERTY_'.$arProp['CODE']] = $arProp['~VALUE'];
						break;
				}
		return $arItem;
	}
	
	protected function auth(){
		$u = $GLOBALS['USER'];
		if(!$u->IsAuthorized()
			|| !empty($this->arParams['LOGIN'])
		){
			if(!strlen($this->arParams['LOGIN']))
				throw new \Exception('Username required');
			if(!strlen($this->arParams['PASSWORD']))
				throw new \Exception('Password required');
			if(true !== $u->Login($this->arParams['LOGIN'],$this->arParams['PASSWORD']))
				throw new \Exception('Invalid credentials');
		}
	}
	
	protected function checkPermission(\forumedia\common\iblock $ib){
		if($GLOBALS['USER']->IsAdmin())
			return true;
		$ob = new \CIBlockRights($ib->id());
		$arRights = $ob->GetRights(['operations' => ['iblock_export']]);
		$arGroups = ['U'.$GLOBALS['USER']->GetID()];
		$rs = CUser::GetUserGroupList($GLOBALS['USER']->GetID());
		while($r = $rs->Fetch())
			$arGroups[] = 'G'.$r['GROUP_ID'];
		foreach($arRights as $r)
			if(in_array($r['GROUP_CODE'],$arGroups))
				return true;
		throw new \Exception('Access denied');
	}
	
	protected function initParams(){
		if(empty($this->arParams['CACHE_TIME']))
			$this->arParams['CACHE_TIME'] = 3600;
		if(empty($this->arParams['PAGE_SIZE_MAX']))
			$this->arParams['PAGE_SIZE_MAX'] = 500;
		if(empty($this->arParams['PAGE_SIZE']))
			$this->arParams['PAGE_SIZE'] = 10;
		if($this->arParams['PAGE_SIZE'] > $this->arParams['PAGE_SIZE_MAX'])
			$this->arParams['PAGE_SIZE'] = $this->arParams['PAGE_SIZE_MAX'];
		if(empty($this->arParams['PAGEN']))
			$this->arParams['PAGEN'] = intval($this->request['page']);
		if(1 > $this->arParams['PAGEN'])
			$this->arParams['PAGEN'] = 1;
		if(empty($this->arParams['LOGIN']))
			$this->arParams['LOGIN'] = $this->request['user'];
		if(empty($this->arParams['PASSWORD']))
			$this->arParams['PASSWORD'] = $this->request['pass'];
	}

}
