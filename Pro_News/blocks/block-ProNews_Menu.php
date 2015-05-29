<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Copyright © 2008-2009 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 3.7 $
  $Date: 2012-07-18 08:38:50 $
  Author: layingback
**********************************************/

if (!defined('CPG_NUKE')) { exit; }

global $Blocks, $CPG_SESS, $module_name, $MAIN_CFG;
$pnsettings = $MAIN_CFG['pro_news'];
$pn_module_name = $pnsettings['module_name'];

if (is_active($pn_module_name)) {
	require_once('modules/'.$pn_module_name.'/functions.php');
	$bid = (isset($block['bid'])) ? $block['bid'] : intval($bid);
	$content = ProNews::get_block_menu($bid);
}
if ($module_name != $pn_module_name) {			// added by Masino Sinaga, June 30, 2009 for support CSS
	if (file_exists('themes/'.$CPG_SESS['theme'].'/style/pro_news.css')) {
		$pnstyle = '
<style type="text/css"><!-- @import url(themes/'.$CPG_SESS['theme'].'/style/pro_news.css); --></style>';
	} else {
		$pnstyle = '
<style type="text/css"><!-- @import url(themes/default/style/pro_news.css); --></style>';
	}
	$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	if (ereg('MSIE 7.0', $user_agent) || ereg('MSIE ([0-6].[0-9]{1,2})', $user_agent)) {
		$pnstyle .= '
<style type="text/css"><!-- @import url(themes/'.$CPG_SESS['theme'].'/style/pro_newsie.css); --></style>';
	}
	if (file_exists('themes/'.$CPG_SESS['theme'].'/style/pn_specific.css')) {
		$pnstyle .= '
<style type="text/css"><!-- @import url(themes/'.$CPG_SESS['theme'].'/style/pn_specific.css);--> </style>';
	} else {
		$pnstyle .= '
<style type="text/css"><!-- @import url(themes/default/style/pn_specific.css); --> </style>';
	}
} else {
	$pnstyle = '';
}
if (!$content) {$content = 'ERROR';
	return trigger_error('Menu Block '.$block['bid'].' ('.$block['title'].') returned no content', E_USER_NOTICE);
} else {
	$content .= $pnstyle."\n".'<script language="JavaScript" type="text/javascript">

<!--
function pn_toggle(x)
{ x.className = (x.className=="pn_show") ? "pn_hide" : "pn_show"; }
//-->
</script>';

}
?>
