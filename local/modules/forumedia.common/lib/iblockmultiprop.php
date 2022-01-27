<?php
namespace forumedia\common;

/**
 * Класс для хранения в одном множественном, текстовом свойстве инфоблока
 * множества наборов (массивов) именнованных JSON данных
 * Так как JSON данные могут превысить максимальный размер поля, используется множественное свойство
 */
class iblockMultiprop implements \Iterator{
	protected $ib,$name,$elementId,$arValues = [];
	
	function __construct(iblock $ib,$name,$element = null){
		$this->ib = $ib;
		$this->name($name);
		$this->load($element);
	}
	
	function iblock(){
		return $this->ib;
	}
	
	function __get($name){
		return $this->get($name);
	}
	
	function __set($name,$value){
		$this->set($name,$value);
	}
	
	function __unset($name){
		$this->set($name,null);
	}
	
	function elementId($v = null){
		if($v === null)
			return $this->elementId;
		$v = intval($v);
		if(!($v > 0))
			throw new \Exception('Invalid Element Id');
		$this->elementId = $v;
	}
	
	function name($v = null){
		if($v === null)
			return $this->name;
		if(!is_string($v) || !strlen($v))
			throw new \Exception('Invalid Name');
		$this->name = $v;
	}
	
	/**
	 * Загружает значение свойства из инфоблока
	 * @param integer|\_CIBElement $element - идентификатор инфоблока или результат
	 *										метода CIBlockResult::GetNextElement
	 * @throws \Exception
	 */
	function load($element = null){
		$this->arValues = [];
		if(is_object($element)){
			if(!($element instanceof \_CIBElement))
				throw new \Exception('Invalid value');
			$arFields = $element->GetFields();
			if($arFields['IBLOCK_ID'] != $this->ib->id())
				throw new \Exception('Wrong Iblock Result');
			$this->elementId($arFields['ID']);
			if($arProp = $element->GetProperty($this->name())){
				if($arRaw = $arProp['~VALUE'] ?: $arProp['VALUE'])
					foreach($arRaw as $i => $v)
						if($v['TEXT'] && ($r = json_decode($v['TEXT'],true)) && !empty($r['data']))
							$this->arValues[$r['name']][] = [
								'id' => $arProp['PROPERTY_VALUE_ID'][$i]
								,'data' => $r['data']
							];
			}else
				throw new \Exception('Property not found');
		}else{
			if($element !== null)
				$this->elementId($element);
			if($this->elementId()
				&& ($arRaw = $this->ib->propVal($this->elementId(),$this->name()))
			){
				foreach($arRaw as $v)
					if($v['VALUE']['TEXT'] && ($r = json_decode($v['VALUE']['TEXT'],true)) && !empty($r['data']))
						$this->arValues[$r['name']][] = [
							'id' => $v['ID']
							,'data' => $r['data']
						];
			}
		}
	}
	
	function save($elementId = null){
		if($elementId !== null)
			$this->elementId($elementId);
		if(!$this->elementId())
			throw new \Exception('Element Id Required');
		$this->ib->setProps($this->elementId(),[$this->name() => $this->serialize()]);
	}
	
	function list($name){
		$arResult = [];
		foreach($this->arValues[$name] as $r)
			$arResult[] = $r['data'];
		return $arResult;
	}
	
	function get($name){
		$arPath = explode('/',$name);
		if($arValues = $this->list(array_shift($arPath))){
			$value = array_shift($arValues);
			while(is_array($value) && ($name = array_shift($arPath)))
				$value = $value[$name];
			return $value;
		}
	}
	
	function set($name,$value){
		$arPath = explode('/',$name);
		if(count($arPath) > 1){
			$storedValue = $this->get($name = array_shift($arPath)) ?: [];
			$v = &$storedValue;
			$vPrev = null;
			while($arPath && ($pathName = array_shift($arPath))){
				if(empty($v[$pathName]))
					$v[$pathName] = [];
				if($arPath && !is_array($v[$pathName]))
					throw new \Exception('Invalid path value');
				$vPrev = &$v;
				$v = &$v[$pathName];
			}
			if($value === null){
				if(isset($pathName))
					unset($vPrev[$pathName]);
			}else
				$v = $value;
		}else
			$storedValue = $value;
		$this->clear($name);
		if($storedValue !== null)
			$this->add($name,$storedValue);
	}
	
	function setList($name,array $arValues){
		$this->clear($name);
		foreach($arValues as $value)
			$this->add($name,$value);
	}
	
	function add($name,$value){
		$this->arValues[$name][] = ['data' => $value];
	}
	
	/**
	 * Удалить все значения для заданного имени
	 * @param string $name
	 */
	function clear($name){
		unset($this->arValues[$name]);
	}
	
	/**
	 * Подготовить данные для сохранения в инфоблок
	 * @return array
	 */
	function serialize(){
		$arResult = [];
		foreach($this->arValues as $name => $arNamedValues){
			foreach($arNamedValues as $r)
				if(isset($r['data']))
					$arResult[] = [
						'VALUE' => [
								'TEXT' => json_encode(['name' => $name,'data' => $r['data']],JSON_UNESCAPED_UNICODE)
								,'TYPE' => 'text'
							]
						,'DESCRIPTION' => ''
						,'ID' => $r['id'] ?: null
					];
		}
		return $arResult;
	}
	
	function toArray(){
		$arData = [];
		foreach($this as $name => $v)
			$arData[$name] = count($v) === 1
				? array_shift($v)
				: $v;
		return $arData;
	}
	
	
	// методы интерфейса Iterator
	
	public function rewind(){ reset($this->arValues); }

	public function current(){ return $this->list(key($this->arValues)); }

	public function key(){ return key($this->arValues); }

	public function next(){  next($this->arValues); return $this->current(); }

	public function valid(){
		$key = key($this->arValues);
		return $key !== null && $key !== false;
	}
	
}
