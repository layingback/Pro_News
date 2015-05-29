<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2013 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 3.5 $
  $Date: 2013-05-14 09:48:10 $
  Author: layingback
********************************************************/
if (!defined('CPG_NUKE')) { exit; }
global $Blocks, $MAIN_CFG, $db, $prefix, $module_name;
$pnsettings = $MAIN_CFG['pro_news'];
$pn_module_name = $pnsettings['module_name'];
get_lang($pn_module_name);
if (is_active($pn_module_name) && ($module_name == $pn_module_name || (can_admin($pn_module_name) && $module_name == 'blocks'))) {
	require_once('modules/'.$pn_module_name.'/functions.php');
	if (is_user() && (can_admin($pn_module_name) || ProNews::in_group_list($pnsettings['mod_grp']))) {
		$bid = (isset($block['bid'])) ? $block['bid'] : intval($bid);
		cache_load_array('blocks_list');
		$content = ProNews::get_moderation ($bid, $sec, $cat, $art_cnt);
		if ($content == '') {
			if ($bid <> '') {
				$arts_per_page = intval($art_cnt) > 0 ? intval($art_cnt) : 5;
				$page = 1;
				$offset = ($page - 1) * $arts_per_page;
				$select = 'SELECT a.id as aid, postby, a.title, posttime, catid, c.title as ctitle, s.title as stitle FROM ';
				$sql = $prefix.'_pronews_articles as a, '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_sections as s';
				$sql .= ' WHERE a.catid = c.id AND c.sid = s.id';
				if ($sec != '') { $sql .= ' AND sid="'.$sec.'"'; }
				if ($cat != '') { $sql .= ' AND catid="'.$cat.'"'; }
				$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((s.view<4 AND (s.view=0 OR s.view=1)) OR (s.view>3 AND s.view-3=g.group_id)))';
				$sql .= ' AND approved = 0 AND active = 1';
				$limit = ' ORDER BY posttime DESC LIMIT '.$offset.', '.$arts_per_page;
				$result = $db->sql_query($select.$sql.$limit);
				if ($db->sql_numrows($result) < 1) {
					$content .= '<i>'._PNNO.' '._PNPENDING.'</i>';
				} else {
					if ($page == 1) {
						$numarticles = $db->sql_count($sql);
					}
					$result = $db->sql_query($select.$sql.$limit);
					$list = $db->sql_fetchrowset($result);
					$db->sql_freeresult($result);
					$pages = ceil($numarticles/$arts_per_page);
					if ($numarticles > 0) {
						$content .= '<i><b>'.$numarticles.'</b> '._PNPENDING.'</i>';
						if ($numarticles > $arts_per_page) {
							$pages = ceil($numarticles/$arts_per_page);
							$content .= '<br /><i class="pn_tinygrey pn_floatright"> viewing '.($offset + 1).' - '.($page * $arts_per_page).'</i>';
						}
						$content .= '<br />';
						if ((isset($list)) && ($list != '')) {
							foreach ($list as $row) {
//								$url_text = $pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', ($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title']) : ($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title'])))) : '';
								$content .= '&#8226; <a class = "pn_tiny" href="'.getlink($pn_module_name."&amp;aid=".$row['aid'].'&amp;uap='.strrev(gmtime())).'" title="'.$row['stitle'].' > '.$row['ctitle'].'">'.$row['title'].'</a><br />';
							}
						}
					}
					// uncomment the line below if you cannot use a menu item to access PDF Upload for Moderators
//					$content .= '<br /><a class = "pn_tiny" href="'.getlink($pn_module_name."&amp;mode=upld").'"> &nbsp; '._PNMODERATOR.' :: '._PNPUPLOAD.' &nbsp; </a><br />';
					$content .= '<br /><a href="http://layingback.net" title="Content Management for DragonflyCMS&#8482;"><span class="pn_tinygrey">Pro_News CM&#8482; &nbsp; &nbsp; &#169; 2007-2013</span></a><br />';
				}
			} else {
				$content = 'ERROR';
			}
		} elseif (is_admin()) {
			echo $content;			// error in block message
		} else {
			$content = 'ERROR';
		}
	} else {
		$content = 'ERROR';
	}
} else {
	$content = 'ERROR';
}
