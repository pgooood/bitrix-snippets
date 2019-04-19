<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arCurrentValues */
use Bitrix\Main\Loader;
use Bitrix\Iblock;

if (!Loader::includeModule("iblock"))
	return;
$catalogIncluded = Loader::includeModule('catalog');
$iblockExists = (!empty($arCurrentValues['IBLOCK_ID']) && (int)$arCurrentValues['IBLOCK_ID'] > 0);

$arIBlockType = CIBlockParameters::GetIBlockTypes();

$arIBlock = array();
$iblockFilter = (
	!empty($arCurrentValues['IBLOCK_TYPE'])
	? array('TYPE' => $arCurrentValues['IBLOCK_TYPE'], 'ACTIVE' => 'Y')
	: array('ACTIVE' => 'Y')
);
$rsIBlock = CIBlock::GetList(array('SORT' => 'ASC'), $iblockFilter);
while ($arr = $rsIBlock->Fetch())
	$arIBlock[$arr['ID']] = '['.$arr['ID'].'] '.$arr['NAME'];
unset($arr, $rsIBlock, $iblockFilter);

$arProperty = array();
$arProperty_N = array();
if ($iblockExists)
{
	$propertyIterator = Iblock\PropertyTable::getList(array(
		'select' => array('ID', 'IBLOCK_ID', 'NAME', 'CODE', 'PROPERTY_TYPE'),
		'filter' => array('IBLOCK_ID' => $arCurrentValues['IBLOCK_ID'], '=ACTIVE' => 'Y'),
		'order' => array('SORT' => 'ASC', 'NAME' => 'ASC')
	));
	while ($property = $propertyIterator->fetch())
	{
		$propertyCode = (string)$property['CODE'];
		if ($propertyCode == '')
			$propertyCode = $property['ID'];
		$propertyName = '['.$propertyCode.'] '.$property['NAME'];

		if ($property['PROPERTY_TYPE'] != Iblock\PropertyTable::TYPE_FILE)
			$arProperty[$propertyCode] = $propertyName;

		if ($property['PROPERTY_TYPE'] == Iblock\PropertyTable::TYPE_NUMBER)
			$arProperty_N[$propertyCode] = $propertyName;
	}
	unset($propertyCode, $propertyName, $property, $propertyIterator);
}

$arComponentParameters = array(
	"PARAMETERS" => array(
		"IBLOCK_TYPE" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "Y",
			"VALUES" => $arIBlockType,
			"REFRESH" => "Y",
		),
		"IBLOCK_ID" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("IBLOCK_IBLOCK"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "Y",
			"VALUES" => $arIBlock,
			"REFRESH" => "Y",
		),
		"FILTER_NAME" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("IBLOCK_FILTER_NAME_OUT"),
			"TYPE" => "STRING",
			"DEFAULT" => "arrFilter",
		),
		"FIELD_CODE" => CIBlockParameters::GetFieldCode(
			GetMessage("IBLOCK_FIELD"),
			"DATA_SOURCE",
			array("SECTION_ID"=>true)
		),
		"PROPERTY_CODE" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("IBLOCK_PROPERTY"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => $arProperty,
			"ADDITIONAL_VALUES" => "Y",
		),
		"PROPERTY_FIELD_TYPE" => array(
			"PARENT" => "VISUAL",
			"NAME" => 'Переопределение типов полей',
			"TYPE" => "LIST",
			"VALUES" => array(),
			"ADDITIONAL_VALUES" => "Y",
		),
		"PROPERTY_FIELD_LABEL" => array(
			"PARENT" => "VISUAL",
			"NAME" => 'Переопределение заголовков полей',
			"TYPE" => "LIST",
			"VALUES" => array(),
			"ADDITIONAL_VALUES" => "Y",
		),
		"PROPERTY_FIELD_PATTERN" => array(
			"PARENT" => "VISUAL",
			"NAME" => 'Валидация полей',
			"TYPE" => "LIST",
			"VALUES" => array(),
			"ADDITIONAL_VALUES" => "Y",
		),
		"CACHE_TIME"  =>  Array("DEFAULT"=>36000000),
		"CACHE_GROUPS" => array(
			"PARENT" => "CACHE_SETTINGS",
			"NAME" => GetMessage("CP_BCF_CACHE_GROUPS"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"LIST_HEIGHT" => array(
			"PARENT" => "VISUAL",
			"NAME" => GetMessage("IBLOCK_LIST_HEIGHT"),
			"TYPE" => "STRING",
			"DEFAULT" => "5"
		),
		"TEXT_WIDTH" => array(
			"PARENT" => "VISUAL",
			"NAME" => GetMessage("IBLOCK_TEXT_WIDTH"),
			"TYPE" => "STRING",
			"DEFAULT" => "20"
		),
		"NUMBER_WIDTH" => array(
			"PARENT" => "VISUAL",
			"NAME" => GetMessage("IBLOCK_NUMBER_WIDTH"),
			"TYPE" => "STRING",
			"DEFAULT" => "5"
		),
		"SAVE_IN_SESSION" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("IBLOCK_SAVE_IN_SESSION"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		),
		"PAGER_PARAMS_NAME" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("CP_BCF_PAGER_PARAMS_NAME"),
			"TYPE" => "STRING",
			"DEFAULT" => "arrPager"
		),
		"FORM_ACTION" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => 'URL страницы со списком для фильтрации',
			"TYPE" => "STRING"
		),
		"PLACEHOLDER_MODE" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => 'Плейсхолдеры без лейблов',
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"
		),
		"IS_COLLAPSED" => array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => 'Свернут по умолчанию',
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N"
		),
	),
);

if(!$OFFERS_IBLOCK_ID)
{
	unset($arComponentParameters["PARAMETERS"]["OFFERS_FIELD_CODE"]);
	unset($arComponentParameters["PARAMETERS"]["OFFERS_PROPERTY_CODE"]);
}