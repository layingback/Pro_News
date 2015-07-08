<?php
/*********************************************
  Pro News MyBlog Module for Dragonfly CMS
  ********************************************
  Copyright © 2013 - 2015 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  Author: layingback
**********************************************/

if (!defined('CPG_NUKE')) { exit; }
global $CPG_SESS, $module_name, $MAIN_CFG, $userinfo, $prefix;

# install module with name matching your Pro_News blog Section, eg. MyBlog
# calling url format: domain.tld/MyBlog/u=username.html

$usr = isset($_GET['u']) ? Fix_Quotes($_GET['u'], 1) : '';

// echo '<br />usr='.$usr.'<br />';
// echo '<br />sec='.$module_name;

if (is_active($module_name)) {    // MyBlog or whatever

	$blog_name = str_replace('_', ' ', $module_name);

	$pnsettings = $MAIN_CFG['pro_news'];
	$module_name = $pnsettings['module_name'];

	if (is_active($module_name)) {    // Pro_News
		require_once('modules/'.$module_name.'/init.inc');

		if (isset($userinfo['_mem_of_groups'])) {
			$member_a_group = "0";
			foreach ($userinfo['_mem_of_groups'] as $id => $name) {
				if (!empty($name)) {
					$member_a_group = "1";
					break;
				}
			}
		}
		$sql = 'SELECT a.id aid';
		$sql .= ' FROM '.$prefix.'_pronews_articles as a';
		$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
		$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
		$sql .= ' WHERE s.title="'.$blog_name.'"';
		$sql .= ' AND postby="'.$usr.'"';
		$sql .= ' AND approved="1"';
		$sql .= ' AND active="1"';
		if (!can_admin($module_name)) {
			if (!is_user()) {
				$sql .= ' AND (s.view=0 OR s.view=3)';
			} else if ($member_a_group) {
				$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((s.view<4 AND (s.view=0 OR s.view=1)) OR (s.view>3 AND s.view-3=g.group_id)))';
			} else {
				$sql .= ' AND (s.view=0 OR s.view=1)';
			}
		}
		$sql .= ($multilingual ? " AND (alanguage='$currentlang' OR alanguage='')" : '');
		$sql .= ' ORDER BY posttime DESC';
		$sql .= ' LIMIT 1';

		$row = $db->sql_fetchrow($db->sql_query($sql));
		if (isset($row) && $row != '') {
// echo '<br />aid='.$row['aid'];
	#	url_redirect(getlink($module_name.'&amp;aid='.$row['aid']));		// deprecated in favour of Pro_News:: call
		ProNews::article($row['aid'], '');

		}
	}
}

?>
