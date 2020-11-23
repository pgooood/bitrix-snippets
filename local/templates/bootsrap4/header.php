<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

CJSCore::RegisterExt('jquery3',[
	'skip_core' => true
	,'js' => 'https://code.jquery.com/jquery-3.5.1.min.js'
]);
CJSCore::RegisterExt('popper',[
	'skip_core' => true
	,'js' => 'https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js'
]);
CJSCore::RegisterExt('bootstrap4',[
	'skip_core' => true
	,'js' => 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js'
	,'rel' => ['jquery3','popper']
]);
CJSCore::Init(['bootstrap4']);

$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addCss('https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css');
$asset->addCss('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.min.css');

?><!doctype html>
<html lang="<?=LANGUAGE_ID;?>">
<head><?
$APPLICATION->ShowHead();
?><title><?$APPLICATION->ShowTitle();?></title><?
?><meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"><?
?></head>
<body class="bg-light"><?$APPLICATION->ShowPanel();?>
<div class="container">
<header class="py-5 text-center">
	<div class="display-1"><i class="fab fa-bootstrap"></i></div>
	<h2>Bootstrap 4</h2>
	<p class="lead">template for 1C-Bitrix</p>
</header>
<main class="mb-5">
<?$APPLICATION->IncludeComponent(
	"bitrix:breadcrumb", 
	".default", 
	array(
		"START_FROM" => "0",
		"PATH" => "",
		"SITE_ID" => "s1",
		"COMPONENT_TEMPLATE" => ".default"
	),
	false
);?>
<h1 class="h3 mb-5"><?$APPLICATION->ShowTitle(false);?></h1>