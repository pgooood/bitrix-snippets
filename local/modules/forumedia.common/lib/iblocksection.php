<?php

namespace forumedia\common;

/**
 * @author Pavel Khoroshkov <pgood@forumedia.com>
 */
class iblockSection{
	protected $arFields;
	
	function __construct(?\_CIBElement $ibEl){
		if($ibEl)
			$this->arFields = $ibEl->GetFields();
	}
	
	/**
	 * @return boolean
	 */
	function empty(){
		return empty($this->arFields);
	}
	
	function __call($name,$arguments){
		$fieldName = strtoupper(iblockElement::toSnakeCase($name));
		return $this->arFields[$fieldName] ?? $this->arFields['UF_'.$fieldName] ?? null;
	}
	
	static function select($arFilter,$arSelect = null,$arProps = null){
		if(!\Bitrix\Main\Loader::IncludeModule('iblock'))
			throw new \Exception('iblock module required');
		if(!\Bitrix\Main\Loader::includeModule('forumedia.common'))
			throw new \Exception('forumedia.common module required');
		if(!is_array($arFilter) || empty($arFilter))
			throw new \Exception('invalid filter value');
		if(!empty($arFilter['IBLOCK_CODE']) && empty($arFilter['IBLOCK_ID'])){
			$arFilter['IBLOCK_ID'] = \forumedia\common\iblock::findId($arFilter['IBLOCK_CODE']) ?: -1;
			unset($arFilter['IBLOCK_CODE']);
		}
		$arRes = [];
		$rs = \CIBlockSection::GetList(
				empty($arProps['sort']) ?? ['SORT' => 'ASC','ID' => 'DESC']
				,$arFilter
				,!empty($arProps['bIncCnt'])
				,$arSelect ?: ['UF_*']
				,$arProps['nav'] ?? false
			);
		while($ibEl = $rs->GetNextElement(false,false))
			$arRes[] = new self($ibEl);
		return $arRes;
	}
	
	static function getById($id,$iblockId = null){
		return $id
			? self::getFirst(['ID' => $id,'IBLOCK_ID' => $iblockId])
			: new self(null);
	}
	
	static function getFirst($arFilter,$arSelect = null,$arProps = null){
		return ($arEl = self::select($arFilter,$arSelect,$arProps))
			? array_shift($arEl)
			: new self(null);
	}
	
}
