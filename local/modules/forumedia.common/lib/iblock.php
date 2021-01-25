<?php

namespace forumedia\common;

/**
 * Description of iblock
 *
 * @author dev_pavel
 */
class iblock{

	protected static $arIblockIds;
	protected $id,$el,$sc;

	function __construct($id){
		$this->id = is_numeric($id) ? intval($id) : self::findId($id);
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
				empty($arProps['group']) ? (empty($arProps['sort']) ? array('SORT' => 'ASC','ID' => 'DESC') : $arProps['sort']) : null
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

	function delete($elementId){
		if(\CIBlock::GetPermission($this->id()) >= 'W')
			return \CIBlockElement::Delete($elementId);
		throw new \Exception('Недостаточно прав для удаления элемента');
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
		if($id = self::findId($path))
			return new iblock($id);
	}

	/**
	 * Ищет инфоблок по коду и с учетем типа инфоблока, если задан
	 * результаты запросов кешируются в статическом свойстве класса
	 * 
	 * @param string $path - '{CODE}' или '{TYPE}/{CODE}'
	 * @return integer
	 */
	static function findId($path){
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
				$arFilter['IBLOCK_TYPE_ID'] = $type;
			if($r = \Bitrix\Iblock\IblockTable::getList([
					'filter' => $arFilter
					,'select' => ['ID']
					,'cache' => ['ttl' => 360,'cache_joins' => true]
				])->fetch()
			){
				self::$arIblockIds[$path] = $r['ID'];
			};
		}
		if(isset(self::$arIblockIds[$path]))
			return self::$arIblockIds[$path];
	}
	
	/**
	 * Сброс тегированного кеша
	 * обычно приходится вызывать после setProps
	 * т.к. битриксовый SetPropertyValuesEx не сбрасывает кеш
	 */
	function clearTagCache(){
		\CIBlock::clearIblockTagCache($this->id());
	}




	/*
	 * Методы работы с свойствами
	 */
	
	
	function setProps($elementId,$arValues){
		if(($elementId = intval($elementId)) && is_array($arValues) && !empty($arValues)
		){
			return \CIBlockElement::SetPropertyValuesEx($elementId,$this->id,$arValues);
		}
	}
	
	/**
	 * Возвращает массив с информацией о свойстве инфоблока
	 * результаты кешируются в статическом свойстве класса
	 * 
	 * @param integer|string $code код или идентификатор свойства
	 * @return array
	 */
	function prop($code,$wrap = false){
		$prop = new iblock\prop($code,$this);
		return $wrap ? $prop : $prop->toArray();
	}
	
	function propList(){
		return iblock\prop::list($this);
	}

	/**
	 * Возвращает массив значений для заданного свойства элемента
	 * 
	 * @param string $code
	 * @param integer $elementId
	 * @return array
	 */
	function propVal($code,$elementId){
		return $this->prop($code,true)->value($elementId);
	}

	/**
	 * Проверяет, есть ли свойство у ИБ
	 * @param string $code
	 * @return type
	 */
	function hasProp($code){
		return $this->prop($code,true)->exists();
	}

	/**
	 * Возвращает массив значений списочного свойства
	 * 
	 * @param string $code
	 * @return array
	 */
	function listPropValues($code){
		return $this->prop($code,true)->enumValues();
	}

	/**
	 * Возвращает айди значения списочного свойства
	 * 
	 * @param string $code код свойства
	 * @param string $value значение или его XML_ID, в зависимости от аргумента $byValue
	 * @param boolean $byValue если установлени правда, будет искать по значению, а не по XML_ID
	 * @param boolean $addIfNotExists если установлени правда, будет добавлять значение в список, если его не существует
	 * @return int
	 */
	function listPropValueId($code,$value,$byValue = false,$addIfNotExists = false){
		return $this->prop($code,true)->emunValueId($value,$byValue,$addIfNotExists);
	}

}
