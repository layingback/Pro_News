<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Copyright © 2008-2012 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 3.13 $
  $Date: 2012-11-28 08:06:27 $
  Author: layingback
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

global $Blocks, $MAIN_CFG, $module_name, $cpgtpl, $prefix, $CPG_SESS, $blocks_list, $user_agent;
$pnsettings = $MAIN_CFG['pro_news'];
$pn_module_name = $pnsettings['module_name'];
get_lang($pn_module_name);

if (is_active($pn_module_name)) {
	require_once('modules/'.$pn_module_name.'/functions.php');
	$bid = (isset($block['bid'])) ? $block['bid'] : intval($bid);

//  To eliminate the XHTML 1.1 non-compliance error ' Document type does not allow element <style> here' you may do the following
//  Look to see where your theme writes its link rel="stylesheet' commands.

// STEP 1
//  If it is theme.php then:
//  	Remove the 3 lines between the comments below, and the marked single line below that.  Add the following line of HTML:
//  	<link rel="stylesheet" type="text/css" href="themes/'.$CPG_SESS['theme'].'/style/pro_news.css" />
//  	to the top of your theme.php file.  Repeat for the other css files.
//  If it is in header.html then:
//  	Remove the 3 lines between the comments below, and the marked single line below that.  Add the following line of HTML:
//  	<link rel="stylesheet" type="text/css" href="themes/{THEME_PATH}/style/pro_news.css" />
//  	to the top of your header.html file.    Repeat for the other css files.

// STEP 2
//  Remove the entire statement below ...
	if ($module_name != $pn_module_name) {
		if (file_exists('themes/'.$CPG_SESS['theme'].'/style/pro_news.css')) {
			$pnstyle = '
<style type="text/css"><!-- @import url(themes/'.$CPG_SESS['theme'].'/style/pro_news.css);/--></style>';
		} else {
			$pnstyle = '
<style type="text/css"><!-- @import url(themes/default/style/pro_news.css); --> </style>';
		}
		if (ereg('MSIE 7.0', $user_agent) || ereg('MSIE ([0-6].[0-9]{1,2})', $user_agent)) {
			$pnstyle .= '
<style type="text/css"><!-- @import url(themes/'.$CPG_SESS['theme'].'/style/pro_newsie.css);--> </style>';
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
	//  Remove the statement above here

	cache_load_array('blocks_list');
// echo ' <br />bid='.$bid;print_r($blocks_list);echo ' <br/><br />mn='.$module_name;
	$alt_module_name = '';
	if ($module_name == 'blocks') {
		foreach ($blocks_list as &$mod_name) {
// echo ' <br /><br />mod='.$mod_name['title'];
			foreach ($mod_name as $key => &$block) {
// echo ' <br />block='.$key;
				if ($key == $bid) {
					$alt_module_name = $mod_name['title'];
					break 2;
				}
			}
		}
		unset($mod_name);
		unset($block);
	}
// echo ' <br /><br />alt_mn='.$alt_module_name;
// echo ' <br />bpos='.($alt_module_name ? $blocks_list[$alt_module_name][$bid] : $blocks_list[$module_name][$bid]);
//	$content = ProNews::get_cntrblk_content($bid, $blocks_list[$module_name][$bid]);
	$content = ProNews::get_cntrblk_content($bid, ($alt_module_name ? $blocks_list[$alt_module_name][$bid] : $blocks_list[$module_name][$bid]));

	if (!$content && is_admin()) {
		$content = '*** ERROR: <i>Block '.$block['bid'].' ('.$block['title'].') returned no content</i> ***';
	}

//  STEP 3
//  Remove the 1 line below ...

	$content = $pnstyle.$content;

//  Remove the 1 line above here

	if ($content == '') { $content = 'ERROR'; }

}

?>
