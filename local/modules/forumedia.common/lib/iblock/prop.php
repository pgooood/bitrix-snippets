<?php
namespace forumedia\common\iblock;

/**
 * Description of prop
 *
 * @author pgood
 */
class prop{
	protected static $arCache,$arCacheValues;
	protected $ib,$pr,$idcode;
	
	function __construct($prop,\forumedia\common\iblock $ib){
		$this->ib = $ib;
		$this->idcode = $prop;
	}
	
	function __get($name){
		if(($arProp = $this->toArray()) && isset($arProp[$name]))
			return $arProp[$name];
	}
	
	function __set($name,$value){
		
	}
	
	function ib(){
		return $this->ib;
	}
	
	/**
	 * Класс Bitrix для работы со свойствами информационных разделов
	 * @return \CIBlockProperty
	 */
	function pr(){
		if(!$this->pr)
			$this->pr = new \CIBlockProperty;
		return $this->pr;
	}
	
	/**
	 * Получает информацию о свойстве из БД и сохраняет ее в кеше
	 * @return integer|null
	 */
	function reset(){
		if($arProp = \CIBlockProperty::GetList(
				[]
				,['IBLOCK_ID' => $this->ib->id(),is_numeric($this->idcode) ? 'ID' : 'CODE' => $this->idcode]
			)->Fetch()
		){
			self::$arCache[$this->ib->id()][$arProp['ID']] = &$arProp;
			if($arProp['CODE'])
				self::$arCache[$this->ib->id()][$arProp['CODE']] = &$arProp;
		}else
			self::$arCache[$this->ib->id()][$this->idcode] = false;
	}
	
	function exists(){
		$isFresh = self::cache($this->ib);
		return (isset(self::$arCache[$this->ib->id()][$this->idcode]) && !empty(self::$arCache[$this->ib->id()][$this->idcode]))
			|| (!isset(self::$arCache[$this->ib->id()][$this->idcode]) && !$isFresh && $this->reset());
	}
	
	function toArray(){
		if($this->exists())
			return self::$arCache[$this->ib->id()][$this->idcode];
	}
	
	function value($elementId){
		if($elementId = intval($elementId)){
			$rs = \CIBlockElement::GetProperty(
					$this->ib->id()
					,$elementId
					,'sort','asc'
					,[is_numeric($this->idcode) ? 'ID' : 'CODE' => $this->idcode]
				);
			$arRes = [];
			while($r = $rs->Fetch())
				$arRes[] = [
					'ID' => $r['~PROPERTY_VALUE_ID']
					,'VALUE' => $r['~VALUE']
					,'DESCRIPTION' => $r['~DESCRIPTION']
					,'VALUE_ENUM' => $r['~VALUE_ENUM']
					,'VALUE_XML_ID' => $r['~VALUE_XML_ID']
				];
			return $arRes;
		}
	}
	
	function create(array $arProp){
		if($this->exists())
			throw new Exception('Property ['.$this->idcode.'] already exsists');
		$arProp = array_merge([
			'CODE' => $this->idcode
			,'SORT' => 500
			,'PROPERTY_TYPE' => 'S'
			,'IS_REQUIRED' => 'N'
			,'MULTIPLE' => 'N'
		],$arProp);
		$arProp['IBLOCK_ID'] = $this->ib->id();
		if(false === ($id = $this->pr()->Add($arProp)))
			throw new \Exception($this->pr()->LAST_ERROR);
		return $this->idcode = $id;
	}
	
	function remove(){
		if($arProp = $this->toArray())
			return \CIBlockProperty::Delete($arProp['ID']);
	}
	
	/**
	 * Возвращает массив значений списочного свойства
	 * результаты запросов кешируются в статическом свойстве класса
	 * 
	 * @param string $name
	 * @param array $arSort
	 * @return array
	 */
	function enumValues(){
		self::initCacheVars($this->ib);
		if(empty(self::$arCacheValues[$this->idcode])){
			$rs = \CIBlockPropertyEnum::GetList(
				['SORT' => 'ASC','ID' => 'ASC']
				,[
					'IBLOCK_ID' => $this->ib->id()
					,is_numeric($this->idcode) ? 'ID' : 'CODE' => $this->idcode
				]
			);
			self::$arCacheValues[$this->idcode] = [];
			while($r = $rs->Fetch()){
				unset($r['PROPERTY_NAME'],$r['PROPERTY_CODE'],$r['PROPERTY_SORT'],$r['TMP_ID']);
				self::$arCacheValues[$this->idcode][$r['EXTERNAL_ID']] = $r;
			}
		}
		if(!empty(self::$arCacheValues[$this->idcode]))
			return self::$arCacheValues[$this->idcode];
	}
	
	/**
	 * Возвращает айди значения списочного свойства
	 * результаты запросов кешируются в статическом свойстве класса
	 * 
	 * @param string $value значение или его XML_ID, в зависимости от аргумента $byValue
	 * @param boolean $byValue если установлени правда, будет искать по значению, а не по XML_ID
	 * @param boolean $addIfNotExists если установлени правда, будет добавлять значение в список, если его не существует
	 * @code string XML_ID для создаваемого свойства
	 * @return int
	 */
	function enumValueId($value,$byValue = false,$addIfNotExists = false,$code = null){
		if($arValues = $this->enumValues()){
			if($byValue){
				foreach($arValues as $arValue)
					if($arValue['VALUE'] == $value)
						return $arValue['ID'];
			}elseif(isset($arValues[$value]))
				return intval($arValues[$value]['ID']);
		}
		//добавляем значение если не существует
		if($byValue && $addIfNotExists)
			return $this->addEnumValue($value,$code);
	}
	
	function linkedElementId($value,$arElementProps = null){
		if($this->CODE){
			$prop = new \forumedia\common\propLinkedElement($this->ib(),$this->CODE);
			return $prop->valueId($value,$arElementProps);
		}
	}
	
	function addEnumValue($value,$code = null,$sort = null){
		if($arProp = $this->toArray()){
			$arValues = [];
			$i = 0;
			foreach($arValues as $arVal)
				$arValues[$arVal['ID']] = array('SORT' => ($i = $i + 10),'VALUE' => $arVal['VALUE']);
			if($sort === null)
				$sort = $i + 10;
			$arValues[] = array('SORT' => $sort,'VALUE' => $value,'XML_ID' => $code);
			$this->pr()->UpdateEnum($arProp['ID'],$arValues);
			unset(self::$arCacheValues[$arProp['ID']],self::$arCacheValues[$arProp['CODE']]);
			return $this->enumValueId($value,true,false);
		}
	}
	
	static function list(\forumedia\common\iblock $ib){
		self::cache($ib);
		$arResult = [];
		
		if(self::$arCache[$ib->id()])
			foreach(self::$arCache[$ib->id()] as $arProp)
				$arResult[$arProp['CODE'] ?: $arProp['ID']] = new prop($arProp['ID'],$ib);
		return $arResult;		
	}
	
	/**
	 * Кеширует все свойства инфоблока
	 * @param \forumedia\common\iblock $ib
	 * @param boolean $reset
	 */
	protected static function cache(\forumedia\common\iblock $ib,$reset = false){
		self::initCacheVars($ib);
		if(empty(self::$arCache[$ib->id()]) || $reset){
			$rs = \CIBlockProperty::GetList(['NAME' => 'ASC'],['IBLOCK_ID' => $ib->id()]);
			while($arProp = $rs->Fetch()){
				self::$arCache[$ib->id()][$arProp['ID']] = &$arProp;
				if($arProp['CODE'])
					self::$arCache[$ib->id()][$arProp['CODE']] = &$arProp;
				unset($arProp);
			}
			return true;
		}
	}
	
	protected static function initCacheVars(\forumedia\common\iblock $ib){
		if(!isset(self::$arCache))
			self::$arCache = [];
		if(!isset(self::$arCache[$ib->id()]))
			self::$arCache[$ib->id()] = [];
		if(!isset(self::$arCacheValues))
			self::$arCacheValues = [];
	}
}
