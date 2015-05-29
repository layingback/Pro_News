<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Copyright © 2006 by D Mower aka Kuragari
  Copyright © 2007-2009 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 3.3 $
  $Date: 2011-08-31 10:20:32 $
  Author: layingback
**********************************************/

if (!defined('CPG_NUKE')) { exit; }

global $Blocks, $MAIN_CFG;
$pnsettings = $MAIN_CFG['pro_news'];
$pn_module_name = $pnsettings['module_name'];
get_lang($pn_module_name);

if (is_active($pn_module_name)) {
	require_once('modules/'.$pn_module_name.'/functions.php');
	$bid = (isset($block['bid'])) ? $block['bid'] : intval($bid);
	$content = ProNews::get_block_content($bid);
}
if (!$content) {$content = 'ERROR';
	return trigger_error('Block '.$block['bid'].' ('.$block['title'].') returned no content', E_USER_NOTICE);
}
?>
