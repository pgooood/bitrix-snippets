<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentParameters = array(
	'GROUPS' => array(),
	'PARAMETERS' => array(
		'SEF_MODE' => array(
			'list' => array(
				'NAME' => 'Список элементов инфоблока',
				'DEFAULT' => '#IBLOCK_CODE#/',
				'VARIABLES' => array('IBLOCK_CODE')
			),
			'groups' => array(
				'NAME' => 'Дерево разделов',
				'DEFAULT' => '#IBLOCK_CODE#/groups/',
				'VARIABLES' => array('IBLOCK_CODE')
			),
			'detail' => array(
				'NAME' => 'Элемент',
				'DEFAULT' => '#IBLOCK_CODE#/element/#ELEMENT_ID#/',
				'VARIABLES' => array('IBLOCK_CODE','ELEMENT_ID')
			)
		),
		'ADDITIONAL_FIELDS' => array(
			'PARENT' => 'BASE',
			'NAME' => 'Дополнительные поля',
			'TYPE' => 'STRING',
			'MULTIPLE' => 'Y'
		),
		'NAME' => array(
			'PARENT' => 'BASE',
			'NAME' => 'Имя выгрузки',
			'TYPE' => 'STRING'
		),
		,'CACHE_TIME' => array('DEFAULT' => 3600)
	)
);

