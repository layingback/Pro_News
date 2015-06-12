<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Copyright © 2006 - 2015 by M Waldron aka layingback

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 3.4 $
  $Date: 2013-01-31 12:53:52 $
   Author: layingback
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
get_lang('Pro_News');

if (is_active('Pro_News') && can_admin('Pro_News')) {
	$num = $db->sql_count($prefix.'_pronews_articles', 'approved="0" AND active="1"');
//  switch lines if you want white-on-black arrow symbol
	$content .= "<b>&#8226;</b>&nbsp;<a href=\"".adminlink('Pro_News')."&amp;mode=list\">"._PNPENDING."</a>: ".$num."<br />";
//	$content .= "<img src=\"images/arrow.gif\" alt=\"\" />&nbsp;<a href=\"".adminlink('Pro_News')."&amp;mode=list\">"._PNPENDING."</a>: ".$num."<br />";
}
