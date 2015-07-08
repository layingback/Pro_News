<?php
/*********************************************
  Pro News MyPages Module for Dragonfly CMS
  ********************************************
  Copyright © 2013 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 1.1 $
  $Date: 2013-05-14 10:09:32 $
  Author: layingback
**********************************************/

if (!defined('CPG_NUKE')) { exit; }
global $CPG_SESS, $module_name, $MAIN_CFG, $userinfo;

if (is_active($module_name)) {		// MyPage
	$pnsettings = $MAIN_CFG['pro_news'];
	$module_name = $pnsettings['module_name'];

	$usr = isset($_GET['u']) ? Fix_Quotes($_GET['u'],1) : '';
	// echo '<br />usr='.$usr.'<br />';

	if (is_active($module_name)) {		// Pro_News
		require_once('modules/'.$module_name.'/init.inc');
		$usr_page = getusrdata($usr, 'user_page');
	// echo '<br />usr='.$usr.'<br />'; print_r($usr_page);

		if (isset($usr_page)) {
	// echo '<br />Gonna link ... to '.$usr_page['user_page'];
	//		url_redirect($usr_page['user_page']);		// deprecated in favour of Pro_News:: call

			preg_match('#aid=([\d]+)#', $usr_page['user_page'], $matches);
			ProNews::article($matches[1], '');
		}
	}
}

?>
