<?php
// PHP Upload Script for CKEditor:  http://vector-it.com.ar/

// INITIALIZE VARIABLES
$uploaded = false;
$msg = '';
$f_name = '';
$url = '';

// HERE SET THE PATH TO THE FOLDERS FOR IMAGES AND AUDIO ON YOUR SERVER (RELATIVE TO THE ROOT OF YOUR WEBSITE ON SERVER)
$upload_dir = array(
	'img'=> '/imgUp',
	'audio'=> '/audioup'
);

// IF NOT EXIST, CREATE THE DIRS
$aux = $_SERVER['DOCUMENT_ROOT'] . $upload_dir['img'];
if (!file_exists($aux) && !is_dir($aux)) {
	mkdir($aux);
}
$aux = $_SERVER['DOCUMENT_ROOT'] . $upload_dir['audio'];
if (!file_exists($aux) && !is_dir($aux)) {
	mkdir($aux);
}

// HERE PERMISSIONS FOR IMAGE
$imgset = array(
	'maxsize' => 2000,     // maximum file size, in KiloBytes (2 MB)
	'maxwidth' => 900,     // maximum allowed width, in pixels
	'maxheight' => 800,    // maximum allowed height, in pixels
	'minwidth' => 10,      // minimum allowed width, in pixels
	'minheight' => 10,     // minimum allowed height, in pixels
	'type' => array('bmp', 'gif', 'jpg', 'jpeg', 'png'),  // allowed extensions
);

// HERE PERMISSIONS FOR AUDIO
$audioset = array(
	'maxsize' => 20000,    // maximum file size, in KiloBytes (20 MB)
	'type' => array('mp3', 'ogg', 'wav'),  // allowed extensions
);

// If 1 and filename exists, RENAME file, adding "_NR" to the end of filename (name_1.ext, name_2.ext, ..)
// If 0, will OVERWRITE the existing file
define('RENAME_F', 0);

if(isset($_FILES['upload']) && strlen($_FILES['upload']['name']) >1) {
	define('F_NAME', preg_replace('/\.(.+?)$/i', '', basename($_FILES['upload']['name'])));  //get filename without extension

	// get protocol and host name to send the absolute image path to CKEditor
	$protocol = (($_SERVER['HTTPS'] == 'off') || empty($_SERVER['HTTPS'])) ? 'http://' : 'https://';
	$site = $protocol. $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . '/';

	$sepext = explode('.', strtolower($_FILES['upload']['name']));
	$type = end($sepext);    // gets extension
	$upload_dir = in_array($type, $imgset['type']) ? $upload_dir['img'] : $upload_dir['audio'];
	$upload_dir = trim($upload_dir, '/') .'/';

	//checkings for image or audio
	if(in_array($type, $imgset['type'])) {
		list($width, $height) = getimagesize($_FILES['upload']['tmp_name']);  // image width and height
		if(isset($width) && isset($height)) {
			if($width > $imgset['maxwidth'] || $height > $imgset['maxheight']) $msg .= '\\n Ancho x Alto = '. $width .' x '. $height .' \\n El Ancho x Alto máximo es: '. $imgset['maxwidth']. ' x '. $imgset['maxheight'];
			if($width < $imgset['minwidth'] || $height < $imgset['minheight']) $msg .= '\\n Ancho x Alto = '. $width .' x '. $height .'\\n El Ancho x Alto mínimo es: '. $imgset['minwidth']. ' x '. $imgset['minheight'];
			if($_FILES['upload']['size'] > $imgset['maxsize']*1000) $msg .= '\\n El tamaño máximo del archivo debe ser: '. $imgset['maxsize']. ' KB.';
		}
	}
	else if(in_array($type, $audioset['type'])){
		if($_FILES['upload']['size'] > $audioset['maxsize']*1000) $msg .= '\\n El tamaño máximo del archivo debe ser: '. $audioset['maxsize']. ' KB.';
	}
	else $msg .= 'El archivo: '. $_FILES['upload']['name']. ' tiene una extensión no permitida.';

	//set filename; if file exists, and RENAME_F is 1, set "img_name_I"
	// $p = dir-path, $fn=filename to check, $ex=extension $i=index to rename
	function setFName($p, $fn, $ex, $i){
		if(RENAME_F ==1 && file_exists($p .$fn .$ex)) return setFName($p, F_NAME .'_'. ($i +1), $ex, ($i +1));
		else return $fn .$ex;
	}

	$f_name = setFName($_SERVER['DOCUMENT_ROOT'] .'/'. $upload_dir, F_NAME, ".$type", 0);
	$uploadpath = $_SERVER['DOCUMENT_ROOT'] .'/'. $upload_dir . $f_name;  // full file path

	// If no errors, upload the image, else, output the errors
	if($msg == '') {
		if(move_uploaded_file($_FILES['upload']['tmp_name'], $uploadpath)) {
			$uploaded = true;
			$url = $site. $upload_dir . $f_name;
			$msg = F_NAME .'.'. $type .' subida! - Tamaño: '. number_format($_FILES['upload']['size']/1024, 2, '.', '') .' KB';
		}
		else $msg = 'Error al subir archivo';
	}
}

$response = array(
	'uploaded'=>true,
	'error'=>array('message'=>false),
	'message'=>$msg,
	'filename'=>$f_name,
	'url'=>$url
);
header('Content-Type: application/json');
echo json_encode($response);