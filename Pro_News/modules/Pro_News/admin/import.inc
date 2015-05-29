<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Original Beta version Copyright © 2006 by D Mower aka Kuragari
  Subsequent releases Copyright © 2007 - 2009 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 3.7 $
  $Date: 2011-09-27 15:38:22 $
   Author: layingback
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin($module_name)) { die('Access Denied'); }
$pagetitle .= ' '._BC_DELIM.' '.$module_name.' Import';
require('header.php');
GraphicAdmin('_AMENU3');
require_once('includes/nbbcode.php');
require_once('modules/'.$module_name.'/admin/admin_functions.php');
$mode = isset($_GET['mode']) ? $_GET['mode']:'';
OpenTable();
ProNewsAdm::admin_menu();

$sql = 'SELECT * FROM '.$prefix.'_stories';

if ($mode == '') {
	echo '<div align="center"><strong>'._PNNOTICE.':</strong><br />'._PNNOTETEXT.'.';
	echo '<hr /><br />';
	$numrows = $db->sql_numrows($db->sql_query($sql));
	echo _PNIMPORT1.' '.$numrows.' '._PNIMPORT2.'.<br /><hr /><br />';
	echo _PNIMPORT3.'.<br /><br />';
	echo '<form method="post" action="'.adminlink("&amp;file=import&amp;mode=import").'">
	 <input name="importit" type="hidden" value="goforit" />
	 <input name="submit" type="submit" value="Continue" /></form></div>';
}
if ($mode == 'import') {
	if ($_POST['importit'] != 'goforit') {url_redirect(adminlink());} else {
		$list = $db->sql_fetchrowset($db->sql_query($sql));
		foreach ($list as $row) {
			$sql2 = 'INSERT INTO '.$prefix.'_pronews_articles VALUES(NULL, "1", "'.Fix_Quotes($row['title'],0,0).'", "'.Fix_Quotes($row['hometext'],0,0).'", "'.Fix_Quotes($row['bodytext'],0,0).'", NULL, NULL,"1", "'.$row['informant'].'", "0", "'.$row['time'].'", "0", "1", "1", "0", NULL, "0", "'.$row['alanguage'].'", "0", "0", "0", "0", "", "", "", "", "", "", "", "", "", "", "", "", "0", "'.$row['score'].'", "'.$row['ratings'].'", "'.$row['topic'].'", "0", "", "'.$row['ihome'].'")';
			$result2 = $db->sql_query($sql2);
		}
		echo '<div align="center"><strong>'._PNIMPCOMPLT.'</strong><br /><a href="'.adminlink().'">'._PNGOBACK.'</a></div>';
	}
}
