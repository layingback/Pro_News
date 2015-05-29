<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Original Beta version Copyright © 2006 by D Mower aka Kuragari
  Subsequent releases Copyright © 2007 - 2013 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 3.15 $
  $Date: 2013-02-06 10:19:19 $
   Author: layingback
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin($module_name)) { die('Access Denied'); }
global $need_pagination, $user_agent;
$pagetitle .= ' '._BC_DELIM.' '.$module_name;
$modheader .= "<script type=\"text/javascript\" src=\"modules/$module_name/includes/scripts.js\"></script>\n";  // added layingback
if (file_exists('themes/'.$CPG_SESS['theme'].'/style/pro_news.css')) {
	$modheader .= '<link rel="stylesheet" type="text/css" href="themes/'.$CPG_SESS['theme'].'/style/pro_news.css" />';
	if (ereg('MSIE 7.0', $user_agent) || ereg('MSIE ([0-6].[0-9]{1,2})', $user_agent)) {
		$modheader .= '<link rel="stylesheet" type="text/css" href="themes/'.$CPG_SESS['theme'].'/style/pro_newsie.css" />';
	}
	$modheader .= '<link rel="stylesheet" type="text/css" href="themes/'.$CPG_SESS['theme'].'/style/pn_specific.css" />';
} else {
	$modheader .= '<link rel="stylesheet" type="text/css" href="themes/default/style/pro_news.css" />';
	if (ereg('MSIE 7.0', $user_agent) || ereg('MSIE ([0-6].[0-9]{1,2})', $user_agent)) {
		$modheader .= '<link rel="stylesheet" type="text/css" href="themes/default/style/pro_newsie.css" />';
	}
	$modheader .= '<link rel="stylesheet" type="text/css" href="themes/default/style/pn_specific.css" />';
}
require('modules/'.$module_name.'/includes/pronews_js.inc');
require_once('modules/'.$module_name.'/includes/load_js.inc');
require_once('includes/nbbcode.php');
require('header.php');
GraphicAdmin('_AMENU3');
require_once('modules/'.$module_name.'/admin/admin_functions.php');
$mode = isset($_POST['mode']) ? Fix_Quotes($_POST['mode']) : (isset($_GET['mode']) ? Fix_Quotes($_GET['mode']) : '');
$do = isset($_GET['do']) ? Fix_Quotes($_GET['do']) : 'none';
OpenTable();
ProNewsAdm::admin_menu();
switch ($mode) {
	case 'add':
		ProNewsAdm::admin_article($do);
	break;

/*	case 'pend':
		ProNewsAdm::admin_artpend($do);
	break;  */

	case 'list':
		ProNewsAdm::admin_artlist($do);
	break;

	case 'cat':
		ProNewsAdm::admin_cat($do);
	break;

	case 'sec':
		ProNewsAdm::admin_sec($do);
	break;

	case 'cfg':
		ProNewsAdm::admin_cfg($do);
	break;

	case 'blk':
		ProNewsAdm::admin_blocks($do);
	break;

	case 'upld':
		ProNewsAdm::admin_upload($do);
	break;

	case 'glry':
		ProNewsAdm::admin_gallery($do);
	break;

	default:
		ProNewsAdm::admin_index();
	break;
}
echo '<div class="pn_menu" align="center"><a target="_blank" href="http://layingback.net/Treasury.html">Pro_News CM</a>&#8482; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <b>C</b>ontent <b>M</b>anagement for Dragonfly <b>CMS</b>&#8482; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <span style="color: grey">&#169; 2007-2013</span><br /><br /></div>';
$cpgtpl->set_filenames(array('body' => 'pronews/admin/admin.html'));
$cpgtpl->display('body');
if ($need_pagination) {
	$cpgtpl->display('pagin', false);
}
CloseTable();
