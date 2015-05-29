<?php
/***************************************************************************
   Coppermine 1.3.1 for CPG-Dragonfly™
  **************************************************************************
   Port Copyright (c) 2004-2005 CPG Dev Team
   http://dragonflycms.com/
  **************************************************************************
   v1.1 (c) by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.
  **************************************************************************
  Last modification notes:
  Source: /cvs/html/modules/coppermine/upload.php,v
  Revision: 9.2
  Author: djmaze
  Date: 2005/01/31 10:31:58
  **************************************************************************
  Adapted from above for batch upload
  $Revision: 1.2 $
  $Date: 2012-11-28 08:00:54 $
  Author: layingback
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }
if (!can_admin($module_name)) { die('Access Denied'); }
define('BATCHUPLOAD_PHP', true);
require("modules/" . $module_name . "/include/load.inc");
if (!USER_CAN_UPLOAD_PICTURES){cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);}
if ($CLASS['member']->demo){
      cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
}
// Type 0 => input
// 1 => file input
// 2 => album list
$data = array(
    sprintf(MAX_FSIZE, $CONFIG['max_upl_size']),
    array(PICTURE.'<br /><span class="tiny">Select 1 or more files to upload</span>', 'userpicture', 1),
);
$batch_limit  = 0;	// Maximum number of files to upload in 1 batch - use 0 for unlimited

if ($batch_limit == 0) {
	$limit = UNLIMITED;
	$max_num = -1;
} else {
	$limit = $batch_limit;
	$max_num = $batch_limit;
}

function bform_label($text) {
    echo <<<EOT
        <tr>
            <td class="tableh2" colspan="2">
                <b>$text</b>
            </td>
        </tr>

EOT;
}

function bform_input($text, $name, $max_length) {
    if ($text == ''){
        echo "<input type=\"hidden\" name=\"$name\" value=\"\" />\n";
          return;
    }

}

function bform_file_input($text, $name) {
	global $CONFIG, $limit;
	$max_file_size = $CONFIG['max_upl_size'] << 10;
	$list = LIST_FILES;
	$f_lim = FILE_LIMIT;

echo <<<EOT
        <tr>
            <td class="tableb">
                        $text
            </td>
            <td class="tableb" valign="top">
                <input type="hidden" name="MAX_FILE_SIZE" value="$max_file_size" />
                <input type="file" name="file_1" size="40" class="listbox" id="first_file_element" />
            </td>
        </tr>


		<tr>
			<td class="tablef">$list<br /><span class="tiny">$f_lim: $limit</span></td>
			<td class="tablef">
				<!-- This is where the output will appear -->
				<div id="files_list"></div>
			</td>
		</tr>

EOT;
}

function bform_alb_list_box($text, $name) {
    global $CONFIG, $public_albums_list;
    $sel_album = isset($_GET['album']) ? $_GET['album'] : 0;

}

function bform_textarea($text, $name, $max_length) {
    global $ALBUM_DATA;
    $value = $ALBUM_DATA[$name];
}

function bcreate_form(& $data) {
    foreach($data as $element){
        if (is_array($element)){
            switch ($element[2]){
                case 0 :
                    bform_input($element[0], $element[1], $element[3]);
                    break;
                case 1 :
                    bform_file_input($element[0], $element[1]);
                    break;
                default:
                    cpg_die(_ERROR, 'Invalid action for form creation', __FILE__, __LINE__);
             } // switch
        } else {
            bform_label($element);
        }
    }
}

pageheader(UP_TITLE);
starttable("100%", UP_TITLE, 2);

echo '
    <form id="multifile" method="post" action="'.getlink('&amp;file=filesupload').'" enctype="multipart/form-data">
    <input type="hidden" name="event" value="picture" />
';
bcreate_form($data);
echo '
    <tr>
        <td colspan="2" align="center" class="tablef">
            <br /><i>"'.STEP1.'"</i><br /><input type="submit" value="'.BATUP_TITLE.'" class="button" /><br /><br />
        </td>
        </form>
    </tr>
';

echo "
	<script>
		var multi_selector = new MultiSelector( document.getElementById( 'files_list' ), ".$max_num." );
		multi_selector.addElement( document.getElementById( 'first_file_element' ) );
	</script>
";

endtable();
pagefooter();
