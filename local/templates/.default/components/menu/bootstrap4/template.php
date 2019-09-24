<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
/**
 * Создан на базе стандартного шаблона меню bootstrap_4
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

if(empty($arResult["ALL_ITEMS"]))
	return;

CUtil::InitJSCore();

if(is_file($_SERVER["DOCUMENT_ROOT"] . $this->GetFolder() . '/themes/' . $arParams["MENU_THEME"] . '/colors.css'))
	$APPLICATION->SetAdditionalCSS($this->GetFolder() . '/themes/' . $arParams["MENU_THEME"] . '/colors.css');


if(!class_exists('menuTag')){
	/**
	 * Класс для формирования HTML элементов на базе DOMDocument
	 */
	class menuTag{
		protected $dd, $e, $arClasses = [];

		function __construct($v, $title = null){
			if(is_object($v) && $v instanceof DOMNode){
				$this->dd = $v->ownerDocument;
				$this->e = $v;
			}else{
				$this->dd = new DOMDocument('1.0', 'utf-8');
				$this->dd->formatOutput = false;
				$this->dd->preserveWhiteSpace = false;
				$this->e = $this->dd->appendChild($this->dd->createElement($v));
				if($title)
					$this->e->appendChild($this->dd->createTextNode($title));
			}
		}
		function e(){
			return $this->e;
		}
		function append($name, $title = null){
			$e = $this->e->appendChild($this->dd->createElement($name));
			if($title)
				$e->appendChild($this->dd->createTextNode($title));
			return new menuTag($e);
		}
		function attr($name, $value){
			$this->e->setAttribute($name, $value);
			return $this;
		}
		function click($value){
			return $this->attr('onclick', $value);
		}
		function mouseover($value){
			return $this->attr('onmouseover', $value);
		}
		function mouseout($value){
			return $this->attr('onmouseout', $value);
		}
		function role($value){
			return $this->attr('data-role', $value);
		}
		function href($value){
			return $this->attr('href', $value);
		}
		function src($value){
			return $this->attr('src', $value);
		}
		function id($value){
			return $this->attr('id', $value);
		}
		/**
		 * Добавить класс
		 * @param string $name
		 * @return $this
		 */
		function cl($name){
			if($name && !in_array($name, $this->arClasses)){
				$this->arClasses[] = $name;
				$this->attr('class', implode(' ', $this->arClasses));
			}
			return $this;
		}

		function __toString(){
			return $this->dd->saveHTML($this->e);
		}

		function menuItem($level, &$arItem, $menuBlockId, $itemId, $existPictureDescColomn, $hasCaret = null){
			$li = $this->append('li')
					->cl('bx-nav-' . $level . '-lvl');
			$a = $li->append('a')
					->cl('bx-nav-' . $level . '-lvl-link')
					->href($arItem['LINK']);
			if(!empty($arItem['PARAMS']['picture_src']))
				$a->attr('data-picture', $arItem['PARAMS']['picture_src']);
			if($existPictureDescColomn)
				$a->mouseover('return hover_' . $menuBlockId . '("' . $itemId . '")');
			if($arItem['SELECTED'])
				$a->cl('bx-active');
			$span = $a->append('span', $arItem['TEXT'] . ($hasCaret ? ' ' : null))
					->cl('bx-nav-' . $level . '-lvl-link-text');
			if($hasCaret)
				$span->append('i')->cl('fa')->cl('fa-angle-down');
			return $li;
		}
	}
}

$menuBlockId = 'menu_' . $this->randString();

$menuElement = (new menuTag('div'))
		->cl('bx-top-nav')
		->cl('bx-' . $arParams["MENU_THEME"])
		->id($menuBlockId);

$ul1 = $menuElement
		->append('nav')
		->cl('bx-top-nav-container')
		->id('cont_' . $menuBlockId)
		
		->append('div')
		->cl('container')
		
		/* список первого уровня меню */
		->append('ul')
		->cl('bx-nav-list-1-lvl')
		->id('ul_' . $menuBlockId);

foreach($arResult["MENU_STRUCTURE"] as $itemID => $arColumns){

	$hasColumns = is_array($arColumns) && !empty($arColumns);
	$existPictureDescColomn = $arResult["ALL_ITEMS"][$itemID]["PARAMS"]["picture_src"] || $arResult["ALL_ITEMS"][$itemID]["PARAMS"]["description"];

	$li = $ul1->menuItem(1
					, $arResult['ALL_ITEMS'][$itemID]
					, $menuBlockId
					, $itemID
					, false
					, $hasColumns);
	if($hasColumns){
		$li->cl('bx-nav-parent')->role('bx-menu-item');

		/* для мобилки */
		$li->append('span')
				->cl('bx-nav-parent-arrow')
				->click('obj_' . $menuBlockId . '.toggleInMobile(this)')
				
				->append('i')
				->cl('fa')
				->cl('fa-angle-left');

		/* подменю */
		$lvl2Container = $li->append('div')
				->cl('bx-nav-2-lvl-container');

		foreach($arColumns as $key => $arRow){

			/* список второго уровеня меню */
			$ul2 = $lvl2Container->append('ul')
					->cl('bx-nav-list-2-lvl');

			foreach($arRow as $itemIdLevel_2 => $arLevel_3){

				$li2 = $ul2->menuItem(2
						, $arResult['ALL_ITEMS'][$itemIdLevel_2]
						, $menuBlockId
						, $itemIdLevel_2
						, $existPictureDescColomn);

				if(is_array($arLevel_3) && !empty($arLevel_3)){

					/* список третьего уровеня меню */
					$ul3 = $li2->append('ul')
							->cl('bx-nav-list-3-lvl');

					foreach($arLevel_3 as $itemIdLevel_3){

						$ul3->menuItem(3
								, $arResult['ALL_ITEMS'][$itemIdLevel_3]
								, $menuBlockId
								, $itemIdLevel_3
								, $existPictureDescColomn);
					}
				}
			}
		}
		if($existPictureDescColomn){
			$div = $lvl2Container->append('div')
					->cl('bx-nav-list-2-lvl')
					->cl('bx-nav-catinfo')
					->cl('dbg')
					->role('desc-img-block');
			$div->append('a')
					->href($arResult["ALL_ITEMS"][$itemID]["LINK"])
					->cl('bx-nav-2-lvl-link-image')
					->append('img')
					->src($arResult["ALL_ITEMS"][$itemID]["PARAMS"]["picture_src"]);
			$div->append('p', $arResult["ALL_ITEMS"][$itemID]["PARAMS"]["description"]);
		}
	}
}

echo $menuElement;

?><script>
$(function(){
	$('#<?= $menuBlockId; ?> .bx-nav-1-lvl')
		.click(function(event){
			if (BX.hasClass(document.documentElement, 'bx-touch'))
				obj_<?= $menuBlockId; ?>.clickInMobile(this, event);
		})
		.mouseover(function(){ BX.CatalogMenu.itemOver(this); })
		.mouseout(function(){ BX.CatalogMenu.itemOut(this); });
});
window.hover_<?= $menuBlockId; ?> = function(id){
	window.obj_<?= $menuBlockId; ?> && window.obj_<?= $menuBlockId; ?>.changeSectionPicure(this, id);
	return false;
};
BX.ready(function(){
	window.obj_<?= $menuBlockId; ?> = new BX.Main.Menu.CatalogHorizontal('<?= CUtil::JSEscape($menuBlockId); ?>', <?= CUtil::PhpToJSObject($arResult["ITEMS_IMG_DESC"]); ?>);
});
</script>