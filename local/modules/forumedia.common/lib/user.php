<?php
namespace forumedia\common;

/**
 * Description of user
 *
 * @author dev_pavel
 */
class user{
	protected $id;
	
	function __construct($id){
		$this->id = \intval($id);
		if(!($this->id > 0))
			throw new \Exception('invalid user id');
	}
	
	static function current(){
		if($id = $GLOBALS['USER']->GetID())
			return new self($id);
	}
	
	function id(){
		return $this->id;
	}
	
	/**
	 * Обертка для метода CUser::GetByID
	 * @return array
	 */
	function toArray(){
		return \CUser::GetByID($this->id())->Fetch();
	}
	
	/**
	 * Проверяет состоит ли пользователь в заданных группах
	 * @param mixed $groupId айди группы, или символьный код группы, или массив айди|кодов
	 * @return boolean
	 * @throws \Exception выбрасывается в случае, если группа с заданным символьным кодом не найдена
	 */
	function inGroup($groupId){
		if(!is_array($groupId))
			$groupId = [$groupId];
		if(($arGroups = array_filter([$groupId]))
			&& ($arGoupIds = \CUser::GetUserGroup($this->id()))
		){
			foreach($arGroups as $groupId){
				if(!is_numeric($groupId) && !($groupId = self::groupId($groupId)))
					throw new \Exception('Group not found');
				if(!in_array($groupId,$arGoupIds))
					return false;
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Обертка для метода CUser::GetList
	 * @param array $arFilter соответствует параметру filter
	 * @param array $arSelect соответствует параметру arParameters['SELECT']
	 *		массив с идентификаторами пользовательских полей для их
	 *		выборки в результат, например array("UF_TEXT_1", "UF_STRUCTURE").
	 *		Для указания выборки всех полей используйте маску: array("UF_*").
	 * @param array $arProps
	 *		ключ массива nav соответствует параметру arParameters['NAV_PARAMS'] - массив с параметрами навигации
	 *		ключ массива fields соответствует параметру arParameters['FIELDS'] - массив с идентификаторами полей для выборки
	 *		ключ массива sort соответствует параметру by - массив вида ['field1' => 'asc', 'field2' => 'desc']
	 * @return \CIBlockResult
	 */
	static function getList($arFilter,$arSelect = null,$arProps = null){
		if(isset($arFilter['ID']) && \is_array($arFilter['ID']))
			$arFilter['ID'] = \implode(' | ',$arFilter['ID']);
		return \CUser::GetList(
			($arSort = empty($arProps['sort']) ? ['id' => 'asc'] : $arProps['sort'])
			,($tmp = '')
			,$arFilter
			,[
				'SELECT' => $arSelect
				,'NAV_PARAMS' => isset($arProps['nav']) ? $arProps['nav'] : null
				,'FIELDS' => isset($arProps['fields']) ? $arProps['fields'] : null
			]
		);
	}
	
	/**
	 * Возвращает идентификатор группы по ее коду
	 * @param string $code
	 * @return integer
	 */
	static function groupId($code){
		if($code
			&& ($r = \Bitrix\Main\GroupTable::getList([
				'select' => ['ID']
				,'filter' => ['STRING_ID' => $code]
				,'cache' => ['ttl' => 60,'cache_joins' => true]
			])->fetch())
		){
			return \intval($r['ID']);
		}
	}
}

