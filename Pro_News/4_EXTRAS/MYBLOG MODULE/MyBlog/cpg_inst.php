<?php
/*********************************************
  MyBlog Module for Pro News on Dragonfly CMS
  ********************************************
  Copyright © 2013 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 1.1 $
  $Date: 2013-05-14 10:09:27 $
  Author: layingback
**********************************************/
if (!defined('ADMIN_MOD_INSTALL')) { exit; }

$mod_dirname = basename(dirname(__FILE__));

eval("
		class $mod_dirname {
			var \$radmin;
			var \$version;
			var \$modname;
			var \$description;
			var \$author;
			var \$website;
			var \$prefix;
			var \$dbtables;
			function $mod_dirname() {
				global \$prefix;
				\$this->radmin = true;
				\$this->version = '1.0';
				\$this->modname = 'MyPage';
				\$this->description = 'Adjunct to Pro_News to Redirect Members\' Latest Blog';
				\$this->author = 'layingback';
				\$this->website = 'layingback.net';
				\$this->prefix = strtolower(basename(dirname(__FILE__)));
				\$this->dbtables = array();
			}

			function install() {
				global \$installer;
				return true;
			}

			function uninstall() {
				global \$installer;
				return true;
			}

			function upgrade(\$prev_version) {
				global \$db, \$prefix, \$installer;
				Cache::array_delete('MAIN_CFG');
				return true;
			}
		}
	");
