<?php

namespace forumedia\common;

/**
 * Класс обертка для _CIBElement
 * позваляет легко получать значения свойств и полей элемента инфоблока используя кэмл-кейс
 * облегчает работу со связями с другими ИБ, Hl, пользователями, файлами, списками
 *
 * @author Pavel Khoroshkov <pgood@forumedia.com>
 */
class iblockElement{
	protected $ibEl,$arFields,$arProperties;
	
	function __construct(?\_CIBElement $ibEl){
		$this->ob($ibEl);
	}
	
	/**
	 * @return \_CIBElement|null
	 */
	function ob(\_CIBElement $ibEl = null){
		if(null === $ibEl)
			return $this->ibEl;
		$this->ibEl = $ibEl;
		$this->arFields = $ibEl->GetFields();
		$this->arProps = $ibEl->GetProperties();
	}
	
	/**
	 * @return boolean
	 */
	function empty(){
		return empty($this->ibEl);
	}
	
	/**
	 * Универсальный метод для получения свойств и полей элемента инфоблока
	 * имя метода должно соответствовать имени свойства или поля, может быть формате camelCase
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	function __call($name,$arguments){
		$fieldName = strtoupper(self::toSnakeCase($name));
		if(isset($this->arProps[$fieldName])){
			$r = &$this->arProps[$fieldName];
			if('Y' === $r['MULTIPLE']){
				switch($r['PROPERTY_TYPE']){
					case 'E':
						return $r['~VALUE'] ? iblockElement::select(['ID' => $r['~VALUE']]) : [];
					case 'G':
						return $r['~VALUE'] ? iblockSection::select(['ID' => $r['~VALUE']]) : [];
					default:
						if('directory' === $r['USER_TYPE'])
							return $this->propValue($r,$v,$arguments);
						$arValues = [];
						foreach($r['~VALUE'] as $v)
							$arValues[] = $this->propValue($r,$v,$arguments);
						return $arValues;
				}
				
			}
			return $this->propValue($r,$r['~VALUE'],$arguments);
		}
		return $this->fieldValue($fieldName,$arguments);
	}
	
	protected function fieldValue($name,&$arguments){
		return $this->arFields[$name] ?? null;
	}
	
	protected function propValue(&$arProp,$value,&$arguments){
		switch($arProp['PROPERTY_TYPE']){
			case 'L':
				return [
					'ID' => $arProp['VALUE_ENUM_ID']
					,'CODE' => $arProp['VALUE_XML_ID']
					,'VALUE' => $arProp['~VALUE']
				];
			case 'E':
				return self::getById($value);
			case 'G':
				return iblockSection::getById($value,$arProp['LINK_IBLOCK_ID']);
			case 'F':
				return $value
					? ['ID' => $value,'PATH' => \CFile::GetPath($value)]
					: null;
			default:
				if('MULTIDATA' === $arProp['CODE'])
					return new multiProp($this->iblock(),'MULTIDATA',$this->ibEl);
				if('directory' === $arProp['USER_TYPE'])
					return new \forumedia\common\propHl($this->iblock(),'PRODUCTS',$this->ibEl);
				if('UserID' === $arProp['USER_TYPE'] && $value)
					return new \forumedia\common\user($value);
				if('HTML' === $arProp['USER_TYPE'] && $value)
					return $value['TEXT'];
				return $value;
		}
	}
	
	static function toSnakeCase($v){
		if(is_array($v))
			$v = $v[1].'_'.strtolower($v[2]).$v[3];
		return preg_replace_callback('/^([^A-Z].*)([A-Z])(.*)$/',[self,'toSnakeCase'],$v);
	}
	
	/**
	 * Обертка для CIBlockElement::GetList
	 * можно передавать IBLOCK_CODE вместо IBLOCK_ID
	 * @param type $arFilter
	 * @param type $arSelect
	 * @param type $arProps
	 * @return \self
	 * @throws \Exception
	 */
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
		$rs = \CIBlockElement::GetList(
				empty($arProps['group'])
					? ($arProps['sort'] ?? ['SORT' => 'ASC','ID' => 'DESC'])
					: null
				,$arFilter
				,$arProps['group'] ?? false
				,$arProps['nav'] ?? false
				,$arSelect
			);
		$className = get_called_class();
		while($ibEl = $rs->GetNextElement(false,false))
			$arRes[] = new $className($ibEl);
		return $arRes;
	}
	
	static function getFirst($arFilter,$arSelect = null,$arProps = []){
		return ($arEl = self::select($arFilter,$arSelect,array_merge(['nTopCount' => 1],$arProps)))
			? array_shift($arEl)
			: new self(null);
	}
	
	static function getById($id){
		return $id
			? self::getFirst(['ID' => $id])
			: new self(null);
	}
	
	function iblock(){
		if(\Bitrix\Main\Loader::includeModule('forumedia.common'))
			return new \forumedia\common\iblock($this->arFields['IBLOCK_ID']);
	}
	
}

