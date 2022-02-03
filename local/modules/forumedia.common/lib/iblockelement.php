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
	protected $ibEl,$arProps;
	
	function __construct(?\_CIBElement $ibEl){
		$this->ibEl = $ibEl;
	}
	
	/**
	 * @return \_CIBElement|null
	 */
	function ob(\_CIBElement $ibEl = null){
		if(null === $ibEl)
			return $this->ibEl;
		$this->ibEl = $ibEl;
		unset($this->arProps);
	}
	
	/**
	 * Поля элемента
	 * @return array
	 */
	function fields(){
		return $this->ob()->GetFields();
	}
	
	/**
	 * Значение поля элемента по имени
	 * @param string $name
	 * @return mixed
	 */
	function field($name){
		$fieldName = strtoupper(self::toSnakeCase($name));
		$arFields = $this->fields();
		return $arFields['~'.$fieldName]
			?? $arFields[$fieldName]
			?? $arFields['~PROPERTY_'.$fieldName]
			?? $arFields['~PROPERTY_'.$fieldName.'_VALUE']
			?? null;
	}
	
	/**
	 * Свойства элемента
	 * @return array
	 */
	function props(){
		if(!isset($this->arProps))
			$this->arProps = $this->ob()->GetProperties();
		return $this->arProps ?? [];
	}
	
	/**
	 * Информация о свойстве и его значение
	 * @param string $name
	 * @return array
	 */
	function prop($name){
		return $this->props()[strtoupper(self::toSnakeCase($name))] ?? null;
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
		if($r = $this->prop($name)){
			if('Y' === $r['MULTIPLE']){
				if('raw' === $arguments[0] ?? null)
					return $r['~VALUE'];
				switch($r['PROPERTY_TYPE']){
					case 'E':
						return $r['~VALUE'] ? iblockElement::select(['ID' => $r['~VALUE']]) : [];
					case 'G':
						return $r['~VALUE'] ? iblockSection::select(['ID' => $r['~VALUE']]) : [];
					default:
						if('MULTIDATA' === $r['CODE'] || 'directory' === $r['USER_TYPE'])
							return $this->propValue($r,$this->ob(),$arguments);
						$arValues = [];
						foreach($r['~VALUE'] as $v)
							$arValues[] = $this->propValue($r,$v,$arguments);
						return $arValues;
				}
			}
			return $this->propValue($r,$r['~VALUE'],$arguments);
		}
		return $this->fieldValue($name,$arguments);
	}
	
	protected function fieldValue($name,&$arguments){
		$value = $this->field($name);
		switch($name){
			case 'ACTIVE_FROM':
			case 'ACTIVE_TO':
			case 'TIMESTAMP_X':
			case 'DATE_CREATE':
				return static::dateValue($value,$arguments);
		}
		return $value;
	}
	
	protected function propValue(&$arProp,$value,&$arguments){
		if('raw' === $arguments[0] ?? null)
			return $value;
		switch($arProp['PROPERTY_TYPE']){
			case 'L':
				if('Y' === $arProp['MULTIPLE']){
					if(false !== ($i = array_search($value,$arProp['~VALUE'])))
						$arResult = [
							'ID' => $arProp['VALUE_ENUM_ID'][$i]
							,'CODE' => $arProp['VALUE_XML_ID'][$i]
							,'VALUE' => $arProp['~VALUE'][$i]
						];
				}else
					$arResult = [
						'ID' => $arProp['VALUE_ENUM_ID']
						,'CODE' => $arProp['VALUE_XML_ID']
						,'VALUE' => $arProp['~VALUE']
					];
				return isset($arguments[0])
						? ($arResult[$arguments[0]] ?? null)
						: $arResult;
			case 'E':
				return self::getById($value);
			case 'G':
				return iblockSection::getById($value,$arProp['LINK_IBLOCK_ID']);
			case 'F':
				if($value && ($path = \CFile::GetPath($value))){
					$arResult = ['ID' => $value,'PATH' => $path];
					return isset($arguments[0])
							? ($arResult[strtoupper($arguments[0])] ?? null)
							: $arResult;
				}
				return null;
			default:
				if('iblockMultiprop' == $arProp['HINT'])
					return new iblockMultiprop($this->iblock(),$arProp['CODE'],$value);
				if('MULTIDATA' === $arProp['CODE'] && \Bitrix\Main\Loader::includeModule('forumedia.applications'))
					return new \forumedia\applications\multiProp($this->iblock(),$arProp['CODE'],$value);
				if($arProp['USER_TYPE']){
					switch($arProp['USER_TYPE']){
						case 'Date':
						case 'DateTime':
							return $value
								? ('Y' === $arProp['MULTIPLE']
									? array_map(function($value) use ($arguments){
											return $value ? static::dateValue($value,$arguments) : null;
										},$value)
									: static::dateValue($value,$arguments))
								: null;
						case 'directory':
							return new \forumedia\common\propHl($this->iblock(),$arProp['CODE'],$value);
						case 'UserID':
							return $value
								? ('Y' === $arProp['MULTIPLE']
									? array_map(function($id){ return $id ? new user($id) : null; },$value)
									: new user($value))
								: null;
						case 'HTML':
							return $value ? $value['TEXT'] : null;
					}
				}
				return $value;
		}
	}
	
	function iblock(){
		if(\Bitrix\Main\Loader::includeModule('forumedia.common'))
			return new \forumedia\common\iblock($this->field('IBLOCK_ID'));
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
	 * @return \static
	 * @throws \Exception
	 */
	static function select($arFilter,$arSelect = null,$arProps = null,&$rs = null){
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
		if($arSelect && !in_array('IBLOCK_ID',$arSelect))
			$arSelect[] = 'IBLOCK_ID';
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
		while($ibEl = $rs->GetNextElement())
			$arRes[] = new $className($ibEl);
		return $arRes;
	}
	
	static function getFirst($arFilter,$arSelect = null,$arProps = []){
		return ($arEl = static::select($arFilter,$arSelect,array_merge(['nTopCount' => 1],$arProps)))
			? array_shift($arEl)
			: new static(null);
	}
	
	static function getById($id){
		return $id
			? static::getFirst(['ID' => $id])
			: new static(null);
	}
	
	protected static function dateValue($value,&$arguments){
		if(!empty($value) && $arguments){
			$dt = isset($arguments[1])
				? new \DateTime($value,new \DateTimeZone($arguments[1]))
				: new \DateTime($value);
			return $dt->format($arguments[0]);
		}
		return $value;
	}
	
}
