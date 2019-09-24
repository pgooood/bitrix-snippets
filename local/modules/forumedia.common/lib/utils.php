<?php

namespace forumedia\common;

class utils {
	static function jsonResponse($data,$errors = null){
		$GLOBALS['APPLICATION']->RestartBuffer();
		header('Content-Type: application/json');
		echo json_encode(array(
			'data' => $data
			,'success' => empty($errors)
			,'errors' => $errors
		));
		die;
	}
	
	static function parseFloat($v){
		if(is_string($v))
			$v = preg_replace(['/\s+/','/,/'],['','.'],$v);
		return is_numeric($v) ? floatval($v) : null;
	}
}
