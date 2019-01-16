<?php

namespace forumedia\common;

/**
 * Description of iblock
 *
 * @author dev_pavel
 */
class iblock{

	protected static $arListPropValues;
	protected $id,$el;

	function __construct($id){
		$this->id = intval($id);
		if(!($this->id > 0))
			throw new \Exception('invalid iblock id');
		if(!\CModule::IncludeModule('iblock'))
			throw new \Exception('iblock module not found');
	}

	function toArray(){
		return \CIBlock::GetByID($this->id)->GetNext(true, false);
	}

	function prop($code){
		return \CIBlockProperty::GetList(array('NAME' => 'ASC'), array('IBLOCK_ID' => $this->id, 'CODE' => $code))
				->GetNext(false, false);
	}
	
	function el(){
		if(!$this->el)
			$this->el = new \CIBlockElement;
		return $this->el;
	}

	/**
	 * Обертка для CIBlockElement::GetList
	 * 
	 * @param array $arFilter соответствует параметру arFilter
	 * 		айди инфоблока добавляется автоматически
	 * @param array $arSelect соответствует параметру arSelectFields
	 * @param array $arProps
	 * 		ключ массива sort соответствует параметру arOrder
	 * 		ключ массива group соответствует параметру arGroupBy
	 * 		ключ массива nav соответствует параметру arNavStartParams
	 * @return \CIBlockResult
	 */
	function getList($arFilter, $arSelect = null, $arProps = null){
		if(!is_array($arFilter))
			$arFilter = array();
		return \CIBlockElement::GetList(
				empty($arProps['sort']) ? array('SORT' => 'ASC', 'ID' => 'DESC') : $arProps['sort']
				, array_merge($arFilter, array('IBLOCK_ID' => $this->id))
				, empty($arProps['group']) ? false : $arProps['group']
				, empty($arProps['nav']) ? false : $arProps['nav']
				, $arSelect
		);
	}
	
	/**
	 * Возвращает массив значений списочного свойства
	 * результаты запросов кешируются в статическом свойстве класса
	 * 
	 * @param string $name
	 * @return array
	 */
	function listPropValues($name){
		if(!is_array(self::$arListPropValues))
			self::$arListPropValues = array();
		if(empty(self::$arListPropValues[$name])){
			$rs = \CIBlockPropertyEnum::GetList(array('SORT' => 'ASC')
					, array('IBLOCK_ID' => $this->id, 'CODE' => $name));
			self::$arListPropValues[$name] = array();
			while($r = $rs->GetNext(false, false)){
				unset($r['PROPERTY_NAME'],$r['PROPERTY_CODE'],$r['PROPERTY_SORT'],$r['TMP_ID']);
				self::$arListPropValues[$name][$r['EXTERNAL_ID']] = $r;
			}
		}
		if(!empty(self::$arListPropValues[$name]))
			return self::$arListPropValues[$name];
	}

	/**
	 * Возвращает айди значения списочного свойства
	 * результаты запросов кешируются в статическом свойстве класса
	 * 
	 * @param string $name код свойства
	 * @param string $value значение или его XML_ID, в зависимости от аргумента $byValue
	 * @param boolean $byValue если установлени правда, будет искать по значению, а не по XML_ID
	 * @return int
	 */
	function listPropValueId($name, $value, $byValue = false){
		if($arValues = $this->listPropValues($name)){
			if($byValue){
				foreach($arValues as $arValue)
					if($arValue['VALUE'] == $value)
						return $arValue['ID'];
			}elseif(isset(self::$arListPropValues[$name][$value]))
				return intval($arValues[$value]['ID']);
		}
	}

	function add($arFields,$arProps = null){
		if(is_array($arFields) && !empty($arFields['NAME'])){
			$arFields = array_merge($arFields,array('IBLOCK_ID' => $this->id));
			$arFields['PROPERTY_VALUES'] = $arProps;
			if(!($elId = $this->el()->Add($arFields)))
				throw new \Exception($this->el()->LAST_ERROR);
			return $elId;
		}else
			throw new \Exception('Обязательные значения не заданы');
	}
	
	function update($elementId,$arFields,$arProps = null){
		if(($elementId = intval($elementId)) > 0
			&& is_array($arFields)
		){
			if(!($res = $this->el()->Update($elementId,$arFields)))
				throw new \Exception($this->el()->LAST_ERROR);
			if(!empty($arProps) && is_array($arProps) && $res)
				$this->setProps($elementId,$arProps);
			return $res;
		}
	}
	
	function setProps($elementId,$arValues){
		if(($elementId = intval($elementId))
			&& is_array($arValues)
			&& !empty($arValues)
		){
			return \CIBlockElement::SetPropertyValuesEx($elementId,$this->id,$arValues);
		}
	}

}
