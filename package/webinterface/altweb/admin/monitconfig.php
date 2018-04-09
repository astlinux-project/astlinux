<?php

// Copyright (C) 2014 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// monitconfig.php for AstLinux
// 12-16-2014
//
// System location of rc.conf file
$CONFFILE = '/etc/rc.conf';
// System location of /mnt/kd/rc.conf.d directory
$MONITCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.monit.conf file
$MONITCONFFILE = '/mnt/kd/rc.conf.d/gui.monit.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$check_interval_menu = array (
  '20' => '20 seconds',
  '30' => '30 seconds',
  '40' => '40 seconds',
  '60' => '1 minute',
  '120' => '2 minutes',
  '300' => '5 minutes'
);

// Function: saveMONITsettings
//
function saveMONITsettings($conf_dir, $conf_file) {

  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.monit.conf - start ###\n###\n");

  $value = 'MONIT_SERVER="'.$_POST['monit_server'].'"';
  fwrite($fp, "### Monit Server\n".$value."\n");

  $value = 'MONIT_CHECK_INTERVAL="'.$_POST['monit_check_interval'].'"';
  fwrite($fp, "### Check Interval\n".$value."\n");

  fwrite($fp, "### Notifications\n");
  $value = 'MONIT_NOTIFY="'.tuq($_POST['monit_notify']).'"';
  fwrite($fp, $value."\n");

  $value = 'MONIT_NOTIFY_FROM="'.tuq($_POST['monit_notify_from']).'"';
  fwrite($fp, $value."\n");

  fwrite($fp, "### gui.monit.conf - end ###\n");
  fclose($fp);

  return($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $result = saveMONITsettings($MONITCONFDIR, $MONITCONFFILE);
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('monit', 10, $result, 'init');
    } else {
      $result = 2;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  if (is_file($MONITCONFFILE)) {
    $db = parseRCconf($MONITCONFFILE);
    $cur_db = parseRCconf($CONFFILE);
  } else {
    $db = parseRCconf($CONFFILE);
    $cur_db = NULL;
  }

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 2) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error creating file.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">Monit Monitoring'.statusPROCESS('monit').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart Monit" to apply any changed settings.</p>');
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
  <h2>Monit Monitoring Configuration:</h2>
  </td></tr><tr><td width="280" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart Monit" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="60">&nbsp;</td><td width="100">&nbsp;</td><td width="100">&nbsp;</td><td>&nbsp;</td><td width="100">&nbsp;</td><td width="80">&nbsp;</td></tr>
<?php
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Monit Server:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Monitoring:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $monit_server = getVARdef($db, 'MONIT_SERVER', $cur_db);
  putHtml('<select name="monit_server">');
  putHtml('<option value="no">disabled</option>');
  $sel = ($monit_server === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Check Interval:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="monit_check_interval">');
  if (($check_interval = getVARdef($db, 'MONIT_CHECK_INTERVAL', $cur_db)) === '') {
    $check_interval = '60';
  }
  foreach ($check_interval_menu as $key => $value) {
    $sel = ($check_interval === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Monit Notifications:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Email Address To:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'MONIT_NOTIFY', $cur_db);
  putHtml('<input type="text" size="72" maxlength="256" value="'.$value.'" name="monit_notify" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Email Address From:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'MONIT_NOTIFY_FROM', $cur_db);
  putHtml('<input type="text" size="36" maxlength="128" value="'.$value.'" name="monit_notify_from" />');
  putHtml('</td></tr>');

  putHtml('</table>');
  putHtml('</form>');

  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
