<?php
namespace forumedia\common;

/**
 * Объект пользователя, разные удобные утилиты
 *
 * @author Pavel Khoroshkov <pgood@forumedia.com>
 */
class user{
	protected $id;
	protected static $arUserGroupIds,$arGroupIds;
	
	function __construct($id){
		$this->id = \intval($id);
		if(!($this->id > 0))
			throw new \Exception('invalid user id');
	}
	
	/**
	 * Текущий пользователь
	 * @return \self
	 */
	static function current(){
		if($id = $GLOBALS['USER']->GetID())
			return new self($id);
	}
	
	static function currentIsAdmin(){
		if($user = static::current())
			return $user->isAdmin();
	}
	
	function id(){
		return $this->id;
	}
	
	function fullName(){
		$arUser = $this->toArray();
		return implode(' ',array_filter([$arUser['LAST_NAME'],$arUser['NAME'],$arUser['SECOND_NAME']]));
	}
	
	function fullInitialsName(){
		$arUser = $this->toArray();
		return $arUser['LAST_NAME']
				.($arUser['NAME'] ? ' '.substr($arUser['NAME'], 0, 1) . '.' : '')
				.($arUser['SECOND_NAME'] ? substr($arUser['SECOND_NAME'], 0, 1) . '.' : '');
	}
	
	function isAdmin(){
		if($this->isCurrent())
			return $GLOBALS['USER']->IsAdmin();
		return $this->inGroup('admins');
	}
	
	function isCurrent(){
		return $GLOBALS['USER']->GetID() == $this->id();
	}
	
	/**
	 * Идентификаторы палат, к котрым прикреплен пользователь
	 * @return array
	 */
	function chamberIds(){
		return ($r = $GLOBALS['DB']->Query('SELECT UF_MSB_CHAMBER FROM b_uts_user where VALUE_ID='.$this->id())->Fetch())
				&& !empty($r['UF_MSB_CHAMBER'])
			? unserialize($r['UF_MSB_CHAMBER'])
			: [];
	}

	/**
	 * Идентификаторы департаментов, к котрым прикреплен пользователь
	 * @return array
	 */
	function departmentIds(){
		return ($r = $GLOBALS['DB']->Query('SELECT UF_DEPARTMENT FROM b_uts_user where VALUE_ID='.$this->id())->Fetch())
				&& !empty($r['UF_DEPARTMENT'])
			? unserialize($r['UF_DEPARTMENT'])
			: [];
	}
	
	/**
	 * Проверяет принадлежит ли пользователь к заданным палатам
	 * @param integer|array $chumberId
	 * @return array - айди заданных палат, к которым принадлежит пользователь
	 */
	function inChamber($chumberId){
		return array_intersect(
			is_array($chumberId) ? $chumberId : array_filter([$chumberId])
			,$this->chamberIds());
	}
	
	/**
	 * Обертка для метода CUser::GetByID
	 * @return array
	 */
	function toArray(){
		return \CUser::GetByID($this->id())->Fetch();
	}
	
	/**
	 * Обертка для метода CUser::GetUserGroup
	 * кеширует данные в статическом свойстве класса
	 * @param boolean $reset сбрасывает кеш
	 * @return array
	 */
	function groups($reset = false){
		if(!isset(self::$arUserGroupIds[$this->id()]) || $reset)
			self::$arUserGroupIds[$this->id()] = \CUser::GetUserGroup($this->id());
		return self::$arUserGroupIds[$this->id()];
	}
	
	/**
	 * Проверяет состоит ли пользователь в заданных группах
	 * @param mixed $groups айди группы, или символьный код группы, или массив айди|кодов
	 * @param boolean $strict если true, то возвращает правду, только вслучае вхождения
	 *		пользователя во все группы заданные в аргументе $groupId, иначе возвращает
	 *		правду, если пользователь состоит хотя бы в одной из заданных групп
	 * @return boolean
	 * @throws \Exception выбрасывается в случае, если группа с заданным символьным кодом не найдена
	 */
	function inGroup($groups,$strict = false){
		if(($arGroups = array_filter(is_array($groups) ? $groups : [$groups]))
			&& ($arUserGoupIds = $this->groups())
		){
			$this->cacheGroupIds($arGroups); // кешируем айди групп для которых заданы символьные коды
			foreach($arGroups as $group){
				$groupId = is_numeric($group)
					? $group
					: self::groupId($group);
				if(!($groupId > 0))
					throw new \Exception('Group ['.$group.'] not found');
				if(in_array($groupId,$arUserGoupIds)){
					if(!$strict)
						return true;
				}elseif($strict)
					return false;
			}
			return $strict;
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
	 * Кеширует айди групп с заданными символьными кодами
	 * @param array $arGroups
	 */
	protected function cacheGroupIds($arGroups){
		if(is_array($arGroups)
			&& count($arGroups = array_filter($arGroups))
		){
			$arGroupCodes = [];
			foreach($arGroups as $groupId)
				if(!is_numeric($groupId) && !isset(self::$arGroupIds[$groupId]))
					$arGroupCodes[] = $groupId;
			if($arGroupCodes
				&& ($rs = \Bitrix\Main\GroupTable::getList([
					'select' => ['ID','STRING_ID']
					,'filter' => ['STRING_ID' => $arGroupCodes]
				]))
			){
				while($r = $rs->fetch())
					self::$arGroupIds[$r['STRING_ID']] = \intval($r['ID']);
			}
		}
	}
	
	/**
	 * Возвращает идентификатор группы по ее коду
	 * @param string $code
	 * @return integer
	 */
	static function groupId($code){
		if(isset(self::$arGroupIds[$code]))
			return self::$arGroupIds[$code];
		if($code
			&& ($r = \Bitrix\Main\GroupTable::getList([
				'select' => ['ID']
				,'filter' => ['STRING_ID' => $code]
				,'cache' => ['ttl' => 60,'cache_joins' => true]
			])->fetch())
		){
			return self::$arGroupIds[$code] = \intval($r['ID']);
		}
	}
}

