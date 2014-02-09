<?php

// Copyright (C) 2014 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// dnscrypt.php for AstLinux
// 02-08-2014
//
// System location of rc.conf file
$CONFFILE = '/etc/rc.conf';
// System location of /mnt/kd/rc.conf.d directory
$DNSCRYPTCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.dnscrypt.conf file
$DNSCRYPTCONFFILE = '/mnt/kd/rc.conf.d/gui.dnscrypt.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$verbosity_menu = array (
  '3' => 'error',
  '5' => 'notice',
  '6' => 'info'
);

// Function: saveDNSCRYPTsettings
//
function saveDNSCRYPTsettings($conf_dir, $conf_file) {
  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.dnscrypt.conf - start ###\n###\n");

  $value = 'DNSCRYPT_PROXY="'.$_POST['proxy'].'"';
  fwrite($fp, "### DNSCrypt Enable\n".$value."\n");
  
  $value = 'DNSCRYPT_VERBOSITY="'.$_POST['verbosity'].'"';
  fwrite($fp, "### Log Level\n".$value."\n");
  
  $value = 'DNSCRYPT_SERVER_ADDRESS="'.tuq($_POST['server_address']).'"';
  fwrite($fp, "### Server Address\n".$value."\n");

  $value = 'DNSCRYPT_PROVIDER_NAME="'.tuq($_POST['provider_name']).'"';
  fwrite($fp, "### Provider Name\n".$value."\n");

  $value = 'DNSCRYPT_PROVIDER_KEY="'.tuq($_POST['provider_key']).'"';
  fwrite($fp, "### Provider Key\n".$value."\n");

  fwrite($fp, "### gui.dnscrypt.conf - end ###\n");
  fclose($fp);
  
  return($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;                                 
  } elseif (isset($_POST['submit_save'])) {
    $result = saveDNSCRYPTsettings($DNSCRYPTCONFDIR, $DNSCRYPTCONFFILE);
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('dnscrypt', 10, $result, 'init');
      $result = restartPROCESS('dnsmasq', $result, 99, 'init');
    } else {
      $result = 2;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  if (is_file($DNSCRYPTCONFFILE)) {
    $db = parseRCconf($DNSCRYPTCONFFILE);
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
      putHtml('<p style="color: green;">DNSCrypt Proxy Server'.statusPROCESS('dnscrypt').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart DNSCrypt" to apply any changed settings.</p>');
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
  <h2>DNSCrypt Proxy Server Configuration:</h2>
  </td></tr><tr><td width="240" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart DNSCrypt" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="60">&nbsp;</td><td width="100">&nbsp;</td><td width="100">&nbsp;</td><td>&nbsp;</td><td width="100">&nbsp;</td><td width="80">&nbsp;</td></tr>
<?php
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>DNSCrypt Proxy Server:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('DNSCrypt:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="proxy">');
  $value = getVARdef($db, 'DNSCRYPT_PROXY', $cur_db);
  putHtml('<option value="no">disabled</option>');
  $sel = ($value === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Log Level:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="verbosity">');
  if (($verbosity = getVARdef($db, 'DNSCRYPT_VERBOSITY', $cur_db)) === '') {
    $verbosity = '5';
  }
  foreach ($verbosity_menu as $key => $value) {
    $sel = ($verbosity === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="color: orange; text-align: center;" colspan="6">');
  putHtml('Note: Leave the fields below empty to use the OpenDNS defaults.');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Server Address:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'DNSCRYPT_SERVER_ADDRESS', $cur_db);
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="server_address" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Provider Name:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'DNSCRYPT_PROVIDER_NAME', $cur_db);
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="provider_name" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Provider Key:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'DNSCRYPT_PROVIDER_KEY', $cur_db);
  putHtml('<input type="text" size="80" maxlength="80" value="'.$value.'" name="provider_key" />');
  putHtml('</td></tr>');

  putHtml('</table>');
  putHtml('</form>');
  
  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
