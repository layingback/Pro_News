<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Copyright © 2007 - 2012 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 1.4 $
  $Date: 2013-04-22 09:39:18 $
   Author: layingback
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

global $MAIN_CFG;
$pnsettings = $MAIN_CFG['pro_news'];
$pn_module_name = $pnsettings['module_name'];
get_lang($pn_module_name);

if (isset($userinfo['_mem_of_groups'])) {
	$member_a_group = "0";
	foreach ($userinfo['_mem_of_groups'] as $id => $name) {
		if (!empty($name)) {
			$member_a_group = "1";
		}
	}
}

// Last 10 viewable Articles
$sql = 'SELECT a.id aid, a.title as atitle, c.id as cid, c.title as ctitle, s.id as sid, s.title as stitle';
$sql .= ' FROM '.$prefix.'_pronews_articles as a';
$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
$sql .= ' WHERE postby="'.$username.'"';
if (!can_admin($pn_module_name)) {
	if (!is_user()) {
		$sql .= ' AND (s.view=0 OR s.view=3)';
	} else if ($member_a_group) {
		$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((s.view<4 AND (s.view=0 OR s.view=1)) OR (s.view>3 AND s.view-3=g.group_id)))';
	} else {
		$sql .= ' AND (s.view=0 OR s.view=1)';
	}
}
$sql .= ' ORDER BY aid DESC LIMIT 0,10';
$result = $db->sql_query($sql);
if ($db->sql_numrows($result) > 0) {
	echo '<br />';
	OpenTable();
	echo '<div align="left"><strong>'.$username.'\'s '._PNLAST10SUBS.':</strong><ul>';
	while (list($aid, $atitle, $cid, $ctitle, $sid, $stitle) = $db->sql_fetchrow($result)) {
		echo '<li><a href="'.getlink('Pro_News&amp;sid='.$sid).'">'.$stitle.'</a> &#187; <a href="'.getlink('Pro_News&amp;cid='.$cid).'">'.$ctitle.'</a> &#187; <a href="'.getlink('Pro_News&amp;aid='.$aid).'">'.$atitle.'</a></li>';
	}
	echo '</ul></div>';
	CloseTable();
}
