<?php
/**
 * За основу взят компонент bitrix:catalog.filter
 * - компонент разбил на логические части и упаковал в класс
 * - убраны цены и товарные предложения
 * - добавлен тип поля "интервал дат" для свойств инфоблока типа дата
 * - добавлен поиск по имени связанного элемента (ввиде текстового поля ввода и ввиде селекта с со списком элементов)
 * - добавлена возможность переопределять тип поля и заголовок из настроек компонента
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

class registryFilter extends CBitrixComponent{

	protected $filterName, $arrPFV, $arrFFV, $arrDFV, $arrCFV, $arTypeOverride, $arLabelsOverride;

	function executeComponent(){
		if(!CModule::IncludeModule("iblock"))
			throw new Exception(GetMessage("CC_BCF_MODULE_NOT_INSTALLED"));
		$this->initParams();
		$this->initFilterValues();
		$this->initCachedData();
		$this->initFields();
		$this->includeComponentTemplate();
	}
	
	function isPlaceholderMode(){
		return !empty($this->arParams['PLACEHOLDER_MODE']) && $this->arParams['PLACEHOLDER_MODE'] == 'Y';
	}

	/**
	 * Adding the titles and input fields
	 */
	function initFields(){
		// array of the input field names; is being used in the function $GLOBALS['APPLICATION']->GetCurPageParam
		$this->arResult["arrInputNames"] = array();
		// simple fields
		$this->arResult["ITEMS"] = array();
		foreach($this->arParams["FIELD_CODE"] as $field_code){
			$field_res = "";
			$field_type = "";
			$field_names = "";
			$field_values = "";
			$field_list = array();
			$this->arResult["arrInputNames"][$this->filterName . "_ff"] = true;
			$name = $this->filterName . "_ff[" . $field_code . "]";
			$value = $this->arrFFV[$field_code];
			switch($field_code){
				case "CODE":
				case "XML_ID":
				case "NAME":
				case "PREVIEW_TEXT":
				case "DETAIL_TEXT":
				case "IBLOCK_TYPE_ID":
				case "IBLOCK_ID":
				case "IBLOCK_CODE":
				case "IBLOCK_NAME":
				case "IBLOCK_EXTERNAL_ID":
				case "SEARCHABLE_CONTENT":
				case "TAGS":
					if(!is_array($value)){
						$field_res = self::input('text', $name, $value, array('size' => $this->arParams["TEXT_WIDTH"]));
						if(strlen($value) > 0)
							$GLOBALS[$this->filterName]["?" . $field_code] = $value;
						$field_type = 'INPUT';
					}
					break;
				case "ID":
				case "SORT":
				case "SHOW_COUNTER":
					$name_left = $this->filterName . "_ff[" . $field_code . "][LEFT]";
					if(is_array($value) && isset($value["LEFT"]))
						$value_left = $value["LEFT"];
					else
						$value_left = "";
					$field_res = self::input('text', $name_left, $value_left, array('size' => $this->arParams["NUMBER_WIDTH"]))
								 . '&nbsp;' . GetMessage("CC_BCF_TILL") . '&nbsp;';

					if(strlen($value_left) > 0)
						$GLOBALS[$this->filterName][">=" . $field_code] = intval($value_left);

					$name_right = $this->filterName . "_ff[" . $field_code . "][RIGHT]";
					if(is_array($value) && isset($value["RIGHT"]))
						$value_right = $value["RIGHT"];
					else
						$value_right = "";
					$field_res = self::input('text', $name_right, $value_right, array('size' => $this->arParams["NUMBER_WIDTH"]));

					if(strlen($value_right) > 0)
						$GLOBALS[$this->filterName]["<=" . $field_code] = intval($value_right);

					$field_type = 'RANGE';
					$field_names = array($name_left, $name_right);
					$field_values = array($value_left, $value_right);
					break;
				case "SECTION_ID":
					$arrRef = array("reference" => array_values($this->arResult["arrSection"]), "reference_id" => array_keys($this->arResult["arrSection"]));
					$field_res = SelectBoxFromArray($name, $arrRef, $value, " ", "");

					if(!is_array($value) && $value != "NOT_REF" && strlen($value) > 0)
						$GLOBALS[$this->filterName][$field_code] = intval($value);

					$_name = $this->filterName . "_ff[INCLUDE_SUBSECTIONS]";
					$_value = $this->arrFFV["INCLUDE_SUBSECTIONS"];
					$field_res .= "<br>" . InputType("checkbox", $_name, "Y", $_value, false, "", "") . "&nbsp;" . GetMessage("CC_BCF_INCLUDE_SUBSECTIONS");

					if(isset($GLOBALS[$this->filterName][$field_code]) && $_value == "Y")
						$GLOBALS[$this->filterName]["INCLUDE_SUBSECTIONS"] = "Y";

					$field_type = 'SELECT';
					$field_list = $this->arResult["arrSection"];
					break;
				case "ACTIVE_DATE":
				case "DATE_ACTIVE_FROM":
				case "DATE_ACTIVE_TO":
				case "DATE_CREATE":
					$arDateField = $this->arrDFV[$field_code];
					$this->arResult["arrInputNames"][$arDateField["from"]["name"]] = true;
					$this->arResult["arrInputNames"][$arDateField["to"]["name"]] = true;
					$this->arResult["arrInputNames"][$arDateField["days_to_back"]["name"]] = true;

					ob_start();
					$GLOBALS['APPLICATION']->IncludeComponent(
						'bitrix:main.calendar', '', array(
						'FORM_NAME' => $this->filterName . "_form",
						'SHOW_INPUT' => 'Y',
						'INPUT_NAME' => $arDateField["from"]["name"],
						'INPUT_VALUE' => $arDateField["from"]["value"],
						'INPUT_NAME_FINISH' => $arDateField["to"]["name"],
						'INPUT_VALUE_FINISH' => $arDateField["to"]["value"],
						'INPUT_ADDITIONAL_ATTR' => 'size="10" class="inputselect inputfield"',
						), null, array('HIDE_ICONS' => 'Y')
					);
					$field_res = ob_get_contents();
					ob_end_clean();

					if(strlen($arDateField["from"]["value"]) > 0)
						$GLOBALS[$this->filterName][$arDateField["filter_from"]] = $arDateField["from"]["value"];

					if(strlen($arDateField["to"]["value"]) > 0)
						$GLOBALS[$this->filterName][$arDateField["filter_to"]] = $arDateField["to"]["value"];

					$field_type = 'DATE_RANGE';
					$field_names = array($arDateField["from"]["name"], $arDateField["to"]["name"]);
					$field_values = array($arDateField["from"]["value"], $arDateField["to"]["value"]);
					break;
			}

			if($field_res){
				$this->arResult["ITEMS"][$field_code] = array(
					"NAME" => htmlspecialcharsbx(GetMessage("IBLOCK_FIELD_" . $field_code)),
					"INPUT" => $field_res,
					"INPUT_NAME" => $name,
					"INPUT_VALUE" => is_array($value) ? array_map("htmlspecialcharsbx", $value) : htmlspecialcharsbx($value),
					"~INPUT_VALUE" => $value,
					"TYPE" => $field_type,
					"INPUT_NAMES" => $field_names,
					"INPUT_VALUES" => is_array($field_values) ? array_map("htmlspecialcharsbx", $field_values) : htmlspecialcharsbx($field_values),
					"~INPUT_VALUES" => $field_values,
					"LIST" => $field_list,
					"CODE" => $field_code,
				);
			}
		}
		foreach($this->arResult["arrProp"] as $prop_id => $arProp){
			$res = "";
			$name = "";
			$value = "";
			$type = "";
			$names = "";
			$values = "";
			$list = array();
			$this->arResult["arrInputNames"][$this->filterName . "_pf"] = true;
			if(isset($this->arTypeOverride[$arProp["CODE"]]))
				$arProp["PROPERTY_TYPE"] = $this->arTypeOverride[$arProp["CODE"]];
			if(isset($this->arLabelsOverride[$arProp["CODE"]]))
				$arProp["NAME"] = $this->arLabelsOverride[$arProp["CODE"]];
			switch($arProp["PROPERTY_TYPE"]){
				case "L":
					$name = $this->filterName . "_pf[" . $arProp["CODE"] . "]";
					$value = $this->arrPFV[$arProp["CODE"]];

					if('C' == $arProp['LIST_TYPE']){
						$arListRadio = array();
						if('Y' == $arProp['MULTIPLE']){
							$type = "CHECKBOX";
							$list = $arProp["VALUE_LIST"];
							$arListValue = (is_array($value) ? $value : array($value));
							foreach($arProp["VALUE_LIST"] as $key => $val){
								$arListRadio[] = self::input('checkbox', $name.'[]', $key, array('checked' => in_array($key, $arListValue))) . htmlspecialcharsex($val);
							}
						}else{
							$type = "RADIO";
							$list[""] = GetMessage("CC_BCF_ALL");
							$arListRadio[] = self::input('radio', $name, '', array('checked' => $key == $value)).' '.GetMessage("CC_BCF_ALL");
							foreach($arProp["VALUE_LIST"] as $key => $val){
								$arListRadio[] = self::input('radio', $name, $key, array('checked' => $key == $value)) . htmlspecialcharsex($val);
								$list[$key] = $val;
							}
						}
						$res .= implode('<br>', $arListRadio);
					}else{
						$type = 'SELECT';
						
						$arOptions = array(array('',$this->isPlaceholderMode()
							? $arProp['NAME']
							: GetMessage("CC_BCF_ALL")));
						$list[""] = GetMessage("CC_BCF_ALL");
						foreach($arProp["VALUE_LIST"] as $key => $val){
							if($arProp["MULTIPLE"] == "Y" && is_array($value))
								$arOptions[] = array($key,$val,in_array($key, $value));
							else
								$arOptions[] = array($key,$val,$key == $value);
							$list[$key] = $val;
						}
						if($arProp["MULTIPLE"] == "Y")
							$res .= self::select($name.'[]', $arOptions,array('multiple' => true));
						else
							$res .= self::select($name, $arOptions);
					}

					if($arProp["MULTIPLE"] == "Y"){
						if(is_array($value) && count($value) > 0)
							$GLOBALS[$this->filterName]["PROPERTY"][$arProp["CODE"]] = $value;
					}
					else{
						if(!is_array($value) && strlen($value) > 0)
							$GLOBALS[$this->filterName]["PROPERTY"][$arProp["CODE"]] = $value;
					}
					break;
				case "N":
					$value = $this->arrPFV[$arProp["CODE"]];
					$name_left = $this->filterName . "_pf[" . $arProp["CODE"] . "][LEFT]";
					if(is_array($value) && isset($value["LEFT"]))
						$value_left = $value["LEFT"];
					else
						$value_left = "";
					$res .= self::input('text',$name_left,$value_left,array('size' => $this->arParams["NUMBER_WIDTH"]))
							.'&nbsp;'.GetMessage("CC_BCF_TILL").'&nbsp;';

					if(strlen($value_left) > 0)
						$GLOBALS[$this->filterName]["PROPERTY"][">=" . $arProp["CODE"]] = doubleval($value_left);

					$name_right = $this->filterName . "_pf[" . $arProp["CODE"] . "][RIGHT]";
					if(is_array($value) && isset($value["RIGHT"]))
						$value_right = $value["RIGHT"];
					else
						$value_right = "";
					$res .= self::input('text',$name_right,$value_right,array('size' => $this->arParams["NUMBER_WIDTH"]));

					if(strlen($value_right) > 0)
						$GLOBALS[$this->filterName]["PROPERTY"]["<=" . $arProp["CODE"]] = doubleval($value_right);

					$type = 'RANGE';
					$names = array($name_left, $name_right);
					$values = array($value_left, $value_right);
					break;
				case "S":
				case "E":
				case "G":
					$name = $this->filterName . "_pf[" . $arProp["CODE"] . "]";
					$value = $this->arrPFV[$arProp["CODE"]];
					$arAttrs = array('size' => $this->arParams["TEXT_WIDTH"]);
					if($this->isPlaceholderMode())
						$arAttrs['placeholder'] = $arProp['NAME'];
					if(!is_array($value)){
						$res .= self::input('text',$name,$value,$arAttrs);

						if(strlen($value) > 0)
							$GLOBALS[$this->filterName]["PROPERTY"]["?" . $arProp["CODE"]] = $value;
					}
					$type = 'INPUT';
					break;
				
				//для поиска по имени связанного элемента инфоблока
				case "LINKED_NAME":
					$name = $this->filterName . "_pf[" . $arProp["CODE"] . "]";
					$value = $this->arrPFV[$arProp["CODE"]];
					$arAttrs = array('size' => $this->arParams["TEXT_WIDTH"]);
					if($this->isPlaceholderMode())
						$arAttrs['placeholder'] = $arProp['NAME'];
					if(!is_array($value)){
						$res .= self::input('text',$name,$value,$arAttrs);

						if(strlen($value) > 0)
							$GLOBALS[$this->filterName]['PROPERTY_'.$arProp["CODE"].'.NAME'] = '%'.$value.'%';
					}
					$type = 'INPUT';
					break;
					
				//селект для выбора связанного элемента
				case "LINKED_LIST":
					$name = $this->filterName . "_pf[" . $arProp["CODE"] . "]";
					$value = $this->arrPFV[$arProp["CODE"]];
					$type = 'SELECT';
					$arOptions = array(array('',$this->isPlaceholderMode()
						? $arProp['NAME']
						: GetMessage("CC_BCF_ALL")));
					$list[""] = GetMessage("CC_BCF_ALL");
					foreach($arProp["VALUE_LIST"] as $key => $val){
						if($arProp["MULTIPLE"] == "Y" && is_array($value))
							$arOptions[] = array($key,$val,in_array($key, $value));
						else
							$arOptions[] = array($key,$val,$key == $value);
						$list[$key] = $val;
					}
					if($arProp["MULTIPLE"] == "Y")
						$res .= self::select($name.'[]', $arOptions,array('multiple' => true));
					else
						$res .= self::select($name, $arOptions);
					
					if(strlen($value) > 0)
						$GLOBALS[$this->filterName]['PROPERTY_'.$arProp["CODE"]] = $value;
					break;
				
				//для поиска по интервалу дат
				case "DATE_RANGE":
					$arDateField = $this->arrDFV[$arProp["CODE"]];
					$this->arResult["arrInputNames"][$arDateField["from"]["name"]] = true;
					$this->arResult["arrInputNames"][$arDateField["to"]["name"]] = true;
					$this->arResult["arrInputNames"][$arDateField["days_to_back"]["name"]] = true;
					
					ob_start();
					$GLOBALS['APPLICATION']->IncludeComponent(
						'bitrix:main.calendar', '', array(
						'FORM_NAME' => $this->filterName . "_form",
						'SHOW_INPUT' => 'Y',
						'INPUT_NAME' => $arDateField["from"]["name"],
						'INPUT_VALUE' => $arDateField["from"]["value"],
						'INPUT_NAME_FINISH' => $arDateField["to"]["name"],
						'INPUT_VALUE_FINISH' => $arDateField["to"]["value"],
						'INPUT_ADDITIONAL_ATTR' => 'size="10" class="inputselect inputfield"',
						), null, array('HIDE_ICONS' => 'Y')
					);
					$res = ob_get_contents();
					ob_end_clean();

					if(strlen($arDateField["from"]["value"]) > 0)
						$GLOBALS[$this->filterName][$arDateField["filter_from"]] = ConvertDateTime($arDateField["from"]["value"],'YYYY-MM-DD').' 00:00:00';

					if(strlen($arDateField["to"]["value"]) > 0)
						$GLOBALS[$this->filterName][$arDateField["filter_to"]] = ConvertDateTime($arDateField["to"]["value"],'YYYY-MM-DD').' 00:00:00';

					$type = 'DATE_RANGE';
					$names = array($arDateField["from"]["name"], $arDateField["to"]["name"]);
					$values = array($arDateField["from"]["value"], $arDateField["to"]["value"]);
					break;
			}
			if($res){
				//$this->arResult["ITEMS"]["PROPERTY_" . $prop_id] = array(
				//ключи полей делаем по коду свойства, а не по айди
				$this->arResult["ITEMS"]['PROPERTY_'.$arProp['CODE']] = array(
					"NAME" => htmlspecialcharsbx($arProp["NAME"]),
					"INPUT" => $res,
					"INPUT_NAME" => $name,
					"INPUT_VALUE" => is_array($value) ? array_map("htmlspecialcharsbx", $value) : htmlspecialcharsbx($value),
					"~INPUT_VALUE" => $value,
					"TYPE" => $type,
					"INPUT_NAMES" => $names,
					"INPUT_VALUES" => is_array($values) ? array_map("htmlspecialcharsbx", $values) : htmlspecialcharsbx($values),
					"~INPUT_VALUES" => $values,
					"LIST" => $list,
					"CODE" => $arProp["CODE"],
				);
			}
		}
		if(!empty($this->arParams["PAGER_PARAMS_NAME"])
			&& preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $this->arParams["PAGER_PARAMS_NAME"])
		){
			if(!is_array($GLOBALS[$this->arParams["PAGER_PARAMS_NAME"]]))
				$GLOBALS[$this->arParams["PAGER_PARAMS_NAME"]] = array();

			foreach($this->arResult["ITEMS"] as $arItem){
				if(isset($arItem["INPUT_NAMES"]) && is_array($arItem["INPUT_NAMES"])){
					foreach($arItem["INPUT_NAMES"] as $i => $name){
						$value = $arItem["~INPUT_VALUES"][$i];
						if(strlen($value) > 0){
							$GLOBALS[$this->arParams["PAGER_PARAMS_NAME"]][$name] = $value;
						}
					}
				}elseif(isset($arItem["INPUT_NAME"]) && is_array($arItem["~INPUT_VALUE"])){
					foreach($arItem["~INPUT_VALUE"] as $value){
						if(strlen($value) > 0){
							$GLOBALS[$this->arParams["PAGER_PARAMS_NAME"]][$arItem["INPUT_NAME"]][] = $value;
						}
					}
				}elseif(isset($arItem["INPUT_NAME"]) && strlen($arItem["~INPUT_VALUE"]) > 0){
					$GLOBALS[$this->arParams["PAGER_PARAMS_NAME"]][$arItem["INPUT_NAME"]] = $arItem["~INPUT_VALUE"];
				}
			}

			if(strlen($_REQUEST["del_filter"]) > 0){
				//$GLOBALS[$this->arParams["PAGER_PARAMS_NAME"]]["del_filter"] = $_REQUEST["del_filter"];
			}elseif(strlen($_REQUEST["set_filter"]) > 0){
				$GLOBALS[$this->arParams["PAGER_PARAMS_NAME"]]["set_filter"] = $_REQUEST["set_filter"];
			}
		}

		$this->arResult["arrInputNames"]["set_filter"] = true;
		$this->arResult["arrInputNames"]["del_filter"] = true;

		$arSkip = array(
			"AUTH_FORM" => true,
			"TYPE" => true,
			"USER_LOGIN" => true,
			"USER_CHECKWORD" => true,
			"USER_PASSWORD" => true,
			"USER_CONFIRM_PASSWORD" => true,
			"USER_EMAIL" => true,
			"captcha_word" => true,
			"captcha_sid" => true,
			"login" => true,
			"Login" => true,
			"backurl" => true,
		);

		foreach(array_merge($_GET, $_POST) as $key => $value){
			if(!isset($this->arResult["arrInputNames"][$key])
				&& !isset($arSkip[$key])
			){
				$this->arResult["ITEMS"]["HIDDEN_" . htmlspecialcharsEx($key)] = array(
					"HIDDEN" => true,
					"INPUT" => self::input('hidden',$key,$value)
				);
			}
		}
	}

	function initCachedData(){
		$cache_id = serialize(array(
			__CLASS__
			, $this->arParams
			, ($this->arParams['CACHE_GROUPS'] === 'N' ? false : $GLOBALS['USER']->GetGroups()
			)
		));
		$obCache = new CPHPCache;
		if($obCache->InitCache($this->arParams['CACHE_TIME'], $cache_id, '/')){
			$vars = $obCache->GetVars();
			$this->arResult = $vars['arResult'];
		}elseif($obCache->StartDataCache()){

			$this->arResult["arrProp"] = array();
			$this->arResult["arrPrice"] = array();
			$this->arResult["arrSection"] = array();
			$this->arResult["arrOfferProp"] = array();

			// simple fields
			if(in_array("SECTION_ID", $this->arParams["FIELD_CODE"])){
				$this->arResult["arrSection"][0] = GetMessage("CC_BCF_TOP_LEVEL");
				$rsSection = CIBlockSection::GetList(
						Array("left_margin" => "asc"), Array(
						"IBLOCK_ID" => $this->arParams["IBLOCK_ID"],
						"ACTIVE" => "Y",
						), false, Array("ID", "DEPTH_LEVEL", "NAME")
				);
				while($arSection = $rsSection->Fetch()){
					$this->arResult["arrSection"][$arSection["ID"]] = str_repeat(" . ", $arSection["DEPTH_LEVEL"]) . $arSection["NAME"];
				}
			}

			// properties
			$rsProp = CIBlockProperty::GetList(Array("sort" => "asc", "name" => "asc"), Array("ACTIVE" => "Y", "IBLOCK_ID" => $this->arParams["IBLOCK_ID"]));
			while($arProp = $rsProp->Fetch()){
				if(in_array($arProp["CODE"], $this->arParams["PROPERTY_CODE"]) && $arProp["PROPERTY_TYPE"] != "F"){
					$arTemp = array(
						"CODE" => $arProp["CODE"],
						"NAME" => $arProp["NAME"],
						"PROPERTY_TYPE" => $arProp["PROPERTY_TYPE"],
						"MULTIPLE" => $arProp["MULTIPLE"],
					);
					if($arProp["PROPERTY_TYPE"] == "L"){
						$arTemp['LIST_TYPE'] = $arProp['LIST_TYPE'];
						$arrEnum = array();
						$rsEnum = CIBlockProperty::GetPropertyEnum($arProp["ID"]);
						while($arEnum = $rsEnum->Fetch()){
							$arrEnum[$arEnum["ID"]] = $arEnum["VALUE"];
						}
						$arTemp["VALUE_LIST"] = $arrEnum;
					}elseif($arProp["PROPERTY_TYPE"] == "E"
						&& isset($this->arTypeOverride[$arProp['CODE']])
						&& 'LINKED_LIST' == $this->arTypeOverride[$arProp['CODE']]
						&& !empty($arProp['LINK_IBLOCK_ID'])
					){
						$arTemp['VALUE_LIST'] = array();
						$navNum = $GLOBALS['NavNum']; //фиксим баг с увеличением номера параметра пагинации
						$rs = \CIBlockElement::GetList(
							array('SORT' => 'ASC', 'NAME' => 'ASC')
							, array('IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'],'ACTIVE' => 'Y')
							, false
							, array('nPageSize' => 500)
							, array('ID','NAME')
						);
						$GLOBALS['NavNum'] = $navNum; //возвращаем номер параметра, который был перед запросом
						while($r = $rs->GetNext(false, false))
							$arTemp['VALUE_LIST'][$r['ID']] = $r['NAME'];
					}
					$this->arResult["arrProp"][$arProp["ID"]] = $arTemp;
				}
			}
			$obCache->EndDataCache(array(
				'arResult' => $this->arResult
			));
		}
		
		$this->arResult["FORM_ACTION"] = !empty($this->arParams['FORM_ACTION'])
			? $this->arParams['FORM_ACTION']
			: htmlspecialcharsbx($_SERVER['REQUEST_URI']);
		$this->arResult["FILTER_NAME"] = $this->filterName;
	}

	function initFilterValues(){
		$this->arrPFV = array();
		$this->arrCFV = array();
		$this->arrFFV = array(); //Element fields value
		$this->arrDFV = array(); //Element date fields
		$arDateFields = array(
			"ACTIVE_DATE" => array(
				"from" => "_ACTIVE_DATE_1",
				"to" => "_ACTIVE_DATE_2",
				"days_to_back" => "_ACTIVE_DATE_1_DAYS_TO_BACK",
				"filter_from" => ">=DATE_ACTIVE_FROM",
				"filter_to" => "<=DATE_ACTIVE_TO",
			),
			"DATE_ACTIVE_FROM" => array(
				"from" => "_DATE_ACTIVE_FROM_1",
				"to" => "_DATE_ACTIVE_FROM_2",
				"days_to_back" => "_DATE_ACTIVE_FROM_1_DAYS_TO_BACK",
				"filter_from" => ">=DATE_ACTIVE_FROM",
				"filter_to" => "<=DATE_ACTIVE_FROM",
			),
			"DATE_ACTIVE_TO" => array(
				"from" => "_DATE_ACTIVE_TO_1",
				"to" => "_DATE_ACTIVE_TO_2",
				"days_to_back" => "_DATE_ACTIVE_TO_1_DAYS_TO_BACK",
				"filter_from" => ">=DATE_ACTIVE_TO",
				"filter_to" => "<=DATE_ACTIVE_TO",
			),
			"DATE_CREATE" => array(
				"from" => "_DATE_CREATE_1",
				"to" => "_DATE_CREATE_2",
				"days_to_back" => "_DATE_CREATE_1_DAYS_TO_BACK",
				"filter_from" => ">=DATE_CREATE",
				"filter_to" => "<=DATE_CREATE",
			),
		);
		foreach($this->arTypeOverride as $prop => $type){
			if($type == 'DATE_RANGE'){
				$arDateFields[$prop] = array(
					"from" => "_{$prop}_1",
					"to" => "_{$prop}_2",
					"days_to_back" => "_{$prop}_1_DAYS_TO_BACK",
					"filter_from" => ">=PROPERTY_$prop",
					"filter_to" => "<=PROPERTY_$prop",
				);
			}
		}
		foreach($arDateFields as $id => $arField){
			$arField["from"] = array(
				"name" => $this->filterName . $arField["from"],
				"value" => "",
			);
			$arField["to"] = array(
				"name" => $this->filterName . $arField["to"],
				"value" => "",
			);
			$arField["days_to_back"] = array(
				"name" => $this->filterName . $arField["days_to_back"],
				"value" => "",
			);
			$this->arrDFV[$id] = $arField;
		}

		/* Leave filter values empty */
		if(strlen($_REQUEST["del_filter"]) > 0){
			foreach($this->arrDFV as $id => $arField)
				$GLOBALS[$arField["days_to_back"]["name"]] = "";
		}
		/* Read filter values from request */
		elseif(strlen($_REQUEST["set_filter"]) > 0){
			if(isset($_REQUEST[$this->filterName . "_pf"]))
				$this->arrPFV = $_REQUEST[$this->filterName . "_pf"];
			if(isset($_REQUEST[$this->filterName . "_cf"]))
				$this->arrCFV = $_REQUEST[$this->filterName . "_cf"];
			if(isset($_REQUEST[$this->filterName . "_ff"]))
				$this->arrFFV = $_REQUEST[$this->filterName . "_ff"];

			$now = time();
			foreach($this->arrDFV as $id => $arField){
				$name = $arField["from"]["name"];
				if(isset($_REQUEST[$name]))
					$this->arrDFV[$id]["from"]["value"] = $_REQUEST[$name];

				$name = $arField["to"]["name"];
				if(isset($_REQUEST[$name]))
					$this->arrDFV[$id]["to"]["value"] = $_REQUEST[$name];

				$name = $arField["days_to_back"]["name"];
				if(isset($_REQUEST[$name])){
					$value = $this->arrDFV[$id]["days_to_back"]["value"] = $_REQUEST[$name];
					if(strlen($value) > 0)
						$this->arrDFV[$id]["from"]["value"] = GetTime($now - 86400 * intval($value));
				}
			}
		}
		/* No action specified, so read from the session (if parameter is set) */
		elseif($this->arParams["SAVE_IN_SESSION"]){
			if(isset($_SESSION[$this->filterName . "arrPFV"]))
				$this->arrPFV = $_SESSION[$this->filterName . "arrPFV"];
			if(isset($_SESSION[$this->filterName . "arrCFV"]))
				$this->arrCFV = $_SESSION[$this->filterName . "arrCFV"];
			if(isset($_SESSION[$this->filterName . "arrFFV"]))
				$this->arrFFV = $_SESSION[$this->filterName . "arrFFV"];
			if(isset($_SESSION[$this->filterName . "arrDFV"]) && is_array($_SESSION[$this->filterName . "arrDFV"])){
				foreach($_SESSION[$this->filterName . "arrDFV"] as $id => $arField){
					$this->arrDFV[$id]["from"]["value"] = $arField["from"]["value"];
					$this->arrDFV[$id]["to"]["value"] = $arField["to"]["value"];
					$this->arrDFV[$id]["days_to_back"]["value"] = $arField["days_to_back"]["value"];
				}
			}
		}

		/* Save filter values to the session */
		if($this->arParams["SAVE_IN_SESSION"]){
			$_SESSION[$this->filterName . "arrPFV"] = $this->arrPFV;
			$_SESSION[$this->filterName . "arrCFV"] = $this->arrCFV;
			$_SESSION[$this->filterName . "arrFFV"] = $this->arrFFV;
			$_SESSION[$this->filterName . "arrDFV"] = $this->arrDFV;
		}
	}

	function initParams(){
		if(!isset($this->arParams["CACHE_TIME"]))
			$this->arParams["CACHE_TIME"] = 36000000;

		unset($this->arParams["IBLOCK_TYPE"]); //was used only for IBLOCK_ID setup with Editor
		$this->arParams["IBLOCK_ID"] = intval($this->arParams["IBLOCK_ID"]);

		if(!is_array($this->arParams["FIELD_CODE"]))
			$this->arParams["FIELD_CODE"] = array();
		foreach($this->arParams["FIELD_CODE"] as $k => $v)
			if($v === "")
				unset($this->arParams["FIELD_CODE"][$k]);

		if(!is_array($this->arParams["PROPERTY_CODE"]))
			$this->arParams["PROPERTY_CODE"] = array();
		foreach($this->arParams["PROPERTY_CODE"] as $k => $v)
			if($v === "")
				unset($this->arParams["PROPERTY_CODE"][$k]);

		$this->arParams["SAVE_IN_SESSION"] = $this->arParams["SAVE_IN_SESSION"] == "Y";

		if(strlen($this->arParams["FILTER_NAME"]) <= 0 || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $this->arParams["FILTER_NAME"]))
			$this->arParams["FILTER_NAME"] = "arrFilter";
		$this->filterName = $this->arParams["FILTER_NAME"];

		$GLOBALS[$this->filterName] = array();

		$this->arParams["NUMBER_WIDTH"] = intval($this->arParams["NUMBER_WIDTH"]);
		if($this->arParams["NUMBER_WIDTH"] <= 0)
			$this->arParams["NUMBER_WIDTH"] = 5;
		$this->arParams["TEXT_WIDTH"] = intval($this->arParams["TEXT_WIDTH"]);
		if($this->arParams["TEXT_WIDTH"] <= 0)
			$this->arParams["TEXT_WIDTH"] = 20;
		$this->arParams["LIST_HEIGHT"] = intval($this->arParams["LIST_HEIGHT"]);
		if($this->arParams["LIST_HEIGHT"] <= 0)
			$this->arParams["LIST_HEIGHT"] = 5;
		
		$this->arTypeOverride = array();
		if(!empty($this->arParams['PROPERTY_FIELD_TYPE'])
			&& is_array($this->arParams['PROPERTY_FIELD_TYPE'])
		){
			foreach($this->arParams['PROPERTY_FIELD_TYPE'] as $str){
				$arVal = explode(':',$str);
				if(count($arVal) == 2)
					$this->arTypeOverride[trim($arVal[0])] = trim($arVal[1]);
			}
		}
		$this->arLabelsOverride = array();
		if(!empty($this->arParams['PROPERTY_FIELD_LABEL'])
			&& is_array($this->arParams['PROPERTY_FIELD_LABEL'])
		){
			foreach($this->arParams['PROPERTY_FIELD_LABEL'] as $str){
				$arVal = explode(':',$str);
				if(count($arVal) == 2)
					$this->arLabelsOverride[trim($arVal[0])] = trim($arVal[1]);
			}
		}
	}
	
	static function select($name,$arOptions,$arAttrs = null){
		if(!is_array($arAttrs))
			$arAttrs = array();
		$arAttrs['name'] = $name;
		if(!isset($arAttrs['class']))
			$arAttrs['class'] = 'form-control';
		$s = '<select';
		foreach($arAttrs as $name => $value){
			if(is_bool($value)){
				if($value)
					$s.= ' '.$name;
			}else
				$s.= ' '.$name.'="'.htmlspecialcharsbx($value).'"';
		}
		$s.= '>';
		if(is_array($arOptions))
			foreach($arOptions as $v => $data){
				$value = $data[0];
				$text = $data[1];
				$selected = isset($data[2]) && $data[2];
				$s.= '<option value="'.htmlspecialcharsbx($value).'"'.($selected ? ' selected' : null).'>'
						.$text.'</option>';
			}
		$s.= '</select>';
		return $s;
	}
	
	static function input($type,$name,$value,$arAttrs = null){
		if(!is_array($arAttrs))
			$arAttrs = array();
		$arAttrs['type'] = $type;
		$arAttrs['name'] = $name;
		$arAttrs['value'] = $value;
		switch($type){
			case 'radio':
			case 'checkbox':
				$arAttrs['class'] = 'form-check-input';
				break;
			case 'hidden':
				break;
			default:
				if(!isset($arAttrs['class']))
					$arAttrs['class'] = 'form-control';
		}
		$s = '<input';
		foreach($arAttrs as $name => $value){
			if(is_bool($value)){
				if($value)
					$s.= ' '.$name;
			}else
				$s.= ' '.$name.'="'.htmlspecialcharsbx($value).'"';
		}
		$s.= '>';
		return $s;
	}

}
