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

$this->setFrameMode(true);
$id = 'filter-'. rand(1000,9999);

?><div class="card mb-4 catalog-filter<?=$themeClass?> fm">
<form name="<?=$arResult["FILTER_NAME"];?>_form" action="<?=$arResult["FORM_ACTION"];?>" method="get">
	<h5 class="mb-0"><a href="javascript:void(0)" type="button" data-toggle="collapse" data-target="#<?=$id;?>" aria-expanded="true" aria-controls="<?=$id;?>"><?=GetMessage("CT_BCF_FILTER_TITLE")?></a></h5>
	<div id="<?=$id;?>" class="collapse show" aria-labelledby="header-<?=$id;?>">
		<div class="card-body">
			<div class="row"><?
foreach($arResult["ITEMS"] as $arItem):
	if(array_key_exists("HIDDEN", $arItem)):
		echo $arItem["INPUT"];

	elseif ($arItem["TYPE"] == "RANGE"):
		?><div class="f1 mb-2 col-sm-6 col-md-4 catalog-filter-block">
			<div class="mb-1 catalog-filter-block-title"><?=$arItem["NAME"]?></div>
			<div class="catalog-filter-block-body d-flex">
				<div class="flex-6"><input<?
						?> class="form-control"<?
						?> type="text"<?
						?> value="<?=$arItem["INPUT_VALUES"][0]?>"<?
						?> name="<?=$arItem["INPUT_NAMES"][0]?>"<?
						?> placeholder="<?=GetMessage("CT_BCF_FROM")?>"></div>

				<div class="catalog-filter-field-separator"></div>
				<div class="flex-6"><input<?
						?> class="form-control"<?
						?> type="text"<?
						?> value="<?=$arItem["INPUT_VALUES"][1]?>"<?
						?> name="<?=$arItem["INPUT_NAMES"][1]?>"<?
						?> placeholder="<?=GetMessage("CT_BCF_TO")?>"></div>

			</div>
		</div><?

	elseif ($arItem["TYPE"] == "DATE_RANGE"):
		?><div class="f2 mb-2 col-sm-6 col-md-4 catalog-filter-block">
			<div class="mb-1 catalog-filter-block-title"><?=$arItem["NAME"];?></div>
			<div class="catalog-filter-block-body row">
				<div class="col-6"><?$APPLICATION->IncludeComponent(
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
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);?>
				</div>
				<div class="col-6"><?$APPLICATION->IncludeComponent(
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
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);?>
				</div>
			</div>
		</div><?

	elseif ($arItem["TYPE"] == "SELECT"):
		?><div class="f3 mb-2 col-sm-6 col-md-4 catalog-filter-block">
			<div class="mb-1 catalog-filter-block-title"><?=$arItem["NAME"]?></div>
			<div class="catalog-filter-block-body"><?
				echo $arItem["INPUT"];
			?></div>
		</div><?

	elseif ($arItem["TYPE"] == "CHECKBOX"):?>
		<div class="f4 mb-2 col-sm-6 col-md-4 catalog-filter-block">
			<div class="mb-1 catalog-filter-block-title"><?=$arItem["NAME"]?></div>
			<div class="catalog-filter-block-body"><?
				 $arListValue = (is_array($arItem["~INPUT_VALUE"]) ? $arItem["~INPUT_VALUE"] : array($arItem["~INPUT_VALUE"]));
				foreach ($arItem["LIST"] as $key => $value):
					?><div class="form-check"><label class="form-check-label"><?
						echo $arItem["INPUT"];
					?></label></div><?
				endforeach;
			?></div>
		</div><?

	elseif ($arItem["TYPE"] == "RADIO"):
		?><div class="f5 mb-2 col-sm-6 col-md-4 catalog-filter-block">
			<div class="mb-1 catalog-filter-block-title"><?=$arItem["NAME"]?></div>
			<div class="catalog-filter-block-body"><?
				$arListValue = (is_array($arItem["~INPUT_VALUE"]) ? $arItem["~INPUT_VALUE"] : array($arItem["~INPUT_VALUE"]));
				foreach ($arItem["LIST"] as $key => $value):
					?><div class="form-check"><label class="form-check-label"><?
						echo $arItem["INPUT"];
					?></label></div><?
				endforeach;
			?></div>
		</div><?

	else:
		?><div class="f6 mb-2 col-sm-6 col-md-4 catalog-filter-block">
			<div class="mb-1 catalog-filter-block-title"><?=$arItem["NAME"]?></div>
			<div class="catalog-filter-block-body"><?=$arItem["INPUT"]?></div>
		</div><?
	endif;
endforeach;
		?></div></div>
		<input type="submit" name="set_filter" value="<?=GetMessage("CT_BCF_SET_FILTER")?>" class="btn btn-primary" />
		<input type="hidden" name="set_filter" value="Y" />
		<input type="submit" name="del_filter" value="<?=GetMessage("CT_BCF_DEL_FILTER")?>" class="btn btn-link" />
	</div>
</form>
</div><?