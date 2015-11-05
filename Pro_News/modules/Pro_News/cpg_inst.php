<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Original Beta version Copyright © 2006 by D Mower aka Kuragari
  Subsequent releases Copyright © 2007 - 2015 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 3.48 $
  $Date: 2013-05-28 13:04:40 $
  Author: layingback
**********************************************/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }

$mod_dirname = basename(dirname(__FILE__));

eval("
		class $mod_dirname {
			var \$description;
			var \$radmin;
			var \$modname;
			var \$version;
			var \$author;
			var \$website;
			var \$dbtables;
		// class constructor
			function $mod_dirname() {
				\$this->radmin = true;
				\$this->version = '4.0.7';
				\$this->modname = \"$mod_dirname\";
				\$this->description = 'Pro_News CM&#8482; - Ultra Configurable Content Management - interfaces to Forums, Photo Gallery & Calendar (v4: developed and extended from beta module of same name by Kauragari)';
				\$this->author = 'layingback';
				\$this->website = 'www.layingback.net';
				\$this->dbtables = array('pronews_articles', 'pronews_cats', 'pronews_sections', 'pronews_blocks', 'pronews_schedule');
			}

		// module installer
			function install() {
				global \$installer, \$db, \$prefix;
				\$installer->add_query('CREATE', 'pronews_cats', \"
					id int(11) NOT NULL auto_increment,
					sid tinyint(11) default '0',
					title varchar(255) NOT NULL default 'New Cat',
					description text default NULL,
					icon varchar(255) NULL default 'clearpixel.gif',
					view tinyint(11) NOT NULL default '0',
					admin tinyint(11) NOT NULL default '2',
					sequence tinyint(11) NOT NULL default '0',
					forum_id int(10) NULL,
					forum_module int(11) NOT NULL default '0',
					forumspro_name varchar(20) default NULL,
						PRIMARY KEY (id)\", 'pronews_cats');
				\$installer->add_query('CREATE', 'pronews_sections', \"
					id int(11) NOT NULL auto_increment,
					title varchar(255) NOT NULL default 'New Section',
					description text default NULL,
					view int(10) UNSIGNED NOT NULL default '0',
					admin int(10) UNSIGNED NOT NULL default '1',
					forum_id int(10) NULL,
					sequence tinyint(11) NOT NULL default '0',
					in_home tinyint(11) NOT NULL default '0',
					forum_module int(11) NOT NULL default '0',
					forumspro_name varchar(20) default NULL,
					usrfld0 varchar(50) default NULL,
					usrfld1 varchar(50) default NULL,
					usrfld2 varchar(50) default NULL,
					usrfld3 varchar(50) default NULL,
					usrfld4 varchar(50) default NULL,
					usrfld5 varchar(50) default NULL,
					usrfld6 varchar(50) default NULL,
					usrfld7 varchar(50) default NULL,
					usrfld8 varchar(50) default NULL,
					usrfld9 varchar(50) default NULL,
					usrfldttl varchar(50) NOT NULL default '',
					template varchar(25) NOT NULL default '',
					art_ord tinyint(11) NOT NULL default '0',
					secheadlines tinyint(11) NOT NULL default '0',
					sectrunc1head int(11) NOT NULL default '0',
					sectrunchead int(11) NOT NULL default '0',
					post int(10) UNSIGNED NOT NULL default '1',
					secdsplyby tinyint(11) NOT NULL default '0',
					keyusrfld int(11) NOT NULL default '0',
					moderate int(10) UNSIGNED NOT NULL default '2',
						PRIMARY KEY (id)\", 'pronews_sections');
			   \$installer->add_query('CREATE', 'pronews_articles', \"
					id int(11) NOT NULL auto_increment,
					catid int(11) NOT NULL default '0',
					title varchar(255) NOT NULL default '',
					intro text NULL,
					content text NULL,
					image varchar(255) NULL default '',
					caption varchar(255) NULL default '',
					allow_comment tinyint(1) NOT NULL default '1',
					postby varchar(50) NOT NULL default '',
					postby_show tinyint(1) NOT NULL default '1',
					posttime varchar(14) NULL,
					show_cat tinyint(1) NULL default '1',
					active tinyint(1) NULL default '0',
					approved tinyint(1) NULL default '0',
					viewable text NULL,
					topic_id int(10) NULL,
					display_order tinyint(1) NULL default '0',
					alanguage varchar(30) NOT NULL,
					album_id int(11) NOT NULL default '0',
					album_cnt tinyint(1) NOT NULL default '0',
					album_seq tinyint(1) NOT NULL default '0',
					slide_show tinyint(1) NOT NULL default '0',
					image2 varchar(255) NOT NULL default '',
					caption2 varchar(255) NOT NULL default '',
					user_fld_0 varchar(255) NOT NULL default '',
					user_fld_1 varchar(255) NOT NULL default '',
					user_fld_2 varchar(255) NOT NULL default '',
					user_fld_3 varchar(255) NOT NULL default '',
					user_fld_4 varchar(255) NOT NULL default '',
					user_fld_5 varchar(255) NOT NULL default '',
					user_fld_6 varchar(255) NOT NULL default '',
					user_fld_7 varchar(255) NOT NULL default '',
					user_fld_8 varchar(255) NOT NULL default '',
					user_fld_9 varchar(255) NOT NULL default '',
					counter mediumint(9) NULL default '0',
					score int(11) NOT NULL default '0',
					ratings int(11) NOT NULL default '0',
					df_topic int(10) default '0',
					cal_id int(11) NULL default '0',
					associated text NOT NULL,
					display tinyint(1) NOT NULL default '1',
					updtby varchar(50) NULL default '',
					updttime varchar(14) NULL default '',
					seod varchar(255) NULL default '');
						PRIMARY KEY (id),
							KEY (topic_id)\", 'pronews_articles');

				\$installer->add_query('CREATE', 'pronews_blocks', \"
					bid varchar(255) NOT NULL default '',
					type varchar(255) NOT NULL default '',
					section varchar(255) NOT NULL default '',
					num varchar(255) NOT NULL default '',
					category varchar(255) default NULL,
						PRIMARY KEY (bid)\", 'pronews_blocks');

				\$installer->add_query('CREATE', 'pronews_schedule', \"
					id int(11) NOT NULL default '0',
					newstate tinyint(1) NULL default '0',
					dttime varchar(14) NULL default '0',
						PRIMARY KEY (id, newstate),
							KEY (dttime)\", 'pronews_schedule');

				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'imgpath', 'uploads/pro_news'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'max_w', '200'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'max_h', '200'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'allow_up', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_icons', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_postby', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_cat', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'per_page', '10'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'template', 'index2alb.html'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'introlen', '450'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_intro', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'comments', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'display_by', '2'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_smilies', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'hdlines', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'per_hdline', '4'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'forum_module', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'hdln1len', '275'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'hdlnlen', '150'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'postlink', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_album', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_usrflds', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'cal_module', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'read_cnt', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_reads', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'ratings', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'per_gllry', '24'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'emailf', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'printf', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'lmt_fulart', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'art_ordr', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'topic_lnk', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_noimage', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'notify_admin_pending_article', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'receiver_email_pending_article', 'admin@email.com'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'subject_email_pending_article', 'Pending Article Submission'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'body_email_pending_article', 'A user has submitted an article on your site that requires admin approval. When you get a chance, please look into it.'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'sender_email_pending_article', 'admin@email.com'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'notify_user_approving_article', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'per_admn_page', '25'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'subject_email_approved_article', ''\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'body_email_approved_article', ''\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'forum_per-cat', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'related_arts', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'img_limit', '800'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'soc_net', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'secat_hdgs', '3'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'actv_sort_ord', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'clrblks_hm', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'art_inhome', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'member_pages', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'arts_in_secpg', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'rss_in_secpg', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'enbl_rss', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'cnt_cmnts', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'num_arts_sec', '5'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'sec_in_sec', '4'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'mem_in_sec', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'cmntlen', '150'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'permalink', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'usr_ovr_ppage', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'admin_html', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'forumdate', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'dateformat', 'l F d, Y (H:i:s)'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'text_on_url', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'module_name', '$mod_dirname'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'url_lcase', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'url_hyphen', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'sec_in_url', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'cat_in_url', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'srch_tmplt', 'index2alb.html'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'srch_num_arts', '10'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'srch_introlen', '450'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'srch_show_img', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'srch_num_inlist', '25'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'inc_intro', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'SEOtitle', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'mod_grp', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'auto_apprv', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'edit_time', ''\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'disply_full', '1'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'cal_ofst', ''\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'aspect', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'opn_grph', '0'\");
				\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'img_max_width_remote', ''\");
				\$installer->add_query('INSERT', 'pronews_sections', \"'-1', 'Default Section', 'Default Section (Cannot be Altered)', '2', '2', '0', '0', '0', '1', '', '', '', '', '', '', '', '', '', '', '', '', '', '0', '0', '0', '0', '2', '0', '0', '0'\");
				\$installer->add_query('UPDATE', 'pronews_sections', \"id = '0' WHERE id = '-1'\");
				\$installer->add_query('INSERT', 'pronews_cats', \"'1', '0', 'Default Category', 'Default Category (Cannot be Deleted)','clearpixel.gif', '2', '2', '0', '0', '0', ''\");
				\$installer->add_query('INSERT', 'pronews_sections', \"'1', 'Members Pages', 'Member\'s Section (Cannot be Deleted)', 0, 2, 0, 1, 0, 1, '', '', '', '', '', '', '', '', '', '', '', '', '', '0', '0', '0', '0', '1', '0', '0', '0'\");
				\$installer->add_query('INSERT', 'pronews_cats', \"'2', '1', 'My Page', 'Member\'s Single Page accessed through Your Account','clearpixel.gif', '0', '0', '0', '0', '0', ''\");

				return true;
			}

		// module upgrade function
			function upgrade(\$prev_version) {
				global \$installer;
				if (\$prev_version == '1.0.0.1') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'display_by', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_smilies', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'hdlines', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'per_hdline', '1'\");
				}
				if (\$prev_version <= '1.0.2.0') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'forum_module', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'hdln1len', '275'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'hdlnlen', '175'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'postlink', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_album', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_usrflds', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'cal_module', '0'\");

					\$installer->add_query('ADD', 'pronews_sections', \"'forum_module int(11) NOT NULL default 1'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'forumspro_name varchar(20) default NULL'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'album_id int(11) NOT NULL default 0'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'album_cnt tinyint(1) NOT NULL default 0'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'album_seq tinyint(1) NOT NULL default 0'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'slide_show tinyint(1) NOT NULL default 0'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'image2 varchar(255) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'caption2 varchar(255) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'user_fld_0 varchar(255) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'user_fld_1 varchar(255) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'user_fld_2 varchar(255) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'user_fld_3 varchar(255) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'user_fld_4 varchar(255) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'user_fld_5 varchar(255) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'user_fld_6 varchar(255) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'user_fld_7 varchar(255) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'user_fld_8 varchar(255) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'user_fld_9 varchar(255) NOT NULL'\");
				}
				if (\$prev_version <= '2.0.0.0') {
					\$installer->add_query('UPDATE', 'pronews_cats', \"ICON = 'clearpixel.gif' WHERE ID = '1'\");
				}
				if (\$prev_version <= '2.0.0.3') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'read_cnt', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_reads', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'ratings', '1'\");

					\$installer->add_query('ADD', 'pronews_articles', \"'counter mediumint(9) NULL default 0'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'score int(11) NOT NULL default 0'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'ratings int(11) NOT NULL default 0'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'usrfld0 varchar(50) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'usrfld1 varchar(50) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'usrfld2 varchar(50) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'usrfld3 varchar(50) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'usrfld4 varchar(50) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'usrfld5 varchar(50) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'usrfld6 varchar(50) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'usrfld7 varchar(50) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'usrfld8 varchar(50) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'usrfld9 varchar(50) NOT NULL'\");
				}
				if (\$prev_version <= '2.0.1.0') {
					\$installer->add_query('ADD', 'pronews_sections', \"'usrfldttl varchar(50) NOT NULL'\");
				}
				if (\$prev_version <= '2.0.1.2') {
					\$installer->add_query('ADD', 'pronews_sections', \"'template varchar(25) NOT NULL'\");

					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'per_gllry', '24'\");
				}

				if (\$prev_version <= '2.1.0.3') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'emailf', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'printf', '1'\");

					\$installer->add_query('CREATE', 'pronews_schedule', \"
						id int(11) NOT NULL default '0',
						newstate tinyint(1) NOT NULL default '0',
						dttime int(11) NOT NULL default '0',
							PRIMARY KEY (id, newstate),
							KEY (dttime)\",
							 'pronews_schedule');
				}

				if (\$prev_version <= '2.2.0.0') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'lmt_fulart', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'art_ordr', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'topic_lnk', '0'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'df_topic int(10) default 0'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'art_ord tinyint(11) NOT NULL default 0'\");
				}

				if ((\$prev_version >= '2.1.0.3') && (\$prev_version <= '2.3.0.1')) {		// Fix for 2.1.0.3 CLEAN (only) Install creating single primary key
					global \$prefix, \$db;
					\$result = \$db->sql_query('SHOW INDEX FROM '.\$prefix.'_pronews_schedule');
					if (\$db->sql_numrows(\$result) < 3) {
						\$result = \$db->sql_query('ALTER TABLE '.\$prefix.'_pronews_schedule DROP PRIMARY KEY');
						\$result = \$db->sql_query('ALTER TABLE '.\$prefix.'_pronews_schedule ADD PRIMARY KEY (id, newstate)');
						\$result = \$db->sql_query('ALTER TABLE '.\$prefix.'_pronews_schedule ADD KEY (dttime)');
					}
				}

				if (\$prev_version <= '2.3.0.1') {
					global \$prefix, \$db;
					\$result = \$db->sql_query('ALTER TABLE '.\$prefix.'_pronews_articles ADD KEY (topic_id)');
				}

				if (\$prev_version <= '3.0.0.1') {
					//fishingfan upgrade
					\$installer->add_query('ADD', 'pronews_sections', \"'secheadlines tinyint(11) NOT NULL default 0'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'sectrunc1head int(11) NOT NULL default 0'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'sectrunchead int(11) NOT NULL default 0'\");
				}

				if (\$prev_version <= '3.0.0.2') {
					\$installer->add_query('ADD', 'pronews_articles', \"'cal_id int(11) NULL default 0'\");
				}

				if (\$prev_version <= '3.1.0.0') {
					\$installer->add_query('ADD', 'pronews_articles', \"'associated text NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_cats', \"'forum_id int(10) NULL default 0'\");
					\$installer->add_query('ADD', 'pronews_cats', \"'forum_module int(11) NOT NULL default 0'\");
					\$installer->add_query('ADD', 'pronews_cats', \"'forumspro_name varchar(20) default NULL'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'show_noimage', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'notify_admin_pending_article', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'receiver_email_pending_article', 'admin@email.com'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'subject_email_pending_article', 'Pending Article Submission'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'body_email_pending_article', 'A user has submitted an article on your site that requires admin approval. When you get a chance, please look into it.'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'sender_email_pending_article', 'admin@email.com'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'notify_user_approving_article', '1'\");
				}

				if (\$prev_version <= '3.2.0.0') {
					\$installer->add_query('ADD', 'pronews_articles', \"'display tinyint(1) NOT NULL default 1'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'post tinyint(11) NOT NULL default 1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'per_admn_page', '25'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'forum_per-cat', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'related_arts', '0'\");
					\$installer->add_query('UPDATE', 'pronews_sections', \"post = '2' WHERE id = '1'\");
				}

				if (\$prev_version <= '3.2.0.1') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'img_limit', '800'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'soc_net', '0'\");
				}

				if (\$prev_version <= '3.2.1.2') {
					\$installer->add_query('ADD', 'pronews_sections', \"'secdsplyby tinyint(11) NOT NULL default 0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'secat_hdgs', '3'\");
				}

				if (\$prev_version <= '3.2.2.1') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'actv_sort_ord', '0'\");
				}

				if (\$prev_version <= '3.3.0.0') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'clrblks_hm', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'art_inhome', '0'\");
				}

				if (\$prev_version <= '3.3.1.1') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'member_pages', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'arts_in_secpg', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'rss_in_secpg', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'enbl_rss', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'cnt_cmnts', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'num_arts_sec', '5'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'sec_in_sec', '4'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'mem_in_sec', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'cmntlen', '150'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'permalink', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'usr_ovr_ppage', '0'\");
					\$installer->add_query('UPDATE', 'pronews_sections', \"id = '0', description = 'Default Section (Cannot be Altered) WHERE id = 1'\");
					\$installer->add_query('UPDATE', 'pronews_cats', \"sid = '0', sequence = '0' WHERE sid = '1'\");
					\$installer->add_query('INSERT', 'pronews_sections', \"'1', 'Members Pages', 'Member\'s Section (Cannot be Deleted)', '0', '2', '0', '1', '0', '1', '', '', '', '', '', '', '', '', '', '', '', '', '', '0', '0', '0', '0', '1', '0'\");
					\$installer->add_query('INSERT', 'pronews_cats', \"'0', '1', 'My Page', 'Member\'s Page accessed through Your Account','clearpixel.gif', '0', '0', '0', '0', '0', ''\");
				}

				if (\$prev_version <= '3.3.2.1') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'admin_html', '0'\");
				}

				if (\$prev_version <= '3.3.2.4') {
					\$installer->add_query('ADD', 'pronews_sections', \"'keyusrfld int(11) NOT NULL default 0'\");
				}

				if (\$prev_version <= '3.3.2.6') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'forumdate', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'dateformat', 'l F d, Y (H:i:s)'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'text_on_url', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'module_name', 'Pro_News'\");
				}

				if (\$prev_version <= '3.3.2.7') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'url_lcase', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'url_hyphen', '0'\");
				}

				if (\$prev_version <= '3.3.3') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'sec_in_url', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'cat_in_url', '0'\");
				}

				if (\$prev_version <= '3.3.3.3') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'srch_tmplt', 'index2alb.html'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'srch_num_arts', '10'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'srch_introlen', '450'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'srch_show_img', '1'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'srch_num_inlist', '25'\");
				}

				if (\$prev_version <= '3.4.2') {
					\$installer->add_query('ADD', 'pronews_blocks', \"'category varchar(255) default NULL'\");
				}

				if (\$prev_version <= '3.4.5') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'inc_intro', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'SEOtitle', '0'\");
				}

				if (\$prev_version <= '3.4.6') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'mod_grp', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'auto_apprv', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'edit_time', ''\");
					\$installer->add_query('ADD', 'pronews_articles', \"'updtby varchar(50) NOT NULL'\");
					\$installer->add_query('ADD', 'pronews_articles', \"'updttime varchar(14) NOT NULL'\");
				}

				if (\$prev_version <= '3.4.7') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'disply_full', '1'\");
					\$installer->add_query('ADD', 'pronews_sections', \"'moderate tinyint(11) NOT NULL default 2'\");
				}

				if (\$prev_version <= '3.4.8') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'cal_ofst', ''\");
				}

				if (\$prev_version <= '4.0.1') {
					\$installer->add_query('ADD', 'pronews_articles', \"'seod varchar(255) default NULL'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'aspect', '0'\");
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'opn_grph', '0'\");
				}

				if (\$prev_version <= '4.0.3') {
					\$installer->add_query('INSERT', 'config_custom', \"'pro_news', 'img_max_width_remote', ''\");
				}

				if (\$prev_version <= '4.0.5') {
					\$installer->add_query('CHANGE', 'pronews_sections', \"'view view int(10) UNSIGNED NOT NULL default 0'\");
					\$installer->add_query('CHANGE', 'pronews_sections', \"'admin admin int(10) UNSIGNED NOT NULL default 1'\");
					\$installer->add_query('CHANGE', 'pronews_sections', \"'post post int(10) UNSIGNED NOT NULL default 1'\");
					\$installer->add_query('CHANGE', 'pronews_sections', \"'moderate moderate int(10) UNSIGNED NOT NULL default 2'\");
				}

				if (\$prev_version <= '4.0.6') {
					\$installer->add_query('CHANGE', 'pronews_sections', \"'description description text default NULL'\");
					\$installer->add_query('CHANGE', 'pronews_cats', \"'description description text default NULL'\");
				}


				return true;
			}

		// module uninstaller
			function uninstall() {
				global \$installer;
				foreach(\$this->dbtables as \$table) {
					\$installer->add_query('DROP', \$table);
				}
				return true;
			}
		}
	");
/*
if ($mod_dirname != "Pro_News") {
	$php_code = '<?php include_once(BASEDIR.\'rss/pro_news.php\'); ?>';
	$fh = fopen(BASEDIR.'rss/'.$mod_dirname.'.php', 'w');
	fwrite($fh, $php_code) or die('cannot open file /rss/'.$mod_dirname.'.php for write');
	fclose($fh);
	$php_code = '<?php include_once(BASEDIR.\'language/english/pro_news.php\'); ?>';
	$fh = fopen(BASEDIR.'language/english/'.$mod_dirname.'.php', 'w');
	fwrite($fh, $php_code) or die('cannot open file /language/english/'.$mod_dirname.'.php for write');
	fclose($fh);
}
*/
