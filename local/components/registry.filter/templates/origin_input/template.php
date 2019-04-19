<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

$themeClass = isset($arParams['TEMPLATE_THEME']) ? ' bx-'.$arParams['TEMPLATE_THEME'] : '';

$isCollapsed = !empty($arParams['IS_COLLAPSED']) && $arParams['IS_COLLAPSED'] == 'Y';

$this->setFrameMode(true);
$id = 'filter-'. rand(1000,9999);

?><div class="card mb-4 catalog-filter<?=$themeClass?> fm">
<form name="<?=$arResult["FILTER_NAME"];?>_form" action="<?=$arResult["FORM_ACTION"];?>" method="get">
	<h5 class="mb-0 text-right"><a class="fm_collapsed<?=$isCollapsed ? ' collapsed' : null;?>" href="javascript:void(0)" data-toggle="collapse" data-target="#<?=$id;?>" aria-expanded="true" aria-controls="<?=$id;?>"><?=GetMessage("CT_BCF_FILTER_TITLE")?> <i class="fa fa-chevron-up"></i></a></h5>
	<div id="<?=$id;?>" class="fm_filtr collapse<?=$isCollapsed ? null : ' show';?>" aria-labelledby="header-<?=$id;?>">
		<div class="card-body">
			<div class="row"><?
if(isset($arResult["ITEMS"]["PROPERTY_INN"]) && ($arItem = $arResult["ITEMS"]["PROPERTY_INN"])){
	?><div class="f6 mb-2 col-sm-6 col-md-4 catalog-filter-block">
		<div class="catalog-filter-block-body"><?=$arItem["INPUT"]?></div>
	</div><?
}
if(isset($arResult["ITEMS"]["PROPERTY_OGRN"]) && ($arItem = $arResult["ITEMS"]["PROPERTY_OGRN"])){
	?><div class="f6 mb-2 col-sm-6 col-md-4 catalog-filter-block">
		<div class="catalog-filter-block-body"><?=$arItem["INPUT"]?></div>
	</div><?
}
if(isset($arResult["ITEMS"]["PROPERTY_OKVED"]) && ($arItem = $arResult["ITEMS"]["PROPERTY_OKVED"])){
	?><div class="f6 mb-2 col-sm-6 col-md-4 catalog-filter-block">
		<div class="catalog-filter-block-body"><?=$arItem["INPUT"]?></div>
	</div><?
}
if(isset($arResult["ITEMS"]["PROPERTY_CCI"]) && ($arItem = $arResult["ITEMS"]["PROPERTY_CCI"])){
	?><div class="f6 mb-2 col-sm-6 col-md-4 catalog-filter-block">
		<div class="catalog-filter-block-body"><?=$arItem["INPUT"]?></div>
	</div><?
}
if(isset($arResult["ITEMS"]["PROPERTY_LINK_REGION"]) && ($arItem = $arResult["ITEMS"]["PROPERTY_LINK_REGION"])){
	?><div class="f6 mb-2 col-sm-6 col-md-4 catalog-filter-block">
		<div class="catalog-filter-block-body"><?=$arItem["INPUT"]?></div>
	</div><?
}
if(isset($arResult["ITEMS"]["PROPERTY_CERTIFICATE_NUMBER"]) && ($arItem = $arResult["ITEMS"]["PROPERTY_CERTIFICATE_NUMBER"])){
	?><div class="f6 mb-2 col-sm-6 col-md-4 catalog-filter-block">
		<div class="catalog-filter-block-body"><?=$arItem["INPUT"]?></div>
	</div><?
}
if(isset($arResult["ITEMS"]["PROPERTY_CERTIFICATE_DATE"]) && ($arItem = $arResult["ITEMS"]["PROPERTY_CERTIFICATE_DATE"])){
	?><div class="f2 mb-2 col-sm-12 col-lg-8 catalog-filter-block">
			<div class="catalog-filter-block-body row fm_data">
				<div class="col-12 col-md-4 catalog-filter-block-title align-self-end"><?=$arItem["NAME"];?>. Период:</div>
				<div class="col-12 col-md-4 align-self-end"><?$APPLICATION->IncludeComponent(
						'bitrix:main.calendar',
						'',
						array(
							'FORM_NAME' => $arResult["FILTER_NAME"]."_form",
							'SHOW_INPUT' => 'Y',
							'INPUT_ADDITIONAL_ATTR' => 'class="calendar" placeholder="'.FormatDate("SHORT", $arItem["VALUES"]["MIN"]["VALUE"]).'"',
							'INPUT_NAME' => $arItem["INPUT_NAMES"][0],
							'INPUT_VALUE' => $arItem["INPUT_VALUES"][0],
							'SHOW_TIME' => 'N',
							'HIDE_TIMEBAR' => 'Y',
							'PLACEHOLDER' => 'от'
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);?>
				</div>
				<div class="col-12 col-md-4 align-self-end"><?$APPLICATION->IncludeComponent(
						'bitrix:main.calendar',
						'',
						array(
							'FORM_NAME' => $arResult["FILTER_NAME"]."_form",
							'SHOW_INPUT' => 'Y',
							'INPUT_ADDITIONAL_ATTR' => 'class="calendar" placeholder="'.FormatDate("SHORT", $arItem["VALUES"]["MAX"]["VALUE"]).'"',
							'INPUT_NAME' => $arItem["INPUT_NAMES"][1],
							'INPUT_VALUE' => $arItem["INPUT_VALUES"][1],
							'SHOW_TIME' => 'N',
							'HIDE_TIMEBAR' => 'Y',
							'PLACEHOLDER' => 'до'
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);?>
				</div>
			</div>
		</div><?
}
		?><div class="f6 mb-2 col-sm-12 col-lg-4 catalog-filter-block align-self-end fm_btn_group">
		<input type="submit" name="set_filter" value="<?=GetMessage("CT_BCF_SET_FILTER")?>" class="btn btn-primary" />
		<input type="hidden" name="set_filter" value="Y" />
		<input type="submit" name="del_filter" value="<?=GetMessage("CT_BCF_DEL_FILTER")?>" class="btn btn-outline-secondary" />
		</div></div></div>
	</div>
</form>
</div>