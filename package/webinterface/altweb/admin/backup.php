<?php

// Copyright (C) 2017 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// backup.php for AstLinux
// 09-10-2017
//
// System location of rc.conf file
$CONFFILE = '/etc/rc.conf';
// System location of /mnt/kd/rc.conf.d directory
$BACKUPCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.backup.conf file
$BACKUPCONFFILE = '/mnt/kd/rc.conf.d/gui.backup.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$prune_age_menu = array (
  '' => 'disabled',
  '10' => '10 days',
  '20' => '20 days',
  '30' => '30 days',
  '60' => '60 days',
  '90' => '90 days',
  '120' => '120 days',
  '180' => '180 days',
  '240' => '240 days',
  '300' => '300 days',
  '400' => '400 days'
);

// Function: saveBACKUPsettings
//
function saveBACKUPsettings($conf_dir, $conf_file) {
  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.backup.conf - start ###\n###\n");

  fwrite($fp, "### Tarsnap Backup\n");
  $value = 'BACKUP_KD_DEFAULTS="'.$_POST['kd_defaults'].'"';
  fwrite($fp, $value."\n");
  $value = 'BACKUP_KD_INCLUDE_DIRS="'.tuq($_POST['kd_include_dirs']).'"';
  fwrite($fp, $value."\n");
  $value = 'BACKUP_KD_INCLUDE_FILES="'.tuq($_POST['kd_include_files']).'"';
  fwrite($fp, $value."\n");

  $value = 'BACKUP_ASTURW_DEFAULTS="'.$_POST['asturw_defaults'].'"';
  fwrite($fp, $value."\n");
  $value = 'BACKUP_ASTURW_INCLUDE_DIRS="'.tuq($_POST['asturw_include_dirs']).'"';
  fwrite($fp, $value."\n");
  $value = 'BACKUP_ASTURW_INCLUDE_FILES="'.tuq($_POST['asturw_include_files']).'"';
  fwrite($fp, $value."\n");
  
  fwrite($fp, "### Automatic Archive Aging\n");
  $value = 'BACKUP_PRUNE_AGE_DAYS="'.$_POST['prune_age'].'"';
  fwrite($fp, $value."\n");
  
  fwrite($fp, "### Email Notifications\n");
  $value = 'BACKUP_NOTIFY="'.tuq($_POST['notify']).'"';
  fwrite($fp, $value."\n");
  $value = 'BACKUP_NOTIFY_FROM="'.tuq($_POST['notify_from']).'"';
  fwrite($fp, $value."\n");

  fwrite($fp, "### gui.backup.conf - end ###\n");
  fclose($fp);
  
  return($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;                                 
  } elseif (isset($_POST['submit_save'])) {
    $result = saveBACKUPsettings($BACKUPCONFDIR, $BACKUPCONFFILE);
    if ($result == 11) {
      $result = restartPROCESS('', $result, 99, 'apply');
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  if (is_file($BACKUPCONFFILE)) {
    $db = parseRCconf($BACKUPCONFFILE);
    $cur_db = parseRCconf($CONFFILE);
  } else {
    $db = parseRCconf($CONFFILE);
    $cur_db = NULL;
  }

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 3) {
      putHtml('<p style="color: red;">Error creating file.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Tarsnap Backup settings are saved and applied.</p>');
    } elseif ($result == 99) {
      putHtml('<p style="color: red;">Action Failed.</p>');
    } elseif ($result == 999) {
      putHtml('<p style="color: red;">Permission denied for user "'.$global_user.'".</p>');
    } else {
      putHtml('<p style="color: orange;">No Action.</p>');
    }
  } else {
    putHtml('<p>&nbsp;</p>');
  }
  putHtml("</center>");
?>
  <center>
  <table class="layout"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;" colspan="2">
  <h2>Tarsnap Backup Options:</h2>
  </td></tr><tr><td style="text-align: center;" colspan="2">
  <input type="submit" class="formbtn" value="Save and Apply Settings" name="submit_save" />
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="100">&nbsp;</td><td width="100">&nbsp;</td><td width="100">&nbsp;</td><td>&nbsp;</td><td width="100">&nbsp;</td><td width="80">&nbsp;</td></tr>
<?php
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Tarsnap Backup:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('<strong>Backup [kd]:</strong>');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<i>Directory and File names are relative to /mnt/kd/</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Backup [kd] Defaults:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="kd_defaults">');
  $value = getVARdef($db, 'BACKUP_KD_DEFAULTS', $cur_db);
  $sel = ($value === 'no') ? ' selected="selected"' : '';
  putHtml('<option value="no"'.$sel.'>disabled</option>');
  $sel = ($value !== 'no') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Backup [kd] Dirs:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'BACKUP_KD_INCLUDE_DIRS', $cur_db);
  putHtml('<input type="text" size="82" maxlength="256" value="'.$value.'" name="kd_include_dirs" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Backup [kd] Files:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'BACKUP_KD_INCLUDE_FILES', $cur_db);
  putHtml('<input type="text" size="82" maxlength="256" value="'.$value.'" name="kd_include_files" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('<strong>Backup [asturw]:</strong>');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<i>Directory and File names are relative to /oldroot/mnt/asturw/</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Backup [asturw] Defaults:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="asturw_defaults">');
  $value = getVARdef($db, 'BACKUP_ASTURW_DEFAULTS', $cur_db);
  $sel = ($value === 'no') ? ' selected="selected"' : '';
  putHtml('<option value="no"'.$sel.'>disabled</option>');
  $sel = ($value !== 'no') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Backup [asturw] Dirs:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'BACKUP_ASTURW_INCLUDE_DIRS', $cur_db);
  putHtml('<input type="text" size="82" maxlength="256" value="'.$value.'" name="asturw_include_dirs" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Backup [asturw] Files:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'BACKUP_ASTURW_INCLUDE_FILES', $cur_db);
  putHtml('<input type="text" size="82" maxlength="256" value="'.$value.'" name="asturw_include_files" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Automatic Archive Aging:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Prune Backups:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="prune_age">');
  $prune_age = getVARdef($db, 'BACKUP_PRUNE_AGE_DAYS', $cur_db);
  foreach ($prune_age_menu as $key => $value) {
    $sel = ($prune_age === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('old or older archives are deleted');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('&nbsp;');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('Aging Exceptions:<br />');
  putHtml('&bull; Keep 1st of the month archives for a year<br />');
  putHtml('&bull; Never delete July 1st archives for any year');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Email Notifications:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Notify Email Addresses<br />To:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'BACKUP_NOTIFY', $cur_db);
  putHtml('<input type="text" size="48" maxlength="256" value="'.$value.'" name="notify" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Notify Email Address<br />From:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'BACKUP_NOTIFY_FROM', $cur_db);
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="notify_from" />');
  putHtml('</td></tr>');

  putHtml('</table>');
  putHtml('</form>');
  
  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
