<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Original Beta version Copyright © 2006 by D Mower aka Kuragari
  Subsequent releases Copyright © 2007 - 2015 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  Interface to ForumsPro provided and Copyright © 2007 by Sarah
  http://www.diagonally.org

  $Revision: 3.48 $
  $Date: 2013-06-23 16:05:17 $
   Author: layingback
**********************************************/
if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin($module_name)) { die('Access Denied'); }
require_once('includes/nbbcode.php');
$pnsettings = $MAIN_CFG['pro_news'];
$gblsettings = $MAIN_CFG['global'];

// To support multi-home (see CHANGES.txt) add suitable entries to array below, e.g. array('layingback.net', 'layingback.com')
//  - Remember to also place corresponding entries in functions.php, and the order must match!
$domainlist = array();

class ProNewsAdm {

	function admin_artlist($func, $sort='') {
		global $db, $prefix, $userinfo, $pnsettings, $gblsettings, $bgcolor1, $bgcolor2, $bgcolor3, $CPG_SESS, $cpgtpl, $multilingual, $need_pagination, $pn_module_name, $pagetitle;

		// Begin of pagination feature, modified by Masino Sinaga, June 22, 2009
		$pages = '';
		$arts_per_page = (isset($pnsettings['per_admn_page']) && ($pnsettings['per_admn_page'] > '0')) ? $pnsettings['per_admn_page'] : '25';
		if (isset($_GET['page']) && intval($_GET['page']) > 1) {
			$page = intval($_GET['page']);
//			$pagetitle .= ' - '._PNPAGE.' '.$page;
		} else {
			$page = 1;
		}
		$offset = ($page - 1) * $arts_per_page;
		// End of pagination feature, modified by Masino Sinaga, June 22, 2009

		if (isset($_POST['cat']) && $_POST['cat'] != '') {
			$sortcat = intval($_POST['cat']);
		} elseif (isset($_GET['cat']) && $_GET['cat'] != '') {
			$sortcat = intval($_GET['cat']);
		} else {
			$sortcat = '';
		}						  // moved up from $func=sort to set $sortcat before filling artlist_top: layingback 061120
		if ((isset($_POST['sort'])) && ($_POST['sort'] != '')) {
			$sort = Fix_Quotes($_POST['sort']);
		} elseif ((isset($_GET['sort'])) && ($_GET['sort'] != '')) {
			$sort = Fix_Quotes($_GET['sort']);
		} else {
			$sort = '';
		}
// echo ' sort='.$sort.' sortcat='.$sortcat;
		$cpgtpl->assign_block_vars('artlist_top', array(
			'G_STARTFORM' => open_form(adminlink("&amp;mode=list&amp;do=sort&amp;sort=".$sort),'artlist_top','&nbsp;'._PNSELCAT.'&nbsp;'),
			'G_ENDFORM' => close_form(),
			'T_CATS' => ProNewsAdm::admin_seccat('cat',$sortcat,true,'',true),  // $sortcat param added for select=selected: layingback 061120
			'T_SUBMIT' => '<input type="submit" name="submitcat" value="'._PNGO.'" />',
			'S_DSPLYBY' => _PNDSPLYORDER.' ',
			'T_DSPLYBY' => select_box('sort', $sort, array('0'=> _PNSECDFLT , 'posttime ASC'=> _PNDTASC , 'posttime DESC'=> _PNDTDSC , 'a.title ASC'=> _PNTTLASC , 'a.title DESC'=> _PNTTLDSC , 'postby ASC'=> _PNPTDBASC , 'postby DESC'=> _PNPTDBDSC , 'a.counter ASC'=> _PNRDSASC , 'a.counter DESC'=> _PNRDSDSC , 'ratings ASC'=> _PNRATASC , 'ratings DESC'=> _PNRATDSC , 'active DESC, approved DESC'=> _PNACTV1ST , 'active ASC, approved ASC'=> _PNINACT1ST , 'approved DESC, active DESC'=> _PNAPPD1ST , 'approved ASC, active ASC'=> _PNUAPP1ST )),
			'S_MSG' => _PNCHOOSECAT,
			'P_MSG' => _PNCHOOSEPENDCAT
		));
		if ($func == 'sort' && $sortcat != '') {
			if ($sort != '' && $sort !='0') {$sortby = $sort;} else {$sortby = '';}
// echo ' sortby='.$sortby;
			$artsortkey = $pnsettings['art_ordr'] / '2';
			$artsortord = ($pnsettings['art_ordr'] % '2')  ? 'DESC' : 'ASC';
			if ($artsortkey < 1) {
				$artsortfld = 'posttime';
			} elseif ($artsortkey < 2) {
				$artsortfld = 'a.title';
			} elseif ($artsortkey < 3) {
				$artsortfld = 'ratings';
			} elseif ($artsortkey < 4) {
				$artsortfld = 'a.counter';
			}
			$sql = 'SELECT a.*, c.title ctitle, c.id cid, s.id sid, s.title stitle, s.art_ord, template FROM '.$prefix.'_pronews_articles as a';
			$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON a.catid=c.id';
			$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id';
			if ($pnsettings['actv_sort_ord'] == '1') {
				$sql .= ' LEFT JOIN '.$prefix.'_pronews_schedule as h ON a.id=h.id AND CASE s.art_ord';
				$sql .= ' WHEN "9" THEN h.newstate="0" WHEN "10" THEN h.newstate="1" WHEN "11" THEN h.newstate="0" WHEN "12" THEN h.newstate="1" END';
			}

			$sql .= ' WHERE a.catid='.$sortcat;

			if ($sortby) {
				$sql .= ' ORDER BY '.$sortby;
			} else {
				$sql .= ' ORDER BY a.display_order DESC,';
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
			}

			//$list = $db->sql_fetchrowset($db->sql_query($sql));
			// Pagination, Modified by Masino Sinaga, June 22, 2009
			if ($sort != '' && $sort !='0') {$sortby = $sort;} else {$sortby = 'a.display_order DESC, a.posttime DESC';}
			$sqlcount = ' a.catid = c.id AND c.sid = s.id AND a.catid = '.$sortcat.' ORDER BY '.$sortby;
			$numarticles = $db->sql_count($prefix.'_pronews_articles as a, '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_sections as s ', $sqlcount);
			$pages = ceil($numarticles/$arts_per_page);
			if ($pages < $page && $arts_per_page > '0' && $page != '1') { cpg_error(_PNNOTHING); }
			$sql .= ' LIMIT '.$offset.','.$arts_per_page;
			$result = $db->sql_query($sql);
			$list = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);
			// Pagination, Modified by Masino Sinaga, June 22, 2009


			$bgcolor = '';
			if (($list) && ($list != '')) {
				$templist = '';
				$x = 1;
				foreach ($list as $row) {
					$url_text = ($pnsettings['text_on_url'] ? '&amp;'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', ($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title']) : ($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title'])))) : '');
					$no = $x + ($page-1)*$arts_per_page;
					$bgcolor = ($bgcolor == '') ? ' style="bgcolor: '.$bgcolor3.'"' : '';
					$templist .= '<tr'.$bgcolor.'>';
					$templist .= '<td align="right">'.$no.'</td>';
					$templist .= '<td><input type="checkbox" name="checked[]" value="'.$row['id'].'" /></td>';
					$templist .= '<td><a href="'.getlink("&amp;aid=".$row['id'].$url_text).'">'.$row['title'].($row['display_order']<>0 ? ' <span class="pn_tinygrey"> /'.$row['display_order'].'</span>' : '').'</a></td>';
					$templist .= '<td>'.$row['postby'].'</td>';
					$templist .= ($row['display'] <> '0')  ? (($row['display'] == '2') ? '<td align="center" style="font-weight: bold; text-decoration: underline;">'._PNY.'</td>' : '<td align="center" style="font-weight: bold;">'._PNY.'</td>') : '<td>&nbsp;</td>';
					$templist .= '<td class="pn_tinygrey">'.formatDateTime($row['posttime'],_DATESTRING).'</td>'; // added by Masino Sinaga, June 23, 2009
					$templist .= '<td align="center">'.ProNewsAdm::admin_get_status('app',$row['id'],$row['approved'],$row['catid']).'</td>';

// $res = ('^(\d*)\.?(5)','${1}:00',$userinfo['user_timezone']+L10NTime::in_dst($row2['dttime'], $userinfo['user_dst']));

					$result = $db->sql_query('SELECT dttime FROM '.$prefix.'_pronews_schedule WHERE id="'.$row['id'].'" AND newstate="1"');
					$row2 =$db->sql_fetchrow($result, SQL_ASSOC);
					$act_sched = ($row2['dttime']) ? '<img src="images/pro_news/plus.png" width="9" height="9" border="0" alt="" title="'.ProNewsAdm::pndate("d M y H:i", intval($row2['dttime']), $userinfo['user_dst'], $userinfo['user_timezone']).' (GMT'.((($userinfo['user_timezone']+L10NTime::in_dst($row2['dttime'], $userinfo['user_dst'])) >= 0) ? '+' : '').($userinfo['user_timezone']+L10NTime::in_dst($row2['dttime'], $userinfo['user_dst']))._PNHRS.')" />' : '<img src="images/pro_news/icons/clearpixel.gif" width="9" height="9" border="0" alt="" />' ;

// echo ' dst='.$userinfo['user_dst'].' tz='.$userinfo['user_timezone'];

// $local = ProNewsAdm::pndate("d M y H:i", intval($row2['dttime']), $userinfo['user_dst'], $userinfo['user_timezone']);
// $gmtoffset = ($userinfo['user_timezone']+L10NTime::in_dst($row2['dttime'], $userinfo['user_dst']));
//$sign = ($gmtoffset >= 0) ? '+' : '';
// $lcltime = ProNewsAdm::pndate("d M y H:i", intval($row2['dttime']), $userinfo['user_dst'], $userinfo['user_timezone']).' (GMT'.((($userinfo['user_timezone']+L10NTime::in_dst($row2['dttime'], $userinfo['user_dst'])) >= 0) ? '+' : '').($userinfo['user_timezone']+L10NTime::in_dst($row2['dttime'], $userinfo['user_dst'])).'hrs)';
// echo ' lcltime='.$lcltime;
					$result = $db->sql_query('SELECT dttime FROM '.$prefix.'_pronews_schedule WHERE id="'.$row['id'].'" AND newstate="0"');
					$row2 =$db->sql_fetchrow($result, SQL_ASSOC);
					$deact_sched = ($row2['dttime']) ? '<img src="images/pro_news/minus.png" width="9" height="9" border="0" alt="" title="'.ProNewsAdm::pndate("d M y H:i", intval($row2['dttime']), $userinfo['user_dst'], $userinfo['user_timezone']).' (GMT'.((($userinfo['user_timezone']+L10NTime::in_dst($row2['dttime'], $userinfo['user_dst'])) >= 0) ? '+' : '').($userinfo['user_timezone']+L10NTime::in_dst($row2['dttime'], $userinfo['user_dst']))._PNHRS.')" />' : '<img src="images/pro_news/icons/clearpixel.gif" width="9" height="9" border="0" alt="" />' ;

					$templist .= '<td align="center" nowrap="nowrap">'.$act_sched.ProNewsAdm::admin_get_status('act',$row['id'],$row['active'],$row['catid']).$deact_sched.'</td>';
					$templist .= ($multilingual ? '<td align="center">'.($row['alanguage'] == '' ? _PNALL : $row['alanguage']).'</td>' : '');
					$templist .= '<td align="right"><a href="'.adminlink("&amp;mode=add&amp;do=edit&amp;id=".$row['id']).'">'._EDIT.'</a></td></tr>';
					$x++;
				}         // added View link to Article title: layingback 061121
				$cpgtpl->assign_block_vars('artlist', array(
					'G_STARTFORM' => open_form(adminlink("&amp;mode=list&amp;do=mod"),'artlist','&nbsp;'._PNARTICLES.'&nbsp;'),
					'G_ENDFORM' => close_form(),
					'G_BGCOLOR' => $bgcolor2,
					'S_PAGENO' => $page > 1 ? ' - '._PNPAGE.' '.$page : '',
					'S_ARTCOUNT' => $numarticles,
					'S_ARTFOUND' => ($numarticles == '1') ? $numarticles.' '._PNFOUNDART : $numarticles.' '._PNFOUNDARTS ,
					'S_SECTITLE' => _PNSECTION,
					'U_SECTITLE' => '<a href="'.getlink("&amp;sid=".$list['0']['sid']).'" title="'.$list['0']['template'].'">'.$list['0']['stitle'].'</a>',
					'S_CATTITLE' => ' | '._PNCAT,
					'U_CATTITLE' => '<a href="'.getlink("&amp;cid=".$list['0']['cid']).'">'.$list['0']['ctitle'].'</a>',
					'L_TITLE' => _PNTITLE,
					'L_OWNER' => _PNPOSTEDBY,
					'L_DATE' => _PNFORART,
					'L_APPROVED' => _PNAPPROVED,
					'L_ACTIVE' => _PNACTIVE,
					'L_DISPLAY' => _PNHOME,
					'S_LEGEND' => '<img src="images/pro_news/plus.png" width="9" height="9" border="0" alt="" /> - '._PNLEGENDA.' &nbsp; <img src="images/pro_news/minus.png" width="9" height="9" border="0" alt="" /> - '._PNLEGENDB.' &nbsp; (<i>'._PNMOUSE.'</i>)',
					'G_LANG' => ($multilingual ? '1' : '0'),
					'L_LANG' => _PNLANG,
					'S_LIST' => $templist,
					'L_CHKALL' => '<input type="hidden" name="modtype" value="modtype" /><a href="javascript:CheckAll()">'._PNCHECKALL.'</a>',
					'L_UCHKALL' => '<a href="javascript:UncheckAll()">'._PNUNCHECKALL.'</a>',
					'L_TARGETCAT' => _PNTARGETCAT,
					'L_ACTIVATE' => '',
					'L_APPROVE' => '',
					'L_CATLIST' => ProNewsAdm::admin_seccat('cat2','',false,'',''),
					'L_COPY' => '<a href="javascript:domod(&quot;copy&quot;)">'._PNCOPY.'</a>',
					'L_MOVE' => '<a href="javascript:domod(&quot;move&quot;)">'._PNMOVE.'</a>',
					'L_DEL' => '<a href="javascript:domod(&quot;delete&quot;)">'._DELETE.'</a>'
				));

				if ($sortcat) {
					$cpgtpl->assign_block_vars('sub_list', array(
						'IS_USER' => (is_user()) ? '1' : '',
						'G_STARTFORM' => open_form(adminlink("&amp;mode=add&amp;do=new&amp;cat=".$sortcat),'addart','&nbsp;'._PNADDARTICLE.'&nbsp;'),
						'G_ENDFORM' => close_form(),
						'U_SUBMIT' => '<input type="submit" name="addart" value="'._PNADDNXTART.'" />',
					));
				}

			} else {
				$msg = _PNNOARTICLES;
				$cpgtpl->assign_block_vars('pn_msg', array(
					'S_MSG' => $msg
				));

				$cpgtpl->assign_block_vars('sub_list', array(
					'IS_USER' => (is_user()) ? '1' : '',
					'G_STARTFORM' => open_form(adminlink("&amp;mode=add&amp;do=new&amp;cat=".$sortcat),'addart','&nbsp;'._PNADDARTICLE.'&nbsp;'),
					'G_ENDFORM' => close_form(),
					'U_SUBMIT' => '<input type="submit" name="addart" value="'._PNADDNXTART.'" />',
				));
			}
		}

		// Pagination feature, modified by Masino Sinaga, June 22, 2009
		pagination($pn_module_name.'&amp;mode=list&amp;do=sort&amp;sort='.$sort.'&amp;cat='.$sortcat.'&amp;page=', $pages, 1, $page);
//		$cpgtpl->set_filenames(array('pagin' => 'pronews/pagination.html'));
		$cpgtpl->set_filenames(array('pagin' => 'pagination.html'));
		$need_pagination = '1';
//		$cpgtpl->display('pagin', false);
		// Pagination feature, modified by Masino Sinaga, June 22, 2009

		if ($func == 'app') {
			if ((isset($_GET['id'])) && (isset($_GET['re'])) && (isset($_GET['s']))) {
				$set = ($_GET['s'] == '1') ? '0' : '1';
				$sql = 'UPDATE '.$prefix.'_pronews_articles SET approved="'.$set.'" WHERE id="'.intval($_GET['id']).'"';
				$result = $db->sql_query($sql);

				// Tell user if his/her article has been approved! modified by Masino Sinaga, June 23, 2009
				$sql = 'SELECT u.user_email, a.title FROM '.$prefix.'_pronews_articles a, '.$prefix.'_users u
					WHERE u.username = a.postby AND a.id="'.intval($_GET['id']).'"';
    		    list($user_email, $arttitle) = $db->sql_fetchrow($db->sql_query($sql));
				if ($pnsettings['notify_user_approving_article']=='1') {
					$sitename = $gblsettings['sitename'];
					$subject_email = (($pnsettings['subject_email_approved_article']) ? $pnsettings['subject_email_approved_article'] : _PNSUBJAPPRVART).' '._PNAT.' '.$sitename;
					$message_body = _PNARTICLE.': '.$arttitle."\n\n".(($pnsettings['body_email_approved_article']) ? $pnsettings['body_email_approved_article'] : _PNBODYAPPRVART."\n\n"._PNTHANKU)."\n"._PNADMINISTRATOR." ".$sitename;
					if (!send_mail($mailer_message, $message_body, 0, $subject_email, $user_email, $user_email, $pnsettings['sender_email_pending_article'], $pnsettings['sender_email_pending_article'])) {
						$msg = $mailer_message;
					}
				}

				url_redirect(adminlink("&amp;mode=list&amp;do=sort&amp;cat=".Fix_Quotes($_GET['re'])));
			}
		}
		if ($func == 'act') {
			if ((isset($_GET['id'])) && (isset($_GET['re'])) && (isset($_GET['s']))) {
				$set = ($_GET['s'] == '1') ? '0' : '1';
				$sql = 'UPDATE '.$prefix.'_pronews_articles SET active="'.$set.'" WHERE id="'.intval($_GET['id']).'"';
				$result = $db->sql_query($sql);
				url_redirect(adminlink("&amp;mode=list&amp;do=sort&amp;cat=".Fix_Quotes($_GET['re'])));
			}
		}

		if ($func == 'mod') {
			if ((isset($_POST['modtype'])) && ($_POST['modtype'] == 'move')) {
				if ($_POST['cat2'] == '') {$_POST['cat2'] = '1';}  // prevent move to non-existant NULL category - layingback 061121
				$checked = implode(',',$_POST['checked']);echo '<br />';
				cpg_delete_msg(adminlink("&amp;mode=list&amp;do=mod"),_PNCONFIRMARTMOV,'<input type="hidden" name="movcheck" value="'.$checked.'" /><input type="hidden" name="cat" value="'.intval($_POST['cat2']).'" />');

			}
			elseif (isset($_POST['movcheck'])) {
				if (isset($_POST['confirm'])) {
					foreach (explode(',',Fix_Quotes($_POST['movcheck'],1)) as $id) {
						$db->sql_query('UPDATE '.$prefix.'_pronews_articles SET catid="'.intval($_POST['cat']).'" WHERE id="'.$id.'"');
					}
					url_redirect(adminlink("&amp;mode=list&amp;do=sort&amp;cat=".intval($_POST['cat'])));  // display ArticleList for target Cat: layingback 061121
					$msg = '<div align="center">'._PNMOVSUC.'<br /></div>';
					$cpgtpl->assign_block_vars('pn_msg', array(
						'S_MSG' => $msg
					));

				} else {
					url_redirect(adminlink("&amp;mode=list"));
				}
			}
			elseif ((isset($_POST['modtype'])) && ($_POST['modtype'] == 'copy')) {
				if ($_POST['cat2'] == '') {$_POST['cat2'] = '1';}  // prevent copy to non-existant NULL category - layingback 061121
				$checked = implode(',',$_POST['checked']);echo '<br />';
				cpg_delete_msg(adminlink("&amp;mode=list&amp;do=mod"),_PNCONFIRMARTCPY,'<input type="hidden" name="cpycheck" value="'.$checked.'" /><input type="hidden" name="cat" value="'.intval($_POST['cat2']).'" />');
			}
			elseif (isset($_POST['cpycheck'])) {
				if (isset($_POST['confirm'])) {
					foreach (explode(',',$_POST['cpycheck']) as $id) {
						$result = $db->sql_query('SELECT image, image2 FROM '.$prefix.'_pronews_articles WHERE id='.$id);
						$images = $db->sql_fetchrow($result);
						$db->sql_freeresult($result);
						if ($images) {
							$image = ($images['image']) ? ProNewsAdm::admin_imgcopy($images['image']) : '';
							$image2 = ($images['image2']) ? ProNewsAdm::admin_imgcopy($images['image2']) : '';
						}
						$db->sql_query('INSERT INTO '.$prefix.'_pronews_articles (id, catid, title, intro, content, image, caption, allow_comment, postby, postby_show, posttime, show_cat, active, approved, viewable, display_order, alanguage, album_id, album_cnt, album_seq, slide_show, image2, caption2, user_fld_0, user_fld_1, user_fld_2, user_fld_3, user_fld_4, user_fld_5, user_fld_6, user_fld_7, user_fld_8, user_fld_9, df_topic, associated, display)
							 SELECT NULL, '.$_POST['cat'].', title, intro, content, "'.$image.'", caption, allow_comment, "'.$userinfo['username'].'", postby_show, '.gmtime().', show_cat, active, approved, viewable, display_order, alanguage, album_id, album_cnt, album_seq, slide_show, "'.$image2.'", caption2, user_fld_0, user_fld_1, user_fld_2, user_fld_3, user_fld_4, user_fld_5, user_fld_6, user_fld_7, user_fld_8, user_fld_9, df_topic, associated, display FROM '.$prefix.'_pronews_articles WHERE id="'.$id.'"');		// Do NOT copy counter, score, ratings, df_topic, cal_id
						$newid = mysql_insert_id();
						$result = $db->sql_query('SELECT * FROM '.$prefix.'_pronews_schedule WHERE id='.$id);
						while ($row2 = $db->sql_fetchrow($result)) {
							$db->sql_query('INSERT INTO '.$prefix.'_pronews_schedule VALUES ("'.$newid.'", "'.$row2['newstate'].'", "'.intval($row2['dttime']).'")');
						}
					}
					url_redirect(adminlink("&amp;mode=list&amp;do=sort&amp;cat=".$_POST['cat']));  // display ArticleList for target Cat: layingback 061121
					$msg = '<div align="center">'._PNCPYSUC.'<br /></div>';
					$cpgtpl->assign_block_vars('pn_msg', array(
						'S_MSG' => $msg
					));
				} else {
					url_redirect(adminlink("&amp;mode=list"));
				}
			}
			elseif ((isset($_POST['modtype'])) && ($_POST['modtype'] == 'delete')) {
// echo 'chkd='.print_r($_POST['checked']);
				$checked = implode(',',$_POST['checked']);echo '<br />';
// echo '$chkd='.$checked;
				cpg_delete_msg(adminlink("&amp;mode=list&amp;do=mod"),_PNDELARTS.': '.$checked.'<br /><br />'._PNCONFIRMARTDEL.'<br />( <i>'._PNNOUNDO.'</i> )','<input type="hidden" name="delcheck" value="'.$checked.'" />');
			}
			elseif (isset($_POST['delcheck'])) {
				if (isset($_POST['confirm'])) {
// echo 'delchk='.$_POST['delcheck'];
					foreach (explode(',',Fix_Quotes($_POST['delcheck'],1)) as $id) {
// echo '<br />$id='.$id;
						$db->sql_query('DELETE FROM '.$prefix.'_pronews_articles WHERE id="'.$id.'"');
						$db->sql_query('DELETE FROM '.$prefix.'_pronews_schedule WHERE id="'.$id.'"');
					}
					$msg = '<div align="center">'._PNARTICLES.' '._PNDELSUC.'<br /><a href="'.adminlink("&amp;mode=list").'">'._PNGOBACK.'</a></div>';
					$cpgtpl->assign_block_vars('pn_msg', array(
						'S_MSG' => $msg
					));

				} else {
					url_redirect(adminlink("&amp;mode=list"));
				}
			}
		}
	}

	function admin_block_form($func='',$bid='') {
		global $cpgtpl, $db, $prefix;
		$category = '';
		if (($bid != '') && ($func == 'save')) {
			$submit = _PNSAVE;
			$dosave='save&amp;bid='.$bid;
			$btitle = '&nbsp;'._PNAEDIT.'&nbsp;'._PNBLOCK.'&nbsp;';
			list($bid, $type, $section, $num, $category) = $db->sql_fetchrow($db->sql_query('SELECT * FROM '.$prefix.'_pronews_blocks WHERE bid='.$bid));
			list($blktitle) = $db->sql_fetchrow($db->sql_query('SELECT title FROM '.$prefix.'_blocks WHERE bid='.$bid));
		} else {
			$bid='';
			$type = 'latest';
			$section='ALL';
			$num='';
			$submit=_PNADD.'&nbsp;'._PNBLOCK;
			$dosave='save';
			$btitle = '&nbsp;'._PNADD.'&nbsp;'._PNBLOCK.'&nbsp;';
		}
//		$sql = 'SELECT id, title FROM '.$prefix.'_pronews_sections WHERE id <> "0" ORDER By sequence';
		$sql = 'SELECT id, title FROM '.$prefix.'_pronews_sections ORDER By sequence';
		$list = $db->sql_fetchrowset($db->sql_query($sql));
		$sections = '<select name="section">';
		foreach ($list as $row) {
			$selected = ($section == $row['id']) ? ' selected="selected"' : '';
			$sections .= '<option value="'.$row['id'].'"'.$selected.'>'.$row['title'].'</option>';
		}
		$selected = ($section == 'ALL') ? ' selected="selected"' : '';
		$sections .= '<option value="ALL"'.$selected.'>-- '._PNALL.' --</option></select>';
		$type_list[_PNLSIDEONLY] = array('moderator' => _PNMODERATOR);
		$type_list[_PNLSIDE] = array('latest' => _PNMSTRCNT, 'popular' => _PNMSTPPLR, 'rated' => _PNRATED);
		$type_list[_PNLMENU] = array('menu' => _PNMENU, 'menuwa' => _PNMENUWA);
		$type_list[_PNLCENTER] = array('oldestctr' => _PNOLDSTCTR, 'latestctr' => _PNMSTRCNTCTR, 'randomctr' => _PNRANDOMCTR, 'headlines' => _PNHEADLINES);
		$type_list[_PNLCENTERSECTN] = array('toldestctr' => _PNOLDSTCTR.' (t)', 'tlatestctr' => _PNMSTRCNTCTR.' (t)', 'trandomctr' => _PNRANDOMCTR.' (t)', 'theadlines' => _PNHEADLINES.' (t)');
		$type_list[_PNLCOMMENT] = array('comments' => _PNCOMMENTS);
		$types = '<select name="type">';
		foreach ($type_list as $u => $uu) {
// echo '<br />list '.$u.'= ';print_r($uu);
			if ($bid != '' && !array_key_exists($type, $uu)) {		// for Edit limit selection to same type group
			} else {
				$types .= '<optgroup label="'.$u.'"'.'>';
				foreach ($uu as $t => $tt) {
					$selected = ($type == $t) ? ' selected="selected"' : '';
					$types .= '<option value="'.$t.'"'.$selected.'>'.$tt.'</option>';
				}
				$types .= '</optgroup>';
			}
		}
		$types .= '</select>';
// echo 'mc='.$row['category'];
		$catlist = explode(',', $category , 2);
// print_r($catlist);
 		$tempcat = array(
			'G_STARTFORM' => open_form(adminlink("&amp;mode=blk&amp;do=".$dosave),'blockform',$btitle),
			'G_ENDFORM' => close_form(),
			'S_TYPES' => _PNTYPE,
			'T_TYPES' => $types,
			'L_TEXT' => ' &nbsp; '._PNBLKMENU,
			'S_NUM' => _PNNUM,
			'T_NUM' => ($num == '' || $num == '0') ? '<input type="text" name="num" size="5" value="5" />' : '<input type="text" name="num" size="5" value="'.$num.'" />',
			'S_SEC' => _PNSECTION,
			'T_SEC' => $sections,
			'L_OR' => _PNOR,
			'S_CAT' => _PNCAT,
			'T_CAT' => ProNewsAdm::admin_seccat('cat', $catlist['0'], false, _PNALL. ''),
			'S_MOREC' => _PNMOREC,
			'T_MOREC' => '<input type="checkbox" name="morec" value="" '.($catlist['1'] != '' ? 'checked' : '').' onclick="document.getElementById(\'morecat\').style.visibility = \'visible\'" />',
			'S_MORECAT' => '<span style="color:red;font-weight:bold">+</span> '._PNMORECAT,
			'T_MORECAT' => '<input type="text" name="morecat" size="20" value="'.$catlist['1'].'" />',
			'G_MORECAT' => $catlist['1'] != '' ? 'visible' : 'hidden',
			'L_MORECAT' => _PNMORECOMMA,
			'S_TITLE' => _PNBLKTITLE,
			'T_TITLE' => ($bid == '') ? '<input type="text" name="title" size="25" value="" />' : '<strong>'.$blktitle.'</strong>',
			'G_ID' => ($bid != '') ? '<input type="hidden" name="bid" value="'.$bid.'" />' : '',
			'G_SAVE' => '<input type="submit" name="submit" value="'.$submit.'" />'
		);
		return $tempcat;

	}

	function admin_blocks($func='') {
		global $db, $prefix, $bgcolor2, $bgcolor3, $cpgtpl, $gblsettings;
		if (($func == '') || ($func == 'none')) {
			$sql = 'SELECT p.*, p.bid as pbid, b.bid as bbid, s.title as stitle, b.title as btitle, c.title as ctitle';
			$sql .= ' FROM '.$prefix.'_pronews_blocks as p';
			$sql .= ' LEFT JOIN '.$prefix.'_pronews_cats as c ON p.category=c.id';
			$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON CASE p.category WHEN 0 THEN p.section=s.id ELSE s.id=c.sid END';
			$sql .= ' LEFT JOIN '.$prefix.'_blocks as b ON p.bid=b.bid';
			$sql .= ' ORDER BY b.title';
			$list = $db->sql_fetchrowset($db->sql_query($sql));
			$templist = '';
			$bgcolor = '';
			if ($list) {
				foreach ($list as $row) {
					$section = (!is_numeric($row['section'])) ? strtoupper($row['section']) : $row['stitle'];
// echo 'mc='.$row['category'];
					$catlist = explode(',', $row['category'] , 2);
					$morecat = $catlist['1'] != '' ? '+' : '';
// print_r($catlist);
					$bgcolor = ($bgcolor == '') ? ' style="bgcolor: '.$bgcolor3.'"' : '';
					$templist .= ($row['bbid']) ? '<tr'.$bgcolor.'><td><a href="'.adminlink("&amp;op=blocks&amp;show=".$row['pbid']).'" target="blank">'.$row['pbid'].'</a></td>' : '<tr'.$bgcolor.'><td'.((!$row['bbid']) ? ' class="pn_grey"' : '').'>'.$row['pbid'].'</td>';
					$templist .= '<td>'.$row['btitle'].'</td>';
					$templist .= '<td'.((!$row['bbid']) ? ' class="pn_grey"' : '').'>'.$row['type'].'</td>';
					$templist .= '<td'.((!$row['bbid']) ? ' class="pn_grey"' : '').'>'.($row['category'] != '' ? '<i>'.(strlen($row['stitle']) > 20 ? substr($row['stitle'],0,8).'...'.substr($row['stitle'],-4,4): $row['stitle']).'</i> / <a href="'.getlink("&amp;mode=allarts&amp;cat=".$catlist['0']).'" title="+ '.$catlist['1'].'">'.$row['ctitle'].' '.$morecat : '<a href="'.getlink("&amp;mode=allarts&amp;sec=".$row['section']).'">'.$section).'</a></td>';
					$templist .= '<td align="center"'.((!$row['bbid']) ? ' class="pn_grey"' : '').'>'.$row['num'].'</td>';
					$templist .= '<td align="right">'.(($row['bbid']) ? '<a href="'.adminlink("&amp;mode=blk&amp;do=edit&amp;id=".$row['pbid']).'">'._PNAEDIT.'</a> / ' : '').'<a href="'.adminlink("&amp;mode=blk&amp;do=del&amp;id=".$row['pbid']).'">'._DELETE.'</a></td></tr>';
				}
			}
			$cpgtpl->assign_block_vars('block_list', array(
				'G_STARTFORM' => open_form(adminlink("&amp;mode=blk"),'block_list','&nbsp;'._BLOCKS.'&nbsp;'),
				'G_ENDFORM' => close_form(),
				'G_BGCOLOR' => $bgcolor2,
				'L_ID' => _PNID,
				'L_TITLE' => _PNTITLE,
				'L_TYPE' => _PNTYPE,
				'L_SECCAT' => _PNSECTION,
				'L_OR' => _PNOR,
				'L_CAT' => '<i>'._PNSECTION.'</i> / '._PNCAT,
				'L_NUM' => _PNNUM,
				'T_LIST' => $templist,
			));
			$cpgtpl->assign_block_vars('block_form', ProNewsAdm::admin_block_form('add'));

		}
		if ($func == 'edit') {
			if ($_GET['id'] != '') {
				$cpgtpl->assign_block_vars('block_form', ProNewsAdm::admin_block_form('save',intval($_GET['id'])));
			} else {url_redirect(adminlink("&amp;mode=blk"));}
		}
		if ($func == 'save') {
			if ($_POST['num'] == '' || $_POST['num'] == '0') { $_POST['num'] = '5'; }
			if ($_POST['cat'] != '') {
				$cat = intval($_POST['cat']);
				if ($_POST['morecat'] != '') {
					$cat .= ','.implode(',', (preg_split('/(,\s?)/', trim($_POST['morecat']), 0, PREG_SPLIT_NO_EMPTY)));
				}
			} else {
				$cat = '';
			}
			if ($_POST['bid'] != '') {
				$update = $db->sql_query('UPDATE '.$prefix.'_pronews_blocks SET type="'.Fix_Quotes($_POST['type']).'", section="'.($_POST['section'] == 'ALL' ? 'ALL' : intval($_POST['section'])).'", num="'.intval($_POST['num']).'", category="'.$cat.'" WHERE bid="'.intval($_POST['bid']).'"');
				url_refresh(adminlink("&amp;mode=blk"));
				$msg = '<div align="center">'._PNBLOCK.' '._PNUPSUC.'<br /><a href="'.adminlink("&amp;mode=blk").'">'._PNGOBACK.'</a></div>';
			} else {
				$result = $db->sql_query("SELECT weight FROM ".$prefix."_blocks WHERE bposition='l' ORDER BY weight DESC");
				list($weight) = $db->sql_fetchrow($result);
				$weight++;
				$blktitle = ($_POST['title'] != '') ? Fix_Quotes($_POST['title']) : 'ProNews Block';
				$blkpos = 'l';
// echo 'tmpl='.$_POST['type'];
				if ($_POST['type'] == 'headlines' || $_POST['type'] == 'randomctr' || $_POST['type'] == 'latestctr' || $_POST['type'] == 'oldestctr') {
					$blkpos = 'c';
					$blkfile = 'block-ProNews_Center.php';
				} elseif ($_POST['type'] == 'theadlines' || $_POST['type'] == 'trandomctr' || $_POST['type'] == 'tlatestctr' || $_POST['type'] == 'toldestctr') {
					$blkpos = 'c';
					$blkfile = 'block-ProNews_Center.php';
				} elseif ($_POST['type'] == 'comments') {
					$blkpos = 'c';
					$blkfile = 'block-ProNews_Comments_Center.php';
				} elseif ($_POST['type'] == 'menu' || $_POST['type'] == 'menuwa') {
					$blkfile = 'block-ProNews_Menu.php';
				} elseif ($_POST['type'] == 'moderator') {
					$blkfile = 'block-ProNews_Moderators.php';
				} else {
					$blkfile = 'block-ProNews_Default.php';
				}
				$verstr = explode('.',$gblsettings['Version_Num']);
				if ($verstr[0] == '9' && $verstr[1] == '0') {
//					echo '9.0.6.1';
					$insert = $db->sql_query('INSERT INTO '.$prefix.'_blocks VALUES(NULL, "", "'.$blktitle.'", "", "", "'.$blkpos.'", "'.$weight.'", "0", "0", "0", "", "'.$blkfile.'", "2")');
				} else {
					if ($verstr[0] == '9' && $verstr[1] == '1') {
//						echo '9.1.x.x';
						$insert = $db->sql_query('INSERT INTO '.$prefix.'_blocks VALUES(NULL, "", "'.$blktitle.'", "", "", "'.$blkpos.'", "'.$weight.'", "0", "0", "0", "", "'.$blkfile.'", "2", "-1")');
					} else {
//						echo '9.2.x.x';
						$insert = $db->sql_query('INSERT INTO '.$prefix.'_blocks VALUES(NULL, "file", "'.$blktitle.'", "", "", "'.$blkpos.'", "'.$weight.'", "0", "0", "", "", "'.$blkfile.'", "2", "")');
					}
				}

				$newid = mysql_insert_id();
			   	$add = $db->sql_query('INSERT INTO '.$prefix.'_pronews_blocks VALUES("'.$newid.'", "'.Fix_Quotes($_POST['type']).'", "'.($_POST['section'] == 'ALL' ? 'ALL' : intval($_POST['section'])).'", "'.intval($_POST['num']).'", "'.($_POST['cat'] == '' ? '' : intval($_POST['cat'])).'")');
//				url_refresh(adminlink("&amp;mode=blk"));
				$msg = '<div align="center">'._PNBLOCK.' '._PNADDSUC.'<br /><br />'._PNBLKENABLE.'<br />'._PNWARN.': <i>'._PNBLOCKSWARN.'</i><br /><br /><a href="'.adminlink("blocks&amp;edit=$newid").'">'._BLOCKSADMIN.': '._EDITBLOCK.' ('.$blktitle.')</a></div>';
			}
			$cpgtpl->assign_block_vars('pn_msg', array(
				'S_MSG' => $msg
			));
		}
		if ($func == 'del') {
			if ((isset($_GET['id'])) && ($_GET['id'] != '')) {
				cpg_delete_msg(adminlink("&amp;mode=blk&amp;do=del"),_PNCONFIRMBLKDEL,'<input type="hidden" name="id" value="'.intval($_GET['id']).'" />');
			}
			elseif (isset($_POST['id'])) {
				if (isset($_POST['confirm'])) {
					$db->sql_query('DELETE FROM '.$prefix.'_pronews_blocks WHERE bid="'.intval($_POST['id']).'"');
					$db->sql_query('DELETE FROM '.$prefix.'_blocks WHERE bid="'.intval($_POST['id']).'"');
					url_refresh(adminlink("&amp;mode=blk"));
					$msg = '<div align="center">'._PNBLOCK.' '._PNDELSUC.'<br />
				 	 <a href="'.adminlink("&amp;mode=blk").'">'._PNGOBACK.'</a></div>';
					$cpgtpl->assign_block_vars('pn_msg', array(
						'S_MSG' => $msg
					));
				} else {
					url_redirect(adminlink("&amp;mode=blk"));
				}
                        }
		}
	}

	function admin_sec($func) {
		global $db, $prefix, $bgcolor2, $bgcolor3, $cpgtpl, $pnsettings;
// echo 'func = '.$func.' | $_POST[chng] = '.$_POST['chng'].' || ';
		if ($func == 'none') {
			$tempsec = ProNewsAdm::admin_sec_form();
			$result = $db->sql_query('SELECT * FROM '.$prefix.'_pronews_sections ORDER BY sequence');
			$rows = $db->sql_numrows($result);
			$list = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);
			if ((isset($list)) && ($list != '')) {
				$bgcolor = '';
				$tlist = '';
				foreach ($list as $row) {
					$bgcolor = ($bgcolor == '') ? ' style="bgcolor: '.$bgcolor3.'"' : '';
					if ($row['view'] == 0) {
						$view = _PNALL;
					} elseif ($row['view'] == 1) {
						$view = _PNUSERS;
					} elseif ($row['view'] == 2) {
						$view = _PNADMIN;
					} elseif ($row['view'] == 3) {
						$view = _PNANON;
					} elseif ($row['view'] > 3) {
						list($view) = $db->sql_ufetchrow("SELECT group_name FROM ".$prefix.'_bbgroups WHERE group_id='.($row['view']-3), SQL_NUM);
					}
					if ($row['post'] == 0) {
						$post = _PNALL;
					} elseif ($row['post'] == 1) {
						$post = _PNUSERS;
					} elseif ($row['post'] == 2) {
						$post = _PNADMIN;
					} elseif ($row['post'] == 3) {
						$post = _PNANON;
					} elseif ($row['post'] > 3) {
						list($post) = $db->sql_ufetchrow("SELECT group_name FROM ".$prefix.'_bbgroups WHERE group_id='.($row['post']-3), SQL_NUM);
					}
					if ($row['admin'] == 0) {
						$admin = _PNALL;
					} elseif ($row['admin'] == 1) {
						$admin = _PNUSERS;
					} elseif ($row['admin'] == 2) {
						$admin = _PNADMIN;
					} elseif ($row['admin'] == 3) {
						$admin = _PNANON;
					} elseif ($row['admin'] > 3) {
						list($admin) = $db->sql_ufetchrow("SELECT group_name FROM ".$prefix.'_bbgroups WHERE group_id='.($row['admin']-3), SQL_NUM);
					}
					if ($row['moderate'] == 2-3) {
						$mod = _PNADMIN;
					} elseif ($row['moderate'] > 3-3) {
						list($mod) = $db->sql_ufetchrow("SELECT group_name FROM ".$prefix.'_bbgroups WHERE group_id='.($row['moderate']), SQL_NUM);
					}
					if ($row['sequence'] > 2) {
						$seqchngup = '<a href="'.adminlink("&amp;mode=sec&amp;do=up&amp;id=".$row['id']).'"><img src="images/up.gif" alt="'._PNUP.'" title="'._PNUP.'" border="0" /></a>';
					} else {
						$seqchngup = '&nbsp;';
					}
					if (($row['sequence'] > 1 && $row['sequence'] < $rows) && ($row['sequence'] < (sizeof($list)-1))) {
						$seqchngdn = '<a href="'.adminlink("&amp;mode=sec&amp;do=down&amp;id=".$row['id']).'"><img src="images/down.gif" alt="'._PNDOWN.'" title="'._PNDOWN.'" border="0" /></a>';
					} else {
						$seqchngdn = '&nbsp;';
					}
					switch ($row['art_ord']) {
						case '1': $order = _PNDTASCA; break;
						case '2': $order = _PNDTDSCA; break;
						case '3': $order = _PNTTLASCA; break;
						case '4': $order = _PNTTLDSCA; break;
						case '5': $order = _PNRATASCA; break;
						case '6': $order = _PNRATDSCA; break;
						case '7': $order = _PNRDSASCA; break;
						case '8': $order = _PNRDSDSCA; break;
						case '9': $order = _PNSTDTASCA; break;
						case '10': $order = _PNSTDTDSCA; break;
						case '11': $order = _PNENDTASCA; break;
						case '12': $order = _PNENDTDSCA;
					}
					switch ($row['secdsplyby']) {
								case '0': $lby = ''; break;
								case '1': $lby = 'A'; break;
								case '2': $lby = 'C';
					}
					$template = explode('.', $row['template'], '-1');
					$tlist .= '<tr'.$bgcolor.'><td><br /><a href="'.getlink("&amp;mode=allarts&amp;sec=".$row['id']).'">'.$row['title'].'</a><br /><span class="pn_tinygrey">'.make_clickable(decode_bb_all($row['description'], 1, true)).'&nbsp;</span></td>';
					$tlist .= '<td align="center">'.$seqchngup.'<img src="images/pro_news/icons/clearpixel.gif" width="5" height="5" alt="" />'.$seqchngdn.'</td>';
					$tlist .= '<td align="center" class="pn_tinygrey">'.$post.'</td>';
					$tlist .= '<td align="center" class="pn_tiny">'.$view.'</td>';
					$tlist .= '<td align="center" class="pn_tinygrey">'.$admin.'</td>';
					$tlist .= '<td align="center" class="pn_tinygrey">'.$mod.'</td>';
					$tlist .= '<td class="pn_tiny">&nbsp;'.$lby.'</td>';
					$tlist .= ($row['art_ord'] != "0") ? '<td align="center" class="pn_tinygrey">'.$order.'</td>' : '<td>&nbsp;</td>';
					$tlist .= '<td class="pn_tiny">&nbsp;'.(sizeof($template) > 0?$template['0']:'').'</td>';
					$tlist .= ($row['in_home'] == "1") ? '<td align="center" style="font-weight:bold;">'._PNY.'</td>' : (($row['in_home'] == "2") ? '<td align="center" style="font-weight:bold;">'._PNX.'</td>' : '<td>&nbsp;</td>');
					$tlist .= ($row['forum_id'] == '0') ? '<td>&nbsp;</td>' : (($row['forum_module'] <= '1') ? '<td align="center" class="pn_tinygrey">'._PNCPGFORUM.'</td>' : '<td align="center" class="pn_tinygrey">'.$row['forumspro_name'].'</td>');
					$tlist .= ($row['id'] == '0') ? '<td>&nbsp;</td></tr>' : (($row['id'] == '1') ? '<td><a href="'.adminlink("&amp;mode=sec&amp;do=ed&amp;id=".$row['id']).'">'._PNAEDIT.'</a></td></tr>' : ('<td><a href="'.adminlink("&amp;mode=sec&amp;do=ed&amp;id=".$row['id']).'">'._PNAEDIT.'</a>/<a href="'.adminlink("&amp;mode=sec&amp;do=del&amp;id=".$row['id']).'">'._PNDEL.'</a></td></tr>'));
				}
			} else {
				echo '<div align="center"><strong>'._PNNOSSECS.'</strong></div>';
			}
			$cpgtpl->assign_block_vars('sec_form', $tempsec);
			$cpgtpl->assign_block_vars('sec_list', array(
			'G_STARTFORM' => open_form(adminlink(),'seclist','&nbsp;'._PNSECTIONS.'&nbsp;'),
			'G_ENDFORM' => close_form(),
			'G_BGCOLOR' => $bgcolor2,
			'L_TITLE' => _PNTITLE,
			'L_DESC' => _PNDESC,
			'L_ORDER' => _PNORDER,
			'L_LBY' => _PNOBY,
			'L_TEMPLATE' => _PNTEMPLATE,
			'L_SEQ' => '&nbsp;',
			'L_VIEW' => _PNVIS,
			'L_POST' => _PNPOST,
			'L_ADMIN' => _PNSECADMINC,
			'L_MOD' => _PNSECMOD,
			'L_HOME' => _PNHOME,
			'L_DISCUSS' => _PNDISC,
			'L_FUNCTION' => _PNFUNCTION,
			'T_LIST' => $tlist,
			'L_DEFAULTS' => _PNDEFTEMPLATE.': '.$pnsettings['template']
			));
		}
		if ($func == 'del') {
			if (isset($_GET['id']) && $_GET['id'] > '1') {
				cpg_delete_msg(adminlink("&amp;mode=sec&amp;do=del"),_PNCONFIRMSECDEL,'<input type="hidden" name="id" value="'.intval($_GET['id']).'" />');
			} elseif (isset($_POST['id']) && $_POST['id'] > '1') {
				if (isset($_POST['confirm'])) {
					$list = $db->sql_fetchrowset($db->sql_query('SELECT * FROM '.$prefix.'_pronews_cats WHERE sid="'.intval($_POST['id']).'"'));
					foreach ($list as $row) {$db->sql_uquery('UPDATE '.$prefix.'_pronews_cats SET sid="1" WHERE id="'.$row['id'].'"');}
					list($sequence) = $db->sql_ufetchrow('SELECT sequence FROM '.$prefix.'_pronews_sections WHERE id='.intval($_POST['id']),SQL_NUM,__FILE__,__LINE__);
					$db->sql_query('UPDATE '.$prefix.'_pronews_sections SET sequence=sequence-1 WHERE sequence>'.$sequence,false,__FILE__,__LINE__);
					$db->sql_query('DELETE FROM '.$prefix.'_pronews_sections WHERE id="'.intval($_POST['id']).'"');  // adjust sequence - layingback 061214
					url_refresh(adminlink("&amp;mode=sec"));
					$msg = '<div align="center">'._PNSECTION.' '._PNDELSUC.'<br />
				 	 <a href="'.adminlink("&amp;mode=sec").'">'._PNGOBACK.'</a></div>';
					$cpgtpl->assign_block_vars('pn_msg', array(
						'S_MSG' => $msg
					));
				} else {
					url_redirect(adminlink("&amp;mode=sec"));
				}
			} else {url_redirect(getlink());}
		}
		if ($func == 'up') {
			if (isset($_GET['id'])) {
				$id = intval($_GET['id']);
				list($sequence) = $db->sql_ufetchrow('SELECT sequence FROM '.$prefix.'_pronews_sections WHERE id='.$id,SQL_NUM,__FILE__,__LINE__);
				if ($sequence > 1) {
					$result = $db->sql_query('UPDATE '.$prefix.'_pronews_sections SET sequence=sequence+1 WHERE sequence='.($sequence - 1),false,__FILE__,__LINE__);
					$result = $db->sql_query('UPDATE '.$prefix.'_pronews_sections SET sequence=sequence-1 WHERE id='.$id,false,__FILE__,__LINE__);
				}
				url_redirect(adminlink("&amp;mode=sec#sec_tbl"));
			}
		}       // added sequence ordering (up & down) - layingback 061214
		if ($func == 'down') {
			if (isset($_GET['id'])) {
				$id = intval($_GET['id']);
				list($sequence) = $db->sql_ufetchrow('SELECT sequence FROM '.$prefix.'_pronews_sections WHERE id='.$id,SQL_NUM,__FILE__,__LINE__);
				list($maxseqnum) = $db->sql_ufetchrow('SELECT sequence FROM '.$prefix.'_pronews_sections ORDER BY sequence DESC LIMIT 0,1',SQL_NUM,__FILE__,__LINE__);
				if ($sequence < $maxseqnum) {
					$result = $db->sql_query('UPDATE '.$prefix.'_pronews_sections SET sequence=sequence-1 WHERE sequence='.($sequence + 1),false,__FILE__,__LINE__);
					$result = $db->sql_query('UPDATE '.$prefix.'_pronews_sections SET sequence=sequence+1 WHERE id='.$id,false,__FILE__,__LINE__);
				}
				url_redirect(adminlink("&amp;mode=sec#sec_tbl"));
			}
		}
		if ($func == 'ed') {
			if (isset($_GET['id']) && $_GET['id'] >= '1') {
				$id = intval($_GET['id']);
				$cpgtpl->assign_block_vars('sec_form', ProNewsAdm::admin_sec_form('save', $id));
			} else {url_redirect(getlink());}
		}
		if ($func == 'save') {
			if (isset($_POST['chng'])) {
				if (isset($_POST['id'])) { $id = intval($_POST['id']); }
				$cpgtpl->assign_block_vars('sec_form', ProNewsAdm::admin_sec_form('ed', $id));
			} else {
// echo ' POSTid='.$_POST['id'];
				$id = ((isset($_POST['id'])) && ($_POST['id'] != '')) ? intval($_POST['id']) : '';
				if ($id <> "0") {
// echo ' id='.$id;
					$title = Fix_Quotes($_POST['title'],1,1);
					$descrip = str_replace('&','&amp;',Fix_Quotes($_POST['descrip'],0,0));
					$view = intval($_POST['view']);
					$post = intval($_POST['post']);
					$admin = intval($_POST['admin']);
					$mod = intval($_POST['mod']-3);
					$art_ord = (isset($_POST['art_ord'])) ? $_POST['art_ord'] : '';
// echo ' $art_ord='.$art_ord;
					$forum_id = (isset($_POST['forum_id'])) ? $_POST['forum_id'] : '';
					$forum_module = (isset($_POST['forum_module'])) ? $_POST['forum_module'] : (($pnsettings['forum_module'] == '2') ? '2' : '0');
//					$forum_module = (isset($_POST['forum_module'])) ? $_POST['forum_module'] : '0';
// echo '<br />pnset[forum_module] ='.$pnsettings['forum_module'].' forum_module ='.$forum_module;
					$forumspro_name = (($pnsettings['forum_module'] == '2') ? 'fpro' : '');
					if (isset($_POST['forum_name'])) {
						if ($_POST['forum_name'] == '1') {
							$forumspro_name = '';
							$forum_module = '1';
						} else {
							$forumspro_name = $_POST['forum_name'];
							$forum_module = '2';
						}
					}
					//fishingfan additions start
//				if ($_POST['secheadlines'] == '' || $_POST['secheadlines'] == '0') { $_POST['secheadlines'] = '1'; }
						$secheadlines = intval($_POST['secheadlines']);
//				if ($_POST['sectrunc1head'] == '' || $_POST['sectrunc1head'] == '0') { $_POST['sectrunc1head'] = '500'; }
						$sectrunc1head = intval($_POST['sectrunc1head']);
//				if ($_POST['sectrunc1head'] == '' || $_POST['sectrunchead'] == '0') { $_POST['sectrunchead'] = '50'; }
						$sectrunchead = intval($_POST['sectrunchead']);
					//fishingfan additions end
					$template = (isset($_POST['template']) && ($_POST['template'] <> _PNDEFTEMPLATE)) ? $_POST['template'] : '';
					$secdsplyby = (isset($_POST['secdsplyby'])) ? $_POST['secdsplyby'] : '0';
// echo 'postdspby='.$_POST['secdsplyby'].' secdsplyby='.$secdsplyby;
					$usrfld0 = ($_POST['user_fld_0'] == ' ') ? ' ' : Fix_Quotes($_POST['user_fld_0'],1,0);
					$usrfld1 = ($_POST['user_fld_1'] == ' ') ? ' ' : Fix_Quotes($_POST['user_fld_1'],1,0);
					$usrfld2 = ($_POST['user_fld_2'] == ' ') ? ' ' : Fix_Quotes($_POST['user_fld_2'],1,0);
					$usrfld3 = ($_POST['user_fld_3'] == ' ') ? ' ' : Fix_Quotes($_POST['user_fld_3'],1,0);
					$usrfld4 = ($_POST['user_fld_4'] == ' ') ? ' ' : Fix_Quotes($_POST['user_fld_4'],1,0);
					$usrfld5 = ($_POST['user_fld_5'] == ' ') ? ' ' : Fix_Quotes($_POST['user_fld_5'],1,0);
					$usrfld6 = ($_POST['user_fld_6'] == ' ') ? ' ' : Fix_Quotes($_POST['user_fld_6'],1,0);
					$usrfld7 = ($_POST['user_fld_7'] == ' ') ? ' ' : Fix_Quotes($_POST['user_fld_7'],1,0);
					$usrfld8 = ($_POST['user_fld_8'] == ' ') ? ' ' : Fix_Quotes($_POST['user_fld_8'],1,0);
					$usrfld9 = ($_POST['user_fld_9'] == ' ') ? ' ' : Fix_Quotes($_POST['user_fld_9'],1,0);
					$keyusrfld = intval($_POST['key_usr_fld']);
					$usrfldttl = Fix_Quotes($_POST['user_fld_ttl'],1,0);
					$in_home = $_POST['in_home'];
// echo ' SQLid='.$id;
					if ($id == '') {
						list($maxseqnum) = $db->sql_ufetchrow('SELECT sequence FROM '.$prefix.'_pronews_sections ORDER BY sequence DESC LIMIT 0,1');
						$maxseqnum++;   // add sequence - layingback 061214
						//fishingfan add $secheadlines $sectrunc1head & $sectrunchead
						$db->sql_query('INSERT INTO '.$prefix.'_pronews_sections VALUES(NULL, "'.$title.'", "'.$descrip.'", "'.$view.'", "'.$admin.'", "'.$forum_id.'", "'.$maxseqnum.'", "'.$in_home.'", "'.$forum_module.'", "'.$forumspro_name.'", "'.$usrfld0.'", "'.$usrfld1.'", "'.$usrfld2.'", "'.$usrfld3.'", "'.$usrfld4.'", "'.$usrfld5.'", "'.$usrfld6.'", "'.$usrfld7.'", "'.$usrfld8.'", "'.$usrfld9.'", "'.$usrfldttl.'", "'.$template.'", "'.$art_ord.'", "'.$secheadlines.'", "'.$sectrunc1head.'", "'.$sectrunchead.'", "'.$post.'", "'.$secdsplyby.'", "'.$keyusrfld.'", "'.$mod.'")');
						$msg = '<div align="center">'._PNSECTION.' '._PNADDSUC.'<br /><a href="'.adminlink("&amp;mode=sec").'">'._PNGOBACK.'</a></div>';
					} else {
						//fishingfan add $secheadlines $sectrunc1head & $sectrunchead
						$db->sql_query('UPDATE '.$prefix.'_pronews_sections SET title="'.$title.'", description="'.$descrip.'", view="'.$view.'", admin="'.$admin.'", forum_id="'.$forum_id.'", in_home="'.$in_home.'", forum_module="'.$forum_module.'", forumspro_name="'.$forumspro_name.'", template="'.$template.'", usrfld0="'.$usrfld0.'", usrfld1="'.$usrfld1.'", usrfld2="'.$usrfld2.'", usrfld3="'.$usrfld3.'", usrfld4="'.$usrfld4.'", usrfld5="'.$usrfld5.'", usrfld6="'.$usrfld6.'", usrfld7="'.$usrfld7.'", usrfld8="'.$usrfld8.'", usrfld9="'.$usrfld9.'", usrfldttl="'.$usrfldttl.'", template="'.$template.'", art_ord="'.$art_ord.'", secheadlines="'.$secheadlines.'", sectrunc1head="'.$sectrunc1head.'", sectrunchead="'.$sectrunchead.'", post="'.$post.'", secdsplyby="'.$secdsplyby.'", keyusrfld="'.$keyusrfld.'", moderate="'.$mod.'" WHERE id='.$id);
						$msg = '<div align="center">'._PNSECTION.' '._PNUPSUC.'<br /><a href="'.adminlink("&amp;mode=sec").'">'._PNGOBACK.'</a></div>';
					}
					url_refresh(adminlink("&amp;mode=sec"));
					$cpgtpl->assign_block_vars('pn_msg', array(
						'S_MSG' => $msg
					));
				}
			}
		}
	}

	function admin_cat($func) {
		global $db, $prefix, $bgcolor1, $bgcolor2, $bgcolor3, $cpgtpl;
		if ($func == 'none') {
			$sql = 'SELECT c.*, s.title stitle, s.forum_id sforum_id, s.forum_module sforum_module, s.forumspro_name sforumspro_name FROM '.$prefix.'_pronews_cats as c ';
			$sql .= ' LEFT JOIN '.$prefix.'_pronews_sections as s ON c.sid=s.id ORDER BY s.sequence, c.sequence';
			$result = $db->sql_query($sql);
			$rows = $db->sql_numrows($result);
			$list = $db->sql_fetchrowset($result);
			if ((isset($list)) && ($list != '')) {
				$tempcat = ProNewsAdm::admin_cat_form();
				$bgcolor = '';
				$cpgtpl->assign_block_vars('cat_form', $tempcat);
				$cpgtpl->assign_block_vars('cat', array(
					'G_STARTFORM' => open_form(adminlink(),'catlist','&nbsp;'._PNCATS.'&nbsp;'),
					'G_ENDFORM' => close_form(),
					'S_COLOR' => $bgcolor2,
					'L_TITLE' => _PNTITLE,
					'L_DESC' => _PNDESC,
					'L_SECTION' => _PNSECTION,
					'L_CAT' => _PNICON,
					'L_FORUM' => _PNDISC,
					'L_SEQ' => _PNSEQ,
				));

				for ($i=0; $i < sizeof($list); $i++) {
					$bgcolor = ($bgcolor == '') ? ' style="bgcolor: '.$bgcolor3.'"' : '';
					if ($i < 2 || ($list[$i]['stitle'] != $list[$i-1]['stitle'])) {
						$seqchngup = '&nbsp;';
						$sectionbrk = ($list[$i]['sid'] >= 1) ? '1' : '0';
					} else {
						$seqchngup = '<a href="'.adminlink("&amp;mode=cat&amp;do=up&amp;id=".$list[$i]['id']).'"><img src="images/up.gif" alt="'._PNUP.'" title="'._PNUP.'" border="0" /></a>';
						$sectionbrk = '0';
					}
					if ($i == 0 || (($list[$i]['stitle'] != $list[$i + 1]['stitle']) || $list[$i + 1]['sequence'] == false)) {
						$seqchngdn = '&nbsp;';
					} else {
						$seqchngdn = '<a href="'.adminlink("&amp;mode=cat&amp;do=down&amp;id=".$list[$i]['id']).'"><img src="images/down.gif" alt="'._PNDOWN.'" title="'._PNDOWN.'" border="0" /></a>';
					}
					if ($list[$i]['forum_id'] == '-1') {
						$forum = _PNNOCOM;
					} elseif (($list[$i]['forum_id'] == '0' && $list[$i]['sforum_id'] == '0')) {
						$forum = '&nbsp;';
					} else {
						if ($list[$i]['forum_id'] == '0') {
							if ($list[$i]['sforum_module'] <= '1') {
								$forum = _PNCPGFORUM;
							} else {
								$forum = $list[$i]['sforumspro_name'];
							}
						} else {
							if ($list[$i]['forum_module'] <= '1') {
								$forum = _PNCPGFORUM;
							} else {
								$forum = $list[$i]['forumspro_name'];
							}
						}
					}
					$cpgtpl->assign_block_vars('cat.cat_list', array(
						'G_SECTIONBRK' => $sectionbrk,
						'S_ICON' => ($list[$i]['icon'] != '') ? $list[$i]['icon'] : '',
						'S_COLOR' => $bgcolor,
						'S_SECTION' => ($list[$i]['stitle'] != $list[$i-1]['stitle']) ? '<a href="'.getlink('&amp;sid='.$list[$i]['sid']).'">'.$list[$i]['stitle'].'</a>' : '&nbsp;',
						'S_DESC' => make_clickable(decode_bb_all($list[$i]['description'], 1, true)).'&nbsp;',
						'S_TITLE_LNK' => '<a href="'.getlink("&amp;cid=".$list[$i]['id']).'">'.$list[$i]['title'].'</a>',
						'S_TITLE' => $list[$i]['title'],
						'S_SEQUP' => $seqchngup,
						'S_SEQDN' => $seqchngdn,
						'S_FORUM' => $forum,
						'S_LINKS' => ($list[$i]['id'] <= '1') ? '&nbsp;' : '<a href="'.adminlink("&amp;mode=cat&amp;do=ed&amp;id=".$list[$i]['id']).'">'._PNAEDIT.'</a> / <a href="'.adminlink("&amp;mode=cat&amp;do=del&amp;id=".$list[$i]['id']).'">'._PNDEL.'</a>'
					));
				}
			} else {
				$msg = '<div align="center"><strong>'._PNNOCATS.'</strong></div>';
				$cpgtpl->assign_block_vars('pn_msg', array(
					'S_MSG' => $msg
				));
			}
		}
		if ($func == 'del') {
			if (isset($_GET['id'])) {
				cpg_delete_msg(adminlink("&amp;mode=cat&amp;do=del"),_PNCONFIRMCATDEL,'<input type="hidden" name="id" value="'.$_GET['id'].'" />');
			}
			elseif (isset($_POST['id'])) {
				if (isset($_POST['confirm'])) {
					$list = $db->sql_fetchrowset($db->sql_query('SELECT * FROM '.$prefix.'_pronews_articles WHERE catid="'.$_POST['id'].'"'));
					foreach ($list as $row) {$db->sql_uquery('UPDATE '.$prefix.'_pronews_articles SET catid="1" WHERE id="'.$row['id'].'"');}

					list($sequence, $sid) = $db->sql_ufetchrow('SELECT sequence, sid FROM '.$prefix.'_pronews_cats WHERE id='.$_POST['id'],SQL_NUM,__FILE__,__LINE__);
					$db->sql_query('UPDATE '.$prefix.'_pronews_cats SET sequence=sequence-1 WHERE sid='.$sid.' AND sequence>'.$sequence,false,__FILE__,__LINE__);
					$db->sql_query('DELETE FROM '.$prefix.'_pronews_cats WHERE id="'.$_POST['id'].'"');  // adjust sequence - layingback 061214
					url_refresh(adminlink("&amp;mode=cat"));
					$msg = '<div align="center">'._PNCAT.' '._PNDELSUC.'<br />
				 	 <a href="'.adminlink("&amp;mode=cat").'">'._PNGOBACK.'</a></div>';
					$cpgtpl->assign_block_vars('pn_msg', array(
						'S_MSG' => $msg
					));
				} else {
					url_redirect(adminlink("&amp;mode=cat"));
				}
			}
			else {url_redirect(getlink());}
		}

		if ($func == 'up') {
			if (isset($_GET['id'])) {
				$id = intval($_GET['id']);
				list($sequence, $sid) = $db->sql_ufetchrow('SELECT sequence, sid FROM '.$prefix.'_pronews_cats WHERE id='.$id,SQL_NUM,__FILE__,__LINE__);
				if ($sequence > 0) {
					$result = $db->sql_query('UPDATE '.$prefix.'_pronews_cats SET sequence=sequence+1 WHERE sid='.$sid.' AND sequence='.($sequence - 1),false,__FILE__,__LINE__);
					$result = $db->sql_query('UPDATE '.$prefix.'_pronews_cats SET sequence=sequence-1 WHERE sid='.$sid.' AND id='.$id,false,__FILE__,__LINE__);
				}
				url_redirect(adminlink("&amp;mode=cat#cat_tbl"));
			}
		}       // added sequence ordering (up & down) - layingback 061214

		if ($func == 'down') {
			if (isset($_GET['id'])) {
				$id = intval($_GET['id']);
				list($sequence, $sid) = $db->sql_ufetchrow('SELECT sequence, sid FROM '.$prefix.'_pronews_cats WHERE id='.$id,SQL_NUM,__FILE__,__LINE__);
				list($maxseqnum) = $db->sql_ufetchrow('SELECT sequence FROM '.$prefix.'_pronews_cats WHERE sid='.$sid.' ORDER BY sequence DESC LIMIT 0,1',SQL_NUM,__FILE__,__LINE__);
				if ($sequence < $maxseqnum) {
					$result = $db->sql_query('UPDATE '.$prefix.'_pronews_cats SET sequence=sequence-1 WHERE sid='.$sid.' AND sequence='.($sequence + 1),false,__FILE__,__LINE__);
					$result = $db->sql_query('UPDATE '.$prefix.'_pronews_cats SET sequence=sequence+1 WHERE sid='.$sid.' AND id='.$id,false,__FILE__,__LINE__);
				}
				url_redirect(adminlink("&amp;mode=cat#cat_tbl"));
			}
		}

		if ($func == 'ed') {
			$id = $_GET['id'];
			$cpgtpl->assign_block_vars('cat_form', ProNewsAdm::admin_cat_form('save', $id));
		}

		if ($func == 'save') {
			if (isset($_POST['chng'])) {
				if (isset($_POST['id'])) { $id = intval($_POST['id']); }
				$cpgtpl->assign_block_vars('cat_form', ProNewsAdm::admin_cat_form('ed', $id));
			} else {
				$id = ((isset($_POST['id'])) && ($_POST['id'] != '')) ? $_POST['id'] : '';
				$section = Fix_Quotes($_POST['section'],1,1);
				$title = Fix_Quotes($_POST['title'],1,1);
				$descrip = Fix_Quotes($_POST['descrip'],0,0);
				$icon = $_POST['icon'];
				$view = intval($_POST['view']);
				$admin = intval($_POST['admin']);

				$forum_id = (isset($_POST['forum_id'])) ? $_POST['forum_id'] : '';
				$forum_module = (isset($_POST['forum_module'])) ? $_POST['forum_module'] : '0';
				if (isset($_POST['forum_name'])) {
					if ($_POST['forum_name'] == '1') {
						$forumspro_name = '';
						$forum_module = '1';
					} else {
						$forumspro_name = $_POST['forum_name'];
						$forum_module = '2';
					}
				}

				if ($id == '') {
					list($maxseqnum) = $db->sql_ufetchrow('SELECT sequence FROM '.$prefix.'_pronews_cats WHERE sid='.$_POST['section'].' ORDER BY sequence DESC LIMIT 0,1',SQL_NUM);
					if ($maxseqnum != '') {$maxseqnum++;} else {$maxseqnum = '0';}   // add sequence - layingback 061219
					$db->sql_query('INSERT INTO '.$prefix.'_pronews_cats VALUES(NULL, "'.$section.'", "'.$title.'", "'.$descrip.'", "'.$icon.'", "'.$view.'", "'.$admin.'", "'.$maxseqnum.'", "'.$forum_id.'", "'.$forum_module.'", "'.$forumspro_name.'")');  // Modified by Masino Sinaga, June 24, 2009
					$msg = '<div align="center">'._PNCAT.' '._PNADDSUC.'<br /><a href="'.adminlink("&amp;mode=cat").'">'._PNGOBACK.'</a></div>';
				} else {
					list($maxseqnum) = $db->sql_ufetchrow('SELECT sequence FROM '.$prefix.'_pronews_cats WHERE sid='.$_POST['section'].' ORDER BY sequence DESC LIMIT 0,1',SQL_NUM,__FILE__,__LINE__);
					$maxseqnum++;   // add sequence - layingback 061219
					list($sequence, $sid) = $db->sql_ufetchrow('SELECT sequence, sid FROM '.$prefix.'_pronews_cats WHERE id='.$_POST['id'],SQL_NUM,__FILE__,__LINE__);
					if ($sid != $section) {
						$db->sql_query('UPDATE '.$prefix.'_pronews_cats SET sequence=sequence-1 WHERE sid='.$sid.' AND sequence>'.$sequence,false,__FILE__,__LINE__);
						$db->sql_query('UPDATE '.$prefix.'_pronews_cats SET sid="'.$section.'", title="'.$title.'", description="'.$descrip.'", icon="'.$icon.'", view="'.$view.'", admin="'.$admin.'", sequence="'.$maxseqnum.'", forum_id="'.$forum_id.'", forum_module="'.$forum_module.'", forumspro_name="'.$forumspro_name.'" WHERE id='.$id);
						$msg = '<div align="center">'._PNCAT.' '._PNUPSUC.'<br /><a href="'.adminlink("&amp;mode=cat").'">'._PNGOBACK.'</a></div>';
					} else {
						$db->sql_query('UPDATE '.$prefix.'_pronews_cats SET sid="'.$section.'", title="'.$title.'", description="'.$descrip.'", icon="'.$icon.'", view="'.$view.'", admin="'.$admin.'", forum_id="'.$forum_id.'", forum_module="'.$forum_module.'", forumspro_name="'.$forumspro_name.'" WHERE id='.$id);
						$msg = '<div align="center">'._PNCAT.' '._PNUPSUC.'<br /><a href="'.adminlink("&amp;mode=cat").'">'._PNGOBACK.'</a></div>';
					}
				}
				url_refresh(adminlink("&amp;mode=cat"));
				$cpgtpl->assign_block_vars('pn_msg', array(
					'S_MSG' => $msg
				));
			}
		}
	}

	function admin_img($image='',$realname='') {
		global $pnsettings, $db, $prefix;
		if (!isset($pnsettings)) {$pnsettings = $db->sql_fetchrow($db->sql_query('SELECT * FROM '.$prefix.'_pronews_settings'));}
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
				if ($need_thumb) {
					$thumb = $pnsettings['imgpath'].'/thumb_'.$newname;
				}
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
					$reimage = imagecreatetruecolor($sizemax[0], $sizemax[1]) or die('Cannot Initialize new GD image stream (101)');		// die by xfunsoles Mar 07
					imagefilledrectangle($reimage, 0, 0, $sizemax[0], $sizemax[1], imagecolorallocatealpha($reimage, 255, 255, 255, 127));
					imagealphablending($reimage, false);
					imagesavealpha($reimage, true);
					imagecopyresampled($reimage, $im, 0, 0, 0, 0, $sizemax[0], $sizemax[1], $size[0], $size[1]);
				}
				if ($need_thumb) {
					$small = imagecreatetruecolor($sizemin[0], $sizemin[1]) or die('Cannot Initialize new GD image stream (102)');		// die by xfunsoles Mar 07
					imagefilledrectangle($small, 0, 0, $sizemin[0], $sizemin[1], imagecolorallocatealpha($small, 255, 255, 255, 127));
					imagealphablending($small, false);
					imagesavealpha($small, true);
					imagecopyresampled($small, $im, 0, 0, 0, 0, $sizemin[0], $sizemin[1], $size[0], $size[1]);
				}
				$tiny = imagecreatetruecolor($tinysize[0], $tinysize[1]) or die('Cannot Initialize new GD image stream (103)');
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

	function admin_get_status($type, $id, $status, $return) {
		global $module_name;
		$stat = '&nbsp;';
		if (($id != '') && ($status != '')) {
			if (can_admin($module_name)) {
				$stat .= '<a href="'.adminlink("&amp;mode=list&amp;do=".$type."&amp;id=".$id."&amp;re=".$return."&amp;s=".$status).'">';
				if ($status == '1') {
					$stat .= '<img src="images/checked.gif" border="0" alt="" /></a>';
				} else {
					$stat .= '<img src="images/unchecked.gif" border="0" alt="" /></a>';
				}
			}
		}
		return $stat;
	}

	function admin_article($func,$id='') {
		global $db, $prefix, $userinfo, $cpgtpl, $pnsettings, $gblsettings, $CPG_SESS, $module_name, $multilingual, $BASEHREF;
		$id = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : '');
		$cpgtpl->set_filenames(array('body' => 'pronews/admin/admin.html'));
		$cpgtpl->display('body');
		if (($func == '') || ($func == 'none')) {
			ProNewsAdm::admin_article_form();
		}
		elseif ($func == 'new') {
			$category = ((isset($_POST['cat'])) && ($_POST['cat'] != '')) ? $_POST['cat'] : (isset($_GET['cat']) ? $_GET['cat'] : '');
			ProNewsAdm::admin_article_form('' ,$category);
		}
		elseif (($func == 'edit') && ($id != '')) {
			$sql = 'SELECT a.*, template, usrfld0, usrfld1, usrfld2, usrfld3, usrfld4, usrfld5, usrfld6, usrfld7, usrfld8, usrfld9';
			$sql .= ' FROM '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_sections as s, '.$prefix.'_pronews_articles as a';
			$sql .= ' WHERE c.sid=s.id AND a.id="'.$id.'"';
			$result = $db->sql_query($sql);
			$list = $db->sql_fetchrow($result);

			if ($list) {
				$result = $db->sql_query('SELECT dttime FROM '.$prefix.'_pronews_schedule WHERE id="'.$id.'" AND newstate="1"');
				$row2 =$db->sql_fetchrow($result, SQL_ASSOC);
				$list['sdttime'] = $row2['dttime'];
				$result = $db->sql_query('SELECT dttime FROM '.$prefix.'_pronews_schedule WHERE id="'.$id.'" AND newstate="0"');
				$row2 =$db->sql_fetchrow($result, SQL_ASSOC);
				$list['edttime'] = $row2['dttime'];
				$db->sql_freeresult($result);
				if ($list['cal_id']) {
					$res = $db->sql_query('SELECT * FROM '.$prefix.'_cpgnucalendar WHERE eid ='.$list['cal_id']);
					if ($db->sql_numrows($res) != 1) {
						$list['cal_id'] = '';						// clear if calendar entry has been deleted
						$db->sql_freeresult($res);
					} else {
						$crow = $db->sql_fetchrow($res);
						$db->sql_freeresult($res);
						$cl_s_date = $crow['date'];
						$cl_time = $crow['time'];
// echo '<br />$cl_s_date='.$cl_s_date.' $cl_time='.$cl_time;
						if ($cl_time == -1) {
							$list['clsdttime'] =  gmmktime(0, 0, 0, substr($cl_s_date, 4, 2 ), substr($cl_s_date, 6, 2 ), substr($cl_s_date, 0, 4 ));
						} else {
							$list['clsdttime'] =  gmmktime(floor($cl_time / 10000), ($cl_time / 100 ) % 100, 0, substr($cl_s_date, 4, 2 ), substr($cl_s_date, 6, 2 ), substr($cl_s_date, 0, 4 ));
							$list['clsdttime'] -= (L10NTime::in_dst($list['clsdttime'], $userinfo['user_dst']) * 3600);
						}
// echo '<br />$list[clsdttime]='.$list['clsdttime'];
						if ($crow['type'] == 'R') {
							$list['cledttime'] = '';
							$res = $db->sql_query('SELECT * FROM '.$prefix.'_cpgnucalendar_repeat WHERE eid ='.$list['cal_id']);
							if ($db->sql_numrows($res) == 1) {
								$crrow = $db->sql_fetchrow($res);
								$db->sql_freeresult($res);
								$cl_d_date = $crrow['end'];
								if ($crrow['type'] == 'daily' && $crrow['frequency'] == 1 && $crrow['days'] == 'nnnnnnn' && $crrow['end'] != '') {
									if ($cl_time == -1) {
										$list['cledttime'] =  gmmktime(0, 0, 0, substr($cl_d_date, 4, 2 ), substr($cl_d_date, 6, 2 ), substr($cl_d_date, 0, 4 ));
									} else {
										$list['cledttime'] =  gmmktime(floor($cl_time / 10000), ($cl_time / 100 ) % 100, 0, substr($cl_d_date, 4, 2 ), substr($cl_d_date, 6, 2 ), substr($cl_d_date, 0, 4 ));
										$list['cledttime'] -= (L10NTime::in_dst($list['cledttime'], $userinfo['user_dst']) * 3600);
									}
									if ($crow['duration'] != 0) {
										$list['cledttime'] = $list['cledttime'] + ($crow['duration'] * 60);
									}
								}
							}
						} else {
							if ($crow['duration'] != 0) {
//								$list['cledttime'] = gmmktime(floor($cl_time / 10000), ($cl_time / 100 ) % 100, 0, substr($cl_s_date, 4, 2 ), substr($cl_s_date, 6, 2 ), substr($cl_s_date, 0, 4 ));
								$list['cledttime'] = $list['clsdttime'] + ($crow['duration'] * 60);
							} else {
//								$list['cledttime'] = gmmktime(0, 0, 0, '', '', '');
								$list['cledttime'] = '';
							}
						}
					}
				}
// echo '<br />$list[cledttime]='.$list['cledttime'];
				ProNewsAdm::admin_article_form($list);
			} else {
				$db->sql_freeresult($result);
				url_redirect(adminlink("&amp;mode=act"));
			}
		} elseif ($func == 'save') {
			if (can_admin($module_name)) {
				$id = ((isset($_POST['id'])) && ($_POST['id'] != '')) ? $_POST['id'] : '';
				$title = Fix_Quotes($_POST['title'],1,1);
				$seod = Fix_Quotes($_POST['seod'],1,1);
				$content = Fix_Quotes($_POST['addtext'],0,!$pnsettings['admin_html']);
				$intro = Fix_Quotes($_POST['intro'],0,!$pnsettings['admin_html']);
				$alanguage = isset($_POST['alanguage']) ? Fix_Quotes($_POST['alanguage']) : '';
				$associated = (isset($_POST['assotop'])) ? implode(',', $_POST['assotop']) : ''; // Related Articles, modified by Masino Sinaga, June 22, 2009
				if (isset($_POST['inclnum'])) {
					$included = intval($_POST['inclnum']);
					$included = ($included + (intval($_POST['inclcatsec']) * 100)) * -1;
					$associated = ($associated == '') ? $included : $included.','.$associated;
				}
// echo $_POST['inclnum'].' '.$included.' '.$associated.' ';
				$display = intval($_POST['display']);
				$image_error = '';
				$image2_error = '';
				if ($_FILES['iname'] != '') {
					$image = ProNewsAdm::admin_img($_FILES['iname']['tmp_name'],$_FILES['iname']['name']);
						if ($image <> '' && !empty($_FILES['iname']['error'])) {
							switch ($_FILES['iname']['error']) {
								case '1':
									cpg_error('<br />'._PNMXUPPHPA.' '.substr(ini_get(upload_max_filesize),0,-1)._PNMXUPPHPB._PNMXUPPHPC.' (1 / imgae1)');
								case '2':
									cpg_error('<br />'.sprintf(ERR_IMGSIZE_TOO_LARGE,$_POST['MAX_FILE_SIZE']).' (2 / image1)');
								default :
									cpg_error('<br />'.NO_PIC_UPLOADED.' ('.$_FILES['iname']['error'].' / image1)');
							}
						}
					if ($image == '' && $_FILES['iname']['tmp_name'] != '') {
						$image_error = '1';
						$cpgtpl->assign_block_vars('pn_msg', array(
							'S_MSG' => '<b>'._PNDUPFILEA.' '.$_FILES['iname']['name'].' '._PNDUPFILEB.'</b>'
						));
					}
				} elseif (isset($_POST['delimage'])) {
					$image = '';
					if (file_exists($pnsettings['imgpath'].'/'.$_POST['image'])) {unlink($pnsettings['imgpath'].'/'.$_POST['image']);}
					if (file_exists($pnsettings['imgpath'].'/thumb_'.$_POST['image'])) {unlink($pnsettings['imgpath'].'/thumb_'.$_POST['image']);}
					if (file_exists($pnsettings['imgpath'].'/icon_'.$_POST['image'])) {unlink($pnsettings['imgpath'].'/icon_'.$_POST['image']);}
				} else {
					$image = Fix_Quotes($_POST['image']);
				}
				if ($_FILES['iname2'] != '') {
					$image2 = ProNewsAdm::admin_img($_FILES['iname2']['tmp_name'],$_FILES['iname2']['name'],'400','300');
						if ($image2 <> '' && !empty($_FILES['iname2']['error'])) {
							switch ($_FILES['iname2']['error']) {
								case '1':
									cpg_error('<br />'._PNMXUPPHPA.' '.substr(ini_get(upload_max_filesize),0,-1)._PNMXUPPHPB._PNMXUPPHPC.' (1 / image1)');
								case '2':
									cpg_error('<br />'._PNMXFLSZEA.' '.$_POST['MAX_FILE_SIZE']._PNMXFLSZEB.' (2 / image2)');
								default :
									cpg_error('<br />'.NO_PIC_UPLOADED.' ('.$_FILES['iname2']['error'].' / image2)');
							}
						}
					if ($image2 == '' && $_FILES['iname2']['tmp_name'] != '') {
						$image2_error = '1';
						$cpgtpl->assign_block_vars('pn_msg', array(
							'S_MSG' => '<b>'._PNDUPFILEA.' '.$_FILES['iname2']['name'].' '._PNDUPFILEB.'</span></b>'
						));
					}
				} elseif (isset($_POST['delimage2'])) {
					$image2 = '';
					if (file_exists($pnsettings['imgpath'].'/'.$_POST['image2'])) {unlink($pnsettings['imgpath'].'/'.$_POST['image2']);}
					if (file_exists($pnsettings['imgpath'].'/thumb_'.$_POST['image2'])) {unlink($pnsettings['imgpath'].'/thumb_'.$_POST['image2']);}
					if (file_exists($pnsettings['imgpath'].'/icon_'.$_POST['image2'])) {unlink($pnsettings['imgpath'].'/icon_'.$_POST['image2']);}
				} else {
					$image2 = Fix_Quotes($_POST['image2']);
				}
				$caption = Fix_Quotes($_POST['imgcap'],1,0);
				$caption2 = Fix_Quotes($_POST['imgcap2'],1,0);
				$comment = Fix_Quotes($_POST['comments'],1,0);

				if ($pnsettings['topic_lnk'] >= 1) {
					$df_topic = ($_POST['topic']) ? Fix_Quotes($_POST['topic'],1) : 0;
					if ($_POST['topic']) {
						$sql = 'SELECT t.topicimage, t.topictext FROM '.$prefix.'_topics as t WHERE t.topicid='.$df_topic;
						$tlist = $db->sql_fetchrow($db->sql_query($sql));
					}
				} else {
					$df_topic = 0;
				}

				$postby = $userinfo['username'];			// greenday2k
				$postby_show = "1";
				$post_time = gmtime();
				$show_cat = "1";
				$approved = "1";
				$viewable = "1";
				$display_order = Fix_Quotes($_POST['display_order']);
				$album_id = ($_POST['album_id']) ? intval($_POST['album_id']) : 0;
				$album_cnt = intval($_POST['album_cnt']);
				$album_seq = intval($_POST['album_seq']);
				$slide_show = intval($_POST['slide_show']);
				$gallery = intval($_POST['gallery']);
//				$topicid = $_POST['topicid'];  // Related Article, modified by Masino Sinaga, June 22, 2009
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
				$category = ($_POST['cat'] != '') ? intval($_POST['cat']) : 1;

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

				$sdttime = L10NTime::toGMT(gmmktime($_POST['shour'], $_POST['smin'], 0, $_POST['smonth'], $_POST['sday'], $_POST['syear']), $userinfo['user_dst'], $userinfo['user_timezone']);
				$edttime = L10NTime::toGMT(gmmktime($_POST['ehour'], $_POST['emin'], 0, $_POST['emonth'], $_POST['eday'], $_POST['eyear']), $userinfo['user_dst'], $userinfo['user_timezone']);

				$today = time();
				if ($id == '') {						// only test if new article, so not to disturb current setting
					$active = ($sdttime > $today) ? "0" : "1";
				}
// echo '$sdttime='.$sdttime.' $edttime='.$edttime.' $today='.$today.' time='.time();

				if (isset($_POST['submitart'])) {
// -- Submit mode
					if ($id == '') {
//						$db->sql_query('INSERT INTO '.$prefix.'_pronews_articles VALUES(NULL, "'.$category.'", "'.$title.'", "'.$intro.'", "'.$content.'", "'.$image.'", "'.$caption.'", "'.$comment.'", "'.$postby.'", "'.$postby_show.'", "'.$post_time.'", "'.$show_cat.'", "'.$active.'", "'.$approved.'", "'.$viewable.'", NULL, "'.$display_order.'", "'.$alanguage.'", "'.$album_id.'", "'.$album_cnt.'", "'.$album_seq.'", "'.$slide_gallery.'", "'.$image2.'", "'.$caption2.'", "'.$user_fld_0.'", "'.$user_fld_1.'", "'.$user_fld_2.'", "'.$user_fld_3.'", "'.$user_fld_4.'", "'.$user_fld_5.'", "'.$user_fld_6.'", "'.$user_fld_7.'", "'.$user_fld_8.'", "'.$user_fld_9.'", 0,0,0, "'.$df_topic.'", "")');

						// Duplicated article, modified by Masino Sinaga, June 22, 2009
//						$checkarticle = $db->sql_query('SELECT * FROM '.$prefix.'_pronews_articles WHERE title = "'.$title.'" AND intro = "'.$intro.'"');
						$checkarticlesql = 'SELECT * FROM '.$prefix.'_pronews_articles WHERE title = "'.$title.'" AND intro = "'.$intro.'"';
						$checkarticlesql .= ($multilingual ? ' AND (alanguage="'.$alanguage.'" OR alanguage="'.$gblsettings['language'].'")' : '');
						$checkarticle = $db->sql_query($checkarticlesql);
						$rowcheckarticle = $db->sql_numrows($checkarticle);
						if ($rowcheckarticle > 0) {
							$cpgtpl->assign_block_vars('newsnone', array('S_MSG' => _PNTITLE.': <b>'.$title.'</b><br /><br />'._PNALREADYEXISTS.'<br /><br />[ <a href="javascript:history.go(-1)">'._PNGOBACK2.'</a> ]&nbsp;&nbsp;[ <a href="'.getlink().'">'._GO.'</a> ]'));
							$cpgtpl->set_filenames(array('errbody' => 'pronews/article/'.$pnsettings['template']));
							$cpgtpl->display('errbody', false);
//							url_refresh(adminlink("&amp;mode=list&amp;do=sort&amp;cat=".$category));  // re-display ArticleList for currect Cat: layingback 061121
						} else {
						  $db->sql_query('INSERT INTO '.$prefix.'_pronews_articles VALUES(NULL, "'.$category.'", "'.$title.'", "'.$intro.'", "'.$content.'", "'.$image.'", "'.$caption.'", "'.$comment.'", "'.$postby.'", "'.$postby_show.'", "'.$post_time.'", "'.$show_cat.'", "'.$active.'", "'.$approved.'", "'.$viewable.'", NULL, "'.$display_order.'", "'.$alanguage.'", "'.$album_id.'", "'.$album_cnt.'", "'.$album_seq.'", "'.$slide_gallery.'", "'.$image2.'", "'.$caption2.'", "'.$user_fld_0.'", "'.$user_fld_1.'", "'.$user_fld_2.'", "'.$user_fld_3.'", "'.$user_fld_4.'", "'.$user_fld_5.'", "'.$user_fld_6.'", "'.$user_fld_7.'", "'.$user_fld_8.'", "'.$user_fld_9.'", 0,0,0, "'.$df_topic.'", "'.$cal_id.'", "'.$associated.'", "'.$display.'", "", "", "'.$seod.'")');
						// end Duplicated article, modified by Masino Sinaga, June 22, 2009

						$newid = mysql_insert_id();
						if ($sdttime > $today) {
							$db->sql_query('INSERT INTO '.$prefix.'_pronews_schedule VALUES ("'.$newid.'", "1", "'.$sdttime.'")');
						}
						if ($edttime > $today) {
							$db->sql_query('INSERT INTO '.$prefix.'_pronews_schedule VALUES ("'.$newid.'", "0", "'.$edttime.'")');
						}

						  if ($pnsettings['cal_module'] && $clsdttime != 0) {
							ProNewsAdm::create_cal($clsdttime, $cledttime, $newid, $title, $intro, $postby);
						}


						url_redirect(adminlink("&amp;mode=list&amp;do=sort&amp;cat=".$category));  // re-display ArticleList for currect Cat: layingback 061121
						  $msg = _PNARTICLE.'&nbsp;'._PNUPSUC.'<br />';

						  }  // Check whether news already exists or not, Related Articles, modified by Masino Sinaga, June 22, 2009

					} else {

						$db->sql_query('UPDATE '.$prefix.'_pronews_articles SET catid="'.$category.'", title="'.$title.'", seod="'.$seod.'", intro="'.$intro.'", content="'.$content.'", image="'.$image.'", caption="'.$caption.'", allow_comment="'.$comment.'", postby_show="'.$postby_show.'", show_cat="'.$show_cat.'", display_order="'.$display_order.'", alanguage="'.$alanguage.'", album_id="'.$album_id.'", album_cnt="'.$album_cnt.'", album_seq="'.$album_seq.'", slide_show="'.$slide_gallery.'", image2="'.$image2.'", caption2="'.$caption2.'", user_fld_0="'.$user_fld_0.'", user_fld_1="'.$user_fld_1.'", user_fld_2="'.$user_fld_2.'", user_fld_3="'.$user_fld_3.'", user_fld_4="'.$user_fld_4.'", user_fld_5="'.$user_fld_5.'", user_fld_6="'.$user_fld_6.'", user_fld_7="'.$user_fld_7.'", user_fld_8="'.$user_fld_8.'", user_fld_9="'.$user_fld_9.'", df_topic="'.$df_topic.'", cal_id="'.$cal_id.'", associated="'.$associated.'", display="'.$display.'", updtby="'.$postby.'", updttime="'.$post_time.'" WHERE id='.$id);  // Related article, modified by Masino Sinaga, June 22, 2009

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
// To automatically approve CPGNuCalendar entry on saving this article [ remove line below to remove this feature]
										if ($crow['approved'] == 0) {$crow['approved'] = formatDateTime(time(),'%Y%m%d');}
										$cal_content = $intro.' &nbsp; [i][color=grey]'._PNCLK2FLLW.'[/color][/i] [url='.getlink("&amp;aid=".$newid).'] '._PNORGNGART.'[/url].';
										$startday = mktime(0, 0, 0, ProNewsAdm::pndate('m', $clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']), ProNewsAdm::pndate('d', $clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']), ProNewsAdm::pndate('Y', $clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']));
// echo '<br />$startday='.$startday;
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
// echo '<br />$endday='.$endday;
										$cal_rpt = ($endday > $startday) ? 'R' : 'E';
/*  change for functions.php 						$cal_appvd = (true && true) ? formatDateTime(time(),'%Y%m%d') : ''; 		*/
										$clstime = ($clstime == -1) ? -1 : formatDateTime($clsdttime,'%H%M').'00';
// echo '<br />$cal_durtn='.$cal_durtn.' $cal_rpt'.$cal_rpt;

										$db->sql_query('UPDATE '.$prefix.'_cpgnucalendar SET date="'.formatDateTime($clsdttime,'%Y%m%d').'", time="'.formatDateTime($clsdttime,'%H%M').'00'.'", mod_date="'.formatDateTime(time(),'%Y%m%d').'", mod_time="'.formatDateTime(time(),'%H%M%S').'", duration="'.$cal_durtn.'", type="'.$cal_rpt.'", name="'.$title.'", description="'.$cal_content.'", approved="'.$crow['approved'].'" WHERE eid='.$cal_id);
										if ($cal_rpt == 'R') {
											$db->sql_query('INSERT INTO '.$prefix.'_cpgnucalendar_repeat VALUES ("'.$cal_id.'", "daily", "'.formatDateTime($cledttime,'%Y%m%d').'", "1", "nnnnnnn") ON DUPLICATE KEY UPDATE end="'.formatDateTime($cledttime,'%Y%m%d').'"');
										} elseif ($crow['type'] == 'R') {
											$db->sql_query('DELETE FROM '.$prefix.'_cpgnucalendar_repeat WHERE eid='.$cal_id, true);		// ignore error if already deleted
										}
									}
								}
							} elseif ($clsdttime != 0) {
								ProNewsAdm::create_cal($clsdttime, $cledttime, $id, $title, $intro, $postby);
							}
						}

// echo '$sdttime='.$sdttime.' $edttime='.$edttime.' $today='.$today;
						if ($sdttime > $today) {
//						if (!$db->sql_query('UPDATE '.$prefix.'_pronews_schedule SET dttime="'.$sdttime.'" WHERE id='.$id.' AND newstate="1"', true) || !$db->sql_affectedrows()) {
//							$db->sql_query('INSERT INTO '.$prefix.'_pronews_schedule VALUES ("'.$id.'", "1", "'.$sdttime.'")');
//						}
							$db->sql_query('INSERT INTO '.$prefix.'_pronews_schedule VALUES ("'.$id.'", "1", "'.$sdttime.'") ON DUPLICATE KEY UPDATE dttime="'.$sdttime.'"');
						}
						if ($edttime > $today) {
//						if (!$db->sql_query('UPDATE '.$prefix.'_pronews_schedule SET dttime="'.$edttime.'" WHERE id='.$id.' AND newstate="0"', true) || !$db->sql_affectedrows()) {
//							$db->sql_query('INSERT INTO '.$prefix.'_pronews_schedule VALUES ("'.$id.'", "0", "'.$edttime.'")');
//						}
							$db->sql_query('INSERT INTO '.$prefix.'_pronews_schedule VALUES ("'.$id.'", "0", "'.$edttime.'") ON DUPLICATE KEY UPDATE dttime="'.$edttime.'"');
						}
						url_redirect(adminlink("&amp;mode=list&amp;do=sort&amp;cat=".$category));  // re-display ArticleList for currect Cat: layingback 061121
						$msg = _PNARTICLE.'&nbsp;'._PNUPSUC.'<br />';
					}
					$cpgtpl->assign_block_vars('pn_msg', array(
						'S_MSG' => $msg
						));
// -- Preview mode
				} else {
//				if (isset($_POST['assotop'])) {
//					$assotop = $_POST['assotop'];
//					$associated = implode(',', $_POST['assotop']); // Related Articles, modified by Masino Sinaga, June 22, 2009
//				} else {
//					$assotop = '';
//				}
					$album_order = array(0=>'',1=>'title',2=>'title',3=>'filename',4=>'filename',5=>'ctime',6=>'ctime',7=>'pic_rating',8=>'pic_rating');
					$sql = 'SELECT s.title stitle, c.title ctitle, s.id sid, s.forum_id sforum_id, s.description sdescription, c.forum_id cforum_id, c.description cdescription, icon, template, usrfld0, usrfld1, usrfld2, usrfld3, usrfld4, usrfld5, usrfld6, usrfld7, usrfld8, usrfld9, usrfldttl';
					$sql .= ' FROM '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_sections as s';
					$sql .= ' WHERE c.sid=s.id AND c.id="'.$category.'"';
					$result = $db->sql_query($sql);
					$list = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);
					$msg = _PNSUBPREVIEW;
					$msg2 = _PNREVIEW.'<br />'._PNARTPREVIEW.' --- '._PNDEMOLINKS;
					$cpgtpl->assign_block_vars('preview_article', array(
						'S_HEADING' => $msg,
						'S_MSG' => $msg2
					));
					$title = ProNewsAdm::cleanse($title);
					$seod = ProNewsAdm::cleanse($seod);
					$intro = ProNewsAdm::cleanse($intro);
					$content = ProNewsAdm::cleanse($content);
					$caption = ProNewsAdm::cleanse($caption);
					$caption2 = ProNewsAdm::cleanse($caption2);
					$user_fld_0 = ProNewsAdm::cleanse($user_fld_0);
					$user_fld_1 = ProNewsAdm::cleanse($user_fld_1);
					$user_fld_2 = ProNewsAdm::cleanse($user_fld_2);
					$user_fld_3 = ProNewsAdm::cleanse($user_fld_3);
					$user_fld_4 = ProNewsAdm::cleanse($user_fld_4);
					$user_fld_5 = ProNewsAdm::cleanse($user_fld_5);
					$user_fld_6 = ProNewsAdm::cleanse($user_fld_6);
					$user_fld_7 = ProNewsAdm::cleanse($user_fld_7);
					$user_fld_8 = ProNewsAdm::cleanse($user_fld_8);
					$user_fld_9 = ProNewsAdm::cleanse($user_fld_9);
					$row = array('stitle'=>$list['stitle'], 'ctitle'=>$list['ctitle'], 'sdesc'=>$list['sdescription'], 'cdesc'=>$list['cdescription'], 'sforum_id'=>$list['sforum_id'], 'cforum_id'=>$list['cforum_id'], 'icon'=>$list['icon'], 'id'=>$id, 'catid'=>$category, 'sid'=>$list['sid'], 'title'=>$title, 'seod'=>$seod, 'intro'=>$intro, 'content'=>$content, 'image'=>$image, 'caption'=>$caption, 'allow_comment'=>$comment, 'postby'=>$postby, 'postby_show'=>$postby_show, 'posttime'=>$post_time, 'show_cat'=>$show_cat, 'active'=>$active, 'approved'=>$approved, 'viewable'=>$viewable, 'display_order'=>$display_order, 'alanguage'=>$alanguage, 'album_id'=>$album_id, 'album_cnt'=>$album_cnt, 'album_seq'=>$album_seq, 'slide_show'=>$slide_gallery, 'image2'=>$image2, 'caption2'=>$caption2, 'user_fld_0'=>$user_fld_0, 'user_fld_1'=>$user_fld_1, 'user_fld_2'=>$user_fld_2, 'user_fld_3'=>$user_fld_3, 'user_fld_4'=>$user_fld_4, 'user_fld_5'=>$user_fld_5, 'user_fld_6'=>$user_fld_6, 'user_fld_7'=>$user_fld_7, 'user_fld_8'=>$user_fld_8, 'user_fld_9'=>$user_fld_9, 'usrfld0'=>$list['usrfld0'], 'usrfld1'=>$list['usrfld1'], 'usrfld2'=>$list['usrfld2'], 'usrfld3'=>$list['usrfld3'], 'usrfld4'=>$list['usrfld4'], 'usrfld5'=>$list['usrfld5'], 'usrfld6'=>$list['usrfld6'], 'usrfld7'=>$list['usrfld7'], 'usrfld8'=>$list['usrfld8'], 'usrfld9'=>$list['usrfld9'], 'usrfldttl'=>$list['usrfldttl'], 'df_topic'=>$df_topic, 'topictext'=>$tlist['topictext'], 'topicimage'=>$tlist['topicimage'], 'template'=>$list['template'], 'clsdttime'=>$clsdttime, 'cledttime'=>$cledttime, 'sdttime'=>$sdttime, 'edttime'=>$edttime, 'associated'=>$associated, 'display'=>$display );
//print_r($row);
					$target = 'pn'.uniqid(rand());
					if ($image_error) {
						$display_image = '<img class="pn_thumb" src="themes/default/images/'.$module_name.'/noimage.gif" />';
					} else {
						$display_image = ProNewsAdm::admin_displayimage($image,$target);
					}
					if ($image2_error) {
						$display2_image = '<img class="pn_thumb" src="themes/default/images/'.$module_name.'/noimage.gif" />';
					} else {
						$display2_image = ProNewsAdm::admin_displayimage($image2,$target);
					}
					$numpics = '0';
					$lpic = '';
					$maxsizeX = '200';
					$maxsizeY = '200';
					if (($row['album_id'] != '') && ($row['album_cnt'] > '0')) {
						$sql = 'SELECT pid pid, filepath, filename, pwidth, pheight, title, caption FROM '.$prefix.'_cpg_pictures';
//						$sql = 'SELECT pid pid, filepath, filename, pwidth, pheight, title, caption, remote_url FROM '.$prefix.'_cpg_pictures';  // lb - use after installing my coppermine remote CPG hack
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
								$talbum[$key] = ($pic['title'] != '') ? trim($pic['title']) : '&nbsp;';				// trim cos cpg adds trailing space!
								$calbum[$key] = ($pic['caption'] != '') ? $pic['caption'] : '';
								$palbum[$key] = '<a href="'.str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'" target="'.$target.'"
 onclick="PN_openBrWindow(\''.$BASEHREF.str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;"><img class="pn_image" src="'.$thumb.'" alt="'.$pic['title'].'" /></a>';
								$qalbum[$key] = str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename'])));
								$ralbum[$key] = str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'" target="'.$target.'"
 onclick="PN_openBrWindow(\''.$BASEHREF.str_replace("%2F", "/", str_replace("%3A", ":", rawurlencode($fullsizepath.$pic['filename']))).'\',\''.$target.'\',\'resizable=yes,scrollbars=no,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;';
								$valbum[$key] = $thumb;
								$ualbum[$key] = '<img class="pn_image" src="'.str_replace("%2F", "/", rawurlencode($pic['filepath'].'normal_'.$pic['filename'])).'" alt="" />';
								$galbum[$key] = '1';
								$lpic = $pic['pid'];		//track pid of last pic shown
								$numpics++;
							}
						}
					}
					$openLink = '<script type="text/javascript">
<!-- Script courtesy of http://www.web-source.net - Your Guide to Professional Web Site Design and Development
function load() {var load = window.open("'.getlink('Pro_News&amp;mode=slide&id='.$row['id'].'&album='.$row['album_id'].'&pid='.$lpic.'&slideshow=5000","","scrollbars=no,menubar=no,height='.($maxsizeY + 72).',width='.($maxsizeX + 32).',resizable=yes,toolbar=no,location=no,status=no',false,true).'");}
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

// echo '<br> 1assoc=';print_r($row['associated']);
// echo '<br> 1assoc='.$row['associated'];
//				if (substr($row['associated'], -1) == '-') {
//					$row['associated'] = substr($row['associated'], 0, -1);
//				}
//				$row['associated'] = ereg_replace('-', ',', $row['associated']);
					$assoc = '';
					$assoclst = '';
					if ($row['associated'] != '') {
// echo ' assoc='.$row['associated'].' tok='.strtok($row['associated'],',');
						$inclcode = strtok($row['associated'], ',');
//						$row['associated'] = strtok('');			// reset to remainder of associated
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
								$gettitle = $rowa['title'];
								if ($title != $gettitle) {
									$title = $gettitle;
								}
								$assoclst .= '<div class="pn_relart"><a href="'.getlink("Pro_News&amp;aid=".$rowa['id']).'" title="'.$datestory.'">'.$rowa['title'].'</a> <span class="pn_relartdate">'.$datestory.'</span></div>';
							}
							$db->sql_freeresult($result);
						}
						if ($row['associated'] != '') {		// check for individual related articles
							$result = $db->sql_query('SELECT id, title, posttime, postby FROM '.$prefix.'_pronews_articles WHERE id IN ('.$row['associated'].') AND approved="1" AND active="1"');
							while ($rowa = $db->sql_fetchrow($result)) {
								$datestory = formatDateTime($rowa['posttime'], _DATESTRING);
								$gettitle = $rowa['title'];
								if ($title != $gettitle) {
									$title = $gettitle;
								}
								$assoc .= '<div class="pn_relart"><a href="'.getlink("Pro_News&amp;aid=".$rowa['id']."&amp;title=".$title).'" title="'.$datestory.'">'.$rowa['title'].'</a> <span class="pn_relartdate">'.$datestory.'</span></div>';
							}
							$db->sql_freeresult($result);
						}
					}

//					$introtext = decode_bb_all($row['intro'], 1, true);
//					$combotext = $introtext.'<br /><br />'.decode_bb_all($row['content'], 1, true);
					$cpgtpl->assign_block_vars('newsarticle', array(
						'G_PREVIEW' => '1',
						'S_SECBRK' => $row['stitle'],
						'S_CATBRK' => $row['ctitle'],
						'U_CATDESC' => make_clickable(decode_bb_all($row['cdesc'], 1, true)),
						'U_SECDESC' => make_clickable(decode_bb_all($row['sdesc'], 1, true)),
						'S_INTRO' => make_clickable(decode_bb_all($row['intro'], 1, true)),
						'S_CONTENT' => make_clickable(decode_bb_all($row['content'], 2, true)),
						'S_ICON' => ($row['icon'] != '') ? $row['icon'] : '',
						'T_ICON' => $row['ctitle'],
						'S_TITLE' => $row['title'],
						'S_SEOD' => $row['seod'],
						'L_POSTBY' => _PNPOSTBY,
						'S_POSTBY' => $row['postby'],
						'T_POSTBY' => getlink("Your_Account&amp;profile=".$row['postby']),
						'S_POSTTIME' => ProNewsAdm::create_date(false, $row['posttime']),
						'L_POSTON' => _PNPOSTON,
						'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
						'S_CATEGORY' => $row['catid'],
						'S_IMAGE' => $display_image,
						'T_CAP' => $row['caption'],
						'S_IMGIMAGE' => $pnsettings['imgpath'].'/thumb_'.$row['image'],
						'S_IMAGE2' => $display2_image,
						'T_CAP2' => $row['caption2'],
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
						'G_RATE' => '0',
						'G_SCORE' => '0',
						'G_SHOW_READS' => $pnsettings['show_reads'],
						'S_READS' => _PNREADS,
						'T_READS' => '0',
						'G_MOREPICS' => ($pnsettings['per_gllry'] != 0 && $numpics < $row['album_cnt'] && $row['album_id'] && $row['album_id'] != '0' && $row['slide_show'] > '1') ? '1' : '0',
						'T_MOREPICS' => _PNMOREPICS,
						'U_MOREPICS' => ($row['id'] != '') ? getlink("&amp;mode=gllry&amp;id=".$row['id']."&amp;npic=".$row['album_cnt']) : 'javascript:void()',
						'G_SOCIALNET' => $pnsettings['soc_net'],
						'U_SOCNETLINK' => urlencode($BASEHREF.getlink("&amp;aid=".$row['id'])),
						'S_SOCTITLE' => urlencode($row['title']),
						'L_ID' => _PNREFNO,
						'S_ID' => ($id) ? $id : '???',
						'T_DFTOPIC' => $row['topictext'],
						'U_DFTOPIC' => (file_exists("themes/$CPG_SESS[theme]/images/topics/".$row['topicimage']) ? "themes/$CPG_SESS[theme]/" : '').'images/topics/'.$row['topicimage'],
						'L_ASSOCARTICLE' => _PNLSTLNKS,
						'L_ASSOCARTICLES' => $assoclst,
						'S_ASSOCARTICLE' => _PNRELATEDART,		// Related Articles, modified by Masino Sinaga, June 22, 2009
						'S_ASSOCARTICLES' => $assoc,  			// Related article, modified by Masino Sinaga, June 22, 2009
						'L_DISCUSS' => ($row['topic_id'] ? _PNDISCUSSION :_PNDISCUSSNEW),
						'U_DISCUSS' => 'javascript:void()',
						'S_DISCUSS' => _PNDISCUSS,
//						'G_DISCUSS' => (($row['allow_comment'] == '1') && ($pnsettings['comments'] == '1')) ? '1' : '',
						'G_DISCUSS' => (!($row['sforum_id'] == '0' && $row['cforum_id'] == '0') && $row['cforum_id'] != -1 && $row['allow_comment'] == '1' && $pnsettings['comments'] == '1') ? '1' : '',
						'G_PLINK' => $pnsettings['permalink'],
						'S_PLINK' => _PNLINK,
						'T_PLINK' => 'javascript:void()',
						'G_SENDPRINT' => '1',
						'G_EMAILF' => ($pnsettings['emailf'] && is_user()) ? '1' : '',
						'S_SENDART' => _PNSENDART,
						'U_SENDART' => getlink("&amp;mode=send&amp;id=".$row['id']),
						'G_PRINTF' => ($pnsettings['printf']) ? '1' : '',
						'S_PRINTART' => _PNPRINTART,
						'U_PRINTART' => getlink("&amp;mode=prnt&amp;id=".$row['id']),
						'U_CANADMIN' => '',
						'S_CANADMIN' => '',
						'T_CANADMIN' => '',
						'G_CANADMIN' => ''
					));
					$tpl = ($row['template'] != '') ? $row['template'] : $pnsettings['template'];
					$cpgtpl->set_filenames(array('body' => 'pronews/article/'.$tpl), false);
					$cpgtpl->display('body', false);
					ProNewsAdm::admin_article_form($row);
				}
			}
		}
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

	function admin_cat_form($func='', $id='') {
		global $db, $prefix, $cpgtpl, $forum_module, $pnsettings;
		if (($id != '') && ($func == 'save')) {
			$submit = _PNSAVECAT;$dosave='save&amp;id='.$id;
			$btitle = '&nbsp;'._PNAEDIT.'&nbsp;'._PNCAT.'&nbsp;';
			// Forum ID now can be choosen from Category Panel Admin; modified by Masino Sinaga, June 22, 2009
			list($cid, $sid, $title, $description, $icon, $view, $admin, $sequence, $forum_id, $forum_module, $forumspro_name) = $db->sql_fetchrow($db->sql_query('SELECT * FROM '.$prefix.'_pronews_cats WHERE id='.$id));  // fixed order of list - layingback 061129
		} else {
			$title='';$description='';$icon='';$view='';$admin='';$forum_id=''; $forum_module='1'; $forumspro_name='';
			$submit=_PNADDCAT;$dosave='save';
			$btitle = '&nbsp;'._PNADD.'&nbsp;'._PNCAT.'&nbsp;';
			$sid = '0';
		}

// echo ' here';
		// Forum ID now can be choosen from Category; modified by Masino Sinaga, June 22, 2009
		$forum_name = ($pnsettings['forum_module'] == '2') ? 'fpro' : '1';
		$forum_module = ($forum_name == '1') ? '1' : '2';
		$forumspro_name = ($forum_name == '1') ? '' : $forum_name;
		if (isset($_POST['chng'])) {
			if (isset($_POST['forum_id'])) { $forum_id = intval($_POST['forum_id']); }
			if (isset($_POST['forum_name'])) {
				$forum_name = Fix_Quotes($_POST['forum_name']);
				$forum_module = ($forum_name == '1') ? '1' : '2';
				$forumspro_name = ($forum_name == '1') ? '' : $forum_name;
				$forum_id = '';
			}
			if (isset($_POST['title'])) { $title = Fix_Quotes($_POST['title'],1); }
			if (isset($_POST['descrip'])) { $description =  Fix_Quotes($_POST['descrip'],1); }
			if (isset($_POST['section'])) { $sid = Fix_Quotes($_POST['section'],1); }
			if (isset($_POST['icon'])) { $icon =  Fix_Quotes($_POST['icon'],1); }
		}

		$handle = opendir('images/pro_news/icons');
		$tlist = array();
		$tlist[] = '&nbsp;';   // added null entry for no selection - layingback 061129
		while ($file = readdir($handle)) {
			if (ereg("^([a-zA-Z0-9_\-]+)([.]{1})([a-zA-Z0-9_\-]{3})$",$file)) {
			$tlist[] = $file;
			}
		}
		closedir($handle);
		sort($tlist);
		$icons = select_option('icon', $icon, $tlist);
		$sql = 'SELECT id, title FROM '.$prefix.'_pronews_sections WHERE id>"0" ORDER BY sequence';
		$list = $db->sql_fetchrowset($db->sql_query($sql));
		$sections = '<select name="section">';
		foreach ($list as $row) {$selected = ($sid == $row['id']) ? ' selected="selected"' : '';$sections .= '<option value="'.$row['id'].'"'.$selected.'>'.$row['title'].'</option>';}
		$sections .= '</select>';

		$tempfname = ($pnsettings['forum_module'] == '2') ? select_box('forum_name', 'ForumsPro', array('fpro'=>'ForumsPro')) : select_box('forum_name', 'Forum', array('1'=>'Forum (cpg-BB)'));

// echo ' pnset='.$pnsettings['forum_module'].' fn='.$forum_name.' fm='.$forum_module.' fpname='.$forumspro_name;
 		$tempcat = array(
			'G_STARTFORM' => open_form(adminlink("&amp;mode=cat&amp;do=".$dosave),'catform',$btitle),
			'G_ENDFORM' => close_form(),
			'S_TITLE' => _PNTITLE,
			'T_TITLE' => '<input type="text" name="title" size="25" value="'.$title.'" />',
			'S_DESC' => _PNDESC,
			'T_DESC' => '<textarea cols="54" rows="3" name="descrip" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);">'.$description.'</textarea>',
			'S_SEC' => _PNSECTION,
			'T_SEC' => $sections,
			'L_ADVANCED' => _PNADVANCED,

			// Forum ID from Category; modified by Masino Sinaga, June 22, 2009
			'T_CHANGE' => '<input type="submit" name="chng" value="'._PNCHANGE.'" />',
			'G_FPRONAME' => ($pnsettings['forum_module'] == '3' && $pnsettings['forum_per-cat']) ? '1' : '0',
			'S_FPRONAME' => _PNFPRONAME,
			'T_FPRONAME' => ($pnsettings['forum_module'] == '3') ? ProNewsAdm::admin_activefpros($forumspro_name) : $tempfname,
			'S_FORUM' => _PNFORUM,
			'T_FORUM' => ($forum_module <= '1' || $forum_module == '') ? ProNewsAdm::admin_forumlist($forum_id, 1, _PNSECFRMDFLT) : ProNewsAdm::admin_forumsprolist($forumspro_name, $forum_id, 1, _PNSECFRMDFLT),
			'U_FORUM' => ' '._PNCURSECDFLT,

			'S_ICON' => _PNICON,
			'T_ICON' => $icons,
			'G_ID' => ($id != '') ? '<input type="hidden" name="id" value="'.$id.'" />' : '',
			'G_SAVE' => '<input type="submit" name="submitcat" value="'.$submit.'" />'
		);
		return $tempcat;
	}

	function admin_cfg($func) {
		global $prefix, $db, $cpgtpl, $pnsettings, $gblsettings, $multilingual, $board_config;

		if (!Cache::array_load('board_config', 'Forums', true)) {
			$result = $db->sql_query('SELECT * FROM '.$prefix.'_bbconfig');
			while ($row = $db->sql_fetchrow($result)) {
				$board_config[$row['config_name']] =  str_replace("'", "\'",$row['config_value']);
				$new[$config_name] = ( isset($_POST[$config_name]) ) ? Fix_Quotes($_POST[$config_name],1) : Fix_Quotes($board_config[$config_name],1);
				if ($config_name == 'smilies_path') {
					$new['smilies_path'] = 'images/smiles';
				}
			}
			Cache::array_save('board_config', 'Forums');
		}
		if (($func == '') || ($func == 'none')) {
	 		$cpgtpl->assign_block_vars('cfg_form', array(
				'G_STARTFORM' => open_form(adminlink("&amp;mode=cfg&amp;do=save"),'cfgform','&nbsp;'._PNSAVECONFIG.'&nbsp;'),
				'G_ENDFORM' => close_form(),
				'S_PERPAGE' => _PNPERPAGE,
				'L_DSPLYSTG' => _PNDISSET,
				'T_PERPAGE' => '<input type="text" name="per_page" size="5" value="'.$pnsettings['per_page'].'" />',
				'S_USROVRPP' => _PNUSROVRPPG,
				'T_USROVRPP' => yesno_option('usr_ovr_ppage',$pnsettings['usr_ovr_ppage']),
				'L_USROVRPP' => _PNUSROVRLBL,
				'L_ADVANCED' => _PNADVANCED,
				'S_DSPLYBY' => _PNDSPLYBY,
				'T_DSPLYBY' => select_box('display_by', $pnsettings['display_by'], array(0=>_PNDSPLYBYART, 1=>_PNDSPLYBYSEC, 2=>_PNDSPLYBYCAT)),
				'S_ARTORDR' => _PNARTORDR,
				'T_ARTORDR' => select_box('art_ordr', $pnsettings['art_ordr'], array(0=>_PNDTASC,1=>_PNDTDSC,2=>_PNTTLASC,3=>_PNTTLDSC,4=>_PNRATASC,5=>_PNRATDSC,6=>_PNRDSASC,7=>_PNRDSDSC)),
				'S_HDLINES' => _PNENABLEHDLINES,
				'T_HDLINES' => yesno_option('hdlines',$pnsettings['hdlines']),
				'S_PERHDLINE' => _PNPERHDLINE,
				'T_PERHDLINE' => '<input type="text" name="per_hdline" size="5" value="'.$pnsettings['per_hdline'].'" />',
				'S_1STHDLINE' => _PN1STHDLINE,
				'T_1STHDLINE' => '<input type="text" name="hdln1len" size="5" value="'.$pnsettings['hdln1len'].'" />',
				'S_OTHHDLINE' => _PNOTHHDLINE,
				'T_OTHHDLINE' => '<input type="text" name="hdlnlen" size="5" value="'.$pnsettings['hdlnlen'].'" />',
				'S_ARTHOME' => _PNARTINHOME,
				'T_ARTHOME' => yesno_option('art_inhome',$pnsettings['art_inhome']),
				'L_ARTHOME' => _PNARTINHMWARN,
				'S_DEFTEMP' => _PNDEFTEMPLATE,
				'T_DEFTEMP' => select_option('template', $pnsettings['template'], ProNewsAdm::get_templates()),
				'S_INTROLEN' => _PNINTROLEN,
				'T_INTROLEN' => '<input type="text" name="introlen" size="5" value="'.$pnsettings['introlen'].'" />',
				'S_CMNTLEN' => _PNCMNTLEN,
				'T_CMNTLEN' => '<input type="text" name="cmntlen" size="5" value="'.$pnsettings['cmntlen'].'" />',
				'L_CMNTLEN' => _PNCMNTBLK,
				'S_SECATHDGS' => _PNSECATHDGS,
				'T_SECATHDGS' => select_box('secat_hdgs', $pnsettings['secat_hdgs'], array(0=>_PNNOHDGS, 1=>_PNSECHDGS, 2=>_PNCATHDGS, 3=>_PNBOTHDGS)),
				'S_SHOWICON' => _PNSHOWICON,
				'T_SHOWICON' => yesno_option('show_icons',$pnsettings['show_icons']),
				'S_POSTBY' => _PNSHOWPOSTBY,
				'T_POSTBY' => yesno_option('show_postby',$pnsettings['show_postby']),
				'S_DISCUSS' => _PNSHOWDISC,
				'T_DISCUSS' => yesno_option('comments',$pnsettings['comments']),
				'S_CNTCMNTS' => _PNSHWCNTCMNTS,
				'T_CNTCMNTS' => yesno_option('cnt_cmnts',$pnsettings['cnt_cmnts']),
				'S_PERMALINK' => _PNPERMALINK,
				'T_PERMALINK' => yesno_option('permalink',$pnsettings['permalink']),
				'S_SMILIES' => _PNSMILIES,
				'T_SMILIES' => yesno_option('show_smilies',$pnsettings['show_smilies']),
				'S_READCNT' => _PNREADCNT,
				'T_READCNT' => yesno_option('read_cnt',$pnsettings['read_cnt']),
				'S_SHOWREADS' => _PNSHOWREADS,
				'T_SHOWREADS' => yesno_option('show_reads',$pnsettings['show_reads']),
				'S_RATINGS' => _PNRATINGS,
				'T_RATINGS' => yesno_option('ratings',$pnsettings['ratings']),
				'S_EMAILF' => _PNEMAILF,
				'T_EMAILF' => yesno_option('emailf',$pnsettings['emailf']),
				'S_PRINTF' => _PNPRINTF,
				'T_PRINTF' => yesno_option('printf',$pnsettings['printf']),
				'S_SOC_NET' => _PNSOC_NET,
				'T_SOC_NET' => yesno_option('soc_net',$pnsettings['soc_net']),
				'S_OPN_GRPH' => _PNOPN_GRPH,
				'T_OPN_GRPH' => yesno_option('opn_grph',$pnsettings['opn_grph']),
				'S_SEOTITLE' => _PNSEOTITLE,
				'T_SEOTITLE' => yesno_option('seotitle',$pnsettings['SEOtitle']),
				'S_ALBUM' => _PNSHWALBUM,
				'T_ALBUM' => select_box('show_album', $pnsettings['show_album'], array(1=>_PNYES, 2=>_PNADMNOPT, 0=>_PNNO)),
				'S_USRFLDS' => _PNSHWUSRFLDS,
				'T_USRFLDS' => select_box('show_usrflds', $pnsettings['show_usrflds'], array(1=>_PNYES, 2=>_PNADMNOPT, 0=>_PNNO)),
				'S_RLTDARTS' => _PNSHWRLTDARTS,
//				'T_RLTDARTS' => ($pnsettings['related_arts'] == 1) ? select_box('related_arts', $pnsettings['related_arts'], array(1=>_PNYES, 2=>_PNADMNOPT, 0=>_PNNO)) : select_box('related_arts', $pnsettings['related_arts'], array(2=>_PNADMNOPT, 0=>_PNNO)),
				'T_RLTDARTS' => select_box('related_arts', $pnsettings['related_arts'], array(1=>_PNYES, 2=>_PNADMNOPT, 0=>_PNNO)),
				'S_PERGLLRY' => _PNNUMPGALPG,
				'T_PERGLLRY' => '<input type="text" name="per_gllry" size="5" value="'.$pnsettings['per_gllry'].'" />',
				'L_PERGLLRY' => _PNMAX32,
				'S_RSSINSEC' => _PNRSSINSEC,
				'T_RSSINSEC' => yesno_option('rss_in_secpg', $pnsettings['rss_in_secpg']),
				'S_ENBLRSS' => _PNENBLRSS,
				'T_ENBLRSS' => select_box('enbl_rss', $pnsettings['enbl_rss'], array(0=>_PNNONE, 1=>_PNRSSTO, 2=>_PNRSSTSH, 3=>_PNRSSTSA, 4=>_PNRSSALH, 5=>_PNRSSALL)),
				'S_MEMINSEC' => _PNMEMINSEC,
				'T_MEMINSEC' => yesno_option('mem_in_sec', $pnsettings['mem_in_sec']),
				'S_SECINSEC' => _PNSECINSEC,
				'T_SECINSEC' => select_box('sec_in_sec', $pnsettings['sec_in_sec'], array(0=>_PNNONE, 1=>_PNTRUHME, 2=>_PNHOME, 4=>_PNALL)),
				'S_INCINTRO' => _PNINCINTRO,
				'T_INCINTRO' => yesno_option('inc_intro', $pnsettings['inc_intro']),
				'S_ARTINSEC' => _PNARTINSEC,
				'T_ARTINSEC' => select_box('arts_in_secpg', $pnsettings['arts_in_secpg'], array(0=>_PNNO, 1=>_PNOPTIONAL, 2=>_PNALLWYS)),
				'S_TXTONURL' => _PNTXTONURL,
				'T_TXTONURL' => yesno_option('text_on_url', $pnsettings['text_on_url']),
				'S_LCASEURL' => _PNLCASEURL,
				'T_LCASEURL' => '<input type="checkbox" name="url_lcase" value="'.$pnsettings['url_lcase'].'"'.($pnsettings['url_lcase'] ? ' checked="checked"' : '').' />',
				'S_HYPHENURL' => _PNHYPHENURL,
				'T_HYPHENURL' => '<input type="checkbox" name="url_hyphen" value="'.$pnsettings['url_hyphen'].'"'.($pnsettings['url_hyphen'] ? ' checked="checked"' : '').' />',
				'S_SECCATURL' => _PNSECCATURL,
				'S_SECURL' => _PNSECURL,
				'T_SECURL' => '<input type="checkbox" name="sec_in_url" value="'.$pnsettings['sec_in_url'].'"'.($pnsettings['sec_in_url'] ? ' checked="checked"' : '').' />',
				'S_CATURL' => _PNCATURL,
				'T_CATURL' => '<input type="checkbox" name="cat_in_url" value="'.$pnsettings['cat_in_url'].'"'.($pnsettings['cat_in_url'] ? ' checked="checked"' : '').' />',
				'S_NUMARTSEC' => _PNNUMARTSSEC,
				'T_NUMARTSEC' => '<input type="text" name="num_arts_sec" size="5" value="'.$pnsettings['num_arts_sec'].'" />',
				'S_IFSET' => _PNIFSET,

				'S_FORUMDATE' => _PNFORUMDATE,
				'T_FORUMDATE' => yesno_option('forumdate',$pnsettings['forumdate']),
				'U_FORUMDATE' => _PNFORUMOFFST.' '.($board_config['board_timezone'] >= 0 ? '+' : '').$board_config['board_timezone'].' '._PNHRS,
				'T_DATEFRMT' => '<input type="text" name="dateformat" size="15" value="'.($pnsettings['dateformat'] ? $pnsettings['dateformat'] : 'l F d, Y (H:i:s)').'" />',
				'S_DATEFRMT' => _PNDATEFRMT,

				'S_POSTLINK' => _PNPOSTLINK,
				'T_POSTLINK' => yesno_option('postlink',$pnsettings['postlink']),
				'S_FORUMTYPE' => _PNFORFPRO,
				'T_FORUMTYPE' => select_box('forum_module', $pnsettings['forum_module'], array(1=>_PNNO, 2=>_PNFPRO, 3=>_PNFPROCS)),
				'S_FRMTYPBYCAT' => _PNFRMTYPPERCAT,
				'T_FRMTYPBYCAT' => yesno_option('forum_per-cat',$pnsettings['forum_per-cat']),
				'S_CALTYPE' => _PNCALLINK,
				'T_CALTYPE' => select_box('cal_module', $pnsettings['cal_module'], array(0=>_PNNO, 1=>_PNCALCPGNU, 2=>_PNCALCPGNU2)),
				'S_CALOFST' => $pnsettings['cal_module'] == 2 ? _PNCALOFST : '',
				'T_CALOFST' => $pnsettings['cal_module'] == 2 ? '<input type="text" name="cal_ofst" size="5" value="'.$pnsettings['cal_ofst'].'" />' : '',
				'L_CALOFST' => $pnsettings['cal_module'] == 2 ? _PNHOURS : '',
				'S_TOPICLNK' => _PNTOPICLNK,
				'T_TOPICLNK' => select_box('topic_lnk', $pnsettings['topic_lnk'], array(1=>_PNYES, 2=>_PNADMNOPT, 0=>_PNNO)),
				'S_LMTFULART' => _PNLMTFULART,
				'T_LMTFULART' => yesno_option('lmt_fulart', $pnsettings['lmt_fulart']),
				'S_DSPYFULART' => _PNDSPYFULART,
				'T_DSPYFULART' => yesno_option('disply_full', $pnsettings['disply_full']),
				'S_MEMBERPAGE' => _PNMEMBERPAGE,
				'T_MEMBERPAGE' => select_box('member_pages', $pnsettings['member_pages'], array(0=>_PNNO, 1=>_PNMAXOF.' 1', 2=>_PNMAXOF.' 2', 3=>_PNMAXOF.' 3', 4=>_PNMAXOF.' 4', 5=>_PNMAXOF.' 5', 10=>_PNMAXOF.' 10', 25=>_PNMAXOF.' 25', 50=>_PNMAXOF.' 50', 100=>_PNMAXOF.' 100', 1000=>_PNMAXOF.' 1000')),
				'S_ACTVSRTORD' => _PNACTVSRTORD,
				'T_ACTVSRTORD' => yesno_option('actv_sort_ord', $pnsettings['actv_sort_ord']),
				'L_ACTVSRTORD' => _PNONLYIF,

				'L_SRCHSET' => _PNSRCHSET,
				'S_SRCHTMPLT' => _PNSRCHTMPLT,
				'T_SRCHTMPLT' => select_option('srch_tmplt', $pnsettings['srch_tmplt'], array_merge(array(_PNDEFTEMPLATE), ProNewsAdm::get_templates())),
				'S_SRCHNUMARTS' => _PNSRCHNUMARTS,
				'T_SRCHNUMARTS' => '<input type="text" name="srch_num_arts" size="5" value="'.$pnsettings['srch_num_arts'].'" />',
				'S_SRCHINTROLEN' => _PNSRCHINTROLEN,
				'T_SRCHINTROLEN' => '<input type="text" name="srch_introlen" size="5" value="'.$pnsettings['srch_introlen'].'" />',
				'S_SRCHSHOWIMG' => _PNSRCHSHOWIMG,
				'T_SRCHSHOWIMG' => yesno_option('srch_show_img', $pnsettings['srch_show_img']),
				'S_SRCHNUMLIST' => _PNSRCHNUMLIST,
				'T_SRCHNUMLIST' => '<input type="text" name="srch_num_inlist" size="5" value="'.$pnsettings['srch_num_inlist'].'" />',

				'S_IMGSET' => _PNIMGSET,
				'S_ALLUPL' => _PNALLOWUPLD,
				'T_ALLUPL' => yesno_option('allow_up',$pnsettings['allow_up']),
				'S_MAXW' => _PNMAXWIDTH,
				'T_MAXW' => '<input type="text" name="max_w" size="5" value="'.$pnsettings['max_w'].'" />',
				'S_MAXH' => _PNMAXHEIGHT,
				'T_MAXH' => '<input type="text" name="max_h" size="5" value="'.$pnsettings['max_h'].'" />',
				'S_ASPECT' => _PNASPECT,
				'T_ASPECT' => select_box('aspect', $pnsettings['aspect'], array(0=>_PNASPECTMAX, 1=>_PNASPECTW, 2=>_PNASPECTH)),
				'S_MAXIMG' => _PNMAXIMG,
				'T_MAXIMG' => '<input type="text" name="img_limit" size="5" value="'.$pnsettings['img_limit'].'" />',
				'S_IMGMAXRMTE' => _PNIMGMAXRMTE,
				'T_IMGMAXRMTE' => '<input type="text" name="img_max_width_remote" size="5" value="'.$pnsettings['img_max_width_remote'].'" />',
				'S_SHOWNOIMG' => _PNSHOWNOIMG,
				'T_SHOWNOIMG' => yesno_option('show_noimage',$pnsettings['show_noimage']),
				'S_CLRBLKS' => _PNCLRBLKS,
				'T_CLRBLKS' => select_box('clrblks_hm', $pnsettings['clrblks_hm'], array(0=>_PNNEVER, 1=>_PNHOMEPLUS, 2=>_PNHOMEONLY)),
				'S_SAVE' => _PNSAVECONFIG,
				'G_SAVE' => '<input type="submit" name="submit" value="'._SUBMIT.'" />',
				'S_WARNING' => _PNCFGWARNING,
				'S_HINT' => _PNHINT,
				'S_ADMINSTTNGS' => _PNADMINSTTNGS,
				'S_MODERATE' => _PNWHOMODGRP,
				'T_MODERATE' => ProNewsAdm::groups_only_selectbox('mod_grp', explode(',',$pnsettings['mod_grp']), false, false),
				'S_AUTOAPPRV' => _PNAUTOAPPRV,
				'T_AUTOAPPRV' => yesno_option('auto_apprv',$pnsettings['auto_apprv']),
				'S_EDITTIME' => _PNEDITTIME,
				'T_EDITTIME' => select_box('edit_time', $pnsettings['edit_time'], array(0=>_PNEVER, 5=> '5 '._PNMINS, 15=>'15 '._PNMINS, 30=>'30 '._PNMINS, 60=>'60 '._PNMINS, 1440=>'24 '._PNHRS, 4320=>'3 '._PNDAYS, 10080=>'7 '._PNDAYS, 20160=>'14 '._PNDAYS, 40320=>'28 '._PNDAYS, ''=>_PNALWAYS)),
				'G_MULTILANG' => $multilingual,
				'S_MULTILANG_WARN' => _PNMULTILANGWARN,
				'S_MULTILANG_WARN2' => _PNMULTILANGWARN2,
				'S_ADMINHTML' => _PNADMINHTML,
				'T_ADMINHTML' => yesno_option('admin_html', $pnsettings['admin_html']),
				'S_NOTIFYAMPA' => _PNNOTIFYAMPA,
				'T_NOTIFYAMPA' => yesno_option('notify_admin_pending_article', $pnsettings['notify_admin_pending_article']),
				'S_PERADMNPAGE' => _PNPERADMNPAGE,
				'T_PERADMNPAGE' => '<input type="text" name="per_admn_page" size="5" value="'.$pnsettings['per_admn_page'].'" />',
//				'S_RECEMAILPA' => _PNRECEMAILPA,
//				'T_RECEMAILPA' => '<input type="text" name="receiver_email_pending_article" size="44" value="'.$pnsettings['receiver_email_pending_article'].'" />',
				'S_SUBJMAILPA' => _PNSUBJMAILPA,
				'T_SUBJMAILPA' => '<input type="text" name="subject_email_pending_article" size="44" value="'.(($pnsettings['subject_email_pending_article'] || $multilingual) ? $pnsettings['subject_email_pending_article'] : '').'" />',
				'S_MESSBODYPA' => _PNMESSBODYPA,
				'T_MESSBODYPA' => '<textarea name="body_email_pending_article" cols="42" rows="3">'.(($pnsettings['body_email_pending_article'] || $multilingual) ? $pnsettings['body_email_pending_article'] : _PNBODYPENDART).'</textarea>',
				'S_SENDMAILPA' => _PNSENDMAILPA,
				'T_SENDMAILPA' =>'<input type="text" name="sender_email_pending_article" size="44" value="'.$pnsettings['sender_email_pending_article'].'" />',
				'S_NOTIFYUSER' => _PNNOTIFYUSER,
				'T_NOTIFYUSER' => yesno_option('notify_user_approving_article', $pnsettings['notify_user_approving_article']),
				'S_SUBJAPPRV' =>  _PNSUBJMAILPA,
				'T_SUBJAPPRV' => '<input type="text" name="subject_email_approved_article" size="30" value="'.(($pnsettings['subject_email_approved_article'] || $multilingual) ? $pnsettings['subject_email_approved_article'] : _PNSUBJAPPRVART).'" /> ',
				'L_SUBJAPPRV' => _PNAT.' '.$gblsettings['sitename'],
				'S_MESSAPPRV' => _PNMESSBODYPA,
				'L_MESSAPPRV' => _PNARTICLE.': < <i>'._PNARTICLETITLE.'</i> > <br />',
				'T_MESSAPPRV' => '<textarea name="body_email_approved_article" cols="42" rows="5">'.(($pnsettings['body_email_approved_article'] || $multilingual) ? $pnsettings['body_email_approved_article'] : _PNBODYAPPRVART."\n\n"._PNTHANKU).'</textarea>',
				'M_MESSAPPRV' => '<br />'._PNADMINISTRATOR.' '._PNAT.' '.$gblsettings['sitename']
			));
	 		$cpgtpl->assign_block_vars('import_form', array(
				'S_IMPORT' => _PNLIMPORT,
				'G_IMPORT' => '<input type="hidden" name="import" value="doit" /><input type="submit" name="submit" value="'._PNIMPORT.'" />',
				'G_STARTFORM' => open_form(adminlink("&amp;file=import"),'impform','&nbsp;'._PNIMPORT.'&nbsp;'),
				'G_ENDFORM' => close_form()
			));
		}
		if ($func == 'save') {
			if ($_POST['per_page'] != $pnsettings['per_page']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['per_page'].'" WHERE cfg_name="pro_news" AND cfg_field="per_page"');}
			if ($_POST['usr_ovr_ppage'] != $pnsettings['usr_ovr_ppage']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['usr_ovr_ppage'].'" WHERE cfg_name="pro_news" AND cfg_field="usr_ovr_ppage"');}
			if ($_POST['display_by'] != $pnsettings['display_by']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['display_by'].'" WHERE cfg_name="pro_news" AND cfg_field="display_by"');}
			if ($_POST['art_ordr'] != $pnsettings['art_odrdr']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['art_ordr'].'" WHERE cfg_name="pro_news" AND cfg_field="art_ordr"');}
			if ($_POST['hdlines'] != $pnsettings['hdlines']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['hdlines'].'" WHERE cfg_name="pro_news" AND cfg_field="hdlines"');}
			if ($_POST['per_hdline'] != $pnsettings['per_hdline']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['per_hdline'].'" WHERE cfg_name="pro_news" AND cfg_field="per_hdline"');}
			if ($_POST['hdln1len'] != $pnsettings['hdln1len']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['hdln1len'].'" WHERE cfg_name="pro_news" AND cfg_field="hdln1len"');}
			if ($_POST['hdlnlen'] != $pnsettings['hdlnlen']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['hdlnlen'].'" WHERE cfg_name="pro_news" AND cfg_field="hdlnlen"');}
			if ($_POST['art_inhome'] != $pnsettings['art_inhome']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['art_inhome'].'" WHERE cfg_name="pro_news" AND cfg_field="art_inhome"');}
			if ($_POST['template'] != $pnsettings['template']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['template'].'" WHERE cfg_name="pro_news" AND cfg_field="template"');}
			if ($_POST['introlen'] != $pnsettings['introlen']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['introlen'].'" WHERE cfg_name="pro_news" AND cfg_field="introlen"');}
			if ($_POST['cmntlen'] != $pnsettings['cmntlen']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['cmntlen'].'" WHERE cfg_name="pro_news" AND cfg_field="cmntlen"');}
			if ($_POST['secat_hdgs'] != $pnsettings['secat_hdgs']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['secat_hdgs'].'" WHERE cfg_name="pro_news" AND cfg_field="secat_hdgs"');}
			if ($_POST['show_icons'] != $pnsettings['show_icons']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['show_icons'].'" WHERE cfg_name="pro_news" AND cfg_field="show_icons"');}
			if ($_POST['show_postby'] != $pnsettings['show_postby']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['show_postby'].'" WHERE cfg_name="pro_news" AND cfg_field="show_postby"');}
			if ($_POST['allow_up'] != $pnsettings['allow_up']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['allow_up'].'" WHERE cfg_name="pro_news" AND cfg_field="allow_up"');}
			if ($_POST['show_noimage'] != $pnsettings['show_noimage']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['show_noimage'].'" WHERE cfg_name="pro_news" AND cfg_field="show_noimage"');}  // Modified by Masino Sinaga, June 22, 2009
			if ($_POST['comments'] != $pnsettings['comments']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['comments'].'" WHERE cfg_name="pro_news" AND cfg_field="comments"');}
			if ($_POST['cnt_cmnts'] != $pnsettings['cnt_cmnts']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['cnt_cmnts'].'" WHERE cfg_name="pro_news" AND cfg_field="cnt_cmnts"');}
			if ($_POST['permalink'] != $pnsettings['permalink']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['permalink'].'" WHERE cfg_name="pro_news" AND cfg_field="permalink"');}
			if ($_POST['show_smilies'] != $pnsettings['show_smilies']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['show_smilies'].'" WHERE cfg_name="pro_news" AND cfg_field="show_smilies"');}
			if ($_POST['show_album'] != $pnsettings['show_album']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['show_album'].'" WHERE cfg_name="pro_news" AND cfg_field="show_album"');}
			if ($_POST['show_usrflds'] != $pnsettings['show_usrflds']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['show_usrflds'].'" WHERE cfg_name="pro_news" AND cfg_field="show_usrflds"');}
			if ($_POST['related_arts'] != $pnsettings['related_arts']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['related_arts'].'" WHERE cfg_name="pro_news" AND cfg_field="related_arts"');}
			if ($_POST['text_on_url'] != $pnsettings['text_on_url']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['text_on_url'].'" WHERE cfg_name="pro_news" AND cfg_field="text_on_url"');}
			if (isset($_POST['url_hyphen'])) {$url_hyphen = '1';} else {$url_hyphen = 0;}
			if ($url_hyphen != $pnsettings['url_hyphen']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$url_hyphen.'" WHERE cfg_name="pro_news" AND cfg_field="url_hyphen"');}
			if (isset($_POST['url_lcase'])) {$url_lcase = '1';} else {$url_lcase = 0;}
			if ($url_lcase != $pnsettings['url_lcase']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$url_lcase.'" WHERE cfg_name="pro_news" AND cfg_field="url_lcase"');}
			if (isset($_POST['sec_in_url'])) {$sec_in_url = '1';} else {$sec_in_url = 0;}
			if ($sec_in_url != $pnsettings['sec_in_url']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$sec_in_url.'" WHERE cfg_name="pro_news" AND cfg_field="sec_in_url"');}
			if (isset($_POST['cat_in_url'])) {$cat_in_url = '1';} else {$cat_in_url = 0;}
			if ($cat_in_url != $pnsettings['cat_in_url']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$cat_in_url.'" WHERE cfg_name="pro_news" AND cfg_field="cat_in_url"');}
			if ($_POST['per_gllry'] != $pnsettings['per_gllry']) {
				if ($_POST['per_gllry'] < '3') {$_POST['per_gllry'] = '3';}
				if ($_POST['per_gllry'] > '32') {$_POST['per_gllry'] = '32';}
				$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.intval($_POST['per_gllry']).'" WHERE cfg_name="pro_news" AND cfg_field="per_gllry"');
			}
			if ($_POST['arts_in_secpg'] != $pnsettings['arts_in_secpg']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['arts_in_secpg'].'" WHERE cfg_name="pro_news" AND cfg_field="arts_in_secpg"');}
			if ($_POST['num_arts_sec'] != $pnsettings['num_arts_sec']) {
				if ($_POST['num_arts_sec'] <= '1') {$_POST['num_arts_sec'] = '1';}
				$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.intval($_POST['num_arts_sec']).'" WHERE cfg_name="pro_news" AND cfg_field="num_arts_sec"');
			}
			if ($_POST['rss_in_secpg'] != $pnsettings['rss_in_secpg']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['rss_in_secpg'].'" WHERE cfg_name="pro_news" AND cfg_field="rss_in_secpg"');}
			if ($_POST['mem_in_sec'] != $pnsettings['mem_in_sec']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['mem_in_sec'].'" WHERE cfg_name="pro_news" AND cfg_field="mem_in_sec"');}
			if ($_POST['sec_in_sec'] != $pnsettings['sec_in_sec']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['sec_in_sec'].'" WHERE cfg_name="pro_news" AND cfg_field="sec_in_sec"');}
			if ($_POST['inc_intro'] != $pnsettings['inc_intro']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['inc_intro'].'" WHERE cfg_name="pro_news" AND cfg_field="inc_intro"');}
			if ($_POST['enbl_rss'] != $pnsettings['enbl_rss']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['enbl_rss'].'" WHERE cfg_name="pro_news" AND cfg_field="enbl_rss"');}
			if ($_POST['postlink'] != $pnsettings['postlink']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['postlink'].'" WHERE cfg_name="pro_news" AND cfg_field="postlink"');}
			if ($_POST['forum_module'] != $pnsettings['forum_module']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['forum_module'].'" WHERE cfg_name="pro_news" AND cfg_field="forum_module"');}
			if ($_POST['forum_per-cat'] != $pnsettings['forum_per-cat']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['forum_per-cat'].'" WHERE cfg_name="pro_news" AND cfg_field="forum_per-cat"');}
			if ($_POST['read_cnt'] != $pnsettings['read_cnt']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['read_cnt'].'" WHERE cfg_name="pro_news" AND cfg_field="read_cnt"');}
			if ($_POST['show_reads'] != $pnsettings['show_reads']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['show_reads'].'" WHERE cfg_name="pro_news" AND cfg_field="show_reads"');}
			if ($_POST['ratings'] != $pnsettings['ratings']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['ratings'].'" WHERE cfg_name="pro_news" AND cfg_field="ratings"');}
			if ($_POST['emailf'] != $pnsettings['emailf']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['emailf'].'" WHERE cfg_name="pro_news" AND cfg_field="emailf"');}
			if ($_POST['printf'] != $pnsettings['printf']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['printf'].'" WHERE cfg_name="pro_news" AND cfg_field="printf"');}
			if ($_POST['soc_net'] != $pnsettings['soc_net']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['soc_net'].'" WHERE cfg_name="pro_news" AND cfg_field="soc_net"');}
			if ($_POST['opn_grph'] != $pnsettings['opn_grph']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['opn_grph'].'" WHERE cfg_name="pro_news" AND cfg_field="opn_grph"');}
			if ($_POST['seotitle'] && $_POST['seotitle'] != $pnsettings['SEOtitle']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['seotitle'].'" WHERE cfg_name="pro_news" AND cfg_field="SEOtitle"');}
			if ($_POST['cal_module'] != $pnsettings['cal_module']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['cal_module'].'" WHERE cfg_name="pro_news" AND cfg_field="cal_module"');}
			if ($_POST['cal_ofst'] != $pnsettings['cal_ofst']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['cal_ofst'].'" WHERE cfg_name="pro_news" AND cfg_field="cal_ofst"');}

			if ($_POST['forumdate'] != $pnsettings['forumdate']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['forumdate'].'" WHERE cfg_name="pro_news" AND cfg_field="forumdate"');}
			if ($_POST['dateformat'] != $pnsettings['dateformat']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['dateformat'].'" WHERE cfg_name="pro_news" AND cfg_field="dateformat"');}

			if ($_POST['topic_lnk'] != $pnsettings['topic_lnk']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['topic_lnk'].'" WHERE cfg_name="pro_news" AND cfg_field="topic_lnk"');}
			if ($_POST['lmt_fulart'] != $pnsettings['lmt_fulart']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['lmt_fulart'].'" WHERE cfg_name="pro_news" AND cfg_field="lmt_fulart"');}
			if ($_POST['disply_full'] != $pnsettings['disply_full']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['disply_full'].'" WHERE cfg_name="pro_news" AND cfg_field="disply_full"');}
			if ($_POST['member_pages'] != $pnsettings['member_pages']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['member_pages'].'" WHERE cfg_name="pro_news" AND cfg_field="member_pages"');}
			if ($_POST['actv_sort_ord'] != $pnsettings['actv_sort_ord']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['actv_sort_ord'].'" WHERE cfg_name="pro_news" AND cfg_field="actv_sort_ord"');}

			if ($_POST['srch_num_arts'] != $pnsettings['srch_num_arts']) {
				if ($_POST['srch_num_arts'] == '') { $_POST['srch_num_arts'] = '10'; }
				$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['srch_num_arts'].'" WHERE cfg_name="pro_news" AND cfg_field="srch_num_arts"');}
			if ($_POST['srch_num_inlist'] != $pnsettings['srch_num_inlist']) {
				if ($_POST['srch_num_inlist'] == '') { $_POST['srch_num_inlist'] = '25'; }
				$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['srch_num_inlist'].'" WHERE cfg_name="pro_news" AND cfg_field="srch_num_inlist"');}
			if ($_POST['srch_introlen'] != $pnsettings['srch_introlen']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['srch_introlen'].'" WHERE cfg_name="pro_news" AND cfg_field="srch_introlen"');}
			if ($_POST['srch_tmplt'] != $pnsettings['srch_tmplt']) {
				if ($_POST['srch_tmplt'] == _PNDEFTEMPLATE) { $_POST['srch_tmplt'] = ''; }
				$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['srch_tmplt'].'" WHERE cfg_name="pro_news" AND cfg_field="srch_tmplt"');}
			if ($_POST['srch_show_img'] != $pnsettings['srch_show_img']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['srch_show_img'].'" WHERE cfg_name="pro_news" AND cfg_field="srch_show_img"');}
			if ($_POST['srch_num_inlist'] != $pnsettings['srch_num_inlist']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['srch_num_inlist'].'" WHERE cfg_name="pro_news" AND cfg_field="srch_num_inlist"');}

			if ($_POST['max_w'] != $pnsettings['max_w']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['max_w'].'" WHERE cfg_name="pro_news" AND cfg_field="max_w"');}
			if ($_POST['max_h'] != $pnsettings['max_h']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['max_h'].'" WHERE cfg_name="pro_news" AND cfg_field="max_h"');}
			if ($_POST['aspect'] != $pnsettings['aspect']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['aspect'].'" WHERE cfg_name="pro_news" AND cfg_field="aspect"');}
			if ($_POST['img_limit'] != $pnsettings['img_limit']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['img_limit'].'" WHERE cfg_name="pro_news" AND cfg_field="img_limit"');}
			if ($_POST['img_max_width_remote'] != $pnsettings['img_max_width_remote']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['img_max_width_remote'].'" WHERE cfg_name="pro_news" AND cfg_field="img_max_width_remote"');}
			if ($_POST['clrblks_hm'] != $pnsettings['clrblks_hm']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['clrblks_hm'].'" WHERE cfg_name="pro_news" AND cfg_field="clrblks_hm"');}
			if ($_POST['admin_html'] != $pnsettings['admin_html']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['admin_html'].'" WHERE cfg_name="pro_news" AND cfg_field="admin_html"');}
			if ($_POST['notify_admin_pending_article'] != $pnsettings['notify_admin_pending_article']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['notify_admin_pending_article'].'" WHERE cfg_name="pro_news" AND cfg_field="notify_admin_pending_article"');}
//			if ($_POST['receiver_email_pending_article'] != $pnsettings['receiver_email_pending_article']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['receiver_email_pending_article'].'" WHERE cfg_name="pro_news" AND cfg_field="receiver_email_pending_article"');}
			if ($_POST['subject_email_pending_article'] != $pnsettings['subject_email_pending_article']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['subject_email_pending_article'].'" WHERE cfg_name="pro_news" AND cfg_field="subject_email_pending_article"');}
			if ($_POST['body_email_pending_article'] != $pnsettings['body_email_pending_article']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['body_email_pending_article'].'" WHERE cfg_name="pro_news" AND cfg_field="body_email_pending_article"');}
			if ($_POST['sender_email_pending_article'] != $pnsettings['sender_email_pending_article']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['sender_email_pending_article'].'" WHERE cfg_name="pro_news" AND cfg_field="sender_email_pending_article"');}
			if ($_POST['notify_user_approving_article'] != $pnsettings['notify_user_approving_article']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['notify_user_approving_article'].'" WHERE cfg_name="pro_news" AND cfg_field="notify_user_approving_article"');}
			if ($_POST['subject_email_approved_article'] != $pnsettings['subject_email_approved_article']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['subject_email_approved_article'].'" WHERE cfg_name="pro_news" AND cfg_field="subject_email_approved_article"');}
			if ($_POST['body_email_approved_article'] != $pnsettings['body_email_approved_article']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['body_email_approved_article'].'" WHERE cfg_name="pro_news" AND cfg_field="body_email_approved_article"');}
			if ($_POST['per_admn_page'] != $pnsettings['per_admn_page']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['per_admn_page'].'" WHERE cfg_name="pro_news" AND cfg_field="per_admn_page"');}
			if ($_POST['mod_grp'] != $pnsettings['mod_grp']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.implode(',',$_POST['mod_grp']).'" WHERE cfg_name="pro_news" AND cfg_field="mod_grp"');}
			if ($_POST['auto_apprv'] != $pnsettings['auto_apprv']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['auto_apprv'].'" WHERE cfg_name="pro_news" AND cfg_field="auto_apprv"');}
			if ($_POST['edit_time'] != $pnsettings['edit_time']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['edit_time'].'" WHERE cfg_name="pro_news" AND cfg_field="edit_time"');}

//			if ($_POST['cmnts_dflt'] != $pnsettings['cmnts_dflt']) {$db->sql_query('UPDATE '.$prefix.'_config_custom SET cfg_value="'.$_POST['cmnts_dflt'].'" WHERE cfg_name="pro_news" AND cfg_field="cmnts_dflt"');}
			Cache::array_delete('MAIN_CFG');
			url_refresh(adminlink("&amp;mode=cfg"));
			$msg = _PNCFGSUC.'<br /><a href="'.adminlink("&amp;mode=cfg").'">'._PNGOBACK.'</a>';
			$cpgtpl->assign_block_vars('pn_msg', array(
				'S_MSG' => $msg
			));
		}
	}

	function admin_sec_form($func='', $id='') {
		global $db, $prefix, $cpgtpl, $forum_module, $pnsettings, $domainlist, $multilingual, $currentlang;
		if ((!is_null($id) && $func == 'save')) {
			$submit = _PNSAVESEC;
			$dosave='save&amp;id='.$id;
			$btitle = '&nbsp;'._PNAEDIT.'&nbsp;'._PNSECTION.'&nbsp;';
//			list($sid, $title, $description, $view, $admin, $forum_id, $sequence, $in_home, $forum_module, $forumspro_name, $usrfld0, $usrfld1, $usrfld2, $usrfld3, $usrfld4, $usrfld5, $usrfld6, $usrfld7, $usrfld8, $usrfld9, $usrfldttl, $template, $art_ord) = $db->sql_fetchrow($db->sql_query('SELECT * FROM '.$prefix.'_pronews_sections WHERE id='.$id));
            //fishingfan change line below to add $secheadlines $sectrunc1head & $sectrunchead
			list($sid, $title, $description, $view, $admin, $forum_id, $sequence, $in_home, $forum_module, $forumspro_name, $usrfld0, $usrfld1, $usrfld2, $usrfld3, $usrfld4, $usrfld5, $usrfld6, $usrfld7, $usrfld8, $usrfld9, $usrfldttl, $template, $art_ord, $secheadlines, $sectrunc1head , $sectrunchead, $post, $secdsplyby, $keyusrfld, $moderate) = $db->sql_fetchrow($db->sql_query('SELECT * FROM '.$prefix.'_pronews_sections WHERE id='.$id));
			$mod = $moderate;
		} else {
//			$title='';$description='';$view='';$admin='2';$forum_id='';$in_home='1'; $forum_module='1'; $forumspro_name=''; $usrfld0=''; $usrfld1=''; $usrfld2=''; $usrfld3=''; $usrfld4=''; $usrfld5=''; $usrfld6=''; $usrfld7=''; $usrfld8=''; $usrfld9=''; $usrfldttl=''; $template=''; $art_ord='0'; $submit=_PNADDSEC;$dosave='save';
            //fishingfan change line below to add $secheadlines $sectrunc1head & $sectrunchead
			$title = '';
			$description = '';
			$view = '';
			$post = '1';
			$admin = '2';
			$mod = '5';			// needs to be 2 but will have -3 applied before display
			$forum_id = '';
			$in_home = '1';
			$forum_module = '1';
			$forumspro_name = '';
			$secdsplyby = '0';
			$usrfld0 = ''; $usrfld1=''; $usrfld2=''; $usrfld3=''; $usrfld4=''; $usrfld5=''; $usrfld6=''; $usrfld7=''; $usrfld8=''; $usrfld9='';
			$usrfldttl = '';
			$keyusrfld = '0';
			$template = '';
			$art_ord = '0';
			$secheadlines = '';
			$sectrunc1head = '';
			$sectrunchead = '';
			$submit = ($id == '') ? _PNADDSEC : _PNSAVESEC;
			$dosave = 'save';
			$btitle = ($id != '') ? '&nbsp;'._PNAEDIT.'&nbsp;'._PNSECTION.'&nbsp;' : '&nbsp;'._PNADD.'&nbsp;'._PNSECTION.'&nbsp;';
		}
// echo ' post='.$post.' f_mod='.$forum_module.' fp_nme= '.$forumspro_name;
		$forum_name = ($pnsettings['forum_module'] == '2') ? 'fpro' : '1';
		$forum_module = ($forum_name == '1') ? '1' : '2';
		if ($forumspro_name == '') {
			$forumspro_name = ($forum_name == '1') ? '' : $forum_name;
		}
		if (isset($_POST['chng'])) {
			if (isset($_POST['forum_id'])) { $forum_id = intval($_POST['forum_id']); }
			if (isset($_POST['forum_name'])) {
				$forum_name = Fix_Quotes($_POST['forum_name'],1);
				$forum_module = ($forum_name == '1') ? '1' : '2';
				$forumspro_name = ($forum_name == '1') ? '' : $forum_name;
				$forum_id = '';
			}
			if (isset($_POST['title'])) { $title = Fix_Quotes($_POST['title'],1); }
			if (isset($_POST['descrip'])) { $description = Fix_Quotes($_POST['descrip'],1); }
			if (isset($_POST['view'])) { $view = intval($_POST['view']); }
			if (isset($_POST['post'])) { $post =  intval($_POST['post']); }
			if (isset($_POST['admin'])) { $admin = intval($_POST['admin']); }
			if (isset($_POST['mod'])) { $mod = intval($_POST['mod']-3); }
			if (isset($_POST['template'])) { $template =  Fix_Quotes($_POST['template'],1); }
			if (isset($_POST['secdsplyby'])) { $secdsplyby = Fix_Quotes($_POST['secdsplyby'],1); }
			if (isset($_POST['art_ord'])) { $art_ord =  Fix_Quotes($_POST['art_ord'],1); }
			if (isset($_POST['in_home'])) { $in_home = Fix_Quotes($_POST['in_home'],1); }
			if (isset($_POST['secheadlines'])) { $secheadlines =  intval($_POST['secheadlines']); }
			if (isset($_POST['sectrunc1head'])) { $sectrunc1head = intval($_POST['sectrunc1head']); }
			if (isset($_POST['sectrunchead'])) { $sectrunchead =  intval($_POST['sectrunchead']); }
			if (isset($_POST['user_fld_ttl'])) { $usrfldttl = Fix_Quotes($_POST['user_fld_ttl'],1); }
			if (isset($_POST['user_fld_0'])) { $usrfld0 =  Fix_Quotes($_POST['user_fld_0'],1); }
			if (isset($_POST['user_fld_1'])) { $usrfld1 =  Fix_Quotes($_POST['user_fld_1'],1); }
			if (isset($_POST['user_fld_2'])) { $usrfld2 =  Fix_Quotes($_POST['user_fld_2'],1); }
			if (isset($_POST['user_fld_3'])) { $usrfld3 =  Fix_Quotes($_POST['user_fld_3'],1); }
			if (isset($_POST['user_fld_4'])) { $usrfld4 =  Fix_Quotes($_POST['user_fld_4'],1); }
			if (isset($_POST['user_fld_5'])) { $usrfld5 =  Fix_Quotes($_POST['user_fld_5'],1); }
			if (isset($_POST['user_fld_6'])) { $usrfld6 =  Fix_Quotes($_POST['user_fld_6'],1); }
			if (isset($_POST['user_fld_7'])) { $usrfld7 =  Fix_Quotes($_POST['user_fld_7'],1); }
			if (isset($_POST['user_fld_8'])) { $usrfld8 =  Fix_Quotes($_POST['user_fld_8'],1); }
			if (isset($_POST['user_fld_8'])) { $usrfld9 =  Fix_Quotes($_POST['user_fld_9'],1); }
			if (isset($_POST['key_usr_fld'])) { $keyusrfld =  intval($_POST['key_usr_fld']); }
		}
// echo ' pnset='.$pnsettings['forum_module'].' fn='.$forum_name.' fm='.$forum_module.' fpname='.$forumspro_name;

		$artord_select = array('0'=>_PNARTORDR,'1'=>_PNDTASC,'2'=>_PNDTDSC,'3'=>_PNTTLASC,'4'=>_PNTTLDSC,'5'=>_PNRATASC,'6'=>_PNRATDSC,'7'=>_PNRDSASC,'8'=>_PNRDSDSC);
		if ($pnsettings['actv_sort_ord'] == '1') {
			$artord_select = array_merge($artord_select, array('9'=>_PNSTDTASC,'10'=>_PNSTDTDSC,'11'=>_PNENDTASC,'12'=>_PNENDTDSC));
		}
		$secdspbylst = array(0=>_PNDSPLYBYART, 1=>_PNDSPLYBYSEC, 2=>_PNDSPLYBYCAT);

		$tempfname = ($pnsettings['forum_module'] == '2') ? select_box('forum_name', 'ForumsPro', array('fpro'=>'ForumsPro')) : select_box('forum_name', 'Forum', array('1'=>'Forum (cpg-BB)'));
		$tempform = array(
			'S_TITLE' => _PNTITLE,
			'T_TITLE' => '<input type="text" name="title" size="25" value="'.$title.'" />',
			'S_DESC' => _PNDESC,
			'T_DESC' => '<textarea cols="54" rows="1" name="descrip" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);">'.$description.'</textarea>',
			'S_VIEW' => _PNVIS,
			'T_VIEW' => group_selectbox('view', $view, '1'),
			'S_POST' => _PNALLWPST,
			'T_POST' => group_selectbox('post', $post, '1'),
			'S_ADMIN' => _PNWHOADMIN,
			'T_ADMIN' => group_selectbox('admin', $admin, false, false),
			'S_MOD' => _PNWHOMOD,
			'T_MOD' => ProNewsAdm::group_selectbox('mod', $mod+3),
			'S_ARTORD' => _PNARTORD,
			'T_ARTORD' => select_box('art_ord', $art_ord, $artord_select),
			'U_ARTORD' => _PNARTORDR.' '._PNCRRNT.': '.$artord_select[$pnsettings['art_ordr']+1],
			'S_SECDSPLYBY' => _PNDSPLYBY,
			'T_SECDSPLYBY' => select_box('secdsplyby', $secdsplyby, array(0=>_PNDFLT, 1=>_PNDSPLYBYSART, 2=>_PNDSPLYBYSCAT)),
			'U_SECDSPLYBY' => _PNDFLT.' '._PNCRRNT.': '.$secdspbylst[$pnsettings['display_by']],
			'T_CHANGE' => '<input type="submit" name="chng" value="'._PNCHANGE.'" />',
			'G_FPRONAME' => ($pnsettings['forum_module'] == '3') ? '1' : '0',
			'S_FPRONAME' => _PNFPRONAME,
			'T_FPRONAME' => ($pnsettings['forum_module'] == '3') ? ProNewsAdm::admin_activefpros($forumspro_name) : $tempfname,

			'S_FORUM' => _PNFORUM,
			'T_FORUM' => ($forum_module <= '1' || $forum_module == '') ? ProNewsAdm::admin_forumlist($forum_id, 0, _PNFRMNONE) : (($pnsettings['forum_module'] == '3') ? ProNewsAdm::admin_forumsprolist($forumspro_name, $forum_id, 0 ,'') : ProNewsAdm::admin_forumsprolist($forumspro_name, $forum_id, 0, _PNFRMNONE)),
			'S_TEMPLATE' => _PNTEMPLATE,
			'T_TEMPLATE' => select_option('template', ($template != '') ? $template : _PNDEFTEMPLATE, array_merge(array(_PNDEFTEMPLATE), ProNewsAdm::get_templates())),
			'U_TEMPLATE' => _PNDEFTEMPLATE.' '._PNCRRNT.': '.$pnsettings['template'],
			'S_INHOME' => _PNINHOME,
			'T_INHOME' => select_box('in_home',$in_home, array_merge(array(0=>_PNNEVER, 1=>_PNHOMEPLUS, 2=>_PNHOMEONLY), $domainlist)),
            //fishingfan additions start
            'S_SECHEADLINES' => _PNPERHDLINE,
			'T_SECHEADLINES' => '<input type="text" name="secheadlines" size="5" value="'.(($secheadlines =='0') ? '' : $secheadlines).'" />',
            'S_SECTRUNC1HEAD' => _PN1STHDLINE,
			'T_SECTRUNC1HEAD' => '<input type="text" name="sectrunc1head" size="5" value="'.(($sectrunc1head =='0') ? '' : $sectrunc1head).'" />',
            'S_SECTRUNCHEAD' => _PNOTHHDLINE,
			'T_SECTRUNCHEAD' => '<input type="text" name="sectrunchead" size="5" value="'.(($sectrunchead =='0') ? '' : $sectrunchead).'" />',
            //fishingfan additions end
			'L_SECHEADCMMT' => _PNLVBLNK,
			'U_HEADLINES' => _PNCRRNT.': '.$pnsettings['per_hdline'],
			'U_TRUNC1HEAD' => _PNCRRNT.': '.$pnsettings['hdln1len'],
			'U_TRUNCHEAD' => _PNCRRNT.': '.$pnsettings['hdlnlen'],
			'S_DEF_USR_FLDS' => _PNDEFUSRFLDS,
			'L_DEF_USR_FLDS' => _PNOPTIONAL.' - '._PNMSTDEFN,
			'G_MULTILANG' => $multilingual,
			'S_MULTILANG_WARN' => _PNMULTILANGWARN,
			'S_MULTILANG_WARN2' => _PNMULTILANGWARN2,
			'S_USER_FLD_TTL' => _PNDETAILS,
			'T_USER_FLD_TTL' => '<input type="text" name="user_fld_ttl" size="50" value="'.$usrfldttl.'" />',
			'S_FIELDS' => _PNFIELDS,
			'L_FIELDS' => _PNFRMTOP,
			'S_USER_FLD_0' => _PNUSRFLD0,
			'T_USER_FLD_0' => '<input type="text" name="user_fld_0" size="50" value="'.$usrfld0.'" />',
			'S_USER_FLD_1' => _PNUSRFLD1,
			'T_USER_FLD_1' => '<input type="text" name="user_fld_1" size="50" value="'.$usrfld1.'" />',
			'S_USER_FLD_2' => _PNUSRFLD2,
			'T_USER_FLD_2' => '<input type="text" name="user_fld_2" size="50" value="'.$usrfld2.'" />',
			'S_USER_FLD_3' => _PNUSRFLD3,
			'T_USER_FLD_3' => '<input type="text" name="user_fld_3" size="50" value="'.$usrfld3.'" />',
			'S_USER_FLD_4' => _PNUSRFLD4,
			'T_USER_FLD_4' => '<input type="text" name="user_fld_4" size="50" value="'.$usrfld4.'" />',
			'S_USER_FLD_5' => _PNUSRFLD5,
			'T_USER_FLD_5' => '<input type="text" name="user_fld_5" size="50" value="'.$usrfld5.'" />',
			'S_USER_FLD_6' => _PNUSRFLD6,
			'T_USER_FLD_6' => '<input type="text" name="user_fld_6" size="50" value="'.$usrfld6.'" />',
			'S_USER_FLD_7' => _PNUSRFLD7,
			'T_USER_FLD_7' => '<input type="text" name="user_fld_7" size="50" value="'.$usrfld7.'" />',
			'S_USER_FLD_8' => _PNUSRFLD8,
			'T_USER_FLD_8' => '<input type="text" name="user_fld_8" size="50" value="'.$usrfld8.'" />',
			'S_USER_FLD_9' => _PNUSRFLD9,
			'T_USER_FLD_9' => '<input type="text" name="user_fld_9" size="50" value="'.$usrfld9.'" />',
			'T_KEY_USR_FLD' => select_box('key_usr_fld', $keyusrfld, array(0=>'0 '.$usrfld0.($usrfld0 == '' ? ' ('._PNDEFLT.')' : ''),1=>'1 '.$usrfld1,2=>'2 '.$usrfld2,3=>'3 '.$usrfld3,4=>'4 '.$usrfld4,5=>'5 '.$usrfld5,6=>'6 '.$usrfld6,7=>'7 '.$usrfld7,8=>'8 '.$usrfld8,9=>'9 '.$usrfld9)),
			'L_ADVANCED' => _PNADVANCED,
			'L_SEC_WARN' => _PNSECWARN,
			'G_ID' => (!is_null($id)) ? '<input type="hidden" name="id" value="'.$id.'" />' : '',
			'G_SAVE' => '<input type="submit" name="submit" value="'.$submit.'" />',
			'G_STARTFORM' => open_form(adminlink("&amp;mode=sec&amp;do=".$dosave),'secform',$btitle),
			'G_ENDFORM' => close_form()
		);
// echo 'kuf='.$keyusrfld;
		return $tempform;
	}

	function admin_forumlist($forum_id, $cat, $lbl_0) {
		global $db, $prefix, $forum_module;
		$sql = "SELECT f.* FROM ".$prefix."_bbforums f, ".$prefix."_bbcategories c";
		$sql .= " WHERE c.cat_id = f.cat_id ORDER BY c.cat_order ASC, f.forum_order ASC";
		$result = $db->sql_query($sql);
		$forum_rows = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);
		$result = $db->sql_uquery("SELECT cat_id, cat_title FROM ".$prefix."_bbcategories ORDER BY cat_order");
		while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
			$category_rows[$row[0]] = $row[1];
		}
		if (count($forum_rows) > 0) {
			$cat_id = 0;
			$select_list = '<select name="forum_id"><option value="0">'.$lbl_0.'</option>';
			if ($cat) {
				if ($forum_id == '-1') {
					$select_list .= '<option value="-1" selected="selected">';
				} else {
					$select_list .= '<option value="-1">';
				}
				$select_list .= _PNFRMNONE.'</option>';
			}
			for ($i = 0; $i < count($forum_rows); $i++) {
				if ($cat_id != $forum_rows[$i]['cat_id']) {
					if ($cat_id > 0) $select_list .= '</optgroup>';
						$cat_id = $forum_rows[$i]['cat_id'];
						$select_list .= '<optgroup label="'.$category_rows[$forum_rows[$i]['cat_id']].'">';
				}
				$selected = ($forum_rows[$i]['forum_id'] == $forum_id) ? 'selected="selected"' : '';
				$select_list .= '<option value="'.$forum_rows[$i]['forum_id'].'"'.$selected.'>' . $forum_rows[$i]['forum_name'].'</option>';
			}
			$select_list .= '</optgroup></select>';
		} else {
			$select_list = _PNNOFRMS;
		}
		return $select_list;
	}

	function admin_forumsprolist($forumspro_prefix, $forumspro_id, $cat, $lbl_0) {
		global $db, $prefix, $forum_module;
		$forumspro_prefix = $prefix.'_'.$forumspro_prefix.'_';
		$sql = "SELECT f.* FROM ".$forumspro_prefix."forums f, ".$forumspro_prefix."categories c";
		$sql .= " WHERE c.cat_id = f.cat_id ORDER BY c.cat_order ASC, f.forum_order ASC";
		$result = $db->sql_query($sql);
		$forumspro_rows = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);
		$result = $db->sql_uquery("SELECT cat_id, cat_title FROM ".$forumspro_prefix."categories ORDER BY cat_order");
		while ($row = $db->sql_fetchrow($result, SQL_NUM)) {
			$category_rows[$row[0]] = $row[1];
		}
		if (count($forumspro_rows) > 0) {
			$cat_id = 0;
			$forumspro_select_list = '<select name="forum_id">';
			if ($lbl_0 != '') {$forumspro_select_list .= '<option value="0">'.$lbl_0.'</option>';}
			if ($cat) {
				if ($forum_id == '-1') {
					$forumspro_select_list .= '<option value="-1" selected="selected">';
				} else {
					$forumspro_select_list .= '<option value="-1">';
				}
				$forumspro_select_list .= _PNFRMNONE.'</option>';
			}
			for ($i = 0; $i < count($forumspro_rows); $i++) {
				if ($cat_id != $forumspro_rows[$i]['cat_id']) {
					if ($cat_id > 0) $forumspro_select_list .= '</optgroup>';
						$cat_id = $forumspro_rows[$i]['cat_id'];
						$forumspro_select_list .= '<optgroup label="'.$category_rows[$forumspro_rows[$i]['cat_id']].'">';
				}
				$forumspro_selected = ($forumspro_rows[$i]['forum_id'] == strtolower($forumspro_id)) ? 'selected="selected"' : '';
				$forumspro_select_list .= '<option value="'.$forumspro_rows[$i]['forum_id'].'"'.$forumspro_selected.'>' . $forumspro_rows[$i]['forum_name'].'</option>';
			}
			$forumspro_select_list .= '</optgroup></select>';
		} else {
			$forumspro_select_list = _PNNOFRMS;
		}
		return $forumspro_select_list;
	}

	function admin_seccat($name,$id='',$onchange=true,$selecttext,$pending=false) {
		global $db, $prefix;
		$sql = 'SELECT s.title stitle, c.title, c.id FROM '.$prefix.'_pronews_sections as s,';
		$sql .= ' '.$prefix.'_pronews_cats as c WHERE c.sid=s.id ORDER BY s.sequence, c.sequence';	// changed outer join to inner join to handle empty Section - layingback 061218

		$result = $db->sql_query($sql);
		$list = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);
		$seccat = 'none';
		if (($list) && ($list != '')) {
			foreach ($list as $row) {
				$list2[$row['stitle']][$row['title']][] = $row['id'];
			}
// echo '<br />'.$pending.'<pre>';print_r($list2);echo '</pre>';
			$selected = ($id != '') ? $id : '';
			$seccat = '<select '.($onchange ? 'onchange="this.form.submit()" ' : '').'name="'.$name.'"'.'><option value="">-- '.($selecttext != '' ? $selecttext : _PNSELONE).' --</option>';
			$pending_arts = '';
			foreach ($list2 as $row => $value) {
				$seccat .= '<optgroup label="'.$row.'">';
				foreach ($value as $op => $tid) {
						if ($pending) {
							$result = $db->sql_query('SELECT * FROM '.$prefix.'_pronews_articles WHERE catid='.$tid['0'].' AND approved="0" AND active="1"');
							$pending_arts = $db->sql_numrows($result);
							$db->sql_freeresult($result);
							$pending_arts = ($pending_arts == 0) ? '' : '*&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[ '.$pending_arts.' ]';  // indicate # of Pending Arts in Cat - layingback 061129
						}
						$select = ($tid['0'] == $selected) ? ' selected="selected"' : '';
						$seccat .= '<option value="'.$tid['0'].'"'.$select.'>'.$op.' '.$pending_arts.'</option>';
				}
				$seccat .= '</optgroup>';
			}
			$seccat .= '</select>';
		}

		return $seccat;
	}

	function admin_article_form($edit='',$cat='') {
		global $db, $prefix, $pnsettings, $userinfo, $cpgtpl, $multilingual, $CPG_SESS;

		if ($edit == '' && $cat == '') {
			$categories = ProNewsAdm::admin_seccat('cat','',true,'','');
			$cpgtpl->assign_block_vars('newarticle_form', array(
				'S_MSG' => _PNSELECTCAT,
				'S_CAT' => _PNCAT,
				'T_CAT' => $categories.'<noscript><input type="submit" value="'._PNGO.'" /></noscript>',
				'S_FORMSTART' => open_form(adminlink("&amp;mode=add&amp;do=new"),'addstory','&nbsp;'._PNADDSTORY.'&nbsp;'),
				'S_FORMEND' => close_form()
			));
			$cpgtpl->set_filenames(array('body' => 'pronews/submit.html'));
			$cpgtpl->display('body');
		} else {
			$options = array(0=>_PNDONORMAL, 1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8, 9=>9, 10=>_PNDOTOPRANK);
				$sql = 'SELECT template, usrfld0, usrfld1, usrfld2, usrfld3, usrfld4, usrfld5, usrfld6, usrfld7, usrfld8, usrfld9, usrfldttl, c.forum_id cforum_id, s.forum_id sforum_id';
				$sql .= ' FROM '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_sections as s';
			if ($edit == '') {
				$preview = '';
				$edit = array('id'=>'', 'title'=>'', 'seod'=>'', 'allow_comment'=>'', 'catid'=>$cat,'topic'=>'', 'content'=>'', 'image'=>'', 'intro'=>'', 'caption'=>'', 'display_order'=>'', 'alanguage'=>'', 'album_id'=>'', 'album_cnt'=>'', 'album_seq'=>'', 'slide_show'=>'', 'gallery'=>'', 'image2'=>'', 'caption2'=>'', 'user_fld_0'=>'', 'user_fld_1'=>'', 'user_fld_2' => '', 'user_fld_3'=>'', 'user_fld_4'=>'', 'user_fld_5'=>'', 'user_fld_6' => '', 'user_fld_7' => '', 'user_fld_8' => '', 'user_fld_9' => '', 'usrfld0'=>'', 'usrfld1'=>'', 'usrfld2'=>'', 'usrfld3'=>'', 'usrfld4'=>'', 'usrfld5'=>'', 'usrfld6'=>'', 'usrfld7'=>'', 'usrfld8'=>'', 'usrfld9'=>'', 'clsdttime'=>'', 'cledttime'=>'', 'sdttime'=>'', 'edttime'=>'', 'cal_id'=>'', 'associated'=>'', 'display'=>'1');
				$sql .= ' WHERE c.sid=s.id AND c.id="'.$cat.'"';
			} else {
				$preview = '1';
				$sql .= ' WHERE c.sid=s.id AND c.id="'.$edit['catid'].'"';
			}
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
				$usrflds_prsnt = $list['usrfld0'].$list['usrfld1'].$list['usrfld2'].$list['usrfld3'].$list['usrfld4'].$list['usrfld5'].$list['usrfld6'].$list['usrfld7'].$list['usrfld8'].$list['usrfld9'];
			}
			$relcat = isset($_POST['relcat']) ? intval($_POST['relcat']) : '';
			// Related Articles, modified by Masino Sinaga, June 22, 2009
 			$assotop = isset($_POST['assotop']) ? $_POST['assotop'] : explode(',', $edit['associated']);	// Masino Sinaga, June 18, 2009
// echo '<br> p0_assotop=';print_r($assotop);
//			if ($assotop == false) {
//				$assotop = explode(',', $edit['associated']); // Modified by Masino Sinaga, June 18, 2009
//			}
// echo '<br> p1_assotop=';print_r($assotop);
// echo '<br> p1_associated=';print_r($edit['associated']);
			// Related Articles, modified by Masino Sinaga, June 22, 2009

			$tpl = ($edit['template'] != '') ? $edit['template'] : $pnsettings['template'];
			if ($edit['image'] != '') {
				$imagesize = getimagesize($pnsettings['imgpath'].'/'.$edit['image']);
				$thumb = ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) ? '/thumb_' : $thumb = '/';
			}
			if ($edit['image2'] != '') {
				$imagesize = getimagesize($pnsettings['imgpath'].'/'.$edit['image2']);
				$thumb2 = ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) ? '/thumb_' : $thumb2 = '/';
			}
			$comments = ($edit['allow_comment'] == '' || $list['cforum_id'] == '-1' || ($list['cforum_id'] == '0' && $list['sforum_id'] == '0')) ? '' : $pnsettings['comments'];
 			if ($pnsettings['topic_lnk'] > 0) {
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

			$categories = ProNewsAdm::admin_seccat('cat',$edit['catid'],false,'','');
			$albums = ProNewsAdm::admin_album('album_id', $edit['album_id']);

			$clsdttime = intval($edit['clsdttime']);
			$cledttime = intval($edit['cledttime']);
//			$clwhlday = (ProNewsAdm::pndate('H',$clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']) == 0 && ProNewsAdm::pndate('i',$clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']) == 0 && ProNewsAdm::pndate('H',$cledttime, $userinfo['user_dst'], $userinfo['user_timezone']) == 0 && ProNewsAdm::pndate('i',$cledttime, $userinfo['user_dst'], $userinfo['user_timezone']) == 0) ? 1 : 0;
			$clwhlday = ($clsdttime == $cledttime || $cledttime == 0 || (ProNewsAdm::pndate('H',$clsdttime, 0, 0) == 0 && ProNewsAdm::pndate('i',$clsdttime, 0, 0) == 0 && ProNewsAdm::pndate('H',$cledttime, 0, 0) == 0 && ProNewsAdm::pndate('i',$cledttime, 0, 0) == 0)) ? true : false;
// echo '<br /> cal stime='.$clsdttime.' cal etime='.$cledttime.' cal wholeday='.$clwhlday;

			$sdttime = intval($edit['sdttime']);
			$edttime = intval($edit['edttime']);

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
				$checked = ($pnsettings['related_arts'] >= '1') ? 'checked="checked"' : 'checked="checked" READONLY';
				$result = $db->sql_query('SELECT id, title, posttime, postby FROM '.$prefix.'_pronews_articles WHERE id IN ('.$edit['associated'].')');
				while ($rowa = $db->sql_fetchrow($result)) {
					$datestory = formatDateTime($rowa['posttime'], _DATESTRING);
//					$gettitle = $rowa['title'];
//					if ($title != $gettitle) {
//						$title = $gettitle;
//					}
					$assoc .= '<input type="checkbox" name="assotop[]" value="'.$rowa['id'].'" '.$checked.' /> <a href="'.getlink("Pro_News&amp;aid=".$rowa['id']."&amp;title=".$rowa['title']).'">'.$rowa['title'].'</a>, <span class="pn_tinygrey">'.$datestory.'</span><br />';
				}
				$db->sql_freeresult($result);
//			} else {
//				$assoc = '<span class="pn_tinygrey">('._PNRELNONE.')</span>';
			}

			//Begin of Related Articles, modified by Masino Sinaga, June 22, 2009
			$assarticle = '';
			if ($pnsettings['related_arts'] >= '1') {
				if ($relcat != '') {
					$result = $db->sql_query("SELECT id, title FROM ".$prefix."_pronews_articles WHERE id<>'".$edit['id']."' AND catid='".$relcat."' ORDER BY id");
					while ($ass = $db->sql_fetchrow($result)) {
						$checked = (empty($assotop)) ? '' : (in_array($ass['id'], $assotop) ? ' checked="checked" disabled="disabled"' : '');
						$assarticle .= "<tr><td></td><td><input type=\"checkbox\" name=\"assotop[]\" value=\"$ass[id]\"$checked /> <a href=\"".getlink("Pro_News&amp;aid=$ass[id]")."\" target=\"_blank\">$ass[title]</a></td></tr>";
					}
				}
			}
// echo '<table>'.$assarticle.'</table>';
			//End of Related Articles, modified by Masino Sinaga, June 22, 2009

			$cpgtpl->assign_block_vars('article_form', array(
				'G_PREVIEW' => ($preview == '') ? '1' : '1',
				'S_TITLE' => _PNTITLE,
				'T_TITLE' => '<input type="text" name="title" size="50" value="'.$edit['title'].'" />',
				'S_SEOD' => _PNSEOD,
				'T_SEOD' => '<input type="text" name="seod" size="60" value="'.$edit['seod'].'" />'.'&nbsp;<span class="pn_tinygrey">('._PNOPTIONAL.')</span>',
				'G_ADMIN' => '1',
				'G_IMAGE' => '1',
				'G_DISCUSS' => '1',
// line below can be used to hide comments selection box if discuss is disabled by current Category, Section & Config options
//				'G_DISCUSS' => ($pnsettings['comments'] == '1') ? '1' : '',
				'S_DSPLYORDER' => _PNDSPLYORDER,
				'T_DSPLYORDER' => select_box('display_order', $edit['display_order'], $options),
				'S_DISPLAY' => _PNDSPLYHME,
				'T_DISPLAY' => select_box('display', $edit['display'], array(1=>_PNYES, 0=>_PNNO, 2=>_PNHMONLY)),
				'S_ALLCOM' => _PNALCOMMENTS,
				'T_ALLCOM' => yesno_option('comments', $edit['allow_comment']),
// line below can be used to force article discuss settings to be stored as per current Category, Section & Config options
//				'T_ALLCOM' => yesno_option('comments',$comments),
				'G_TOPIC' => ($topics) ? '1' : '',
				'S_TOPIC' => _TOPIC,
				'T_TOPIC' => $topics.'&nbsp;<span class="pn_tinygrey">('._PNOPTIONAL.')</span>',
				'G_LANG' => ($multilingual) ? '1' : '',
				'S_LANG' => _PNLANG,
				'T_LANG' => lang_selectbox($edit['alanguage']),
				'S_INTRO' => _PNINTRO,
				'L_INTRO' => bbcode_table('intro', 'adminbbadd', 1),
				'T_INTRO' => '<textarea cols="66" rows="15" name="intro" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);">'.$edit['intro'].'</textarea>',
				'T_INTROS' => ($pnsettings['show_smilies']) ? smilies_table('inline', 'intro', 'adminbbadd') : '',
				'S_IMAGE' => ($edit['image'] != '') ? _PNDELIMAGE.'<br /><input type="checkbox" name="delimage" value="" />' : _IMAGE,
				'T_IMAGE' => ($edit['image'] != '') ? '<input type="hidden" name="image" value="'.$edit['image'].'" /><img src="'.$pnsettings['imgpath'].$thumb.$edit['image'].'" alt=""/>' : '<input type="file" name="iname" size="35" />',
				'S_IMGIMAGE' => $pnsettings['imgpath'].'/'.$edit['image'],
				'G_IMAGEDISPLAY' => ($edit['image'] != '') ? '1' : '',
				'S_CAP' => _PNIMGCAP,
				'T_CAP' => '<input type="text" name="imgcap" size="50" value="'.$edit['caption'].'" />&nbsp;<span class="pn_tinygrey">('._PNOPTIONAL.')</span>',
				'S_STORY' => _PNSTORY,
				'L_STORY' => bbcode_table('addtext', 'adminbbadd', 2),
				'T_STORY' => '<textarea cols="66" rows="15" name="addtext" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);" onchange="storeCaret(this);">'.$edit['content'].'</textarea>',
				'T_STORYS' => ($pnsettings['show_smilies']) ? smilies_table('inline', 'addtext', 'adminbbadd') : '',
				'S_IMAGE2' => ($edit['image2'] != '') ? _PNDELIMAGE.'<br /><input type="checkbox" name="delimage2" value="" />' : _IMAGE,
				'T_IMAGE2' => ($edit['image2'] != '') ? '<input type="hidden" name="image2" value="'.$edit['image2'].'" /><img src="'.$pnsettings['imgpath'].$thumb2.$edit['image2'].'" alt=""/>' : '<input type="file" name="iname2" size="35" />',
				'S_IMGIMAGE2' => $pnsettings['imgpath'].'/'.$edit['image2'],
				'G_IMAGE2DISPLAY' => ($edit['image2'] != '') ? '1' : '',
				'S_CAP2' => _PNIMGCAP,
				'T_CAP2' => '<input type="text" name="imgcap2" size="50" value="'.$edit['caption2'].'" />&nbsp;<span class="pn_tinygrey">('._PNOPTIONAL.')</span>',

				'G_CALENDAR' => ($pnsettings['cal_module']) ? '1' : '',
				'S_CALSDTTIME' => _PNEVENT.' '._PNCALSTART.'<input type="hidden" name="calid" value="'.(($edit['cal_id']) ? $edit['cal_id'] : 0).'" />',
//				'T_CALSDTTIME' => ($clsdttime) ? (($clwhlday) ? ProNewsAdm::dttime_edit('c', $clsdttime, true, true) : ProNewsAdm::dttime_edit('c', $clsdttime, true, true)) : ProNewsAdm::dttime_edit('c', '', true, true),
				'T_CALSDTTIME' => ($clwhlday) ? ProNewsAdm::dttime_edit('c', $clsdttime, true, true, true) : ProNewsAdm::dttime_edit('c', $clsdttime, true, true),
				'S_CALEDTTIME' => _PNEVENT.' '._PNCALEND,
//				'T_CALEDTTIME' => ($cledttime) ? (($clwhlday) ? ProNewsAdm::dttime_edit('d', $cledttime, true, true) : ProNewsAdm::dttime_edit('d', $cledttime, true, true)) : ProNewsAdm::dttime_edit('d', '', true, true),
				'T_CALEDTTIME' => ($clwhlday) ? ProNewsAdm::dttime_edit('d', $cledttime, true, true, true) : ProNewsAdm::dttime_edit('d', $cledttime, true, true),
//				'S_CALEDTTIME' => _PNEVENT.' '._PNDURATION,
//				'T_CALEDTTIME' => _PNDAYS.' '.select_option('cletime',$edit['cletime'],array(0=>'',1=>'1',2=>'2',3=>'3',4=>'4',5=>'5',6=>'6',7=>'7')).' &nbsp; <i><span class="pn_tinygrey">'._PNOR.'</span></i> &nbsp;'.ProNewsAdm::dttime_edit('d', $edit['cletime'], true, true),

				'G_ALBUM' => ($pnsettings['show_album']) ? '1' : '',
				'S_ALBUM' => _PNALBUM,
				'T_ALBUM' => $albums,
				'S_ALBCNT' => _PNALBCNT,
				'T_ALBCNT' => select_option('album_cnt',$edit['album_cnt'],array(0=>'0',1=>'1',2=>'2',3=>'3',4=>'4',5=>'5',6=>'6',7=>'7',8=>'8',9=>'9',10=>'10',11=>'11',12=>'12',13=>'13',14=>'14',15=>'15',16=>'16',17=>'17',18=>'18',19=>'19',20=>'20',21=>'21',22=>'22',23=>'23',24=>'24',25=>'25',26=>'26',27=>'27',28=>'28',29=>'29',30=>'30',31=>'31',32=>'32')),
				'L_ALBCNT' => _PNFRSTPGA,
				'S_ALBSEQ' => _PNALBSEQ,
				'T_ALBSEQ' => select_box('album_seq',$edit['album_seq'],array(0=>_PNDEFAULT,1=>_PNTTLASC,2=>_PNTTLDSC,3=>_PNFLNASC,4=>_PNFLNDSC,5=>_PNDLASC,6=>_PNDLDSC,7=>_PNRATASC,8=>_PNRATDSC)),
				'S_SLDSHW' => _PNSLDSHOW,
				'T_SLDSHW' => yesno_option('slide_show',($edit['slide_show'] & 1)),
				'S_GLLRY' => _PNADDLPGS,
				'T_GLLRY' => yesno_option('gallery',($edit['slide_show'] > 1)),
				'G_ID' => ($edit['id'] != '') ? '<input type="hidden" name="id" value="'.$edit['id'].'" />' : '',
				'G_SAVE' => '<input type="submit" value="'._PNPREVIEW.'" />&nbsp;&nbsp;<input type="submit" name="submitart" value="'._PNSAVEART.'" />',
				'S_CAT' => _PNCAT,
				'T_CAT' => $categories.'<noscript><input type="submit" value="'._PNCHANGE.'" /></noscript> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="submit" value="'._PNPREVIEW.'" />',
				'L_CAT' => $edit['catid'],
				'G_FIELDS' => ($pnsettings['show_usrflds'] && $usrflds_prsnt != '') ? '1' : '',
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
				'G_ASSC' => ($pnsettings['related_arts'] >= '1' || $edit['associated']) ? '1' : '',
				'L_ASSC'  => _PNRELATEDART, // Related Articles, modified by Masino Sinaga, June 22, 2009
				'L_LSTLNKS'  => _PNLSTLNKS,
				'T_ALRDYASSOC' => $assoc,
				'L_SEL_ASSC'  => ($pnsettings['related_arts'] >= '1' || $edit['associated']) ? _PNSELRELATEDART : '',
				'T_RLDTCAT' => ($pnsettings['related_arts'] >= '1' || $edit['associated']) ? ProNewsAdm::admin_seccat('relcat',$relcat,false,'','').'<noscript><input type="submit" name="dsply" value="'._PNDISPLAY.'" /></noscript>' : '',
				'L_INCL_ASSC'  => ($pnsettings['related_arts'] >= '1' || $edit['associated']) ? _PNINCL.' ' : '',
				'S_INCL_ASSC'  => ($pnsettings['related_arts'] >= '1' || $edit['associated']) ? select_box('inclnum', $inclnum, array(0=>_PNNO, 99=>_PNALL, 12=>_PNLAST.' 12', 11=>_PNLAST.' 11', 10=>_PNLAST.' 10', 6=>_PNLAST.' 6', 5=>_PNLAST.' 5')).' '._PNINCLASSOC : '',
				'T_INCL_CATSEC' => ($pnsettings['related_arts'] >= '1' || $edit['associated']) ? select_box('inclcatsec', $inclcatsec, array(0=>_PNRALCAT, 1=>_PNRALSEC, 2=>_PNRALUSER, 3=>_PNRALUSRSEC)) : '',
 				'S_ASSC'  => ($pnsettings['related_arts'] >= '1' || $edit['associated']) ? $assarticle : '',
				'S_STARTDTTIME' => _PNSTART.' '._PNFORART,
				'T_STARTDTTIME' => ($edit['sdttime']) ? ProNewsAdm::dttime_edit('s', $sdttime) : ProNewsAdm::dttime_edit('s', (time() - 60)),
				'S_ENDDTTIME' => _PNEND.' '._PNFORART,
				'T_ENDDTTIME' => ($edit['edttime']) ? ProNewsAdm::dttime_edit('e', $edttime) : ProNewsAdm::dttime_edit('e', (time() - 60)),
				'L_CALENDAR' => _PNCALENDAR,
				'L_ALBUM' => _PNPHOTOALBUM,
				'L_FIELDS' => _PNUSERFIELDS,
				'L_SCHED' => _PNARTSCHED,
				'L_SEL_CAL' => _PNSELECTDATE,
				'L_SEL_ALB' => _PNSELECTALBUM,
				'L_SEL_FLDS' => _PNENTERFIELDS,
				'L_SEL_SCHED' => _PNSELECTSCHED,
				'S_FORMSTART' => ($edit['id'] != '') ? open_form(adminlink("&amp;mode=add&amp;do=save"),'adminbbadd','&nbsp;'._PNEDITSTORY.'&nbsp;') : open_form(adminlink("&amp;mode=add&amp;do=save"),'adminbbadd','&nbsp;'._PNADDSTORY.'&nbsp;'),
				'S_FORMEND' => close_form()
			));
			$tpl = ($edit['template'] != '') ? $edit['template'] : $pnsettings['template'];
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

	function admin_trunc($string,$repl,$limit) {
		if(strlen($string) > $limit) {
			return substr_replace(strip_tags($string),$repl,$limit-strlen($repl));
		} else {
			return $string;
		}
	}

	function addbb($addtext,$bbform) {
		$add_bbtable = bbcode_table('addtext', $bbform, 1);
		echo '<br />'.$add_bbtable.'<textarea cols="80" rows="15" name="addtext">'.htmlprepare($addtext).'</textarea>';
	}

	function smilies($field='',$bbform) {
		$pn_smilie = smilies_table('inline', $field, $bbform);
		echo $pn_smilie;
	}

	function admin_menu() {
		global $cpgtpl;
		$cpgtpl->assign_block_vars('menu', array(
			'S_MAIN' => _PNMAIN,
			'U_MAIN' => adminlink(),
			'S_ADD' => _PNADDSTORY,
			'U_ADD' => adminlink("&amp;mode=add"),
			'S_ARTS' => _PNARTICLES,
			'U_ARTS' => adminlink("&amp;mode=list"),
			'S_GLRY' => _PNGLRY,
			'U_GLRY' => adminlink("&amp;mode=glry"),
			'S_UPLD' => _PNUPLOAD,
			'U_UPLD' => adminlink("&amp;mode=upld"),
			'S_SECS' => _PNSECTIONS,
			'U_SECS' => adminlink("&amp;mode=sec"),
			'S_CATS' => _PNCATS,
			'U_CATS' => adminlink("&amp;mode=cat"),
			'S_BLKS' => _PNBLOCKS,
			'U_BLKS' => adminlink("&amp;mode=blk"),
			'S_CFG' => _PNCONFIG,
			'U_CFG' => adminlink("&amp;mode=cfg")
		));
	}

	function admin_index() {
		global $db, $prefix, $cpgtpl;
		$pending = $db->sql_numrows($db->sql_query('SELECT * FROM '.$prefix.'_pronews_articles WHERE approved="0" AND active="1"'));
		$articles = $db->sql_numrows($db->sql_query('SELECT * FROM '.$prefix.'_pronews_articles'));
		$categories = $db->sql_numrows($db->sql_query('SELECT * FROM '.$prefix.'_pronews_cats'));
		$sections = $db->sql_numrows($db->sql_query('SELECT * FROM '.$prefix.'_pronews_sections'));
		$msg = '<div style="width:40%"><div style="margin:0 auto"><span style="float:left;font-weight:bold">'._PNPENDINGARTS.'</span><span style="float:right;font-weight:bold">'.$pending.'</span><br /><span style="float:left">'._PNTOTALARTS.'</span><span style="float:right">'.$articles.'</span><br /><span style="float:left">'._PNTOTALCATS.'</span><span style="float:right">'.$categories.'</span><br /><span style="float:left">'._PNTOTALSECS.'</span><span style="float:right">'.$sections.'</span></div></div>';
		$cpgtpl->assign_block_vars('main', array(
			'S_MSG' => $msg
		));
	}

	function get_templates() {
		global $CPG_SESS;
		if (file_exists('themes/'.$CPG_SESS['theme'].'/template/pronews/article')) {
			$dir = dir('themes/'.$CPG_SESS['theme'].'/template/pronews/article');
		} elseif (file_exists('themes/default/template/pronews/article')) {
			$dir = dir('themes/default/template/pronews/article');
		} else {
			$templatelist[] = ('!ERROR - No templates found!'); return $templatelist;
		}
		while($func=$dir->read()) {
			if (ereg('(.*).html$', $func, $matches)) {
			$templatelist[] = $func;
			}
		}
		$dir->close();
		sort($templatelist);
		return $templatelist;
	}

	function admin_activefpros($name) {
		global $db, $prefix, $active_modules;

		$result = $db->sql_query("SHOW TABLES LIKE '".$prefix."\_%\_forums'");
		$forumspros = array();
		while (list($tblname) = $db->sql_fetchrow($result)) {
			$abbrv = preg_replace("#^($prefix)_#", '', $tblname);
			$abbrv = preg_replace("#_forums#", '', $abbrv);
			if ($abbrv == 'fpro') {$abbrv = 'forumspro';}
			$forumspros[$abbrv] = $abbrv;
		}
		$db->sql_freeresult($result);

		$actmods['1'] = 'Forum (CPG-BB)';
		foreach ($active_modules as $key => $row) {
			if (in_array(strtolower($key), $forumspros)) {
				if ($key == 'ForumsPro') {
					$actmods['fpro'] = $key;
				} else {$actmods[$key] = $key;}
			}
		}
		$possmods = select_box('forum_name', $name, $actmods);
		return $possmods;
	}

	function admin_album($name,$id='') {
		global $db, $prefix, $module_name;
		$albnum = '10000' + is_user();
		$sql = 'SELECT a.title atitle, a.aid aid, c.catname catname FROM '.$prefix.'_cpg_albums as a LEFT JOIN '.$prefix.'_cpg_categories as c ON a.category = c.cid';
		$sql .= ' WHERE a.category='.$albnum;
		if (can_admin($module_name) && can_admin("coppermine")) {
			$sql .= ' OR a.category<1000';
//			$sql .= ' OR a.category>1000';		/* To allow Administrator access to ALL User Albums remove the // at beginning of this line */
		}
		$sql .= ' ORDER BY catname, atitle';
		$result = $db->sql_query($sql);
		$list = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);
		$album = 'none';
		$lastcatname = '';
		if (($list) && ($list != '')) {
			$selected = ($id != '') ? $id : '';
			$album = '<select name="'.$name.'"><option value="">-- '._PNSELONE.' --</option>';
				foreach ($list as $value) {
					if ($value['catname'] != $lastcatname) {
						if ($lastcatname != '') {
							$album .= '</optgroup>';
						}
						$album .= '<optgroup label="'.$value['catname'].'">';
					}
					$select = ($value['1'] == $selected) ? ' selected="selected"' : '';
					$album .= '<option value="'.$value['1'].'"'.$select.'>'.$value['0'].'</option>';
					$lastcatname = $value['catname'];
				}
			$album .= '</optgroup></select>';
		}
		return $album;
	}

	function admin_displayimage($image,$target) {
		global $pnsettings;
		if($image != '') {
			$imagesize = getimagesize($pnsettings['imgpath'].'/'.$image);
			$imagesizeX = $imagesize[0] + 16; $imagesizeY = $imagesize[1] + 16;
			if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
				$thumbimage = $pnsettings['imgpath'].'/thumb_'.$image;
				$display_image = '<a href="'.$pnsettings['imgpath'].'/'.$image.'" target="'.$target.'"
onclick="PN_openBrWindow(\''.$pnsettings['imgpath'].'/'.$image.'\',\'' . $target . '\',\'resizable=yes,scrollbars=yes,width='.$imagesizeX.',height='.$imagesizeY.'\',\''.$imagesizeX.'\',\''.$imagesizeY.'\');return false;"><img class="pn_thumb" src="'.$thumbimage.'" alt="" /></a>';
			} else {
				$thumbimage = $pnsettings['imgpath'].'/'.$image;
				$display_image = '<img class="pn_thumb" src="'.$thumbimage.'" alt="" />';
			}
		} else {$display_image = '';}
		return $display_image;
	}

	function dttime_edit($s, $gmttime, $hropt='', $blank='', $whlday='') {
		global $userinfo, $pnsettings;
// echo '<br /> gmt='.$gmttime.' dst='.$userinfo['user_dst'].' tz='.$userinfo['user_timezone'].' hr='.ProNewsAdm::pndate('H',$gmttime, 0, $userinfo['user_timezone']).' indst='.L10NTime::in_dst($gmttime, $userinfo['user_dst']).' dsthour='.ProNewsAdm::pndate('H',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
		$calpopup = '';
		if (file_exists('modules/Pro_News/includes/calendarpopup.js')) {
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
	document.forms[\'adminbbadd\'].'.$s.'year.options[document.forms[\'adminbbadd\'].'.$s.'year.selectedIndex].text=y;
	document.forms[\'adminbbadd\'].'.$s.'month.selectedIndex=m'.($s=='s' || $s == 'e' ? '-1' : '').'; // only non-Calendar dates need -1 ???
	for (var i = 0; i < document.forms[\'adminbbadd\'].'.$s.'day.options.length; i++ ) {
		if (document.forms[\'adminbbadd\'].'.$s.'day.options[i].text==d) {
			document.forms[\'adminbbadd\'].'.$s.'day.selectedIndex=i;
		}
	}
}
			</script>';
		}

		$separater = (!$hropt) ? '&nbsp;' : '&nbsp; <span class="pn_tinygrey">|</span> ';
		$content = '';
		$content .= '<span class="pn_tiny">'._DAY.'</span> <select name="'.$s.'day">';
		$content .= ($blank) ? '<option>&nbsp;</option>' : '';
		$target_day = ProNewsAdm::pndate('d',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
		for($i = 1; $i <= 31; $i++) {
			$k = ($i < 10) ? "0$i" : $i;
			$content .= '<option'.(($i == $target_day) ? ($gmttime) ? ' selected="selected"' : '' : '').'>'.$k.'</option>';
		}
		$content .= '</select> <span class="pn_tiny">'._UMONTH.'</span> <select name="'.$s.'month">';
		$content .= ($blank) ? '<option selected="selected">&nbsp;</option>' : '';
		$target_mnth = ProNewsAdm::pndate('m',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
		for ($i = 1; $i <= 12; $i++) {
			$k = ($i < 10) ? "0$i" : $i;
			$content .= '<option'.(($i == $target_mnth) ? ($gmttime) ? ' selected="selected"' : '' : '').'>'.$k.'</option>';
		}
		$content .= '</select> <span class="pn_tiny">'._YEAR.'</span> <select name="'.$s.'year">';
		$content .= ($blank) ? '<option selected="selected">&nbsp;</option>' : '';
		$target_yr = ProNewsAdm::pndate('Y',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
		$beg_yr = ($blank) ? (ProNewsAdm::pndate('Y', time()))-4 : $target_yr-4;
		$end_yr = $beg_yr + 15;
		for ($i = $beg_yr; $i <= $end_yr; $i++) {
			$content .= '<option'.(($i == $target_yr) ? ($gmttime) ? ' selected="selected"' : '' : '').'>'.$i.'</option>';
		}
		$content .= '</select> &nbsp;';
		if ($calpopup != '') {
			if ($pnsettings['cal_module']) {
				if ($s == 'c' || $s == 's') {
					$content .= '<a href="javascript:cal'.$s.'.showCalendar(\'anchor'.$s.'\',getDateString(document.forms[\'adminbbadd\'].'.$s.'year,document.forms[\'adminbbadd\'].'.$s.'month,document.forms[\'adminbbadd\'].'.$s.'day));" name="anchor'.$s.'" id="anchor'.$s.'"><img src="modules/CPGNuCalendar/images/calendar.gif" style="border: 0pt none; width: 20px;" alt="'._PNLSELCAL.'..." title="'._PNLSELCAL.'..." /></a> ';
				} else {
					$content .= '<a href="javascript:var%20d=getDateString(document.forms[\'adminbbadd\'].'.$s.'year,document.forms[\'adminbbadd\'].'.$s.'month,document.forms[\'adminbbadd\'].'.$s.'day);cal'.$s.'.showCalendar(\'anchor'.$s.'\',(d==null)?getDateString(document.forms[\'adminbbadd\'].cyear,document.forms[\'adminbbadd\'].cmonth,document.forms[\'adminbbadd\'].cday):d);" name="anchor'.$s.'" id="anchor'.$s.'"><img src="modules/CPGNuCalendar/images/calendar.gif" style="border: 0pt none; width: 20px;" alt="'._PNLSELCAL.'..." title="'._PNLSELCAL.'..." /></a> ';
				}
			} else {
				if ($s == 's') {
					$content .= '<a href="javascript:cal'.$s.'.showCalendar(\'anchor'.$s.'\',getDateString(document.forms[\'adminbbadd\'].'.$s.'year,document.forms[\'adminbbadd\'].'.$s.'month,document.forms[\'adminbbadd\'].'.$s.'day));" name="anchor'.$s.'" id="anchor'.$s.'"><img src="modules/CPGNuCalendar/images/calendar.gif" style="border: 0pt none; width: 20px;" alt="'._PNLSELCAL.'..." title="'._PNLSELCAL.'..." /></a> ';
				} else {
					$content .= '<a href="javascript:var%20d=getDateString(document.forms[\'adminbbadd\'].'.$s.'year,document.forms[\'adminbbadd\'].'.$s.'month,document.forms[\'adminbbadd\'].'.$s.'day);cal'.$s.'.showCalendar(\'anchor'.$s.'\',(d==null)?getDateString(document.forms[\'adminbbadd\'].syear,document.forms[\'adminbbadd\'].smonth,document.forms[\'adminbbadd\'].sday):d);" name="anchor'.$s.'" id="anchor'.$s.'"><img src="modules/CPGNuCalendar/images/calendar.gif" style="border: 0pt none; width: 20px;" alt="'._PNLSELCAL.'..." title="'._PNLSELCAL.'..." /></a> ';
				}
			}
		}
		$content .= ' '.$separater.' ';
		$content .= '<span class="pn_tiny">'._HOUR.'</span> <select name="'.$s.'hour">';
		$content .= ($blank || $whlday) ? '<option selected="selected">&nbsp;</option>' : '';
//		$target_hr = ProNewsAdm::pndate('H',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
//		$target_hr = ($gmttime == 0) ? "00" : ProNewsAdm::pndate('H',$gmttime, 0, $userinfo['user_timezone']);
		$target_hr = ($whlday) ? '00' : ProNewsAdm::pndate('H',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
// echo '<br />$target_hr='.$target_hr.' (gmtm='.$gmttime.') whlday='.$whlday;
		for ($i = 0; $i <= 23; $i++) {
			$dummy = ($i < 10) ? "0$i" : $i;
			$content .= '<option'.(($dummy == $target_hr) ? ($gmttime && !$whlday && !($hropt && $blank && $target_hr == 0)) ? ' selected="selected"' : '' : '').'>'.$dummy.'</option>';
		}
		$content .= '</select> : <select name="'.$s.'min">';
		$content .= ($blank || $whlday) ? '<option selected="selected">&nbsp;</option>' : '';
		$target_min = ProNewsAdm::pndate('i',$gmttime, $userinfo['user_dst'], $userinfo['user_timezone']);
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
// CPGNuCALENDAR Default Settings	--------------------------------------------------------------------------
		$cal_image = 'circle.gif';		// To adjust these values for all CPGNuCalendar events
		$cal_priority = '2';			//   - edit these values here
		$cal_category = '1';			//   - look at cms_cpgnucalendar for possible values
		$cal_view = '0';				//
// CPGNuCALENDAR End Default Settings	----------------------------------------------------------------------

// CPGNuCALENDAR Time Correction if CPGNuCalendar logged event times differ from Pro_News in hours
		if ($pnsettings['cal_module'] == 2 && $pnsettings['cal_ofst'] != '' && is_numeric($pnsettings['cal_ofst'])) {
			$cal_timefix = $pnsettings['cal_ofst'] * 3600;
		} else {
			$cal_timefix = '';
		}

		$clstime = '';
//		$cal_content = $intro.' &nbsp; [i][color=grey]'._PNCLK2FLLW.'[/color][/i] [url='.getlink("&amp;aid=".$aid).'] '._PNORGNGART.'[/url].';
		$cal_content = $intro.' &nbsp; '._PNCLK2FLLW.' [url='.getlink("&amp;aid=".$aid).'] '._PNORGNGART.'[/url].';
		$startday = mktime(0, 0, 0, ProNewsAdm::pndate('m', $clsdttime, 0, 0), ProNewsAdm::pndate('d', $clsdttime, 0, 0), ProNewsAdm::pndate('Y', $clsdttime, 0, 0));
//		$startday = mktime(0, 0, 0, date('m', $clsdttime), date('d', $clsdttime), date('Y', $clsdttime));
//		$startday = L10NTime::tolocal($clstime, 0, $userinfo['user_timezone']);
//		$startday = $clsdttime + (3600*$userinfo['user_timezone']);
		if ($cledttime == 0) {
			$endday = $startday;
		} else {
//			$endday = mktime(0, 0, 0, date('m', $cledttime, 0, $userinfo['user_timezone']), date('d', $cledttime, 0, $userinfo['user_timezone']), date('Y', $cledttime, 0, $userinfo['user_timezone']));
			$endday = mktime(0, 0, 0, date('m', $cledttime), date('d', $cledttime), date('Y', $cledttime));
		}
// echo '<br /> startday='.$startday.' endday='.$endday.' s time='.$clsdttime.' e time='.$cledttime;
		if ($cledttime == 0) {
			$caldurtn = $startday + 86400 - $clsdttime;			// end date/time omitted so cap at end of day
			$cledttime = $clsdttime;							// force end date = start date if omitted
		}
		if (($clsdttime == $startday && $cledttime == ($endday + 86400)) || (ProNewsAdm::pndate('H',$clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']) == 0 && ProNewsAdm::pndate('i',$clsdttime, $userinfo['user_dst'], $userinfo['user_timezone']) == 0 && ProNewsAdm::pndate('H',$cledttime, $userinfo['user_dst'], $userinfo['user_timezone']) == 0 && ProNewsAdm::pndate('i',$cledttime, $userinfo['user_dst'], $userinfo['user_timezone']) == 0)) {
			$clstime = -1;										// if whole day - ie no time provided
			$cal_durtn = 0;
		} else {
			$cal_durtn = (($cledttime - $endday) - ($clsdttime - $startday)) / 60;
		}
// echo '<br /> s hr='.ProNewsAdm::pndate('H',$clsdttime, 0, 0).' s min='.ProNewsAdm::pndate('i',$clsdttime, 0, 0).' e hr='.ProNewsAdm::pndate('H',$cledttime, 0, 0).' e min='.ProNewsAdm::pndate('i',$cledttime, 0, 0);
		$cal_rpt = ($endday > $startday) ? 'R' : 'E';
/*  change for functions.php 						$cal_appvd = (true && true) ? formatDateTime(time(),'%Y%m%d') : ''; 		*/
		$cal_appvd = formatDateTime(time(),'%Y%m%d');
		$y = date('Y');
		$dtz = mktime(0,0,0,12,2,$y,0) - gmmktime(0,0,0,12,2,$y,0) + $cal_timefix;
// echo '<br /> dtz='.$dtz;
		$clstime = ($clstime == -1) ? -1 : date('Hi',$clsdttime + $dtz).'00';
// echo '<br> s time='.$clsdttime.' e time='.$cledttime.' s f day='.date('Ymd',$clsdttime + $dtz).' s f time='.$clstime.' dur='.$cal_durtn.' m f time='.date('His',time() + $dtz);
		$sql = 'INSERT INTO '.$prefix.'_cpgnucalendar VALUES ("0", "'.$postby.'", "'.formatDateTime($clsdttime,'%Y%m%d').'", "'.$clstime.'", "'.formatDateTime(time(),'%Y%m%d').'", "'.formatDateTime(time(),'%H%M%S').'", "'.$cal_durtn.'", "'.$cal_priority.'", "'.$cal_rpt.'", "'.$cal_view.'", "'.htmlunprepare($title).'", "'.$cal_content.'", "'.$cal_image.'", "'.$cal_appvd.'", "'.$cal_category.'")';
		$db->sql_query($sql);
		$cal_id = $db->sql_nextid('eid');  					// get and set calendar eid link
		$db->sql_query('UPDATE '.$prefix.'_pronews_articles SET cal_id="'.$cal_id.'" WHERE id='.$aid);
		if ($cal_rpt == 'R') {
			$sql = 'INSERT INTO '.$prefix.'_cpgnucalendar_repeat VALUES ("'.$cal_id.'", "daily", "'.formatDateTime($cledttime,'%Y%m%d').'", "1", "nnnnnnn")';
			$db->sql_query($sql);
		}
	}

function create_date($format, $gmepoch)
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
		if ($pnsettings['forumdate'] == 1) {
			return L10NTime::date($format, $gmepoch, 0, $board_config['board_timezone']);
		} else {
			return L10NTime::date($format, $gmepoch, 0, 0);
		}
	}
}

	function admin_imgcopy($image_fname='') {
		global $pnsettings;
		if (!isset($pnsettings)) {$pnsettings = $db->sql_fetchrow($db->sql_query('SELECT * FROM '.$prefix.'_pronews_settings'));}
		if ($image_fname == '') {return '';}
		else {
			$mtime = array_sum(explode(" ",microtime()));
			$newname = $mtime.substr($image_fname, -4);
			if (file_exists($pnsettings['imgpath'].'/'.$image_fname)) {
				copy($pnsettings['imgpath'].'/'.$image_fname, $pnsettings['imgpath'].'/'.$newname);
				if (file_exists($pnsettings['imgpath'].'/thumb_'.$image_fname)) {
					copy($pnsettings['imgpath'].'/thumb_'.$image_fname, $pnsettings['imgpath'].'/thumb_'.$newname);
				}
				if (file_exists($pnsettings['imgpath'].'/icon_'.$image_fname)) {
					copy($pnsettings['imgpath'].'/icon_'.$image_fname, $pnsettings['imgpath'].'/icon_'.$newname);
				}
			}
		return $newname;
		}
	}

	function admin_upload($form) {
		global $cpgtpl, $pnsettings;
		// upload dir - relative from document root
		// this needs to be a folder that is writeable by the server - must have sub-folders under it
		$destination = 'uploads/pdfs/';			// Parent pdf folder - adjust as necessary

		// acceptable files
		// if array is blank then all file types will be accepted
		$filetypes = array(			// Allowable file types
//					'doc' => 'application/msword',
//					'dot' => 'application/msword',
//					'eps' => 'application/postscript',
					'pdf' => 'application/pdf',
//					'pot' => 'application/vnd.ms-powerpoint',
//					'pps' => 'application/vnd.ms-powerpoint',
//					'ppt' => 'application/vnd.ms-powerpoint',
//					'rtf' => 'application/rtf',
//					'tar' => 'application/x-tar',
//					'tgz' => 'application/x-compressed',
//					'txt' => 'text/plain',
//					'xls' => 'application/vnd.ms-excel',
//					'zip' => 'application/zip'
				);

		// initialize success and error vars for processing
		$uploaded = '';
		$error = '';

		if ($form == '' || $form == 'none') {
			ProNewsAdm::admin_upload_form($form, $filetypes, $destination);
		} else {
			if (isset($_POST['target'])) {	$destination = $destination . Fix_Quotes($_POST['target'],1); }
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
			$msg = _PNUPLOADCOMPL.'<br /><a href="'.adminlink("&amp;mode=upld").'">'._PNGOBACK.'</a>';
			$cpgtpl->assign_block_vars('upload', array(
				'L_TITLE' => _PNUPLDRPT,
				'T_UPLOAD' => $uploaded,
				'T_ERRORS' => ($error) ? $error : '<tr><td colspan="3" align="center"><b>'._PNALLUPSUC.'</b></td></tr>',
				'S_MSG' => $msg
			));
		}
	}

	function admin_upload_form($func='',&$filetypes, $folder) {
		global $BASEHREF, $cpgtpl, $prefix;
//		$target_path = $path;
		$batch_limit  = 6;			// Maximum number of files to upload in 1 batch - use 0 for unlimited
		$max_size = '16 MB';		// Maximum individual file size for upload

		$dir = opendir($folder);	// Find target folders under $folder
		while (false !== ($file = readdir($dir))) {
			if (is_dir($folder . $file)) {
				if ($file != "." && $file != "..") {
					$dir_array[] = $file;
				}
			}
		}
		closedir($dir);
		if (is_array($dir_array)) {natcasesort($dir_array);}

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
		if ($dir_array != '') {
			$target = '<select name="target"><option value="">-- '._PNSELONE.' --</option>';
			foreach ($dir_array as $value) {
					$target .= '<option value="'.$value.'">'.ucfirst($value).'</option>';
			}
			$target .= '</select>';
		}
		$submit = _PNBTCHUP;
		$cpgtpl->assign_block_vars('upload_form', array(
			'G_STARTFORM' => open_form(adminlink("&amp;mode=upld&amp;do=up"),'multifile','&nbsp;'._PNPUPLOAD.'&nbsp;'),
			'G_ENDFORM' => close_form(),
			'L_TARGET' => _PNUPFILES,
			'S_TARGET' => $folder,
			'T_TARGET' => $target,
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
	}

	function admin_gallery($form) {
		global  $cpgtpl, $CPG_URL;
		$cpgtpl->assign_block_vars('glry_menu', array(
			'S_MSG' => _PNGLRYMENU,
			'S_ALBUM' => _PNNEWALB,
			'U_ALBUM' => getlink("coppermine&amp;file=albmgr"),
			'S_BAT_UP' => _PNBATCHUP,
			'U_BAT_UP' => getlink("coppermine&amp;file=batchupload"),
			'S_BAT_ADD' => _PNBATCHADD,
			'U_BAT_ADD' => getlink("coppermine&amp;file=searchnew"),
			'S_FORMSTART' => open_form(adminlink("&amp;mode=upld"),'glrymeny','&nbsp;'._PNPHOTOMENU.'&nbsp;'),
			'S_FORMEND' => close_form()
		));
		$cpgtpl->set_filenames(array('body' => 'pronews/admin/admin.html'));
		$cpgtpl->display('body');
	}

	function groups_only_selectbox($fieldname, $current, $multiple=false) {
		global $db, $prefix;
		$groupsResult = $db->sql_query('SELECT group_id, group_name FROM '.$prefix.'_bbgroups WHERE group_single_user=0');
		while (list($groupID, $groupName) = $db->sql_fetchrow($groupsResult)) {
			$groups[$groupID] = $groupName;
		}
		return ProNewsAdm::select_multi_box($fieldname, $current, $groups);
	}

	function select_multi_box($name, $default, $options) {
//		if (function_exists('theme_select_multi_box')) {
//			return theme_select_multi_box($name, $default, $options);
//		} else {
			$select = '<select class="set" name="'.$name.'[]" multiple="multiple" size="3" id="'.$name."\">\n";
			foreach ($options as $value => $title) {
				$select .= "<option value=\"$value\"".(in_array($value, $default) ? ' selected="selected"' : '').">$title</option>\n";
			}
			return $select.'</select>';
//		}
	}

	function group_selectbox($fieldname, $current=0) {
		static $groups;
		if (!isset($groups)) {
			global $db, $prefix;
			$groups = array(2=>_MVADMIN);
			$groupsResult = $db->sql_query('SELECT group_id, group_name FROM '.$prefix.'_bbgroups WHERE group_single_user=0');
			while (list($groupID, $groupName) = $db->sql_fetchrow($groupsResult)) {
				$groups[($groupID+3)] = $groupName;
			}
		}
		$tmpgroups = $groups;
		return select_box($fieldname, $current, $tmpgroups);
	}
}
