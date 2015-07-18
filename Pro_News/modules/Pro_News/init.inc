<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Copyright © 2015 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  Author: layingback
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
$pnsettings = $MAIN_CFG['pro_news'];
$pn_module_name = $pnsettings['module_name'];
// echo '<br />imn='.$module_name.' ipn='.$pn_module_name;

$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
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
$modheader .= "<script type=\"text/javascript\" src=\"modules/$pn_module_name/includes/scripts.js\"></script>\n";  // added layingback
require_once('language/'.$language.'/pro_news.php');
require_once('modules/'.$pn_module_name.'/includes/load_js.inc');
require_once('modules/'.$pn_module_name.'/functions.php');

if (!Cache::array_load('board_config', 'Forums', true)) {
	$result = $db->sql_query('SELECT * FROM '.$prefix.'_bbconfig');
	while ($row = $db->sql_fetchrow($result)) {
		$board_config[$row['config_name']] =  str_replace("'", "\'",$row['config_value']);
		$new[$config_name] = ( isset($_POST[$config_name]) ) ? $_POST[$config_name] : $board_config[$config_name];
		if ($config_name == 'smilies_path') {
			$new['smilies_path'] = 'images/smiles';
		}
	}
	Cache::array_save('board_config', 'Forums');
}

/* // layingback - code added to limit image display width - comment out for legacy behaviour
if (!Cache::array_load('attach_config', 'Forums', true)) {
	$result = $db->sql_query('SELECT * FROM '.$prefix.'_bbattachments_config');
	while ($row = $db->sql_fetchrow($result)) {
		$attach_config[$row['config_name']] = $row['config_value'];
	}
	$db->sql_freeresult($result);
	$attach_config['board_lang'] = $board_config['default_lang'];
	Cache::array_save('attach_config', 'Forums', $attach_config);
}
$attach_config['img_max_width_remote'] = 1;
if ($attach_config['img_max_width_remote']) {
	$modheader .= '
<style type="text/css">.pn_content img{max-width:'.$attach_config['img_link_width'].'px}
<!--[if lt IE 8]>
.pn_content img {
	width: expression(this.clientWidth > '.$attach_config['img_link_width'].' ? "'.$attach_config['img_link_width'].'px" : this.clientWidth+"px");
}
<![endif]-->
</style>
';
}
// end of image display width limit
*/

// layingback - code added to limit image display width of linked remote images (BBCode))
if ($pnsettings['img_max_width_remote'] != '' && $pnsettings['img_max_width_remote'] != '0') {
	$modheader .= '
<style type="text/css">.pn_content img{max-width:'.$pnsettings['img_max_width_remote'].'px}
<!--[if lt IE 8]>
.pn_content img {
	width: expression(this.clientWidth > '.$pnsettings['img_max_width_remote'].' ? "'.$pnsettings['img_max_width_remote'].'px" : this.clientWidth+"px");
}
<![endif]-->
</style>
';
}
// end of image display width limit
