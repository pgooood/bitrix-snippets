<?php

namespace forumedia\common;

use Bitrix\Highloadblock as HL;

/**
 * Description of propLinkedHl
 *
 * @author pgood
 */
class propLinkedHl{

	static protected $arHlClasses,$arValues;
	protected $arProp,$hlblock;

	function __construct(iblock $iblock,$propCode){
		if(!($this->arProp = $iblock->prop($propCode)))
			throw new \Exception('Propery ['.$propCode.'] is not found');
		if($this->arProp['PROPERTY_TYPE'] != 'S' || empty($this->arProp['USER_TYPE_SETTINGS']['TABLE_NAME']))
			throw new \Exception('Invalid propery ['.$propCode.'] type. It\'s should be linked element.');
		$this->hlblock = $this->hlClass($this->arProp['USER_TYPE_SETTINGS']['TABLE_NAME']);
	}

	/**
	 * Возвращает значение для сохранения в свойстве инфоблока
	 * @param string $name
	 * @return string
	 */
	function valueId($name){
		if(isset(self::$arValues[$this->hlblock][$name]))
			return self::$arValues[$this->hlblock][$name];
		if($arEl = $this->get($name))
			return self::$arValues[$this->hlblock][$name] = $arEl['UF_XML_ID'];
		elseif(($id = $this->insert($name)) && ($arEl = $this->hlblock::getRowById($id)))
			return self::$arValues[$this->hlblock][$name] = $arEl['UF_XML_ID'];
	}

	function get($name){
		if(mb_strlen($name) && ($rs = $this->hlblock::getList(['select' => ['*'],'filter' => ['UF_NAME' => $name]])))
			return $rs->fetch();
	}

	function insert($name){
		return $this->hlblock::add(array(
					'UF_NAME' => $name
					,'UF_XML_ID' => \Cutil::translit($name,'ru',['replace_space' => '-','replace_other' => '-'])
					,'UF_SORT' => $this->maxSort() + 10
				))->getId();
	}

	protected function externalId($name){
		return 'auto-import::'.\Cutil::translit($name,'ru',['replace_space' => '-','replace_other' => '-']);
	}

	protected function maxSort(){
		return ($r = $this->hlblock::query()->addSelect(new \Bitrix\Main\Entity\ExpressionField('MAX_SORT','MAX(UF_SORT)'))->exec()->fetch()) ? $r['MAX_SORT'] : 0;
	}

	protected function hlClass($tableName){
		if(!is_array(self::$arHlClasses))
			self::$arHlClasses = [];
		if(!isset(self::$arHlClasses[$tableName]))
			self::$arHlClasses[$tableName] = HL\HighloadBlockTable::compileEntity(HL\HighloadBlockTable::query()
									->addSelect('*')
									->where('TABLE_NAME',$tableName)
									->exec()->fetch())->getDataClass();
		if(isset(self::$arHlClasses[$tableName]))
			return self::$arHlClasses[$tableName];
		throw new \Exception('Highload entity class not found');
	}

}
