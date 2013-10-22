<?php

// Copyright (C) 2013 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// slapd.php for AstLinux
// 10-21-2013
//
// System location of rc.conf file
$CONFFILE = '/etc/rc.conf';
// System location of /mnt/kd/rc.conf.d directory
$SLAPDCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.slapd.conf file
$SLAPDCONFFILE = '/mnt/kd/rc.conf.d/gui.slapd.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$anonymous_menu = array (
  'localhost' => 'localhost only',
  'yes' => 'access enabled',
  'no' => 'access disabled'
);

// Function: saveSLAPDsettings
//
function saveSLAPDsettings($conf_dir, $conf_file) {
  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.slapd.conf - start ###\n###\n");

  $value = 'LDAP_SERVER="'.$_POST['slapd_enabled'].'"';
  fwrite($fp, "### LDAP Server Enabled\n".$value."\n");
  
  $value = 'LDAP_SERVER_ANONYMOUS="'.$_POST['slapd_anonymous'].'"';
  fwrite($fp, "### LDAP Server Anonymous\n".$value."\n");

  $value = 'LDAP_SERVER_BASEDN="'.tuq($_POST['slapd_basedn']).'"';
  fwrite($fp, "### LDAP Server Base DN\n".$value."\n");

  $value = 'LDAP_SERVER_PASS="'.string2RCconfig(trim($_POST['slapd_admin_pass'])).'"';
  fwrite($fp, "### LDAP Server Password\n".$value."\n");

  fwrite($fp, "### gui.slapd.conf - end ###\n");
  fclose($fp);
  
  return($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;                                 
  } elseif (isset($_POST['submit_save'])) {
    $result = saveSLAPDsettings($SLAPDCONFDIR, $SLAPDCONFFILE);
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('slapd', 10, $result, 'init');
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_sip_tls'])) {
    $result = saveSLAPDsettings($SLAPDCONFDIR, $SLAPDCONFFILE);
    header('Location: /admin/siptlscert.php');
    exit;
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  if (is_file($SLAPDCONFFILE)) {
    $db = parseRCconf($SLAPDCONFFILE);
  } else {
    $db = NULL;
  }

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 2) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error creating file.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">LDAP Server has Restarted.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart LDAP" to apply any changed settings.</p>');
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
  <h2>LDAP Server Configuration:</h2>
  </td></tr><tr><td width="240" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart LDAP" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="60">&nbsp;</td><td width="100">&nbsp;</td><td width="100">&nbsp;</td><td>&nbsp;</td><td width="100">&nbsp;</td><td width="80">&nbsp;</td></tr>
<?php
if (! is_file('/mnt/kd/ssl/sip-tls/keys/server.crt') || ! is_file('/mnt/kd/ssl/sip-tls/keys/server.key')) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Missing SIP-TLS Server Certificate:</strong> <i>(Shared with LDAP Server)</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Create SIP-TLS<br />Server Certificate:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<input type="submit" value="SIP-TLS Certificate" name="submit_sip_tls" class="button" />');
  putHtml('</td></tr>');
}

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>LDAP Directory Server:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('LDAP Server:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $slapd_enable = getVARdef($db, 'LDAP_SERVER');
  putHtml('<select name="slapd_enabled">');
  putHtml('<option value="no">disabled</option>');
  $sel = ($slapd_enable === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Anonymous Read-Only:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $anonymous = getVARdef($db, 'LDAP_SERVER_ANONYMOUS');
  putHtml('<select name="slapd_anonymous">');
  foreach ($anonymous_menu as $key => $value) {
    $sel = ($anonymous === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Base DN:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'LDAP_SERVER_BASEDN')) === '') {
    $value = 'dc=ldap';
  }
  putHtml('<input type="text" size="56" maxlength="128" name="slapd_basedn" value="'.$value.'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Admin Password<br />cn=admin:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'LDAP_SERVER_PASS');
  $value = htmlspecialchars(RCconfig2string($value));
  putHtml('<input type="password" size="56" maxlength="128" name="slapd_admin_pass" value="'.$value.'" />');
  putHtml('<i><br />(defaults to web interface "admin" password)</i>');
  putHtml('</td></tr>');

  putHtml('</table>');
  putHtml('</form>');
  
  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
