<?php
/*********************************************
  Pro_News for CPG Dragonfly CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com
  Copyright © 2007 Sarah
  Copyright © 2007 - 2012 M Waldron aka layingback

  Modified for Pro_News by Sarah 2007/02/07
  Advance Search for Pro_News by layingback

  $Revision: 3.6 $
  $Date: 2012-03-08 11:34:23 $
  Author: layingback

  Released under the terms and conditions
  of the GNU GPL version 2 or any later version

  Original Source: /cvs/html/modules/News/search.inc
  Revision: 9.6
  Author: djmaze
  Date: 2006/01/06 13:04:14
**********************************************/

$mod_dirname = basename(dirname(__FILE__));

eval("class ".$mod_dirname."_search
	{
		var \$title;
		var \$options;
		var \$result = array();
		var \$result_count = 0;
		var \$link_next;
		var \$link_prev;

		// class constructor
		function ".$mod_dirname."_search() {
			global \$db, \$prefix, \$userinfo, \$MAIN_CFG;
			\$pnsettings = \$MAIN_CFG['pro_news'];
			\$pn_module_name = \$pnsettings['module_name'];
			get_lang(\$pn_module_name);
			\$secs[-1] = _ALL;
			\$seclist = \$db->sql_query(\"SELECT id, title, view FROM \".\$prefix.\"_pronews_sections ORDER BY title\");
			while (list(\$id, \$title, \$view) = \$db->sql_fetchrow(\$seclist)) {
				if ((\$view == '0') || (can_admin(\$pn_module_name)) || (is_user() && ((\$view == '1') || ((\$view > 3) && (isset(\$userinfo['_mem_of_groups'][\$view - 3])))))) {
					\$secs[\$id] = \$title;
				}
			}
			\$db->sql_freeresult(\$catlist);

			\$sql = 'SELECT s.title stitle, s.view, c.title, c.id FROM '.\$prefix.'_pronews_sections as s,';
			\$sql .= ' '.\$prefix.'_pronews_cats as c WHERE c.sid=s.id';

			\$list = \$db->sql_fetchrowset(\$db->sql_query(\$sql));
			if ((\$list) && (\$list != '')) {
				foreach (\$list as \$row) {
					if ((\$row['view'] == '0') || (can_admin(\$pn_module_name)) || (is_user() && ((\$row['view'] == '1') || ((\$row['view'] > 3) && (isset(\$userinfo['_mem_of_groups'][\$row['view'] - 3])))))) {
						\$list2[\$row['stitle']][\$row['title']][] = \$row['id'];
					}
				}
				\$seccat = '<select name=\"pronews_cat\"><option value=\"\">'._ALL.'</option>';
				foreach (\$list2 as \$row => \$value) {
					\$seccat .= '<optgroup label=\"'.\$row.'\">';
					foreach (\$value as \$op => \$tid) {
						\$seccat .= '<option value=\"'.\$tid['0'].'\">'.\$op.'</option>';
					}
					\$seccat .= '</optgroup>';
				}
				\$seccat .= '</select>';
			}

	//		\$db->sql_freeresult(\$list);

			\$this->title = _PNPRONEWS;
			\$this->options = '<table border=\"0\"><tr><td>'._PNSECTION.'</td><td>'.select_box('pronews_sec', -1, \$secs).'</td></tr>'
				.'<tr><td></td><td> <i>'._PNOR.'</i> </td></tr>'
				.'<tr><td>'._PNCAT.'</td><td>'.\$seccat.'</td></tr>'
				.'</table>';
		}

		function search(\$search, \$url_search, \$limit=10, \$page=0) {
			global \$db, \$prefix, \$userinfo, \$multilingual, \$currentlang, \$MAIN_CFG;
			\$pnsettings = \$MAIN_CFG['pro_news'];
			\$pn_module_name = \$pnsettings['module_name'];
			\$first = \$page*\$limit;

			\$pn_sec   = (isset(\$_POST['pronews_sec']) ? intval(\$_POST['pronews_sec']) : 0);
			\$pn_cat   = (isset(\$_POST['pronews_cat']) ? intval(\$_POST['pronews_cat']) : 0);

			\$days  = (isset(\$_POST['days']) ? (intval(\$_POST['days'])*86400) : 0);
			\$seceg = (\$pn_sec > 0) ? \"s.id = \$pn_sec\" : 's.id > -1';
			\$categ = (\$pn_cat > 0) ? \"catid=\$pn_cat\" : 'catid > -1';

			\$q	= \"SELECT a.id, a.postby, a.title, a.posttime, a.intro, c.title, s.title, s.view FROM \".\$prefix.\"_pronews_articles as a, \".\$prefix.\"_pronews_cats as c, \".\$prefix.\"_pronews_sections as s\";
			\$q .= \" WHERE a.catid = c.id AND c.sid = s.id AND \$seceg AND \$categ\";
			\$q .= \" AND approved = 1 AND active = 1\";
			if (\$search != '') \$q .= \" AND (a.title LIKE '%\$search%' OR a.intro LIKE '%\$search%' OR a.content LIKE '%\$search%' OR a.caption LIKE '%\$search%')\";
			if (\$days > 0) \$q .= \" AND s.time >= \".(gmtime()-\$days);
			if (\$multilingual) \$q .= \" AND (alanguage='\$currentlang' OR alanguage='')\";
			\$q .= \" ORDER BY posttime DESC LIMIT \$first,\".(\$limit+1);
			\$result = \$db->sql_query(\$q);
			\$nrows	= \$db->sql_numrows(\$result);
			\$crows  = 0;

			\$x = (\$limit > \$nrows) ? \$nrows : \$limit;
			if (\$nrows > 0) {
				while(\$x > 0) {
					list(\$id, \$postby, \$title, \$posttime, \$intro, \$cat, \$sec, \$sview) = \$db->sql_fetchrow(\$result);
					if ((\$sview == '0') || (can_admin(\$pn_module_name)) || (is_user() && ((\$sview == '1') || ((\$sview > 3) && (isset(\$userinfo['_mem_of_groups'][\$sview - 3])))))) {
						if (\$postby == '') {
							\$item['author'] = _ANONYMOUS;
						} else {
							\$item['author'] = '<a href=\"'.getlink(\"Your_Account&amp;profile=\$postby\").'\">'.\$postby.'</a>';
						}
						\$item['title'] = \$title;
						\$item['image'] = 'images/folders.gif';
						\$item['date'] = \$posttime;
						\$item['url'] = getlink(\"\$pn_module_name&amp;aid=\$id\");
						\$item['short_text'] = substr(strip_tags(decode_bbcode(\$intro)), 0, 128);
						\$item['footer'] = _PNSECTION. ': '.\$sec.' &nbsp; '._PNCAT. ': '.\$cat;
						if (can_admin(\$pn_module_name)) {
							\$item['footer'] .= ' &nbsp; [ <a href=\"'.adminlink(\"\$pn_module_name&amp;mode=add&amp;do=edit&amp;id=\$id\").'\">'._EDIT.'</a> ]';
						}
						\$this->add_result(\$item);
						unset(\$item);
					}

					\$x--;
				}
			}

//			\$db->sql_fetchrow(\$result);

			if (\$page>0) {
				\$this->link_prev = '<a href=\"'.getlink('Search&amp;page='.(\$page-1).\"&amp;topic=\$topic&amp;cat=\$cat&amp;search=\$url_search&amp;mod=\$pn_module_name\").'\"><strong>'._PREVMATCHES.' '._IN.' '._PNPRONEWS.'</strong></a>';
			}
			if ((\$nrows > \$limit) || (\$crows > \$limit)) {
				\$this->link_next = '<a href=\"'.getlink('Search&amp;page='.(\$page+1).\"&amp;topic=\$topic&amp;cat=\$cat&amp;search=\$url_search&amp;mod=\$pn_module_name\").'\"><strong>'._NEXTMATCHES.' '._IN.' '._PNPRONEWS.'</strong></a>';
			}

		}

		function add_result(\$resultarray) {
			\$this->result[\$this->result_count++] = \$resultarray;
		}
	}
");
