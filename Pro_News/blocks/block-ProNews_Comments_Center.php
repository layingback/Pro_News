<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2007 - 2009 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 3.6 $
  $Date: 2012-04-24 13:47:00 $
  Author: layingback
********************************************************/
if (!defined('CPG_NUKE')) { exit; }
global $Blocks, $MAIN_CFG, $cpgtpl, $db, $prefix, $user_prefix, $sitename, $CPG_SESS, $blocks_list;
$pnsettings = $MAIN_CFG['pro_news'];
$pn_module_name = $pnsettings['module_name'];
get_lang($pn_module_name);
if (is_active($pn_module_name)) {
	require_once('modules/'.$pn_module_name.'/functions.php');
	$bid = (isset($block['bid'])) ? $block['bid'] : intval($bid);
	if (!is_active('Forums')) {
		$content = 'ERROR';
		return trigger_error('Forums module is inactive', E_USER_WARNING);
	}
	if (file_exists("themes/$CPG_SESS[theme]/images/forums/icon_mini_message.gif")) {
		$iconpath = "themes/$CPG_SESS[theme]/images/forums";
	} else {
		$iconpath = "themes/default/images/forums";
	}
	cache_load_array('blocks_list');
	$content = ProNews::get_comment_id($bid, $section, $dsply_cmnt_cnt, $art_cmntid);
// echo ' id='.$art_cmntid.' dsply='.$dsply_cmnt_cnt;
	if ($content == '') {
		if ($art_cmntid <> '') {
			$view = ' AND f.auth_view=0';
			if (can_admin('forums')) {
				$view = '';
			} elseif (is_user() && count($userinfo['_mem_of_groups'])) {
				foreach ($userinfo['_mem_of_groups'] as $id => $name) {
					$groups[] = $id;
				}
				$result = $db->sql_uquery("SELECT forum_id FROM ".$prefix."_bbauth_access WHERE group_id IN (".implode(',', $groups).") AND (auth_mod = 1 OR auth_view = 1) GROUP BY forum_id");
				while ($row = $db->sql_fetchrow($result)) {
					$forums[] = $row[0];
				}
				if (count($forums)) {
					$view = ' AND (f.auth_view=0 OR f.forum_id IN ('.implode(',', $forums).'))';
				}
			}
// echo ' sec='.$section;
			$result = $db->sql_query('SELECT
			 t.topic_id, t.topic_replies, t.topic_first_post_id, t.topic_last_post_id, t.topic_title,
			 f.forum_name, f.forum_id,
			 u.username, u.user_id,
			 p.poster_id, p.post_time
			 FROM ('.$prefix.'_bbtopics t, '.$prefix.'_bbforums f)
			 LEFT JOIN '.$prefix.'_bbposts p ON (p.post_id = t.topic_id)
			 LEFT JOIN '.$user_prefix.'_users u ON (u.user_id = p.poster_id)
			 WHERE t.forum_id=f.forum_id '.$view.'
			 AND t.topic_id='.$art_cmntid.'
			 ORDER BY t.topic_last_post_id DESC
			 LIMIT 1');								//f.auth_view = 0 - everyone; f.auth_view = 1 - member; f.auth_view = 2 - private; f.auth_view = 3 - moderator; f.auth_view = 5 - admin
// echo ' topics='.$topic_replies;
			if ($db->sql_numrows($result) < 1) {
				$content = 'ERROR';
				return trigger_error('There are no forum posts', E_USER_NOTICE);
			} else {
				list($topic_id, $topic_replies, $topic_first_post_id, $topic_last_post_id, $topic_title, $forum_name, $forum_id, $username, $user_id, $poster_id, $post_time) = $db->sql_fetchrow($result);
// echo ' replies='.$topic_replies;
				$titleinfo = ($topic_replies == '1') ? _PN1CMNT : (($topic_replies > $dsply_cmnt_cnt) ? sprintf(_PNFRSTCMNTS, $dsply_cmnt_cnt, $topic_replies) : sprintf(_PNALLCMNTS, $topic_replies));
				$post_time = formatDateTime($post_time, '%b %d, %Y '._AT.' %T');
				$topic_title = check_words($topic_title);
				$cpgtpl->assign_block_vars('comment_blk', array(
					'T_TITLE' => $titleinfo,
					'S_TITLE' => $topic_title,
//					'L_POSTBY' => _PNPOSTBY,
//					'S_POSTBY' => $username,
//					'T_POSTBY' => getlink("Your_Account&amp;profile=".$username),
//					'L_POSTON' => _PNPOSTON,
//					'S_POSTTIME' => $post_time,
//					'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
					'G_MORECMNTS' => ($topic_replies > $dsply_cmnt_cnt) ? '1' : '',
					'T_NUMCMNTS' => sprintf(_PNSEALCMNTS, $topic_replies),
					'U_ALLLINK' => getlink('Forums&amp;file=viewtopic&amp;p='.$topic_last_post_id),
					'L_CMNTLINK' => _PNLEAVCMNT,
					'T_CMNTLINK' => getlink('Forums&amp;file=viewtopic&amp;p='.$topic_last_post_id.'#'.$topic_last_post_id),
					'S_CMNTLINK' => _PNLVCMNT
				));

				$result = $db->sql_query('SELECT p.post_id, p.poster_id, p.post_time, p.post_attachment, q.post_subject, q.post_text, u.username, u.user_id
					FROM '.$prefix.'_bbposts p
					LEFT JOIN '.$prefix.'_bbposts_text q ON (p.post_id =  q.post_id)
					LEFT JOIN '.$user_prefix.'_users u ON (u.user_id = p.poster_id)
					WHERE p.topic_id='.$art_cmntid.' AND p.post_id <> '.$topic_first_post_id.'
					ORDER BY p.post_time ASC LIMIT '.$dsply_cmnt_cnt);
				$i = 0;
				while (list($post_id, $poster_id, $post_time, $post_attachment, $post_subject, $post_text, $username, $user_id) = $db->sql_fetchrow($result)) {

					$post_time = formatDateTime($post_time, '%b %d, %Y '._AT.' %T');
					$post_subject = check_words($post_subject);
					$post_text = (strlen($post_text) > $pnsettings['cmntlen']) ? substr_replace($post_text," ...",$pnsettings['cmntlen']) : $post_text;
					$post_text = decode_bbcode($post_text, 1, true);
//					$post_text = str_replace('[quote]', '<span class="pn_quoter"> quote: </span>"', $post_text);		// uncomment to remove full CSS from quotes
//					$post_text = str_replace('[/quote]', '"', $post_text);						// uncomment to remove full CSS from quotes
					$post_text = preg_replace('/\[quote="(.*?)"](.*?)/', '<span class="pn_quoter">\\1:</span> "\\2', $post_text, -1);
					$more_link = (strlen($post_text) > $pnsettings['cmntlen']) ? '<a href="'.getlink().'"><i>'._PNMORE.'</i></a>' : '';
					$cpgtpl->assign_block_vars('comment_blk.comment_txt', array(
						'G_EVNODD' => ($post_id % 2) ? '_dark' : '',
						'S_TITLE' => $post_subject,
						'S_INTRO' => $post_text,
						'T_ICON' => $iconpath.'/icon_mini_message.gif',
						'G_ATTCH' => ($post_attachment) ? '1' : '',
						'T_ATTCHICN' => 'images/icons/icon_clip.gif',
						'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
						'L_POSTBY' => ($i == 0) ? _PNLSTCMNT : _PNPSTCMNT,
						'S_POSTBY' => $username,
						'T_POSTBY' => getlink("Your_Account&amp;profile=".$username),
						'L_POSTON' => _PNPOSTON,
						'S_POSTTIME' => $post_time,
						'U_MORELINK' => getlink('Forums&amp;file=viewtopic&amp;p='.$post_id.'#'.$post_id),
						'S_MORELINK' => _PNMORE,
						'G_MORELINK' => (strlen($post_text) > $pnsettings['cmntlen']) ? '1' : '',
						'L_POSTLINK' => _PNSEECMNT,
						'U_POSTLINK' => getlink('Forums&amp;file=viewtopic&amp;p='.$post_id.'#'.$post_id),
						'U_ALLLINK' => getlink("$pn_module_name&amp;mode=home")					));
						$i++;
				}
				ob_start();
				$cpgtpl->set_filenames(array('cmntblk' => 'pronews/cmntblk_dn.html'), false);
				$cpgtpl->display('cmntblk');
				$content .= ob_get_clean();
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
	$content = '';
}
