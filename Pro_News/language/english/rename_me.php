<?php

$root_path = dirname(dirname(dirname(__FILE__)));
if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
	$root_path = str_replace('\\', '/', $root_path); //Damn' windows
}

if (strlen($root_path) > 2) define('BASE_DIR', $root_path.'/');
else define('BASE_DIR', '../');

// echo 'x='.BASE_DIR;

include_once(BASE_DIR.'language/english/pro_news.php');
?>



