<?php
namespace forumedia\common;

/**
 * Класс для работы со свойством инфоблока типа Справочник
 * @author Pavel Khoroshkov <pgood@forumedia.com>
 */
class propHl implements \Iterator{

	static protected $arHlClasses;
	protected $ib,$arProp,$elementId,$hlblock
			,$arValues = []
			,$arHashFields = []
			,$fnDeleteFilter;

	function __construct(\forumedia\common\iblock $iblock,$name,$element = null){
		$this->ib = $iblock;
		if(!($this->arProp = $this->ib->prop($name)))
			throw new \Exception('Propery ['.$name.'] is not found');
		if($this->arProp['PROPERTY_TYPE'] != 'S' || empty($this->arProp['USER_TYPE_SETTINGS']['TABLE_NAME']))
			throw new \Exception('Invalid propery ['.$name.'] type. It\'s should be linked element.');
		$this->hlblock = $this->hlClass($this->arProp['USER_TYPE_SETTINGS']['TABLE_NAME']);
		$this->load($element);
	}
	
	function elementId($v = null){
		if($v === null)
			return $this->elementId;
		$v = intval($v);
		if(!($v > 0))
			throw new \Exception('Invalid Element Id');
		$this->elementId = $v;
	}
	
	function load($element = null){
		$this->arValues = [];
		$arXmlIds = null;
		if(is_object($element)){
			if(!($element instanceof \_CIBElement))
				throw new \Exception('Invalid value');
			$arFields = $element->GetFields();
			if($arFields['IBLOCK_ID'] != $this->ib->id())
				throw new \Exception('Wrong Iblock Result');
			$this->elementId($arFields['ID']);
			if($arProp = $element->GetProperty($this->arProp['CODE']))
				$arXmlIds = $arProp['~VALUE'] ?: $arProp['VALUE'];
		}else{
			if($element !== null)
				$this->elementId($element);
			if($this->elementId()
				&& ($arRaw = $this->ib->propVal($this->elementId(),$this->arProp['CODE']))
			){
				foreach($arRaw as $v)
					$arXmlIds[] = $v['VALUE'];
			}
		}
		if($arXmlIds){
			$rs = $this->select(['filter' => ['UF_XML_ID' => $arXmlIds]]);
			while($r = $rs->fetch())
				$this->arValues[] = $r;
		}
	}
	
	function select($arParams = null){
		if(!is_array($arParams))
			$arParams = [];
		if(!isset($arParams['select']))
			$arParams['select'] = ['*'];
		return $this->hlblock::getList($arParams);
	}
	
	/**
	 * Устанавливает новые значения для свойства ИБ, предварительно актуализировав переданные записи в HL-блоке
	 * @param array $arRows
	 * @param integer $elementId
	 * @throws \Exception
	 */
	function set($arRows,$elementId = null){
		if($elementId !== null)
			$this->elementId($elementId);
		if(!$this->elementId())
			throw new \Exception('Element Id Required');
		if(!is_array($arRows))
			throw new \Exception('Invalid Value');
		$arFilter = null;
		if($arPropValues = $this->ib->propVal($this->elementId(),$this->arProp['CODE']))
			$arFilter = ['UF_XML_ID' => array_column($arPropValues,'VALUE')];
		$arXmlIds = $this->distinctSaving($arRows,$arFilter);
		$this->ib->setProps($this->elementId(),[$this->arProp['CODE'] => $arXmlIds]);
	}
	
	function add($arRow){
		if(!$this->elementId())
			throw new \Exception('Element Id Required');
		if(empty($arRow['UF_XML_ID']))
			$arRow['UF_XML_ID'] = $this->elementId().'::'.self::rowHash($arRow);
		$rs = $this->hlblock::add($arRow);
		if($rs->isSuccess())
			return $arRow['UF_XML_ID'];
		else
			throw new \Exception(implode("; \n",$rs->getErrorMessages()));
	}
	
	function update($id,$arRow){
		if(!$this->elementId())
			throw new \Exception('Element Id Required');
		if(!intval($id) > 0)
			throw new \Exception('Invalid id');
		if(empty($arRow['UF_XML_ID']))
			$arRow['UF_XML_ID'] = $this->elementId().'::'.self::rowHash($arRow);
		$rs = $this->hlblock::update($id,$arRow);
		if($rs->isSuccess())
			return $arRow['UF_XML_ID'];
		else
			throw new \Exception(implode("; \n",$rs->getErrorMessages()));
	}
	
	/**
	 * Возвращает хэш данных в массиве, не учитывает поля:
	 *   - UF_SORT, UF_DESCRIPTION, UF_FULL_DESCRIPTION, UF_XML_ID (по умолчанию)
	 *   - не заданные методом hashFields (если был вызван)
	 * @param array $arRow
	 * @return string
	 */
	protected function rowHash($arRow){
		unset($arRow['ID']);
		if($this->arHashFields){
			foreach($arRow as $field => $v)
				if(!in_array($field,$this->arHashFields))
					unset($arRow[$field]);
		}else
			unset($arRow['UF_SORT'],$arRow['UF_DESCRIPTION'],$arRow['UF_FULL_DESCRIPTION'],$arRow['UF_XML_ID']);
		ksort($arRow);
		return crc32(implode('|',array_map(function($v){ return is_scalar($v) ? $v : serialize($v); },$arRow)));
	}
	
	/**
	 * Задает/возвращает поля, значения которых учавствуют при сравнивании существующих записей и устанавливаемых записей
	 * @param array $v
	 * @return array|null
	 */
	function hashFields($v = null){
		if(null === $v)
			return $this->arHashFields;
		elseif(is_array($v))
			$this->arHashFields = $v;
	}
	
	/**
	 * Позволяет отключать удаление записей из HL при использовании методов set или distinctSaving
	 * @param callable $fnFilter
	 */
	function deleteFilter(callable $fnFilter){
		$this->fnDeleteFilter = $fnFilter;
	}
	
	static function prettifyRow($arRow){
		$arResult = [];
		unset($arRow['UF_SORT'],$arRow['UF_DESCRIPTION'],$arRow['UF_XML_ID']);
		foreach($arRow as $name => $value){
			$name = strtolower($name);
			if(preg_match('/^uf_(.+)$/',$name,$m))
				$name = $m[1];
			$arResult[$name] = $value;
		}
		return $arResult;
	}
	
	/**
	 * Добавляет, изменяет или удаляет записи сопоставляя их с набором
	 * существующих в hl-блоке записей соответствующих переданному фильтру.
	 * 
	 * @param array $arRows - значения записей hl-блока
	 *		имена полей записей могут не содержать префиксы UF_ и могут быть в нижнем регистре
	 * @param array $arFilter - фильтр для выбора набора существующих записей
	 */
	protected function distinctSaving($arRows,$arFilter = null){
		$arResult = [];
		$fnName = function($name){
			$name = strtoupper($name);
			if('ID' !== $name && strpos($name,'UF_') !== 0)
				return 'UF_'.$name;
			return $name;
		};
	
		$fnPrepRows = function($r) use ($arRows,$fnName){
			$arResult = [];
			foreach($arRows as $r){
				$arRow = [];
				foreach($r as $name => $value)
					$arRow[$fnName($name)] = $value;
				$arResult[self::rowHash($arRow)] = $arRow;
			}
			return $arResult;
		};
		
		// существующие записи
		$arExistedRows = [];
		if(is_array($arFilter)){
			$rs = $this->select(['filter' => $arFilter]);
			while($r = $rs->fetch())
				$arExistedRows[] = $r;
		}
		
		// существующие записи, айди которых, не пришли в новом наборе, их будем пробовать сопостваить по контенту
		$arRows = $fnPrepRows($arRows);
		$arIds = array_column($arRows,'ID');
		$arLostExisted = [];
		foreach($arExistedRows as $r)
			if(!in_array($r['ID'],$arIds))
				$arLostExisted[self::rowHash($r)] = $r;
		
		// определяем что обновлять/добавлять
		$arIds = array_column($arExistedRows,'ID');
		$arAdd = [];
		$arUpdate = [];
		$sort = 0;
		foreach($arRows as $hash => $r){
			$sort+= 10;
			$r['UF_SORT'] = $sort;
			if(!empty($r['ID']) && in_array($r['ID'],$arIds))
				$arUpdate[] = $r;
			elseif(isset($arLostExisted[$hash])){
				$r['ID'] = $arLostExisted[$hash]['ID'];
				unset($arLostExisted[$hash]);
				$arUpdate[] = $r;
			}else
				$arAdd[] = $r;
		}
		if($arAdd)
			foreach($arAdd as $r)
				$arResult[] = $this->add($r);
		// удаление не сопоставленных записей в HL, есть возможность отфильтровать удаляемые записи через deleteFilter()
		if($arToDelete = array_diff($arIds,array_column($arUpdate,'ID')))
			foreach($arExistedRows as $r)
				if(in_array($r['ID'],$arToDelete) && (empty($this->fnDeleteFilter) || $this->fnDeleteFilter($r)))
					$this->hlblock::delete($r['ID']);			
		if($arUpdate)
			foreach($arUpdate as $r)
				$arResult[] = $this->update($r['ID'],$r);

		return $arResult;
	}

	/**
	 * Возвращает имя класса таблицы HL-блока
	 * @param string $tableName
	 * @return string
	 * @throws \Exception
	 */
	protected function hlClass($tableName){
		if(!is_array(self::$arHlClasses))
			self::$arHlClasses = [];
		if(!isset(self::$arHlClasses[$tableName]))
			self::$arHlClasses[$tableName] = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity(
					\Bitrix\Highloadblock\HighloadBlockTable::query()
						->setSelect(['*'])
						->where('TABLE_NAME',$tableName)
						->exec()->fetch()
				)->getDataClass();
		if(isset(self::$arHlClasses[$tableName]))
			return self::$arHlClasses[$tableName];
		throw new \Exception('Highload entity class not found');
	}
	
	// методы интерфейса Iterator
	
	public function rewind(){ reset($this->arValues); }

	public function current(){ return current($this->arValues); }

	public function key(){ return key($this->arValues); }

	public function next(){ return next($this->arValues); }

	public function valid(){
		$key = key($this->arValues);
		return $key !== null && $key !== false;
	}

}
