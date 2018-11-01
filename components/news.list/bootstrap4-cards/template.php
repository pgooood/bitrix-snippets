<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var array $arParams
 * @var array $arResult
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @global CDatabase $DB
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 * @var CBitrixComponent $component
 */
$this->setFrameMode(true);

$numCols = intval($arParams['NUM_COLS']); // количество колонок
$colSize = $numCols ? 12 / $numCols : 12;

if($arParams["DISPLAY_TOP_PAGER"])
	echo $arResult["NAV_STRING"];

?><div class="row"><?

foreach($arResult["ITEMS"] as $i => $arItem){
	
	$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
	$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
	
	$hasDetail = !$arParams["HIDE_LINK_WHEN_NO_DETAIL"] || ($arItem["DETAIL_TEXT"] && $arResult["USER_HAVE_ACCESS"]);
	$hasDate = $arParams["DISPLAY_DATE"]!="N" && $arItem["DISPLAY_ACTIVE_FROM"];
	$hasImage = $arParams["DISPLAY_PICTURE"]!="N" && is_array($arItem["PREVIEW_PICTURE"]);
	$hasTitle = $arParams["DISPLAY_NAME"]!="N" && $arItem["NAME"];
	$hasAnnounce = $arParams["DISPLAY_PREVIEW_TEXT"]!="N" && $arItem["PREVIEW_TEXT"];
	
	/* строки */
	if($numCols && $i && $i % $numCols == 0){
		?></div><div class="row"><?
	}
	
	?><div class="col-md-<?=$colSize;?>"><?
	
	?><figure class="card" id="<?=$this->GetEditAreaId($arItem['ID']);?>"><?
	
	/* картинка */
	if($hasImage){
		if($hasDetail){
			?><a href="<?=$arItem["DETAIL_PAGE_URL"]?>"><img class="card-img-top"<?
				?> src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>"<?
				?> width="<?=$arItem["PREVIEW_PICTURE"]["WIDTH"]?>"<?
				?> height="<?=$arItem["PREVIEW_PICTURE"]["HEIGHT"]?>"<?
				?> alt="<?=$arItem["PREVIEW_PICTURE"]["ALT"]?>"></a><?
		}else{
			?><img class="card-img-top"<?
				?> src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>"<?
				?> width="<?=$arItem["PREVIEW_PICTURE"]["WIDTH"]?>"<?
				?> height="<?=$arItem["PREVIEW_PICTURE"]["HEIGHT"]?>"<?
				?> alt="<?=$arItem["PREVIEW_PICTURE"]["ALT"]?>"><?
		}
	}
	
	?><div class="card-body"><?
	
	/* заголовок */
	if($hasTitle){
		?><h3 class="card-title"><?
		if($hasDetail){
			?><a href="<?echo $arItem["DETAIL_PAGE_URL"]?>"><?echo $arItem["NAME"];?></a><?
		}else{
			echo $arItem["NAME"];
		}
		?></h3><?
	}
	
	/* анонс */
	if($hasAnnounce){
		?><div class="card-text"><?echo $arItem["PREVIEW_TEXT"];?></div><?
	}
	
	/* свойства */
	?><dl><?
	foreach($arItem["FIELDS"] as $code => $value){
		?><dt><?echo GetMessage("IBLOCK_FIELD_".$code);?></dt><dd><?echo $value;?></dd><?
	}
	foreach($arItem["DISPLAY_PROPERTIES"] as $pid=>$arProperty){
		?><dt><?echo $arProperty["NAME"];?></dt><dd><?echo $arProperty["DISPLAY_VALUE"];?></dd><?
	}
	?></dl><?
	
	?></div><? //card-body
	
	/* подвал */
	if($hasDate || $hasDetail){
		?><div class="card-footer d-flex justify-content-between align-items-center"><?
		if($hasDate){
			?><small class="text-muted"><?echo $arItem["DISPLAY_ACTIVE_FROM"];?></small><?
		}	
		if($hasDetail){
			?><a href="button" class="btn btn-sm btn-outline-secondary">Подробнее</a><?
		}
		?></div><?
	}
	
	?></figure><? //card
	
	?></div><? //col
}

?></div><? //row

if($arParams["DISPLAY_BOTTOM_PAGER"])
	echo $arResult["NAV_STRING"];