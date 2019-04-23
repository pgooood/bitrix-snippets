<?php

namespace forumedia\common;

/**
 * Description of propLinkedElement
 *
 * @author pgood
 */
class propLinkedElement{
	static protected $arValues;
	protected $arProp,$iblock;

	function __construct(iblock $iblock,$propCode){
		if(!($this->arProp = $iblock->prop($propCode)))
			throw new \Exception('Propery ['.$propCode.'] is not found');
		if($this->arProp['PROPERTY_TYPE'] != 'E')
			throw new \Exception('Invalid propery ['.$propCode.'] type. It\'s should be linked element.');
		$this->iblock = new \forumedia\common\iblock($this->arProp['LINK_IBLOCK_ID']);
	}
	
	/**
	 * Возвращает значение для сохранения в свойстве инфоблока
	 * @param string $name
	 * @return integer
	 */
	function valueId($name,$arProps = null){
		if(isset(self::$arValues[$this->arProp['ID']][$name]))
			return self::$arValues[$this->arProp['ID']][$name];
		return self::$arValues[$this->arProp['ID']][$name] = ($arEl = $this->get($name))
			? $arEl['ID']
			: $this->insert($name,$arProps);
	}
	
	function get($name){
		if(mb_strlen($name))
			return $this->iblock->getElement([[
					'LOGIC' => 'OR'
					,'EXTERNAL_ID' => $this->externalId($name)
					,'NAME' => $name
				]],['ID']);
	}

	function insert($name,$arProps = null){
		return $this->iblock->add([
				'MODIFIED_BY' => $GLOBALS['USER']->GetID()
				,'NAME' => $name
				,'CODE' => \Cutil::translit($name,'ru',['replace_space' => '-','replace_other' => '-'])
				,'ACTIVE' => 'Y'
			],$arProps);
	}

	protected function externalId($name){
		return 'auto-import::'.\Cutil::translit($name,'ru',['replace_space' => '-','replace_other' => '-']);
	}

}
