<?php

namespace forumedia\common;

/**
 * Description of iblock
 *
 * @author dev_pavel
 */
class iblock{

	protected static $arIblockIds,$arListProps,$arListPropValues;
	protected $id,$el,$sc;

	function __construct($id){
		$this->id = intval($id);
		if(!($this->id > 0))
			throw new \Exception('invalid iblock id');
		if(!\CModule::IncludeModule('iblock'))
			throw new \Exception('iblock module not found');
	}

	function id(){
		return $this->id;
	}

	function toArray(){
		return \CIBlock::GetByID($this->id)->Fetch();
	}

	/**
	 * Возвращает массив с информацией о свойстве инфоблока
	 * результаты кешируются в статическом свойстве класса
	 * 
	 * @param string $code
	 * @return array
	 */
	function prop($code){
		if(!is_array(self::$arListProps))
			self::$arListProps = array();
		if(!isset(self::$arListProps[$this->id()][$code]))
			self::$arListProps[$this->id()][$code] = \CIBlockProperty::GetList(array('NAME' => 'ASC'),array('IBLOCK_ID' => $this->id(),'CODE' => $code))
					->GetNext(false,false);
		if(isset(self::$arListProps[$this->id()][$code]))
			return self::$arListProps[$this->id()][$code];
	}

	/**
	 * Возвращает массив значений для заданного свойства элемента
	 * 
	 * @param integer $elementId
	 * @param string $code
	 * @return array
	 */
	function propVal($elementId,$code){
		if(($elementId = intval($elementId)) && $code){
			$rs = \CIBlockElement::GetProperty(
					$this->id()
					,$elementId
					,'sort','asc'
					,array('CODE' => $code)
				);
			$arRes = array();
			while($r = $rs->GetNext()){
				$arRes[] = array(
					'ID' => $r['~PROPERTY_VALUE_ID']
					,'VALUE' => $r['~VALUE']
					,'DESCRIPTION' => $r['~DESCRIPTION']
					,'VALUE_ENUM' => $r['~VALUE_ENUM']
					,'VALUE_XML_ID' => $r['~VALUE_XML_ID']
				);
			}
			return $arRes;
		}
	}

	/**
	 * Проверяет, есть ли свойство у ИБ
	 * 
	 * @return Boolean
	 */
	function hasProp($code){
		$res = \Bitrix\Iblock\PropertyTable::getList([
			'select' => ['ID']
			,'filter' => ['IBLOCK_ID' => $this->id, 'CODE' => $code]
			,'cache'  => ['ttl' => 3600]
		])->fetch();
		return !empty($res);
	}

	function el(){
		if(!$this->el)
			$this->el = new \CIBlockElement;
		return $this->el;
	}

	function sc(){
		if(!$this->sc)
			$this->sc = new \CIBlockSection;
		return $this->sc;
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
	function getList($arFilter,$arSelect = null,$arProps = null){
		if(!is_array($arFilter))
			$arFilter = array();
		return \CIBlockElement::GetList(
						empty($arProps['sort']) ? array('SORT' => 'ASC','ID' => 'DESC') : $arProps['sort']
						,array_merge($arFilter,array('IBLOCK_ID' => $this->id))
						,empty($arProps['group']) ? false : $arProps['group']
						,empty($arProps['nav']) ? false : $arProps['nav']
						,$arSelect
		);
	}

	function getElement($arFilter,$arSelect = null,$arProps = null){
		return $this->getList($arFilter,$arSelect,$arProps)->GetNext(true,false);
	}

	function getSectionList($arFilter,$arSelect = null,$arProps = null){
		if(!is_array($arFilter))
			$arFilter = array();
		return \CIBlockSection::GetList(
						empty($arProps['sort']) ? array('SORT' => 'ASC','ID' => 'DESC') : $arProps['sort']
						,array_merge($arFilter,array('IBLOCK_ID' => $this->id))
						,!empty($arProps['bIncCnt'])
						,$arSelect
						,empty($arProps['nav']) ? false : $arProps['nav']
		);
	}

	function getSection($arFilter,$arSelect = null,$arProps = null){
		return $this->getSectionList($arFilter,$arSelect,$arProps)->GetNext(true,false);
	}

	function addSection($arFields){
		$arFields = array_merge($arFields,array('IBLOCK_ID' => $this->id()));
		if(!($id = $this->sc()->Add($arFields)))
			throw new \Exception($this->sc()->LAST_ERROR);
		return $id;
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
		if($arProp = $this->prop($name)){
			if(empty(self::$arListPropValues[$arProp['ID']])){
				$rs = \CIBlockPropertyEnum::GetList(array('SORT' => 'ASC')
								,array('IBLOCK_ID' => $this->id,'CODE' => $name));
				self::$arListPropValues[$arProp['ID']] = array();
				while($r = $rs->GetNext(false,false)){
					unset($r['PROPERTY_NAME'],$r['PROPERTY_CODE'],$r['PROPERTY_SORT'],$r['TMP_ID']);
					self::$arListPropValues[$arProp['ID']][$r['EXTERNAL_ID']] = $r;
				}
			}
			if(!empty(self::$arListPropValues[$arProp['ID']]))
				return self::$arListPropValues[$arProp['ID']];
		}
	}

	/**
	 * Возвращает айди значения списочного свойства
	 * результаты запросов кешируются в статическом свойстве класса
	 * 
	 * @param string $name код свойства
	 * @param string $value значение или его XML_ID, в зависимости от аргумента $byValue
	 * @param boolean $byValue если установлени правда, будет искать по значению, а не по XML_ID
	 * @param boolean $addIfNotExists если установлени правда, будет добавлять значение в список, если его не существует
	 * @return int
	 */
	function listPropValueId($name,$value,$byValue = false,$addIfNotExists = false){
		if($arValues = $this->listPropValues($name)){
			if($byValue){
				foreach($arValues as $arValue)
					if($arValue['VALUE'] == $value)
						return $arValue['ID'];
			}elseif(isset($arValues[$value]))
				return intval($arValues[$value]['ID']);

			//добавляем значение если не существует
			if($addIfNotExists && ($arProp = $this->prop($name))){
				$arValues = array();
				$i = 0;
				foreach($arValues as $arVal)
					$arValues[$arVal['ID']] = array('SORT' => ++$i,'VALUE' => $arVal['VALUE']);
				$arValues[] = array('SORT' => ++$i,'VALUE' => $value);
				$CIBlockProp = new \CIBlockProperty;
				$CIBlockProp->UpdateEnum($arProp['ID'],$arValues);
				unset(self::$arListPropValues[$arProp['ID']]);
				return $this->listPropValueId($name,$value,$byValue,false);
			}
		}
	}

	function add($arFields,$arProps = null){
		if(is_array($arFields) && !empty($arFields['NAME'])){
			$arFields = array_merge($arFields,array('IBLOCK_ID' => $this->id()));
			$arFields['PROPERTY_VALUES'] = $arProps;
			if(!($elId = $this->el()->Add($arFields)))
				throw new \Exception($this->el()->LAST_ERROR);
			return $elId;
		}else
			throw new \Exception('Обязательные значения не заданы');
	}

	function update($elementId,$arFields,$arProps = null){
		if(($elementId = intval($elementId)) > 0 && is_array($arFields)
		){
			if(!($res = $this->el()->Update($elementId,$arFields)))
				throw new \Exception($this->el()->LAST_ERROR);
			if(!empty($arProps) && is_array($arProps) && $res)
				$this->setProps($elementId,$arProps);
			return $res;
		}
	}

	function setProps($elementId,$arValues){
		if(($elementId = intval($elementId)) && is_array($arValues) && !empty($arValues)
		){
			return \CIBlockElement::SetPropertyValuesEx($elementId,$this->id,$arValues);
		}
	}

	function deactivateElements($arFilter = null){
		$rs = $this->getList(is_array($arFilter) ? array_merge($arFilter,['ACTIVE' => 'Y']) : ['ACTIVE' => 'Y'],['ID']);
		while($r = $rs->GetNext(true,false))
			$this->el()->Update($r['ID'],['ACTIVE' => 'N'],false,false,false,false);
	}

	/**
	 * Ищет инфоблок по коду и с учетем типа инфоблока, если задан
	 * результаты запросов кешируются в статическом свойстве класса
	 * 
	 * @param string $path - '{CODE}' или '{TYPE}/{CODE}'
	 * @return \forumedia\common\iblock
	 */
	static function find($path){
		if(!is_array(self::$arIblockIds))
			self::$arIblockIds = array();
		
		$arPath = array_filter(explode('/',$path));
		$code = array_pop($arPath);
		$type = array_pop($arPath);
		
		if(!isset(self::$arIblockIds[$path])
			&& strlen($code)
		){
			$arFilter = array('CODE' => $code);
			if(strlen($type))
				$arFilter['TYPE'] = $type;
			if($r = \CIBlock::GetList(array(),$arFilter)->Fetch())
				self::$arIblockIds[$path] = $r['ID'];
		}
		if(isset(self::$arIblockIds[$path]))
			return new iblock(self::$arIblockIds[$path]);
	}

}
