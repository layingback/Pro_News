<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Original Beta version Copyright © 2006 by D Mower aka Kuragari
  Subsequent releases Copyright © 2007-2015 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  Interface to ForumsPro provided and Copyright © 2007 by Sarah
  http://www.diagonally.org

  $Revision: 3.59 $
  $Date: 2013-05-28 13:15:53 $
  Author: layingback
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
global $db, $prefix, $MAIN_CFG;
$gblsettings = $MAIN_CFG['global'];
$pnsettings = $MAIN_CFG['pro_news'];
if ($pnsettings['text_on_url']) {
	setlocale(LC_CTYPE, 'en_US.utf8');			// iconv TRANSLIT fails in some configurations - locale defaults to C or POSIX
}

// To support multi-home (see CHANGES.txt) add domain name to array below starting at 2, e.g:
//		$domain = array(2=>'layingback.net', 3=>'layingback.com');
// Do NOT include http://
// Do NOT include www. (doing so will require all users to access only via www., omitting it will allow either)
//  - Remember to also place corresponding entries in admin_functions.php - and the order must match!
$domain = array(2=>'', 3=>'');
// If you are unsure what the domain value should be, uncomment the line below, and display Home page, value appears at very top
// echo ' $domain_name='.ereg_replace('www.', '', $_SERVER['SERVER_NAME']);

class ProNews  {
	function get_block_content($bid) {
		global $db, $prefix, $MAIN_CFG, $multilingual, $currentlang, $sitename, $userinfo;
		$pnsettings = $MAIN_CFG['pro_news'];
		$pn_module_name = $pnsettings['module_name'];
		$sql = 'SELECT * FROM '.$prefix.'_pronews_blocks WHERE bid='.$bid;
		$bsets = $db->sql_fetchrow($db->sql_query($sql));
		if (isset($bsets) && $bsets != '') {
			if ($bsets['type'] == 'latest' || $bsets['type'] == 'random' || $bsets['type'] == 'popular' || $bsets['type'] == 'rated') {
				$sql = 'SELECT a.id, a.catid, a.title, a.posttime, a.postby, s.id ssid, s.title stitle, c.title ctitle, c.id cid, c.sid';
				$sql .= ' FROM '.$prefix.'_pronews_articles as a';
				$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
				$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
				if ($pnsettings['actv_sort_ord'] == '1') {
					$sql .= ' LEFT JOIN '.$prefix.'_pronews_schedule as h ON a.id=h.id AND CASE s.art_ord';
					$sql .= ' WHEN "9" THEN h.newstate="0" WHEN "10" THEN h.newstate="1" WHEN "11" THEN h.newstate="0" WHEN "12" THEN h.newstate="1" END';
				}
//				$sql .= ((in_array(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), (array)$domain) ? ' WHERE (s.in_home="1" OR s.in_home="'.array_search(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), $domain).'")' : ' WHERE s.in_home="1"'));
//				$sql .= ' AND s.id != "0"';
				$sql .= ' WHERE s.id != "0"';
				$sql .= ' AND a.approved="1" AND a.active="1"';
				if (!can_admin($pn_module_name)) {
					$member_a_group = "0";
					if (isset($userinfo['_mem_of_groups'])) {
						foreach ($userinfo['_mem_of_groups'] as $id => $name) {
							if (!empty($name)) {
								$member_a_group = "1";
							}
						}
					}
					if (!is_user()) {
						$sql .= ' AND (s.view=0 OR s.view=3)';
					} else if ($member_a_group) {
						$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((s.view<4 AND (s.view=0 OR s.view=1)) OR (s.view>3 AND s.view-3=g.group_id)))';
					} else {
						$sql .= ' AND (s.view=0 OR s.view=1)';
					}
				}
				$sql .= ($multilingual) ? ' AND (alanguage="" OR alanguage="'.$currentlang.'")' : '';

				if ($bsets['section'] == 'ALL' && $bsets['category'] == '' && $bsets['type'] == 'latest') {
					if ($pnsettings['art_ordr'] == '0') {
						$sql .= ' ORDER BY posttime DESC';
					} else {
						$sql .= ' ORDER BY '.$pnsettings['art_ordr'];
					}
				} else {
					if ($bsets['category'] != '') {
						if (strpos($bsets['category'], ',') === FALSE) {
							$sql .= ' AND c.id="'.$bsets['category'].'"';
						} else {
							$sql .= ' AND c.id IN ('.$bsets['category'].')';
						}
					} else if ($bsets['section'] != 'ALL') {
						$sql .= ' AND s.id="'.$bsets['section'].'"';
					}
					if ($bsets['type'] == 'latest') {
						$artsortkey = $pnsettings['art_ordr'] / '2';
						$artsortord = ($pnsettings['art_ordr'] % '2')  ? 'DESC' : 'ASC';
						if ($artsortkey < 1) {
							$artsortfld = 'posttime';
						} elseif ($artsortkey < 2) {
							$artsortfld = 'a.title';
						} elseif ($artsortkey < 3) {
							$artsortfld = 'ratings';
						} else {
							$artsortfld = 'a.counter';
						}
						$sql .= ' ORDER BY display_order DESC, ';
						$sql .= ' CASE s.art_ord WHEN 0 THEN '.$artsortfld.' END '.$artsortord.',';
						$sql .= ' CASE s.art_ord WHEN 1 THEN posttime END ASC,';
						$sql .= ' CASE s.art_ord WHEN 2 THEN posttime END DESC,';
						$sql .= ' CASE s.art_ord WHEN 3 THEN a.title END ASC,';
						$sql .= ' CASE s.art_ord WHEN 4 THEN a.title END DESC,';
						$sql .= ' CASE s.art_ord WHEN 5 THEN ratings END ASC,';
						$sql .= ' CASE s.art_ord WHEN 6 THEN ratings END DESC,';
						$sql .= ' CASE s.art_ord WHEN 7 THEN a.counter END ASC,';
						$sql .= ' CASE s.art_ord WHEN 8 THEN a.counter END DESC';
						if ($pnsettings['actv_sort_ord'] == '1') {
							$sql .= ', CASE s.art_ord WHEN 9 THEN h.dttime END ASC,';
							$sql .= ' CASE s.art_ord WHEN 10 THEN h.dttime END DESC,';
							$sql .= ' CASE s.art_ord WHEN 11 THEN h.dttime END ASC,';
							$sql .= ' CASE s.art_ord WHEN 12 THEN h.dttime END DESC';
						}
					} elseif ($bsets['type'] == 'random') {
						$sql .= ' AND a.id >= (SELECT FLOOR(MAX(id) * RAND()) FROM '.$prefix.'_pronews_articles) ORDER BY a.id';
					} elseif ($bsets['type'] == 'popular') {
						$sql .= ' ORDER BY counter DESC '.($pnsettings['art_ordr'] == '0' ? '' : $pnsettings['art_ordr']);
					} else {
						$sql .= ' ORDER BY score / ratings DESC '.($pnsettings['art_ordr'] == '0' ? '' : $pnsettings['art_ordr']);
					}
				}
				$sql .= ' LIMIT '.$bsets['num'];
				$result = $db->sql_query($sql);
				$list = $db->sql_fetchrowset($result);
				$db->sql_freeresult($result);
				if ((isset($list)) && ($list != '')) {
					$content = '<div>';
					foreach ($list as $row) {
						$url_text = ($pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace('&amp;','',($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title']) : ($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title']))))) : '');
						$content .= '<div class="pn_blklnk">'.($row['posttime'] > $userinfo['user_lastvisit'] ? '<img src="images/pro_news/bullet_red.png" title="New" alt="new" />' : '<img src="images/pro_news/bullet_blue.png" alt="" />').'&nbsp;'.'<a href="'.getlink("$pn_module_name&amp;aid=".$row['id'].$url_text).'" title="'.htmlentities($row['stitle']).' &raquo; '.htmlentities($row['ctitle']).' &raquo; '.htmlentities($row['title']).' &#10;- '.htmlentities($row['postby']).' - '.ProNews::create_date('d M y', $row['posttime']).'&nbsp;'.'">'.$row['title'].'</a></div>';
					}
					if ($bsets['section'] == 'ALL') {			// link to all
						$content .= '<div style="text-align:center;"><br /><a href="'.getlink("$pn_module_name").'"><strong>'.$sitename.' '._PNARTICLES.'</strong></a></div>';
					} elseif ($bsets['category'] != '') {		// link to cid
						$content .= '<div style="text-align:center;"><br /><a href="'.getlink("$pn_module_name&amp;cid=".$row['cid']).'"><strong>'.$row['ctitle'].' '._PNARTICLES.'</strong></a></div>';
					} else {									// link to sid
						$content .= '<div style="text-align:center;"><br /><a href="'.getlink("$pn_module_name&amp;sid=".$row['sid']).'"><strong>'.$row['stitle'].' '._PNARTICLES.'</strong></a></div>';
					}
					return $content.'</div>';
				} else {
					if (can_admin($pn_module_name)) {
						return 'Category Empty';
					} else {
							return '';
					}
				}
			} else {return 'Invalid Settings';}
		} else {return '<center><br />! Block Not Initialised !<br /><br />Install Pro_News Blocks ONLY from Administration > Pro_News > Blocks</center>';}
	}

	function get_cntrblk_content($bid, $bpos) {
		global $BASEHREF, $db, $prefix, $multilingual, $currentlang, $cpgtpl, $userinfo, $bgcolor3, $MAIN_CFG, $domain, $CPG_SESS, $home;
		$pnsettings = $MAIN_CFG['pro_news'];
		$pn_module_name = $pnsettings['module_name'];
		$artsortkey = $pnsettings['art_ordr'] / '2';
		$artsortord = ($pnsettings['art_ordr'] % '2')  ? 'DESC' : 'ASC';
		if ($artsortkey < 1) {
			$artsortfld = 'posttime';
		} elseif ($artsortkey < 2) {
			$artsortfld = 'a.title';
		} elseif ($artsortkey < 3) {
			$artsortfld = 'ratings';
		} else {
			$artsortfld = 'counter';
		}
// echo ' artsortkey='.$artsortkey.' artsordfld='.$artsortfld.' artsordord='.$artsortord;
		$sql = 'SELECT * FROM '.$prefix.'_pronews_blocks WHERE bid='.$bid;
		$bsets = $db->sql_fetchrow($db->sql_query($sql));
		if (isset($bsets) && $bsets != '') {
			if (substr($bsets['type'], 0, 5) == 'tmpl_') {
				$bsets['type'] = substr($bsets['type'], 5);
			}
// echo '<br />t='.$bsets['type'];

			$use_tmpl = '';
			if ($bsets['type'] == 'trandomctr' || $bsets['type'] == 'tlatestctr' || $bsets['type'] == 'toldestctr' || $bsets['type'] == 'theadlines') {
				$use_tmpl = '1';
				$bsets['type'] = trim($bsets['type'], 't');
			}
// echo '<br />ut='.$use_tmpl.' - '.$bsets['type'];
			if ($bsets['type'] == 'headlines') {
				$arts_per_hdline = (isset($pnsettings['per_hdline']) && ($pnsettings['per_hdline'] > '0')) ? $pnsettings['per_hdline'] : '4';
				$sql = 'SELECT s.id sid,s.title stitle,s.description sdescription,s.view view, art_ord, keyusrfld, template';
				if ($pnsettings['display_by'] == '2' || $bsets['category'] != '') {$sql .= ',c.id cid,c.title ctitle,c.description cdescription,c.icon icon';}
				$sql .= ' FROM 	'.$prefix.'_pronews_sections as s';
				if ($pnsettings['display_by'] == '2' || $bsets['category'] != '') {$sql .= ' JOIN '.$prefix.'_pronews_cats as c ON c.sid=s.id';}
//				$sql .= ' WHERE s.in_home="1"';
				$sql .= ((in_array(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), (array)$domain) ? ' WHERE (s.in_home="1" OR s.in_home="'.array_search(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), $domain).'")' : ' WHERE s.in_home="1"'));
//				if ($bsets['section'] != 'ALL') {$sql .= ' AND s.id="'.intval($bsets['section']).'"';}
				if ($bsets['category'] != '') {
					if (strpos($bsets['category'], ',') === FALSE) {
						$sql .= ' AND c.id="'.intval($bsets['category']).'"';
					} else {
						$sql .= ' AND c.id IN ('.$bsets['category'].')';
					}
				} elseif ($bsets['section'] != 'ALL') {
					$sql .= ' AND s.id="'.intval($bsets['section']).'"';
				}
				$sql .= ' ORDER BY s.sequence ASC';
				if ($pnsettings['display_by'] == '2' || $bsets['category'] != '') {$sql .= ', c.sequence ASC';}
				if ($pnsettings['display_by'] == '0') {$sql .= ' LIMIT 1';}
				$result = $db->sql_query($sql);
				$listc = $db->sql_fetchrowset($result);
				$artcount = $db->sql_numrows($result);
				$db->sql_freeresult($result);
				if (isset($listc) && $listc != '') {
					require_once('includes/nbbcode.php');
					$last_sec = ''; $last_cat = ''; $i = '0'; $z = '0';
					foreach ($listc as $rowc) {
					$r_artsrtky = ($rowc['art_ord'] -1)/ '2';
					$r_artsrtord = ($rowc['art_ord'] % '2')  ? 'ASC' : 'DESC';
					if ($r_artsrtky <= 1) {
						$r_artsrtfld = 'posttime';
					} elseif ($r_artsrtky <= 2) {
						$r_artsrtfld = 'a.title';
					} elseif ($r_artsrtky <= 3) {
						$r_artsrtfld = 'ratings';
					} else {
						$r_artsrtfld = 'counter';
					}
// echo ' artsortkey='.$artsortkey.' artsortfld='.$artsortfld.' artsortord='.$artsortord;
						if (($rowc['view'] == '0') || ($rowc['view'] == '3' && !is_user()) || (can_admin($pn_module_name)) || (is_user() && (($rowc['view'] == '1') || (($rowc['view'] > 3) && (isset($userinfo['_mem_of_groups'][$rowc['view'] - 3])))))) {
							$sql = 'SELECT SQL_CALC_FOUND_ROWS a.id aid,a.*';
							$sql .= ' FROM '.$prefix.'_pronews_articles as a';
							if ($pnsettings['display_by'] == '2' || $bsets['category'] != '') {
								$sql .= ' WHERE a.catid='.$rowc['cid'];
							} elseif ($pnsettings['display_by'] == '1') {
								$sql .= ' JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
								$sql .= ' WHERE c.sid='.$rowc['sid'];
							} else {
								$sql .= ' JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
								$sql .= ' JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
//								$sql .= ' WHERE s.in_home="1"';
								$sql .= ((in_array(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), (array)$domain) ? ' WHERE (s.in_home="1" OR s.in_home="'.array_search(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), $domain).'")' : ' WHERE s.in_home="1"'));
							}
							$sql .= ' AND a.approved="1" AND a.active="1" AND display<>"0"';
							$sql .= ($multilingual ? " AND (alanguage='$currentlang' OR alanguage='')" : '');
							$sql .= ' ORDER BY display_order DESC';
							if ($rowc['art_ord'] == 0) {
								$sql .= ', '.$artsortfld.' '.$artsortord;
							} else {
								$sql .= ', '.$r_artsrtfld.' '.$r_artsrtord;
							}
							$sql .= ' LIMIT '.$bsets['num'];
							$result = $db->sql_query($sql);
							$list = $db->sql_fetchrowset($result);
							$result = $db->sql_query('SELECT FOUND_ROWS()');
							$total_rows = $db->sql_fetchrowset($result);
							$db->sql_freeresult($result);
							if (isset($list) && $list != '') {
								foreach ($list as $row) {
									$url_text = ($pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace('&amp;','',($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title']) : ($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title']))))) : '');
									if (($pnsettings['display_by'] == '0' && $i == '0') || ($pnsettings['display_by'] == '1' && $last_sec != $rowc['stitle']) || (($pnsettings['display_by'] == '2' || $bsets['category'] != '') && $last_cat != $rowc['ctitle'])) {
										$i = 1;
										$bgcolor = "";
										if (($row['image'] != '') && ($row['image'] != '0')) {
											$imagesize = getimagesize($pnsettings['imgpath'].'/'.$row['image']);
											if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
												$thumbimage = $pnsettings['imgpath'].'/thumb_'.$row['image'];
											} else {
												$thumbimage = $pnsettings['imgpath'].'/'.$row['image'];
											}														  // Check if thumb exists before linking - layingback 061122
											$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" />';
											$iconimage = $pnsettings['imgpath'].'/icon_'.$row['image'];
										} elseif ($pnsettings['show_noimage'] != '0' && file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholder.png')) {
											$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholder.png';
											$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="" />';
											$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholdermini.png';
										} elseif ($pnsettings['show_noimage'] != '0' && file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png')) {
											$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png';
											$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="" />';
											$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholdermini.png';
										} else {
											$display_image = '';
											$thumbimage = '';
											$iconimage = '';
										}
										if ($row['intro'] == '') {
											if (strlen($row['content']) > $pnsettings['introlen']) {
//												$text = substr_replace($row['content'],' ...',$pnsettings['introlen']);
												$text = substr_replace($row['content'],'',$pnsettings['introlen']);
												$morelink = '1';
											} else {
												$text = $row['content'];
												$morelink = '0';
											}
										} else {
//											$text = substr_replace($row['intro'],' ...',$pnsettings['hdln1len']);
											$text = substr_replace($row['intro'],'',$pnsettings['hdln1len']);
											if (strlen($row['intro']) > $pnsettings['hdln1len'] && $row['content'] != '') {
												$morelink = '1';
											} else {
												$morelink = '0';
											}
										}
										if ($pnsettings['display_by'] == '2' || $bsets['category'] != '') {
											$artlink = getlink("$pn_module_name&amp;cid=".$row['catid']);
										} elseif ($pnsettings['display_by'] == '1') {
											$artlink = getlink("$pn_module_name&amp;sid=".$rowc['sid']);
										} else {
											$artlink = getlink("$pn_module_name&amp;mode=home");
										}
										$cpgtpl->assign_block_vars('cblk_hdline', array(
											'G_CBLK_UP' => (($bpos == 'd') ? '' : '1'),
											'G_SECBRK' => (($pnsettings['display_by'] == '1' || $pnsettings['display_by'] == '2') && ($rowc['stitle'] != $last_sec) && ($last_sec)) ? '1' : '0',
											'S_SECBRK' => ProNews::getsctrnslt('_PN_SECTITLE_', $rowc['stitle'], $rowc['sid']),
											'U_SECDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_SECDESC_', $rowc['sdescription'], $rowc['sid']), 1, true)),
											'G_CATBRK' => (($pnsettings['display_by'] == '2' || $bsets['category'] != '') && $rowc['ctitle'] != $last_cat) ? '1' : '0',
//											'S_CATBRK' => ($pnsettings['display_by'] == '2') ? $rowc['ctitle'] : '',
											'S_CATBRK' => ProNews::getsctrnslt('_PN_CATTITLE_', $rowc['ctitle'], $rowc['cid']),
											'U_CATDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_CATDESC_', $rowc['cdescription'], $rowc['cid']), 1, true)),
											'S_INTRO' => decode_bb_all($text, 1, true),  // true param added for images - layingback
											'S_TITLE' => $row['title'],
											'S_ICON' => ($pnsettings['display_by'] == '2' || $bsets['category'] != '') ? ($rowc['icon'] != '') ? $rowc['icon'] : 'clearpixel.gif' : 'clearpixel.gif',
											'T_ICON' => ($pnsettings['display_by'] == '2' || $bsets['category'] != '') ? $rowc['ctitle'] : '',
											'L_POSTBY' => _PNPOSTBY,
											'S_POSTBY' => $row['postby'],
											'T_POSTBY' => getlink("Your_Account&amp;profile=".$row['postby']),
											'L_POSTON' => _PNPOSTON,
											'S_POSTTIME' => ProNews::create_date(false, $row['posttime']),
											'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
											'S_CATEGORY' => $row['catid'],
											'S_CATLINK' => getlink("$pn_module_name&amp;cid=".$row['catid']),
											'S_IMAGE' => ($row['image'] == '' && $pnsettings['show_noimage'] == '0') ? '' : $display_image,
											'T_THUMBIMAGE' => $thumbimage,
											'T_ICONIMAGE' => $iconimage,
											'U_MORELINK' => getlink("$pn_module_name&amp;aid=".$row['aid'].$url_text),
											'S_MORELINK' => _PNMORE,
											'G_MORELINK' => $morelink,
											'G_ICONS' => ($pnsettings['show_icons'] == '1') ? '1' : '',
											'U_ARTLINK' => $artlink,
											'U_ALLLINK' => getlink("$pn_module_name&amp;mode=home"),
											'L_HDLINES' => _PNLHDLINES,
											'S_HDLINES' => _PNALL,
											'G_MORE_ARTS' => $i+1 == count($list) && $total_rows['0']['FOUND_ROWS()'] > count($list) ? '1' : ''
										));
										$last_sec = $rowc['stitle']; $last_cat = ($pnsettings['display_by'] == '2' || $bsets['category'] != '') ? $rowc['ctitle'] : '';
									} else {
										if ($i < $bsets['num']) {
											if (($row['image'] != '') && ($row['image'] != '0')) {
												$imagesize = getimagesize($pnsettings['imgpath'].'/'.$row['image']);
												if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
													$iconimage = $pnsettings['imgpath'].'/icon_'.$row['image'];
													$display_image = '<img class="pn_image" src="'.$iconimage.'" alt="'.$row['title'].'" />';
												} else {
													$iconimage = $pnsettings['imgpath'].'/'.$row['image'];
													$display_image = '<img class="pn_image" src="'.$iconimage.'" alt="'.$row['caption'].'" />';
												}														  // Check if thumb exists before linking - layingback 061122
												$iconimage = $pnsettings['imgpath'].'/icon_'.$row['image'];
												$display_image = '<img class="pn_image" src="'.$iconimage.'" alt="'.$row['caption'].'" />';
											} elseif (file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholdermini.png')) {
												$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholdermini.png';
												$display_image = '<img class="pn_image" src="'.$iconimage.'" alt="" />';
												$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholder.png';
											} elseif ($pnsettings['show_noimage'] != '0' && file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholdermini.png')) {
												$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholdermini.png';
												$display_image = '<img class="pn_image" src="'.$iconimage.'" alt="" />';
												$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png';
											} else {
												$display_image = '';
												$iconimage = '';
												$thumbimage = '';
											}
											if ($row['intro'] == '') {
												if(strlen($row['content']) > $pnsettings['introlen']) {
													$text = substr_replace($row['content'],'',$pnsettings['introlen']);
													$morelink = '1';
												} else {
													$text = $row['content'];
													$morelink = '0';
												}
											} else {
//												$text = substr_replace($row['intro'],' ...',$pnsettings['hdlnlen']);
												$text = substr_replace($row['intro'],'',$pnsettings['hdlnlen']);
												$morelink = '1';
											}
											if ($pnsettings['display_by'] == 2 || $bsets['category'] != '') {
												$titlebrk = $rowc['stitle'].' '._BC_DELIM.' '.$rowc['ctitle'];
											} elseif ($pnsettings['display_by'] == 1) {
												$titlebrk = $rowc['stitle'];
											} else {
												$titlebrk = '';
											}
											$bgcolor = ($bgcolor == '') ? ' style="background-color: '.$bgcolor3.'"' : '';
											$cpgtpl->assign_block_vars('cblk_hdline.hdline_addtl', array(
												'U_BGCOLOR' => $bgcolor,
												'S_INTRO' => decode_bb_all($text, 1, true),  // true param added for images - layingback
												'S_TITLE' => $row['title'],
												'S_ICON' => ($pnsettings['display_by'] == '2' || $bsets['category'] != '') ? ($rowc['icon'] != '') ? $rowc['icon'] : 'clearpixel.gif' : 'clearpixel.gif',
												'T_ICON' => ($pnsettings['display_by'] == '2' || $bsets['category'] != '') ? $rowc['ctitle'] : '',
												'L_POSTBY' => _PNPOSTBY,
												'S_POSTBY' => $row['postby'],
												'T_POSTBY' => getlink("Your_Account&amp;profile=".$row['postby']),
												'S_POSTTIME' => ProNews::create_date(false, $row['posttime']),
												'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
												'S_CATEGORY' => $row['catid'],
												'S_CATLINK' => getlink("$pn_module_name&amp;cid=".$row['catid']),
												'S_IMAGE' => ($row['image'] == '' && $pnsettings['show_noimage'] == '0') ? '' : $display_image,
												'T_ICONIMAGE' => $iconimage,
												'T_THUMBIMAGE' => $thumbimage,
												'U_MORELINK' => getlink("$pn_module_name&amp;aid=".$row['aid'].$url_text),
												'S_MORELINK' => _PNMORE,
												'G_MORELINK' => $morelink,
												'G_ICONS' => ($pnsettings['show_icons'] == '1') ? '1' : '',

												'S_ARTCOUNT' => min($artcount, $bsets['num']),
												'S_ARTINDEX' => $z,
												'G_MORE_ARTS' => $i+1 == count($list) && $total_rows['0']['FOUND_ROWS()'] > count($list) ? '1' : ''


											));
											$i++;
										}
									}
									$z++;
								}
							}
						}
					}
// echo ' ok bpos='.$bpos;
					ob_start();
					if ($use_tmpl) {
						$tplt = 'pronews/article/'.($rowc['template'] != '' ? $rowc['template'] : $pnsettings['template']);
					} else {
					$cbtplt = 'ctrblk_'.(($bpos == 'd') ? 'dn' : 'up').'.html';
					if (file_exists('themes/'.$CPG_SESS['theme'].'/template/pronews/article/'.$cbtplt)) {
						$tplt = 'pronews/article/'.$cbtplt;
					} else {
						$tplt = 'pronews/'.$cbtplt;
					}
					}
// echo '<br />cpgtpl='.$tplt;
					$cpgtpl->set_filenames(array('blkbody' => $tplt));
					$cpgtpl->display('blkbody', false);
					$content = ob_get_clean();
					$cpgtpl->unset_block('cblk_hdline');
				} else {
					$cpgtpl->assign_block_vars('cblkempty', array(
						'S_MSG' => _PNNOVIEWARTS,
					));
// echo ' err bpos='.$bpos;
					ob_start();
//					$cpgtpl->set_filenames(array('errbody' => 'pronews/article/'.$pnsettings['template']));
					$cpgtpl->set_filenames(array('errbody' => 'pronews/ctrblk_'.(($bpos == 'd') ? 'dn' : 'up').'.html'));
					$cpgtpl->display('errbody');
					$content = ob_get_clean();
				}
// Non-headline version
			} elseif ($bsets['type'] == 'randomctr' || $bsets['type'] == 'latestctr' || $bsets['type'] == 'oldestctr') {
				$sql = 'SELECT SQL_CALC_FOUND_ROWS a.id aid, a.*, c.id cid, c.sequence, c.title ctitle, c.description cdescription, c.icon icon, c.forum_id cforum_id, s.id sid, s.sequence, s.title stitle, s.description sdescription, s.view view, s.admin sadmin, s.forum_id sforum_id, template, usrfld0, usrfld1, usrfld2, usrfld3, usrfld4, usrfld5, usrfld6, usrfld7, usrfld8, usrfld9, usrfldttl, keyusrfld';
				$sql .= ' FROM '.$prefix.'_pronews_articles as a';
				$sql .= ', '.$prefix.'_pronews_cats as c';
				$sql .= ', '.$prefix.'_pronews_sections as s';

				if ($pnsettings['actv_sort_ord'] == '1') {
					$sql .= ', '.$prefix.'_pronews_schedule as h';
				}

				$sql .= ' WHERE a.catid=c.id AND c.sid=s.id AND s.id!="0"';

				if ($pnsettings['actv_sort_ord'] == '1') {
					$sql .= ' AND a.id=h.id';
				}

				$sql .= ' AND a.approved="1" AND a.active="1"';
				$sql .= ($multilingual ? " AND (alanguage='$currentlang' OR alanguage='')" : '');
				$sql .= ' AND display<>"2"';		// comment out this line, and uncomment lines below to restrict output to Home page based on Section settings
//				$is_home = $home || (isset($_GET['mode']) ? Fix_Quotes($_GET['mode'],1) : '');
//				$homeval = ($is_home) ? (($home) ? ' s.in_home<>"0"' : ' s.in_home="1"') : '';
//				$sql .= ($is_home ? ' AND display<>"0"'.((in_array(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), (array)$domain) ? '  AND ('.$homeval.' OR s.in_home="'.array_search(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), $domain).'")' : ' AND '.$homeval)) : ' AND display<>"2"');
				if (!can_admin($pn_module_name)) {
					$member_a_group = "0";
					if (isset($userinfo['_mem_of_groups'])) {
						foreach ($userinfo['_mem_of_groups'] as $id => $name) {
							if (!empty($name)) {
								$member_a_group = "1";
							}
						}
					}
					if (!is_user()) {
						$sql .= ' AND (s.view=0 OR s.view=3)';
					} else if ($member_a_group) {
						$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((s.view<4 AND (s.view=0 OR s.view=1)) OR (s.view>3 AND s.view-3=g.group_id)))';
					} else {
						$sql .= ' AND (s.view=0 OR s.view=1)';
					}
				}

				if ($pnsettings['actv_sort_ord'] == '1') {
					$sort_key = 'h.dttime';
				} else {
					$sort_key = 'posttime';
				}

				if ($bsets['section'] == 'ALL' && $bsets['category'] == '') {
					if ($bsets['type'] == 'randomctr') {
						$sql .= ' AND a.id >= (SELECT FLOOR(MAX(id) * RAND()) FROM '.$prefix.'_pronews_articles) ORDER BY a.id';
					} elseif ($bsets['type'] == 'oldestctr') {
						$sql .= ' ORDER BY display_order ASC, '.$sort_key.' ASC';
					} else {
						if ($pnsettings['display_by'] == '0') {
							$sql .= ' ORDER BY display_order DESC, '.$sort_key.' DESC';
						} elseif ($pnsettings['display_by'] == '1')  {
							$sql .= ' ORDER BY s.sequence ASC, display_order DESC, '.$sort_key.' DESC';
						} else {
							$sql .= ' ORDER BY s.sequence ASC, c.sequence ASC, display_order DESC, '.$sort_key.' DESC';
						}
					}
				} else {
					if ($bsets['category'] != '') {
						if (strpos($bsets['category'], ',') === FALSE) {
							$sql .= ' AND c.id="'.$bsets['category'].'"';
						} else {
							$sql .= ' AND c.id IN ('.$bsets['category'].')';
						}
					} elseif ($bsets['section'] != 'ALL' && $bsets['section'] != '0') {
						$sql .= ' AND c.sid="'.$bsets['section'].'"';
					}
					if ($bsets['type'] == 'randomctr') {
						$sql .= ' AND a.id >= (SELECT FLOOR(MAX(id) * RAND()) FROM '.$prefix.'_pronews_articles) ORDER BY a.id';
					} elseif ($bsets['type'] == 'oldestctr') {
						$sql .= ' ORDER BY display_order ASC, '.$sort_key.' ASC';
					} else {
						if ($pnsettings['display_by'] == '0') {
							$sql .= ' ORDER BY display_order DESC, '.$sort_key.' DESC';
						} elseif ($pnsettings['display_by'] == '1')  {
							$sql .= ' ORDER BY s.sequence ASC, display_order DESC, '.$sort_key.' DESC';
						} else {
							$sql .= ' ORDER BY s.sequence ASC, c.sequence ASC, display_order DESC, '.$sort_key.' DESC';
						}
					}
				}
				$sql .= ' LIMIT '.$bsets['num'];
				$result = $db->sql_query($sql);
				$list = $db->sql_fetchrowset($result);
				$result = $db->sql_query('SELECT FOUND_ROWS()');
				$total_rows = $db->sql_fetchrowset($result);
				$db->sql_freeresult($result);
				if (isset($list) && $list != '' && count($list) > '0') {
					require_once('includes/nbbcode.php');
					$last_sec = ''; $last_cat = '';
					$i = 0;
					foreach ($list as $key => $row) {
						$url_text = ($pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace('&amp;','',($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title']) : ($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title']))))) : '');
						if (($row['view'] == '0') || (can_admin($pn_module_name)) || (is_user() && (($row['view'] == '1') || (($row['view'] > 3) && (isset($userinfo['_mem_of_groups'][$row['view'] - 3])))))) {
							if (($row['image'] != '') && ($row['image'] != '0')) {
								$imagesize = getimagesize($pnsettings['imgpath'].'/'.$row['image']);  // fitted window - layingback 061119
								$imagesizeX = $imagesize[0] + 16; $imagesizeY = $imagesize[1] + 16;
								if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
									$thumbimage = $pnsettings['imgpath'].'/thumb_'.$row['image'];
									$display_image = '<a href="'.$pnsettings['imgpath'].'/'.$row['image'].'" target="pn'.uniqid(rand()).'" onclick="PN_openBrWindow(\''.$BASEHREF.$pnsettings['imgpath'].'/'.$row['image'].'\',\'' . uniqid(rand()) . '\',\'resizable=yes,scrollbars=yes,width='.$imagesizeX.',height='.$imagesizeY.',left=0,top=0\');return false;"><img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" /></a>';
								} else {
									$thumbimage = $pnsettings['imgpath'].'/'.$row['image'];
									$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" />';
								}														  // Check if thumb exists before linking - layingback 061122
								$iconimage = $pnsettings['imgpath'].'/icon_'.$row['image'];
							} elseif ($pnsettings['show_noimage'] != '0' && file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $row['stitle'])).'/imageholder.png')) {
								$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $row['stitle'])).'/imageholder.png';
								$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="" />';
								$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $row['stitle'])).'/imageholdermini.png';
							} elseif ($pnsettings['show_noimage'] != '0' && file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png')) {
								$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png';
								$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="" />';
								$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholdermini.png';
							} else {
								$display_image = '';
								$thumbimage = '';
								$iconimage = '';
							}
							if ($row['intro'] == '') {
								if(strlen($row['content']) > $pnsettings['introlen']) {
//									$text = substr_replace($row['content'],' ...',$pnsettings['introlen']);
									$text = substr_replace($row['content'],'',$pnsettings['introlen']);
									$morelink = '1';
								} else {
									$text = $row['content'];
									$morelink = '0';
								}
							} else {
								$text = $row['intro'];
								if ($row['content'] != '' || $row['user_fld_'.$row['keyusrfld']] !='' || $row['album_id'] != '0' || $row['image2'] != '' || $row['associated']) {$morelink = '1';} else {$morelink = '0';}
							}
							if (can_admin($pn_module_name)) {
								$canedit = "2";
								$editlink = adminlink('&amp;mode=add&amp;do=edit&amp;id=').$row['aid'];
								$editlabel = ($morelink == '1') ? _PNSECADMIN : '<a href="'.adminlink("&amp;mode=list&amp;do=sort&amp;cat=".$row['catid']).'">'._PNSECADMIN.'</a>';       // inline Edit - layingback 061201
							} elseif (($row['sadmin'] == '0') || (($row['sadmin'] == '1') && (is_user())) || (($row['sadmin'] > '3') && (is_user()) && (in_group($row['sadmin']-3)) && ($row['postby'] == $userinfo['username']))) {
								$canedit = $row['sadmin'];
								$editlink = getlink("$pn_module_name&amp;mode=submit&amp;do=edit&amp;id=".$row['aid']);
								$editlabel = _PNEDITARTICLE;     // User as member of authorised group edit - layingback 061201
							} else {
								$canedit = '';
							}
// echo '<br />i='.$i.' first='.$row['stitle'].' prev='.$last_sec.' rslt='.($i == 0 || ($bsets['section'] != 'ALL' && $row['stitle'] != $last_sec) ? '1' : '0');
// echo '<br />i='.$i.' len='.sizeof($list).' last='.$row['sid'].' next='.$list[$key+1]['sid'].' rslt='.($i+1 >= sizeof($list) || ($bsets['section'] != 'ALL' && $row['sid'] != $list[$key+1]['sid']) ? '1' : '0');
							$cpgtpl->assign_block_vars('cblkhome', array(
								'G_CBLK_UP' => (($bpos == 'd') ? '' : '1'),
//								'G_SECBRK' => (($pnsettings['display_by'] == '1' || $pnsettings['display_by'] == '2') && ($row['stitle'] != $last_sec)) ? '1' : '0',
								'G_SECBRK' => $i == 0 ? '1' : '',
								'S_SECBRK' => ProNews::getsctrnslt('_PN_SECTITLE_', $row['stitle'], $row['sid']),
								'U_SECDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_SECDESC_', $row['sdescription'], $row['sid']), 1, true)),
//								'G_CATBRK' => ($pnsettings['display_by'] == '2' && $row['ctitle'] != $last_cat) ? '1' : '0',
								'G_CATBRK' => '1',
								'S_CATBRK' => ProNews::getsctrnslt('_PN_CATTITLE_', $row['ctitle'], $row['catid']),
								'U_CATDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_CATDESC_', $row['cdescription'], $row['catid']), 1, true)),
								'G_FIRSTART' => ($i == 0 || ($bsets['section'] != 'ALL' && $row['stitle'] != $last_sec)) ? '1' : '0',
								'G_LASTART' => ($i+1 >= sizeof($list) || $bsets['section'] != 'ALL' && $row['sid'] != $list[$key+1]['sid']) ? '1' : '0',
								'S_INTRO' => decode_bb_all($row['intro'], 1, true),  // true param added for images - layingback
								'S_TITLE' => $row['title'],
								'S_ICON' => ($row['icon'] != '') ? $row['icon'] : 'clearpixel.gif',
								'T_ICON' => $row['ctitle'],
								'L_POSTBY' => _PNPOSTBY,
								'S_POSTBY' => $row['postby'],
								'T_POSTBY' => getlink("Your_Account&amp;profile=".$row['postby']),
								'L_POSTON' => _PNPOSTON,
								'S_POSTTIME' => ProNews::create_date(false, $row['posttime']),
								'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
								'S_CATEGORY' => $row['catid'],
								'S_CATLINK' => getlink("$pn_module_name&amp;cid=".$row['catid']),
								'S_IMAGE' => ($row['image'] == '' && $pnsettings['show_noimage'] == '0') ? '' : $display_image,
								'T_THUMBIMAGE' => $thumbimage,
								'T_ICONIMAGE' => $iconimage,
								'T_CAP' => $row['caption'],
								'S_USER_FLD_0' => (!$row['user_fld_0']) ? '' : ($row['usrfld0']) ? $row['usrfld0'] : _PNUSRFLD0,
								'T_USER_FLD_0' => $row['user_fld_0'],
								'S_USER_FLD_1' => (!$row['user_fld_1']) ? '' : ($row['usrfld1']) ? $row['usrfld1'] : _PNUSRFLD1,
								'T_USER_FLD_1' => $row['user_fld_1'],
								'S_USER_FLD_2' => (!$row['user_fld_2']) ? '' : ($row['usrfld2']) ? $row['usrfld2'] : _PNUSRFLD2,
								'T_USER_FLD_2' => $row['user_fld_2'],
								'S_USER_FLD_3' => (!$row['user_fld_3']) ? '' : ($row['usrfld3']) ? $row['usrfld3'] : _PNUSRFLD3,
								'T_USER_FLD_3' => $row['user_fld_3'],
								'S_USER_FLD_4' => (!$row['user_fld_4']) ? '' : ($row['usrfld4']) ? $row['usrfld4'] : _PNUSRFLD4,
								'T_USER_FLD_4' => $row['user_fld_4'],
								'U_DISCUSS' => getlink("$pn_module_name&amp;discuss=".$row['aid']),
								'S_DISCUSS' => ($row['topic_id'] ? _PNDISCUSSION :_PNDISCUSS),
								'G_DISCUSS' => (!($row['sforum_id'] == '0' && $row['cforum_id'] == '0') && $row['cforum_id'] != -1 && ($row['allow_comment'] == '1') && ($pnsettings['comments'] == '1')) ? '1' : '',
								'U_MORELINK' => getlink("$pn_module_name&amp;aid=".$row['aid'].$url_text),
								'S_MORELINK' => _PNMORE,
								'G_MORELINK' => ($morelink == '1') ? '1' : '',
								'U_CANADMIN' => $editlink,
								'S_CANADMIN' => _PNEDIT,
								'T_CANADMIN' => $editlabel,
								'G_CANADMIN' => ($canedit != '') ? '1' : '',
								'G_ICONS' => ($pnsettings['show_icons'] == '1') ? '1' : '',
								'G_MORE_ARTS' => $i+1 == count($list) && $total_rows['0']['FOUND_ROWS()'] > count($list) ? '1' : ''
							));
							$i++;
// if ($bsets['type'] == 'latestctr' && $i==1) {echo '<br />cpgtpl=<b>'.$row['title'].'</b>'.print_r($cpgtpl);}
							$last_sec = $row['stitle']; $last_cat = $row['ctitle'];
						}
					}
// echo ' ok 2 bpos='.$bpos;
// echo '<br />i='.$i;
// echo '<br />cpgtpl='.$tplt.' art_tpl='.$row['template'];
					ob_start();
					if ($use_tmpl) {
						$tplt = 'pronews/article/'.($row['template'] != '' ? $row['template'] : $pnsettings['template']);
					} else {
						$cbtplt = 'ctrblk_'.(($bpos == 'd') ? 'dn' : 'up').'.html';
						if (file_exists('themes/'.$CPG_SESS['theme'].'/template/pronews/article/'.$cbtplt)) {
							$tplt = 'pronews/article/'.$cbtplt;
						} else {
							$tplt = 'pronews/'.$cbtplt;
						}
					}
// echo '<br />cpgtpl='.$tplt;
					$cpgtpl->set_filenames(array('blkbody' => $tplt));
// echo '<br />cpgtpl='.print_r($cpgtpl);
					$cpgtpl->display('blkbody', false);
					$content = ob_get_clean();
// echo '<br />content='.$content;
					$cpgtpl->unset_block('cblkhome');
				} else {
					$cpgtpl->assign_block_vars('cblkempty', array(
						'S_MSG' => _PNNOVIEWARTS,
					));
// echo ' err 2 bpos='.$bpos;
					ob_start();
//					$cpgtpl->set_filenames(array('errbody' => 'pronews/article/'.$pnsettings['template']));
					$cpgtpl->set_filenames(array('errbody' => 'pronews/ctrblk_'.(($bpos == 'd') ? 'dn' : 'up').'.html'));
					$cpgtpl->display('errbody');
					$content = ob_get_clean();
				}
			} else { $content = 'Invalid Request Type'; }
		} else {$content = '<center><br />! Block Not Initialised !<br /><br />Install Pro_News Blocks ONLY from Administration > Pro_News > Blocks</center>';}
		return $content;
	}

	function article($aid,$page='') {
		global $board_config, $BASEHREF, $db, $prefix, $cpgtpl, $gblsettings, $pnsettings, $pagetitle, $CPG_SESS, $module_name, $userinfo, $multilingual,
		 $currentlang, $Blocks, $home, $curr_discuss, $curr_sec;
		if ($home && $pnsettings['clrblks_hm'] != '0' && ($gblsettings['Version_Num'] >= "9.2.1")) {
// Remove the lines below - by adding // in columns 1-2 - if you do NOT want the left, center up/down,  and/or right column blocks disabled when Pro_News Article is displayed on Home page
			$Blocks->l=-1;
			$Blocks->r=-1;
			$Blocks->c=-1;
			$Blocks->d=-1;
// end of left, center up/down, and right block disable code
		}
		$album_order = array(0=>'',1=>'title',2=>'title',3=>'filename',4=>'filename',5=>'ctime',6=>'ctime',7=>'pic_rating',8=>'pic_rating');
		$rsymbol = '&hearts;';
		if ($pnsettings['lmt_fulart'] && !is_user() && !is_admin()) {
			cpg_error ('<br /><br /><strong>'._RESTRICTEDAREA.'</strong><br /><br />'._MODULEUSERS2, 401);
		}

		$member_a_group = "0";
		if (isset($userinfo['_mem_of_groups'])) {
			foreach ($userinfo['_mem_of_groups'] as $id => $name) {
				if (!empty($name)) {
					$member_a_group = "1";
					break;
				}
			}
		}
		$sql = 'SELECT a.*, a.id aid, c.icon, c.id cid, c.title ctitle, c.description cdescription, s.id sid, c.forum_id cforum_id, s.admin sadmin, s.view view, s.title stitle, s.description sdescription, s.forum_id sforum_id, template, usrfld0, usrfld1, usrfld2, usrfld3, usrfld4, usrfld5, usrfld6, usrfld7, usrfld8, usrfld9, usrfldttl, keyusrfld';

		if ($pnsettings['topic_lnk'] > 0) {
			$sql .= ' , t.topicimage, t.topictext';
		}

		$sql .= ' FROM '.$prefix.'_pronews_articles as a';
		$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
		$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';

		if ($pnsettings['topic_lnk'] > 0) {
			$sql .= ' LEFT JOIN '.$prefix.'_topics as t ON a.df_topic=t.topicid';
		}

		if ($aid == '') {
			if ($pnsettings['actv_sort_ord'] == '1') {
				$sql .= ' LEFT JOIN '.$prefix.'_pronews_schedule as h ON a.id=h.id AND CASE s.art_ord';
				$sql .= ' WHEN "9" THEN h.newstate="0" WHEN "10" THEN h.newstate="1" WHEN "11" THEN h.newstate="0" WHEN "12" THEN h.newstate="1" END';
			}
			$sql .= ' WHERE sid!="0" AND s.in_home="2"';
//			$sql .= ($multilingual) ? ' AND (alanguage="" OR alanguage="'.$currentlang.'")' : '';	// redundant?
		} else {
			$sql .= ' WHERE a.id='.$aid;
		}
		$sql .= ($multilingual ? " AND (alanguage='$currentlang' OR alanguage='')" : '');
		$uap_ok = isset($_GET['uap']) && $_GET['uap'] != '' ? gmtime() - intval(strrev($_GET['uap'])) : '0';
		if (can_admin($module_name) || ProNews::in_group_list($pnsettings['mod_grp'])) {
			if (!can_admin($module_name) && $uap_ok > 300) {
				cpg_error(_SEC_ERROR, _ERROR_BAD_LINK);
			}
		} else {
			$sql .= ' AND approved="1"';
		}
		if (!can_admin($module_name)) {
			$sql .= ' AND active="1"';
			if (!is_user()) {
				$sql .= ' AND (s.view=0 OR s.view=3)';
			} else if ($member_a_group) {
				$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((s.view<4 AND (s.view=0 OR s.view=1)) OR (s.view>3 AND s.view-3=g.group_id)))';
			} else {
				$sql .= ' AND (s.view=0 OR s.view=1)';
			}
		}
		if ($aid == '') {
			$artsortkey = $pnsettings['art_ordr'] / '2';
			$artsortord = ($pnsettings['art_ordr'] % '2')  ? 'DESC' : 'ASC';
			if ($artsortkey < 1) {
				$artsortfld = 'posttime';
			} elseif ($artsortkey < 2) {
				$artsortfld = 'a.title';
			} elseif ($artsortkey < 3) {
				$artsortfld = 'ratings';
			} else {
				$artsortfld = 'a.counter';
			}
			$sql .= ' ORDER BY display_order DESC, ';
			$sql .= ' CASE s.art_ord WHEN 0 THEN '.$artsortfld.' END '.$artsortord.',';
			$sql .= ' CASE s.art_ord WHEN 1 THEN posttime END ASC,';
			$sql .= ' CASE s.art_ord WHEN 2 THEN posttime END DESC,';
			$sql .= ' CASE s.art_ord WHEN 3 THEN a.title END ASC,';
			$sql .= ' CASE s.art_ord WHEN 4 THEN a.title END DESC,';
			$sql .= ' CASE s.art_ord WHEN 5 THEN ratings END ASC,';
			$sql .= ' CASE s.art_ord WHEN 6 THEN ratings END DESC,';
			$sql .= ' CASE s.art_ord WHEN 7 THEN a.counter END ASC,';
			$sql .= ' CASE s.art_ord WHEN 8 THEN a.counter END DESC';
			if ($pnsettings['actv_sort_ord'] == '1') {
				$sql .= ', CASE s.art_ord WHEN 9 THEN h.dttime END ASC,';
				$sql .= ' CASE s.art_ord WHEN 10 THEN h.dttime END DESC,';
				$sql .= ' CASE s.art_ord WHEN 11 THEN h.dttime END ASC,';
				$sql .= ' CASE s.art_ord WHEN 12 THEN h.dttime END DESC';
			}
			$sql .= ' LIMIT 1';
		}
		$row = $db->sql_fetchrow($db->sql_query($sql));
		if (isset($row) && $row != '') {
			if ($pnsettings['show_reads'] && $pnsettings['read_cnt']) {
				$db->sql_query('UPDATE '.$prefix.'_pronews_articles SET counter=counter+1 WHERE id='.$row['id']);
				$show_reads = '1';
			} else {
				$show_reads = '0';
			}
			require_once('includes/nbbcode.php');
			if (can_admin($module_name)) {
				$canedit = "2";
				$editlink = adminlink('&amp;mode=add&amp;do=edit&amp;id=').$row['id'];
				$editlabel = '<a href="'.adminlink("&amp;mode=list&amp;do=sort&amp;cat=".$row['catid']).'">'._PNSECADMIN.'</a>';
			} elseif (ProNews::in_group_list($pnsettings['mod_grp'])) {
				$canedit = "-1";
				$editlink = getlink("&amp;mode=submit&amp;do=edit&amp;id=".$row['id']);
				$editlabel = '<a href="'.getlink("&amp;aid=".$row['id']."&amp;mod=mod").'">'._PNMODERATE.'</a>';	// moderate
			} elseif
				(
					(
						($row['sadmin'] == 0) ||
						($row['sadmin'] == 3 && !is_user()) ||
						(($row['sadmin'] == 1) && (is_user()) || (($row['sadmin'] > 3) && (is_user()) && (in_group($row['sadmin']-3)) && ($row['postby'] == $userinfo['username'])))
					) &&
					($pnsettings['edit_time'] == 0 || time() < ($row['updttime'] ? $row['updttime'] : $row['posttime']) + ($pnsettings['edit_time'] * 60))
				) {
				$canedit = $row['sadmin'];
				$editlink = getlink("&amp;mode=submit&amp;do=edit&amp;id=".$row['id']);
				$editlabel = _PNEDITARTICLE;
			} else {$canedit = '';}
// echo 'o/l='.($pnsettings['edit_time'] == 0 || time() < ($row['updttime'] ? $row['updttime'] : $row['posttime']) + $pnsettings['edit_time']).' p-t='.$row['posttime'].' u-t='.$row['updttime'];
			$mod = isset($_POST['mod']) ? Fix_Quotes($_POST['mod']) : (isset($_GET['mod']) ? Fix_Quotes($_GET['mod']) : '');
// echo 'mod='.$mod.'aid='.$aid.'cid='.$row['cid'];
			if (isset($mod) && ProNews::in_group_list($pnsettings['mod_grp'])) {
				switch ($mod) {
					case 'app':
						ProNews::approve($aid, $row['cid']);		//	doesn't return
					break;

					case 'act':
						ProNews::activate($aid, $row['cid']);		//	doesn't return
					break;

					case 'mov':
						ProNews::move_art($aid);		//	doesn't return
					break;
				}
				$applink = ProNews::get_status('app', $row['id'], $row['approved']);
// echo 'applnk='.$applink;
				$actlink = ProNews::get_status('act', $row['id'], $row['active']);
// echo 'actlnk='.$actlink;
				$movlink = ProNews::seccat('cat2','',false,'',false,true);
			} else {
				$applink = $actlink = $movlink = '';
			}


			$target = 'pn'.uniqid(rand());
			if(($row['image'] != '') && ($row['image'] != '0')) {
				$imagesize = getimagesize($pnsettings['imgpath'].'/'.$row['image']);  // fitted window - layingback 061119
				$imagesizeX = $imagesize[0] + 16; $imagesizeY = $imagesize[1] + 16;
				if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
					$thumbimage = $pnsettings['imgpath'].'/thumb_'.$row['image'];
					$display_image = '<a href="'.$pnsettings['imgpath'].'/'.$row['image'].'" target="'.$target.'"
 onclick="PN_openBrWindow(\''.$BASEHREF.$pnsettings['imgpath'].'/'.$row['image'].'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;"><img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" /></a>';
				} else {
					$thumbimage = $pnsettings['imgpath'].'/'.$row['image'];  // Check if thumb exists before linking - layingback 061122
					$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" />';
				}
			} elseif ($pnsettings['show_noimage'] != '0' && file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $row['stitle'])).'/imageholder.png')) {
				$display_image = '<img class="pn_image" src="themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $row['stitle'])).'/imageholder.png" alt="'.$row['caption'].'" />';
			} elseif ($pnsettings['show_noimage'] != '0' && file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png')) {
				$display_image = '<img class="pn_image" src="themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png" alt="'.$row['caption'].'" />';
			} else {$display_image = '';}
			if(($row['image2'] != '') && ($row['image2'] != '0')) {
				$imagesize = getimagesize($pnsettings['imgpath'].'/'.$row['image2']);  // fitted window - layingback 061119
				$imagesizeX = $imagesize[0] + 16; $imagesizeY = $imagesize[1] + 16;
				if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
					$thumbimage = $pnsettings['imgpath'].'/thumb_'.$row['image2'];
					$display2_image = '<a href="'.$pnsettings['imgpath'].'/'.$row['image2'].'" target="'.$target.'"
 onclick="PN_openBrWindow(\''.$BASEHREF.$pnsettings['imgpath'].'/'.$row['image2'].'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;"><img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" /></a>';
				} else {
				   	$thumbimage = $pnsettings['imgpath'].'/'.$row['image2'];  // Check if thumb exists before linking - layingback 061122
					$display2_image = '<img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" />';
				}
			} else {$display2_image = '';}
			$numpics = '0';
			$lpic = '';
			$maxsizeX = '0';
			$maxsizeY = '0';
			if (($row['album_id'] != '') && ($row['album_cnt'] > '0')) {
				$sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM '.$prefix.'_cpg_pictures';
				$sql .= ' WHERE aid='.$row['album_id'];
				$asc_desc = ($row['album_seq'] & 1) ? 'ASC' : 'DESC';
				if ($row['album_seq'] != '0') {$sql .= ' ORDER BY '.$album_order[$row['album_seq']].' '.$asc_desc;}
				$sql .= ' LIMIT '.$row['album_cnt'];
				$list = $db->sql_fetchrowset($db->sql_query($sql));
				$result = $db->sql_query('SELECT FOUND_ROWS()');
				$total_rows = $db->sql_fetchrowset($result);
// print_r($total_rows); echo '<br />tot-rows='.$total_rows['0']['FOUND_ROWS()'];
				$db->sql_freeresult($result);
				if (($list) && ($list != "")) {
					foreach ($list as $key => $pic) {
						$fullsizepath = ($pic['remote_url'] != '' && preg_match("/(?:https?\:\/\/)?([^\.]+\.?[^\.\/]+\.[^\.\/]+[^\.]+)/", $pic['remote_url'], $matches)) ? 'http://'.$matches[1].'/' : $pic['filepath'];		// lb - cpg remote_url hack support
						$imagesizeX = $pic['pwidth'] + 16; $imagesizeY = $pic['pheight'] + 16;
						if ($pic['pwidth'] > $maxsizeX) { $maxsizeX = $pic['pwidth'];}
						if ($pic['pheight'] > $maxsizeY) { $maxsizeY = $pic['pheight'];}
						$thumb = str_replace("%2F", "/", rawurlencode($pic['filepath'].'thumb_'.$pic['filename']));
						$talbum[$key] = $pic['title'] != ' ' ? trim($pic['title']) : '&nbsp;';				// trim cos cpg adds trailing space!
						$calbum[$key] = ($pic['caption'] != '') ? $pic['caption'] : '&nbsp;';
						$palbum[$key] = '<a href="'.str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'" target="'.$target.'"
 onclick="PN_openBrWindow(\''.$BASEHREF.str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;"><img class="pn_image" src="'.$thumb.'" alt="'.$pic['title'].'" /></a>';
						$qalbum[$key] = str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename'])));
						$ralbum[$key] = str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'" target="'.$target.'"
 onclick="PN_openBrWindow(\''.$BASEHREF.str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;';
						$valbum[$key] = $thumb;
						$ualbum[$key] = '<img class="pn_image" src="'.str_replace("%2F", "/", rawurlencode($pic['filepath'].(file_exists($pic['filepath'].'normal_'.$pic['filename']) ? 'normal_' : '').$pic['filename'])).'" alt="" />';
						$galbum[$key] = '1';
						$lpic = $pic['pid'];		//track pid of last pic shown
						$numpics++;
					}
				}
			}
			$maxsizeX = ($maxsizeX == '0') ? $maxsizeX = '800' : $maxsizeX;
			$maxsizeY = ($maxsizeY == '0') ? $maxsizeY = '600' : $maxsizeY;
			$openLink = '<script type="text/javascript">
<!-- Script courtesy of http://www.web-source.net - Your Guide to Professional Web Site Design and Development
function load() {var load = window.open("'.getlink($module_name.'&mode=slide&id='.$row['id'].'&album='.$row['album_id'].'&pid='.$lpic.'&slideshow=5000","","scrollbars=no,menubar=no,height='.($maxsizeY + 72).',width='.($maxsizeX + 32).',resizable=yes,toolbar=no,location=no,status=no',false,true).'");}
// -->
</script>';
			$j = $numpics;
			while ($j <= '32') {
				$galbum[$j] = '';
				$palbum[$j] = '';
				$qalbum[$j] = '';
				$ralbum[$j] = '';
				$talbum[$j] = '&nbsp;';
				$calbum[$j] = '&nbsp;';
				$ualbum[$j] = '';
				$valbum[$j] = '';
				$j++;
			}
// echo '<br />Per_Gallery='.$pnsettings['per_gllry'].' Album_Id='.$row['album_id'].' Slide_Show='.$row['slide_show'].' Album_Cnt='.$row['album_cnt'].' sshow='.$row['slide_show'].' $lpic='.$lpic.' numpics='.$numpics.' totrows='.$total_rows['0']['FOUND_ROWS()'].' mpics='.(($pnsettings['per_gllry'] != 0 && $numpics > 0 && $total_rows['0']['FOUND_ROWS()'] > $numpics && $row['album_id'] != '0' && $row['slide_show'] > '1') ? '1' : '0');

			$show_comments = (!($row['sforum_id'] == '0' && $row['cforum_id'] == '0' && empty($row['topic_id'])) && $row['cforum_id'] != -1 && $row['allow_comment'] == '1' && $pnsettings['comments'] == '1') ? '1' : '';
			$row['topic_replies'] = '';
			if ($show_comments && $pnsettings['cnt_cmnts'] && $row['topic_id'] <> '') {
				$sql = 'SELECT topic_replies FROM '.$prefix.'_bbtopics';
				$sql .= ' WHERE topic_id='.$row['topic_id'];
				$sql .= ' LIMIT 1';
				$cmnts = $db->sql_fetchrow($db->sql_query($sql));
				if (($cmnts) && ($cmnts != "")) {
					$row['topic_replies'] = $cmnts['topic_replies'];
				}
			}


			// Begin of Showing Related Articles, modified by Masino Sinaga, June 22, 2009
			$assoc = '';
			$assoclst = '';
			if ($row['associated'] != '') {
				$member_a_group = "0";
				if (isset($userinfo['_mem_of_groups'])) {
					foreach ($userinfo['_mem_of_groups'] as $id => $name) {
						if (!empty($name)) {
							$member_a_group = "1";
						}
					}
				}
// echo ' assoc='.$row['associated'].' tok='.strtok($row['associated'],',');
				$inclcode = strtok($row['associated'], ',');
//				$row['associated'] = strtok('');			// reset to remainder of associated
				if ($inclcode < '0') {
					$inclcode = $inclcode * -1;
					$inclnum = $inclcode % 100;
					$inclcatsec = intval($inclcode / 100);
					$sql = 'SELECT a.id, a.title, s.title stitle, c.title ctitle, posttime, postby FROM '.$prefix.'_pronews_articles as a';
					$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
					$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
					if ($inclcatsec == '0')  {
						$sql .= ' WHERE catid='.$row['catid'];
					} elseif ($inclcatsec == '1') {
						$sql .= ' WHERE c.sid='.$row['sid'];
					} elseif ($inclcatsec == '2') {
						$sql .= ' WHERE  catid='.$row['catid'].' AND postby="'.$row['postby'].'"';
					} else {
						$sql .= ' WHERE c.sid='.$row['sid'].' AND postby="'.$row['postby'].'"';
					}
					$sql .= ' AND a.id!='.$row['id'];
					$sql .= ' AND a.approved="1" AND a.active="1"';
					$sql .= ($multilingual ? " AND (alanguage='$currentlang' OR alanguage='')" : '');

					if (!can_admin($module_name)) {
						if (!is_user()) {
							$sql .= ' AND (s.view=0 OR s.view=3)';
						} else if ($member_a_group) {
							$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((s.view<4 AND (s.view=0 OR s.view=1)) OR (s.view>3 AND s.view-3=g.group_id)))';
						} else {
							$sql .= ' AND (s.view=0 OR s.view=1)';
						}
					}

					$sql .= ' ORDER BY display_order DESC, posttime DESC';
					if ( $inclnum < '99') { $sql .= ' LIMIT '.$inclnum ; }
					$result = $db->sql_query($sql);
					while ($rowa = $db->sql_fetchrow($result)) {
						$datestory = formatDateTime($rowa['posttime'], _DATESTRING);
//						$gettitle = $rowa['title'];
//						if ($title != $gettitle) {
//							$title = $gettitle;
//						}
//						$assoclst .= '<div class="pn_relart"><a href="'.getlink("$module_name&amp;aid=".$rowa['id']).'" title="'.$datestory.'">'.$rowa['title'].'</a> <span class="pn_relartdate">'.$datestory.'</span></div>';
						$assoclst .= '<div class="pn_relart"><a href="'.getlink("$module_name&amp;aid=".$rowa['id'].($pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', ($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $rowa['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $rowa['ctitle'].'/' : '').$rowa['title']) : ($pnsettings['sec_in_url'] ? $rowa['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $rowa['ctitle'].'/' : '').$rowa['title'])))) : '')).'" title="'.$datestory.'">'.$rowa['title'].'</a></div>';
// echo '<br /> assoclst='.$assoclst;
					}
					$db->sql_freeresult($result);
				}

//				if (substr($row['associated'], -1) == '-') {
//					$row['associated'] = substr($row['associated'], 0, -1);
//				}
//				$row['associated'] = ereg_replace('-', ',', $row['associated']);
				if ($row['associated'] != '') {		// check for individual related articles
					$sql = 'SELECT a.id, a.title, s.title stitle, c.title ctitle, posttime, postby FROM '.$prefix.'_pronews_articles as a';
					$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
					$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
					$sql .= ' WHERE a.id IN ('.$row['associated'].')';
					$sql .= ' AND approved="1" AND active="1"';
					$sql .= ($multilingual ? " AND (alanguage='$currentlang' OR alanguage='')" : '');
					if (!can_admin($module_name)) {
						if (!is_user()) {
							$sql .= ' AND (s.view=0 OR s.view=3)';
						} else if ($member_a_group) {
							$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((s.view<4 AND (s.view=0 OR s.view=1)) OR (s.view>3 AND s.view-3=g.group_id)))';
						} else {
							$sql .= ' AND (s.view=0 OR s.view=1)';
						}
					}
					$result = $db->sql_query($sql);
					while ($rowa = $db->sql_fetchrow($result)) {
						$datestory = formatDateTime($rowa['posttime'], _DATESTRING);
//						$gettitle = $rowa['title'];
//						if ($title != $gettitle) {
//							$title = $gettitle;
//						}
						$assoc .= '<div class="pn_relart"><a href="'.getlink("$module_name&amp;aid=".$rowa['id'].($pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', ($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $rowa['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $rowa['ctitle'].'/' : '').$rowa['title']) : ($pnsettings['sec_in_url'] ? $rowa['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $rowa['ctitle'].'/' : '').$rowa['title'])))) : '')).'">'.$rowa['title'].'</a></div>';
					}
					$db->sql_freeresult($result);
				}
			}
//				if ($assoc != '') {
//					$cpgtpl->assign_vars(array(
//						'S_ASSOCARTICLE'  => _PNRELATEDART,
//						'S_ASSOCARTICLES' => $assoc
//					));
//				}
//			} else {
//				$cpgtpl->assign_vars(array('S_ASSOCARTICLES' => false));
			// End of Showing Related Articles, modified by Masino Sinaga, June 22, 2009

//	ALBUM KEY
//		G_PALBUM - 1 if entries are present, else 0
//		S_PALBUM - title as text
//		C_PALBUM - caption as text
//		T_PALBUM - thumb_ as clickable link to full image in popup window
//		I_PALBUM - filepath/filename of full image as text
//		A_PALBUM - filepath/filename of normal (400px) image with clickable link to full image - use in <a href="{newsarticle.A_PALBUM}">...</a>
//		U_PALBUM - normal (400px) image formatted as <img class= "pn_image" src="normal_..." alt="" >
//		V_PALBUM - filepath/filename of thumb image as text

			$curr_discuss = $row['topic_id'];
			$curr_sec = $row['sid'];
			$url_text = $pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace('&amp;','',($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title']) : ($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title']))))) : '';
			$cpgtpl->assign_block_vars('newsarticle', array(
				'S_SECBRK' => ProNews::getsctrnslt('_PN_SECTITLE_', $row['stitle'], $row['sid']),
				'U_SECDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_SECDESC_', $row['sdescription'], $row['sid']), 1, true)),
				'S_CATBRK' => ProNews::getsctrnslt('_PN_CATTITLE_', $row['ctitle'], $row['catid']),
				'U_CATDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_CATDESC_', $row['cdescription'], $row['catid']), 1, true)),
				'T_SECBRK' => getlink("&amp;sid=".$row['sid']),
				'T_CATBRK' => getlink("&amp;cid=".$row['cid']),
				'S_INTRO' => $ogintro = make_clickable(decode_bb_all($row['intro'], 1, true)),
				'S_CONTENT' => $ogcontent = make_clickable(decode_bb_all($row['content'], 2, true)),
				'S_ICON' => ($row['icon'] != '') ? $row['icon'] : 'clearpixel.gif',
				'T_ICON' => $row['ctitle'],
				'S_TITLE' => $row['title'],
				'S_ASSOCARTICLE' => _PNRELATEDART,		// Related Articles, modified by Masino Sinaga, June 22, 2009
				'S_ASSOCARTICLES' => $assoc,			// Related Articles, modified by Masino Sinaga, June 22, 2009
				'L_ASSOCARTICLE' => _PNLSTLNKS,
				'L_ASSOCARTICLES' => $assoclst,
				'L_POSTBY' => _PNPOSTBY,
				'S_POSTBY' => $row['postby'],
				'T_POSTBY' => getlink("Your_Account&amp;profile=".$row['postby']),
				'L_POSTON' => _PNPOSTON,
				'S_POSTTIME' => ProNews::create_date(false, $row['posttime']),
				'L_UPDTBY' => _PNUPDTBY,
				'S_UPDTBY' => $row['updtby'],
				'T_UPDTBY' => getlink("Your_Account&amp;profile=".$row['updtby']),
				'S_UPDTTIME' => ProNews::create_date(false, $row['updttime']),
				'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
				'S_CATEGORY' => $row['catid'],
				'S_IMAGE' => ($row['image'] == '' && $pnsettings['show_noimage'] == '0') ? '' : $display_image,
				'T_CAP' => ($row['caption'] == '') ? '&nbsp;' : $row['caption'],
				'S_FULIMAGE' => $ogimage = $row['image'] ? $pnsettings['imgpath'].'/'.$row['image'] : '',
				'S_IMGIMAGE' => file_exists($pnsettings['imgpath'].'/thumb_'.$row['image']) ? $pnsettings['imgpath'].'/thumb_'.$row['image'] : $pnsettings['imgpath'].'/'.$row['image'],
				'S_THBIMAGE' => file_exists($pnsettings['imgpath'].'/icon_'.$row['image']) ? $pnsettings['imgpath'].'/icon_'.$row['image'] : $pnsettings['imgpath'].'/'.$row['image'],
				'S_IMAGE2' => $display2_image,
				'T_CAP2' => ($row['caption2'] == '') ? '&nbsp;' : $row['caption2'],
				'S_FULIMAGE2' => $pnsettings['imgpath'].'/'.$row['image2'],
				'S_IMGIMAGE2' => file_exists($pnsettings['imgpath'].'/thumb_'.$row['image2']) ? $pnsettings['imgpath'].'/thumb_'.$row['image2'] : $pnsettings['imgpath'].'/'.$row['image2'],
				'S_THBIMAGE2' => file_exists($pnsettings['imgpath'].'/icon_'.$row['image2']) ? $pnsettings['imgpath'].'/icon_'.$row['image2'] : $pnsettings['imgpath'].'/'.$row['image2'],
				'G_PALBUM_0' => ($galbum['0']) ? '1' : '',
				'S_PALBUM_0' => $talbum['0'],
				'C_PALBUM_0' => $calbum['0'],
				'T_PALBUM_0' => ($row['album_cnt'] == '1' && ($row['slide_show'] == '1' || $row['slide_show'] == '3')) ? '<a href="javascript:load()"><img class="pn_image" src="'.$thumb.'" alt="'.$row['caption'].'" /></a>' : $palbum['0'],
				'I_PALBUM_0' => $qalbum['0'],
				'A_PALBUM_0' => $ralbum['0'],
				'U_PALBUM_0' => $ualbum['0'],
				'V_PALBUM_0' => $valbum['0'],
				'T_PALBUM_1' => $palbum['1'],
				'G_PALBUM_1' => ($galbum['1']) ? '1' : '',
				'S_PALBUM_1' => $talbum['1'],
				'C_PALBUM_1' => $calbum['1'],
				'T_PALBUM_1' => $palbum['1'],
				'I_PALBUM_1' => $qalbum['1'],
				'A_PALBUM_1' => $ralbum['1'],
				'U_PALBUM_1' => $ualbum['1'],
				'V_PALBUM_1' => $valbum['1'],
				'G_PALBUM_2' => ($galbum['2']) ? '1' : '',
				'S_PALBUM_2' => $talbum['2'],
				'C_PALBUM_2' => $calbum['2'],
				'T_PALBUM_2' => $palbum['2'],
				'I_PALBUM_2' => $qalbum['2'],
				'A_PALBUM_2' => $ralbum['2'],
				'U_PALBUM_2' => $ualbum['2'],
				'V_PALBUM_2' => $valbum['2'],
				'G_PALBUM_3' => ($galbum['3']) ? '1' : '',
				'S_PALBUM_3' => $talbum['3'],
				'C_PALBUM_3' => $calbum['3'],
				'T_PALBUM_3' => $palbum['3'],
				'I_PALBUM_3' => $qalbum['3'],
				'A_PALBUM_3' => $ralbum['3'],
				'U_PALBUM_3' => $ualbum['3'],
				'V_PALBUM_3' => $valbum['3'],
				'G_PALBUM_4' => ($galbum['4']) ? '1' : '',
				'S_PALBUM_4' => $talbum['4'],
				'C_PALBUM_4' => $calbum['4'],
				'T_PALBUM_4' => $palbum['4'],
				'I_PALBUM_4' => $qalbum['4'],
				'A_PALBUM_4' => $ralbum['4'],
				'U_PALBUM_4' => $ualbum['4'],
				'V_PALBUM_4' => $valbum['4'],
				'G_PALBUM_5' => ($galbum['5']) ? '1' : '',
				'S_PALBUM_5' => $talbum['5'],
				'C_PALBUM_5' => $calbum['5'],
				'T_PALBUM_5' => $palbum['5'],
				'I_PALBUM_5' => $qalbum['5'],
				'A_PALBUM_5' => $ralbum['5'],
				'U_PALBUM_5' => $ualbum['5'],
				'V_PALBUM_5' => $valbum['5'],
				'G_PALBUM_6' => ($galbum['6']) ? '1' : '',
				'S_PALBUM_6' => $talbum['6'],
				'C_PALBUM_6' => $calbum['6'],
				'T_PALBUM_6' => $palbum['6'],
				'I_PALBUM_6' => $qalbum['6'],
				'A_PALBUM_6' => $ralbum['6'],
				'U_PALBUM_6' => $ualbum['6'],
				'V_PALBUM_6' => $valbum['6'],
				'G_PALBUM_7' => ($galbum['7']) ? '1' : '',
				'S_PALBUM_7' => $talbum['7'],
				'C_PALBUM_7' => $calbum['7'],
				'T_PALBUM_7' => $palbum['7'],
				'I_PALBUM_7' => $qalbum['7'],
				'A_PALBUM_7' => $ralbum['7'],
				'U_PALBUM_7' => $ualbum['7'],
				'V_PALBUM_7' => $valbum['7'],
				'G_PALBUM_8' => ($galbum['8']) ? '1' : '',
				'S_PALBUM_8' => $talbum['8'],
				'C_PALBUM_8' => $calbum['8'],
				'T_PALBUM_8' => $palbum['8'],
				'I_PALBUM_8' => $qalbum['8'],
				'A_PALBUM_8' => $ralbum['8'],
				'U_PALBUM_8' => $ualbum['8'],
				'V_PALBUM_8' => $valbum['8'],
				'G_PALBUM_9' => ($galbum['9']) ? '1' : '',
				'S_PALBUM_9' => $talbum['9'],
				'C_PALBUM_9' => $calbum['9'],
				'T_PALBUM_9' => $palbum['9'],
				'I_PALBUM_9' => $qalbum['9'],
				'A_PALBUM_9' => $ralbum['9'],
				'U_PALBUM_9' => $ualbum['9'],
				'V_PALBUM_9' => $valbum['9'],
				'G_PALBUM_10' => ($galbum['10']) ? '1' : '',
				'S_PALBUM_10' => $talbum['10'],
				'C_PALBUM_10' => $calbum['10'],
				'T_PALBUM_10' => $palbum['10'],
				'I_PALBUM_10' => $qalbum['10'],
				'A_PALBUM_10' => $ralbum['10'],
				'U_PALBUM_10' => $ualbum['10'],
				'V_PALBUM_10' => $valbum['10'],
				'T_PALBUM_10' => $palbum['10'],
				'G_PALBUM_11' => ($galbum['11']) ? '1' : '',
				'S_PALBUM_11' => $talbum['11'],
				'C_PALBUM_11' => $calbum['11'],
				'T_PALBUM_11' => $palbum['11'],
				'I_PALBUM_11' => $qalbum['11'],
				'A_PALBUM_11' => $ralbum['11'],
				'U_PALBUM_11' => $ualbum['11'],
				'V_PALBUM_11' => $valbum['11'],
				'G_PALBUM_12' => ($galbum['12']) ? '1' : '',
				'S_PALBUM_12' => $talbum['12'],
				'C_PALBUM_12' => $calbum['12'],
				'T_PALBUM_12' => $palbum['12'],
				'I_PALBUM_12' => $qalbum['12'],
				'A_PALBUM_12' => $ralbum['12'],
				'U_PALBUM_12' => $ualbum['12'],
				'V_PALBUM_12' => $valbum['12'],
				'G_PALBUM_13' => ($galbum['13']) ? '1' : '',
				'S_PALBUM_13' => $talbum['13'],
				'C_PALBUM_13' => $calbum['13'],
				'T_PALBUM_13' => $palbum['13'],
				'I_PALBUM_13' => $qalbum['13'],
				'A_PALBUM_13' => $ralbum['13'],
				'U_PALBUM_13' => $ualbum['13'],
				'V_PALBUM_13' => $valbum['13'],
				'G_PALBUM_14' => ($galbum['14']) ? '1' : '',
				'S_PALBUM_14' => $talbum['14'],
				'C_PALBUM_14' => $calbum['14'],
				'T_PALBUM_14' => $palbum['14'],
				'I_PALBUM_14' => $qalbum['14'],
				'A_PALBUM_14' => $ralbum['14'],
				'U_PALBUM_14' => $ualbum['14'],
				'V_PALBUM_14' => $valbum['14'],
				'G_PALBUM_15' => ($galbum['15']) ? '1' : '',
				'S_PALBUM_15' => $talbum['15'],
				'C_PALBUM_15' => $calbum['15'],
				'T_PALBUM_15' => $palbum['15'],
				'I_PALBUM_15' => $qalbum['15'],
				'A_PALBUM_15' => $ralbum['15'],
				'U_PALBUM_15' => $ualbum['15'],
				'V_PALBUM_15' => $valbum['15'],
				'G_PALBUM_16' => ($galbum['16']) ? '1' : '',
				'S_PALBUM_16' => $talbum['16'],
				'C_PALBUM_16' => $calbum['16'],
				'T_PALBUM_16' => $palbum['16'],
				'I_PALBUM_16' => $qalbum['16'],
				'A_PALBUM_16' => $ralbum['16'],
				'U_PALBUM_16' => $ualbum['16'],
				'V_PALBUM_16' => $valbum['16'],
				'G_PALBUM_17' => ($galbum['17']) ? '1' : '',
				'S_PALBUM_17' => $talbum['17'],
				'C_PALBUM_17' => $calbum['17'],
				'T_PALBUM_17' => $palbum['17'],
				'I_PALBUM_17' => $qalbum['17'],
				'A_PALBUM_17' => $ralbum['17'],
				'U_PALBUM_17' => $ualbum['17'],
				'V_PALBUM_17' => $valbum['17'],
				'G_PALBUM_18' => ($galbum['18']) ? '1' : '',
				'S_PALBUM_18' => $talbum['18'],
				'C_PALBUM_18' => $calbum['18'],
				'T_PALBUM_18' => $palbum['18'],
				'I_PALBUM_18' => $qalbum['18'],
				'A_PALBUM_18' => $ralbum['18'],
				'U_PALBUM_18' => $ualbum['18'],
				'V_PALBUM_18' => $valbum['18'],
				'G_PALBUM_19' => ($galbum['19']) ? '1' : '',
				'S_PALBUM_19' => $talbum['19'],
				'C_PALBUM_19' => $calbum['19'],
				'T_PALBUM_19' => $palbum['19'],
				'I_PALBUM_19' => $qalbum['19'],
				'A_PALBUM_19' => $ralbum['19'],
				'U_PALBUM_19' => $ualbum['19'],
				'V_PALBUM_19' => $valbum['19'],
				'G_PALBUM_20' => ($galbum['20']) ? '1' : '',
				'S_PALBUM_20' => $talbum['20'],
				'C_PALBUM_20' => $calbum['20'],
				'T_PALBUM_20' => $palbum['20'],
				'I_PALBUM_20' => $qalbum['20'],
				'A_PALBUM_20' => $ralbum['20'],
				'U_PALBUM_20' => $ualbum['20'],
				'V_PALBUM_20' => $valbum['20'],
				'T_PALBUM_21' => $palbum['21'],
				'G_PALBUM_21' => ($galbum['21']) ? '1' : '',
				'S_PALBUM_21' => $talbum['21'],
				'C_PALBUM_21' => $calbum['21'],
				'T_PALBUM_21' => $palbum['21'],
				'I_PALBUM_21' => $qalbum['21'],
				'A_PALBUM_21' => $ralbum['21'],
				'U_PALBUM_21' => $ualbum['21'],
				'V_PALBUM_21' => $valbum['21'],
				'G_PALBUM_22' => ($galbum['22']) ? '1' : '',
				'S_PALBUM_22' => $talbum['22'],
				'C_PALBUM_22' => $calbum['22'],
				'T_PALBUM_22' => $palbum['22'],
				'I_PALBUM_22' => $qalbum['22'],
				'A_PALBUM_22' => $ralbum['22'],
				'U_PALBUM_22' => $ualbum['22'],
				'V_PALBUM_22' => $valbum['22'],
				'G_PALBUM_23' => ($galbum['23']) ? '1' : '',
				'S_PALBUM_23' => $talbum['23'],
				'C_PALBUM_23' => $calbum['23'],
				'T_PALBUM_23' => $palbum['23'],
				'I_PALBUM_23' => $qalbum['23'],
				'A_PALBUM_23' => $ralbum['23'],
				'U_PALBUM_23' => $ualbum['23'],
				'V_PALBUM_23' => $valbum['23'],
				'G_PALBUM_24' => ($galbum['24']) ? '1' : '',
				'S_PALBUM_24' => $talbum['24'],
				'C_PALBUM_24' => $calbum['24'],
				'T_PALBUM_24' => $palbum['24'],
				'I_PALBUM_24' => $qalbum['24'],
				'A_PALBUM_24' => $ralbum['24'],
				'U_PALBUM_24' => $ualbum['24'],
				'V_PALBUM_24' => $valbum['24'],
				'G_PALBUM_25' => ($galbum['25']) ? '1' : '',
				'S_PALBUM_25' => $talbum['25'],
				'C_PALBUM_25' => $calbum['25'],
				'T_PALBUM_25' => $palbum['25'],
				'I_PALBUM_25' => $qalbum['25'],
				'A_PALBUM_25' => $ralbum['25'],
				'U_PALBUM_25' => $ualbum['25'],
				'V_PALBUM_25' => $valbum['25'],
				'G_PALBUM_26' => ($galbum['26']) ? '1' : '',
				'S_PALBUM_26' => $talbum['26'],
				'C_PALBUM_26' => $calbum['26'],
				'T_PALBUM_26' => $palbum['26'],
				'I_PALBUM_26' => $qalbum['26'],
				'A_PALBUM_26' => $ralbum['26'],
				'U_PALBUM_26' => $ualbum['26'],
				'V_PALBUM_26' => $valbum['26'],
				'G_PALBUM_27' => ($galbum['27']) ? '1' : '',
				'S_PALBUM_27' => $talbum['27'],
				'C_PALBUM_27' => $calbum['27'],
				'T_PALBUM_27' => $palbum['27'],
				'I_PALBUM_27' => $qalbum['27'],
				'A_PALBUM_27' => $ralbum['27'],
				'U_PALBUM_27' => $ualbum['27'],
				'V_PALBUM_27' => $valbum['27'],
				'G_PALBUM_28' => ($galbum['28']) ? '1' : '',
				'S_PALBUM_28' => $talbum['28'],
				'C_PALBUM_28' => $calbum['28'],
				'T_PALBUM_28' => $palbum['28'],
				'I_PALBUM_28' => $qalbum['28'],
				'A_PALBUM_28' => $ralbum['28'],
				'U_PALBUM_28' => $ualbum['28'],
				'V_PALBUM_28' => $valbum['28'],
				'G_PALBUM_29' => ($galbum['29']) ? '1' : '',
				'S_PALBUM_29' => $talbum['29'],
				'C_PALBUM_29' => $calbum['29'],
				'T_PALBUM_29' => $palbum['29'],
				'I_PALBUM_29' => $qalbum['29'],
				'A_PALBUM_29' => $ralbum['29'],
				'U_PALBUM_29' => $ualbum['29'],
				'V_PALBUM_29' => $valbum['29'],
				'G_PALBUM_30' => ($galbum['30']) ? '1' : '',
				'S_PALBUM_30' => $talbum['30'],
				'C_PALBUM_30' => $calbum['30'],
				'T_PALBUM_30' => $palbum['30'],
				'I_PALBUM_30' => $qalbum['30'],
				'A_PALBUM_30' => $ralbum['30'],
				'U_PALBUM_30' => $ualbum['30'],
				'V_PALBUM_30' => $valbum['30'],
				'T_PALBUM_31' => $palbum['30'],
				'G_PALBUM_31' => ($galbum['31']) ? '1' : '',
				'S_PALBUM_31' => $talbum['31'],
				'C_PALBUM_31' => $calbum['31'],
				'T_PALBUM_31' => $palbum['31'],
				'I_PALBUM_31' => $qalbum['31'],
				'A_PALBUM_31' => $ralbum['31'],
				'U_PALBUM_31' => $ualbum['31'],
				'V_PALBUM_31' => $valbum['31'],
				'G_SLIDESHOW' => ($pnsettings['show_album'] != 0 && $row['album_id'] && $row['album_id'] != '0' && ($row['slide_show'] == '1' || $row['slide_show'] == '3')) ? '1' : '0',
				'S_SLIDESHOW' => ($row['slide_show'] == '1' || $row['slide_show'] == '3') ? $openLink.'<a href="javascript:load()"><img src="themes/'.$CPG_SESS['theme'].'/images/'.strtolower($module_name).'/slideshow.png" style="border: 0" alt="'._PNSLDSHOW.'" /></a>' : '',
				'T_SLIDESHOW' => ($row['slide_show'] == '1' || $row['slide_show'] == '3') ? '<a href="javascript:load()">'._PNSLDSHOW.'</a>' : '',
				'L_FIELDS' => (!$row['user_fld_0']) ? '' : ($row['usrfldttl']) ? htmlentities($row['usrfldttl']) : _PNDETAILS,
				'G_FIELDS' => ($row['user_fld_'.$row['keyusrfld']]) ? '1' : '',
				'S_USER_FLD_0' => (!$row['user_fld_0']) ? '' : ($row['usrfld0']) ? $row['usrfld0'] : _PNUSRFLD0,
				'T_USER_FLD_0' => $row['user_fld_0'],
				'G_FIELDS_1' => ($row['user_fld_1']) ? '1' : '',
				'S_USER_FLD_1' => (!$row['user_fld_1']) ? '' : ($row['usrfld1']) ? $row['usrfld1'] : _PNUSRFLD1,
				'T_USER_FLD_1' => $row['user_fld_1'],
				'G_FIELDS_2' => ($row['user_fld_2']) ? '1' : '',
				'S_USER_FLD_2' => (!$row['user_fld_2']) ? '' : ($row['usrfld2']) ? $row['usrfld2'] : _PNUSRFLD2,
				'T_USER_FLD_2' => $row['user_fld_2'],
				'G_FIELDS_3' => ($row['user_fld_3']) ? '1' : '',
				'S_USER_FLD_3' => (!$row['user_fld_3']) ? '' : ($row['usrfld3']) ? $row['usrfld3'] : _PNUSRFLD3,
				'T_USER_FLD_3' => $row['user_fld_3'],
				'G_FIELDS_4' => ($row['user_fld_4']) ? '1' : '',
				'S_USER_FLD_4' => (!$row['user_fld_4']) ? '' : ($row['usrfld4']) ? $row['usrfld4'] : _PNUSRFLD4,
				'T_USER_FLD_4' => $row['user_fld_4'],
				'G_FIELDS_5' => ($row['user_fld_5']) ? '1' : '',
				'S_USER_FLD_5' => (!$row['user_fld_5']) ? '' : ($row['usrfld5']) ? $row['usrfld5'] : _PNUSRFLD5,
				'T_USER_FLD_5' => $row['user_fld_5'],
				'G_FIELDS_6' => ($row['user_fld_6']) ? '1' : '',
				'S_USER_FLD_6' => (!$row['user_fld_6']) ? '' : ($row['usrfld6']) ? $row['usrfld6'] : _PNUSRFLD6,
				'T_USER_FLD_6' => $row['user_fld_6'],
				'G_FIELDS_7' => ($row['user_fld_7']) ? '1' : '',
				'S_USER_FLD_7' => (!$row['user_fld_7']) ? '' : ($row['usrfld7']) ? $row['usrfld7'] : _PNUSRFLD7,
				'T_USER_FLD_7' => $row['user_fld_7'],
				'G_FIELDS_8' => ($row['user_fld_8']) ? '1' : '',
				'S_USER_FLD_8' => (!$row['user_fld_8']) ? '' : ($row['usrfld8']) ? $row['usrfld8'] : _PNUSRFLD2,
				'T_USER_FLD_8' => $row['user_fld_8'],
				'G_FIELDS_9' => ($row['user_fld_9']) ? '1' : '',
				'S_USER_FLD_9' => (!$row['user_fld_9']) ? '' : ($row['usrfld9']) ? $row['usrfld9'] : _PNUSRFLD2,
				'T_USER_FLD_9' => $row['user_fld_9'],
				'U_DISCUSS' => getlink("&amp;discuss=".$row['id']),
				'S_DISCUSS' => ($row['topic_id'] ? _PNDISCUSSION :_PNDISCUSS),
				'G_DISCUSS' => $show_comments,
				'L_DISCUSS' => ($row['topic_id'] ? _PNDISCUSSION :_PNDISCUSSNEW),
				'S_NUMCMNTS' => $row['topic_replies'],
				'G_SHOW_READS' => $show_reads,
				'S_READS' => ($row['counter'] != '1') ? _PNREADS : _PNREAD,
				'T_READS' => $row['counter'],
				'G_RATE' => ($pnsettings['ratings'] && is_user()) ? '1' : '',
				'S_RATE' => ($row['ratings'] != '0') ? _PNRATE : _PNFIRSTRATE,
				'T_RATE' => select_box('score'.$row['aid'],'0',array(0=>_PNSELECT,1=>$rsymbol,2=>$rsymbol.' '.$rsymbol,3=>$rsymbol.' '.$rsymbol.' '.$rsymbol,4=>$rsymbol.' '.$rsymbol.' '.$rsymbol.' '.$rsymbol,5=>$rsymbol.' '.$rsymbol.' '.$rsymbol.' '.$rsymbol.' '.$rsymbol)),
				'G_SCORE' => ($pnsettings['ratings'] && $row['ratings'] != '0') ? '1' : '',
				'S_SCORE' => _PNRATING,
				'T_SCORE' => ($row['ratings'] != '0') ? $row['score']/$row['ratings'] : '',
				'G_MOREPICS' => ($pnsettings['per_gllry'] != 0 && $numpics > 0 && $total_rows['0']['FOUND_ROWS()'] > $numpics && $row['album_id'] != '0' && $row['slide_show'] > '1') ? '1' : '0',
				'T_MOREPICS' => _PNMOREPICS,
				'U_MOREPICS' => getlink("&amp;mode=gllry&amp;id=".$row['id']."&amp;npic=".$row['album_cnt']),
				'G_SOCIALNET' => $pnsettings['soc_net'],
				'G_PLINK' => $pnsettings['permalink'],
				'S_PLINK' => _PNLINK,
				'T_PLINK' => $ogurl = getlink("&amp;aid=".$row['id'].$url_text),
				'U_SOCNETLINK' => urlencode($BASEHREF.getlink("&amp;aid=".$row['id'])),
				'S_SOCTITLE' => urlencode($row['title']),
				'G_SENDPRINT' => '1',
				'G_EMAILF' => ($pnsettings['emailf'] && is_user()) ? '1' : '',
				'S_SENDART' => _PNSENDART,
				'U_SENDART' => getlink("&amp;mode=send&amp;id=".$row['id'].$url_text),
				'G_PRINTF' => ($pnsettings['printf']) ? '1' : '',
				'S_PRINTART' => _PNPRINTART,
				'U_PRINTART' => getlink("&amp;mode=prnt&amp;id=".$row['id'].$url_text),
				'T_DFTOPIC' => ($pnsettings['topic_lnk'] == 1) ? $row['topictext'] : '',
				'U_DFTOPIC' => ($pnsettings['topic_lnk'] == 1) ? ((file_exists("themes/$CPG_SESS[theme]/images/topics/".$row['topicimage']) ? "themes/$CPG_SESS[theme]/" : '').'images/topics/'.$row['topicimage']) : '',
				'G_STARTFORM' => open_form(getlink(),'rating','&nbsp;','class="pn_noborder"'),
				'G_ENDFORM' => close_form(),
				'L_ID' => _PNREFNO,
				'S_ID' => $row['id'],
				'T_SUBMIT' => '<input type="hidden" name="aid" value="'.$row['id'].'" /><input type="submit" name="rate" value="'._PNRATE.'" class="pn_tinygrey" />',
				'U_CANADMIN' => $editlink,
				'S_CANADMIN' => _PNEDIT,
				'T_CANADMIN' => $editlabel,
				'G_CANADMIN' => ($canedit != '') ? '1' : '',
				'G_MOD' => ($canedit == '-1' && $mod != '') ? '1' : '',
				'S_APPLINK' => _PNAPPROVE,
				'T_APPLINK' => $applink,
				'S_ACTLINK' => _PNACTIVATE,
				'T_ACTLINK' => $actlink,
				'G_STARTMOVEFORM' => open_form(getlink("&amp;aid=".$aid."&amp;mod=mov").'#moderate','move','','class="pn_noborder"'),
				'G_ENDMOVEFORM' => close_form(),
				'S_MOVLINK' => _PNMOVE,
				'T_MOVLINK' => $movlink,
//				'T_AVATAR' => (strpos($source, 'http') === 0) ? $userinfo['user_avatar'] : ($userinfo['user_avatar'] == '' ? 'images/pro_news/icons/clearpixel.gif' : (strpos($source, 'gallery') === 0 ? $userinfo['user_avatar'] : 'uploads/avatars/'.$userinfo['user_avatar']))   //puts current user's icon there!  DUH!'
			));

			if ($pnsettings['SEOtitle']) {
				$pagetitle .= ($aid ) ? $row['title'].' '._BC_DELIM.' <a href="'.getlink($module_name.'&amp;cid='.$row['catid']).'">'.$row['ctitle'].'</a> '._BC_DELIM.' <a href="'.getlink($module_name.'&amp;sid='.$row['sid']).'">'.$row['stitle'].'</a>' : _PNHOMETEXT;
			} else {
				$pagetitle .= ($aid ) ? ' '._BC_DELIM.' <a href="'.getlink($module_name.'&amp;sid='.$row['sid']).'">'.$row['stitle'].'</a> '._BC_DELIM.' <a href="'.getlink($module_name.'&amp;cid='.$row['catid']).'">'.$row['ctitle'].'</a> '._BC_DELIM.' '.$row['title'] : _PNHOMETEXT;
			}
// Add // comment identifiers in line below to DISABLE dynamic meta tags based on top 30 significant words in article text
			ProNews::dyn_meta_tags($row['seod'], $row['stitle'], $row['ctitle'], $row['title'], decode_bb_all($row['intro'], 1, true));

			if ($pnsettings['opn_grph']) {		// if enabled output facebook open graph and schema microdata fields reqd in page head
				$newln   = array("\r\n", "\n", "\r");
				$dflt_img = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $row['stitle'])).'/imageholder.png';
				$cpgtpl->assign_vars(array(
					'FBOOK_XMLNS'		=>	'itemtype="http://schema.org/Article" xmlns:fb="http://ogp.me/ns/fb#"',
					'FBOOK_OG'			=>	$ogimage ? $BASEHREF.$ogimage : (file_exists($dflt_img) ? $BASEHREF.$dflt_img : ''),
					'FBOOK_OGURL'		=>	$BASEHREF.$ogurl,
					'FBOOK_OGTITLE'		=>	strip_tags(str_replace('"', "'", $row['title'])),
					'FBOOK_OGDESC'		=>	$row['seod'] ? strip_tags(str_replace('"', "'", $row['seod'])) : strip_tags(str_replace('"', "'", str_replace($newln, ' ', $ogintro))),
				));
			}

			require_once('header.php');
			$tpl = ($row['template'] != '') ? $row['template'] : $pnsettings['template'];
			$cpgtpl->set_filenames(array('body' => 'pronews/article/'.$tpl));
			$cpgtpl->display('body');
		} else { url_redirect(getlink());}
//		} else { require_once('header.php');}	// alternate to line above for debug
	}

	function section_list($type_arts='', $showarts='', $sec='', $catid='') {
		global $db, $prefix, $pnsettings, $gblsettings, $userinfo, $cpgtpl, $pagetitle, $multilingual, $currentlang, $module_name, $module_title, $BASEHREF;
// Remove the lines below - by adding // in columns 1-2 - if you do NOT want the center and right column blocks disabled when Pro_News Article is displayed
		if ($gblsettings['Version_Num'] >= "9.2.1") {
			global $Blocks;
//			$Blocks->r=-1;
//			$Blocks->l=-1;
//			$Blocks->c=-1;
//			$Blocks->d=-1;
		}
// end of center and right block disable code
		ProNews::scheduler();					// check for scheduled activations/deactivations
		$sql = ($pnsettings['mem_in_sec'] != "0") ? ' WHERE id>"0"' : ' WHERE id>"1"';
		if ($pnsettings['sec_in_sec'] != "0") {								// Section List disabled
			if ($pnsettings['sec_in_sec'] == "1") {
				$sql .= ' AND (in_home="2" OR in_home="1")';				// Display in True Home + Home sections
			} elseif ($pnsettings['sec_in_sec'] == "2") {
				$sql .= ' AND in_home="1"';
			}
			$member_a_group = "0";
			if (is_user()) {
				if (isset($userinfo['_mem_of_groups'])) {
					foreach ($userinfo['_mem_of_groups'] as $id => $name) {
						if (!empty($name)) {
							$member_a_group = "1";
						}
					}
				}
			}
			if (!is_admin()) {
				if (is_user()) {
					if ($member_a_group) {
						$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((view<4 AND (view=0 OR view=1)) OR (view>3 AND view-3=g.group_id)))';
						} else {
						$sql .= ' AND (view=0 OR view=1)';
					}
				} else {
					$sql .= ' AND (view="0" OR view="3")';
				}
			}
			if ($sec) {
				$sql .= ' AND id='.$sec;
			}
			$sec_list = $db->sql_fetchrowset($db->sql_query('SELECT * FROM '.$prefix.'_pronews_sections'.$sql.' ORDER BY sequence'));
			if (file_exists("rss/pro_news.php") && $pnsettings['rss_in_secpg']) {		//check rss not disabled through removal
				$rss = '1';
			} else {
				$rss= '';
			}
			if (isset($sec_list)) {
				if (!$sec && !$catid) {											// dispense with Submit Article button & top level if displaying only 1 section/category
					$pagetitle .= (!$pnsettings['SEOtitle'] ? ' '._BC_DELIM.' ' : '')._PNALLARTS;
					require_once('header.php');
//					if (extension_loaded('iconv')) {echo 'iconv enabled' ;} else {echo 'iconv-NOPE';}
					$cpgtpl->assign_block_vars('sub_list', array(
						'IS_USER' => (is_user()) ? '1' : '',
						'G_STARTFORM' => open_form(getlink("&amp;mode=submit"),'addart',_PNADDARTICLE),
						'G_ENDFORM' => close_form(),
						'U_SUBMIT' => '<input type="submit" name="addart" value="'._PNADDARTICLE.'" />',
					));
					$cpgtpl->assign_block_vars('sec_title', array(
						'S_ART_TITLE' => $module_title,
						'T_RSS' => ($rss && $pnsettings['enbl_rss'] >= '1') ? '<a href="'.$BASEHREF."rss/pro_news.php/".'"><img src="images/pro_news/rss_l.png" alt="rss" title="RSS" /></a>' : '',
						'G_DISPARTS' => ($pnsettings['arts_in_secpg'] == '1'),
						'T_DISPARTS' => ($pnsettings['arts_in_secpg'] == '1' && $showarts == '') ? '<a href="'.getlink("&amp;mode=".$type_arts).'">'._PNDSPWARTS.'</a>' : '<a href="'.getlink("&amp;mode=".$type_arts).'">'._PNHDWARTS.'</a>'
					));
				} else {
					if ($sec && !$catid) {
						$pagetitle .= (!$pnsettings['SEOtitle'] ? ' '._BC_DELIM.' ' : '').$sec_list[0]['title'];
						require_once('header.php');
//						if (extension_loaded('iconv')) {echo 'iconv enabled' ;} else {echo 'iconv-NOPE';}
						$cpgtpl->assign_block_vars('sec_title', array(
							'G_DISPARTS' => ($pnsettings['arts_in_secpg'] == '1'),
							'T_DISPARTS' => ($pnsettings['arts_in_secpg'] == '1' && $showarts == '') ? '<a href="'.getlink("&amp;mode=$type_arts&amp;sec=$sec&amp;cat=$catid").'">'._PNDSPWARTS.'</a>' : '<a href="'.getlink("&amp;sec=$sec&amp;cat=$catid").'">'._PNHDWARTS.'</a>'
						));
					}
				}
				foreach ($sec_list as $row) {
					if (($row['view'] == '0') || ($row['view'] == '3' && !is_user()) || (can_admin($module_name)) || (is_user() && (($row['view'] == '1') || (($row['view'] > 3) && (isset($userinfo['_mem_of_groups'][$row['view'] - 3])))))) {
//						$nullcheck = 'notnull';
						$sql = 'SELECT c.*, COUNT(a.id) artnum FROM '.$prefix.'_pronews_cats as c,';
						$sql .= ' '.$prefix.'_pronews_articles as a WHERE a.catid=c.id';						// changed outer join to inner join to hide empty Sections/Categories - layingback 061201
						if ($catid) {
//echo '<br /> cat='.$catid.' or '.print_r($catid);
							$sql .= ' AND c.id='.$catid;
						}
						$sql .= ' AND sid="'.$row['id'].'" AND approved="1" AND active="1"';					// check active & approved to show correct count - layingback 061229
						$sql .= ' AND (alanguage="" OR alanguage="'.$currentlang.'")';
						if ($type_arts == 'newarts') {
							$sql .= ' AND posttime>"'.$userinfo['user_lastvisit'].'"';
						}
						$sql .= ' GROUP BY catid ORDER BY c.sequence';
						$result = $db->sql_query($sql);
						$cats = $db->sql_fetchrowset($result);
						$db->sql_freeresult($result);
						if ((isset($cats)) && ($cats != '')) {
/*							if ($multilingual) {
								$sectitle_lit = $secdesc_lit = '';
								eval('$sectitle_lit = _PN_SECTITLE_'.$row['id'].';');
									if (substr($sectitle_lit, 0, 13) == '_PN_SECTITLE_') {
										$sectitle_lit = '';
									}
								eval('$secdesc_lit = _PN_SECDESC_'.$row['id'].';');
									if (substr($secdesc_lit, 0, 12) == '_PN_SECDESC_') {
										$secdesc_lit = '';
									}
							}
echo '<br />m/l='.$multilingual.' sec='.$row['id'].' st='.$sectitle_lit.' sd='.$secdesc_lit;
*/
							if ($catid) {
								if ($pnsettings['SEOtitle']) {
									$pagetitle .= $cats[0]['title'].' '._BC_DELIM.' <a href="'.getlink($module_name.'&amp;sid='.$row['id']).'">'.$row['title'].'</a>';
								} else {
									$pagetitle .= ' '._BC_DELIM.' <a href="'.getlink($module_name.'&amp;sid='.$row['id']).'">'.$row['title'].'</a> '._BC_DELIM.' '.$cats[0]['title'];
								}
								require_once('header.php');
// 								if (extension_loaded('iconv')) {echo 'iconv enabled' ;} else {echo 'iconv-NOPE';}
								$cpgtpl->assign_block_vars('sec_title', array(
									'G_DISPARTS' => ($pnsettings['arts_in_secpg'] == '1'),
									'T_DISPARTS' => ($pnsettings['arts_in_secpg'] == '1' && $showarts == '') ? '<a href="'.getlink("&amp;mode=$type_arts&amp;sec=$sec&amp;cat=$catid").'">'._PNDSPWARTS.'</a>' : '<a href="'.getlink("&amp;sec=$sec&amp;cat=$catid").'">'._PNHDWARTS.'</a>'
								));
							}

							$url_text = $pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace('&amp;','',($pnsettings['url_lcase'] ? strtolower($row['title']) : $row['title']))))) : '';
							$cpgtpl->assign_block_vars('sec_list', array(
								'S_SECTION' => '<a href="'.getlink("&amp;sid=".$row['id'].$url_text).'">'.(ProNews::getsctrnslt('_PN_SECTITLE_', $row['title'], $row['id'])).'</a>',
								'S_SECDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_SECDESC_', $row['description'], $row['id']), 1, true)),
								'S_SID' => $row['id'],
								'T_RSSSEC' => ($rss && ((($pnsettings['enbl_rss'] == '2' || $pnsettings['enbl_rss'] == '4') && $row['in_home'] == '1') || $pnsettings['enbl_rss'] == '3' || $pnsettings['enbl_rss'] == '5')) ? '<a href="'.$BASEHREF."rss/pro_news.php?sid=".$row['id'].'"><img src="images/pro_news/rss.png" alt="rss" title="RSS" /></a>' : ($pnsettings['enbl_rss'] == '2' || $pnsettings['enbl_rss'] == '4' ? '<img src="images/pro_news/icons/clearpixel.gif" width="16" height="16" alt="" />' : '')
							));
							foreach ($cats as $cat) {
								if ($cat['artnum'] > $pnsettings['num_arts_sec']) {
									$morelink = 1;
								} else {
									$morelink = 0;
								}
/*								if ($multilingual) {
									$cattitle_lit = $catdesc_lit = '';
									eval('$cattitle_lit = _PN_CATTITLE_'.$cat['id'].';');
									if (substr($cattitle_lit, 0, 13) == '_PN_CATTITLE_') {
										$cattitle_lit = '';
									}
									eval('$catdesc_lit = _PN_CATDESC_'.$cat['id'].';');
									if (substr($catdesc_lit, 0, 12) == '_PN_CATDESC_') {
										$catdesc_lit = '';
									}
								}
echo ' | cat='.$cat['id'].' ct='.$cattitle_lit.' cd='.$catdesc_lit;
*/
								$url_text = $pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace('&amp;','',($pnsettings['url_lcase'] ? strtolower($cat['title'].($pnsettings['sec_in_url'] ? '/'.$row['title'] : '')) : $cat['title'].'/'.$row['title']))))) : '';
								$cpgtpl->assign_block_vars('sec_list.cat_list', array(
									'S_ICON' => ($cat['icon'] == ' ' || $cat['icon'] == '') ? 'clearpixel.gif' : $cat['icon'],
									'U_LINK' => getlink("&amp;cid=".$cat['id'].$url_text),
									'S_TITLE' => $re_use = ProNews::getsctrnslt('_PN_CATTITLE_', $cat['title'], $cat['id']),
									'U_TITLE' => '<a href="'.getlink("&amp;cid=".$cat['id'].$url_text).'">'.$re_use.'</a>',
									'S_DESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_CATDESC_', $cat['description'], $cat['id']), 1, true)),
									'S_CID' => $cat['id'],
									'T_RSSCAT' => ($rss && (($pnsettings['enbl_rss'] == '4' && $row['in_home'] == '1') || $pnsettings['enbl_rss'] == '5')) ? '<a href="'.$BASEHREF."rss/pro_news.php?sid=".$row['id']."&amp;cid=".$cat['id'].'"><img src="images/pro_news/rss_s.png" alt="rss" title="RSS" /></a>' : ($pnsettings['enbl_rss'] == '4' ? '<img src="images/pro_news/icons/clearpixel.gif" width=12 height=12 alt="rss" title="RSS" />' : ''),
									'S_COUNT' => $cat['artnum'],
									'L_COUNT' => ($cat['artnum'] == '1') ? _PNARTICLE : _PNARTICLES,
									'G_ARTICLES' => ($pnsettings['arts_in_secpg'] == '2' || ($pnsettings['arts_in_secpg'] && $showarts != '')) ? '1' : '',
									'G_MORELINK' => $morelink,
									'U_MORELINK' => getlink("&amp;cid=".$cat['id']),
									'S_MORELINK' => _PNMORE
								));
								//this is where the articles are found
								if ($pnsettings['arts_in_secpg'] == '2' || ($pnsettings['arts_in_secpg'] == '1' && $showarts !='')) {
									$sql = 'approved="1" AND active="1"';
									$sql .= ($home) ? ' AND display<>"0"' : '';
									$sql .= ($multilingual) ? " AND (alanguage='$currentlang' OR alanguage='')" : '';
									$sql .= ' AND catid=c.id AND c.sid=s.id';
									$sql .= ' AND sid='.$row['id'];
									$sql .= ' AND catid='.$cat['id'];
									if ($type_arts == 'newarts') {
										$sql .= ' AND posttime>"'.$userinfo['user_lastvisit'].'"';
									}
									if (can_admin($module_name)) {
									} else if (!is_user()) {
										$sql .= ' AND (s.view=0 OR s.view=3)';
									} else if ($member_a_group) {
										$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((s.view<4 AND (s.view=0 OR s.view=1)) OR (s.view>3 AND s.view-3=g.group_id)))';
										} else {
										$sql .= ' AND (s.view=0 OR s.view=1)';
									}

									$artsortkey = $pnsettings['art_ordr'] / '2';
									$artsortord = ($pnsettings['art_ordr'] % '2')  ? 'DESC' : 'ASC';
									if ($artsortkey < 1) {
										$artsortfld = 'posttime';
									} elseif ($artsortkey < 2) {
										$artsortfld = 'a.title';
									} elseif ($artsortkey < 3) {
										$artsortfld = 'ratings';
									} else {
										$artsortfld = 'a.counter';
									}
									$sql .= ' ORDER BY display_order DESC, ';
									$sql .= ' CASE s.art_ord WHEN 0 THEN '.$artsortfld.' END '.$artsortord.',';
									$sql .= ' CASE s.art_ord WHEN 1 THEN posttime END ASC,';
									$sql .= ' CASE s.art_ord WHEN 2 THEN posttime END DESC,';
									$sql .= ' CASE s.art_ord WHEN 3 THEN a.title END ASC,';
									$sql .= ' CASE s.art_ord WHEN 4 THEN a.title END DESC,';
									$sql .= ' CASE s.art_ord WHEN 5 THEN ratings END ASC,';
									$sql .= ' CASE s.art_ord WHEN 6 THEN ratings END DESC,';
									$sql .= ' CASE s.art_ord WHEN 7 THEN a.counter END ASC,';
									$sql .= ' CASE s.art_ord WHEN 8 THEN a.counter END DESC';
									if ($pnsettings['actv_sort_ord'] == '1') {
										$sql .= ', CASE s.art_ord WHEN 9 THEN h.dttime END ASC,';
										$sql .= ' CASE s.art_ord WHEN 10 THEN h.dttime END DESC,';
										$sql .= ' CASE s.art_ord WHEN 11 THEN h.dttime END ASC,';
										$sql .= ' CASE s.art_ord WHEN 12 THEN h.dttime END DESC';
									}


									$articlesql = 'SELECT a.id, a.title, '.($pnsettings['inc_intro'] ? 'a.intro, ' : '').'c.title ctitle, s.title stitle FROM ' .$prefix.'_pronews_articles as a, '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_sections as s WHERE ' . $sql . ' LIMIT 0,'.$pnsettings['num_arts_sec'];
									$result = $db->sql_query($articlesql);
									$arts = $db->sql_fetchrowset($result);
									$db->sql_freeresult($result);

/*									if ((isset($arts)) && ($arts != '')) {
										foreach ($arts as $art) {
											//this is where the article title goes to the template
 											if ((isset($art)) && ($art['title'] != '')) {
												$cpgtpl->assign_block_vars('sec_list.cat_list.art_list', array(
												'A_TITLE' => $art['title'],
												'U_TITLE' => '<a href="'.getlink("&amp;aid=".$art['id'].($pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', ($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $art['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $art['ctitle'].'/' : '').$art['title']) : ($pnsettings['sec_in_url'] ? $art['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $art['ctitle'].'/' : '').$art['title'])))) : '')).'">'.$art['title'].'</a>'
												));
											}
										}
									}
*/
									if ((isset($arts)) && ($arts != '')) {
										foreach ($arts as $art) {
											//this is where the article title goes to the template
 											if (isset($art) && ($art['title'] != '')) {
												$morelink = '0';
												if ($pnsettings['inc_intro']) {
													$max_intro = ($row['sectrunc1head']) ? $row['sectrunc1head'] : $pnsettings['hdln1len'];
													if (strlen($art['intro']) > $max_intro) {
														$art['intro'] = ProNews::stripBBCode($art['intro']);
														$text = substr_replace($art['intro'], ' ...', $max_intro);
														$morelink = '1';
													} else {
														$text = $art['intro'];
													}
												}
												$url_text = $pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace('&amp;','',($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $art['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $art['ctitle'].'/' : '').$art['title']) : ($pnsettings['sec_in_url'] ? $art['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $art['ctitle'].'/' : '').$art['title']))))) : '';
												$cpgtpl->assign_block_vars('sec_list.cat_list.art_list', array(
													'A_TITLE' => $art['title'],
													'U_TITLE' => '<a href="'.getlink("&amp;aid=".$art['id'].$url_text).'">'.$art['title'].'</a>',
													'G_INCINTRO' => $pnsettings['inc_intro'],
													'S_INTRO' => $pnsettings['inc_intro'] ? decode_bb_all($text, 1, false) : '',
													'U_MORELINK' => getlink("&amp;aid=".$art['id'].$url_text),
													'S_MORELINK' => _PNMORE,
													'G_MORELINK' => ($morelink == '1') ? '1' : ''
												));
											}
										}
									}

								}
							}
						} else {
							$sql = 'SELECT id FROM '.$prefix.'_pronews_cats';
							$sql .= ' WHERE sid="'.$row['id'].'" LIMIT 1';
							$result = $db->sql_query($sql);
							$cats = $db->sql_fetchrowset($result);
							$db->sql_freeresult($result);
							if (!$cats) {
								$cpgtpl->assign_block_vars('newsnone', array('S_MSG' => _PNNOCATINSEC.' &nbsp; '.$row['title']));
								$cpgtpl->set_filenames(array('errbody' => 'pronews/article/'.$pnsettings['template']));
								$cpgtpl->display('errbody');
							}
						}
					}
				}
			} else {
		require_once('header.php');
				$cpgtpl->assign_block_vars('newsnone', array('S_MSG' => _PNNOSECORCAT));
				$cpgtpl->set_filenames(array('errbody' => 'pronews/article/'.$pnsettings['template']));
				$cpgtpl->display('errbody');
			}
		}
		$cpgtpl->set_filenames(array('body' => 'pronews/sections.html'));
		$cpgtpl->display('body');
	}


	function get_block_menu($bid) {
		global $db, $prefix, $MAIN_CFG, $userinfo, $cpgtpl, $pagetitle, $currentlang;
		$pnsettings = $MAIN_CFG['pro_news'];
		$pn_module_name = $pnsettings['module_name'];
		$sql = 'SELECT * FROM '.$prefix.'_pronews_blocks WHERE bid='.$bid;
		$bsets = $db->sql_fetchrow($db->sql_query($sql));
		if (isset($bsets) && $bsets != '') {
// echo '<br />t='.$bsets['type'];
			$section_sel = $bsets['section'] == 'ALL' ? 'id > "1"' : 'id = '.$bsets['section'];
			if (is_admin() || is_user()) {
				$sec_list = $db->sql_fetchrowset($db->sql_query('SELECT * FROM '.$prefix.'_pronews_sections WHERE '.$section_sel.' ORDER BY sequence'));
			} else {
				$sec_list = $db->sql_fetchrowset($db->sql_query('SELECT * FROM '.$prefix.'_pronews_sections WHERE '.$section_sel.' AND (view="0" OR view="3") ORDER BY sequence'));
			}
			if (isset($sec_list)) {
				$menuul = '<ul id=\'pn_menutree\'>';
				foreach ($sec_list as $row) {
					if (($row['view'] == '0') || ($row['view'] == '3' && !is_user()) || (can_admin($pn_module_name)) || (is_user() && (($row['view'] == '1') || (($row['view'] > 3) && (isset($userinfo['_mem_of_groups'][$row['view'] - 3])))))) {
						$sql = 'SELECT c.*, COUNT(a.id) artnum';
						$sql .= ' FROM '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_articles as a WHERE a.catid=c.id';
						$sql .= ' AND sid="'.$row['id'].'" AND approved="1" AND active="1"';
						$sql .= ' AND (alanguage="" OR alanguage="'.$currentlang.'")';
						$sql .= ' GROUP BY catid ORDER BY c.sequence';
						$result = $db->sql_query($sql);
						$cats = $db->sql_fetchrowset($result);
						$db->sql_freeresult($result);
						if ((isset($cats)) && ($cats != '')) {
							$url_text = $pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace('&amp;','',($pnsettings['url_lcase'] ? strtolower($row['title']) : $row['title']))))) : '';
							$menuul .= '<li class=\'pn_hide\'><div class=\'clk\' onclick=\'pn_toggle(this.parentNode)\'><a href="'.getlink("$pn_module_name&amp;sid=".$row['id'].$url_text).'">'.$row['title'].'</a></div><ul>';
							foreach ($cats as $cat) {
								$artext = $cat['artnum'] == '1' ? _PNNUMART : _PNNUMARTS;
								$url_text = $pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace('&amp;','',($pnsettings['url_lcase'] ? strtolower($row['title'].'/'.$cat['title']) : $row['title'].'/'.$cat['title']))))) : '';
								if ($bsets['type'] == 'menu') {
									$menuul .= '<li class=\'pn_cat\'><a href="'.getlink("$pn_module_name&amp;cid=".$cat['id'].$url_text).'" title="'.$cat['artnum'].' '.$artext.'">'.$cat['title'].'</a></li>';
								} else {		//menuwa
									$menuul .= '<li class=\'pn_hide\'><div class=\'clk\' onclick=\'pn_toggle(this.parentNode)\'><a href="'.getlink("$pn_module_name&amp;cid=".$cat['id'].$url_text).'" title="'.$cat['artnum'].' '.$artext.'">'.$cat['title'].'</a></div><ul>';
									$artsortkey = $pnsettings['art_ordr'] / '2';
									$artsortord = ($pnsettings['art_ordr'] % '2')  ? 'DESC' : 'ASC';
									if ($artsortkey < 1) {
										$artsortfld = 'posttime';
									} elseif ($artsortkey < 2) {
										$artsortfld = 'a.title';
									} elseif ($artsortkey < 3) {
										$artsortfld = 'ratings';
									} else {
										$artsortfld = 'a.counter';
									}
									$sql = 'SELECT a.id, a.title';
									$sql .= ' FROM '.$prefix.'_pronews_articles as a WHERE a.catid='.$cat['id'];
									$sql .= ' AND approved="1" AND active="1"';
									$sql .= ' AND (alanguage="" OR alanguage="'.$currentlang.'")';
									$sql .= ' ORDER BY '.$artsortfld.' LIMIT '.$bsets['num'];
									$result = $db->sql_query($sql);
									$arts = $db->sql_fetchrowset($result);
									$db->sql_freeresult($result);
									foreach ($arts as $key => $art) {
										$url_text = $pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace('&amp;','',($pnsettings['url_lcase'] ? strtolower($row['title'].'/'.$cat['title'].'/'.$art['title']) : $row['title'].'/'.$cat['title'].'/'.$art['title']))))) : '';
										$menuul .= '<li class=\'pn_cat\'><a href="'.getlink("$pn_module_name&amp;aid=".$art['id'].$url_text).'">'.$art['title'].'</a>'.($key + 1 == $bsets['num'] && $cat['artnum'] > $bsets['num'] ? ' &nbsp; &nbsp; <a href="'.getlink("$pn_module_name&amp;cid=".$cat['id'].$url_text).'" title="'.$cat['artnum'].' '.$artext.'">...</a>' : '').'</li>';
									}
									$menuul .= '</ul></li>';
								}

							}
							$menuul .= '</ul></li>';
						}
					}
				}
				$menuul .= '</ul>';
			}
		} else {$menuul .= '<center><br />! Block Not Initialised !<br /><br />Install Pro_News Blocks ONLY from Administration > Pro_News > Blocks</center>';}
		return $menuul;
	}

	function get_comment_id($bid, &$section, &$art_cmnt_cnt, &$art_cmntid) {		// NB 2nd -> 4th variables by Reference
		global $db, $prefix, $pnsettings, $userinfo, $cpgtpl, $pagetitle, $currentlang, $curr_discuss, $curr_sec;
		$error_txt = '';
		$sql = 'SELECT * FROM '.$prefix.'_pronews_blocks WHERE bid='.$bid;
		$bsets = $db->sql_fetchrow($db->sql_query($sql));
		if (isset($bsets) && $bsets != '') {
			$art_cmnt_cnt = $bsets['num'];
			if ($bsets['section'] == 'ALL' || $bsets['section'] == $curr_sec) {
				$art_cmntid = $curr_discuss;
			} else {
				$art_cmntid = '';
			}
			$section = $curr_sec;
// echo ' art in callback='.$art_cmntid.'/'.$curr_discuss.' in '.$curr_sec.' bsets.sec='.$bsets['section'];
		} else {
			$error_txt .= '<center><br />! Block Not Initialised !<br /><br />Install Pro_News Blocks ONLY from Administration > Pro_News > Blocks</center>';
		}
		return $error_txt;
	}

	function get_moderation ($bid, &$sec, &$cat, &$art_cnt) {		// NB 2nd -> 4th variables by Reference
		global $db, $prefix;
		$error_txt = '';
		$sql = 'SELECT * FROM '.$prefix.'_pronews_blocks WHERE bid='.$bid;
		$bsets = $db->sql_fetchrow($db->sql_query($sql));
		if (isset($bsets) && $bsets != '') {
			$sec = $bsets['section'] == 'ALL' ? '' : $bsets['section'];
			$cat = $bsets['category'];
			$art_cnt = $bsets['num'];
		} else {
			$error_txt .= '<center><br />! Block Not Initialised !<br /><br />Install Pro_News Blocks ONLY from Administration > Pro_News > Blocks</center>';
		}
		return $error_txt;
	}

	function article_form($edit='') {
		global $db, $prefix, $pnsettings, $gblsettings, $module_name, $userinfo, $cpgtpl, $showblocks, $multilingual;
// echo 'from art form='; print_r($edit); echo ' &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ';

/*		if (!is_user()) {
			$cpgtpl->assign_block_vars('pn_msg', array(
				'S_MSG' => _PNLOGIN,
			));
			$cpgtpl->set_filenames(array('body' => 'pronews/submit.html'));
			$cpgtpl->display('body');
		}

		else */
		if ($edit == '') {
			$categories = ProNews::seccat('cat', '', false, '', true, true);
			if ($categories != '') {
				$cpgtpl->assign_block_vars('newarticle_form', array(
					'S_MSG' => _PNSELECTCAT,
					'S_CAT' => _PNCAT,
					'T_CAT' => $categories.'<noscript><input type="submit" value="'._PNGO.'" /></noscript>',
					'S_FORMSTART' => open_form(getlink("&amp;mode=submit&amp;do=new"),'addstory',_PNADDSTORY),
					'S_FORMEND' => close_form()
				));
			} else {
				$cpgtpl->assign_block_vars('pn_msg', array(
					'S_MSG' => _PNNOPERMSN.'<br /><br />'._PNNOPERMSN2,
				));
			}
		}

		else {
//			$showblocks = 1;
			require_once('includes/nbbcode.php');
			$savetext = ($edit['id'] != '') ? _PNSAVEART : _PNADDARTICLE;
			if ($edit['image'] != '') {
				$imagesize = getimagesize($pnsettings['imgpath'].'/'.$edit['image']);
				$thumb = ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) ? '/thumb_' : $thumb = '/';
			}
			if ($edit['image2'] != '') {
				$imagesize = getimagesize($pnsettings['imgpath'].'/'.$edit['image2']);
				$thumb2 = ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) ? '/thumb_' : $thumb2 = '/';
			}
			$comments = ($edit['allow_comment'] != '') ? $edit['allow_comment'] : $pnsettings['comments'];

			if ($pnsettings['topic_lnk'] == 1) {
				$result = $db->sql_query('SELECT topicid, topictext FROM '.$prefix.'_topics ORDER BY topictext');
				$topics = '<select name="topic" id="topic"><option value="">'._PNSELTOPIC."</option> ";
				while ($row = $db->sql_fetchrow($result)) {
					$seltopic = ($row['topicid'] == $edit['df_topic']) ? 'selected="selected" ' : '';
					$topics .= "<option $seltopic value=\"$row[topicid]\">$row[topictext]</option>";
				}
				$topics .= '</select>';
			} else {
				$topics = '';
			}

			$categories = ProNews::seccat('cat',$edit['catid'], false, '', true, false);
			$albums = ProNews::albums('album_id', $edit['album_id']);

			$usrflds_prsnt = $edit['usrfld0'].$edit['usrfld1'].$edit['usrfld2'].$edit['usrfld3'].$edit['usrfld4'].$edit['usrfld5'].$edit['usrfld6'].$edit['usrfld7'].$edit['usrfld8'].$edit['usrfld9'];

			$clsdttime = intval($edit['clsdttime']);
			$cledttime = intval($edit['cledttime']);
			$clwhlday = (ProNews::pndate('H',$clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']) == 0 && ProNews::pndate('i',$clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']) == 0 && ProNews::pndate('H',$cledttime, $userinfo['user_dst'], $userinfo['user_timezone']) == 0 && ProNews::pndate('i',$cledttime, $userinfo['user_dst'], $userinfo['user_timezone']) == 0) ? 1 : 0;
// echo ' cl stime='.$clsdttime.' cl etime='.$cledttime;

// print_r($edit);
// echo ' &nbsp;  slide+gallery='.$edit['slide_show'];

			if (ProNews::in_group_list($pnsettings['mod_grp'])) {
				$sdttime = intval($edit['sdttime']);
				$edttime = intval($edit['edttime']);
			}


			$relcat = isset($_POST['relcat']) ? intval($_POST['relcat']) : '';
			// Related Articles, modified by Masino Sinaga, June 22, 2009
 			$assotop = isset($_POST['assotop']) ? $_POST['assotop'] : explode(',', $edit['associated']);	// Masino Sinaga, June 18, 2009
			$assoc = '';
			$inclnum = '';
			$inclcatsec = '';
			if ($edit['associated'] != '') {
				$inclcode = strtok($edit['associated'], ',');
				if ($inclcode < '0') {
					$inclcode = $inclcode * -1;
					$inclnum = $inclcode % 100;
					$inclcatsec = intval($inclcode / 100);
				}
// echo ' assoc='.$edit['associated'].' code='.$inclcode.' num='.$inclnum.' catsec='.$inclcatsec;
				$checked = ($pnsettings['related_arts'] == '1') ? 'checked="checked"' : 'checked="checked" READONLY';
				$result = $db->sql_query('SELECT id, title, posttime, postby FROM '.$prefix.'_pronews_articles WHERE id IN ('.$edit['associated'].')');
				while ($rowa = $db->sql_fetchrow($result)) {
					$datestory = formatDateTime($rowa['posttime'], _DATESTRING);
//					$gettitle = $rowa['title'];
//					if ($title != $gettitle) {
//						$title = $gettitle;
//					}
					$assoc .= '<input type="checkbox" name="assotop[]" value="'.$rowa['id'].'" '.$checked.' /> <a href="'.getlink("$module_name&amp;aid=".$rowa['id']."&amp;title=".$rowa['title']).'">'.$rowa['title'].'</a>, <span class="pn_tinygrey">'.$datestory.'</span><br />';
				}
				$db->sql_freeresult($result);
//			} else {
//				$assoc = '<span class="pn_tinygrey">('._PNRELNONE.')</span>';
			}

//			$assotop = isset($_POST['assotop']) ? $_POST['assotop'] : false;
//			if ($assotop==false) {
//				$assotop = explode(',', $edit['associated']); // Modified by Masino Sinaga, June 22, 2009
//			}

			$assarticle = '';
			if ($pnsettings['related_arts'] == '1') {
				if ($relcat != '') {
					$result = $db->sql_query("SELECT id, title FROM ".$prefix."_pronews_articles WHERE id <> '".$edit['id']."' AND catid='".$relcat."' ORDER BY id");
					while ($ass = $db->sql_fetchrow($result)) {
						$checked = empty($assotop) ? '' : (in_array($ass['id'], $assotop) ? ' checked="checked" disabled="disabled"' : '');
						$assarticle .= "<tr><td></td><td><input type='checkbox' name='assotop[]' value='$ass[id]'$checked /><a href=\"".getlink("$module_name&amp;aid=$ass[id]")."\" target=_blank>$ass[title]</a></td></tr>";
					}
				}
			}
			//End of Related Articles, modified by Masino Sinaga, June 22, 2009

			$options = array(0=>_PNDONORMAL, 1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8, 9=>9, 10=>_PNDOTOPRANK);
			$cpgtpl->assign_block_vars('article_form', array(
				'G_PREVIEW' => '1',
				'S_TITLE' => _PNTITLE,
				'T_TITLE' => '<input type="text" name="title" size="50" value="'.$edit['title'].'" />',
				'S_ASSC'  => $assarticle,  // Associated articles, modified by Masino Sinaga, Sept 18, 2009
				'L_ASSC'  => _PNRELATEDART,
//				'IS_EDIT' => $is_edit,
//				'S_TOPIC_FORUM' => _PNTOPICFORUM,  // Topic ID, modified by Masino Sinaga, Sept 22, 2009
//				'T_TOPIC_FORUM' => '<input type="text" name="topicid" size="50" value="'.$edit['topic_id'].'" />',  // Topic ID, modified by Masino Sinaga, Sept 22, 2009
				'G_ADMIN' =>  ProNews::in_group_list($pnsettings['mod_grp']) ? '1' : '0',
				'G_DISCUSS' => ProNews::in_group_list($pnsettings['mod_grp']) ? '1' : ($pnsettings['comments'] == '1' ? '1' : ''),
				'S_DSPLYORDER' => _PNDSPLYORDER,
				'T_DSPLYORDER' => select_box('display_order', $edit['display_order'], $options).'&nbsp;<span class="pn_tinygrey">('._PNOPTIONAL.')</span>',
				'S_DISPLAY' => _PNDSPLYHME,
				'T_DISPLAY' => select_box('display', $edit['display'], array(1=>_PNYES, 0=>_PNNO, 2=>_PNHMONLY)).'&nbsp;<span class="pn_tinygrey">('._PNOPTIONAL.')</span>',
				'S_ALLCOM' => _PNALCOMMENTS,
				'T_ALLCOM' => yesno_option('comments',$comments),
				'G_LANG' => ($multilingual) ? '1' : '',
				'S_LANG' => _PNLANG,
				'T_LANG' => lang_selectbox($edit['alanguage']),
				'G_TOPIC' => ($topics) ? '1' : '',
				'S_TOPIC' => _TOPIC,
				'T_TOPIC' => $topics.'&nbsp;<span class="pn_tinygrey">('._PNOPTIONAL.')</span>',
				'S_INTRO' => _PNINTRO,
				'L_INTRO' => bbcode_table('intro', 'addstory', 1),
				'T_INTRO' => '<textarea cols="66" rows="15" name="intro" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);">'.$edit['intro'].'</textarea>',
				'T_INTROS' => ($pnsettings['show_smilies']) ? smilies_table('inline', 'intro', 'addstory') : '',
				'S_IMAGE' => ($edit['image'] != '') ? '<br /><br /><center>'._PNDELIMAGE.'<br /><input type="checkbox" name="delimage" value="" /></center>' : _IMAGE,
//				'T_IMAGE' => ($edit['image'] != '') ? '<input type="hidden" name="image" value="'.$edit['image'].'" /><img src="'.$pnsettings['imgpath'].'/thumb_'.$edit['image'].'" alt="'.$edit['caption'].'" />' : '<input type="hidden" name="image" value="" /><input type="file" name="iname" size="35" />',
				'T_IMAGE' => ($edit['image'] != '') ? '<input type="hidden" name="image" value="'.$edit['image'].'" /><img src="'.$pnsettings['imgpath'].$thumb.$edit['image'].'" alt=""/>' : '<input type="file" name="iname" size="35" />',
				'S_IMGIMAGE' => $pnsettings['imgpath'].'/'.$edit['image'],
				'G_IMAGEDISPLAY' => ($edit['image'] != '') ? '1' : '',
				'S_STORY' => _PNSTORY,
				'L_STORY' => bbcode_table('addtext', 'addstory', 2),
				'T_STORY' => '<textarea cols="66" rows="15" name="addtext" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);">'.$edit['content'].'</textarea>',
				'T_STORYS' => ($pnsettings['show_smilies']) ? smilies_table('inline', 'addtext', 'addstory') : '',
				'S_IMAGE2' => ($edit['image2'] != '') ? '<br /><br /><center>'._PNDELIMAGE.'<br /><input type="checkbox" name="delimage2" value="" /></center>' : _IMAGE,
				'T_IMAGE2' => ($edit['image2'] != '') ? '<input type="hidden" name="image2" value="'.$edit['image2'].'" /><img src="'.$pnsettings['imgpath'].'/thumb_'.$edit['image2'].'" alt="'.$edit['caption2'].'" />' : '<input type="hidden" name="image2" value="" /><input type="file" name="iname2" size="35" />',
				'S_IMGIMAGE2' => $pnsettings['imgpath'].'/'.$edit['image2'],
				'G_IMAGE2DISPLAY' => ($edit['image2'] != '') ? '1' : '',
				'G_IMAGE' => ($pnsettings['allow_up'] == '1') ? '1' : '',
				'G_ID' => ($edit['id'] != '') ? '<input type="hidden" name="id" value="'.$edit['id'].'" />' : false,
				'G_SAVE' => ($edit['title'] != '' && $edit['catid'] != '' && $edit['intro'] != '') ? '<input type="submit" value="'._PNPREVIEW.'" />&nbsp;&nbsp;<input type="submit" name="submitart" value="'.$savetext.'" />' : '<input type="submit" value="'._PNPREVIEW.'" />',
				'S_APPROVE' => ($edit['id'] == '' && $edit['title'] != '' && $edit['catid'] != '' && $edit['intro'] != '') ? _PNAPPRNOTICE : '',
				'S_CAP' => _PNIMGCAP,
				'T_CAP' => '<input type="text" name="imgcap" size="35" value="'.$edit['caption'].'" />&nbsp;<span class="pn_tinygrey">('._PNOPTIONAL.')</span>',
				'S_CAP2' => _PNIMGCAP,
				'T_CAP2' => '<input type="text" name="imgcap2" size="35" value="'.$edit['caption2'].'" />&nbsp;<span class="pn_tinygrey">('._PNOPTIONAL.')</span>',
				'S_CAT' => _PNCAT,
				'T_CAT' => $categories.'<noscript><input type="submit" value="'._PNCHANGE.'" /></noscript> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="submit" value="'._PNPREVIEW.'" />',
				'L_CAT' => $edit['catid'],
				'G_CALENDAR' => ($pnsettings['cal_module']) ? '1' : '',
				'S_CALSDTTIME' => _PNEVENT.' '._PNCALSTART.'<input type="hidden" name="calid" value="'.(($edit['cal_id']) ? $edit['cal_id'] : 0).'" />',
//				'T_CALSDTTIME' => ($clsdttime) ? (($clwhlday) ? ProNews::dttime_edit('c', $clsdttime, true, true) : ProNews::dttime_edit('c', $clsdttime, true)) : ProNews::dttime_edit('c', '', true, true),
//				'T_CALSDTTIME' => ProNews::dttime_edit('c', $clsdttime, true, true),
				'T_CALSDTTIME' => ($clwhlday) ? ProNews::dttime_edit('c', $clsdttime, true, true, true) : ProNews::dttime_edit('c', $clsdttime, true, true),
				'S_CALEDTTIME' => _PNEVENT.' '._PNCALEND,
//				'T_CALEDTTIME' => ($cledttime) ? (($clwhlday) ? ProNews::dttime_edit('d', $cledttime, true, true) : ProNews::dttime_edit('d', $cledttime, true)) : ProNews::dttime_edit('d', '', true, true),
//				'T_CALEDTTIME' => ProNews::dttime_edit('d', $cledttime, true, true),
				'T_CALEDTTIME' => ($clwhlday) ? ProNews::dttime_edit('d', $cledttime, true, true, true) : ProNews::dttime_edit('d', $cledttime, true, true),

				'G_ALBUM' => ($pnsettings['show_album'] == '1') ? '1' : '',
				'S_ALBUM' => _PNALBUM,
				'T_ALBUM' => $albums,
				'S_ALBCNT' => _PNALBCNT,
				'T_ALBCNT' => select_option('album_cnt',$edit['album_cnt'],array(0=>'0',1=>'1',2=>'2',3=>'3',4=>'4',5=>'5',6=>'6',7=>'7',8=>'8',9=>'9',10=>'10',11=>'11',12=>'12',13=>'13',14=>'14',15=>'15',16=>'16',17=>'17',18=>'18',19=>'19',20=>'20',21=>'21',22=>'22',23=>'23',24=>'24',25=>'25',26=>'26',27=>'27',28=>'28',29=>'29',30=>'30',31=>'31',32=>'32')),
//				'T_ALBCNT' => select_option('album_cnt',$edit['album_cnt'],array(0=>'0',1=>'1',2=>'2',3=>'3',4=>'4',5=>'5',6=>'6',7=>'7',8=>'8',9=>'9',10=>'10')),
				'L_ALBCNT' => _PNFRSTPG,
				'S_ALBSEQ' => _PNALBSEQ,
				'T_ALBSEQ' => select_box('album_seq',$edit['album_seq'],array(0=>_PNDEFAULT,1=>_PNTTLASC,2=>_PNTTLDSC,3=>_PNFLNASC,4=>_PNFLNDSC,5=>_PNDLASC,6=>_PNDLDSC,7=>_PNRATASC,8=>_PNRATDSC)),
				'S_SLDSHW' => _PNSLDSHW,
				'T_SLDSHW' => yesno_option('slide_show',($edit['slide_show'] & 1)),
				'S_GLLRY' => _PNADDLPGS,
				'T_GLLRY' => yesno_option('gallery',($edit['slide_show'] > 1)),
				'G_FIELDS' => ($pnsettings['show_usrflds'] == '1' && $usrflds_prsnt != '') ? '1' : '',
				'S_USER_FLD_0' => ($edit['usrfld0']) ? $edit['usrfld0'] : _PNUSRFLD0,
				'T_USER_FLD_0' => $edit['user_fld_0'],
				'S_USER_FLD_1' => ($edit['usrfld1']) ? $edit['usrfld1'] : _PNUSRFLD1,
				'T_USER_FLD_1' => $edit['user_fld_1'],
				'S_USER_FLD_2' => ($edit['usrfld2']) ? $edit['usrfld2'] : _PNUSRFLD2,
				'T_USER_FLD_2' => $edit['user_fld_2'],
				'S_USER_FLD_3' => ($edit['usrfld3']) ? $edit['usrfld3'] : _PNUSRFLD3,
				'T_USER_FLD_3' => $edit['user_fld_3'],
				'S_USER_FLD_4' => ($edit['usrfld4']) ? $edit['usrfld4'] : _PNUSRFLD4,
				'T_USER_FLD_4' => $edit['user_fld_4'],
				'S_USER_FLD_5' => ($edit['usrfld5']) ? $edit['usrfld5'] : _PNUSRFLD5,
				'T_USER_FLD_5' => $edit['user_fld_5'],
				'S_USER_FLD_6' => ($edit['usrfld6']) ? $edit['usrfld6'] : _PNUSRFLD6,
				'T_USER_FLD_6' => $edit['user_fld_6'],
				'S_USER_FLD_7' => ($edit['usrfld7']) ? $edit['usrfld7'] : _PNUSRFLD7,
				'T_USER_FLD_7' => $edit['user_fld_7'],
				'S_USER_FLD_8' => ($edit['usrfld8']) ? $edit['usrfld8'] : _PNUSRFLD8,
				'T_USER_FLD_8' => $edit['user_fld_8'],
				'S_USER_FLD_9' => ($edit['usrfld9']) ? $edit['usrfld9'] : _PNUSRFLD9,
				'T_USER_FLD_9' => $edit['user_fld_9'],
				'G_ASSC' => ($pnsettings['related_arts'] == '1') ? '1' : '',
				'L_ASSC'  => _PNRELATEDART, // Related Articles, modified by Masino Sinaga, June 22, 2009
				'L_LSTLNKS'  => _PNLSTLNKS,
				'T_ALRDYASSOC' => $assoc,
				'L_SEL_ASSC'  => ($pnsettings['related_arts'] == '1') ? _PNSELRELATEDART : '',
				'T_RLDTCAT' => ($pnsettings['related_arts'] == '1') ? ProNews::seccat('relcat',$relcat,false, '', false, true).'<noscript><input type="submit" name="dsply" value="'._PNDISPLAY.'" /></noscript>' : '',
				'L_INCL_ASSC'  => ($pnsettings['related_arts'] == '1') ? _PNINCL.' ' : '',
				'S_INCL_ASSC'  => ($pnsettings['related_arts'] == '1') ? select_box('inclnum', $inclnum, array(0=>_PNNO, 99=>_PNALL, 12=>_PNLAST.' 12', 11=>_PNLAST.' 11', 10=>_PNLAST.' 10', 6=>_PNLAST.' 6', 5=>_PNLAST.' 5')).' '._PNINCLASSOC : '',
				'T_INCL_CATSEC' => ($pnsettings['related_arts'] == '1') ? select_box('inclcatsec', $inclcatsec, array(0=>_PNRALCAT, 1=>_PNRALSEC, 2=>_PNRALUSER, 3=>_PNRALUSRSEC)) : '',
 				'S_ASSC'  => ($pnsettings['related_arts'] == '1') ? $assarticle : '',
				'S_STARTDTTIME' => _PNSTART.' '._PNFORART,
				'T_STARTDTTIME' => ($edit['sdttime']) ? ProNews::dttime_edit('s', $sdttime) : ProNews::dttime_edit('s', (time() - 60)),
				'S_ENDDTTIME' => _PNEND.' '._PNFORART,
				'T_ENDDTTIME' => ($edit['edttime']) ? ProNews::dttime_edit('e', $edttime) : ProNews::dttime_edit('e', (time() - 60)),
				'L_CALENDAR' => _PNCALENDAR,
				'L_ALBUM' => _PNPHOTOALBUM,
				'L_FIELDS' => _PNUSERFIELDS,
				'L_SCHED' => _PNARTSCHED,
				'L_SEL_CAL' => _PNSELECTDATE,
				'L_SEL_ALB' => _PNSELECTALBUM,
				'L_SEL_FLDS' => _PNENTERFIELDS,
				'S_FORMSTART' => ($edit['id'] != '') ? open_form(getlink("&amp;mode=submit&amp;do=save"),'addstory',_PNEDITSTORY) : open_form(getlink("&amp;mode=submit&amp;do=save"),'addstory',_PNADDSTORY),
				'S_FORMEND' => close_form()
			));
		}
	}

	function display_articles($sid='',$cid='',$usr='') {
		global $db, $prefix, $cpgtpl, $pnsettings, $gblsettings, $pagetitle, $home, $userinfo, $multilingual, $currentlang, $domain, $module_name,
		 $Blocks, $MAIN_CFG, $BASEHREF, $CPG_SESS;
// The pagination include below is not required for CPG-Nuke 9.1.x or later
		if ($gblsettings['Version_Num'] == "9.0.6.1") {
			require_once('includes/pagination.php');
		}
		$is_home = $home || (isset($_GET['mode']) ? Fix_Quotes($_GET['mode'],1) : '');
		if ((($home && $pnsettings['clrblks_hm'] == '2') || ($is_home && $pnsettings['clrblks_hm'] == '1')) && ($gblsettings['Version_Num'] >= "9.2.1")) {
// Remove the lines below - by adding // in columns 1-2 - if you do NOT want the left, center up/down, and/or right column blocks disabled when Pro_News Article is displayed on Home page
			$Blocks->l=-1;
			$Blocks->r=-1;
			$Blocks->c=-1;
			$Blocks->d=-1;
// end of left, center up/down, and right block disable code
		}
		$rsymbol = '&hearts;';
		ProNews::scheduler();					// check for scheduled activations/deactivations
		$content = '';
		if ($pnsettings['usr_ovr_ppage'] && is_user() && $userinfo['storynum'] && $MAIN_CFG['member']['user_news']) {
			$arts_per_page = $userinfo['storynum'];
		} else {
			$arts_per_page = (isset($pnsettings['per_page']) && ($pnsettings['per_page'] > '0')) ? $pnsettings['per_page'] : '10';
		}
		if (isset($_GET['page']) && intval($_GET['page']) > 1) {
			$page = intval($_GET['page']);
			if ($pnsettings['SEOtitle']) {
				$pagetitle .= _PNPAGE.' '.$page.' '._BC_DELIM.' ';
			} else {
				$pagetitle .= _BC_DELIM.' '._PNPAGE.' '.$page;
			}
		} else {
			$page = 1;
		}
		$offset = ($page - 1) * $arts_per_page;
		$sid = ($sid <> '0') ? (($sid) ? intval($sid) : '') : '0';	// preserve sid=0, but remove any .html left by cpgmm getlink error
		$cid = ($cid <> '0') ? (($cid) ? intval($cid) : '') : '0';
		$member_a_group = "0";
		if (isset($userinfo['_mem_of_groups'])) {
			foreach ($userinfo['_mem_of_groups'] as $id => $name) {
				if (!empty($name)) {
					$member_a_group = "1";
				}
			}
		}
		$sql = 'approved="1" AND active="1"';
		$homeval = ($is_home) ? (($home) ? ' s.in_home<>"0"' : ' s.in_home="1"') : '';
		$sql .= ($is_home ? ' AND display<>"0"'.((in_array(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), (array)$domain) ? '  AND ('.$homeval.' OR s.in_home="'.array_search(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), $domain).'")' : ' AND '.$homeval)) : ' AND display<>"2"');
		$sql .= ($multilingual ? " AND (alanguage='$currentlang' OR alanguage='')" : '');
		$sql .= ' AND catid=c.id AND c.sid=s.id';
		$sql .= (!empty($sid)) ? ' AND sid='.$sid : '';
		$sql .= (!empty($cid)) ? ' AND catid='.$cid : '';

		$sql .= ($usr != '') ? ' AND postby="'.$usr.'"' : '';

		if (can_admin($module_name)) {
			$numarticles = $db->sql_count($prefix.'_pronews_articles, '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_sections as s', $sql);
		} else if (!is_user()) {
			$sql .= ' AND (s.view=0 OR s.view=3)';
			$numarticles = $db->sql_count($prefix.'_pronews_articles, '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_sections as s', $sql);
		} else if ($member_a_group) {
			$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((s.view<4 AND (s.view=0 OR s.view=1)) OR (s.view>3 AND s.view-3=g.group_id)))';
			$numarticles = $db->sql_count($prefix.'_pronews_articles, '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_sections as s', $sql);
		} else {
			$sql .= ' AND (s.view=0 OR s.view=1)';
			$numarticles = $db->sql_count($prefix.'_pronews_articles, '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_sections as s', $sql);
		}
		$pages = ceil($numarticles/$arts_per_page);
		if ($pages < $page && $arts_per_page > '0' && $page != '1') { cpg_error(_PNNOTHING); }
		$sql = 'SELECT a.id aid, a.*, c.id cid, c.sequence, c.title ctitle, c.description cdescription, c.icon icon, c.forum_id cforum_id, s.id sid, s.sequence, s.title stitle, s.description sdescription, s.view view, s.admin sadmin, s.forum_id sforum_id, template, usrfld0, usrfld1, usrfld2, usrfld3, usrfld4, keyusrfld';

		if ($pnsettings['topic_lnk'] > 0) {
			$sql .= ', t.topicimage, t.topictext';
		}

		$sql .= ' FROM '.$prefix.'_pronews_articles as a';

		if ($pnsettings['topic_lnk'] > 0) {
			$sql .= ' LEFT JOIN '.$prefix.'_topics as t ON a.df_topic=t.topicid';
		}

		$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
		$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
		if ($pnsettings['actv_sort_ord'] == '1') {
			$sql .= ' LEFT JOIN '.$prefix.'_pronews_schedule as h ON a.id=h.id AND CASE s.art_ord';
			$sql .= ' WHEN "9" THEN h.newstate="0" WHEN "10" THEN h.newstate="1" WHEN "11" THEN h.newstate="0" WHEN "12" THEN h.newstate="1" END';
		}

		$sql .= ' WHERE a.catid=c.id AND c.sid=s.id';
		$sql .= ' AND a.approved="1" AND a.active="1"';
		$sql .= ($multilingual ? " AND (alanguage='$currentlang' OR alanguage='')" : '');
		$sql .= ($is_home ? ' AND display<>"0"'.((in_array(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), (array)$domain) ? '  AND ('.$homeval.' OR s.in_home="'.array_search(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), $domain).'")' : ' AND '.$homeval)) : ' AND display<>"2"');
// echo ' domain='; print_r($domain); echo ' inarray='.in_array(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), (array)$domain).' key='.array_search(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), $domain).' domain_name='.ereg_replace('www.', '', $_SERVER['SERVER_NAME']);
		$artsortkey = $pnsettings['art_ordr'] / '2';
		$artsortord = ($pnsettings['art_ordr'] % '2')  ? 'DESC' : 'ASC';
		if ($artsortkey < 1) {
			$artsortfld = 'posttime';
		} elseif ($artsortkey < 2) {
			$artsortfld = 'a.title';
		} elseif ($artsortkey < 3) {
			$artsortfld = 'ratings';
		} else {
			$artsortfld = 'a.counter';
		}
// echo ' [art_ordr]='.$pnsettings['art_ordr'].' artsortkey='.$artsortkey.' artsordfld='.$artsortfld.' artsordord='.$artsortord.' sid='.$sid.' cid='.$cid;

		if (!can_admin($module_name)) {
			if (!is_user()) {
				$sql .= ' AND (s.view=0 OR s.view=3)';
			} else if ($member_a_group) {
				$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((s.view<4 AND (s.view=0 OR s.view=1)) OR (s.view>3 AND s.view-3=g.group_id)))';
			} else {
				$sql .= ' AND (s.view=0 OR s.view=1)';
			}
		}

		$sql .= $usr != '' ? ' AND postby="'.$usr.'"' : '';

		if ($sid == '') {
			if ($pnsettings['display_by'] == '0') {
				$sql .= " ORDER BY display_order DESC, ";
			} elseif ($pnsettings['display_by'] == '1')  {
				$sql .= " ORDER BY s.sequence ASC, display_order DESC, ";
			} else {
				$sql .= " ORDER BY s.sequence ASC, c.sequence ASC, display_order DESC,";
			}
		} elseif (!empty($sid)) {
			$sql .= ' AND c.sid="'.$sid.'"';
			if ($pnsettings['display_by'] < '2') {
				$sql .= ' ORDER BY CASE s.secdsplyby WHEN 2 THEN c.sequence END ASC, display_order DESC, ';
			} else {
				$sql .= ' ORDER BY CASE s.secdsplyby WHEN 0 THEN c.sequence WHEN 2 THEN c.sequence END ASC, display_order DESC, ';
			}
		} elseif (($sid == '0') && (!empty($cid))) {
			$sql .= ' AND catid="'.$cid.'"';
			$sql .= " ORDER BY display_order DESC, ";
		} else {
			$sql .= " ORDER BY display_order DESC, ";
		}
		$sql .= ' CASE s.art_ord WHEN 0 THEN '.$artsortfld.' END '.$artsortord.',';
		$sql .= ' CASE s.art_ord WHEN 1 THEN posttime END ASC,';
		$sql .= ' CASE s.art_ord WHEN 2 THEN posttime END DESC,';
		$sql .= ' CASE s.art_ord WHEN 3 THEN a.title END ASC,';
		$sql .= ' CASE s.art_ord WHEN 4 THEN a.title END DESC,';
		$sql .= ' CASE s.art_ord WHEN 5 THEN ratings END ASC,';
		$sql .= ' CASE s.art_ord WHEN 6 THEN ratings END DESC,';
		$sql .= ' CASE s.art_ord WHEN 7 THEN a.counter END ASC,';
		$sql .= ' CASE s.art_ord WHEN 8 THEN a.counter END DESC';
		if ($pnsettings['actv_sort_ord'] == '1') {
			$sql .= ', CASE s.art_ord WHEN 9 THEN h.dttime END ASC,';
			$sql .= ' CASE s.art_ord WHEN 10 THEN h.dttime END DESC,';
			$sql .= ' CASE s.art_ord WHEN 11 THEN h.dttime END ASC,';
			$sql .= ' CASE s.art_ord WHEN 12 THEN h.dttime END DESC';
		}
		$sql .= ' LIMIT '.$offset.','.$arts_per_page;
		$result = $db->sql_query($sql);
		$list = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);
		if (isset($list) && $list != '' && count($list) > '0') {

			if ($pnsettings['disply_full'] && count($list) == 1) {		// if only 1 record display via aid=
//				url_redirect(getlink($module_name.'&amp;aid='.$list['0']['aid']));
				ProNews::article($list['0']['aid']);
				return;
			}

			require_once('includes/nbbcode.php');
			$last_sec = ''; $last_cat = '';
			$lasttpl = '';
			$first_art = '1';
			foreach ($list as $key => $row) {
				if ($first_art) {
					$first_art = '';
					if (!$home) {
						if ($pnsettings['SEOtitle']) {
							$pagetitle .= '<a href="'.getlink($module_name.'&amp;cid='.$row['cid']).'">'.$row['ctitle'].'</a>'.($sid ? ' '._BC_DELIM.' <a href="'.getlink($module_name.'&amp;sid='.$row['sid']).'">'.$row['stitle'].'</a>' : '');
						} else {
							$pagetitle .= ' '._BC_DELIM.' '.($sid ? '<a href="'.getlink($module_name.'&amp;sid='.$row['sid']).'">'.$row['stitle'].'</a> '._BC_DELIM.' ' : '').'<a href="'.getlink($module_name.'&amp;cid='.$row['cid']).'">'.$row['ctitle'].'</a>';
						}
					} else {
						$pagetitle .= _PNHOMETEXT;
					}
				}
				if (($row['view'] == '0') || (can_admin($module_name)) || (is_user() && (($row['view'] == '1') || (($row['view'] > 3) && (isset($userinfo['_mem_of_groups'][$row['view'] - 3])))))) {
					$url_text = $pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace('&amp;','',($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title']) : ($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title']))))) : '';
					if (($row['image'] != '') && ($row['image'] != '0')) {
						$imagesize = getimagesize($pnsettings['imgpath'].'/'.$row['image']);  // fitted window - layingback 061119
						$imagesizeX = $imagesize[0] + 16; $imagesizeY = $imagesize[1] + 16;
						if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
							$thumbimage = $pnsettings['imgpath'].'/thumb_'.$row['image'];
//							$display_image = '<a href="'.$pnsettings['imgpath'].'/'.$row['image'].'" target="pn'.uniqid(rand()).'" onclick="PN_openBrWindow(\''.$BASEHREF.$pnsettings['imgpath'].'/'.$row['image'].'\',\'' . uniqid(rand()) . '\',\'resizable=yes,scrollbars=yes,width='.$imagesizeX.',height='.$imagesizeY.',left=0,top=0\');return false;"><img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" /></a>';
							$full_image = '<img class="pn_image" src="'.$pnsettings['imgpath'].'/'.$row['image'].'" alt="'.$row['caption'].'" />';
						} else {
					    	$thumbimage = $pnsettings['imgpath'].'/'.$row['image'];
//							$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" />';
							$full_image = '';
						}														  // Check if thumb exists before linking - layingback 061122
						$display_image = '<a href="'.getlink("&amp;aid=".$row['aid'].$url_text).'"><img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" /></a>';
						$iconimage = $pnsettings['imgpath'].'/icon_'.$row['image'];
					} elseif ($pnsettings['show_noimage'] != '0' && file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $row['stitle'])).'/imageholder.png')) {
						$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $row['stitle'])).'/imageholder.png';
						$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="" />';
						$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $row['stitle'])).'/imageholdermini.png';
						$full_image = '';
					} elseif ($pnsettings['show_noimage'] != '0' && file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png')) {
						$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png';
						$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="" />';
						$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholdermini.png';
						$full_image = '';
					} else {
						$display_image = '';
						$full_image = '';
						$thumbimage = '';
						$iconimage = '';
					}
					if ($row['intro'] == '') {
						if(strlen($row['content']) > $pnsettings['introlen']) {
							$text = substr_replace($row['content'],' ...',$pnsettings['introlen']);
							$morelink = '1';
						} else {
							$text = $row['content'];
							$morelink = '0';
						}
					} else {
						$text = $row['intro'];
						if ($row['content'] != '' || $row['user_fld_'.$row['keyusrfld']] != '' || $row['album_id'] != '0' || $row['image2'] != '' || $row['associated']) {$morelink = '1';} else {$morelink = '0';}
					}
					if (can_admin($module_name)) {
						$canedit = "2";
						$editlink = adminlink('&amp;mode=add&amp;do=edit&amp;id=').$row['aid'];
						$editlabel = ($morelink == '1') ? _PNSECADMIN : '<a href="'.adminlink("&amp;mode=list&amp;do=sort&amp;cat=".$row['catid']).'">'._PNSECADMIN.'</a>';       // inline Edit - layingback 061201
					} elseif (ProNews::in_group_list($pnsettings['mod_grp'])) {
						$canedit = "-1";
						$editlink = getlink("&amp;mode=submit&amp;do=edit&amp;id=".$row['aid']);
						$editlabel = '<a href="'.getlink("&amp;aid=".$row['aid']."&amp;mod=mod").'">'._PNMODERATE.'</a>';	// moderate
							} elseif ((($row['sadmin'] == '0') || (($row['sadmin'] == '1') && (is_user())) || (($row['sadmin'] > '3') && (is_user()) && (in_group($row['sadmin']-3)) && ($row['postby'] == $userinfo['username']))) && ($pnsettings['edit_time'] == '' || time() < ($row['updttime'] ? $row['updttime'] : $row['posttime']) + ($pnsettings['edit_time'] * 60))) {
								$canedit = $row['sadmin'];
								$editlink = getlink("&amp;mode=submit&amp;do=edit&amp;id=".$row['aid']);
								$editlabel = _PNEDITARTICLE;     // User as member of authorised group edit - layingback 061201
							} else {
								$canedit = '';
							}
					$mod = isset($_POST['mod']) ? Fix_Quotes($_POST['mod']) : (isset($_GET['mod']) ? Fix_Quotes($_GET['mod']) : '');
		// echo 'mod='.$mod;
					if (isset($mod) && ProNews::in_group_list($pnsettings['mod_grp'])) {
						switch ($mod) {
							case 'app':
								ProNews::approve($aid, $row['cid']);		//	doesn't return
							break;

							case 'act':
								ProNews::activate($aid, $row['cid']);		//	doesn't return
							break;

							case 'mov':
								ProNews::move_art($aid);		//	doesn't return
							break;
						}
						$applink = ProNews::get_status('app', $row['id'], $row['approved']);
		// echo 'applnk='.$applink;
						$actlink = ProNews::get_status('act', $row['id'], $row['active']);
		// echo 'actlnk='.$actlink;
						$movlink = ProNews::seccat('cat2','',false,'',false,true);
					}

// echo ' theme='.$CPG_SESS['theme'].' tpl='.$tpl.' lasttpl='.$lasttpl.'<br>';
					$tpl = ($row['template'] != '') ? $row['template'] : $pnsettings['template'];
					if ($tpl <> $lasttpl && $lasttpl <> '') {
						require_once('header.php');
						$cpgtpl->display('body', false);
						$cpgtpl->unset_block('newshome');
					}
// echo '<br />secbrk='.((($pnsettings['display_by'] == '1' || $pnsettings['display_by'] == '2') && ($row['stitle'] != $last_sec)) ? '1' : '0').' dspby='.$pnsettings['display_by'].' key='.$key.' stitle='.$row['stitle'].' lasstsec='.$last_sec.' nextsec='.$list[$key+1]['stitle'].' firstart='.($row['stitle'] != $last_sec ? '1' : '0').' lastart='.($row['sid'] != $list[$key+1]['sid'] ? '1' : '0');
					$cpgtpl->assign_block_vars('newshome', array(
						'G_SECBRK' => (($pnsettings['display_by'] == '1' || $pnsettings['display_by'] == '2') && ($pnsettings['secat_hdgs'] == '1' || $pnsettings['secat_hdgs'] == 3) && $row['stitle'] != $last_sec) ? '1' : '0',
						'S_SECBRK' => ProNews::getsctrnslt('_PN_SECTITLE_', $row['stitle'], $row['sid']),
						'U_SECDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_SECDESC_', $row['sdescription'], $row['sid']), 1, true)),
						'G_CATBRK' => ($pnsettings['display_by'] == '2' && $pnsettings['secat_hdgs'] >= '2' && $row['ctitle'] != $last_cat) ? '1' : '0',
						'S_CATBRK' => ProNews::getsctrnslt('_PN_CATTITLE_', $row['ctitle'], $row['catid']),
						'U_CATDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_CATDESC_', $row['cdescription'], $row['catid']), 1, true)),
						'T_SECBRK' => getlink("&amp;sid=".$row['sid']),
						'T_CATBRK' => getlink("&amp;cid=".$row['cid']),
						'G_FIRSTART' => ($row['stitle'] != $last_sec) ? '1' : '0',
						'G_LASTART' => ($row['sid'] != $list[$key+1]['sid']) ? '1' : '0',
						'L_PAUSED' => ' '._PNPAUSED.' ',
						'S_INTRO' => make_clickable(decode_bb_all($row['intro'], 1, true)),  // true param added for images - layingback
						'S_TITLE' => $row['title'],
						'S_ICON' => ($row['icon'] != '') ? $row['icon'] : 'clearpixel.gif',
						'T_ICON' => $row['ctitle'],
						'L_POSTBY' => _PNPOSTBY,
						'S_POSTBY' => $row['postby'],
						'T_POSTBY' => getlink("Your_Account&amp;profile=".$row['postby']),
						'L_POSTON' => _PNPOSTON,
						'S_POSTTIME' => ProNews::create_date(false, $row['posttime']),
						'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
						'S_CATEGORY' => $row['catid'],
						'S_CATLINK' => getlink("&amp;cid=".$row['catid']),
						'S_IMAGE' => ($row['image'] == '' && $pnsettings['show_noimage'] == '0') ? '' : $display_image,
						'T_FULLIMAGE' => $full_image,
						'T_THUMBIMAGE' => $thumbimage,
						'T_ICONIMAGE' => $iconimage,
						'T_CAP' => $row['caption'],
						'T_DFTOPIC' => ($pnsettings['topic_lnk'] == 1) ? $row['topictext'] : '',
						'U_DFTOPIC' => ($pnsettings['topic_lnk'] == 1) ? ((file_exists("themes/$CPG_SESS[theme]/images/topics/".$row['topicimage']) ? "themes/$CPG_SESS[theme]/" : '').'images/topics/'.$row['topicimage']) : '',
						'S_USER_FLD_0' => (!$row['user_fld_0']) ? '' : ($row['usrfld0']) ? $row['usrfld0'] : _PNUSRFLD0,
						'T_USER_FLD_0' => $row['user_fld_0'],
						'S_USER_FLD_1' => (!$row['user_fld_1']) ? '' : ($row['usrfld1']) ? $row['usrfld1'] : _PNUSRFLD1,
						'T_USER_FLD_1' => $row['user_fld_1'],
						'S_USER_FLD_2' => (!$row['user_fld_2']) ? '' : ($row['usrfld2']) ? $row['usrfld2'] : _PNUSRFLD2,
						'T_USER_FLD_2' => $row['user_fld_2'],
						'S_USER_FLD_3' => (!$row['user_fld_3']) ? '' : ($row['usrfld3']) ? $row['usrfld3'] : _PNUSRFLD3,
						'T_USER_FLD_3' => $row['user_fld_3'],
						'S_USER_FLD_4' => (!$row['user_fld_4']) ? '' : ($row['usrfld4']) ? $row['usrfld4'] : _PNUSRFLD4,
						'T_USER_FLD_4' => $row['user_fld_4'],
						'U_DISCUSS' => getlink("&amp;discuss=".$row['aid']),
						'S_DISCUSS' => ($row['topic_id'] ? _PNDISCUSSION : _PNDISCUSS),
						'G_DISCUSS' => (!($row['sforum_id'] == '0' && $row['cforum_id'] == '0') && $row['cforum_id'] != -1 && $row['allow_comment'] == '1' && $pnsettings['comments'] == '1') ? '1' : '',
						'L_DISCUSS' => ($row['topic_id'] ? _PNDISCUSSION : _PNDISCUSSNEW),
						'S_NUMCMNTS' => ($row['topic_id'] ? '1' : ''),
						'U_MORELINK' => getlink("&amp;aid=".$row['aid'].$url_text),
						'S_MORELINK' => _PNMORE,
						'G_MORELINK' => ($morelink == '1') ? '1' : '',
						'G_SOCIALNET' => $pnsettings['soc_net'],
						'U_SOCNETLINK' => urlencode($BASEHREF.getlink("&amp;aid=".$row['id'])),
						'S_SOCTITLE' => urlencode($row['title']),
						'G_SHOW_READS' => $pnsettings['show_reads'],
						'S_READS' => ($row['counter'] != '1') ? _PNREADS : _PNREAD,
						'T_READS' => $row['counter'],
						'U_CANADMIN' => $editlink,
						'S_CANADMIN' => _PNEDIT,
						'T_CANADMIN' => $editlabel,
						'G_CANADMIN' => ($canedit != '') ? '1' : '',
						'L_ID' => _PNREFNO,
						'S_ID' => $row['aid'],
						'G_ICONS' => ($pnsettings['show_icons'] == '1') ? '1' : '',
						//Added for pn_art_foot_home.html
						'G_RATE' => ($pnsettings['ratings'] && is_user()) ? '1' : '',
						'S_RATE' => ($row['ratings'] != '0') ? _PNRATE : _PNFIRSTRATE,
						'T_RATE' => select_box('score'.$row['aid'],'0',array(0=>_PNSELECT,1=>$rsymbol,2=>$rsymbol.' '.$rsymbol,3=>$rsymbol.' '.$rsymbol.' '.$rsymbol,4=>$rsymbol.' '.$rsymbol.' '.$rsymbol.' '.$rsymbol,5=>$rsymbol.' '.$rsymbol.' '.$rsymbol.' '.$rsymbol.' '.$rsymbol)),
						'T_SUBMIT' => '<input type="hidden" name="aid" value="'.$row['id'].'" /><input type="submit" name="rate" value="'._PNRATE.'" class="pn_tinygrey" />',
						'G_SCORE' => ($pnsettings['ratings'] && $row['ratings'] != '0') ? '1' : '',
						'S_SCORE' => _PNRATING,
						'T_SCORE' => ($row['ratings'] != '0') ? $row['score']/$row['ratings'] : '',
						'G_PLINK' => $pnsettings['permalink'],
						'S_PLINK' => _PNLINK,
						'T_PLINK' => getlink("&amp;aid=".$row['id'].$url_text),
						'G_SENDPRINT' => '1',
						'G_EMAILF' => ($pnsettings['emailf'] && is_user()) ? '1' : '',
						'S_SENDART' => _PNSENDART,
						'U_SENDART' => getlink("&amp;mode=send&amp;id=".$row['id'].$url_text),
						'G_PRINTF' => ($pnsettings['printf']) ? '1' : '',
						'S_PRINTART' => _PNPRINTART,
						'U_PRINTART' => getlink("&amp;mode=prnt&amp;id=".$row['id'].$url_text),
						'G_STARTFORM' => open_form(getlink(),'rating'.$row['id'],'&nbsp;','class="pn_noborder"'),
						'G_ENDFORM' => close_form()
					));
					$cpgtpl->set_filenames(array('body' => 'pronews/article/'.$tpl));
					$lasttpl = $tpl;
					$last_sec = $row['stitle']; $last_cat = $row['ctitle'];
				}
			}
			pagination($module_name.($is_home ? '&amp;mode=home' : '').($sid ? '&amp;sid='.$sid : '').($cid ? '&amp;cid='.$cid :'').'&amp;page=', $pages, 1, $page);	// greenday2k
			require_once('header.php');
			$cpgtpl->display('body', false);
//			$cpgtpl->set_filenames(array('pagin' => 'pronews/pagination.html'));
			$cpgtpl->set_filenames(array('pagin' => 'pagination.html'));
			$cpgtpl->display('pagin', false);
		} else {
			$pagetitle .= $module_name;
			require_once('header.php');
			if (is_admin() || ProNews::in_group_list($pnsettings['mod_grp'])) {
				$rider = ($cid != '' ? _INCAT : ($sid != '' ? _INSEC : ''));
				$cpgtpl->assign_block_vars('newsnone', array('S_MSG' => _PNNOVIEWARTS." ".$rider));
				$cpgtpl->set_filenames(array('errbody' => 'pronews/article/'.$pnsettings['template']));
				$cpgtpl->display('errbody', false);
			}
		}
	}

 	function display_hdlines($sid='',$cid='',$usr='') {
		global $db, $prefix, $cpgtpl, $pnsettings, $CPG_SESS, $pagetitle, $home, $userinfo, $multilingual, $currentlang, $bgcolor3, $domain, $module_name, $Blocks, $gblsettings;
		$is_home = $home || (isset($_GET['mode']) ? Fix_Quotes($_GET['mode'],1) : '');
		if ((($home && $pnsettings['clrblks_hm'] == '2') || ($is_home && $pnsettings['clrblks_hm'] == '1')) && ($gblsettings['Version_Num'] >= "9.2.1")) {
// Remove the lines below - by adding // in columns 1-2 - if you do NOT want the left, center up/down, and/or right column blocks disabled when Pro_News Article is displayed on Home page
			$Blocks->l=-1;
			$Blocks->r=-1;
			$Blocks->c=-1;
			$Blocks->d=-1;
// end of left, center up/down, and right block disable code
		}
		ProNews::scheduler();					// check for scheduled activations/deactivations
		$is_hdln = $home || (isset($_GET['mode']) ? Fix_Quotes($_GET['mode'],1) : '');
		$arts_per_hdline = (isset($pnsettings['per_hdline']) && ($pnsettings['per_hdline'] > '0')) ? $pnsettings['per_hdline'] : '4';
		$artsortkey = $pnsettings['art_ordr'] / '2';
		$artsortord = ($pnsettings['art_ordr'] % '2')  ? 'DESC' : 'ASC';
		if ($artsortkey < 1) {
			$artsortfld = 'posttime';
		} elseif ($artsortkey < 2) {
			$artsortfld = 'a.title';
		} elseif ($artsortkey < 3) {
			$artsortfld = 'ratings';
		} else {
			$artsortfld = 'counter';
		}

// echo '<br /> artsortkey='.$artsortkey.' artsordfld='.$artsortfld.' artsordord='.$artsortord;

//		$sql = 'SELECT s.id sid, s.title stitle, s.view view, template, art_ord';
        //fishingfan change line below to get sectionheadline attributes
		$sql = 'SELECT s.id sid, s.title stitle, s.description sdescription, s.view view, s.secheadlines secheadlines, s.sectrunc1head sectrunc1head, s.sectrunchead sectrunchead, s.secdsplyby secdsplyby, s.forum_id sforum_id, template, art_ord, keyusrfld';
//		if ($pnsettings['display_by'] == '2') {
			$sql .= ', c.id cid, c.title ctitle, c.description cdescription, c.icon icon, c.forum_id cforum_id';
//		}
		$sql .= ' FROM 	'.$prefix.'_pronews_sections as s';
//		if ($pnsettings['display_by'] == '2') {
			$sql .= ' JOIN '.$prefix.'_pronews_cats as c ON c.sid=s.id AND secdsplyby<>"1"';
//		} else {
//			$sql .= ' JOIN '.$prefix.'_pronews_cats as c ON c.sid=s.id';
//		}
//		$sql .= ' WHERE s.in_home="1"';
		$homeval = ($is_home) ? (($home) ? ' s.in_home<>"0"' : ' s.in_home="1"') : '';
		if ($sid == '') {
			$sql .= ((in_array(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), (array)$domain) ? ' WHERE ('.$homeval.' OR s.in_home="'.array_search(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), $domain).'")' : ' WHERE '.$homeval));
		} else {
			$sql .= ' WHERE s.id="'.$sid.'"';
		}

		$sql .= ' ORDER BY s.sequence ASC';
		if ($pnsettings['display_by'] == '2') {
			$sql .= ', CASE secdsplyby WHEN 2 THEN c.sequence END ASC';
//		} else {
//			$sql .= ' CASE secdsplyby WHEN 0 THEN c.sequence WHEN 2 THEN c.sequence END ASC';
		}

//		if ($pnsettings['display_by'] == '0') {$sql .= ' LIMIT 1';}
		if ($sid == '' && $pnsettings['display_by'] == '0') {$sql .= ' LIMIT 1';}
		$result = $db->sql_query($sql);
		$listc = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);
		if (isset($listc) && $listc != '') {
			require_once('includes/nbbcode.php');
			$last_sec = ''; $last_cat = ''; $i = '0';
			$tpl = ''; $lasttpl = '';
			foreach ($listc as $keyc => $rowc) {

			// if List By Section only want to display 1 entry per Section so skip unless Section override is List by Category & Section
				if (!($pnsettings['display_by'] == '1' && $rowc['secdsplyby'] <> '2' && $rowc['stitle'] == $last_sec)) {



					if (!$home) {
						if ($pnsettings['SEOtitle']) {
							$pagetitle .= $row['ctitle'].($cid ? ' '._BC_DELIM.' <a href="'.getlink($module_name.'&amp;sid='.$row['sid']).'">'.$row['stitle'].'</a>' : '');
						} else {
							$pagetitle .= ' '._BC_DELIM.' '.($cid ? '<a href="'.getlink($module_name.'&amp;sid='.$row['sid']).'">'.$row['stitle'].'</a> '._BC_DELIM.' ' : '').$row['ctitle'];
						}
					} else {
						$pagetitle .= _PNHOMETEXT;
					}
					require_once('header.php');
//	print_r($rowc);
//	echo 'section='.$rowc['stitle'].' tpl='.$rowc['template'].' &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ';
					$r_artsrtky = ($rowc['art_ord'] -1)/ '2';
					$r_artsrtord = ($rowc['art_ord'] % '2')  ? 'ASC' : 'DESC';
					if ($r_artsrtky <= 1) {
						$r_artsrtfld = 'posttime';
					} elseif ($r_artsrtky <= 2) {
						$r_artsrtfld = 'a.title';
					} elseif ($r_artsrtky <= 3) {
						$r_artsrtfld = 'ratings';
					} else {
						$r_artsrtfld = 'counter';
					}
// echo ' r_artsrtky='.$r_artsrtky.' r_artsrdfld='.$r_artsrtfld.' r_artsrtord='.$r_artsrtord;

//	$pnsettings['display_by']				$rowc['secdsplyby']
//	0 - Articles							0 - Default
//	1 - Articles by Section					1 - Articles (within the Section)
//	2 - Articles by Category by Section		2 - Articles by Category (within the Section)

					if (($rowc['view'] == '0') || ($rowc['view'] == '3' && !is_user()) || (can_admin($module_name)) || (is_user() && (($rowc['view'] == '1') || (($rowc['view'] > 3) && (isset($userinfo['_mem_of_groups'][$rowc['view'] - 3])))))) {
						$sec_arts_per_hdline =  ($rowc['secheadlines']) ? $rowc['secheadlines'] : $arts_per_hdline;
						$sql = 'SELECT a.id aid, a.*';
// echo '<br /> display_by='.$pnsettings['display_by'].' secdsplyby='.$rowc['secdsplyby'].'<br />';
						if (!(($pnsettings['display_by'] == '2' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '2')) {
							$sql .= ', c.id cid, c.sid sid';
						}
						$sql .= ' FROM '.$prefix.'_pronews_articles as a';
						$sql .= ' JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';

		if ($pnsettings['actv_sort_ord'] == '1') {
			$sql .= ' LEFT JOIN '.$prefix.'_pronews_schedule as h ON a.id=h.id';
		}

						$by_article = 0;
						if (($pnsettings['display_by'] == '2' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '2') {	// 2 = List By Articles within Categories within Sections
							$sql .= ' WHERE a.catid='.$rowc['cid'];
						} elseif (($pnsettings['display_by'] == '1' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '1') {	// 1 = List By Articles within Sections
							$sql .= ' WHERE c.sid='.$rowc['sid'];
						} else {																			// 0 = List By Articles Only
							$sql .= ' JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
							$by_article = 1;
//							$sql .= ' WHERE s.in_home="1"';
							if ($sid == '') {
								$sql .= ((in_array(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), (array)$domain) ? ' WHERE (s.in_home="1" OR s.in_home="'.array_search(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), $domain).'")' : ' WHERE s.in_home="1"'));
							} else {
								$sql .= ' AND s.id="'.$sid.'"';
							}
						}
						$sql .= ' AND a.approved="1" AND a.active="1"';
						if ($by_article) {
							$sql .= ($is_home ? ' AND display<>"0"'.((in_array(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), (array)$domain) ? '  AND ('.$homeval.' OR s.in_home="'.array_search(ereg_replace('www.', '', $_SERVER['SERVER_NAME']), $domain).'")' : ' AND '.$homeval)) : ' AND display<>"2"');
						} else {
							$sql .= ' AND display<>"0"';
						}

						$sql .= $usr != '' ? ' AND postby="'.$usr.'"' : '';

						$sql .= ($multilingual ? " AND (alanguage='$currentlang' OR alanguage='')" : '');
						$sql .= ' ORDER BY display_order DESC';

//						if ($rowc['art_ord'] == 0) {
//							$sql .= ' '.$artsortfld.' '.$artsortord;
//						} else {
//							$sql .= ' '.$r_artsrtfld.' '.$r_artsrtord;
//						}

						$sql .= $rowc['art_ord'] == 0 ? ', '.$artsortfld.' '.$artsortord : '';
						$sql .= $rowc['art_ord'] == 1 ? ', posttime ASC' : '';
						$sql .= $rowc['art_ord'] == 2 ? ', posttime DESC' : '';
						$sql .= $rowc['art_ord'] == 3 ? ', a.title ASC' : '';
						$sql .= $rowc['art_ord'] == 4 ? ', a.title DESC' : '';
						$sql .= $rowc['art_ord'] == 5 ? ', ratings ASC' : '';
						$sql .= $rowc['art_ord'] == 6 ? ', ratings DESC' : '';
						$sql .= $rowc['art_ord'] == 7 ? ', a.counter ASC' : '';
						$sql .= $rowc['art_ord'] == 8 ? ', a.counter DESC' : '';
						if ($pnsettings['actv_sort_ord'] == '1') {
							$sql .= $rowc['art_ord'] == 9 ? ', h.dttime ASC' : '';
							$sql .= $rowc['art_ord'] == 10 ? ', h.dttime DESC' : '';
							$sql .= $rowc['art_ord'] == 11 ? ', h.dttime ASC' : '';
							$sql .= $rowc['art_ord'] == 12 ? ', h.dttime DESC' : '';
						}

						//	$sql .= ' LIMIT '.($arts_per_hdline+1);
						//fishingfan change line below
						$sql .= ' LIMIT '.($sec_arts_per_hdline + 1);
						$result = $db->sql_query($sql);
						$list = $db->sql_fetchrowset($result);

						$artcount = $db->sql_numrows($result);
						$db->sql_freeresult($result);

						if (isset($list) && $list != '' && count($list) > '0') {

							$z = 0;

							foreach ($list as $key => $row) {
// echo '<br />aid='.$row['aid'].' cat='.$rowc['cid'].' artcnt='.$artcount.' dispby='.$pnsettings['display_by'].' secdspby='.$rowc['secdsplyby'].' i='.$i.' lastsec='.$last_sec.' stitle='.$rowc['stitle'].' lastcat='.$last_cat.' ctitle='.$rowc['ctitle'];
								if (($pnsettings['display_by'] == '0' && $rowc['secdsplyby'] == '0' && $i == '0') || ((($pnsettings['display_by'] == '1' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '1') && ($key == '0' || $list[$key-1]['sid'] != $row['sid'])) || ((($pnsettings['display_by'] == '2' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '2') && ($key == '0' || $list[$key-1]['catid'] != $row['catid']))) {
									$i = 1;
									$bgcolor = "";
//  echo ' path= themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholder.png';	// remove // at beginning of line to display path
									if (($row['image'] != '') && ($row['image'] != '0')) {
										$imagesize = getimagesize($pnsettings['imgpath'].'/'.$row['image']);
										if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
											$thumbimage = $pnsettings['imgpath'].'/thumb_'.$row['image'];
											$full_image = $pnsettings['imgpath'].'/'.$row['image'];
										} else {
											$thumbimage = $pnsettings['imgpath'].'/'.$row['image'];
											$full_image = '';
										}														  // Check if thumb exists before linking - layingback 061122
										$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" />';
										$iconimage = $pnsettings['imgpath'].'/icon_'.$row['image'];
									} elseif ($pnsettings['show_noimage'] != '0' && file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholder.png')) {
										$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholder.png';
										$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="" />';
										$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholdermini.png';
									} elseif ($pnsettings['show_noimage'] != '0' && file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png')) {
										$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png';
										$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="" />';
										$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholdermini.png';
									} else {
										$display_image = '';
										$full_image = '';
										$thumbimage = '';
										$iconimage = '';
									}
									if ($row['intro'] == '') {
										if(strlen($row['content']) > $pnsettings['introlen']) {
											$text = substr_replace($row['content'],' ...',$pnsettings['introlen']);
											$morelink = '1';
										} else {
											$text = $row['content'];
											$morelink = '0';
										}
									} else {
										$max_intro = ($rowc['sectrunc1head']) ? $rowc['sectrunc1head'] : $pnsettings['hdln1len'];
										if (strlen($row['intro']) > $max_intro || $row['content'] != '' || $row['user_fld_'.$rowc['keyusrfld']] !='' || $row['album_id'] != '0' || $row['image2'] != '' || $row['associated']) {
											$morelink = '1';
										} else {
											$morelink = '';
										}
// echo '<br>aid='.$row['aid'].' : '.(strlen($row['intro']) > $pnsettings['hdlnlen']).' : '.($row['content'] != '').' : '.($row['user_fld_0'] !='').' : '.($row['album_id'] != '0').' : '.($row['image2'] != '').' ml='.$morelink;
										if (strlen($row['intro']) > $max_intro) {
										//	$text = substr_replace($row['intro'],' ...',$pnsettings['hdln1len']-strlen(' ...'));
										//fishingfan change 2 lines below for section changes
											$row['intro'] = ProNews::stripBBCode($row['intro']);
											$text = substr_replace($row['intro'], ' ...', $max_intro);
										} else {
											$text = $row['intro'];
										}
									}
									if ( can_admin($module_name) || ($row['sadmin'] == '0') || (($row['sadmin'] == '1') && (is_user())) || (($row['sadmin'] > '3') && (is_user()) && (in_group($row['sadmin']-3)) && ($row['postby'] == $userinfo['username']))) {
										$morelink = '1';
									}
									if (($pnsettings['display_by'] == '2' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '2') {
										$artlink = getlink("&amp;cid=".$row['catid']);
									} elseif (($pnsettings['display_by'] == '1' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '1') {
										$artlink = getlink("&amp;sid=".$rowc['sid']);
									} else {
										$artlink = getlink("&amp;mode=home");
									}
									if (can_admin($module_name)) {
										$canedit = "2";
										$editlink = adminlink('&amp;mode=add&amp;do=edit&amp;id=').$row['aid'];
										$editlabel = ($morelink == '1') ? _PNSECADMIN : '<a href="'.adminlink("&amp;mode=list&amp;do=sort&amp;cat=".$row['catid']).'">'._PNSECADMIN.'</a>';       // inline Edit - layingback 061201
									} elseif (($row['sadmin'] == '0') || (($row['sadmin'] == '1') && (is_user())) || (($row['sadmin'] > '3') && (is_user()) && (in_group($row['sadmin']-3)) && ($row['postby'] == $userinfo['username']))) {
										$canedit = $row['sadmin'];
										$editlink = getlink("&amp;mode=submit&amp;do=edit&amp;id=".$row['aid']);
										$editlabel = _PNEDITARTICLE;     // User as member of authorised group edit - layingback 061201
									} else {
										$canedit = '';
									}
									$url_text = ($pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace('&amp;','',($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $rowc['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $rowc['ctitle'].'/' : '').$row['title']) : ($pnsettings['sec_in_url'] ? $rowc['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $rowc['ctitle'].'/' : '').$row['title']))))) : '');
									$tpl = ($rowc['template'] != '') ? $rowc['template'] : $pnsettings['template'];
									if ($pnsettings['display_by'] == '0') { $tpl = $pnsettings['template']; }
// echo ' theme='.$CPG_SESS['theme'].' tpl='.$tpl.' lasttpl='.$lasttpl.' #='.$artcnt++.'<br>';
									if ($tpl <> $lasttpl && $lasttpl <> '') {
										$cpgtpl->display('body', false);
										$cpgtpl->unset_block('newshdline');
									}
// echo 'sizeof list='.sizeof($list).' '.((sizeof($list) > 1) ? '1' : '').' > #/hdline='.((sizeof($list) > $sec_arts_per_hdline && $sec_arts_per_hdline > '1') ? '1' : '');
// echo '<br />main i='.$i.' #='.sizeof($list).' first='.($rowc['sid'] != $listc[$keyc-1]['sid']).' art p/sec='.$sec_arts_per_hdline.' art p/='.$pnsettings['per_hdline'].' sid='.$rowc['sid'].' cid='.$row['catid'].' cid+1='.$list[$key+1]['catid'].' sid+1='.$listc[$keyc+1]['sid'].' last='.(/*$i == $sec_arts_per_hdline ||*/ $rowc['sid'] != $listc[$keyc+1]['sid'] /*|| $row['catid'] != $list[$key+1]['catid']*/);
									$cpgtpl->assign_block_vars('newshdline', array(
										'G_SECBRK' => (($pnsettings['display_by'] != '0' || $rowc['secdsplyby'] != '0') && ($pnsettings['secat_hdgs'] == '1' || $pnsettings['secat_hdgs'] == '3') && $rowc['stitle'] != $last_sec) ? '1' : '0',
										'S_SECBRK' => ProNews::getsctrnslt('_PN_SECTITLE_', $rowc['stitle'], $rowc['sid']),
										'U_SECDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_SECDESC_', $rowc['sdescription'], $rowc['sid']), 1, true)),
										'G_CATBRK' => ((($pnsettings['display_by'] == '2' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '2') && $pnsettings['secat_hdgs'] >= '2' && $rowc['ctitle'] != $last_cat) ? '1' : '0',
										'S_CATBRK' => ProNews::getsctrnslt('_PN_CATTITLE_', $rowc['ctitle'], $rowc['cid']),
										'U_CATDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_CATDESC_', $rowc['cdescription'], $rowc['cid']), 1, true)),
										'T_SECBRK' => getlink("&amp;sid=".$rowc['sid']),
										'T_CATBRK' => getlink("&amp;cid=".$rowc['cid']),
										'G_FIRSTART' => '1',
//										'G_LASTART' => (/*$i == $sec_arts_per_hdline ||*/ $rowc['sid'] != $listc[$keyc+1]['sid'] /*|| $row['catid'] != $list[$key+1]['catid']*/) ? '1' : '0',
										'G_LASTART' => ($rowc['sid'] != $listc[$keyc+1]['sid'] || sizeof($list) == 1) ? '1' : '0',
										'L_PAUSED' => ' '._PNPAUSED.' ',
										'S_INTRO' => decode_bb_all($text, 1, true),  // true param added for images - layingback
										'S_TITLE' => $row['title'],
//										'S_ICON' => (($pnsettings['display_by'] == '2' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '2') ? ($rowc['icon'] != '') ? $rowc['icon'] : 'clearpixel.gif' : 'clearpixel.gif',
										'S_ICON' => ($rowc['icon'] != '') ? $rowc['icon'] : 'clearpixel.gif',
										'T_ICON' => (($pnsettings['display_by'] == '2' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '2') ? $rowc['ctitle'] : '',
										'L_POSTBY' => _PNPOSTBY,
										'S_POSTBY' => $row['postby'],
										'T_POSTBY' => getlink("Your_Account&amp;profile=".$row['postby']),
										'L_POSTON' => _PNPOSTON,
										'S_POSTTIME' => ProNews::create_date(false, $row['posttime']),
										'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
										'S_CATEGORY' => $row['catid'],
										'S_CATLINK' => getlink("&amp;cid=".$row['catid']),
										'S_IMAGE' => ($row['image'] == '' && $pnsettings['show_noimage'] == '0') ? '' : $display_image,
										'T_FULLIMAGE' => $full_image,
										'T_THUMBIMAGE' => $thumbimage,
										'T_ICONIMAGE' => $iconimage,
										'G_SHOW_READS' => $pnsettings['show_reads'],
										'S_READS' => ($row['counter'] != '1') ? _PNREADS : _PNREAD,
										'T_READS' => $row['counter'],
										'U_MORELINK' => getlink("&amp;aid=".$row['aid'].$url_text),
										'S_MORELINK' => _PNMORE,
										'G_MORELINK' => $morelink,
										'U_DISCUSS' => getlink("&amp;discuss=".$row['aid']),
										'S_DISCUSS' => ($row['topic_id'] ? _PNDISCUSSION : _PNDISCUSS),
										'G_DISCUSS' => (!($rowc['sforum_id'] == '0' && $rowc['cforum_id'] == '0') && $rowc['cforum_id'] != -1 && $row['allow_comment'] == '1' && $pnsettings['comments'] == '1') ? '1' : '',
										'L_DISCUSS' => ($row['topic_id'] ? _PNDISCUSSION : _PNDISCUSSNEW),
										'S_NUMCMNTS' => ($row['topic_id'] ? '1' : ''),
										'G_PLINK' => $pnsettings['permalink'],
										'S_PLINK' => _PNLINK,
										'T_PLINK' => getlink("&amp;aid=".$row['id'].$url_text),
										'G_SENDPRINT' => '1',
										'G_EMAILF' => ($pnsettings['emailf'] && is_user()) ? '1' : '',
										'S_SENDART' => _PNSENDART,
										'U_SENDART' => getlink("&amp;mode=send&amp;id=".$row['id'].$url_text),
										'G_PRINTF' => ($pnsettings['printf']) ? '1' : '',
										'S_PRINTART' => _PNPRINTART,
										'U_PRINTART' => getlink("&amp;mode=prnt&amp;id=".$row['id'].$url_text),
										'G_SOCIALNET' => $pnsettings['soc_net'],
										'U_SOCNETLINK' => urlencode($BASEHREF.getlink("&amp;aid=".$row['id'])),
										'S_SOCTITLE' => urlencode($row['title']),
										'G_ICONS' => ($pnsettings['show_icons'] == '1') ? '1' : '',
										'L_ID' => _PNREFNO,
										'S_ID' => $row['aid'],
//										'G_HDLINES' => (sizeof($list) > $arts_per_hdline) ? '1' : '',
										//fishingfan change line below from above(commented out)
										'G_MOREHDLINES' => (sizeof($list) > 1) ? '1' : '',
										'G_HDLINES' => (sizeof($list) > $sec_arts_per_hdline && $sec_arts_per_hdline > '1') ? '1' : '',
										'U_ARTLINK' => $artlink,
										'U_ALLLINK' => getlink("&amp;mode=home"),
										'L_HDLINES' => _PNLHDLINES,
										'S_HDLINES' => _PNALL,
										'U_CANADMIN' => $editlink,
										'S_CANADMIN' => _PNEDIT,
										'T_CANADMIN' => $editlabel,
										'G_CANADMIN' => ($canedit != '') ? '1' : '',
									));
									$cpgtpl->set_filenames(array('body' => 'pronews/article/'.$tpl));
									$lasttpl = $tpl;
									$last_sec = $rowc['stitle']; $last_cat = (($pnsettings['display_by'] == '2' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '2') ? $rowc['ctitle'] : '';
								} else {
//  echo ' path= themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholder.png';	// remove // at beginning of line to display path
									if ($i < $sec_arts_per_hdline) {
									//fishingfan line below change (from above commented out)
									//	if ($i < $rowc['secheadlines']) {
										if (($row['image'] != '') && ($row['image'] != '0')) {
											$imagesize = getimagesize($pnsettings['imgpath'].'/'.$row['image']);
											if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
												$thumbimage = $pnsettings['imgpath'].'/thumb_'.$row['image'];
												$full_image = $pnsettings['imgpath'].'/'.$row['image'];
											} else {
												$thumbimage = $pnsettings['imgpath'].'/'.$row['image'];
												$full_image = '';
											}
											if (file_exists($pnsettings['imgpath'].'/icon_'.$row['image'])) {
												$iconimage = $pnsettings['imgpath'].'/icon_'.$row['image'];
												$display_image = '<img class="pn_image" src="'.$iconimage.'" alt="'.$row['caption'].'" />';
											} else {
												$iconimage = '';
												$display_image = '<img class="pn_image" width="'.($pnsettings['max_w'] / 5).'" height="'.($pnsettings['max_h'] / 5).'" src="'.$thumbimage.'" alt="'.$row['caption'].'" />';
											}
										} elseif ($pnsettings['show_noimage'] != '0' && file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholdermini.png')) {
											$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholdermini.png';
											$display_image = '<img class="pn_image" src="'.$iconimage.'" alt="" />';
											$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $rowc['stitle'])).'/imageholder.png';
										} elseif ($pnsettings['show_noimage'] != '0' && file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholdermini.png')) {
											$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholdermini.png';
											$display_image = '<img class="pn_image" src="'.$iconimage.'" alt="" />';
											$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png';
										} else {
											$display_image = '';
											$full_image = '';
											$iconimage = '';
											$thumbimage = '';
										}
										if ($row['intro'] == '') {
											if(strlen($row['content']) > $pnsettings['introlen']) {
												$text = substr_replace($row['content'],' ...',$pnsettings['introlen']);
												$morelink = '1';
											} else {
												$text = $row['content'];
												$morelink = '0';
											}
										} else {
											$max_intro = ($rowc['sectrunchead']) ? $rowc['sectrunchead'] : $pnsettings['hdlnlen'];
											if (strlen($row['intro']) > $max_intro || $row['content'] != '' || $row['user_fld_'.$rowc['keyusrfld']] !='' || $row['album_id'] != '0' || $row['image2'] != '' || $row['associated']) {
												$morelink = '1';
											} else {
												$morelink = '0';
											}
											if (strlen($row['intro']) > $max_intro) {
												$row['intro'] = ProNews::stripBBCode($row['intro']);
												$text = substr_replace($row['intro'], ' ...', $max_intro);
											} else {
												$text = $row['intro'];
											}
//										$textlen = ($i == '1') ? $pnsettings['hdlnlen'] : $pnsettings['hdlnlen'];
											//if (strlen($row['intro']) > $pnsettings['hdlnlen'] || $row['content'] != '' || $row['user_fld_0'] !='' || $row['album_id'] != '0' || $row['image2'] != '') {$morelink = '1';} else {$morelink = '0';}
											//$text = substr_replace($row['intro'],'...',$pnsettings['hdlnlen'] - strlen('...'));
											//fishingfan change 2 lines comment out above to below for section headline changes
//                                        if (strlen($row['intro']) > $rowc['sectrunchead'] || $row['content'] != '' || $row['user_fld_0'] !='' || $row['album_id'] != '0' || $row['image2'] != '') {$morelink = '1';} else {$morelink = '0';}
//										$text = substr_replace($row['intro'],'...',$rowc['sectrunchead'] - strlen('...'));
										}
										if ( can_admin($module_name) || ($row['sadmin'] == '0') || (($row['sadmin'] == '1') && (is_user())) || (($row['sadmin'] > '3') && (is_user()) && (in_group($row['sadmin']-3)) && ($row['postby'] == $userinfo['username']))) {
											$morelink = '1';
										}
										if (($pnsettings['display_by'] == '2' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '2') {
											$titlebrk = $rowc['stitle'].' '._BC_DELIM.' '.$rowc['ctitle'];
										} elseif (($pnsettings['display_by'] == '1' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '1') {
											$titlebrk = $rowc['stitle'];
										} else {
											$titlebrk = '';
										}
										if (can_admin($module_name)) {
											$canedit = "2";
											$editlink = adminlink('&amp;mode=add&amp;do=edit&amp;id=').$row['aid'];
											$editlabel = ($morelink == '1') ? _PNSECADMIN : '<a href="'.adminlink("&amp;mode=list&amp;do=sort&amp;cat=".$row['catid']).'">'._PNSECADMIN.'</a>';       // inline Edit - layingback 061201
										} elseif (($row['sadmin'] == '0') || (($row['sadmin'] == '1') && (is_user())) || (($row['sadmin'] > '3') && (is_user()) && (in_group($row['sadmin']-3)) && ($row['postby'] == $userinfo['username']))) {
											$canedit = $row['sadmin'];
											$editlink = getlink("&amp;mode=submit&amp;do=edit&amp;id=".$row['aid']);
											$editlabel = _PNEDITARTICLE;     // User as member of authorised group edit - layingback 061201
										} else {
											$canedit = '';
										}
										$bgcolor = ($bgcolor == '') ? ' style="background-color: '.$bgcolor3.'"' : '';
// echo '<br />addtl i='.$i.' #='.sizeof($list).' first='.($rowc['sid'] != $listc[$keyc-1]['sid']).' art p/sec='.$sec_arts_per_hdline.' art p/='.$pnsettings['per_hdline'].' sid='.$rowc['sid'].' cid='.$row['catid'].' cid+1='.$list[$key+1]['catid'].' sid+1='.$listc[$keyc+1]['sid'].' last='.(/*$i == $sec_arts_per_hdline ||*/ $row['catid'] != $list[$key+1]['catid']  || $i+1 >= sizeof($list) || $i+2 > $sec_arts_per_hdline /*|| $row['catid'] != $list[$key+1]['catid']*/);
										$cpgtpl->assign_block_vars('newshdline.hdline_addtl', array(
											'S_SECBRK' => ProNews::getsctrnslt('_PN_SECTITLE_', $rowc['stitle'], $rowc['sid']),
											'U_SECDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_SECDESC_', $rowc['sdescription'], $rowc['sid']), 1, true)),
											'S_CATBRK' => ProNews::getsctrnslt('_PN_CATTITLE_', $rowc['ctitle'], $rowc['cid']),
											'U_CATDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_CATDESC_', $rowc['cdescription'], $rowc['cid']), 1, true)),
											'T_SECBRK' => getlink("&amp;sid=".$rowc['sid']),
											'T_CATBRK' => getlink("&amp;cid=".$rowc['cid']),
											'U_BGCOLOR' => $bgcolor,
//											'G_LASTART' => (/*$i == $sec_arts_per_hdline ||*/ $rowc['sid'] != $listc[$keyc+1]['sid'] /* || $row['catid'] != $list[$key+1]['catid'] */) ? '1' : '0',
											'G_FIRSTART' => '0',
											'G_LASTART' => isset($list[$key+1]) && ($row['catid'] != $list[$key+1]['catid'] || $i+1 >= sizeof($list) || $i+2 > $sec_arts_per_hdline) ? '1' : '0',
											'S_INTRO' => decode_bb_all($text, 1, true),  // true param added for images - layingback
											'S_TITLE' => $row['title'],
											'S_ICON' => (($pnsettings['display_by'] == '2' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '2') ? ($rowc['icon'] != '') ? $rowc['icon'] : 'clearpixel.gif' : 'clearpixel.gif',
											'T_ICON' => (($pnsettings['display_by'] == '2' && $rowc['secdsplyby'] == '0') || $rowc['secdsplyby'] == '2') ? $rowc['ctitle'] : '',
											'L_POSTBY' => _PNPOSTBY,
											'S_POSTBY' => $row['postby'],
											'T_POSTBY' => getlink("Your_Account&amp;profile=".$row['postby']),
											'L_POSTON' => _PNPOSTON,
											'S_POSTTIME' => ProNews::create_date(false, $row['posttime']),
											'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
											'S_CATEGORY' => $row['catid'],
											'S_CATLINK' => getlink("&amp;cid=".$row['catid']),
											'S_IMAGE' => ($row['image'] == '' && $pnsettings['show_noimage'] == '0') ? '' : $display_image,
											'T_FULLIMAGE' => $full_image,
											'T_ICONIMAGE' => $iconimage,
											'T_THUMBIMAGE' => $thumbimage,
											'G_SHOW_READS' => $pnsettings['show_reads'],
											'S_READS' => ($row['counter'] != '1') ? _PNREADS : _PNREAD,
											'T_READS' => $row['counter'],
											'U_MORELINK' => getlink("&amp;aid=".$row['aid'].$url_text),
											'S_MORELINK' => _PNMORE,
											'G_MORELINK' => $morelink,
											'U_DISCUSS' => getlink("&amp;discuss=".$row['aid']),
											'S_DISCUSS' => ($row['topic_id'] ? _PNDISCUSSION : _PNDISCUSS),
											'G_DISCUSS' => (!($rowc['sforum_id'] == '0' && $rowc['cforum_id'] == '0') && $rowc['cforum_id'] != -1 && $row['allow_comment'] == '1' && $pnsettings['comments'] == '1') ? '1' : '',
											'L_DISCUSS' => ($row['topic_id'] ? _PNDISCUSSION : _PNDISCUSSNEW),
											'S_NUMCMNTS' => ($row['topic_id'] ? '1' : ''),
											'G_PLINK' => $pnsettings['permalink'],
											'S_PLINK' => _PNLINK,
											'T_PLINK' => getlink("&amp;aid=".$row['id'].$url_text),
											'G_SENDPRINT' => '1',
											'G_EMAILF' => ($pnsettings['emailf'] && is_user()) ? '1' : '',
											'S_SENDART' => _PNSENDART,
											'U_SENDART' => getlink("&amp;mode=send&amp;id=".$row['id'].$url_text),
											'G_PRINTF' => ($pnsettings['printf']) ? '1' : '',
											'S_PRINTART' => _PNPRINTART,
											'U_PRINTART' => getlink("&amp;mode=prnt&amp;id=".$row['id'].$url_text),
											'G_SOCIALNET' => $pnsettings['soc_net'],
											'U_SOCNETLINK' => urlencode($BASEHREF.getlink("&amp;aid=".$row['id'])),
											'S_SOCTITLE' => urlencode($row['title']),
											'G_ICONS' => ($pnsettings['show_icons'] == '1') ? '1' : '',
											'L_ID' => _PNREFNO,
											'S_ID' => $row['aid'],
											'U_CANADMIN' => $editlink,
											'S_CANADMIN' => _PNEDIT,
											'T_CANADMIN' => $editlabel,
											'G_CANADMIN' => ($canedit != '') ? '1' : '',

											'S_ARTCOUNT' => min($artcount, $sec_arts_per_hdline),
											'S_ARTINDEX' => $z

										));
										$i++;
									}
								}
								$z++;
							}
						} else {
							if (is_admin()) {
								require_once('header.php');
								$cpgtpl->assign_block_vars('basicempty', array('S_MSG' => '<span class="pn_tinygrey">'._PNSECTION.': '.$rowc['stitle'].' (id='.$rowc['sid'].') '.(isset($rowc['cid']) ? '- '._PNCAT.': '.$rowc['ctitle'].' (id='.$rowc['cid'].')' : '').' - '._PNNOVIEWARTS.'</span>'));
								$cpgtpl->set_filenames(array('err' => 'pronews/basic.html'));
								$cpgtpl->display('err', false);
								$cpgtpl->unset_block('basicempty');
							}
						}
					}


				}


			}
// echo ' final tpl='.$tpl.' lasttpl='.$lasttpl.'<br>';
			if ($tpl == '') {
				require_once('header.php');
				$cpgtpl->assign_block_vars('basicempty', array('S_MSG' => _PNNOVIEWARTS));
				$cpgtpl->set_filenames(array('err' => 'pronews/basic.html'));
				$cpgtpl->display('err', false);
				$cpgtpl->unset_block('basicempty');
			} else {
				$cpgtpl->display('body', false);
				$cpgtpl->unset_block('newshdline');
			}
		} else {
			require_once('header.php');
			$cpgtpl->assign_block_vars('newsnone', array('S_MSG' => _PNNOVIEWARTS));
			$cpgtpl->set_filenames(array('errbody' => 'pronews/article/'.$pnsettings['template']));
			$cpgtpl->display('errbody');
		}
	}

	function seccat($name, $id='', $byforum=false, $forum='', $post=false, $onclick=true) {
		global $db, $prefix, $userinfo, $pnsettings, $module_name;
		$sql = 'SELECT s.title stitle, s.id sid, s.view, s.post, c.title, c.id';
		$sql .= ' FROM '.$prefix.'_pronews_sections as s';
		$sql .= ' JOIN '.$prefix.'_pronews_cats as c ON c.sid=s.id';   // dropped LEFT to exclude case where no Cat - layingback
		$sql .= ' WHERE s.id > "0"';
		if ($byforum) {$sql .= ' AND s.forumspro_name="'.$forum.'"';}
		$sql .= ' ORDER BY s.sequence, c.sequence';
//		$list = $db->sql_fetchrowset($db->sql_query($sql));
		$result = $db->sql_query($sql);
		$list = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);
		if (($list) && ($list != '')) {
			$list2 = array();
			foreach ($list as $row) {
				if ($post) {
					if ($row['post'] > 3) {$rownum = $row['post']-3;} else {$rownum = $row['post'];}
				} else {
					if ($row['view'] > 3) {$rownum = $row['view']-3;} else {$rownum = $row['view'];}
				}
				if (((is_user() && can_admin($module_name)) || (is_user() && isset($userinfo['_mem_of_groups'][$rownum])) || ($rownum == '0') || (is_user() && $rownum == '1') || (!is_user() && $rownum == '3'))
				&& !($row['sid'] == '1' && $pnsettings['member_pages'] == '0')) {
					$list2[$row['stitle']][$row['title']][] = $row['id'];
					$list2[$row['stitle']][$row['title']][] = $row['sid'];
				}
			}
// print_r($list2);
			if (($list2) && ($list2 != '')) {
				$selected = ($id != '') ? $id : _PNSELONE;
				$seccat = '<select'.($onclick ? ' onchange="this.form.submit()" name="'.$name.'"' : ' name="'.$name.'"').'><option value="">-- '._PNSELONE.' --</option>';
				foreach ($list2 as $row => $value) {
					$seccat .= '<optgroup label="'.$row.'">';
					foreach ($value as $op => $tid) {
						foreach ($tid as $op2 => $dup) {
							if ($op2 % 2 == 0) {		// only process even indexes - odd used to hold sid
								$select = ($dup == $selected) ? ' selected="selected"' : '';
								$seccat .= '<option value="'.($tid['1'] == 1 ? $dup * -1 : $dup).'"'.$select.'>'.$op.'</option>';
							}
						}
					}
					$seccat .= '</optgroup>';
				}
				$seccat .= '</select>';
			} else {
				$seccat = '';
			}
		}
		return $seccat;
	}

	function discussThis($sid) {
		global $prefix, $user_prefix, $db, $board_config, $pnsettings;
		$module_name = 'Forums';			// Added for DragonflyCMS 9.2.3CVS
		// grab the story

		// first: check whether in category level it has been assigned to certain forum_id! modified by Masino Sinaga, June 22, 2009
		$sql = 'SELECT a.*, c.title as ctitle, c.forum_id as cforum_id, c.forum_module as cforum_module, c.forumspro_name cforumspro_name, s.title as stitle, s.forum_id as sforum_id, s.forum_module as sforum_module, s.forumspro_name as sforumspro_name';
		$sql .= ' FROM '.$prefix.'_pronews_articles as a LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id LEFT JOIN '.$prefix.'_pronews_sections as s';
		$sql .= ' ON c.sid=s.id WHERE a.id ='.$sid;
		$res = $db->sql_query($sql);
		if ($db->sql_numrows($res) != 1) { url_redirect(getlink()); }
//		$db->sql_freeresult($res);
		$story = $db->sql_fetchrow($res);
		$db->sql_freeresult($res);

		$url_text = $pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace('&amp;','',($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $story['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $story['ctitle'].'/' : '').$story['title']) : ($pnsettings['sec_in_url'] ? $story['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $story['ctitle'].'/' : '').$story['title']))))) : '';

		$forum_module = ($story['cforum_module'] && $story['cforum_module'] != 0) ? $story['cforum_module'] : $story['sforum_module'];
		$forumspro_name = ($story['cforumspro_name']) ? $story['cforumspro_name'] : $story['sforumspro_name'];
		$forum_id = ($story['cforum_id'] && $story['cforum_id'] != 0) ? $story['cforum_id'] : $story['sforum_id'];

		$forum_topic = $story['topic_id'];

//		$forum_id = $story['cforum_id'];
//		if ($story['cforum_id']==0) {
//		  $res = $db->sql_query('SELECT a.*,s.forum_id,s.forum_module,s.forumspro_name FROM '.$prefix.'_pronews_articles as a LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id WHERE a.id ='.$sid);
//		  if ($db->sql_numrows($res) != 1) { url_redirect(getlink()); }
//		  $story = $db->sql_fetchrow($res);
//		  $db->sql_freeresult($res);
//		} else {
//		}

		// include the relevant files
		require_once('modules/Forums/nukebb.php');
		require_once('modules/Forums/common.php');
		require_once('modules/Your_Account/functions.php');
		require_once('includes/nbbcode.php');
// print_r($forum_module);
		if ( $forum_module == 2) {  // Check if ForumsPro is selected - Sarah
			//require_once('includes/ForumsPro/functions.php');  // Include ForumsPro functions - Sarah
			$forumspro_prefix = $prefix.'_'.strtolower($forumspro_name).'_';
			// if the topic already exists, redirect to it
			// ... but check it hasn't been deleted first - layingback 071206
			if( $forum_topic ) {
				$res = $db->sql_query('SELECT topic_title FROM '.$forumspro_prefix.'topics WHERE topic_id ='.$forum_topic);
				if ($db->sql_numrows($res) != 1) { $forum_topic = ''; }
				$db->sql_freeresult($res);
			}
			if( $forum_topic ) {
				if ($forumspro_name == 'fpro') {
					url_redirect(getlink('ForumsPro&file=viewtopic&'.POST_TOPIC_URL.'='.$forum_topic).'#'.$forum_topic);
				} else {
					url_redirect(getlink(''.$forumspro_name.'&file=viewtopic&'.POST_TOPIC_URL.'='.$forum_topic).'#'.$forum_topic);
				}
			} else {
				// check users forum access rights
				$res = $db->sql_query('SELECT auth_post, auth_reply FROM '.$forumspro_prefix.'forums WHERE forum_id ='.$forum_id);
				$list = $db->sql_fetchrow($res);
				$db->sql_freeresult($res);
				if (is_admin()) {
					$user_auth = '5';
				} elseif ($userinfo['user_level'] == '3') {
					$user_auth = '3';
				} else {
					if (is_user()) {
						$user_auth = '1';
					} else {
						$user_auth = '0';
					}
				}
//				if (($list['auth_post'] > $user_auth) && ($list['auth_reply'] > $user_auth)) {		// uncomment and replace line below if you want legacy behaviour: post rights required
				if ($list['auth_reply'] > $user_auth) {												// only reply rights required
//				if ($list['auth_read'] > $user_auth) {												// only read rights required
					if ($list['auth_reply'] == '5') {
						cpg_error ('<br /><br /><strong>'._RESTRICTEDAREA.'</strong><br /><br />'._MODULESADMINS, 401);
					} else {
						url_redirect(getlink('Your_Account'), true);
					}
				}

				// compile the necessary data
				$username     = $story['postby'];
				list($userid) = $db->sql_fetchrow($db->sql_query("SELECT user_id FROM ".$user_prefix."_users WHERE username='$username'"));
				if ($userid == '') { $userid = '1'; }
				$subject      = $story['title'];
				$message      = ($pnsettings['postlink']) ? _PNCLK2FLLW.' [url='.getlink("&amp;aid=".$story['id'].$url_text).']'._PNORIGARTCL.'[/url].' : $story['intro'].' [url='.getlink("&amp;aid=".$story['id'].$url_text).']'._PNREADMORE.'[/url].';
				$current_time = $story['posttime'];
				$mode         = 'newtopic' ;
				$error_msg    = '';
				$post_data    = array();
				$topic_type   = POST_NORMAL ;
				$message      = Fix_Quotes(message_prepare($message, 1, 1));
				$subject      = htmlprepare($subject, false, ENT_QUOTES, true);
//				$forum_id     = $story['forum_id'];
				// create a new topic
				$sql = "INSERT INTO ".$forumspro_prefix."topics (topic_title, topic_poster, topic_time, forum_id, topic_status, topic_type, topic_vote, icon_id) VALUES ('$subject', $userid, $current_time, $forum_id, ".TOPIC_UNLOCKED.", 0, 0, 0)";
				$db->sql_query($sql);
				$forum_topic_id = $db->sql_nextid('topic_id');  // Set our topic_id variable for ForumsPro - Sarah
				// make forum post
				$sql = "INSERT INTO ".$forumspro_prefix."posts (topic_id, forum_id, poster_id, post_username, post_time, poster_ip, enable_bbcode, enable_html, enable_smilies, enable_sig) VALUES ($forum_topic_id, $forum_id, $userid, '$username', $current_time, '$user_ip', 1, 1, 1, 0)";
				$db->sql_query($sql);
				$post_id = $db->sql_nextid('post_id');
				// store post text
				$sql = "INSERT INTO ".$forumspro_prefix."posts_text (post_id, post_subject, post_text) VALUES ($post_id, '$subject', '$message')" ;
				$db->sql_query($sql);
				// update topic id in the story
				$sql = 'UPDATE '.$prefix.'_pronews_articles SET topic_id='.$forum_topic_id.' WHERE id='.$sid;  // Update with ForumsPro topic id - Sarah
				$db->sql_query($sql);
				// housekeeping, update last post
				$sql = 'UPDATE '.$forumspro_prefix.'forums SET forum_posts = forum_posts + 1, forum_last_post_id = 5, forum_topics = forum_topics + 1 WHERE forum_id='.$forum_id;
				$db->sql_query($sql);
				$sql = 'UPDATE '.$forumspro_prefix.'topics SET topic_last_post_id='.$post_id.', topic_first_post_id='.$post_id.' WHERE topic_id='.$forum_topic_id;
				$db->sql_query($sql);
				// view the newly created forum thread
				if ($forumspro_name == 'fpro') {
					url_redirect(getlink('ForumsPro&file=viewtopic&'.POST_TOPIC_URL.'='.$forum_topic_id).'#'.$topic_id);
				} else {
					url_redirect(getlink(''.$forumspro_name.'&file=viewtopic&'.POST_TOPIC_URL.'='.$forum_topic_id).'#'.$topic_id);
				}
			}

		} else {  // Otherwise include the CPGBB functions - Sarah
			require_once('includes/phpBB/functions.php');
			require_once('includes/phpBB/functions_post.php');
			require_once('includes/phpBB/functions_search.php');
			// if the topic already exists, redirect to it
			// ... but check it hasn't been deleted first - layingback 071206
			if( $forum_topic ) {
				$res = $db->sql_query('SELECT topic_title FROM '.TOPICS_TABLE.' WHERE topic_id ='.$forum_topic);
				if ($db->sql_numrows($res) != 1) { $forum_topic = ''; }
				$db->sql_freeresult($res);
			}
			if ($forum_topic) {
				url_redirect(getlink('Forums&file=viewtopic&'.POST_TOPIC_URL.'='.$forum_topic).'#'.$forum_topic);
			} else {
				// check users forum access rights
				$res = $db->sql_query('SELECT auth_post, auth_reply FROM '.$user_prefix.'_bbforums WHERE forum_id ='.$forum_id);
				$list = $db->sql_fetchrow($res);
				$db->sql_freeresult($res);
				if (is_admin()) {
					$user_auth = '5';
				} elseif ($userinfo['user_level'] == '3') {
					$user_auth = '3';
					} else {
					if (is_user()) {
						$user_auth = '1';
					} else {
						$user_auth = '0';
					}
				}
//				if (($list['auth_post'] > $user_auth) && ($list['auth_post'] > $user_auth)) {		// uncomment and replace line below if you want legacy behaviour: post rights required
				if ($list['auth_reply'] > $user_auth) {												// only reply rights required
//				if ($list['auth_read'] > $user_auth) {												// only read rights required
					if ($list['auth_reply'] == '5') {
						cpg_error ('<br /><br /><strong>'._RESTRICTEDAREA.'</strong><br /><br />'._MODULESADMINS, 401);
					} else {
						url_redirect(getlink('Your_Account'), true);
					}
				}

				// compile the necessary data
				$username     = $story['postby'];
				list($userid) = $db->sql_fetchrow($db->sql_query("SELECT user_id FROM ".$user_prefix."_users WHERE username='$username'"));
				if ($userid == '') { $userid = '1'; }
				$subject      = $story['title'];
				$message      = ($pnsettings['postlink']) ? _PNCLK2FLLW.' [url='.getlink("&amp;aid=".$story['id'].$url_text).']'._PNORIGARTCL.'[/url].' : $story['intro'].' &nbsp; [url='.getlink("&amp;aid=".$story['id'].$url_text).']'. _PNREADMORE.'[/url].';
				$current_time = $story['posttime'];
				$mode         = 'newtopic' ;
				$error_msg    = '';
				$post_data    = array();
				$topic_type   = POST_NORMAL ;
				$message      = Fix_Quotes(message_prepare($message, 1, 1));
				$subject      = htmlprepare($subject, false, ENT_QUOTES, true);
//				$forum_id     = $story['forum_id'];
				// create a new topic
				$sql = "INSERT INTO ".TOPICS_TABLE." (topic_title, topic_poster, topic_time, forum_id, topic_status, topic_type, topic_vote, icon_id) VALUES ('$subject', $userid, $current_time, $forum_id, ".TOPIC_UNLOCKED.", 0, 0, 0)";
				$db->sql_query($sql);
				$topic_id = $db->sql_nextid('topic_id');
				// make forum post
				$sql = "INSERT INTO ".POSTS_TABLE." (topic_id, forum_id, poster_id, post_username, post_time, poster_ip, enable_bbcode, enable_html, enable_smilies, enable_sig) VALUES ($topic_id, $forum_id, $userid, '$username', $current_time, '$user_ip', 1, 1, 1, 0)";
				$db->sql_query($sql);
				$post_id = $db->sql_nextid('post_id');
				// store post text
				$sql = "INSERT INTO ".POSTS_TEXT_TABLE." (post_id, post_subject, post_text) VALUES ($post_id, '$subject', '$message')" ;
				$db->sql_query($sql);
				// update topic id in the story
				$sql = 'UPDATE '.$prefix.'_pronews_articles SET topic_id='.$topic_id.' WHERE id='.$sid;
				$db->sql_query($sql);
				// housekeeping, make search words and update last post etc.
				add_search_words('single', $post_id, $message, $subject);
				update_post_stats($mode, $post_data, $forum_id, $topic_id, $post_id, $userid);
				// view the newly created forum thread
				url_redirect(getlink('Forums&file=viewtopic&'.POST_TOPIC_URL.'='.$topic_id).'#'.$topic_id);
			}
		}
	}

	function submit_article($func='',$id='') {
		global $db, $prefix, $userinfo, $cpgtpl, $pagetitle, $pnsettings, $gblsettings, $module_name, $multilingual, $currentlang, $BASEHREF;
// Remove the lines below - by adding // in columns 1-2 - if you do NOT want the center and right column blocks disabled when Pro_News Article is displayed
		if ($gblsettings['Version_Num'] >= "9.2.1") {
			global $Blocks;
			$Blocks->r=-1;
//			$Blocks->l=-1;
			$Blocks->c=-1;
			$Blocks->d=-1;
		}
// end of center and right block disable code
		$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : '');
		require_once('includes/nbbcode.php');
		require_once('header.php');
		if (($func == '') || ($func == 'none')) {
			ProNews::article_form();
			$cpgtpl->set_filenames(array('body' => 'pronews/submit.html'));		// use submit.html as don't know which Section yet
			$cpgtpl->display('body');
		}
		$category = ((isset($_POST['cat'])) && ($_POST['cat'] != '')) ? intval($_POST['cat']) : '';
// echo '<br />id='.$id;
		if ($id == '' && $category <= '0' && $pnsettings['member_pages'] >= '1' && (sizeof($rslt = ProNews::user_page_check(abs($category), '')) >= $pnsettings['member_pages'])) {								// member's user_page'
// echo '<br />lmt='.$pnsettings['member_pages'].' sizeof='.sizeof($rslt).' id='.$rslt[0]['id'].' rslt=';print_r($rslt);
				if (sizeof($rslt) == '1') {
					$msg = _PNPAGEXIST.'<br /><br />[ <a href="javascript:history.go(-1)">'._PNGOBACK2.'</a> ]&nbsp;&nbsp;[ <a href="'.getlink("&mode=submit&do=edit&id=".$rslt[0]['id']).'">'._PNAEDIT.'</a> ]';
				} else {
					$msg = sprintf(_PNPAGEXCEED, $pnsettings['member_pages']).'<br /><br />[ <a href="javascript:history.go(-1)">'._PNGOBACK2.'</a> ]&nbsp;&nbsp;[ <a href="'.getlink("&mode=submit&do=edit&id=".$rslt[0]['id']).'">'._PNAEDIT.'</a> ]';
				}
				$cpgtpl->assign_block_vars('pn_msg', array(
					'S_MSG' => $msg
				));
				$cpgtpl->set_filenames(array('body' => 'pronews/submit.html'));		// use submit.html as don't know which Section yet
				$cpgtpl->display('body', false);
		} else {
// echo '<br />Category='.$category;
			$category = abs($category);											// -ve values used to indicate Members Pages Section
			if ($func == 'new') {

				$msg = _PNSUBMITADVICE;
				$cpgtpl->assign_block_vars('pn_msg', array(
					'S_MSG' => $msg
				));
				$cpgtpl->set_filenames(array('body' => 'pronews/submit.html'));		// use submit.html as don't know which Section yet
				$cpgtpl->display('body', false);

				$edit = array('id'=>'', 'catid'=>$category, 'title' =>'', 'allow_comment'=>'', 'content'=>'', 'image'=>'', 'intro'=>'', 'caption'=>'', 'display_order'=>'', 'alanguage'=>'', 'album_id'=>'', 'album_cnt'=>'', 'album_seq'=>'', 'slide_show'=>'', 'image2'=>'', 'caption2'=>'', 'user_fld_0' => '', 'user_fld_1'=>'', 'user_fld_2'=>'', 'user_fld_3'=>'', 'user_fld_4'=>'', 'user_fld_5'=>'', 'user_fld_6'=>'', 'user_fld_7'=>'', 'user_fld_8'=>'', 'user_fld_9'=>'', 'clsdttime'=>'', 'cledttime'=>'', 'cal_id'=>'', 'associated'=>'', 'display'=>'');
				$sql = 'SELECT template, usrfld0, usrfld1, usrfld2, usrfld3, usrfld4, usrfld5, usrfld6, usrfld7, usrfld8, usrfld9, usrfldttl';
				$sql .= ' FROM '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_sections as s';
				$sql .= ' WHERE c.sid=s.id AND c.id="'.$category.'"';
				$list = $db->sql_fetchrow($db->sql_query($sql));
				if ($list) {
					$edit['template'] = $list['template'];
					$edit['usrfld0'] = $list['usrfld0'];
					$edit['usrfld1'] = $list['usrfld1'];
					$edit['usrfld2'] = $list['usrfld2'];
					$edit['usrfld3'] = $list['usrfld3'];
					$edit['usrfld4'] = $list['usrfld4'];
					$edit['usrfld5'] = $list['usrfld5'];
					$edit['usrfld6'] = $list['usrfld6'];
					$edit['usrfld7'] = $list['usrfld7'];
					$edit['usrfld8'] = $list['usrfld8'];
					$edit['usrfld9'] = $list['usrfld9'];
					$edit['usrfldttl'] = $list['usrfldttl'];
				}

				$tpl = ($edit['template'] != '') ? $edit['template'] : $pnsettings['template'];

				if (ProNews::in_group_list($pnsettings['mod_grp'])) {
					$result = $db->sql_query('SELECT dttime FROM '.$prefix.'_pronews_schedule WHERE id="'.$id.'" AND newstate="1"');
					$row2 =$db->sql_fetchrow($result, SQL_ASSOC);
					$list['sdttime'] = $row2['dttime'];
					$result = $db->sql_query('SELECT dttime FROM '.$prefix.'_pronews_schedule WHERE id="'.$id.'" AND newstate="0"');
					$row2 =$db->sql_fetchrow($result, SQL_ASSOC);
					$list['edttime'] = $row2['dttime'];
					$db->sql_freeresult($result);
				}

				if ($edit['cal_id']) {
					$res = $db->sql_query('SELECT * FROM '.$prefix.'_cpgnucalendar WHERE eid ='.$edit['cal_id']);
					if ($db->sql_numrows($res) != 1) {
						$edit['cal_id'] = '';						// clear if calendar entry has been deleted
						$db->sql_freeresult($res);
					} else {
						$crow = $db->sql_fetchrow($res);
						$db->sql_freeresult($res);
						$cl_s_date = $crow['date'];
						$cl_time = $crow['time'];
						if ($cl_time == -1) {
							$edit['clsdttime'] =  gmmktime(0, 0, 0, substr($cl_s_date, 4, 2 ), substr($cl_s_date, 6, 2 ), substr($cl_s_date, 0, 4 ));
						} else {
							$edit['clsdttime'] =  gmmktime(floor($cl_time / 10000), ($cl_time / 100 ) % 100, 0, substr($cl_s_date, 4, 2 ), substr($cl_s_date, 6, 2 ), substr($cl_s_date, 0, 4 ));
							$edit['clsdttime'] -= (L10NTime::in_dst($edit['clsdttime'], $userinfo['user_dst']) * 3600);
						}
 						if ($crow['type'] == 'R') {
							$edit['cledttime'] = '';
							$res = $db->sql_query('SELECT * FROM '.$prefix.'_cpgnucalendar_repeat WHERE eid ='.$edit['cal_id']);
							if ($db->sql_numrows($res) == 1) {
								$crrow = $db->sql_fetchrow($res);
								$db->sql_freeresult($res);
								$cl_d_date = $crrow['end'];
								if ($crrow['type'] == 'daily' && $crrow['frequency'] == 1 && $crrow['days'] == 'nnnnnnn' && $crrow['end'] != '') {
									if ($cl_time == -1) {
										$edit['cledttime'] =  gmmktime(0, 0, 0, substr($cl_d_date, 4, 2 ), substr($cl_d_date, 6, 2 ), substr($cl_d_date, 0, 4 ));
									} else {
										$edit['cledttime'] =  gmmktime(floor($cl_time / 10000), ($cl_time / 100 ) % 100, 0, substr($cl_d_date, 4, 2 ), substr($cl_d_date, 6, 2 ), substr($cl_d_date, 0, 4 ));
										$edit['cledttime'] -= (L10NTime::in_dst($edit['cledttime'], $userinfo['user_dst']) * 3600);
									}
									if ($crow['duration'] != 0) {
										$edit['cledttime'] = $edit['cledttime'] + ($crow['duration'] * 60);
									}
								}
							}
						} else {
							if ($crow['duration'] != 0) {
								$edit['cledttime'] = gmmktime(floor($cl_time / 10000), ($cl_time / 100 ) % 100, 0, substr($cl_s_date, 4, 2 ), substr($cl_s_date, 6, 2 ), substr($cl_s_date, 0, 4 ));
								$edit['cledttime'] = $edit['clsdttime'] + ($crow['duration'] * 60);
							} else {
//								$edit['cledttime'] = gmmktime(0, 0, 0, '', '', '');
								$edit['cledttime'] = '';
							}
						}
					}
				}


				ProNews::article_form($edit);
				if ($tpl && file_exists('themes/'.$CPG_SESS['theme'].'/template/pronews/submit/'.$tpl)) {
					$sbmt = 'pronews/submit/'.$tpl;
				} elseif ($tpl && file_exists('themes/'.$CPG_SESS['theme'].'/template/pronews/submit/submit.html')) {
					$sbmt = 'pronews/submit/submit.html';
				} else {
					$sbmt = 'pronews/submit.html';
				}
				$cpgtpl->set_filenames(array('body' => $sbmt));
				$cpgtpl->display('body', false);
			}
			if (($func == 'edit') && ($id != '')) {
				$sql = 'SELECT a.*, s.admin sadmin, moderate, template, usrfld0, usrfld1, usrfld2, usrfld3, usrfld4, usrfld5, usrfld6, usrfld7, usrfld8, usrfld9, usrfldttl';
				$sql .= ' FROM '.$prefix.'_pronews_articles as a';
				$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
				$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
				$sql .= ' WHERE a.id="'.$id.'"';
				$list = $db->sql_fetchrow($db->sql_query($sql));
// echo 'from sub art='; print_r($list);
				if ($list) {
//  echo '<br />mod='.$list['moderate'].' mod_grp='.$pnsettings['mod_grp'].' in_grp:'.in_array($list['moderate'], explode(',', $pnsettings['mod_grp']), true).' in_mod_lst:'.in_group($list['moderate']);
					if ((in_group($list['moderate']) && in_array($list['moderate'], explode(',', $pnsettings['mod_grp']), true)) || ((($list['sadmin'] == 0) || ($list['sadmin'] == 3 && !is_user()) || ($list['sadmin'] == 1 && is_user()) || ($list['sadmin'] == 2 && can_admin($module_name)) || ($list['sadmin'] > 3 && is_user() && (in_group($list['sadmin']-3)))) && ($list['postby'] == $userinfo['username']))) {
						ProNews::article_form($list);
					}
				} else {
					url_redirect(getlink());
				}
				$pagetitle .= (!$pnsettings['SEOtitle'] ? ' '._BC_DELIM.' ' : '')._PNSUBMIT;
				$tpl = ($list['template'] != '') ? $list['template'] : $pnsettings['template'];
				if ($tpl && file_exists('themes/'.$CPG_SESS['theme'].'/template/pronews/submit/'.$tpl)) {
					$sbmt = 'pronews/submit/'.$tpl;
				} elseif ($tpl && file_exists('themes/'.$CPG_SESS['theme'].'/template/pronews/submit/submit.html')) {
					$sbmt = 'pronews/submit/submit.html';
				} else {
					$sbmt = 'pronews/submit.html';
				}
				$cpgtpl->set_filenames(array('body' => $sbmt));
				$cpgtpl->display('body', false);
			}
			if ($func == 'save') {
				$id = ((isset($_POST['id'])) && ($_POST['id'] != '')) ? intval($_POST['id']) : '';
//				$category = ((isset($_POST['cat'])) && ($_POST['cat'] != '')) ? $_POST['cat'] : '';
// echo '<br />Category='.$category;
				$sql = 'SELECT s.admin sadmin, s.forum_id sforum_id, c.id, s.id sid, s.post, s.title stitle, c.title ctitle, c.forum_id cforum_id, icon, template, usrfld0, usrfld1, usrfld2, usrfld3, usrfld4, usrfld5, usrfld6, usrfld7, usrfld8, usrfld9, usrfldttl';
				$sql .= ' FROM '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_sections as s';
				$sql .= ' WHERE c.sid=s.id AND c.id="'.$category.'"';
				$result = $db->sql_query($sql);
				$list = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);

				if ($list['post'] > 3) {$postnum = $list['post']-3;} else {$postnum = $list['post'];}
				if (can_admin($module_name) || (is_user() && ((isset($userinfo['_mem_of_groups'][$postnum])) || ($postnum == '1'))) || ($postnum == '0') || (!is_user() && $postnum == '3')) {
					if ($pnsettings['auto_apprv'] && is_user()) {
						$approved = 1;
					} else {
						$approved = ( ((can_admin($module_name)) || ($list['sadmin'] > 3) && (is_user()) && (in_group($list['sadmin']-3))) ) ? "1" : "0";    // Pre-approve if member auth group - layingback 061201
					}
					$title = (isset($_POST['title'])) ? Fix_Quotes($_POST['title'],1,1) : 'No Title';
					$intro = (isset($_POST['intro'])) ? Fix_Quotes($_POST['intro'],1,1) : 'No Content';
					$content = (isset($_POST['addtext'])) ? Fix_Quotes($_POST['addtext'],1,1) : 'No Content';
					$title = check_words($title);
					$content = check_words($content);
					$intro = check_words($intro);
					$alanguage = isset($_POST['alanguage']) ? Fix_Quotes($_POST['alanguage']) : '';
					$associated = (isset($_POST['assotop'])) ? implode(',', $_POST['assotop']) : ''; // Related Articles, modified by Masino Sinaga, June 22, 2009
					if (isset($_POST['inclnum'])) {
						$included = intval($_POST['inclnum']);
						$included = ($included + (intval($_POST['inclcatsec']) * 100)) * -1;
						$associated = ($associated == '') ? $included : $included.','.$associated;
					}
// echo $_POST['inclnum'].' '.$included.' '.$associated.' ';
					$image_error = '';
					$image2_error = '';
//					$tpl = ($row['template'] != '') ? $row['template'] : $pnsettings['template'];
					if ($_POST['image'] == '') {
						$image = ProNews::img($_FILES['iname']['tmp_name'],$_FILES['iname']['name']);
						if ($image <> '' && !empty($_FILES['iname']['error'])) {
							switch ($_FILES['iname']['error']) {
								case '1':
									cpg_error('<br />'._PNMXUPPHPA.' '.substr(ini_get(upload_max_filesize),0,-1)._PNMXUPPHPB.' (1)');
								case '2':
									cpg_error('<br />'.sprintf(ERR_IMGSIZE_TOO_LARGE,$_POST['MAX_FILE_SIZE']).' (2)');
								default :
									cpg_error('<br />'.NO_PIC_UPLOADED.' ('.$_FILES['iname']['error'].')');
							}
						}
						if ($image == '' && $_FILES['iname']['tmp_name'] != '' ) {
							$image_error = '1';
							$cpgtpl->assign_block_vars('pn_msg', array(
								'S_MSG' => '<b>'._PNDUPFILEA.' '.$_FILES['iname']['name'].' '._PNDUPFILEB.'</b>'
							));
						}
					} elseif (isset($_POST['delimage'])) {															  // enable delete - layingback 061208
						$image = '';
						if (file_exists($pnsettings['imgpath'].'/'.$_POST['image'])) {unlink($pnsettings['imgpath'].'/'.$_POST['image']);}
						if (file_exists($pnsettings['imgpath'].'/thumb_'.$_POST['image'])) {unlink($pnsettings['imgpath'].'/thumb_'.$_POST['image']);}
						if (file_exists($pnsettings['imgpath'].'/icon_'.$_POST['image'])) {unlink($pnsettings['imgpath'].'/icon_'.$_POST['image']);}
					} else {
						$image = Fix_Quotes($_POST['image'],1);
					}
					if ($_POST['image2'] == '') {
						$image2 = ProNews::img($_FILES['iname2']['tmp_name'],$_FILES['iname2']['name']);
						if ($image2 <> '' && !empty($_FILES['iname2']['error'])) {
							switch ($_FILES['iname2']['error']) {
								case '1':
									cpg_error('<br />'._PNMXUPPHPA.' '.substr(ini_get(upload_max_filesize),0,-1)._PNMXUPPHPB.' (1)');
								case '2':
									cpg_error('<br />'.sprintf(ERR_IMGSIZE_TOO_LARGE,$_POST['MAX_FILE_SIZE']).' (2)');
								default :
									cpg_error('<br />'.NO_PIC_UPLOADED.' ('.$_FILES['iname2']['error'].')');
							}
						}
						if ($image2 == '' && $_FILES['iname2']['tmp_name'] != '' ) {
							$image2_error = '1';
							$cpgtpl->assign_block_vars('pn_msg', array(
								'S_MSG' => '<b>'._PNDUPFILEA.' '.$_FILES['iname2']['name'].' '._PNDUPFILEB.'</b>'
							));
						}
					} elseif (isset($_POST['delimage2'])) {
						$image2 = '';
						if (file_exists($pnsettings['imgpath'].'/'.$_POST['image2'])) {unlink($pnsettings['imgpath'].'/'.$_POST['image2']);}
						if (file_exists($pnsettings['imgpath'].'/thumb_'.$_POST['image2'])) {unlink($pnsettings['imgpath'].'/thumb_'.$_POST['image2']);}
						if (file_exists($pnsettings['imgpath'].'/icon_'.$_POST['image2'])) {unlink($pnsettings['imgpath'].'/icon_'.$_POST['image2']);}
					} else {
						$image2 = Fix_Quotes($_POST['image2'],1);
					}
					$caption = Fix_Quotes($_POST['imgcap'],1,0);
					$caption2 = Fix_Quotes($_POST['imgcap2'],1,0);
					$comment = Fix_Quotes($_POST['comments'],1,0);
					$postby = $userinfo['username'];
					$postby_show = "1";
					$post_time = gmtime();
					$show_cat = "1";
					$viewable = "1";
					$active = "1";

					if ($pnsettings['topic_lnk'] == 1) {
						$df_topic = ($_POST['topic']) ? intval($_POST['topic']) : 0;
						if ($_POST['topic']) {
							$sql = 'SELECT t.topicimage, t.topictext FROM '.$prefix.'_topics as t WHERE t.topicid='.$df_topic;
							$tlist = $db->sql_fetchrow($db->sql_query($sql));
						}
					} else {
						$df_topic = 0;
					}

					$album_id = ($_POST['album_id']) ? intval($_POST['album_id']) : 0;
					$album_cnt = intval($_POST['album_cnt']);
					$album_seq = intval($_POST['album_seq']);
					$slide_show = intval($_POST['slide_show']);
					$gallery = intval($_POST['gallery']);
//					$topicid = $_POST['topicid'];  // Editing Forum Topic, modified by Masino Sinaga, June 22, 2009
					$slide_gallery = $slide_show + ($gallery * 2);
					$user_fld_0 = Fix_Quotes($_POST['user_fld_0'],1,0);
					$user_fld_1 = Fix_Quotes($_POST['user_fld_1'],1,0);
					$user_fld_2 = Fix_Quotes($_POST['user_fld_2'],1,0);
					$user_fld_3 = Fix_Quotes($_POST['user_fld_3'],1,0);
					$user_fld_4 = Fix_Quotes($_POST['user_fld_4'],1,0);
					$user_fld_5 = Fix_Quotes($_POST['user_fld_5'],1,0);
					$user_fld_6 = Fix_Quotes($_POST['user_fld_6'],1,0);
					$user_fld_7 = Fix_Quotes($_POST['user_fld_7'],1,0);
					$user_fld_8 = Fix_Quotes($_POST['user_fld_8'],1,0);
					$user_fld_9 = Fix_Quotes($_POST['user_fld_9'],1,0);


// echo '<br />cy='.$_POST['cyear'].' cm='.$_POST['cmonth'].' cd='.$_POST['cday'].' ch='.$_POST['chour'].' cm='.$_POST['cmin'];
// echo '<br />dy='.$_POST['dyear'].' dm='.$_POST['dmonth'].' dd='.$_POST['dday'].' dh='.$_POST['dhour'].' dm='.$_POST['dmin'];
					if ($_POST['cyear']==' ' && $_POST['cmonth']==' ' && $_POST['cday']==' ' && $_POST['chour']==' ' && $_POST['cmin']==' ') {
						$clsdttime = 0;
						$cledttime = 0;
					} else {
					$clsdttime = L10NTime::toGMT(gmmktime(($_POST['chour']!=' ') ? intval($_POST['chour']) : 0, ($_POST['cmin']!=' ') ? intval($_POST['cmin']) : 0, 0, intval($_POST['cmonth']), intval($_POST['cday']), intval($_POST['cyear'])), $userinfo['user_dst'], $userinfo['user_timezone']);
					if ($_POST['dyear']==' ' && $_POST['dmonth']==' ' && $_POST['dday']==' ' && $_POST['dhour']==' ' && $_POST['dmin']==' ') {
						$cledttime = 0;
					} else {
						$cledttime = L10NTime::toGMT(gmmktime(($_POST['dhour']!=' ') ? intval($_POST['dhour']) : 0, ($_POST['dmin']!=' ') ? intval($_POST['dmin']) : 0, 0, ($_POST['dmonth']==' ') ? intval($_POST['cmonth']) : intval($_POST['dmonth']), ($_POST['dday']==' ') ? intval($_POST['cday']) : intval($_POST['dday']), ($_POST['dyear']==' ') ? intval($_POST['cyear']) : intval($_POST['dyear'])), $userinfo['user_dst'], $userinfo['user_timezone']);
					}
				}
// echo '<br /> cl stime='.$clsdttime.' cl etime='.$cledttime;
					$cal_id = intval($_POST['calid']);
// echo ' cl stime='.$clsdttime.' cl etime='.$cledttime.' cal_id='.$cal_id;

					if (ProNews::in_group_list($pnsettings['mod_grp'])) {
						$sdttime = L10NTime::toGMT(gmmktime($_POST['shour'], $_POST['smin'], 0, $_POST['smonth'], $_POST['sday'], $_POST['syear']), $userinfo['user_dst'], $userinfo['user_timezone']);
						$edttime = L10NTime::toGMT(gmmktime($_POST['ehour'], $_POST['emin'], 0, $_POST['emonth'], $_POST['eday'], $_POST['eyear']), $userinfo['user_dst'], $userinfo['user_timezone']);

						$today = time();
						if ($id == '') {						// only test if new article, so not to disturb current setting
							$active = ($sdttime > $today) ? "0" : "1";
						}
// echo '$sdttime='.$sdttime.' $edttime='.$edttime.' $today='.$today.' time='.time();
					}


					if (isset($_POST['submitart'])) {
// -- Submit mode
						if ($id == '') {
//							$sql = 'INSERT INTO '.$prefix.'_pronews_articles VALUES(NULL, "'.$category.'", "'.$title.'", "'.$intro.'", "'.$content.'", "'.$image.'", "'.$caption.'", "'.$comment.'", "'.$postby.'", "'.$postby_show.'", "'.$post_time.'", "'.$show_cat.'", "'.$active.'", "'.$approved.'", "'.$viewable.'", NULL, "'.$display_order.'", "'.$alanguage.'", "'.$album_id.'", "'.$album_cnt.'", "'.$album_seq.'", "'.$slide_gallery.'", "'.$image2.'", "'.$caption2.'", "'.$user_fld_0.'", "'.$user_fld_1.'", "'.$user_fld_2.'", "'.$user_fld_3.'", "'.$user_fld_4.'", "'.$user_fld_5.'", "'.$user_fld_6.'", "'.$user_fld_7.'", "'.$user_fld_8.'", "'.$user_fld_9.'",0,0,0, "'.$df_topic.'", "")';
//							$result = $db->sql_query($sql);
//							$newid = mysql_insert_id();

							// This will check whether the news already exists or not, modified by Masino Sinaga, June 22, 2009
							$checkarticle = $db->sql_query('SELECT * FROM '.$prefix.'_pronews_articles WHERE title = "'.$title.'" AND intro = "'.$intro.'"');
							$rowcheckarticle = $db->sql_numrows($checkarticle);
							if ($rowcheckarticle > 0) {
								require_once('header.php');
								$cpgtpl->assign_block_vars('newsnone', array('S_MSG' => _PNTITLE.': <b>'.$title.'</b><br /><br />'._PNALREADYEXISTS.'<br /><br />[ <a href="javascript:history.go(-1)">'._PNGOBACK2.'</a> ]&nbsp;&nbsp;[ <a href="'.getlink().'">'._GO.'</a> ]'));
								$cpgtpl->set_filenames(array('errbody' => 'pronews/article/'.$pnsettings['template']));
								$cpgtpl->display('errbody', false);
							} else {

								$sql = 'INSERT INTO '.$prefix.'_pronews_articles VALUES(NULL, "'.$category.'", "'.$title.'", "'.$intro.'", "'.$content.'", "'.$image.'", "'.$caption.'", "'.$comment.'", "'.$postby.'", "'.$postby_show.'", "'.$post_time.'", "'.$show_cat.'", "'.$active.'", "'.$approved.'", "'.$viewable.'", NULL, 0, "'.$alanguage.'", "'.$album_id.'", "'.$album_cnt.'", "'.$album_seq.'", "'.$slide_gallery.'", "'.$image2.'", "'.$caption2.'", "'.$user_fld_0.'", "'.$user_fld_1.'", "'.$user_fld_2.'", "'.$user_fld_3.'", "'.$user_fld_4.'", "'.$user_fld_5.'", "'.$user_fld_6.'", "'.$user_fld_7.'", "'.$user_fld_8.'", "'.$user_fld_9.'",0,0,0, "'.$df_topic.'", "'.$cal_id.'", "'.$associated.'", "1", "", "", "")';
								// Related Articles, modified by Masino Sinaga, June 22, 2009
								$result = $db->sql_query($sql);
								$newid = mysql_insert_id();

								if (ProNews::in_group_list($pnsettings['mod_grp'])) {
									if ($sdttime > $today) {
										$db->sql_query('INSERT INTO '.$prefix.'_pronews_schedule VALUES ("'.$newid.'", "1", "'.$sdttime.'")');
									}
									if ($edttime > $today) {
										$db->sql_query('INSERT INTO '.$prefix.'_pronews_schedule VALUES ("'.$newid.'", "0", "'.$edttime.'")');
									}
								}


								if ($pnsettings['cal_module'] && $clsdttime != 0) {
									ProNews::create_cal($clsdttime, $cledttime, $newid, $title, $intro, $postby);
								}

								//$msg = _PNARTICLE.'&nbsp;'._PNADDSUC.'<br /><a href="'.getlink().'">'._PNGOBACK.'</a>';
								// Modified by Masino Sinaga, June 23, 2009
								require_once('header.php');
//								$cpgtpl->assign_block_vars('newsnone', array('S_MSG' => _PNARTICLE.'&nbsp;<b>'.$title.'</b>&nbsp;'._PNADDSUC.(!$approved ? '.<br /><br />'._PNWEWILLCHECK : '').'<br /><br />[ <a href="'.$module_name.'/mode=submit.html">'._PNADDARTICLE.'</a> ]&nbsp;&nbsp;[ <a href="'.getlink().'">'._GO.'</a> ]'));
//								$cpgtpl->set_filenames(array('errbody' => 'pronews/article/'.$pnsettings['template']));
//								$cpgtpl->display('errbody');
								$msg = _PNARTICLE.'&nbsp;<b>'.$title.'</b>&nbsp;'._PNADDSUC.(!$approved ? '.<br /><br />'._PNWEWILLCHECK : '').'<br /><br />[ <a href="'.$module_name.'/mode=submit.html">'._PNADDARTICLE.'</a> ]&nbsp;&nbsp;[ <a href="'.getlink().'">'._GO.'</a> ]';
								if ($list['sid'] == '1' && $pnsettings['member_pages'] >= '1') {				// member's user_page enabled
									$first = ProNews::user_page_check($category, $newid);
								}

							}

						} else {
							$db->sql_query('UPDATE '.$prefix.'_pronews_articles SET catid="'.$category.'", title="'.$title.'", intro="'.$intro.'", content="'.$content.'", image="'.$image.'", caption="'.$caption.'", allow_comment="'.$comment.'", postby_show="'.$postby_show.'", show_cat="'.$show_cat.'", alanguage="'.$alanguage.'", album_id="'.$album_id.'", album_cnt="'.$album_cnt.'", album_seq="'.$album_seq.'", slide_show="'.$slide_gallery.'", image2="'.$image2.'", caption2="'.$caption2.'", user_fld_0="'.$user_fld_0.'", user_fld_1="'.$user_fld_1.'", user_fld_2="'.$user_fld_2.'", user_fld_3="'.$user_fld_3.'", user_fld_4="'.$user_fld_4.'", user_fld_5="'.$user_fld_5.'", user_fld_6="'.$user_fld_6.'", user_fld_7="'.$user_fld_7.'", user_fld_8="'.$user_fld_8.'", user_fld_9="'.$user_fld_9.'", df_topic="'.$df_topic.'", associated="'.$associated.'", updtby="'.$postby.'", updttime="'.$post_time.'" WHERE id='.$id);
							// Related Articles, modified by Masino Sinaga, June 22, 2009

							if ($pnsettings['cal_module']) {
								if ($cal_id && $cal_id != 0) {
									if ($clsdttime == 0 && $cledttime == 0) {
										$db->sql_query('DELETE FROM '.$prefix.'_cpgnucalendar WHERE eid='.$cal_id, true);				// ignore error if already deleted
										$db->sql_query('DELETE FROM '.$prefix.'_cpgnucalendar_repeat WHERE eid='.$cal_id, true);		// ignore error if already deleted
										$cal_id = '';													// if both times are cleared clear link and delete calendar entry
									} else {
										$res = $db->sql_query('SELECT * FROM '.$prefix.'_cpgnucalendar WHERE eid ='.$cal_id);			// check it has not been deleted
										if ($db->sql_numrows($res) != 1) {
											$cal_id = '';						//clear link if calendar entry already deleted
											$db->sql_freeresult($res);
										} else {
											$crow = $db->sql_fetchrow($res);
											$db->sql_freeresult($res);
// To automatically approve CPGNuCalendar entry on saving this article [ remove line below to remove this feature ]
											if ($crow['approved'] == 0) {$crow['approved'] = formatDateTime(time(),'%G%m%d');}
											$cal_content = $intro.' &nbsp; '._PNCLK2FLLW.' [url='.getlink("&amp;aid=".$newid).'] '._PNORGNGART.'[/url].';
											$startday = mktime(0, 0, 0, ProNews::pndate('m', $clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']), ProNews::pndate('d', $clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']), ProNews::pndate('Y', $clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']));
// echo ' startday='.$startday.' endday='.$endday;
											if ($cledttime == 0) {
											$endday = $startday;
										} else {
											$endday = mktime(0, 0, 0, ProNewsAdm::pndate('m', $cledttime, $userinfo['user_dst'], $userinfo['user_timezone']), ProNewsAdm::pndate('d', $cledttime, $userinfo['user_dst'], $userinfo['user_timezone']), ProNewsAdm::pndate('Y', $cledttime, $userinfo['user_dst'], $userinfo['user_timezone']));
										}
										if ($clsdttime == $startday && $cledttime == ($endday + 86400)) {
												$clstime = -1;										// if whole day - ie no time provided
												$cal_durtn = 0;
//										if ($cledttime == 0) {$cledttime = $clsdttime;}		// force end date = start date if omitted
										} elseif ($cledttime == 0) {
											$cal_durtn = $startday + 86400 - $clsdttime;		// end date/time omitted so cap at end of day
											} else {
												$cal_durtn = (($cledttime - $endday) - ($clsdttime - $startday)) / 60;
											}
											$cal_rpt = ($endday > $startday) ? 'R' : 'E';
					/*  change for functions.php 						$cal_appvd = (true && true) ? formatDateTime(time(),'%Y%m%d') : ''; 		*/
											$clstime = ($clstime == -1) ? -1 : formatDateTime($clsdttime,'%H%M').'00';

											$db->sql_query('UPDATE '.$prefix.'_cpgnucalendar SET date="'.formatDateTime($clsdttime,'%G%m%d').'", time="'.formatDateTime($clsdttime,'%H%M').'00'.'", mod_date="'.formatDateTime(time(),'%Y%m%d').'", mod_time="'.formatDateTime(time(),'%H%M%S').'", duration="'.$cal_durtn.'", type="'.$cal_rpt.'", name="'.$title.'", description="'.$cal_content.'", approved="'.$crow['approved'].'" WHERE eid='.$cal_id);
											if ($cal_rpt == 'R') {
												$db->sql_query('INSERT INTO '.$prefix.'_cpgnucalendar_repeat VALUES ("'.$cal_id.'", "daily", "'.formatDateTime($cledttime,'%Y%m%d').'", "1", "nnnnnnn") ON DUPLICATE KEY UPDATE end="'.formatDateTime($cledttime,'%G%m%d').'"');
											} elseif ($crow['type'] == 'R') {
												$db->sql_query('DELETE FROM '.$prefix.'_cpgnucalendar_repeat WHERE eid='.$cal_id, true);		// ignore error if already deleted
											}
										}
									}
								} elseif ($clsdttime != 0) {
									ProNews::create_cal($clsdttime, $cledttime, $id, $title, $intro, $postby);
								}
							}

							if (ProNews::in_group_list($pnsettings['mod_grp'])) {
								if ($sdttime > $today) {
									$db->sql_query('INSERT INTO '.$prefix.'_pronews_schedule VALUES ("'.$id.'", "1", "'.$sdttime.'") ON DUPLICATE KEY UPDATE dttime="'.$sdttime.'"');
								}
								if ($edttime > $today) {
									$db->sql_query('INSERT INTO '.$prefix.'_pronews_schedule VALUES ("'.$id.'", "0", "'.$edttime.'") ON DUPLICATE KEY UPDATE dttime="'.$edttime.'"');
								}
							}

							$msg = _PNARTICLE.'&nbsp;'._PNUPSUC.'<br /><a href="'.getlink("&amp;aid=".$id).'">'._PNGOBACK.'</a>';
						}

						/*
						if ($gblsettings['notify'] && $id == '') {
							$notify_message = $gblsettings['notify_message']."\n\n\n========================================================\n"._PNARTTITLE.": ".$title."\n\n"._PNINTRO.": ".$intro."\n\n"._PNPOSTEDBY.": ".$postby;
							if (!send_mail($mailer_message,$notify_message,0,$gblsettings['notify_subject'],$gblsettings['notify_email'],$gblsettings['notify_email'],$gblsettings['notify_from'],$postby)) {
								echo $mailer_message;
							}
						}
						*/

						// Notify Admin for Pending Article, using setting from Pro_News admin panel; modified by Masino Sinaga, June 23, 2009
						if ($pnsettings['notify_admin_pending_article']=='1'  && !$approved) {			// skip if a pre-approved post
							$notify_message = "========================================================\n\n".decode_bbcode($intro, 1, true)."\n\n".decode_bbcode($content, 1, true)."\n\n"._PNPOSTEDBY.": ".$postby;
							$sender_email = ($pnsettings['sender_email_pending_article']) ? $pnsettings['sender_email_pending_article'] : $gblsettings['notify_from'];
							$message_body = (($pnsettings['body_email_pending_article']) ? $pnsettings['body_email_pending_article'] : _PNBODYPENDART)."\n\n"._PNTITLE.": ".$title."\n\n".$notify_message;
							if (!send_mail($mailer_message, $message_body, 0, (($pnsettings['subject_email_pending_article']) ? $pnsettings['subject_email_pending_article'] : _PNSUBJPENDART), $sender_email, $sender_email, $sender_email, $postby)) {
								$msg = $mailer_message;
							}
						}

						$cpgtpl->assign_block_vars('pn_msg', array(
							'S_MSG' => $msg
						));
						$cpgtpl->set_filenames(array('body' => 'pronews/submit.html'));
						$cpgtpl->display('body', false);
// -- Preview mode
					} else {
						require_once('includes/nbbcode.php');
						$album_order = array(0=>'',1=>'title',2=>'title',3=>'filename',4=>'filename',5=>'ctime',6=>'ctime',7=>'pic_rating',8=>'pic_rating');
						$msg = _PNSUBPREVIEW;
						$msg2 = _PNREVIEW.'<br />'._PNARTPREVIEW.' --- '._PNDEMOLINKS;
						$cpgtpl->assign_block_vars('preview_article', array(
							'S_HEADING' => $msg,
							'S_MSG' => $msg2
						));
						$title = ProNews::cleanse($title);
						$intro = ProNews::cleanse($intro);
						$content = ProNews::cleanse($content);
						$caption = ProNews::cleanse($caption);
						$caption2 = ProNews::cleanse($caption2);
						$user_fld_0 = ProNews::cleanse($user_fld_0);
						$user_fld_1 = ProNews::cleanse($user_fld_1);
						$user_fld_2 = ProNews::cleanse($user_fld_2);
						$user_fld_3 = ProNews::cleanse($user_fld_3);
						$user_fld_4 = ProNews::cleanse($user_fld_4);
						$user_fld_5 = ProNews::cleanse($user_fld_5);
						$user_fld_6 = ProNews::cleanse($user_fld_6);
						$user_fld_7 = ProNews::cleanse($user_fld_7);
						$user_fld_8 = ProNews::cleanse($user_fld_8);
						$user_fld_9 = ProNews::cleanse($user_fld_9);
						$row = array('stitle'=>$list['stitle'],'ctitle'=>$list['ctitle'],'icon'=>$list['icon'],'id'=>$id,'catid'=>$category,'sid'=>$list['sid'],'title'=>$title,'intro'=>$intro, 'content'=>$content,'image'=>$image,'caption'=>$caption,'allow_comment'=>$comment,'postby'=>$postby,'postby_show'=>$postby_show,'posttime'=>$post_time,'show_cat'=>$show_cat,'active'=>$active,'approved'=>$approved,'viewable'=>$viewable, 'display_order'=>NULL,'alanguage'=>$alanguage,'album_id'=>$album_id,'album_cnt'=>$album_cnt,'album_seq'=>$album_seq, 'slide_show'=>$slide_gallery,'image2'=>$image2,'caption2'=>$caption2,'user_fld_0'=>$user_fld_0,'user_fld_1'=>$user_fld_1,'user_fld_2'=>$user_fld_2,'user_fld_3'=>$user_fld_3,'user_fld_4'=>$user_fld_4,'user_fld_5'=>$user_fld_5,'user_fld_6'=>$user_fld_6,'user_fld_7'=>$user_fld_7,'user_fld_8'=>$user_fld_8,'user_fld_9'=>$user_fld_9, 'usrfld0'=>$list['usrfld0'], 'usrfld1'=>$list['usrfld1'], 'usrfld2'=>$list['usrfld2'], 'usrfld3'=>$list['usrfld3'], 'usrfld4'=>$list['usrfld4'], 'usrfld5'=>$list['usrfld5'], 'usrfld6'=>$list['usrfld6'], 'usrfld7'=>$list['usrfld7'], 'usrfld8'=>$list['usrfld8'], 'usrfld9'=>$list['usrfld9'], 'usrfldttl'=>$list['usrfldttl'], 'template'=>$list['template'], 'df_topic'=>$df_topic, 'topictext'=>$tlist['topictext'], 'topicimage'=>$tlist['topicimage'], 'clsdttime'=>$clsdttime, 'cledttime'=>$cledttime, 'associated'=>$associated, 'display'=>$display);

						if (ProNews::in_group_list($pnsettings['mod_grp'])) {
							$row['sdttime'] = $sdttime;
							$row['edttime'] = $edttime;
						}

// print_r($row);
						$target = 'pn'.uniqid(rand());
						if($image != '') {
							$imagesize = getimagesize($pnsettings['imgpath'].'/'.$image);
							$imagesizeX = $imagesize[0] + 16; $imagesizeY = $imagesize[1] + 16;
							if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
								$thumbimage = $pnsettings['imgpath'].'/thumb_'.$image;
							} else {
								$thumbimage = $pnsettings['imgpath'].'/'.$image;
							}
							if (file_exists($thumbimage)) {
								$display_image = '<a href="'.$pnsettings['imgpath'].'/'.$row['image'].'" target="'.$target.'"
 onclick="PN_openBrWindow(\''.$BASEHREF.$pnsettings['imgpath'].'/'.$row['image'].'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;"><img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" /></a>';
							}
						} else {$display_image = '';}
						if ($image_error) {$display_image = '<img class="pn_image" src="themes/PH2/images/'.$module_name.'/noimage.gif" alt="" />';}
						if($image2 != '') {
							$imagesize = getimagesize($pnsettings['imgpath'].'/'.$image2);
							$imagesizeX = $imagesize[0] + 16; $imagesizeY = $imagesize[1] + 16;
							if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
								$thumbimage2 = $pnsettings['imgpath'].'/thumb_'.$image2;
							} else {
								$thumbimage2 = $pnsettings['imgpath'].'/'.$image2;
							}
							if (file_exists($thumbimage2)) {
								$display_image2 = '<a href="'.$pnsettings['imgpath'].'/'.$row['image2'].'" target="'.$target.'"
 onclick="PN_openBrWindow(\''.$BASEHREF.$pnsettings['imgpath'].'/'.$row['image2'].'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;"><img class="pn_image" src="'.$thumbimage2.'" alt="'.$row['caption'].'" /></a>';
							}
						} else {$display_image2 = '';}
						if ($image2_error) {$display_image2 = '<img class="pn_image" src="themes/PH2/images/'.$module_name.'/noimage.gif" alt="" />';}
						$numpics = '0';
						$lpic = '';
						$maxsizeX = '200';
						$maxsizeY = '200';
						if (($row['album_id'] != '') && ($row['album_cnt'] > '0')) {
							$sql = 'SELECT pid pid, filepath, filename, pwidth, pheight, title, caption FROM '.$prefix.'_cpg_pictures';
//							$sql = 'SELECT pid pid, filepath, filename, pwidth, pheight, title, caption, remote_url FROM '.$prefix.'_cpg_pictures';  // lb - use after installing my coppermine remote CPG hack
							$sql .= ' WHERE aid='.$row['album_id'];
							$asc_desc = ($row['album_seq'] & 1) ? 'ASC' : 'DESC';
							if ($row['album_seq'] != '0') {$sql .= ' ORDER BY '.$album_order[$row['album_seq']].' '.$asc_desc;}
							$sql .= ' LIMIT '.$row['album_cnt'];
							$list = $db->sql_fetchrowset($db->sql_query($sql));
							if (($list) && ($list != "")) {
								foreach ($list as $key => $pic) {
									$fullsizepath = ($pic['remote_url'] != '' && preg_match("/(?:https?\:\/\/)?([^\.]+\.?[^\.\/]+\.[^\.\/]+[^\.]+)/", $pic['remote_url'], $matches)) ? 'http://'.$matches[1].'/' : $pic['filepath'];		// lb - cpg remote_url hack support
									$imagesizeX = $pic['pwidth'] + 16; $imagesizeY = $pic['pheight'] + 16;
									if ($pic['pwidth'] > $maxsizeX) { $maxsizeX = $pic['pwidth'];}
									if ($pic['pheight'] > $maxsizeY) { $maxsizeY = $pic['pheight'];}
									$thumb = str_replace("%2F", "/", rawurlencode($pic['filepath'].'thumb_'.$pic['filename']));
									$talbum[$key] = $pic['title'] != ' ' ? trim($pic['title']) : '&nbsp;';				// trim cos cpg adds trailing space!
									$calbum[$key] = ($pic['caption'] != '') ? $pic['caption'] : '';
									$palbum[$key] = '<a href="'.str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'" target="'.$target.'"
 onclick="PN_openBrWindow(\''.$BASEHREF.str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;"><img class="pn_image" src="'.$thumb.'" alt="'.$pic['title'].'" /></a>';
									$qalbum[$key] = str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename'])));
									$ralbum[$key] = str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'" target="'.$target.'"
 onclick="PN_openBrWindow(\''.$BASEHREF.str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;';
									$valbum[$key] = $thumb;
									$ualbum[$key] = '<img class="pn_image" src="'.str_replace("%2F", "/", rawurlencode($pic['filepath'].(file_exists($pic['filepath'].'normal_'.$pic['filename']) ? 'normal_' : '').$pic['filename'])).'" alt="" />';
									$galbum[$key] = '1';
									$lpic = $pic['pid'];		//track pid of last pic shown
									$numpics++;
								}
							}
						}
						$openLink = '<script type="text/javascript">
<!-- Script courtesy of http://www.web-source.net - Your Guide to Professional Web Site Design and Development
function load() {var load = window.open("'.getlink($module_name.'&mode=slide&id='.$row['id'].'&album='.$row['album_id'].'&pid='.$lpic.'&slideshow=5000","","scrollbars=no,menubar=no,height='.($maxsizeY + 72).',width='.($maxsizeX + 32).',resizable=yes,toolbar=no,location=no,status=no',false,true).'");}
// -->
</script>';
						$j = $numpics;
						while ($j <= '32') {
							$galbum[$j] = '';
							$palbum[$j] = '';
							$qalbum[$j] = '';
							$ralbum[$j] = '';
							$talbum[$j] = '&nbsp;';
							$calbum[$j] = '&nbsp;';
							$ualbum[$j] = '';
							$valbum[$j] = '';
							$j++;
						}
//	echo 'slideshow='.$row['slide_show'];
						$assoc = '';

						if ($row['associated'] != '') {
// echo ' assoc='.$row['associated'].' tok='.strtok($row['associated'],',');
							$inclcode = strtok($row['associated'], ',');
//							$row['associated'] = strtok('');			// reset to remainder of associated
							if ($inclcode < '0') {
								$inclcode = $inclcode * -1;
								$inclnum = $inclcode % 100;
								$inclcatsec = intval($inclcode / 100);
								$sql = 'SELECT a.id, a.title, posttime, postby FROM '.$prefix.'_pronews_articles as a';
								if ($inclcatsec == '0')  {
									$sql .= ' WHERE catid='.$category;
								} elseif ($inclcatsec == '1') {
									$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id WHERE c.sid='.$row['sid'];
								} elseif ($inclcatsec == '2') {
									$sql .= ' WHERE  catid='.$category.' AND postby="'.$row['postby'].'"';
								} else {
									$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id WHERE c.sid='.$row['sid'].' AND postby="'.$row['postby'].'"';
								}
								if ($row['id'] != '') { $sql .= ' AND a.id!='.$row['id']; }
								$sql .= ' AND a.approved="1" AND a.active="1"';
								$sql .= ($multilingual ? " AND (alanguage='$currentlang' OR alanguage='')" : '');
/*
		if (!can_admin('Pro_News')) {
			if (!is_user()) {
				$sql .= ' AND (s.view=0 OR s.view=3)';
			} else if ($member_a_group) {
				$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((s.view<4 AND (s.view=0 OR s.view=1)) OR (s.view>3 AND s.view-3=g.group_id)))';
			} else {
				$sql .= ' AND (s.view=0 OR s.view=1)';
			}
		}
*/
								$sql .= ' ORDER BY display_order DESC, posttime DESC';
								if ( $inclnum < '99') { $sql .= ' LIMIT '.$inclnum ; }
								$result = $db->sql_query($sql);
								while ($rowa = $db->sql_fetchrow($result)) {
									$datestory = formatDateTime($rowa['posttime'], _DATESTRING);
//									$gettitle = $rowa['title'];
//									if ($title != $gettitle) {
//										$title = $gettitle;
//									}
									$assoclst .= '<div class="pn_relart"><a href="'.getlink("$module_name&amp;aid=".$rowa['id']).'" title="'.$datestory.' '._PNAPOSTBY.' '.$rowa['postby'].'">'.$rowa['title'].'</a> <span class="pn_relartdate">'.$datestory.'</span></div>';
								}
								$db->sql_freeresult($result);
							}

							if ($row['associated'] != '') {
								$result = $db->sql_query('SELECT id, title, posttime, postby FROM '.$prefix.'_pronews_articles WHERE id IN ('.$row['associated'].')');
								while ($rowa = $db->sql_fetchrow($result)) {
									$datestory = formatDateTime($rowa['posttime'], _DATESTRING);
//									$gettitle = $rowa['title'];
//									if ($title != $gettitle) {
//										$title = $gettitle;
//									}
									$assoc .= '<div class="pn_relart"><a href="'.getlink("$module_name&amp;aid=".$rowa['id']).'" title="'.$datestory.' '._PNAPOSTBY.' '.$rowa['postby'].'">'.$rowa['title'].'</a> <span class="pn_relartdate">'.$datestory.'</span></div>';
								}
								$db->sql_freeresult($result);
							}

						}

//						$introtext = decode_bb_all($row['intro'], 1, true);
//						$combotext = $introtext.'<br /><br />'.decode_bb_all($row['content'], 1, true);
						$cpgtpl->assign_block_vars('newsarticle', array(
							'G_PREVIEW' => '1',
							'S_SECBRK' => ProNews::getsctrnslt('_PN_SECTITLE_', $row['stitle'], $row['sid']),
							'U_SECDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_SECDESC_', $row['sdescription'], $row['sid']), 1, true)),
							'S_CATBRK' => ProNews::getsctrnslt('_PN_CATTITLE_', $row['ctitle'], $row['catid']),
							'U_CATDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_CATDESC_', $row['cdescription'], $row['catid']), 1, true)),
							'S_INTRO' => make_clickable(decode_bb_all($row['intro'], 1, true)),
							'S_CONTENT' => make_clickable(decode_bb_all($row['content'], 2, true)),
							'S_ICON' => ($row['icon'] != '') ? $row['icon'] : 'clearpixel.gif',
							'T_ICON' => $row['ctitle'],
							'S_TITLE' => $row['title'],
							'L_POSTBY' => _PNPOSTBY,
							'S_POSTBY' => $row['postby'],
							'T_POSTBY' => getlink("Your_Account&amp;profile=".$row['postby']),
							'S_POSTTIME' => ProNews::create_date(false, $row['posttime']),
							'L_POSTON' => _PNPOSTON,
							'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
							'S_CATEGORY' => $row['catid'],
							'S_IMAGE' => ($row['image'] == '' && $pnsettings['show_noimage'] == '0') ? '' : $display_image,
							'T_CAP' => ($row['caption'] == '') ? '&nbsp;' : $row['caption'],
							'S_IMGIMAGE' => $pnsettings['imgpath'].'/thumb_'.$row['image'],
							'S_IMAGE2' => $display_image2,
							'T_CAP2' => ($row['caption2'] == '') ? '&nbsp;' : $row['caption2'],
							'S_IMGIMAGE2' => $pnsettings['imgpath'].'/thumb_'.$row['image2'],
							'G_PALBUM_0' => ($galbum['0']) ? '1' : '',
							'S_PALBUM_0' => $talbum['0'],
							'C_PALBUM_0' => $calbum['0'],
							'T_PALBUM_0' => ($row['album_cnt'] == '1' && ($row['slide_show'] == '1' || $row['slide_show'] == '3')) ? '<a href="javascript:load()"><img class="pn_image" src="'.$thumb.'" alt="'.$row['caption'].'" /></a>' : $palbum['0'],
							'I_PALBUM_0' => $qalbum['0'],
							'A_PALBUM_0' => $ralbum['0'],
							'U_PALBUM_0' => $ualbum['0'],
							'V_PALBUM_0' => $valbum['0'],
							'T_PALBUM_1' => $palbum['1'],
							'G_PALBUM_1' => ($galbum['1']) ? '1' : '',
							'S_PALBUM_1' => $talbum['1'],
							'C_PALBUM_1' => $calbum['1'],
							'T_PALBUM_1' => $palbum['1'],
							'I_PALBUM_1' => $qalbum['1'],
							'A_PALBUM_1' => $ralbum['1'],
							'U_PALBUM_1' => $ualbum['1'],
							'V_PALBUM_1' => $valbum['1'],
							'G_PALBUM_2' => ($galbum['2']) ? '1' : '',
							'S_PALBUM_2' => $talbum['2'],
							'C_PALBUM_2' => $calbum['2'],
							'T_PALBUM_2' => $palbum['2'],
							'I_PALBUM_2' => $qalbum['2'],
							'A_PALBUM_2' => $ralbum['2'],
							'U_PALBUM_2' => $ualbum['2'],
							'V_PALBUM_2' => $valbum['2'],
							'G_PALBUM_3' => ($galbum['3']) ? '1' : '',
							'S_PALBUM_3' => $talbum['3'],
							'C_PALBUM_3' => $calbum['3'],
							'T_PALBUM_3' => $palbum['3'],
							'I_PALBUM_3' => $qalbum['3'],
							'A_PALBUM_3' => $ralbum['3'],
							'U_PALBUM_3' => $ualbum['3'],
							'V_PALBUM_3' => $valbum['3'],
							'G_PALBUM_4' => ($galbum['4']) ? '1' : '',
							'S_PALBUM_4' => $talbum['4'],
							'C_PALBUM_4' => $calbum['4'],
							'T_PALBUM_4' => $palbum['4'],
							'I_PALBUM_4' => $qalbum['4'],
							'A_PALBUM_4' => $ralbum['4'],
							'U_PALBUM_4' => $ualbum['4'],
							'V_PALBUM_4' => $valbum['4'],
							'G_PALBUM_5' => ($galbum['5']) ? '1' : '',
							'S_PALBUM_5' => $talbum['5'],
							'C_PALBUM_5' => $calbum['5'],
							'T_PALBUM_5' => $palbum['5'],
							'I_PALBUM_5' => $qalbum['5'],
							'A_PALBUM_5' => $ralbum['5'],
							'U_PALBUM_5' => $ualbum['5'],
							'V_PALBUM_5' => $valbum['5'],
							'G_PALBUM_6' => ($galbum['6']) ? '1' : '',
							'S_PALBUM_6' => $talbum['6'],
							'C_PALBUM_6' => $calbum['6'],
							'T_PALBUM_6' => $palbum['6'],
							'I_PALBUM_6' => $qalbum['6'],
							'A_PALBUM_6' => $ralbum['6'],
							'U_PALBUM_6' => $ualbum['6'],
							'V_PALBUM_6' => $valbum['6'],
							'G_PALBUM_7' => ($galbum['7']) ? '1' : '',
							'S_PALBUM_7' => $talbum['7'],
							'C_PALBUM_7' => $calbum['7'],
							'T_PALBUM_7' => $palbum['7'],
							'I_PALBUM_7' => $qalbum['7'],
							'A_PALBUM_7' => $ralbum['7'],
							'U_PALBUM_7' => $ualbum['7'],
							'V_PALBUM_7' => $valbum['7'],
							'G_PALBUM_8' => ($galbum['8']) ? '1' : '',
							'S_PALBUM_8' => $talbum['8'],
							'C_PALBUM_8' => $calbum['8'],
							'T_PALBUM_8' => $palbum['8'],
							'I_PALBUM_8' => $qalbum['8'],
							'A_PALBUM_8' => $ralbum['8'],
							'U_PALBUM_8' => $ualbum['8'],
							'V_PALBUM_8' => $valbum['8'],
							'G_PALBUM_9' => ($galbum['9']) ? '1' : '',
							'S_PALBUM_9' => $talbum['9'],
							'C_PALBUM_9' => $calbum['9'],
							'T_PALBUM_9' => $palbum['9'],
							'I_PALBUM_9' => $qalbum['9'],
							'A_PALBUM_9' => $ralbum['9'],
							'U_PALBUM_9' => $ualbum['9'],
							'V_PALBUM_9' => $valbum['9'],
							'G_PALBUM_10' => ($galbum['10']) ? '1' : '',
							'S_PALBUM_10' => $talbum['10'],
							'C_PALBUM_10' => $calbum['10'],
							'T_PALBUM_10' => $palbum['10'],
							'I_PALBUM_10' => $qalbum['10'],
							'A_PALBUM_10' => $ralbum['10'],
							'U_PALBUM_10' => $ualbum['10'],
							'V_PALBUM_10' => $valbum['10'],
							'T_PALBUM_10' => $palbum['10'],
							'G_PALBUM_11' => ($galbum['11']) ? '1' : '',
							'S_PALBUM_11' => $talbum['11'],
							'C_PALBUM_11' => $calbum['11'],
							'T_PALBUM_11' => $palbum['11'],
							'I_PALBUM_11' => $qalbum['11'],
							'A_PALBUM_11' => $ralbum['11'],
							'U_PALBUM_11' => $ualbum['11'],
							'V_PALBUM_11' => $valbum['11'],
							'G_PALBUM_12' => ($galbum['12']) ? '1' : '',
							'S_PALBUM_12' => $talbum['12'],
							'C_PALBUM_12' => $calbum['12'],
							'T_PALBUM_12' => $palbum['12'],
							'I_PALBUM_12' => $qalbum['12'],
							'A_PALBUM_12' => $ralbum['12'],
							'U_PALBUM_12' => $ualbum['12'],
							'V_PALBUM_12' => $valbum['12'],
							'G_PALBUM_13' => ($galbum['13']) ? '1' : '',
							'S_PALBUM_13' => $talbum['13'],
							'C_PALBUM_13' => $calbum['13'],
							'T_PALBUM_13' => $palbum['13'],
							'I_PALBUM_13' => $qalbum['13'],
							'A_PALBUM_13' => $ralbum['13'],
							'U_PALBUM_13' => $ualbum['13'],
							'V_PALBUM_13' => $valbum['13'],
							'G_PALBUM_14' => ($galbum['14']) ? '1' : '',
							'S_PALBUM_14' => $talbum['14'],
							'C_PALBUM_14' => $calbum['14'],
							'T_PALBUM_14' => $palbum['14'],
							'I_PALBUM_14' => $qalbum['14'],
							'A_PALBUM_14' => $ralbum['14'],
							'U_PALBUM_14' => $ualbum['14'],
							'V_PALBUM_14' => $valbum['14'],
							'G_PALBUM_15' => ($galbum['15']) ? '1' : '',
							'S_PALBUM_15' => $talbum['15'],
							'C_PALBUM_15' => $calbum['15'],
							'T_PALBUM_15' => $palbum['15'],
							'I_PALBUM_15' => $qalbum['15'],
							'A_PALBUM_15' => $ralbum['15'],
							'U_PALBUM_15' => $ualbum['15'],
							'V_PALBUM_15' => $valbum['15'],
							'G_PALBUM_16' => ($galbum['16']) ? '1' : '',
							'S_PALBUM_16' => $talbum['16'],
							'C_PALBUM_16' => $calbum['16'],
							'T_PALBUM_16' => $palbum['16'],
							'I_PALBUM_16' => $qalbum['16'],
							'A_PALBUM_16' => $ralbum['16'],
							'U_PALBUM_16' => $ualbum['16'],
							'V_PALBUM_16' => $valbum['16'],
							'G_PALBUM_17' => ($galbum['17']) ? '1' : '',
							'S_PALBUM_17' => $talbum['17'],
							'C_PALBUM_17' => $calbum['17'],
							'T_PALBUM_17' => $palbum['17'],
							'I_PALBUM_17' => $qalbum['17'],
							'A_PALBUM_17' => $ralbum['17'],
							'U_PALBUM_17' => $ualbum['17'],
							'V_PALBUM_17' => $valbum['17'],
							'G_PALBUM_18' => ($galbum['18']) ? '1' : '',
							'S_PALBUM_18' => $talbum['18'],
							'C_PALBUM_18' => $calbum['18'],
							'T_PALBUM_18' => $palbum['18'],
							'I_PALBUM_18' => $qalbum['18'],
							'A_PALBUM_18' => $ralbum['18'],
							'U_PALBUM_18' => $ualbum['18'],
							'V_PALBUM_18' => $valbum['18'],
							'G_PALBUM_19' => ($galbum['19']) ? '1' : '',
							'S_PALBUM_19' => $talbum['19'],
							'C_PALBUM_19' => $calbum['19'],
							'T_PALBUM_19' => $palbum['19'],
							'I_PALBUM_19' => $qalbum['19'],
							'A_PALBUM_19' => $ralbum['19'],
							'U_PALBUM_19' => $ualbum['19'],
							'V_PALBUM_19' => $valbum['19'],
							'G_PALBUM_20' => ($galbum['20']) ? '1' : '',
							'S_PALBUM_20' => $talbum['20'],
							'C_PALBUM_20' => $calbum['20'],
							'T_PALBUM_20' => $palbum['20'],
							'I_PALBUM_20' => $qalbum['20'],
							'A_PALBUM_20' => $ralbum['20'],
							'U_PALBUM_20' => $ualbum['20'],
							'V_PALBUM_20' => $valbum['20'],
							'T_PALBUM_21' => $palbum['21'],
							'G_PALBUM_21' => ($galbum['21']) ? '1' : '',
							'S_PALBUM_21' => $talbum['21'],
							'C_PALBUM_21' => $calbum['21'],
							'T_PALBUM_21' => $palbum['21'],
							'I_PALBUM_21' => $qalbum['21'],
							'A_PALBUM_21' => $ralbum['21'],
							'U_PALBUM_21' => $ualbum['21'],
							'V_PALBUM_21' => $valbum['21'],
							'G_PALBUM_22' => ($galbum['22']) ? '1' : '',
							'S_PALBUM_22' => $talbum['22'],
							'C_PALBUM_22' => $calbum['22'],
							'T_PALBUM_22' => $palbum['22'],
							'I_PALBUM_22' => $qalbum['22'],
							'A_PALBUM_22' => $ralbum['22'],
							'U_PALBUM_22' => $ualbum['22'],
							'V_PALBUM_22' => $valbum['22'],
							'G_PALBUM_23' => ($galbum['23']) ? '1' : '',
							'S_PALBUM_23' => $talbum['23'],
							'C_PALBUM_23' => $calbum['23'],
							'T_PALBUM_23' => $palbum['23'],
							'I_PALBUM_23' => $qalbum['23'],
							'A_PALBUM_23' => $ralbum['23'],
							'U_PALBUM_23' => $ualbum['23'],
							'V_PALBUM_23' => $valbum['23'],
							'G_PALBUM_24' => ($galbum['24']) ? '1' : '',
							'S_PALBUM_24' => $talbum['24'],
							'C_PALBUM_24' => $calbum['24'],
							'T_PALBUM_24' => $palbum['24'],
							'I_PALBUM_24' => $qalbum['24'],
							'A_PALBUM_24' => $ralbum['24'],
							'U_PALBUM_24' => $ualbum['24'],
							'V_PALBUM_24' => $valbum['24'],
							'G_PALBUM_25' => ($galbum['25']) ? '1' : '',
							'S_PALBUM_25' => $talbum['25'],
							'C_PALBUM_25' => $calbum['25'],
							'T_PALBUM_25' => $palbum['25'],
							'I_PALBUM_25' => $qalbum['25'],
							'A_PALBUM_25' => $ralbum['25'],
							'U_PALBUM_25' => $ualbum['25'],
							'V_PALBUM_25' => $valbum['25'],
							'G_PALBUM_26' => ($galbum['26']) ? '1' : '',
							'S_PALBUM_26' => $talbum['26'],
							'C_PALBUM_26' => $calbum['26'],
							'T_PALBUM_26' => $palbum['26'],
							'I_PALBUM_26' => $qalbum['26'],
							'A_PALBUM_26' => $ralbum['26'],
							'U_PALBUM_26' => $ualbum['26'],
							'V_PALBUM_26' => $valbum['26'],
							'G_PALBUM_27' => ($galbum['27']) ? '1' : '',
							'S_PALBUM_27' => $talbum['27'],
							'C_PALBUM_27' => $calbum['27'],
							'T_PALBUM_27' => $palbum['27'],
							'I_PALBUM_27' => $qalbum['27'],
							'A_PALBUM_27' => $ralbum['27'],
							'U_PALBUM_27' => $ualbum['27'],
							'V_PALBUM_27' => $valbum['27'],
							'G_PALBUM_28' => ($galbum['28']) ? '1' : '',
							'S_PALBUM_28' => $talbum['28'],
							'C_PALBUM_28' => $calbum['28'],
							'T_PALBUM_28' => $palbum['28'],
							'I_PALBUM_28' => $qalbum['28'],
							'A_PALBUM_28' => $ralbum['28'],
							'U_PALBUM_28' => $ualbum['28'],
							'V_PALBUM_28' => $valbum['28'],
							'G_PALBUM_29' => ($galbum['29']) ? '1' : '',
							'S_PALBUM_29' => $talbum['29'],
							'C_PALBUM_29' => $calbum['29'],
							'T_PALBUM_29' => $palbum['29'],
							'I_PALBUM_29' => $qalbum['29'],
							'A_PALBUM_29' => $ralbum['29'],
							'U_PALBUM_29' => $ualbum['29'],
							'V_PALBUM_29' => $valbum['29'],
							'G_PALBUM_30' => ($galbum['30']) ? '1' : '',
							'S_PALBUM_30' => $talbum['30'],
							'C_PALBUM_30' => $calbum['30'],
							'T_PALBUM_30' => $palbum['30'],
							'I_PALBUM_30' => $qalbum['30'],
							'A_PALBUM_30' => $ralbum['30'],
							'U_PALBUM_30' => $ualbum['30'],
							'V_PALBUM_30' => $valbum['30'],
							'T_PALBUM_31' => $palbum['30'],
							'G_PALBUM_31' => ($galbum['31']) ? '1' : '',
							'S_PALBUM_31' => $talbum['31'],
							'C_PALBUM_31' => $calbum['31'],
							'T_PALBUM_31' => $palbum['31'],
							'I_PALBUM_31' => $qalbum['31'],
							'A_PALBUM_31' => $ralbum['31'],
							'U_PALBUM_31' => $ualbum['31'],
							'V_PALBUM_31' => $valbum['31'],
							'G_SLIDESHOW' => ($pnsettings['show_album'] != 0 && $row['album_cnt'] != 0 && $row['album_id'] && $row['album_id'] != '0') ? '1' : '0',
							'S_SLIDESHOW' => ($row['slide_show'] == '1' || $row['slide_show'] == '3') ? $openLink.'<a href="javascript:load()"><img src="themes/'.$CPG_SESS['theme'].'/images/'.strtolower($module_name).'/slideshow.png" border="0" alt="'._PNSLDSHOW.'" /></a>' : '',
							'T_SLIDESHOW' => ($row['slide_show'] == '1' || $row['slide_show'] == '3') ? '<a href="javascript:load()">'._PNSLDSHOW.'</a>' : '',
							'L_FIELDS' => (!$user_fld_0) ? '' : ($row['usrfldttl']) ? $row['usrfldttl'] : _PNDETAILS,
							'G_FIELDS' => ($user_fld_0) ? '1' : '',
							'S_USER_FLD_0' => (!$user_fld_0) ? '' : ($row['usrfld0']) ? $row['usrfld0'] : _PNUSRFLD0,
							'T_USER_FLD_0' => $user_fld_0,
							'G_FIELDS_1' => ($user_fld_1) ? '1' : '',
							'S_USER_FLD_1' => (!$user_fld_1) ? '' : ($row['usrfld1']) ? $row['usrfld1'] : _PNUSRFLD1,
							'T_USER_FLD_1' => $user_fld_1,
							'G_FIELDS_2' => ($user_fld_2) ? '1' : '',
							'S_USER_FLD_2' => (!$user_fld_2) ? '' : ($row['usrfld2']) ? $row['usrfld2'] : _PNUSRFLD2,
							'T_USER_FLD_2' => $user_fld_2,
							'G_FIELDS_3' => ($user_fld_3) ? '1' : '',
							'S_USER_FLD_3' => (!$user_fld_3) ? '' : ($row['usrfld3']) ? $row['usrfld3'] : _PNUSRFLD3,
							'T_USER_FLD_3' => $user_fld_3,
							'G_FIELDS_4' => ($user_fld_4) ? '1' : '',
							'S_USER_FLD_4' => (!$user_fld_4) ? '' : ($row['usrfld4']) ? $row['usrfld4'] : _PNUSRFLD4,
							'T_USER_FLD_4' => $user_fld_4,
							'G_FIELDS_5' => ($user_fld_5) ? '1' : '',
							'S_USER_FLD_5' => (!$user_fld_5) ? '' : ($row['usrfld5']) ? $row['usrfld5'] : _PNUSRFLD5,
							'T_USER_FLD_5' => $user_fld_5,
							'G_FIELDS_6' => ($user_fld_6) ? '1' : '',
							'S_USER_FLD_6' => (!$user_fld_6) ? '' : ($row['usrfld6']) ? $row['usrfld6'] : _PNUSRFLD6,
							'T_USER_FLD_6' => $user_fld_6,
							'G_FIELDS_7' => ($user_fld_7) ? '1' : '',
							'S_USER_FLD_7' => (!$user_fld_7) ? '' : ($row['usrfld7']) ? $row['usrfld7'] : _PNUSRFLD7,
							'T_USER_FLD_7' => $user_fld_7,
							'G_FIELDS_8' => ($user_fld_8) ? '1' : '',
							'S_USER_FLD_8' => (!$user_fld_8) ? '' : ($row['usrfld8']) ? $row['usrfld8'] : _PNUSRFLD8,
							'T_USER_FLD_8' => $user_fld_8,
							'G_FIELDS_9' => ($user_fld_9) ? '1' : '',
							'S_USER_FLD_9' => (!$user_fld_9) ? '' : ($row['usrfld9']) ? $row['usrfld9'] : _PNUSRFLD9,
							'T_USER_FLD_9' => $user_fld_9,
							'L_DISCUSS' => ($row['topic_id'] ? _PNDISCUSSION :_PNDISCUSSNEW),
							'U_DISCUSS' => 'javascript:void()',
							'S_DISCUSS' => _PNDISCUSS,
							'G_DISCUSS' => (!($row['sforum_id'] == '0' && $row['cforum_id'] == '0') && $row['cforum_id'] != -1 && ($row['allow_comment'] == '1') && ($pnsettings['comments'] == '1')) ? '1' : '',
							'S_NUMCMNTS' => '',
							'G_PLINK' => $pnsettings['permalink'],
							'S_PLINK' => _PNLINK,
							'T_PLINK' => 'javascript:void()',
							'G_SENDPRINT' => '1',
							'G_EMAILF' => ($pnsettings['emailf'] && is_user()) ? '1' : '',
							'S_SENDART' => _PNSENDART,
							'U_SENDART' => getlink("&amp;mode=send&amp;id=".$row['id'].$url_text),
							'G_PRINTF' => ($pnsettings['printf']) ? '1' : '',
							'S_PRINTART' => _PNPRINTART,
							'U_PRINTART' => getlink("&amp;mode=prnt&amp;id=".$row['id'].$url_text),
							'G_MOREPICS' => ($pnsettings['per_gllry'] != 0 && $numpics < $row['album_cnt'] && $row['album_id'] && $row['album_id'] != '0' && $row['slide_show'] > '1') ? '1' : '0',
							'T_MOREPICS' => _PNMOREPICS,
							'U_MOREPICS' => ($row['id'] != '') ? getlink("&amp;mode=gllry&amp;id=".$row['id']."&amp;npic=".$row['album_cnt']) : 'javascript:void()',
							'G_SOCIALNET' => $pnsettings['soc_net'],
							'U_SOCNETLINK' => urlencode($BASEHREF.getlink("&amp;aid=".$row['id'])),
							'S_SOCTITLE' => urlencode($row['title']),
							'L_ID' => _PNREFNO,
							'S_ID' => ($id) ? $id : '???',
							'T_DFTOPIC' => ($pnsettings['topic_lnk'] == 1) ? $row['topictext'] : '',
							'U_DFTOPIC' => ($pnsettings['topic_lnk'] == 1) ? ((file_exists("themes/$CPG_SESS[theme]/images/topics/".$row['topicimage']) ? "themes/$CPG_SESS[theme]/" : '').'images/topics/'.$row['topicimage']) : '',
							'L_ASSOCARTICLE' => _PNLSTLNKS,
							'L_ASSOCARTICLES' => $assoclst,  // Related article, modified by Masino Sinaga, June 22, 2009
							'S_ASSOCARTICLE' => _PNRELATEDART,		// Related Articles, modified by Masino Sinaga, June 22, 2009
							'S_ASSOCARTICLES' => $assoc,  // Related article, modified by Masino Sinaga, June 22, 2009
							'G_SHOW_READS' => $pnsettings['show_reads'],
							'S_READS' => _PNREADS,
							'T_READS' => '0',
							'G_RATE' => '',
							'G_SCORE' => '0',
							'U_CANADMIN' => '',
							'S_CANADMIN' => '',
							'T_CANADMIN' => '',
							'G_CANADMIN' => ''
						));
						if ($row['title'] == '' || $row['catid'] == '' || $row['intro'] == '') {
							$msg = '<b>'._PNWARNING.'</b>';
							$msg .= ($row['title'] == '') ? '<br /> &nbsp; '._PNNOTITLE : '';
							$msg .= ($row['catid'] == '') ? '<br /> &nbsp; '._PNNOCATEGORY : '';
							$msg .= ($row['intro'] == '') ? '<br /> &nbsp; '._PNNOINTRO : '';
							$msg .= '';
							$cpgtpl->assign_block_vars('pn_warning', array(
								'S_WARN' => $msg
							));
						}
						$pagetitle .= (!$pnsettings['SEOtitle'] ? ' '._BC_DELIM.' ' : '')._PNSUBMIT;
						$tpl = ($row['template'] != '') ? $row['template'] : $pnsettings['template'];
						$cpgtpl->set_filenames(array('body' => 'pronews/article/'.$tpl));
						$cpgtpl->display('body');
						ProNews::article_form($row);
						if ($tpl && file_exists('themes/'.$CPG_SESS['theme'].'/template/pronews/submit/'.$tpl)) {
							$sbmt = 'pronews/submit/'.$tpl;
						} elseif ($tpl && file_exists('themes/'.$CPG_SESS['theme'].'/template/pronews/submit/submit.html')) {
							$sbmt = 'pronews/submit/submit.html';
						} else {
							$sbmt = 'pronews/submit.html';
						}
						$cpgtpl->set_filenames(array('body' => $sbmt));
						$cpgtpl->display('body', false);
					}
				}
			}
		}
//		$pagetitle .= ' '._BC_DELIM.' '._PNSUBMIT;
//		if ($tpl && file_exists('themes/'.$CPG_SESS['theme'].'/template/pronews/submit/'.$tpl)) {
//			$sbmt = 'pronews/submit/'.$tpl;
//		} else {
//			$sbmt = 'pronews/submit.html';
//		}
//		$cpgtpl->set_filenames(array('body' => $sbmt));
//		$cpgtpl->display('body');
	}

	function cleanse($field) {
		if ($field) {
			$field = str_replace("\\r\\n", "\n", $field);
			$field = str_replace("\\'", "'", $field);
			$field = str_replace('\\"', '"', $field);
			$field = str_replace("\\\\", "\\", $field);
		}
		return $field;
	}

	function albums($name,$id='') {
		global $db, $prefix;
		$albnum = '10000' + is_user();
		$sql = 'SELECT a.title atitle, a.aid aid FROM '.$prefix.'_cpg_albums as a';
		$sql .= ' WHERE a.category='.$albnum;
		$sql .= ' ORDER BY atitle';
		$result = $db->sql_query($sql);
		$list = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);
		$album = 'none';
		if (($list) && ($list != '')) {
			$selected = ($id != '') ? $id : '';
			$album = '<select name="'.$name.'"><option value="">-- '._PNSELONE.' --</option>';
			foreach ($list as $value) {
					$select = ($value['1'] == $selected) ? ' selected="selected"' : '';
					$album .= '<option value="'.$value['1'].'"'.$select.'>'.$value['0'].'</option>';
			}
			$album .= '</select>';
		}
		return $album;
	}

	function img($image='',$realname='') {
		global $pnsettings, $db, $prefix;
		if (!isset($pnsettings)) {$pnsettings = $db->sql_fetchrow($db->sql_query('SELECT * FROM '.$prefix.'_pronews_settings'));} // Added to match admin_img() - layingback 061123
		if ($image == '') {return '';}
		else {
			if (file_exists($pnsettings['imgpath'].'/'.$realname)) {
				$image = '';
				echo '<center><b>'._PNDUPFILEA.' '.$realname.' '._PNDUPFILEB.'</b></center><br />';
			} else {
				list($width, $height, $type, $attr) = getimagesize($image);
				$mtime = array_sum(explode(" ",microtime()));
				if ($type == IMAGETYPE_JPEG) {$newname = $mtime.".jpg";}
				elseif ($type == IMAGETYPE_GIF) {$newname = $mtime.".gif";}
				elseif ($type == IMAGETYPE_PNG) {$newname = $mtime.".png";}
				else {cpg_error(_PNINVTYP."<br /><br />(Imagetype ".$type." is not a supported image type)");}
				copy($image, $pnsettings['imgpath'].'/'.$newname);
				$need_resize = 0;
				if ($width > $pnsettings['img_limit'] || $height > $pnsettings['img_limit']) {
					$need_resize = 1;
				}
				$need_thumb = 0;
				if ($width > $pnsettings['max_w'] || $height > $pnsettings['max_h']) {
					$need_thumb = 1;
				}
				$path = $pnsettings['imgpath'].'/'.$newname;
				if ($need_resize) {
					$resize = $pnsettings['imgpath'].'/'.$newname;
				}
				if ($need_thumb) {$thumb = $pnsettings['imgpath'].'/thumb_'.$newname;}
				$icon = $pnsettings['imgpath'].'/icon_'.$newname;
				$size = getimagesize($path);
				if ($type == IMAGETYPE_JPEG) {$im = @imagecreatefromjpeg($path);}
				elseif ($type == IMAGETYPE_GIF) {$im = @imagecreatefromgif($path);}
				elseif ($type == IMAGETYPE_PNG) {$im = @imagecreatefrompng($path);}
				else {cpg_error(_PNINVTYP."<br /><br />(Imagetype $type is not supported)");}
				if ($pnsettings['aspect'] == '1' || $pnsettings['aspect'] == '0' && ($size[0] >= $size[1])) {	// by Width || Max Dimn
					$sizemax[0] = $pnsettings['img_limit'];
					$sizemax[1] = $pnsettings['img_limit'] * ($size[1] / $size[0]);
					$sizemin[0] = $pnsettings['max_w'];
					$sizemin[1] = $pnsettings['max_w'] * ($size[1] / $size[0]);
				} else {
					$sizemax[0] = $pnsettings['img_limit'] * ($size[0] / $size[1]);
					$sizemax[1] = $pnsettings['img_limit'];
					$sizemin[0] = $pnsettings['max_h'] * ($size[0] / $size[1]);
					$sizemin[1] = $pnsettings['max_h'];
				}
				$tinysize[0] = $sizemin[0] / 4;
				$tinysize[1] = $sizemin[1] / 4;
				if ($need_resize) {
					$reimage = imagecreatetruecolor($sizemax[0], $sizemax[1]) or die('Cannot Initialize new GD image stream (201)');		// die by xfunsoles Mar 07
					imagefilledrectangle($reimage, 0, 0, $sizemax[0], $sizemax[1], imagecolorallocatealpha($reimage, 255, 255, 255, 127));
					imagealphablending($reimage, false);
					imagesavealpha($reimage, true);
					imagecopyresampled($reimage, $im, 0, 0, 0, 0, $sizemax[0], $sizemax[1], $size[0], $size[1]);
				}
				if ($need_thumb) {
					$small = imagecreatetruecolor($sizemin[0], $sizemin[1]) or die('Cannot Initialize new GD image stream (202)');		// die by xfunsoles Mar 07
					imagefilledrectangle($small, 0, 0, $sizemin[0], $sizemin[1], imagecolorallocatealpha($small, 255, 255, 255, 127));
					imagealphablending($small, false);
					imagesavealpha($small, true);
					imagecopyresampled($small, $im, 0, 0, 0, 0, $sizemin[0], $sizemin[1], $size[0], $size[1]);
				}
				$tiny = imagecreatetruecolor($tinysize[0], $tinysize[1]) or die('Cannot Initialize new GD image stream (203)');
				imagefilledrectangle($tiny, 0, 0, $tinysize[0], $tinysize[1], imagecolorallocatealpha($tiny, 255, 255, 255, 127));
				imagealphablending($tiny, false);
				imagesavealpha($tiny, true);
				imagecopyresampled($tiny, $im, 0, 0, 0, 0, $tinysize[0], $tinysize[1], $size[0], $size[1]);
				imagedestroy($im);
				if ($type == IMAGETYPE_JPEG) {
					if ($need_resize) {imagejpeg($reimage, $resize, 100);}
					if ($need_thumb) {imagejpeg($small, $thumb, 100);}
					imagejpeg($tiny, $icon, 100);
				}
				if ($type == IMAGETYPE_GIF) {
					if ($need_resize) {imagegif($reimage, $resize);}
					if ($need_thumb) {imagegif($small, $thumb);}
					imagegif($tiny, $icon);
				}
				if ($type == IMAGETYPE_PNG) {
					if ($need_resize) {imagepng($reimage, $resize, 0);}
					if ($need_thumb) {imagepng($small, $thumb, 0);}
					imagepng($tiny, $icon, 0);
				}
				if ($need_thumb) {imagedestroy($small);}					// by xfsunoles Mar 07
				imagedestroy($tiny);
				return $newname;
			}
		}
	}

	function slideshow() {
		global $cpgtpl;
		require_once("includes/slideshow.inc");
		$start_slideshow = '<script language="JavaScript" type="text/JavaScript">runSlideShow()</script>';
		$cpgtpl->assign_block_vars('slideshow', array(
			'T_WINDOW' => $start_slideshow,
			'T_IMAGE' => '<img src="' . $start_img . '" name="SlideShow" class="image" alt="" /><br />',
			'T_CELL_HEIGHT' => "100%",
			'S_CLOSEMSG' => _PNCLOSESLIDE
		));
		$cpgtpl->set_filenames(array('body' => 'pronews/slideshow.html'));
		$cpgtpl->display('body');
		echo '</body></html>';
	}

	function gallery($aid,$npic) {
		global $BASEHREF, $db, $prefix, $cpgtpl, $pnsettings, $pagetitle, $CPG_SESS, $module_name, $userinfo;

		$album_order = array(0=>'',1=>'title',2=>'title',3=>'filename',4=>'filename',5=>'ctime',6=>'ctime',7=>'pic_rating',8=>'pic_rating');
		$sql = 'SELECT a.*, c.title ctitle, template, usrfld0, usrfld1, usrfld2, usrfld3, usrfld4, usrfld5, usrfld6, usrfld7, usrfld8, usrfld9, usrfldttl';
		$sql .= ' FROM '.$prefix.'_pronews_articles as a';
		$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
		$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
		$sql .= ' WHERE a.id='.$aid;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		$target = 'pn'.uniqid(rand());
		if(($row['image'] != '') && ($row['image'] != '0')) {
			$imagesize = getimagesize($pnsettings['imgpath'].'/'.$row['image']);  // fitted window - layingback 061119
			$imagesizeX = $imagesize[0] + 16; $imagesizeY = $imagesize[1] + 16;
			if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
				$thumbimage = $pnsettings['imgpath'].'/thumb_'.$row['image'];
				$display_image = '<a href="'.$pnsettings['imgpath'].'/'.$row['image'].'" target="'.$target.'"
onclick="PN_openBrWindow(\''.$BASEHREF.$pnsettings['imgpath'].'/'.$row['image'].'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;"><img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" /></a>';
			} else {
				$thumbimage = $pnsettings['imgpath'].'/'.$row['image'];  // Check if thumb exists before linking - layingback 061122
				$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" />';
			}
		} else {
			$display_image = '';
		}
		if(($row['image2'] != '') && ($row['image2'] != '0')) {
			$imagesize = getimagesize($pnsettings['imgpath'].'/'.$row['image2']);  // fitted window - layingback 061119
			$imagesizeX = $imagesize[0] + 16; $imagesizeY = $imagesize[1] + 16;
			if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
				$thumbimage = $pnsettings['imgpath'].'/thumb_'.$row['image2'];
				$display2_image = '<a href="'.$pnsettings['imgpath'].'/'.$row['image2'].'" target="'.$target.'"
	onclick="PN_openBrWindow(\''.$BASEHREF.$pnsettings['imgpath'].'/'.$row['image2'].'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;"><img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" /></a>';
			} else {
				$thumbimage = $pnsettings['imgpath'].'/'.$row['image2'];  // Check if thumb exists before linking - layingback 061122
				$display2_image = '<img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" />';
			}
		} else {
			$display2_image = '';
		}
		if (isset($row) && $row != '') {
		$numpics = '0';
		$maxsizeX = '200';
		$maxsizeY = '200';
		if ($row['album_id'] != '0') {
			$sql = 'SELECT * FROM '.$prefix.'_cpg_pictures';
			$sql .= ' WHERE aid='.$row['album_id'];
			$asc_desc = ($row['album_seq'] & 1) ? 'ASC' : 'DESC';
			if ($row['album_seq'] != '0') {$sql .= ' ORDER BY '.$album_order[$row['album_seq']].' '.$asc_desc;}
			if ($npic == '0') {
				$sql .= ' LIMIT '.($pnsettings['per_gllry'] + 1);
			} else {
				$sql .= ' LIMIT '.$npic.', '.($pnsettings['per_gllry'] + 1);
			}
			$list = $db->sql_fetchrowset($db->sql_query($sql));
// echo 'limit='.count($list);
 			if (count($list) > $pnsettings['per_gllry']) {
				$smore = 1;
				array_pop($list);
			} else {
				$smore = 0;
			}
// echo ' nlimit='.count($list);
			if (($list) && ($list != "")) {
				foreach ($list as $key => $pic) {
					$fullsizepath = ($pic['remote_url'] != '' && preg_match("/(?:https?\:\/\/)?([^\.]+\.?[^\.\/]+\.[^\.\/]+[^\.]+)/", $pic['remote_url'], $matches)) ? 'http://'.$matches[1].'/' : $pic['filepath'];		// lb - cpg remote_url hack support
					$imagesizeX = $pic['pwidth'] + 16; $imagesizeY = $pic['pheight'] + 16;
					if ($pic['pwidth'] > $maxsizeX) { $maxsizeX = $pic['pwidth'];}
					if ($pic['pheight'] > $maxsizeY) { $maxsizeY = $pic['pheight'];}
					$thumb = str_replace("%2F", "/", rawurlencode($pic['filepath'].'thumb_'.$pic['filename']));
					$talbum[$key] = $pic['title'] != ' ' ? trim($pic['title']) : '&nbsp;';				// trim cos cpg adds trailing space!
					$calbum[$key] = ($pic['caption'] != '') ? $pic['caption'] : '&nbsp;';
					$palbum[$key] = '<a href="'.str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'" target="'.$target.'"
 onclick="PN_openBrWindow(\''.$BASEHREF.str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;"><img class="pn_image" src="'.$thumb.'" alt="'.$pic['title'].'" /></a>';
					$qalbum[$key] = str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename'])));
					$ralbum[$key] = str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'" target="'.$target.'"
 onclick="PN_openBrWindow(\''.$BASEHREF.str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;';
					$valbum[$key] = $thumb;
					$ualbum[$key] = '<img class="pn_image" src="'.str_replace("%2F", "/", rawurlencode($pic['filepath'].(file_exists($pic['filepath'].'normal_'.$pic['filename']) ? 'normal_' : '').$pic['filename'])).'" alt="" />';
					$galbum[$key] = '1';
					$lpic = $pic['pid'];		//track pid of last pic shown
					$numpics++;
				}
			}
			$j = $numpics;
			while ($j < '32') {
				$galbum[$j] = '';
				$palbum[$j] = '';
				$talbum[$j] = '';
				$j++;
			}
			$npic = $npic + $pnsettings['per_gllry'];

// echo ' numpics='.$numpics.' smore='.$smore.' mpics='.($pnsettings['per_gllry'] != 0 && $smore == 1 && $numpics == $pnsettings['per_gllry'] && $row['album_id'] != '0' && $row['slide_show'] > '1' ? : 0);
			$cpgtpl->assign_block_vars('gallery', array(
				'S_ICON' => ($row['icon'] != '') ? $row['icon'] : 'clearpixel.gif',
				'T_ICON' => $row['ctitle'],
				'S_TITLE' => $row['title'],
				'L_POSTBY' => _PNPOSTBY,
				'S_POSTBY' => $row['postby'],
				'T_POSTBY' => getlink("Your_Account&amp;profile=".$row['postby']),
				'L_POSTON' => _PNPOSTON,
				'S_POSTTIME' => ProNews::create_date(false, $row['posttime']),
				'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
				'S_CATEGORY' => $row['catid'],
				'S_IMAGE' => ($row['image'] == '' && $pnsettings['show_noimage'] == '0') ? '' : $display_image,
				'T_CAP' => ($row['caption'] == '') ? '&nbsp;' : $row['caption'],
				'S_IMAGE2' => $display2_image,
				'T_CAP2' => ($row['caption2'] == '') ? '&nbsp;' : $row['caption2'],
				'G_PALBUM_0' => ($galbum['0']) ? '1' : '',
				'S_PALBUM_0' => $talbum['0'],
				'C_PALBUM_0' => $calbum['0'],
				'T_PALBUM_0' => ($row['album_cnt'] == '1' && ($row['slide_show'] == '1' || $row['slide_show'] == '3')) ? '<a href="javascript:load()"><img class="pn_image" src="'.$thumb.'" alt="'.$row['caption'].'" /></a>' : $palbum['0'],
				'I_PALBUM_0' => $qalbum['0'],
				'A_PALBUM_0' => $ralbum['0'],
				'U_PALBUM_0' => $ualbum['0'],
				'V_PALBUM_0' => $valbum['0'],
				'T_PALBUM_1' => $palbum['1'],
				'G_PALBUM_1' => ($galbum['1']) ? '1' : '',
				'S_PALBUM_1' => $talbum['1'],
				'C_PALBUM_1' => $calbum['1'],
				'T_PALBUM_1' => $palbum['1'],
				'I_PALBUM_1' => $qalbum['1'],
				'A_PALBUM_1' => $ralbum['1'],
				'U_PALBUM_1' => $ualbum['1'],
				'V_PALBUM_1' => $valbum['1'],
				'G_PALBUM_2' => ($galbum['2']) ? '1' : '',
				'S_PALBUM_2' => $talbum['2'],
				'C_PALBUM_2' => $calbum['2'],
				'T_PALBUM_2' => $palbum['2'],
				'I_PALBUM_2' => $qalbum['2'],
				'A_PALBUM_2' => $ralbum['2'],
				'U_PALBUM_2' => $ualbum['2'],
				'V_PALBUM_2' => $valbum['2'],
				'G_PALBUM_3' => ($galbum['3']) ? '1' : '',
				'S_PALBUM_3' => $talbum['3'],
				'C_PALBUM_3' => $calbum['3'],
				'T_PALBUM_3' => $palbum['3'],
				'I_PALBUM_3' => $qalbum['3'],
				'A_PALBUM_3' => $ralbum['3'],
				'U_PALBUM_3' => $ualbum['3'],
				'V_PALBUM_3' => $valbum['3'],
				'G_PALBUM_4' => ($galbum['4']) ? '1' : '',
				'S_PALBUM_4' => $talbum['4'],
				'C_PALBUM_4' => $calbum['4'],
				'T_PALBUM_4' => $palbum['4'],
				'I_PALBUM_4' => $qalbum['4'],
				'A_PALBUM_4' => $ralbum['4'],
				'U_PALBUM_4' => $ualbum['4'],
				'V_PALBUM_4' => $valbum['4'],
				'G_PALBUM_5' => ($galbum['5']) ? '1' : '',
				'S_PALBUM_5' => $talbum['5'],
				'C_PALBUM_5' => $calbum['5'],
				'T_PALBUM_5' => $palbum['5'],
				'I_PALBUM_5' => $qalbum['5'],
				'A_PALBUM_5' => $ralbum['5'],
				'U_PALBUM_5' => $ualbum['5'],
				'V_PALBUM_5' => $valbum['5'],
				'G_PALBUM_6' => ($galbum['6']) ? '1' : '',
				'S_PALBUM_6' => $talbum['6'],
				'C_PALBUM_6' => $calbum['6'],
				'T_PALBUM_6' => $palbum['6'],
				'I_PALBUM_6' => $qalbum['6'],
				'A_PALBUM_6' => $ralbum['6'],
				'U_PALBUM_6' => $ualbum['6'],
				'V_PALBUM_6' => $valbum['6'],
				'G_PALBUM_7' => ($galbum['7']) ? '1' : '',
				'S_PALBUM_7' => $talbum['7'],
				'C_PALBUM_7' => $calbum['7'],
				'T_PALBUM_7' => $palbum['7'],
				'I_PALBUM_7' => $qalbum['7'],
				'A_PALBUM_7' => $ralbum['7'],
				'U_PALBUM_7' => $ualbum['7'],
				'V_PALBUM_7' => $valbum['7'],
				'G_PALBUM_8' => ($galbum['8']) ? '1' : '',
				'S_PALBUM_8' => $talbum['8'],
				'C_PALBUM_8' => $calbum['8'],
				'T_PALBUM_8' => $palbum['8'],
				'I_PALBUM_8' => $qalbum['8'],
				'A_PALBUM_8' => $ralbum['8'],
				'U_PALBUM_8' => $ualbum['8'],
				'V_PALBUM_8' => $valbum['8'],
				'G_PALBUM_9' => ($galbum['9']) ? '1' : '',
				'S_PALBUM_9' => $talbum['9'],
				'C_PALBUM_9' => $calbum['9'],
				'T_PALBUM_9' => $palbum['9'],
				'I_PALBUM_9' => $qalbum['9'],
				'A_PALBUM_9' => $ralbum['9'],
				'U_PALBUM_9' => $ualbum['9'],
				'V_PALBUM_9' => $valbum['9'],
				'G_PALBUM_10' => ($galbum['10']) ? '1' : '',
				'S_PALBUM_10' => $talbum['10'],
				'C_PALBUM_10' => $calbum['10'],
				'T_PALBUM_10' => $palbum['10'],
				'I_PALBUM_10' => $qalbum['10'],
				'A_PALBUM_10' => $ralbum['10'],
				'U_PALBUM_10' => $ualbum['10'],
				'V_PALBUM_10' => $valbum['10'],
				'T_PALBUM_10' => $palbum['10'],
				'G_PALBUM_11' => ($galbum['11']) ? '1' : '',
				'S_PALBUM_11' => $talbum['11'],
				'C_PALBUM_11' => $calbum['11'],
				'T_PALBUM_11' => $palbum['11'],
				'I_PALBUM_11' => $qalbum['11'],
				'A_PALBUM_11' => $ralbum['11'],
				'U_PALBUM_11' => $ualbum['11'],
				'V_PALBUM_11' => $valbum['11'],
				'G_PALBUM_12' => ($galbum['12']) ? '1' : '',
				'S_PALBUM_12' => $talbum['12'],
				'C_PALBUM_12' => $calbum['12'],
				'T_PALBUM_12' => $palbum['12'],
				'I_PALBUM_12' => $qalbum['12'],
				'A_PALBUM_12' => $ralbum['12'],
				'U_PALBUM_12' => $ualbum['12'],
				'V_PALBUM_12' => $valbum['12'],
				'G_PALBUM_13' => ($galbum['13']) ? '1' : '',
				'S_PALBUM_13' => $talbum['13'],
				'C_PALBUM_13' => $calbum['13'],
				'T_PALBUM_13' => $palbum['13'],
				'I_PALBUM_13' => $qalbum['13'],
				'A_PALBUM_13' => $ralbum['13'],
				'U_PALBUM_13' => $ualbum['13'],
				'V_PALBUM_13' => $valbum['13'],
				'G_PALBUM_14' => ($galbum['14']) ? '1' : '',
				'S_PALBUM_14' => $talbum['14'],
				'C_PALBUM_14' => $calbum['14'],
				'T_PALBUM_14' => $palbum['14'],
				'I_PALBUM_14' => $qalbum['14'],
				'A_PALBUM_14' => $ralbum['14'],
				'U_PALBUM_14' => $ualbum['14'],
				'V_PALBUM_14' => $valbum['14'],
				'G_PALBUM_15' => ($galbum['15']) ? '1' : '',
				'S_PALBUM_15' => $talbum['15'],
				'C_PALBUM_15' => $calbum['15'],
				'T_PALBUM_15' => $palbum['15'],
				'I_PALBUM_15' => $qalbum['15'],
				'A_PALBUM_15' => $ralbum['15'],
				'U_PALBUM_15' => $ualbum['15'],
				'V_PALBUM_15' => $valbum['15'],
				'G_PALBUM_16' => ($galbum['16']) ? '1' : '',
				'S_PALBUM_16' => $talbum['16'],
				'C_PALBUM_16' => $calbum['16'],
				'T_PALBUM_16' => $palbum['16'],
				'I_PALBUM_16' => $qalbum['16'],
				'A_PALBUM_16' => $ralbum['16'],
				'U_PALBUM_16' => $ualbum['16'],
				'V_PALBUM_16' => $valbum['16'],
				'G_PALBUM_17' => ($galbum['17']) ? '1' : '',
				'S_PALBUM_17' => $talbum['17'],
				'C_PALBUM_17' => $calbum['17'],
				'T_PALBUM_17' => $palbum['17'],
				'I_PALBUM_17' => $qalbum['17'],
				'A_PALBUM_17' => $ralbum['17'],
				'U_PALBUM_17' => $ualbum['17'],
				'V_PALBUM_17' => $valbum['17'],
				'G_PALBUM_18' => ($galbum['18']) ? '1' : '',
				'S_PALBUM_18' => $talbum['18'],
				'C_PALBUM_18' => $calbum['18'],
				'T_PALBUM_18' => $palbum['18'],
				'I_PALBUM_18' => $qalbum['18'],
				'A_PALBUM_18' => $ralbum['18'],
				'U_PALBUM_18' => $ualbum['18'],
				'V_PALBUM_18' => $valbum['18'],
				'G_PALBUM_19' => ($galbum['19']) ? '1' : '',
				'S_PALBUM_19' => $talbum['19'],
				'C_PALBUM_19' => $calbum['19'],
				'T_PALBUM_19' => $palbum['19'],
				'I_PALBUM_19' => $qalbum['19'],
				'A_PALBUM_19' => $ralbum['19'],
				'U_PALBUM_19' => $ualbum['19'],
				'V_PALBUM_19' => $valbum['19'],
				'G_PALBUM_20' => ($galbum['20']) ? '1' : '',
				'S_PALBUM_20' => $talbum['20'],
				'C_PALBUM_20' => $calbum['20'],
				'T_PALBUM_20' => $palbum['20'],
				'I_PALBUM_20' => $qalbum['20'],
				'A_PALBUM_20' => $ralbum['20'],
				'U_PALBUM_20' => $ualbum['20'],
				'V_PALBUM_20' => $valbum['20'],
				'T_PALBUM_21' => $palbum['21'],
				'G_PALBUM_21' => ($galbum['21']) ? '1' : '',
				'S_PALBUM_21' => $talbum['21'],
				'C_PALBUM_21' => $calbum['21'],
				'T_PALBUM_21' => $palbum['21'],
				'I_PALBUM_21' => $qalbum['21'],
				'A_PALBUM_21' => $ralbum['21'],
				'U_PALBUM_21' => $ualbum['21'],
				'V_PALBUM_21' => $valbum['21'],
				'G_PALBUM_22' => ($galbum['22']) ? '1' : '',
				'S_PALBUM_22' => $talbum['22'],
				'C_PALBUM_22' => $calbum['22'],
				'T_PALBUM_22' => $palbum['22'],
				'I_PALBUM_22' => $qalbum['22'],
				'A_PALBUM_22' => $ralbum['22'],
				'U_PALBUM_22' => $ualbum['22'],
				'V_PALBUM_22' => $valbum['22'],
				'G_PALBUM_23' => ($galbum['23']) ? '1' : '',
				'S_PALBUM_23' => $talbum['23'],
				'C_PALBUM_23' => $calbum['23'],
				'T_PALBUM_23' => $palbum['23'],
				'I_PALBUM_23' => $qalbum['23'],
				'A_PALBUM_23' => $ralbum['23'],
				'U_PALBUM_23' => $ualbum['23'],
				'V_PALBUM_23' => $valbum['23'],
				'G_PALBUM_24' => ($galbum['24']) ? '1' : '',
				'S_PALBUM_24' => $talbum['24'],
				'C_PALBUM_24' => $calbum['24'],
				'T_PALBUM_24' => $palbum['24'],
				'I_PALBUM_24' => $qalbum['24'],
				'A_PALBUM_24' => $ralbum['24'],
				'U_PALBUM_24' => $ualbum['24'],
				'V_PALBUM_24' => $valbum['24'],
				'G_PALBUM_25' => ($galbum['25']) ? '1' : '',
				'S_PALBUM_25' => $talbum['25'],
				'C_PALBUM_25' => $calbum['25'],
				'T_PALBUM_25' => $palbum['25'],
				'I_PALBUM_25' => $qalbum['25'],
				'A_PALBUM_25' => $ralbum['25'],
				'U_PALBUM_25' => $ualbum['25'],
				'V_PALBUM_25' => $valbum['25'],
				'G_PALBUM_26' => ($galbum['26']) ? '1' : '',
				'S_PALBUM_26' => $talbum['26'],
				'C_PALBUM_26' => $calbum['26'],
				'T_PALBUM_26' => $palbum['26'],
				'I_PALBUM_26' => $qalbum['26'],
				'A_PALBUM_26' => $ralbum['26'],
				'U_PALBUM_26' => $ualbum['26'],
				'V_PALBUM_26' => $valbum['26'],
				'G_PALBUM_27' => ($galbum['27']) ? '1' : '',
				'S_PALBUM_27' => $talbum['27'],
				'C_PALBUM_27' => $calbum['27'],
				'T_PALBUM_27' => $palbum['27'],
				'I_PALBUM_27' => $qalbum['27'],
				'A_PALBUM_27' => $ralbum['27'],
				'U_PALBUM_27' => $ualbum['27'],
				'V_PALBUM_27' => $valbum['27'],
				'G_PALBUM_28' => ($galbum['28']) ? '1' : '',
				'S_PALBUM_28' => $talbum['28'],
				'C_PALBUM_28' => $calbum['28'],
				'T_PALBUM_28' => $palbum['28'],
				'I_PALBUM_28' => $qalbum['28'],
				'A_PALBUM_28' => $ralbum['28'],
				'U_PALBUM_28' => $ualbum['28'],
				'V_PALBUM_28' => $valbum['28'],
				'G_PALBUM_29' => ($galbum['29']) ? '1' : '',
				'S_PALBUM_29' => $talbum['29'],
				'C_PALBUM_29' => $calbum['29'],
				'T_PALBUM_29' => $palbum['29'],
				'I_PALBUM_29' => $qalbum['29'],
				'A_PALBUM_29' => $ralbum['29'],
				'U_PALBUM_29' => $ualbum['29'],
				'V_PALBUM_29' => $valbum['29'],
				'G_PALBUM_30' => ($galbum['30']) ? '1' : '',
				'S_PALBUM_30' => $talbum['30'],
				'C_PALBUM_30' => $calbum['30'],
				'T_PALBUM_30' => $palbum['30'],
				'I_PALBUM_30' => $qalbum['30'],
				'A_PALBUM_30' => $ralbum['30'],
				'U_PALBUM_30' => $ualbum['30'],
				'V_PALBUM_30' => $valbum['30'],
				'T_PALBUM_31' => $palbum['30'],
				'G_PALBUM_31' => ($galbum['31']) ? '1' : '',
				'S_PALBUM_31' => $talbum['31'],
				'C_PALBUM_31' => $calbum['31'],
				'T_PALBUM_31' => $palbum['31'],
				'I_PALBUM_31' => $qalbum['31'],
				'A_PALBUM_31' => $ralbum['31'],
				'U_PALBUM_31' => $ualbum['31'],
				'V_PALBUM_31' => $valbum['31'],
				'L_FIELDS' => (!$row['user_fld_0']) ? '' : ($row['usrfldttl']) ? $row['usrfldttl'] : _PNDETAILS,
				'G_FIELDS' => ($row['user_fld_0']) ? '1' : '',
				'S_USER_FLD_0' => (!$row['user_fld_0']) ? '' : ($row['usrfld0']) ? $row['usrfld0'] : _PNUSRFLD0,
				'T_USER_FLD_0' => $row['user_fld_0'],
				'G_FIELDS_1' => ($row['user_fld_1']) ? '1' : '',
				'S_USER_FLD_1' => (!$row['user_fld_1']) ? '' : ($row['usrfld1']) ? $row['usrfld1'] : _PNUSRFLD1,
				'T_USER_FLD_1' => $row['user_fld_1'],
				'G_FIELDS_2' => ($row['user_fld_2']) ? '1' : '',
				'S_USER_FLD_2' => (!$row['user_fld_2']) ? '' : ($row['usrfld2']) ? $row['usrfld2'] : _PNUSRFLD2,
				'T_USER_FLD_2' => $row['user_fld_2'],
				'G_FIELDS_3' => ($row['user_fld_3']) ? '1' : '',
				'S_USER_FLD_3' => (!$row['user_fld_3']) ? '' : ($row['usrfld3']) ? $row['usrfld3'] : _PNUSRFLD3,
				'T_USER_FLD_3' => $row['user_fld_3'],
				'G_FIELDS_4' => ($row['user_fld_4']) ? '1' : '',
				'S_USER_FLD_4' => (!$row['user_fld_4']) ? '' : ($row['usrfld4']) ? $row['usrfld4'] : _PNUSRFLD4,
				'T_USER_FLD_4' => $row['user_fld_4'],
				'G_FIELDS_5' => ($row['user_fld_5']) ? '1' : '',
				'S_USER_FLD_5' => (!$row['user_fld_5']) ? '' : ($row['usrfld5']) ? $row['usrfld5'] : _PNUSRFLD5,
				'T_USER_FLD_5' => $row['user_fld_5'],
				'G_FIELDS_6' => ($row['user_fld_6']) ? '1' : '',
				'S_USER_FLD_6' => (!$row['user_fld_6']) ? '' : ($row['usrfld6']) ? $row['usrfld6'] : _PNUSRFLD6,
				'T_USER_FLD_6' => $row['user_fld_6'],
				'G_FIELDS_7' => ($row['user_fld_7']) ? '1' : '',
				'S_USER_FLD_7' => (!$row['user_fld_7']) ? '' : ($row['usrfld7']) ? $row['usrfld7'] : _PNUSRFLD7,
				'T_USER_FLD_7' => $row['user_fld_7'],
				'G_FIELDS_8' => ($row['user_fld_8']) ? '1' : '',
				'S_USER_FLD_8' => (!$row['user_fld_8']) ? '' : ($row['usrfld8']) ? $row['usrfld8'] : _PNUSRFLD2,
				'T_USER_FLD_8' => $row['user_fld_8'],
				'G_FIELDS_9' => ($row['user_fld_9']) ? '1' : '',
				'S_USER_FLD_9' => (!$row['user_fld_9']) ? '' : ($row['usrfld9']) ? $row['usrfld9'] : _PNUSRFLD2,
				'T_USER_FLD_9' => $row['user_fld_9'],
				'G_MOREPICS' => ($pnsettings['per_gllry'] != 0 && $numpics == $pnsettings['per_gllry'] && $smore == 1 && $row['album_id'] != '0' && $row['slide_show'] > '1'),
				'T_MOREPICS' => _PNMOREPICS,
				'U_MOREPICS' => getlink("&amp;mode=gllry&amp;id=".$row['id']."&amp;npic=".$npic),
				'T_BACK' => _PNBACK,
				'U_BACK' => 'javascript:history.go(-1)'
			));

			if ($pnsettings['SEOtitle']) {
				$pagetitle .= $row['title'].' '._BC_DELIM.' <a href="'.getlink($module_name.'&amp;cid='.$row['catid']).'">'.$row['ctitle'].'</a>';
			} else {
				$pagetitle .= ' '._BC_DELIM.' <a href="'.getlink($module_name.'&amp;cid='.$row['catid']).'">'.$row['ctitle'].'</a> '._BC_DELIM.' '.$row['title'];
			}
			require_once('header.php');
			$tpl = ($row['template'] != '') ? $row['template'] : $pnsettings['template'];
			$cpgtpl->set_filenames(array('body' => 'pronews/article/'.$tpl));
			$cpgtpl->display('body');
			}
		} else { url_redirect(getlink());}
	}

	function sendart($id) {
		global $pagetitle;
		$pagetitle .= (!$pnsettings['SEOtitle'] ? ' '._BC_DELIM.' ' : '')._PNFRIEND;
		$id = isset($_GET['id']) ? intval($_GET['id']) : '';

		if (isset($_POST['sendstory'])) {
			ProNews::sendstory();
		} else {
			ProNews::friendsend($id);
		}
	}

	function friendsend($id) {
		global $userinfo, $prefix, $db, $CPG_SESS;
		if (empty($id)) { exit; }
		$CPG_SESS['send_story'] = true;
		require_once('header.php');
		$sql = 'SELECT title FROM '.$prefix.'_pronews_articles WHERE id='.$id;
		$result = $db->sql_query($sql);
		$list = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		$yn = $ye = '';
		if (is_user()) {
			$yn = $userinfo['username'];
			$ye = $userinfo['user_email'];
			OpenTable();
			echo open_form(getlink('&amp;mode=send&amp;id='.$id), false, _PNFRIEND).'
			'._PNSENDSTORY.' <strong>'.$list['title'].'</strong> '._PNTOAFRIEND.'<br /><br />
			<label class="ulog" for="yname">'._PNYOURNAME.'</label>';
			if (is_admin()) {
				echo '<input type="text" name="yname" id="yname" value="'.$yn.'" size="25" maxlength="30" /><br />';
			} else {
				echo $yn.'<br />';
			}
			echo '<label class="ulog" for="ymail">'._PNYOUREMAIL.'</label>';
			if (is_admin()) {
				echo '<input type="text" name="ymail" id="ymail" value="'.$ye.'" size="25" maxlength="255" /><br /><br />';
			} else {
				echo $ye.'<br /><br />';
			}
			echo '<label class="ulog" for="fname">'._PNFRIENDNAME.'</label>
			<input type="text" name="fname" id="fname" size="25" maxlength="30" /><br />
			<label class="ulog" for="fmail">'._PNFRIENDEMAIL.'</label>
			<input type="text" name="fmail" id="fmail" size="25" maxlength="255" /><br /><br />
			<input type="hidden" name="id" value="'.$id.'" />
			<input type="submit" name="sendstory" value="'._PNSEND.'" />'.
			close_form();
			CloseTable();
		}
	}

	function sendstory() {
		global $sitename, $prefix, $db, $CPG_SESS, $pagetitle, $userinfo, $pnsettings;

		if (!isset($CPG_SESS['send_story']) && !$CPG_SESS['send_story']) { cpg_error(_SPAMGUARDPROTECTED); }

		$id = intval($_POST['id']);
		$yname = Fix_Quotes($_POST['yname'], true);
		$ymail = Fix_Quotes($_POST['ymail'], true);
		$fname = Fix_Quotes($_POST['fname'], true);
		$fmail = Fix_Quotes($_POST['fmail'], true);

		$ynm = $yem = '';
		if (is_user()) {
			$ynm = $userinfo['username'];
			$yem = $userinfo['user_email'];

			if (empty($id)) { cpg_error(sprintf(_ERROR_NOT_SET, _ID)); }

			if (is_admin()) {
				if (empty($yname)) { cpg_error(sprintf(_ERROR_NOT_SET, _PNYOURNAME)); } else { $ynm = $yname;}
				if (empty($ymail)) { cpg_error(sprintf(_ERROR_NOT_SET, _PNYOUREMAIL)); } else { $yem = $ymail;}
			}

			if (empty($fname)) { cpg_error(sprintf(_ERROR_NOT_SET, _PNFRIENDNAME)); }
			if (empty($fmail)) { cpg_error(sprintf(_ERROR_NOT_SET, _PNFRIENDEMAIL)); }

			$sql = 'SELECT title, intro FROM '.$prefix.'_pronews_articles WHERE id='.$id;
			$result = $db->sql_query($sql);
			$list = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			$subject = _PNINTERESTING." $sitename";
			$message = _HELLO." $fname:\n\n"._PNYOURFRIEND." $yname "._PNCONSIDERED."\n\n\n".$list['title']." "._URL.": ".getlink("&amp;aid=$id", true, true)."\n\n\n\n"._PNCANREAD." $sitename\n".getlink('', true, true)."\n\n";
			$message .= _PNPOSTEDBY." IP: ".decode_ip($userinfo['user_ip']);
			if (!send_mail($mailer_message,$message,0,$subject,$fmail,$fname,$ymail,$yname)) {
				cpg_error($mailer_message);
			} else {
				$CPG_SESS['send_story'] = false;
				unset($CPG_SESS['send_story']);
				cpg_error($mailer_message, $pagetitle, getlink('', true, true));
			}
		}
		url_redirect(getlink("&amp;aid=$id"));
	}

	function printformat($id) {
		global $BASEHREF, $db, $prefix, $cpgtpl, $gblsettings, $pnsettings, $pagetitle, $CPG_SESS, $module_name, $userinfo, $sitename;
		if (isset($_GET['id'])) {
			$aid = intval($_GET['id']);
			$album_order = array(0=>'',1=>'title',2=>'title',3=>'filename',4=>'filename',5=>'ctime',6=>'ctime',7=>'pic_rating',8=>'pic_rating');
			$sql = 'SELECT a.*, c.icon, c.title ctitle, s.admin sadmin, template, usrfld0, usrfld1, usrfld2, usrfld3, usrfld4, usrfld5, usrfld6, usrfld7, usrfld8, usrfld9, usrfldttl';
			$sql .= ' FROM '.$prefix.'_pronews_articles as a';
			$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
			$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
			$sql .= ' WHERE a.id='.$aid;
			$row = $db->sql_fetchrow($db->sql_query($sql));
			if ($pnsettings['show_reads'] && $pnsettings['read_cnt']) {
				$db->sql_query('UPDATE '.$prefix.'_pronews_articles SET counter=counter+1 WHERE id='.$aid);
				$show_reads = '1';
			} else {
				$show_reads = '0';
			}
			if (isset($row) && $row != '') {
				require_once('includes/nbbcode.php');
				if(($row['image'] != '') && ($row['image'] != '0')) {
					$imagesize = getimagesize($pnsettings['imgpath'].'/'.$row['image']);  // fitted window - layingback 061119
					$imagesizeX = $imagesize[0] + 16; $imagesizeY = $imagesize[1] + 16;
					if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
						$thumbimage = $pnsettings['imgpath'].'/thumb_'.$row['image'];
					} else {
						$thumbimage = $pnsettings['imgpath'].'/'.$row['image'];  // Check if thumb exists before linking - layingback 061122
					}
					$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" />';
				} else {$display_image = '';}
				if(($row['image2'] != '') && ($row['image2'] != '0')) {
					$imagesize = getimagesize($pnsettings['imgpath'].'/'.$row['image2']);  // fitted window - layingback 061119
					$imagesizeX = $imagesize[0] + 16; $imagesizeY = $imagesize[1] + 16;
					if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
						$thumbimage = $pnsettings['imgpath'].'/thumb_'.$row['image2'];
					} else {
						$thumbimage = $pnsettings['imgpath'].'/'.$row['image2'];  // Check if thumb exists before linking - layingback 061122
					}
					$display2_image = '<img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" />';
				} else {$display2_image = '';}
//				$numpics = '0';
//				$lpic = '';
				if (($row['album_id'] != '') && ($row['album_cnt'] > '0')) {
					$sql = 'SELECT pid pid, filepath, filename, pwidth, pheight, title FROM '.$prefix.'_cpg_pictures';
//					$sql = 'SELECT pid pid, filepath, filename, pwidth, pheight, title, remote_url FROM '.$prefix.'_cpg_pictures';  // lb - use after installing my coppermine remote CPG hack
					$sql .= ' WHERE aid='.$row['album_id'];
					$asc_desc = ($row['album_seq'] & 1) ? 'ASC' : 'DESC';
					if ($row['album_seq'] != '0') {$sql .= ' ORDER BY '.$album_order[$row['album_seq']].' '.$asc_desc;}
					$sql .= ' LIMIT '.$row['album_cnt'];
					$list = $db->sql_fetchrowset($db->sql_query($sql));
					if (($list) && ($list != "")) {
						foreach ($list as $key => $pic) {
							$fullsizepath = ($pic['remote_url'] != '' && preg_match("/(?:https?\:\/\/)?([^\.]+\.?[^\.\/]+\.[^\.\/]+[^\.]+)/", $pic['remote_url'], $matches)) ? 'http://'.$matches[1].'/' : $pic['filepath'];		// lb - cpg remote_url hack support
							$imagesizeX = $pic['pwidth'] + 16; $imagesizeY = $pic['pheight'] + 16;
							if ($pic['pwidth'] > $maxsizeX) { $maxsizeX = $pic['pwidth'];}
							if ($pic['pheight'] > $maxsizeY) { $maxsizeY = $pic['pheight'];}
							$thumb = str_replace("%2F", "/", rawurlencode($pic['filepath'].'thumb_'.$pic['filename']));
							$palbum[$key] = '<img class="pn_image" src="'.$thumb.'" alt="'.$pic['title'].'" />';
							$qalbum[$key] = $thumb.'" alt="'.$row['caption'];
							$ralbum[$key] = str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'" target="'.$target.'"
 onclick="PN_openBrWindow(\''.$BASEHREF.str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;';
							$talbum[$key] = $pic['title'] != ' ' ? trim($pic['title']) : '&nbsp;';				// trim cos cpg adds trailing space!
							$ualbum[$key] = '<img class="pn_image" src="'.str_replace("%2F", "/", rawurlencode($pic['filepath'].(file_exists($pic['filepath'].'normal_'.$pic['filename']) ? 'normal_' : '').$pic['filename'])).'" alt="" />';
							$galbum[$key] = '1';
							$lpic = $pic['pid'];		//track pid of last pic shown
							$numpics++;
						}
					}
				}
				$j = $numpics;
				while ($j <= '32') {
					$galbum[$j] = '';
					$palbum[$j] = '';
					$qalbum[$j] = '" alt="';
					$ralbum[$j] = '';
					$talbum[$j] = '';
					$ualbum[$j] = '';
					$j++;
				}
				$cpgtpl->assign_block_vars('newsarticle', array(
					'S_SECBRK' => ProNews::getsctrnslt('_PN_SECTITLE_', $row['stitle'], $row['sid']),
					'U_SECDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_SECDESC_', $row['sdescription'], $row['sid']), 1, true)),
					'S_CATBRK' => ProNews::getsctrnslt('_PN_CATTITLE_', $row['ctitle'], $row['catid']),
					'U_CATDESC' => make_clickable(decode_bb_all(ProNews::getsctrnslt('_PN_CATDESC_', $row['cdescription'], $row['catid']), 1, true)),
					'S_INTRO' => decode_bb_all($row['intro'], 1, true),
					'S_CONTENT' => decode_bb_all($row['content'], 1, true),
					'S_ICON' => ($row['icon'] != '') ? $row['icon'] : 'clearpixel.gif',
					'T_ICON' => $row['ctitle'],
					'S_TITLE' => $row['title'],
					'L_POSTBY' => _PNPOSTBY,
					'S_POSTBY' => $row['postby'],
					'T_POSTBY' => getlink("Your_Account&amp;profile=".$row['postby']),
					'L_POSTON' => _PNPOSTON,
					'S_POSTTIME' => ProNews::create_date(false, $row['posttime']),
					'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
					'S_CATEGORY' => $row['catid'],
					'S_IMAGE' => ($row['image'] == '' && $pnsettings['show_noimage'] == '0') ? '' : $display_image,
					'T_CAP' => ($row['caption'] == '') ? '&nbsp;' : $row['caption'],
					'S_IMAGE2' => $display2_image,
					'T_CAP2' => ($row['caption2'] == '') ? '&nbsp;' : $row['caption2'],
					'G_PALBUM_0' => ($galbum['0']) ? '1' : '',
					'S_PALBUM_0' => $talbum['0'],
					'T_PALBUM_0' => $palbum['0'],
					'I_PALBUM_0' => $qalbum['0'],
					'A_PALBUM_0' => $ralbum['0'],
					'U_PALBUM_0' => $ualbum['0'],
					'T_PALBUM_1' => $palbum['1'],
					'G_PALBUM_1' => ($galbum['1']) ? '1' : '',
					'S_PALBUM_1' => $talbum['1'],
					'T_PALBUM_1' => $palbum['1'],
					'I_PALBUM_1' => $qalbum['1'],
					'A_PALBUM_1' => $ralbum['1'],
					'U_PALBUM_1' => $ualbum['1'],
					'G_PALBUM_2' => ($galbum['2']) ? '1' : '',
					'S_PALBUM_2' => $talbum['2'],
					'T_PALBUM_2' => $palbum['2'],
					'I_PALBUM_2' => $qalbum['2'],
					'A_PALBUM_2' => $ralbum['2'],
					'U_PALBUM_2' => $ualbum['2'],
					'G_PALBUM_3' => ($galbum['3']) ? '1' : '',
					'S_PALBUM_3' => $talbum['3'],
					'T_PALBUM_3' => $palbum['3'],
					'I_PALBUM_3' => $qalbum['3'],
					'A_PALBUM_3' => $ralbum['3'],
					'U_PALBUM_3' => $ualbum['3'],
					'G_PALBUM_4' => ($galbum['4']) ? '1' : '',
					'S_PALBUM_4' => $talbum['4'],
					'T_PALBUM_4' => $palbum['4'],
					'I_PALBUM_4' => $qalbum['4'],
					'A_PALBUM_4' => $ralbum['4'],
					'U_PALBUM_4' => $ualbum['4'],
					'G_PALBUM_5' => ($galbum['5']) ? '1' : '',
					'S_PALBUM_5' => $talbum['5'],
					'T_PALBUM_5' => $palbum['5'],
					'I_PALBUM_5' => $qalbum['5'],
					'A_PALBUM_5' => $ralbum['5'],
					'U_PALBUM_5' => $ualbum['5'],
					'G_PALBUM_6' => ($galbum['6']) ? '1' : '',
					'S_PALBUM_6' => $talbum['6'],
					'T_PALBUM_6' => $palbum['6'],
					'I_PALBUM_6' => $qalbum['6'],
					'A_PALBUM_6' => $ralbum['6'],
					'U_PALBUM_6' => $ualbum['6'],
					'G_PALBUM_7' => ($galbum['7']) ? '1' : '',
					'S_PALBUM_7' => $talbum['7'],
					'T_PALBUM_7' => $palbum['7'],
					'I_PALBUM_7' => $qalbum['7'],
					'A_PALBUM_7' => $ralbum['7'],
					'U_PALBUM_7' => $ualbum['7'],
					'G_PALBUM_8' => ($galbum['8']) ? '1' : '',
					'S_PALBUM_8' => $talbum['8'],
					'T_PALBUM_8' => $palbum['8'],
					'I_PALBUM_8' => $qalbum['8'],
					'A_PALBUM_8' => $ralbum['8'],
					'U_PALBUM_8' => $ualbum['8'],
					'G_PALBUM_9' => ($galbum['9']) ? '1' : '',
					'S_PALBUM_9' => $talbum['9'],
					'T_PALBUM_9' => $palbum['9'],
					'I_PALBUM_9' => $qalbum['9'],
					'A_PALBUM_9' => $ralbum['9'],
					'U_PALBUM_9' => $ualbum['9'],
					'G_PALBUM_10' => ($galbum['10']) ? '1' : '',
					'S_PALBUM_10' => $talbum['10'],
					'T_PALBUM_10' => $palbum['10'],
					'I_PALBUM_10' => $qalbum['10'],
					'A_PALBUM_10' => $ralbum['10'],
					'U_PALBUM_10' => $ualbum['10'],
					'T_PALBUM_10' => $palbum['10'],
					'G_PALBUM_11' => ($galbum['11']) ? '1' : '',
					'S_PALBUM_11' => $talbum['11'],
					'T_PALBUM_11' => $palbum['11'],
					'I_PALBUM_11' => $qalbum['11'],
					'A_PALBUM_11' => $ralbum['11'],
					'U_PALBUM_11' => $ualbum['11'],
					'G_PALBUM_12' => ($galbum['12']) ? '1' : '',
					'S_PALBUM_12' => $talbum['12'],
					'T_PALBUM_12' => $palbum['12'],
					'I_PALBUM_12' => $qalbum['12'],
					'A_PALBUM_12' => $ralbum['12'],
					'U_PALBUM_12' => $ualbum['12'],
					'G_PALBUM_13' => ($galbum['13']) ? '1' : '',
					'S_PALBUM_13' => $talbum['13'],
					'T_PALBUM_13' => $palbum['13'],
					'I_PALBUM_13' => $qalbum['13'],
					'A_PALBUM_13' => $ralbum['13'],
					'U_PALBUM_13' => $ualbum['13'],
					'G_PALBUM_14' => ($galbum['14']) ? '1' : '',
					'S_PALBUM_14' => $talbum['14'],
					'T_PALBUM_14' => $palbum['14'],
					'I_PALBUM_14' => $qalbum['14'],
					'A_PALBUM_14' => $ralbum['14'],
					'U_PALBUM_14' => $ualbum['14'],
					'G_PALBUM_15' => ($galbum['15']) ? '1' : '',
					'S_PALBUM_15' => $talbum['15'],
					'T_PALBUM_15' => $palbum['15'],
					'I_PALBUM_15' => $qalbum['15'],
					'A_PALBUM_15' => $ralbum['15'],
					'U_PALBUM_15' => $ualbum['15'],
					'G_PALBUM_16' => ($galbum['16']) ? '1' : '',
					'S_PALBUM_16' => $talbum['16'],
					'T_PALBUM_16' => $palbum['16'],
					'I_PALBUM_16' => $qalbum['16'],
					'A_PALBUM_16' => $ralbum['16'],
					'U_PALBUM_16' => $ualbum['16'],
					'G_PALBUM_17' => ($galbum['17']) ? '1' : '',
					'S_PALBUM_17' => $talbum['17'],
					'T_PALBUM_17' => $palbum['17'],
					'I_PALBUM_17' => $qalbum['17'],
					'A_PALBUM_17' => $ralbum['17'],
					'U_PALBUM_17' => $ualbum['17'],
					'G_PALBUM_18' => ($galbum['18']) ? '1' : '',
					'S_PALBUM_18' => $talbum['18'],
					'T_PALBUM_18' => $palbum['18'],
					'I_PALBUM_18' => $qalbum['18'],
					'A_PALBUM_18' => $ralbum['18'],
					'U_PALBUM_18' => $ualbum['18'],
					'G_PALBUM_19' => ($galbum['19']) ? '1' : '',
					'S_PALBUM_19' => $talbum['19'],
					'T_PALBUM_19' => $palbum['19'],
					'I_PALBUM_19' => $qalbum['19'],
					'A_PALBUM_19' => $ralbum['19'],
					'U_PALBUM_19' => $ualbum['19'],
					'G_PALBUM_20' => ($galbum['20']) ? '1' : '',
					'S_PALBUM_20' => $talbum['20'],
					'T_PALBUM_20' => $palbum['20'],
					'I_PALBUM_20' => $qalbum['20'],
					'A_PALBUM_20' => $ralbum['20'],
					'U_PALBUM_20' => $ualbum['20'],
					'T_PALBUM_21' => $palbum['21'],
					'G_PALBUM_21' => ($galbum['21']) ? '1' : '',
					'S_PALBUM_21' => $talbum['21'],
					'T_PALBUM_21' => $palbum['21'],
					'I_PALBUM_21' => $qalbum['21'],
					'A_PALBUM_21' => $ralbum['21'],
					'U_PALBUM_21' => $ualbum['21'],
					'G_PALBUM_22' => ($galbum['22']) ? '1' : '',
					'S_PALBUM_22' => $talbum['22'],
					'T_PALBUM_22' => $palbum['22'],
					'I_PALBUM_22' => $qalbum['22'],
					'A_PALBUM_22' => $ralbum['22'],
					'U_PALBUM_22' => $ualbum['22'],
					'G_PALBUM_23' => ($galbum['23']) ? '1' : '',
					'S_PALBUM_23' => $talbum['23'],
					'T_PALBUM_23' => $palbum['23'],
					'I_PALBUM_23' => $qalbum['23'],
					'A_PALBUM_23' => $ralbum['23'],
					'U_PALBUM_23' => $ualbum['23'],
					'G_PALBUM_24' => ($galbum['24']) ? '1' : '',
					'S_PALBUM_24' => $talbum['24'],
					'T_PALBUM_24' => $palbum['24'],
					'I_PALBUM_24' => $qalbum['24'],
					'A_PALBUM_24' => $ralbum['24'],
					'U_PALBUM_24' => $ualbum['24'],
					'G_PALBUM_25' => ($galbum['25']) ? '1' : '',
					'S_PALBUM_25' => $talbum['25'],
					'T_PALBUM_25' => $palbum['25'],
					'I_PALBUM_25' => $qalbum['25'],
					'A_PALBUM_25' => $ralbum['25'],
					'U_PALBUM_25' => $ualbum['25'],
					'G_PALBUM_26' => ($galbum['26']) ? '1' : '',
					'S_PALBUM_26' => $talbum['26'],
					'T_PALBUM_26' => $palbum['26'],
					'I_PALBUM_26' => $qalbum['26'],
					'A_PALBUM_26' => $ralbum['26'],
					'U_PALBUM_26' => $ualbum['26'],
					'G_PALBUM_27' => ($galbum['27']) ? '1' : '',
					'S_PALBUM_27' => $talbum['27'],
					'T_PALBUM_27' => $palbum['27'],
					'I_PALBUM_27' => $qalbum['27'],
					'A_PALBUM_27' => $ralbum['27'],
					'U_PALBUM_27' => $ualbum['27'],
					'G_PALBUM_28' => ($galbum['28']) ? '1' : '',
					'S_PALBUM_28' => $talbum['28'],
					'T_PALBUM_28' => $palbum['28'],
					'I_PALBUM_28' => $qalbum['28'],
					'A_PALBUM_28' => $ralbum['28'],
					'U_PALBUM_28' => $ualbum['28'],
					'G_PALBUM_29' => ($galbum['29']) ? '1' : '',
					'S_PALBUM_29' => $talbum['29'],
					'T_PALBUM_29' => $palbum['29'],
					'I_PALBUM_29' => $qalbum['29'],
					'A_PALBUM_29' => $ralbum['29'],
					'U_PALBUM_29' => $ualbum['29'],
					'G_PALBUM_30' => ($galbum['30']) ? '1' : '',
					'S_PALBUM_30' => $talbum['30'],
					'T_PALBUM_30' => $palbum['30'],
					'I_PALBUM_30' => $qalbum['30'],
					'A_PALBUM_30' => $ralbum['30'],
					'U_PALBUM_30' => $ualbum['30'],
					'T_PALBUM_31' => $palbum['30'],
					'G_PALBUM_31' => ($galbum['31']) ? '1' : '',
					'S_PALBUM_31' => $talbum['31'],
					'T_PALBUM_31' => $palbum['31'],
					'I_PALBUM_31' => $qalbum['31'],
					'A_PALBUM_31' => $ralbum['31'],
					'U_PALBUM_31' => $ualbum['31'],
					'G_PALBUM_32' => ($galbum['32']) ? '1' : '',
					'S_PALBUM_32' => $talbum['32'],
					'T_PALBUM_32' => $palbum['32'],
					'I_PALBUM_32' => $qalbum['32'],
					'A_PALBUM_32' => $ralbum['32'],
					'U_PALBUM_32' => $ualbum['32'],
					'G_SLIDESHOW' => ($pnsettings['show_album'] != 0 && $row['album_cnt'] != 0 && $row['album_id'] && $row['album_id'] != '0') ? '1' : '0',
					'S_SLIDESHOW' => ($row['slide_show'] == '1' || $row['slide_show'] == '3') ? '<img src="themes/'.$CPG_SESS['theme'].'/images/'.strtolower($module_name).'/slideshow.png" style="border: 0" alt="'._PNSLDSHOW.'" />' : '',
					'T_SLIDESHOW' => ($row['slide_show'] == '1' || $row['slide_show'] == '3') ? _PNSLDSHOW : '',
					'L_FIELDS' => (!$row['user_fld_0']) ? '' : ($row['usrfldttl']) ? $row['usrfldttl'] : _PNDETAILS,
					'G_FIELDS' => ($row['user_fld_0']) ? '1' : '',
					'S_USER_FLD_0' => (!$row['user_fld_0']) ? '' : ($row['usrfld0']) ? $row['usrfld0'] : _PNUSRFLD0,
					'T_USER_FLD_0' => $row['user_fld_0'],
					'G_FIELDS_1' => ($row['user_fld_1']) ? '1' : '',
					'S_USER_FLD_1' => (!$row['user_fld_1']) ? '' : ($row['usrfld1']) ? $row['usrfld1'] : _PNUSRFLD1,
					'T_USER_FLD_1' => $row['user_fld_1'],
					'G_FIELDS_2' => ($row['user_fld_2']) ? '1' : '',
					'S_USER_FLD_2' => (!$row['user_fld_2']) ? '' : ($row['usrfld2']) ? $row['usrfld2'] : _PNUSRFLD2,
					'T_USER_FLD_2' => $row['user_fld_2'],
					'G_FIELDS_3' => ($row['user_fld_3']) ? '1' : '',
					'S_USER_FLD_3' => (!$row['user_fld_3']) ? '' : ($row['usrfld3']) ? $row['usrfld3'] : _PNUSRFLD3,
					'T_USER_FLD_3' => $row['user_fld_3'],
					'G_FIELDS_4' => ($row['user_fld_4']) ? '1' : '',
					'S_USER_FLD_4' => (!$row['user_fld_4']) ? '' : ($row['usrfld4']) ? $row['usrfld4'] : _PNUSRFLD4,
					'T_USER_FLD_4' => $row['user_fld_4'],
					'G_FIELDS_5' => ($row['user_fld_5']) ? '1' : '',
					'S_USER_FLD_5' => (!$row['user_fld_5']) ? '' : ($row['usrfld5']) ? $row['usrfld5'] : _PNUSRFLD5,
					'T_USER_FLD_5' => $row['user_fld_5'],
					'G_FIELDS_6' => ($row['user_fld_6']) ? '1' : '',
					'S_USER_FLD_6' => (!$row['user_fld_6']) ? '' : ($row['usrfld6']) ? $row['usrfld6'] : _PNUSRFLD6,
					'T_USER_FLD_6' => $row['user_fld_6'],
					'G_FIELDS_7' => ($row['user_fld_7']) ? '1' : '',
					'S_USER_FLD_7' => (!$row['user_fld_7']) ? '' : ($row['usrfld7']) ? $row['usrfld7'] : _PNUSRFLD7,
					'T_USER_FLD_7' => $row['user_fld_7'],
					'G_FIELDS_8' => ($row['user_fld_8']) ? '1' : '',
					'S_USER_FLD_8' => (!$row['user_fld_8']) ? '' : ($row['usrfld8']) ? $row['usrfld8'] : _PNUSRFLD2,
					'T_USER_FLD_8' => $row['user_fld_8'],
					'G_FIELDS_9' => ($row['user_fld_9']) ? '1' : '',
					'S_USER_FLD_9' => (!$row['user_fld_9']) ? '' : ($row['usrfld9']) ? $row['usrfld9'] : _PNUSRFLD2,
					'T_USER_FLD_9' => $row['user_fld_9'],
//					'G_SHOW_READS' => $show_reads,
					'S_READS' => ($row['counter'] != '1') ? _PNREADS : _PNREAD,
					'T_READS' => $row['counter'],
					'G_SCORE' => ($pnsettings['ratings'] && $row['ratings'] != '0') ? '1' : '',
					'S_SCORE' => _PNRATING,
					'T_SCORE' => ($row['ratings'] != '0') ? $row['score']/$row['ratings'] : '',
					'G_MOREPICS' => ($pnsettings['per_gllry'] != 0 && numpics == $row['album_cnt'] && $row['album_id'] != '0' && $row['slide_show'] > '1') ? '1' : '0',
					'T_MOREPICS' => _PNMOREPICS,
					'U_MOREPICS' => getlink("&amp;mode=gllry&amp;id=".$row['id']."&amp;npic=".$row['album_cnt'])
				));
				$tpl = ($row['template'] != '') ? $row['template'] : $pnsettings['template'];
				ob_start();
				$cpgtpl->set_filenames(array('prntbody' => 'pronews/article/'.$tpl));
				$cpgtpl->display('prntbody');
				$content = ob_get_clean();

				if (!defined('_CHARSET')) { define('_CHARSET', 'UTF-8'); }
				if (!defined('_BROWSER_LANGCODE')) { define('_BROWSER_LANGCODE', _LANGCODE); }
				echo '
				<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
				"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html dir="'._TEXT_DIR.'" lang="'._BROWSER_LANGCODE.'">
				<head>
					<base href="'.$BASEHREF.'" />
					<meta http-equiv="Content-Type" content="text/html; charset='._CHARSET.'" />
					<title>'.$sitename.' '._BC_DELIM.' '.$row['title'].'</title>
					<link rel="stylesheet" href="themes/'.$CPG_SESS['theme'].'/style/style.css" type="text/css" media="screen" />
					<link rel="stylesheet" href="themes/'.$CPG_SESS['theme'].'/style/pro_news_print.css" type="text/css" media="screen, print" />
				</head>
				<body>
					<table align="center" class="pn_holder">
						<tr><td align="center">'.$content.'
						</td></tr>
					</table>
					<p align="center">
						'._PNCONTENRECEIVED.' '.$sitename.', <br /><a href='.getlink("&amp;aid=$id", true, true).'>'.getlink("&amp;aid=$id", true, true).'</a>
					</p>
				</body>
				</html>';
			} else {
				url_redirect(getlink());
			}
		}
	}

	function scheduler() {
		global $prefix, $db;
		$result = $db->sql_query('SELECT * FROM '.$prefix.'_pronews_schedule WHERE dttime<='.time());
		while ($row2 = $db->sql_fetchrow($result)) {
			$id = $row2['id'];
			$newstate = intval($row2['newstate']);
//			$dttime = ($row2['dttime']);
			$db->sql_query('UPDATE '.$prefix.'_pronews_articles SET active="'.$newstate.'" WHERE id='.$id, true);
		}
		if ($db->sql_numrows($result)) {
			$db->sql_query('DELETE FROM '.$prefix.'_pronews_schedule WHERE dttime<='.time());
		}
		$db->sql_freeresult($result);
	}

	function dyn_meta_tags($seod,$stitle,$ctitle,$title,$text) {
		global $METATAGS;
		//Process Meta Tags
		//from hack by spacebar of GotPoetry.com for /news/article.php
		//09/15/2007  http://dragonflycms.org/Forums/viewtopic/t=20906/

		//Generate dynamic description
		$order   = array("\r\n", "\n", "\r");
		$replace = ' ';
		// Processes \r\n's first so they aren't converted twice.
		$new_desc = str_replace($order, $replace, strip_tags($text));
		$order   = array('"');
		$replace = ' ';
		// Processes " so tags work.
		$new_keywords = str_replace($order, $replace, $new_desc);

		// If SEOdescription present use, else use Intro text - 1st 255 chars
		if ($seod == '') {
			$METATAGS['description'] = substr($new_keywords,0,255);
		} else {
			$METATAGS['description'] = $seod;		//  = not .= in order to drop $siteslogan prefix
		}

		//Generate dynamic keywords
		$new_keywords = " ".$new_keywords." ";
		$new_keywords = strtolower($title . " " . $new_keywords);
// echo 'k: '.$new_keywords;

		//Remove punctuation, etc						// moved before common word removal per Berty
		$order = array("  ","   ",",",".",":",":","!","(",")");
		$new_keywords = str_replace($order, " ",$new_keywords);
// echo '<br />k: '.$new_keywords;

		//Remove common words
		$order = array(" - ", " & "," / "," a "," about "," after "," against "," all "," almost "," also "," am "," an "," and "," another "," any "," are "," around "," as "," at "," b "," be "," because "," been "," before "," behind "," being "," both "," but "," by "," c "," came "," come "," comes "," could "," d "," did "," do "," does "," done "," e "," each "," either "," etc "," ever "," every "," example "," f "," few "," for "," for: "," from "," g "," go "," h "," had "," has "," have "," here "," how "," however "," i "," ie "," if "," ii "," iii "," in "," include "," included "," including "," into "," is "," it "," its "," iv "," ix "," j "," just "," k "," l "," m "," many "," may "," midst "," might "," my "," n "," nbsp "," neither "," never "," next "," no "," nor "," not "," now "," o "," of "," often "," on "," once "," or "," other "," others "," our "," over "," p "," put "," q "," r "," s "," same "," shall "," should "," show "," since "," so "," some "," something "," sometimes "," soon "," such "," t "," than "," that "," the "," their "," them "," then "," there "," these "," they "," this "," those "," through "," to "," too "," toward "," u "," under "," underneath "," until "," us "," use "," used "," uses "," using "," usually "," v "," very "," vi "," vii "," viii "," w "," was "," we "," went "," were "," what "," when "," where "," whether "," which "," while "," who "," why "," with "," within "," without "," would "," x "," xi "," xii "," xiii "," xiv "," xix "," xv "," xvi "," xvii "," xviii "," xx "," y "," you "," your "," z ");
		$replace = ' ';
		$new_keywords = str_replace($order, $replace,$new_keywords);
// echo '<br />k: '.$new_keywords;

		//remove short words
		$new_keywords = preg_replace("/([,.?!])/"," \\1",$new_keywords);
		$parts = explode(" ",$new_keywords);
		foreach ($parts as $p) {							// added Berty's drop < 3 - now 6-character - words
			if (strlen($p) > 5) {
				$unique[] = $p;
			}
		}
// echo '<br />k: '.$new_keywords;

		//remove duplicate words
		//rank the keywords!!!								// limit to fishingfan's most common words
		$rank = array_count_values(array_map('strtolower', $unique));
		arsort($rank);
// echo '<br /><pre>';print_r($rank);echo '</pre>';
		$un = array_keys($rank);
		//return up to 10 (formerly 30) keywords
		$n=0;
		$words = "";
		$max = sizeof($un);

		while ($n < $max && $n < 10) {
			$words = $words.",".array_shift($un);
			$n++;
		}
// echo '<br />k: '.$words;
// print_r("KEYS:\n".$words."\n");
		$METATAGS['keywords'] = trim($words, ',');
//		return array('description'=>$description_output, 'keywords'=>$keywords_output);
	}

	function promote($id) {
		global $db, $prefix, $cpgtpl, $gblsettings, $pnsettings, $CPG_SESS, $module_name, $userinfo;
		$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) :'');
		$fpro = isset($_POST['fpro']) ? Fix_Quotes($_POST['fpro'],1) : (isset($_GET['fpro']) ? Fix_Quotes($_GET['fpro']) : '');
		if ($id != '') {
			$tid = intval($id);
			if (is_admin() OR $userinfo['user_level'] == 3) {		//admin or mod
				if (!isset($_POST['prmtchck'])) {
//echo ' id='.$id.' tid='.$tid.' fpro='.$fpro;
//					if ($fpro) {$forumpro_prefix = end(explode("_", rtrim($fpro, "_")));}
//echo ' $fpro='.$fpro.' $forumpro_prefix='.rtrim($forumpro_prefix,"_").' explode='.end(explode("_", rtrim($fpro,"_")));

						$sql = 'SELECT a.id aid FROM '.$prefix.'_pronews_articles as a';
						$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
						$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
						$sql .= ' WHERE a.topic_id='.$tid.' LIMIT 1';
						$result = $db->sql_fetchrow($db->sql_query($sql));
						if (isset($result) && $result != '') {
							$pro_art = '<b>'._PNPRVPRMTE.' <a href="'.getlink('&amp;aid='.$result['aid']).'" target="_blank">here</a></b>';
						} else {$pro_art = '';}

					$categories = ProNews::seccat('cat', '', true, $fpro, false, false);
					cpg_delete_msg(getlink("$module_name&amp;mode=prmte&amp;id=$tid"),_PNSELARTCAT.' '.$categories.'<br /><i>'._PNCMTARTCAT.'</i><br /><br /><br />'.$pro_art.'<br /><br /><br />'._PNCNFPMTE,'<input type="hidden" name="prmtchck" value="'.$checked.'" /><input type="hidden" name="tid" value="'.$tid.'" /><input type="hidden" name="fpro" value="'.$fpro.'" />');
				} else {
					if ((isset($_POST['confirm']))) {
						$category = ((isset($_POST['cat'])) && ($_POST['cat'] != '')) ? intval($_POST['cat']) : '';
// echo '<br />PST[cat]='.$_POST['cat'].' category='.$category;
						if ($category == 0 ) {$category = 1;}
						$approved = 0;
						$sql = 'SELECT s.admin admin';
						$sql .= ' FROM '.$prefix.'_pronews_cats as c';
						$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
						$sql .= ' WHERE c.id='.$category;
						if (!is_admin()) {
							$member_a_group = "0";
							if (isset($userinfo['_mem_of_groups'])) {
								foreach ($userinfo['_mem_of_groups'] as $id => $name) {
									if (!empty($name)) {
										$member_a_group = "1";
									}
								}
							}
							if (!is_user()) {
								$sql .= ' AND (s.admin=0 OR s.admin=3)';
							} else if ($member_a_group) {
								$sql .= ' AND EXISTS (SELECT 1 FROM '.$prefix.'_bbuser_group as g WHERE g.user_id='.is_user().' AND ((s.admin<4 AND (s.admin=0 OR s.admin=1)) OR (s.admin>3 AND s.admin-3=g.group_id)))';
							} else {
								$sql .= ' AND (s.admin=0 OR s.admin=1)';
							}
						}
						$row = $db->sql_fetchrow($db->sql_query($sql));
						if (isset($row) && $row != '') {
							$approved = 1;
						}
						$sql = 'SELECT  topic_title as title,  post_text as content,  username as postby,  topic_time as posttime';
						if ($fpro) {
							$sql .= ' FROM '.$prefix.'_fpro_topics as t, '.$prefix.'_fpro_posts_text as p, '.$prefix.'_users as u';
						} else {
							$sql .= ' FROM '.$prefix.'_bbtopics as t, '.$prefix.'_bbposts_text as p, '.$prefix.'_users as u';
						}
						$sql .= ' WHERE t.topic_id='.$tid.' AND t.topic_first_post_id=p.post_id AND t.topic_poster=u.user_id';
//echo 'sql=';print_r($sql);
						$list = $db->sql_fetchrow($db->sql_query($sql));
//echo 'list=';print_r($list);
						if ($list) {
							$comment = ($pnsettings['comments'] == '1') ? '1' : '';
							$fposttime = formatDateTime($list['posttime'],_DATESTRING);
							$title = $list['title'].' [ '._PNFRMFRM.' ] ';
							$intro = '[size=9][i]'._PNARTORIG.' '.$list['postby'].' '._PNPOSTON.' '.$fposttime.' '._PNFRMPST.' - '._PNPRMTBY.' '.$userinfo['username'].'[/i][/size]';
							$sql = 'INSERT INTO '.$prefix.'_pronews_articles SET catid="'.$category.'", title="'.Fix_Quotes($title).'", intro="'.Fix_Quotes($intro).'", content="'.Fix_Quotes($list['content']).'", allow_comment="1", postby="'.$list['postby'].'", posttime="'.gmtime().'", active="1", approved="'.$approved.'", topic_id="'.$tid.'", alanguage=""';  // greenday2k
							$result = $db->sql_query($sql);
							$newid = mysql_insert_id();
						}
						if (is_admin()) {
//echo 'done';
							url_redirect(adminlink("&amp;mode=add&amp;do=edit&amp;id=$newid"));
						} else {
							url_redirect(getlink("&amp;mode=submit&amp;do=edit&amp;id=$newid"));
						}
					}  else {
						if ($fpro == '') {
							url_redirect(getlink("Forums&amp;file=viewtopic&amp;t=$tid"));
						} elseif ($fpro == 'fpro') {
							url_redirect(getlink("fpro&amp;file=viewtopic&amp;t=$tid"));
						} else {
							url_redirect(getlink("$fpro&amp;file=viewtopic&amp;t=$tid"));
						}
					}
				}
			} else {
					if ($fpro == '') {
						url_redirect(getlink("Forums&amp;file=viewtopic&amp;t=$tid"));
					} elseif ($fpro == 'fpro') {
						url_redirect(getlink("fpro&amp;file=viewtopic&amp;t=$tid"));
					} else {
						url_redirect(getlink("$fpro&amp;file=viewtopic&amp;t=$tid"));
					}
			}
		}
	}

/*	function dttime_edit($s, $gmttime, $hropt='', $blank='') {
		global $userinfo;
// echo ' GTM='.$gmttime.' DST='.$userinfo['user_dst'].' TZ='.$userinfo['user_timezone'].' L10N hour='.ProNewsAdm::pndate('H',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
		$gmttime = intval($gmttime);
		$separater = (!$hropt) ? '&nbsp;' : '&nbsp; <span class="pn_tinygrey">|</span> &nbsp;';
		$content = '';
		$content .= _DAY.' <select name="'.$s.'day">';
		$content .= ($blank) ? '<option></option>' : '';
		$target_day = ProNews::pndate('d',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
		for($i = 1; $i <= 31; $i++) {
			$content .= '<option'.(($i == $target_day) ? ($gmttime) ? ' selected="selected"' : '' : '').'>'.$i.'</option>';
		}
		$content .= '</select> '._UMONTH.' <select name="'.$s.'month">';
		$content .= ($blank) ? '<option selected="selected"></option>' : '';
		$target_mnth = ProNews::pndate('m',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
		for ($i = 1; $i <= 12; $i++) {
			$content .= '<option'.(($i == $target_mnth) ? ($gmttime) ? ' selected="selected"' : '' : '').'>'.$i.'</option>';
		}
		$content .= '</select> '._YEAR.' <select name="'.$s.'year">';
		$content .= ($blank) ? '<option selected="selected"></option>' : '';
		$target_yr = ProNews::pndate('Y',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
		$beg_yr = ($blank) ? (ProNews::pndate('Y', time()))-4 : $target_yr-4;
		$end_yr = $beg_yr + 15;
		for ($i = $beg_yr; $i <= $end_yr; $i++) {
			$content .= '<option'.(($i == $target_yr) ? ($gmttime) ? ' selected="selected"' : '' : '').'>'.$i.'</option>';
		}
		$content .= '</select> '.$separater.' ';
		$content .= _HOUR.' <select name="'.$s.'hour">';
		$content .= ($blank) ? '<option selected="selected"></option>' : '';
		$target_hr = ProNews::pndate('H',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
		for ($i = 0; $i <= 23; $i++) {
			$dummy = ($i < 10) ? "0$i" : $i;
			$content .= '<option'.(($dummy == $target_hr) ? ($gmttime && !($hropt && $blank && $target_hr == 0)) ? ' selected="selected"' : '' : '').'>'.$dummy.'</option>';
		}
		$content .= '</select> : <select name="'.$s.'min">';
		$content .= ($blank) ? '<option selected="selected"></option>' : '';
		$target_min = ProNews::pndate('i',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
		for ($i = 0; $i <= 59; $i++) {
			$dummy = ($i < 10) ? "0$i" : $i;
			$content .= '<option'.(($dummy == $target_min) ? ($gmttime && !($hropt && $blank && $target_hr == 0)) ? ' selected="selected"' : '' : '').'>'.$dummy.'</option>';
		}
		$content .= '</select>';
		$content .= ' '._PNGMT.((($userinfo['user_timezone']+L10NTime::in_dst(($gmttime == 0 ? time() : $gmttime), $userinfo['user_dst'])) >= 0) ? '+' : '').($userinfo['user_timezone']+L10NTime::in_dst(($gmttime == 0 ? time() : $gmttime), $userinfo['user_dst']))._PNHRS.' &nbsp; <span class="pn_tinygrey">';
		$content .= ($hropt) ? '('._PNOPTIONAL.')</span>' : '</span>';

		return $content;
	}
*/
	function dttime_edit($s, $gmttime, $hropt='', $blank='', $whlday='') {
		global $userinfo, $module_name, $pnsettings;
// echo '<br /> GTM='.$gmttime.' DST='.$userinfo['user_dst'].' TZ='.$userinfo['user_timezone'].' L10N hour='.ProNewsAdm::pndate('H',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
// echo '<br /> GTM='.$gmttime.' DST='.$userinfo['user_dst'].' TZ='.$userinfo['user_timezone'].' L10N hour='.L10NTime::in_dst($gmttime, $userinfo['user_dst']);
		$calpopup = '';
		if (file_exists('modules/'.$module_name.'/includes/calendarpopup.js')) {
			$calpopup = '1';
			echo '<script type="text/javascript">
var cal'.$s.' = new CalendarPopup("calpopdiv1");
var now = new Date();
now.setDate(now.getDate()-1);

cal'.$s.'.addDisabledDates(null,formatDate(now,"yyyy-MM-dd"));
cal'.$s.'.setReturnFunction("setMultipleValues'.$s.'");
cal'.$s.'.setTodayText("'._PNTODAY.'");
cal'.$s.'.showNavigationDropdowns();
cal'.$s.'.setCssPrefix("PN");
cal'.$s.'.offsetX = -100;
cal'.$s.'.offsetY = 30;
cal'.$s.'.setMonthNames("'._PNJAN.'","'._PNFEB.'","'._PNMAR.'","'._PNAPR.'","'._PNMAY.'","'._PNJUN.'","'._PNJUL.'","'._PNAUG.'","'._PNSEP.'","'._PNOCT.'","'._PNNOV.'","'._PNDEC.'");
cal'.$s.'.setDayHeaders("'._PNSUN.'","'._PNMON.'","'._PNTUES.'","'._PNWED.'","'._PNTHUR.'","'._PNFRI.'","'._PNSAT.'");

function setMultipleValues'.$s.'(y,m,d) {
	document.forms[\'addstory\'].'.$s.'year.options[document.forms[\'addstory\'].'.$s.'year.selectedIndex].text=y;
	document.forms[\'addstory\'].'.$s.'month.selectedIndex=m'.($s=='s' || $s == 'e' ? '-1' : '').'; // only non-Calendar dates need -1 ???
	for (var i = 0; i < document.forms[\'addstory\'].'.$s.'day.options.length; i++ ) {
		if (document.forms[\'addstory\'].'.$s.'day.options[i].text==d) {
			document.forms[\'addstory\'].'.$s.'day.selectedIndex=i;
		}
	}
}
		</script>';
		}

		$separater = (!$hropt) ? '&nbsp;' : '&nbsp; <span class="pn_tinygrey">|</span> ';
		$content = '';
		$content .= '<span class="pn_tiny">'._DAY.'</span> <select name="'.$s.'day">';
		$content .= ($blank) ? '<option>&nbsp;</option>' : '';
		$target_day = ProNews::pndate('d',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
		for($i = 1; $i <= 31; $i++) {
			$k = ($i < 10) ? "0$i" : $i;
			$content .= '<option'.(($i == $target_day) ? ($gmttime) ? ' selected="selected"' : '' : '').'>'.$k.'</option>';
		}
		$content .= '</select> <span class="pn_tiny">'._UMONTH.'</span> <select name="'.$s.'month">';
		$content .= ($blank) ? '<option selected="selected">&nbsp;</option>' : '';
		$target_mnth = ProNews::pndate('m',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
		for ($i = 1; $i <= 12; $i++) {
			$k = ($i < 10) ? "0$i" : $i;
			$content .= '<option'.(($i == $target_mnth) ? ($gmttime) ? ' selected="selected"' : '' : '').'>'.$k.'</option>';
		}
		$content .= '</select> <span class="pn_tiny">'._YEAR.'</span> <select name="'.$s.'year">';
		$content .= ($blank) ? '<option selected="selected">&nbsp;</option>' : '';
		$target_yr = ProNews::pndate('Y',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
		$beg_yr = ($blank) ? (ProNews::pndate('Y', time()))-4 : $target_yr-4;
		$end_yr = $beg_yr + 15;
		for ($i = $beg_yr; $i <= $end_yr; $i++) {
			$content .= '<option'.(($i == $target_yr) ? ($gmttime) ? ' selected="selected"' : '' : '').'>'.$i.'</option>';
		}
		$content .= '</select> &nbsp;';
		if ($calpopup != '') {
			if ($pnsettings['cal_module']) {
				if ($s == 'c') {
					$content .= '<a href="javascript:cal'.$s.'.showCalendar(\'anchor'.$s.'\',getDateString(document.forms[\'addstory\'].'.$s.'year,document.forms[\'addstory\'].'.$s.'month,document.forms[\'addstory\'].'.$s.'day));" name="anchor'.$s.'" id="anchor'.$s.'"><img src="modules/CPGNuCalendar/images/calendar.gif" style="border: 0pt none; width: 20px;" alt="'._PNLSELCAL.'..." title="'._PNLSELCAL.'..." /></a> ';
				} else {
					$content .= '<a href="javascript:var%20d=getDateString(document.forms[\'addstory\'].'.$s.'year,document.forms[\'addstory\'].'.$s.'month,document.forms[\'addstory\'].'.$s.'day);cal'.$s.'.showCalendar(\'anchor'.$s.'\',(d==null)?getDateString(document.forms[\'addstory\'].cyear,document.forms[\'addstory\'].cmonth,document.forms[\'addstory\'].cday):d);" name="anchor'.$s.'" id="anchor'.$s.'"><img src="modules/CPGNuCalendar/images/calendar.gif" style="border: 0pt none; width: 20px;" alt="'._PNLSELCAL.'..." title="'._PNLSELCAL.'..." /></a> ';
				}
			} else {
				if ($s == 's') {
					$content .= '<a href="javascript:cal'.$s.'.showCalendar(\'anchor'.$s.'\',getDateString(document.forms[\'addstory\'].'.$s.'year,document.forms[\'addstory\'].'.$s.'month,document.forms[\'addstory\'].'.$s.'day));" name="anchor'.$s.'" id="anchor'.$s.'"><img src="modules/CPGNuCalendar/images/calendar.gif" style="border: 0pt none; width: 20px;" alt="'._PNLSELCAL.'..." title="'._PNLSELCAL.'..." /></a> ';
				} else {
					$content .= '<a href="javascript:var%20d=getDateString(document.forms[\'addstory\'].'.$s.'year,document.forms[\'addstory\'].'.$s.'month,document.forms[\'addstory\'].'.$s.'day);cal'.$s.'.showCalendar(\'anchor'.$s.'\',(d==null)?getDateString(document.forms[\'addstory\'].syear,document.forms[\'addstory\'].smonth,document.forms[\'addstory\'].sday):d);" name="anchor'.$s.'" id="anchor'.$s.'"><img src="modules/CPGNuCalendar/images/calendar.gif" style="border: 0pt none; width: 20px;" alt="'._PNLSELCAL.'..." title="'._PNLSELCAL.'..." /></a> ';
				}
			}
		}
		$content .= ' '.$separater.' ';
		$content .= '<span class="pn_tiny">'._HOUR.'</span> <select name="'.$s.'hour">';
		$content .= ($blank || $whlday) ? '<option selected="selected">&nbsp;</option>' : '';
//		$target_hr = ProNews::pndate('H',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
//		$target_hr = ($gmttime == 0) ? "00" : ProNews::pndate('H',$gmttime, 0, $userinfo['user_timezone']);
		$target_hr = ($whlday) ? "00" : ProNews::pndate('H',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
// echo '<br />$target_hr='.$target_hr.' (gmtm='.$gmttime.')';
		for ($i = 0; $i <= 23; $i++) {
			$dummy = ($i < 10) ? "0$i" : $i;
			$content .= '<option'.(($dummy == $target_hr) ? ($gmttime && !$whlday && !($hropt && $blank && $target_hr == 0)) ? ' selected="selected"' : '' : '').'>'.$dummy.'</option>';
		}
		$content .= '</select> : <select name="'.$s.'min">';
		$content .= ($blank || $whlday) ? '<option selected="selected">&nbsp;</option>' : '';
		$target_min = ProNews::pndate('i',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
// echo '<br />target_min='.$target_min;
		for ($i = 0; $i <= 59; $i++) {
			$dummy = ($i < 10) ? "0$i" : $i;
			$content .= '<option'.(($dummy == $target_min) ? ($gmttime && !$whlday && !($hropt && $blank && $target_hr == 0)) ? ' selected="selected"' : '' : '').'>'.$dummy.'</option>';
		}
		$content .= '</select>';
		$content .= ' <span class="pn_tiny">'._PNGMT.((($userinfo['user_timezone']+L10NTime::in_dst(($gmttime == 0 ? time() : $gmttime), $userinfo['user_dst'])) >= 0) ? '+' : '').($userinfo['user_timezone']+L10NTime::in_dst(($gmttime == 0 ? time() : $gmttime), $userinfo['user_dst']))._PNHRS.'</span> &nbsp;<span class="pn_tinygrey">';
		$content .= ($hropt) ? '('._PNOPTIONAL.')</span>' : '&nbsp;</span>';



		return $content;
	}


	function pndate($format, $time, $region=0, $gmt=0) {
		global $LNG;
		# check if we already have a unix timestamp else convert
		if (!is_numeric($time)) {
			# 'YEAR-MONTH-DAY HOUR:MIN:SEC' aka MySQL DATETIME
			if (ereg('([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})', $time, $datetime)) {
				$time = mktime($datetime[4],$datetime[5],$datetime[6],$datetime[2],$datetime[3],$datetime[1]);
			}
		}
		# Convert the GMT time to local time
		$time = L10NTime::tolocal($time, $region, $gmt);
		# date() is affected by DST so we avoid that
		if (date('I') == 1 && date('I', $time) == 0) $time += 3600;
		# return correct formatted time
		$format = preg_replace('#([Dl])#', '_\\\\\\1w', $format);
		$format = preg_replace('#([FM])#', '_\\\\\\1n', $format);
		$time = gmdate($format, $time);
		return preg_replace('#_([DlFM])([0-9]{1,2})#e', '$LNG[\'_time\'][\'\\1\'][intval(\\2)]', $time);
	}


	function create_cal($clsdttime, $cledttime, $aid, $title, $intro, $postby) {
		global  $db, $prefix, $pnsettings, $userinfo;
// CPGNuCALENDAR Default Settings		----------------------------------------------------------------------
		$cal_image = 'circle.gif';		// To adjust these values for all CPGNuCalendar events
		$cal_priority = '2';			//   - edit these values here
		$cal_category = '1';			//   - look at cms_cpgnucalendar for possible values
		$cal_view = '1';				//
// CPGNuCALENDAR End Default Settings	----------------------------------------------------------------------

// CPGNuCALENDAR Time Correction if CPGNuCalendar logged event times differ from Pro_News in hours
		if ($pnsettings['cal_module'] == 2 && $pnsettings['cal_ofst'] != '' && is_numeric($pnsettings['cal_ofst'])) {
			$cal_timefix = $pnsettings['cal_ofst'] * 3600;
		} else {
			$cal_timefix = '';
		}

		$clstime = '';
		$cal_content = $intro.' &nbsp; [i][color=grey]'._PNCLK2FLLW.'[/color][/i] [url='.getlink("&amp;aid=".$aid).'] '._PNORGNGART.'[/url].';
		$startday = mktime(0, 0, 0, ProNews::pndate('m', $clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']), ProNews::pndate('d', $clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']), ProNews::pndate('Y', $clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']));
		if ($cledttime == 0) {
			$endday = $startday;
		} else {
//			$endday = mktime(0, 0, 0, ProNews::pndate('m', 0, $userinfo['user_timezone']), ProNews::pndate('d', $cledttime, 0, $userinfo['user_timezone']), ProNews::pndate('Y', $cledttime, 0, $userinfo['user_timezone']));
			$endday = mktime(0, 0, 0, date('m', $cledttime), date('d', $cledttime), date('Y', $cledttime));
		}
// echo ' startday='.$startday.' endday='.$endday.' s time='.$clsdttime.' e time='.$cledttime;
		if ($cledttime == 0) {
			$caldurtn = $startday + 86400 - $clsdttime;			// end date/time omitted so cap at end of day
			$cledttime = $clsdttime;							// force end date = start date if omitted
		}
		if ($clsdttime == $startday) {
			$clstime = -1;										// if whole day - ie no time provided
			$cal_durtn = 0;
		} else {
			$cal_durtn = (($cledttime - $endday) - ($clsdttime - $startday)) / 60;
		}
// echo ' clstime='.$clstime.' calduration='.$cal_durtn;
// echo '<br /> s hr='.ProNews::pndate('H',$clsdttime, 0, 0).' s min='.ProNews::pndate('i',$clsdttime, 0, 0).' e hr='.ProNews::pndate('H',$cledttime, 0, 0).' e min='.ProNews::pndate('i',$cledttime, 0, 0);
		$cal_rpt = ($endday > $startday) ? 'R' : 'E';
/*  change for functions.php 						$cal_appvd = (true && true) ? formatDateTime(time(),'%G%m%d') : ''; 		*/
		$cal_appvd = formatDateTime(time(),'%Y%m%d');
		$y = date('Y');
		$dtz = mktime(0,0,0,12,2,$y,0) - gmmktime(0,0,0,12,2,$y,0) + $cal_timefix;
// echo '<br /> dtz='.$dtz;
		$clstime = ($clstime == -1) ? -1 : date('Hi',$clsdttime + $dtz).'00';
// echo '<br> s time='.$clsdttime.' e time='.$cledttime.' s f day='.date('Ymd',$clsdttime + $dtz).' s f time='.$clstime.' dur='.$cal_durtn.' m f time='.date('His',time() + $dtz);
		$sql = 'INSERT INTO '.$prefix.'_cpgnucalendar VALUES ("0", "'.$postby.'", "'.formatDateTime($clsdttime,'%G%m%d').'", "'.$clstime.'", "'.formatDateTime(time(),'%G%m%d').'", "'.formatDateTime(time(),'%H%M%S').'", "'.$cal_durtn.'", "'.$cal_priority.'", "'.$cal_rpt.'", "'.$cal_view.'", "'.htmlunprepare($title).'", "'.$cal_content.'", "'.$cal_image.'", "'.$cal_appvd.'", "'.$cal_category.'")';
		$db->sql_query($sql);
		$cal_id = $db->sql_nextid('eid');  					// get and set calendar eid link
		$db->sql_query('UPDATE '.$prefix.'_pronews_articles SET cal_id="'.$cal_id.'" WHERE id='.$aid);
		if ($cal_rpt == 'R') {
			$sql = 'INSERT INTO '.$prefix.'_cpgnucalendar_repeat VALUES ("'.$cal_id.'", "daily", "'.formatDateTime($cledttime,'%G%m%d').'", "1", "nnnnnnn")';
			$db->sql_query($sql);
		}
	}


function create_date($format=false, $gmepoch)
{
	global $board_config, $pnsettings, $userinfo;
	if (!$format) {
		if ($pnsettings['dateformat'] != '') {
			$format = $pnsettings['dateformat'];
		} else {
			$format = 'l F d, Y (H:i:s)';
		}
	}
	if (is_user()) {
		return L10NTime::date($format, $gmepoch, $userinfo['user_dst'], $userinfo['user_timezone']);
	} else {
		if ($pnsettings['forumdate'] == "1") {
			return L10NTime::date($format, $gmepoch, 0, $board_config['board_timezone']);
		} else {
			return L10NTime::date($format, $gmepoch, 0, 0);
		}
	}
}

	function user_page_check($cat, $aid='') {
		global $db, $prefix, $pnsettings, $userinfo, $BASEHREF;
		$sql = 'SELECT a.id';
		$sql .= ' FROM '.$prefix.'_pronews_articles as a';
		$sql .= ' WHERE a.catid="'.$cat.'" AND postby="'.$userinfo['username'].'" LIMIT '.$pnsettings['member_pages'];
		$list = $db->sql_fetchrowset($db->sql_query($sql));
		if ($aid == '') {
			if (($list) && ($list != '')) {				// can't have 2 many
				return ($list);
			}
		} else {
// echo '<br />sizeof='.sizeof($list).' id='.$list[0]['id'];
			if ($list != '' && sizeof($list) == 1) {	// this is first one
				$usr_pge_lnk = getlink("&aid=".intval($aid));
				$db->sql_query("UPDATE ".$prefix."_users SET user_page='".$usr_pge_lnk."' WHERE user_id='".intval($userinfo['user_id'])."'");
				$userinfo['user_page'] = $usr_pge_lnk;
			}
			return '0';
		}
	}

	function getsctrnslt($lit_prfx, $txt, $id) {
		global $multilingual;
		if ($multilingual && defined($lit_prfx.$id)) {
			eval('$lit = '.$lit_prfx.$id.';');					// get translation for item
// echo '<br />lt='.$lit_prfx.' tx='.$txt.' id='.$id.' sl='.strlen($lit_prfx).' ss='.substr($lit, 0, strlen($lit_prfx)).' lt='.$lit;
			if (substr($lit, 0, strlen($lit_prfx)) == $lit_prfx) {
				$lit = '';										// check that it was defined
			}
			return $lit != '' ? $lit : $txt;							// return translation if found else original
		} else {
			return $txt;											// skip if not multilingual
		}
	}

	function stripBBCode($text_to_strip) {
// echo '<br />before='.$text_to_strip;
		$pattern = '|\[\/*?(?!url)[biu*(?:color)(?:size)(?:align)(?:list)(?:quote))][^\]]*?\]|si';	// b i u color fontsize align & list BBCodes only
		$replace = ' ';
		$text_to_strip = preg_replace($pattern, $replace, $text_to_strip);
		$pattern = '|\[([^\]]+?)(=[^\]]+?)?\](.+?)\[/\1\]|si';	// all remaining BBCodes + the intervening text
// $test = preg_replace($pattern, $replace, $text_to_strip);
// echo '<br />after='.$test;
		return preg_replace($pattern, $replace, $text_to_strip);
	}

	function search() {
		global $cpgtpl;
		require_once("search.php");
	}

	function approve($aid, $cid) {
		global $db, $prefix;
		$set = ($_GET['s'] == '1') ? '0' : '1';
		$sql = 'UPDATE '.$prefix.'_pronews_articles SET approved="'.$set.'" WHERE id="'.$aid.'"';
		$result = $db->sql_query($sql);
		if (is_admin()) {
			url_redirect(getlink("&amp;aid=".$aid."&amp;mod=mod").'#moderate');
		} else {
			url_redirect(getlink("&amp;cid=".$cid));
		}
	}

	function activate($aid, $cid) {
		global $db, $prefix;
		$set = ($_GET['s'] == '1') ? '0' : '1';
		$sql = 'UPDATE '.$prefix.'_pronews_articles SET active="'.$set.'" WHERE id="'.$aid.'"';
		$result = $db->sql_query($sql);
		if (is_admin()) {
			url_redirect(getlink("&amp;aid=".$aid."&amp;mod=mod").'#moderate');
		} else {
			url_redirect(getlink("&amp;cid=".$cid));
		}
	}

	function move_art($aid) {
		global $db, $prefix, $cpgtpl;
		if (isset($_POST['movcheck'])) {
			if (isset($_POST['confirm'])) {
				foreach (explode(',',Fix_Quotes($_POST['movcheck'],1)) as $id) {
					$db->sql_query('UPDATE '.$prefix.'_pronews_articles SET catid="'.intval($_POST['cat']).'" WHERE id="'.$aid.'"');
// echo 'cat='.intval($_POST['cat']).' aid='.$aid;
				}
				url_refresh(getlink("&amp;aid=".$aid."&amp;mod=mod").'#moderate', 2);
				$msg = '<div align="center">'._PNMOVSUC.'<br /></div>';
				$cpgtpl->assign_block_vars('pn_msg', array(
					'S_MSG' => $msg
				));

			} else {
				url_redirect(getlink("&amp;aid=".$aid."&amp;mod=mod").'#moderate');
			}
		} else {
			if ($_POST['cat2'] == '') {$_POST['cat2'] = '1';}  // prevent move to non-existant NULL category - layingback 061121
			$checked = implode(',',$_POST['checked']);echo '<br />';
			cpg_delete_msg(getlink("&amp;aid=".$aid."&amp;mod=mov").'#moderate',_PNCONFIRMARTMOV.' '.$aid,'<input type="hidden" name="movcheck" value="'.$checked.'" /><input type="hidden" name="cat" value="'.intval($_POST['cat2']).'" />');
		}
	}

	function get_status($type, $id, $status) {
		global $pnsettings;
		$stat = '&nbsp;';
		if (($id != '') && ($status != '')) {
			if (ProNews::in_group_list($pnsettings['mod_grp'])) {
				$stat .= '<a href="'.getlink("&amp;aid=".$id."&amp;mod=".$type."&amp;s=".$status).'#moderate">';
				if ($status == '1') {
					$stat .= '<img src="images/checked.gif" border="0" alt="" /></a>';
				} else {
					$stat .= '<img src="images/unchecked.gif" border="0" alt="" /></a>';
				}
			}
		}
		return $stat;
	}

	function in_group_list($list) {
		global $userinfo;
		$lst = explode(',',$list);
		foreach ($lst as $id) {
			if (isset($userinfo['_mem_of_groups'][$id])) {
				return true;
			}
		return false;
		}
	}

	function mod_upld($form) {
		global $cpgtpl, $CPG_SESS, $pagetitle, $MAIN_CFG, $pnsettings, $module_name;
		$pdftemplate = 'themes/'.$CPG_SESS['theme'].'/template/pronews/pdfl.html';
		if (file_exists($pdftemplate) && ProNews::in_group_list($pnsettings['mod_grp'])) {
			Security::check_post();
			$pagetitle .= ' '._BC_DELIM.' '._PNPUPLOAD;
			require_once('header.php');
			ProNews::mod_up($form);
		}
	}

	function mod_up($form) {
		global $cpgtpl, $CPG_SESS, $pnsettings, $module_name;
		if (file_exists('themes/'.$CPG_SESS['theme'].'/template/pronews/pdfl.html')) {
			if (ProNews::in_group_list($pnsettings['mod_grp'])) {
				Security::check_post();
				// upload dir - relative from document root
				// this needs to be a folder that is writeable by the server
				$destination = 'uploads/pdfs/';			// pdf folder - adjust as necessary

				// acceptable files
				// if array is blank then all file types will be accepted - not recommended
				$filetypes = array(	'pdf' => 'application/pdf',
						);

				// initialize success and error vars for processing
				$uploaded = '';
				$error = '';

				if ($form == '' || $form == 'none') {
					ProNews::mod_up_form($form, $filetypes, $destination);
				} else {
					if (!file_exists($destination)) {
						$error .= '<tr><td><b>'._PNERROR.'</b></td><td colspan="2">'.FOLDER.' <b>'.$destination .'</b> - '._PNDIRNOEXST.'</td></tr>';
					} elseif (!isset($_FILES) || $_FILES['file_0']['name'] == '') {
						$error .= '<tr><td><b>'._PNWARN.'</b></td><td colspan="2">'._PNNOFILES.'</td></tr>';
					} else {
						foreach($_FILES as $file) {
							switch($file['error']) {
								case 0:
									// file found
									if ($file['name'] != NULL && count($filetypes) >= 1) {
										// if no match is made to a valid file types array then kick it back
										if (!in_array($file['type'],$filetypes)) {
											$error .= '<tr><td><b>'._PNERROR.'</b></td><td colspan="2">'.$file['type'].' '._PNNOTFTYP.' - '.U_FILE.' '.$file['name'].' '._PNIGNORED.'</td></tr>';
										} else {
											// set full path/name of file to be moved
											$upload_file = $destination.$file['name'];
											if (file_exists($upload_file)) {
												$error .= '<tr><td><b>'._PNERROR.'</b></td><td colspan="2">'.$file['name'].' - '._PNFLEXIST.'</td></tr>';
											} elseif (!move_uploaded_file($file['tmp_name'], $upload_file)) {
												// failed to move file
												$error .= '<tr><td><b>'._PNERROR.'</b></td><td colspan="2">'._PNUPLOAD.'</td><td>'._PNUPLDFAIL.' '.$file['name'].' - '._PNTRYAGAIN.'</td></tr>';
											} else {
												$uploaded .= '<tr><td>'._PNUPLOADED.'</td><td>'.$file['name'].'</td><td>&nbsp; - &nbsp; <a href="'.$upload_file.'">'.$upload_file.'</a></td></tr>';
											}
										}
									}
									break;

								case (1|2):
									// upload too large
									$error .= '<tr><td><b>'._PNERROR.'</b></td><td colspan="2">'._PNFLE2LRG.' '.$file['name'].'</td></tr>';
									break;

								case 4:
									// no file uploaded
									break;

								case (6|7):
									// no temp folder or failed write - server config errors
									$error .= '<tr><td><b>'._PNERROR.'</b></td><td colspan="2">'._PNINTERR.' '.$file['name'].'</td></tr>';
									break;
							}
						}
					}
					$msg = _PNUPLOADCOMPL.'<br /><a href="'.getlink("&amp;mode=upld").'">'._PNGOBACK.'</a>';
					$cpgtpl->assign_block_vars('upload', array(
						'L_TITLE' => _PNUPLDRPT,
						'T_UPLOAD' => $uploaded,
						'T_ERRORS' => ($error) ? $error : '<tr><td colspan="3" align="center"><b>'._PNALLUPSUC.'</b></td></tr>',
						'S_MSG' => $msg
					));
					$cpgtpl->set_filenames(array('body' => 'pronews/pdfl.html'));
					$cpgtpl->display('body', false);
				}
			}
		}
	}

	function mod_up_form($func='',&$filetypes, $folder) {
		global $cpgtpl, $CPG_SESS, $module_name;
		$batch_limit  = 6;			// Maximum number of files to upload in 1 batch - use 0 for unlimited
		$max_size = '16 MB';		// Maximum individual file size for upload

		$types = '';
		foreach ($filetypes as $key => $value) {
			$types .= $key.', ';
		}
		$types = substr_replace($types ,'',-2);
		if ($batch_limit == 0) {
			$limit = _PNUNLIM;
			$max_num = -1;
		} else {
			$limit = $batch_limit;
			$max_num = $batch_limit;
		}
		$submit = _PNBTCHUP;
		$cpgtpl->assign_block_vars('upload_form', array(
			'G_STARTFORM' => open_form(getlink("&amp;mode=upld&amp;dir=up"),'multifile',_PNPUPLOAD),
			'G_ENDFORM' => close_form(),
			'L_TARGET' => _PNUPFILES,
			'S_TEXT' => _PNPDFUP,
			'T_TEXT' => _PNSELUPLD,
			'S_TYPES' => _PNALWFILES,
			'T_TYPES' => $types,
			'S_MAX' => _PNMAXFSIZE,
			'T_MAX' => $max_size,
			'L_NUMFILES' => _PNLSTUPLD,
			'S_NUMFILES' => _PNFLIM,
			'T_NUMFILES' => $limit,
			'G_ID' => '<input name="file_0" size="40" class="listbox" id="first_file_element" type="file" />',
			'G_SAVE' => '<input value="'.$submit.'" type="submit" />'
		));
		$cpgtpl->set_filenames(array('body' => 'pronews/pdfl.html'));
		$cpgtpl->display('body', false);
	}

}
