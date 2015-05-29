<?php
/*********************************************
  Coppermine Admin Batch Upload for CPG Dragonfly CMS
  ********************************************
  Copyright © 2009 M Waldron aka layingback
  http://layingback.net
  Copyright © 2005 Shawn Parker
  http://top-frog.com/files/scripts/upload_sample.phps
  Using upload-multiple-files-with-a-single-file-element
  Copyright © 2005 The Stickman
  http://the-stickman.com/web-development/javascript/upload-multiple-files-with-a-single-file-element/

  $Revision: 1.3 $
  $Date: 2012-11-28 08:01:55 $
  Author: layingback

  Released under the terms and conditions
  of the GNU GPL version 2 or any later version

**********************************************/

// This script has been updated. Please visit: http://www.top-frog.com/archives/2006/12/22/classes_for_file_uploading_in_php

/*
	this sample is proceedural for those not familiar with OOP. Simply include this file in your
	form processing script and it will handle the uploads. You'll definitely want to make changes to the
	upload directory and to some of the functionality to change it to how you like to work

!!	This file does no security checking - this solely handles file uploads -
!!	this file does not handle any security functions. Heed that warning! You use this file at your
!!	own risk and please do not publically accept files if you don't know what you're doing with
!!	server security.


	at the end of this script you will have two variables
	$filenames - an array that contains the names of the file uploads that succeeded
	$error - an array of errors that occured while processing files


	if the max file size in the form is more than what is set in php.ini then an addition
	needs to be made to the htaccess file to accomodate this

	add this to  your .htaccess file for this directory
	php_value post_max_size 10M
	php_value upload_max_filesize 10M

	replace 10M to match the value you entered above for $max_file_size

*/

//	if (!defined('ADMIN_PAGES')) { exit; }
if (!can_admin($module_name)) { die('Access Denied'); }
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); }
global $MAIN_CFG;
define('FILESUPLOAD_PHP', true);
require("modules/" . $module_name . "/include/load.inc");
if (!USER_CAN_UPLOAD_PICTURES){cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);}
if ($CLASS['member']->demo){
	  cpg_die(_ERROR, PERM_DENIED, __FILE__, __LINE__);
}

// upload dir - relative from document root
// this needs to be a folder that is writeable by the server eg. 'modules/coppermine/albums/' or 'uploads/coppermine/albums/batch/'
$destination = 'uploads/coppermine/albums/batch/';			// adjust as necessary - needs ../albums/ to be present

if(isset($_FILES)) {
	// initialize error var for processing
	$error = array();

	// acceptable files
	// if array is blank then all file types will be accepted
	$filetypes = array(
//					'ai' => 'application/postscript',
//					'bin' => 'application/octet-stream',
//					'bmp' => 'image/x-ms-bmp',
//					'css' => 'text/css',
//					'csv' => 'text/plain',
//					'doc' => 'application/msword',
//					'dot' => 'application/msword',
//					'eps' => 'application/postscript',
					'gif' => 'image/gif',
//					'gz' => 'application/x-gzip',
//					'htm' => 'text/html',
//					'html' => 'text/html',
//					'ico' => 'image/x-icon',
					'jpg' => 'image/jpeg',
					'.jpg' => 'image/pjpeg',
					'jpe' => 'image/jpeg',
					'.jpe' => 'image/pjpeg',
					'jpeg' => 'image/jpeg',
					'.jpeg' => 'image/pjpeg',
//					'js' => 'text/javascript',
//					'mov' => 'video/quicktime',
//					'mp3' => 'audio/mpeg',
//					'mp4' => 'video/mp4',
//					'mpeg' => 'video/mpeg',
//					'mpg' => 'video/mpeg',
//					'pdf' => 'application/pdf',
					'png' => 'image/png',
					'.png' => 'image/x-png',
//					'pot' => 'application/vnd.ms-powerpoint',
//					'pps' => 'application/vnd.ms-powerpoint',
//					'ppt' => 'application/vnd.ms-powerpoint',
//					'qt' => 'video/quicktime',
//					'ra' => 'audio/x-pn-realaudio',
//					'ram' => 'audio/x-pn-realaudio',
//					'rtf' => 'application/rtf',
//					'swf' => 'application/x-shockwave-flash',
//					'tar' => 'application/x-tar',
//					'tgz' => 'application/x-compressed',
					'tif' => 'image/tiff',
					'tiff' => 'image/tiff',
//					'txt' => 'text/plain',
//					'xls' => 'application/vnd.ms-excel',
//					'zip' => 'application/zip'
				);

	// function to check for acceptable file type
	function okFileType($type, $file) {
		global $filetypes, $error;
		// if filetypes array is empty then let everything through
		if(count($filetypes) < 1) {
			return true;
		}
		// if no match is made to a valid file types array then kick it back
		elseif(!in_array($type,$filetypes)) {
			$error[] = $type." ".NOT_FILETYPE." - ".U_FILE." ".$file['name']." ".IGNORED;
			return false;
		}
		// else - let the file through
		else {
			return true;
		}
	}

	// function to check and move files
	function processFiles($file) {
		global $destination, $error;
		// check destination folder exits
		if (!file_exists($destination)) {
			$error[] = FOLDER.' <b>'.$destination .'</b> - '.DIR_NOT_EXIST;
			return false;
		}

		// set full path/name of file to be moved
		$upload_file = $destination.$file['name'];

		if(file_exists($upload_file)) {
			$error[] = $file['name'].' - '.FILE_EXISTS;
			return false;
		}

		if(!move_uploaded_file($file['tmp_name'], $upload_file)) {
			// failed to move file
			$error[] = UPLOAD_FAIL.' '.$file['name'].' - '.TRYAGAIN;
			return false;
		} else {
			// upload OK - change file permissions
//			chmod($upload_file, 0755);
			return true;
		}
	}

	// check to make sure files were uploaded
	$no_files = 0;
	$uploaded = array();
	foreach($_FILES as $file) {
		switch($file['error']) {
			case 0:
				// file found
				if($file['name'] != NULL && okFileType($file['type'], $file) != false) {
					// process the file
					if(processFiles($file) == true) {
						$uploaded[] = $file['name'];
					}
				}
				break;

			case (1|2):
				// upload too large
				$error[] = FILE_TOOLARGE.' '.$file['name'];
				break;

			case 4:
				// no file uploaded
				break;

			case (6|7):
				// no temp folder or failed write - server config errors
				$error[] = INT_ERROR.' '.$file['name'];
				break;
		}
	}

pageheader(BATUP_TITLE);
starttable("100%", BATUP_TITLE, 2);
$text = UPLOADED;
foreach($uploaded as $file) {
echo <<<EOT
        <tr>
            <td class="tableb" valign="top">
                $text
            </td>
            <td class="tableb" valign="top">
                $file
            </td>
        </tr>
EOT;
}
$text = ERRORS;
foreach($error as $file) {
echo <<<EOT
	<tr>
		<td class="tableb" valign="top">
			<b>$text</b>
		</td>
		<td class="tableb" valign="top">
			$file
		</td>
	</tr>
EOT;
}
$next = getlink('&amp;file=searchnew');
$next_l = SEARCHNEW_LNK;
$helptxt = STEP2;
echo <<<EOT
        <tr>
            <td class="tableb" valign="top" colspan="2">
                <br /><br /><center><i>"$helptxt"</i><br /><br /><a href="$next"> $next_l </a><br /><br /></center>
            </td>
        </tr>
EOT;

endtable();
pagefooter();

}
?>
