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

if(!$arResult["NavShowAlways"]
	&& ($arResult["NavRecordCount"] == 0 || ($arResult["NavPageCount"] == 1 && $arResult["NavShowAll"] == false))
){
	return;
}

if(!function_exists('navItem')){
	function navItem($title,$href = null,$active = null,$nofollow = null){
		$arLiClasses = ['page-item'];
		if(!$href){
			$href = 'javascript: void(0);';
			$arLiClasses[] = 'disabled';
		}
		if($active){
			$arLiClasses[] = 'active';
		}
		if(!is_numeric($title)){
			$title = GetMessage($title);
		}
		ob_start();
	?><li class="<?=implode(' ',$arLiClasses)?>"><a href="<?=$href?>" class="page-link"<?if($nofollow){?>  rel="nofollow"<?}?>><?=$title?></a></li><?
		return ob_get_clean();
	}
}

$strNavQueryString = ($arResult["NavQueryString"] != "" ? $arResult["NavQueryString"]."&amp;" : "");
$strNavQueryStringFull = ($arResult["NavQueryString"] != "" ? "?".$arResult["NavQueryString"] : "");
$hrefPref = $arResult["sUrlPath"].'?'.$strNavQueryString.'PAGEN_'.$arResult["NavNum"].'=';

?><nav aria-label="<?=$arResult["NavTitle"]?>"><?

echo '<p>'
		, $arResult["NavFirstRecordShow"]
		, ' ', GetMessage("nav_to")
		, ' ', $arResult["NavLastRecordShow"]
		, ' ', GetMessage("nav_of")
		, ' ', $arResult["NavRecordCount"]
		, '</p>';

?><ul class="pagination"><?

if($arResult["bDescPageNumbering"] === true){
	
	//ссылки перед пагинацией
	$beginLink =
	$prevLink = null;
	if($arResult["NavPageNomer"] < $arResult["NavPageCount"]){	
		$beginLink = $arResult["bSavePage"]
				? $hrefPref.$arResult["NavPageCount"]
				: $arResult["sUrlPath"].$strNavQueryStringFull;
		
		$prevLink = $arResult["bSavePage"]
				? $hrefPref.'1'
				: ($arResult["NavPageCount"] == ($arResult["NavPageNomer"]+1)
					? $arResult["sUrlPath"].$strNavQueryStringFull
					: $hrefPref.($arResult["NavPageNomer"]+1));
	}
	
	//ссылки после пагинации
	$nextLink =
	$endLink = null;
	if ($arResult["NavPageNomer"] > 1){
		$nextLink = $hrefPref.($arResult["NavPageNomer"]-1);
		$endLink = $hrefPref.'1';
	}
	
	//вывод
	echo navItem('nav_begin',$beginLink);
	echo navItem('nav_prev',$prevLink);
	
	while($arResult["nStartPage"] >= $arResult["nEndPage"]){
		$title = $arResult["NavPageCount"] - $arResult["nStartPage"] + 1;
		$isActive = $arResult["nStartPage"] == $arResult["NavPageNomer"];
		$link = $arResult["nStartPage"] == $arResult["NavPageCount"] && $arResult["bSavePage"] == false
			? $arResult["sUrlPath"].$strNavQueryStringFull
			: $hrefPref.$arResult["nStartPage"];		
		echo navItem($title,$link,$isActive);
		$arResult["nStartPage"]--;
	}

	echo navItem('nav_next',$nextLink);
	echo navItem('nav_end',$endLink);
	
}else{
	
	//ссылки перед пагинацией
	$beginLink =
	$prevLink = null;
	if($arResult["NavPageNomer"] > 1){	
		$beginLink = $arResult["bSavePage"]
				? $hrefPref.'1'
				: $arResult["sUrlPath"].$strNavQueryStringFull;
		
		$prevLink = $arResult["bSavePage"]
				? $hrefPref.($arResult["NavPageNomer"]-1)
				: ($arResult["NavPageNomer"] > 2
					? $hrefPref.($arResult["NavPageNomer"]-1)
					: $arResult["sUrlPath"].$strNavQueryStringFull);
	}
	
	//ссылки после пагинации
	$nextLink =
	$endLink = null;
	if ($arResult["NavPageNomer"] < $arResult["NavPageCount"]){
		$nextLink = $hrefPref.($arResult["NavPageNomer"]+1);
		$endLink = $hrefPref.$arResult["NavPageCount"];
	}
	
	//вывод
	echo navItem('nav_begin',$beginLink);
	echo navItem('nav_prev',$prevLink);
	
	while($arResult["nStartPage"] <= $arResult["nEndPage"]){
		$isActive = $arResult["nStartPage"] == $arResult["NavPageNomer"];
		$link = $arResult["nStartPage"] == 1 && $arResult["bSavePage"] == false
			? $arResult["sUrlPath"].$strNavQueryStringFull
			: $hrefPref.$arResult["nStartPage"];		
		echo navItem($arResult["nStartPage"],$link,$isActive);
		$arResult["nStartPage"]++;
	}
	
	echo navItem('nav_next',$nextLink);
	echo navItem('nav_end',$endLink);
	
}
	
if ($arResult["bShowAll"]){
	if($arResult["NavShowAll"])
		echo navItem('nav_paged',$arResult["sUrlPath"].'?'.$strNavQueryString.'SHOWALL_'.$arResult["NavNum"].'=0',null,true);
	else
		echo navItem('nav_all',$arResult["sUrlPath"].'?'.$strNavQueryString.'SHOWALL_'.$arResult["NavNum"].'=1',null,true);
}

?></ul></nav>