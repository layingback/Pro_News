<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Copyright © 20011 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version


  $Revision: 3.10 $
  $Date: 2013-05-28 13:11:19 $
  Author: layingback
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

// The purpose of this file is to load additional javascript functions into Pro_News

// If the install instructions with a Add-On Template for Pro_News instruct you to
//	edit this file to include a new javascript .js file then do so here



// Insert javascript calls above this line only
// 	THIS FILE SHOULD BE EDITED WITH A UTF-8 AWARE EDITOR (eg. Notepad2, Notepad++ for Windows, Geany for Linux)


//	Nothing to edit Below this line
load_if('pnfcs',array('0'=> 'index2fcs','1'=> 'index3fcs','2'=> 'index2fsl','3'=> 'index3fsl','4'=> 'index2fcs-hme','5'=> 'index3fcs-hme'));
load_if('swfobject',array('0'=> 'index2flp','1'=> 'index3flp'));
load_if('dom_drag',array('0'=> 'index4gly','1'=> 'index4alb'));
load_if('calendarpopup',array('0'=> 'index3alb','1'=> 'index3gly'));
load_if('virtualpaginate',array('0'=> 'indexp2gly','1'=> 'indexp2alb','2'=> 'indexp3gly','3'=> 'indexp3alb'));
load_if('thumbnailviewer',array('0'=> 'index3Agly','1'=> 'indext3alb'));

function load_if($jsf, $tpl) {
	global $CPG_SESS, $modheader, $MAIN_CFG;
	$pnsettings = $MAIN_CFG['pro_news'];
	$pn_module_name = $pnsettings['module_name'];
// echo '<br />mn='.$pn_module_name;
	foreach ($tpl as $tplfile) {
// echo ', '.$tplfile;
// echo ': themes/'.$CPG_SESS['theme'].'/template/pronews/article/'.$tplfile.'.html';
		if (file_exists('themes/'.$CPG_SESS['theme'].'/template/pronews/article/'.$tplfile.'.html') ||
				file_exists('themes/default/template/pronews/article/'.$tplfile.'.html')) {
// echo ' - modules/'.$pn_module_name.'/includes/'.$jsf.'.js\';
			$modheader .= "<script type=\"text/javascript\" src=\"modules/".$pn_module_name."/includes/".$jsf.".js\"></script>";
			break;
		}
	}
}
