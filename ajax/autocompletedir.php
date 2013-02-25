<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_mv');

$showLayers = (!empty($_GET['layers']))?$_GET['layers']:-1;
$dirs = array();
if(!empty($_GET['StartDir'])){
	$actualDir = $_GET['StartDir'];
	if(!strlen($actualDir)<=1 || substr($actualDir,0,1)!=='/'){
		$actualDir = '/'.$actualDir;
	}
}
else
	$actualDir = ''; // suchverzeichnis zum erstellen aller moeglicher Zielverzeichnisse

$actFile = (!empty($_GET['file']))?$_GET['file']:'';
if(strpos($actFile,';')!==false){
	// we get an string in the form /directory/file1 ; file2; file3; ...;
	$actFile = explode(';',$actFile);
	array_pop($actFile);
}
else{
	$actFile = array($actFile);
}
$len = count($actFile);
$mainDir = dirname($actFile[0]).'/';
if($mainDir =='//') $mainDir = '/';
// bereinige fehler im Ordnername, insbesondere '//' am Anfang
for($i=0;$i<$len;++$i){
	if($i>0) $actFile[$i] = $mainDir.$actFile[$i];
	if(strpos($actFile[$i],'//')!==false){
		$actFile[$i] = substr($actFile[$i],1);
	}
}
$dirs[] = '<optgroup label="dirs">';

if(!OC_Filesystem::is_dir($actualDir)){
	OCP\JSON::error(array('data'=>array('message'=>$l->t('No filesystem found'))));
}
if(dirname($actFile[0])!=="/" && dirname($actFile[0])!==""){
	$dirs[] = '<option value="/">/</option>';
}
// $dir without "/" at the end
function getDirList($dir,$actFile,$depth=-1){
	if($depth == 0) return array(); // Abbruch wenn depth = 0
	$ret = array();
	$patternFile = '!(('.implode(')|(',$actFile).'))$!';
	foreach(OC_Files::getdirectorycontent( $dir ) as $i ){
		if($i['type']=='dir'){
			$path = $dir.'/'.$i['name'];
			if(preg_match($patternFile,$path)){
				continue;
			}
			if(!empty($i['writable']) && $i['writable']){
				//$ret[] = array('name'=>$path,'r'=>$i['readable'],'w'=>$i['writeable']);
				$ret[] =  '<option value="'.$path.'">'.$path.'</option>';
			}
			;
			$ret = array_merge($ret,getDirList($path,$actFile,$depth-1));
		}
	}
	return $ret;
}

$patternFile = '!('.implode(')|(',$actFile).')!';
if($actualDir!="/" && !preg_match($patternFile,$actualDir)) $dirs[] = '<option value="'.$actualDir.'">'.$actualDir.'</option>';
$dirs = array_merge($dirs,getDirList($actualDir,$actFile,$showLayers));

$dirs[] = '</optgroup>';
OCP\JSON::encodedPrint($dirs);
