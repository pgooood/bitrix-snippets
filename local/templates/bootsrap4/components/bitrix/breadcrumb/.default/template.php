<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * @global CMain $APPLICATION
 */

global $APPLICATION;

//delayed function must return a string
if (empty($arResult))
	return;
ob_start();
echo '<nav class="breadcrumb_fm" aria-label="breadcrumb"><ol class="breadcrumb" itemprop="http://schema.org/breadcrumb" itemscope itemtype="http://schema.org/BreadcrumbList">';
$itemSize = count($arResult);
for ($index = 0; $index < $itemSize; $index++) {
	$title = $arResult[$index]['TITLE'];
	if ($arResult[$index]['LINK'] && $index != $itemSize - 1) {
		echo '<li class="breadcrumb-item" id="bx_breadcrumb_' , $index , '" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">'
				,'<a href="' , $arResult[$index]['LINK'] , '" title="' , $title , '" itemprop="url">'
					,'<span itemprop="name">' , $title , '</span>'
				,'</a><meta itemprop="position" content="' , ($index + 2) , '">'
			,'</li>';
	} else {
		echo '<li class="breadcrumb-item active" aria-current="page" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">'
				,'<span itemprop="name">' , $title , '</span>'
				,'<meta itemprop="position" content="' , ($index + 2) , '">'
			,'</li>';
	}
}
echo '</ol></nav>';

return ob_get_clean();
