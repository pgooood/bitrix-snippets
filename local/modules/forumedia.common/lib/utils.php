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
		),JSON_UNESCAPED_UNICODE);
		die;
	}
	
	static function parseFloat($v){
		if(is_string($v))
			$v = preg_replace(['/\s+/','/,/'],['','.'],$v);
		return is_numeric($v) ? floatval($v) : null;
	}
	
	static function vdump($v,$die = false){
		if($GLOBALS['USER']->IsAdmin()){
			?><pre style="display:block; text-align:left; border:1px solid #ccc; padding:20px; margin:15px 0; background:#152735; color:#ccc; border-radius:10px; box-shadow:1px 1px 8px rgba(0,0,0,0.6);"><?=htmlspecialchars(print_r($v,1))?></pre><?
			if($die) die;
		}
	}

	/**
	 * Вывод файла для скачивания по ID
	 */
	static function downloadFile($id,$fileName = null,$contentDisposition = 'attachment'){
		if(($arFile = \CFile::GetByID($id)->Fetch())
			&& ($sPath = $_SERVER['DOCUMENT_ROOT'].\CFile::GetPath($id))
			&& is_file($sPath)
			&& is_readable($sPath)
		){

			$GLOBALS['APPLICATION']->RestartBuffer();
			header('Content-Type: ' . ($arFile['CONTENT_TYPE'] ?: 'application/octet-stream'));
			header('Cache-Control: must-revalidate');
			header('Pragma: must-revalidate');
			header('Accept-Ranges: bytes');
			header('Content-Disposition: '.($contentDisposition == 'attachment' ? 'attachment' : 'inline').'; filename="'
					.($fileName ?: \Cutil::translit($arFile['ORIGINAL_NAME'],'ru',['safe_chars' => '.']))
				.'"');
			header('Content-Length: '.$arFile['FILE_SIZE']);
			echo file_get_contents($sPath);
			die;
		}
	}
}
