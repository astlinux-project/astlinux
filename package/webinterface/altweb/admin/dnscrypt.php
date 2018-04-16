<?php

// Copyright (C) 2014-2018 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// dnscrypt.php for AstLinux
// 02-08-2014
// 04-06-2018, Added Import sdns:// Stamp
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

// Function: urlsafe_b64decode
//
function urlsafe_b64decode($data)
{
  $data = preg_replace('/[\t-\x0d\s]/', '', strtr($data, '-_', '+/'));
  $mod4 = strlen($data) % 4;
  if ($mod4) {
    $data .= substr('====', $mod4);
  }
  return base64_decode($data, TRUE);
}

// Function: import_dnscrypt_stamp
//
function import_dnscrypt_stamp($stamp) {

  $prefix = 'sdns://';
  $prefix_len = strlen($prefix);

  if (strncasecmp($stamp, $prefix, $prefix_len) == 0) {
    $in = substr($stamp, $prefix_len);
  } else {
    $in = $stamp;
  }
  if (($data = urlsafe_b64decode($in)) === FALSE) {
    return(FALSE);
  }
  $extract = @unpack('Cident/V2props/Caddr_len', $data);
  if ($extract['ident'] != 1) {
    return(FALSE);
  }
  if (($addr_len = $extract['addr_len']) == 0) {
    return(FALSE);
  }
  $extract = @unpack('Cident/V2props/Caddr_len/a'.$addr_len.'addr/Cpk_len/C32pk/Cname_len', $data);
  if ($extract['pk_len'] != 32) {
    return(FALSE);
  }
  if (($name_len = $extract['name_len']) == 0) {
    return(FALSE);
  }
  $extract = @unpack('Cident/V2props/Caddr_len/a'.$addr_len.'addr/Cpk_len/C32pk/Cname_len/a'.$name_len.'name', $data);

  // Sanity check for successful unpack
  if (! isset($extract['name'])) {
    return(FALSE);
  }

  $pk = '';
  for ($i = 1; $i <= 32; $i += 2) {
    $j = $i + 1;
    $pk .= sprintf('%02X%02X%s', $extract["pk$i"], $extract["pk$j"], ($j < 32 ? ':' : ''));
  }

  $dnscrypt['server_address'] = $extract['addr'];
  $dnscrypt['provider_name'] = $extract['name'];
  $dnscrypt['provider_key'] = $pk;

  // dnscrypt-proxy 1.9.5 does not work with [IPv6] bracket-format addresses missing the port
  // Add the default port 443 for [IPv6] if missing
  if (substr($dnscrypt['server_address'], -1) === ']') {
    $dnscrypt['server_address'] .= ':443';
  }

  return($dnscrypt);
}

// Function: get_dnscrypt_stamp
//
function get_dnscrypt_stamp($stamp, &$result) {

  if ($stamp === '') {
    return(FALSE);
  }

  if (($dnscrypt = import_dnscrypt_stamp($stamp)) === FALSE) {
    $result = 5;
  }
  return($dnscrypt);
}

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

  $value = 'DNSCRYPT_EPHEMERAL_KEYS="'.$_POST['ephemeral_keys'].'"';
  fwrite($fp, "### Ephemeral Keys\n".$value."\n");

  if (($dnscrypt = get_dnscrypt_stamp(trim($_POST['import_stamp']), $result)) !== FALSE) {
    $addr = 'DNSCRYPT_SERVER_ADDRESS="'.$dnscrypt['server_address'].'"';
    $name = 'DNSCRYPT_PROVIDER_NAME="'.$dnscrypt['provider_name'].'"';
    $key = 'DNSCRYPT_PROVIDER_KEY="'.$dnscrypt['provider_key'].'"';
  } else {
    $addr = 'DNSCRYPT_SERVER_ADDRESS="'.tuq($_POST['server_address']).'"';
    $name = 'DNSCRYPT_PROVIDER_NAME="'.tuq($_POST['provider_name']).'"';
    $key = 'DNSCRYPT_PROVIDER_KEY="'.tuq($_POST['provider_key']).'"';
  }
  fwrite($fp, "### Server Address\n".$addr."\n");
  fwrite($fp, "### Provider Name\n".$name."\n");
  fwrite($fp, "### Provider Key\n".$key."\n");

  if (($dnscrypt = get_dnscrypt_stamp(trim($_POST['import_stamp2']), $result)) !== FALSE) {
    $addr = 'DNSCRYPT_2SERVER_ADDRESS="'.$dnscrypt['server_address'].'"';
    $name = 'DNSCRYPT_2PROVIDER_NAME="'.$dnscrypt['provider_name'].'"';
    $key = 'DNSCRYPT_2PROVIDER_KEY="'.$dnscrypt['provider_key'].'"';
  } else {
    $addr = 'DNSCRYPT_2SERVER_ADDRESS="'.tuq($_POST['server_address2']).'"';
    $name = 'DNSCRYPT_2PROVIDER_NAME="'.tuq($_POST['provider_name2']).'"';
    $key = 'DNSCRYPT_2PROVIDER_KEY="'.tuq($_POST['provider_key2']).'"';
  }
  fwrite($fp, "### 2nd Server Address\n".$addr."\n");
  fwrite($fp, "### 2nd Provider Name\n".$name."\n");
  fwrite($fp, "### 2nd Provider Key\n".$key."\n");

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
    } elseif ($result == 5) {
      putHtml('<p style="color: red;">Import Stamp skipped, not a valid DNSCrypt stamp.</p>');
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
  </td></tr><tr><td width="280" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart DNSCrypt" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="60">&nbsp;</td><td width="100">&nbsp;</td><td width="100">&nbsp;</td><td>&nbsp;</td><td width="100">&nbsp;</td><td width="80">&nbsp;</td></tr>
<?php
if (isDNS_TLS()) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>The alternate DNS-TLS Proxy Server is running!</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="color: red; text-align: center;" colspan="6">');
  putHtml('Warning: Both DNSCrypt and DNS-TLS can\'t be active simultaneously.</td></tr>');
}
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

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Ephemeral Keys:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="ephemeral_keys">');
  $value = getVARdef($db, 'DNSCRYPT_EPHEMERAL_KEYS', $cur_db);
  putHtml('<option value="no">disabled</option>');
  $sel = ($value === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Primary Server:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Import sdns:// Stamp:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<input type="text" size="80" maxlength="512" value="" name="import_stamp" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="color: orange; text-align: center;" colspan="6">');
  putHtml('Note: Any empty fields below use the OpenDNS defaults.');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Server Address:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'DNSCRYPT_SERVER_ADDRESS', $cur_db)) === '') {
    $value = '208.67.220.220:443';
  }
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

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Secondary Server:</strong> <i>(optional)</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Import sdns:// Stamp:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<input type="text" size="80" maxlength="512" value="" name="import_stamp2" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="color: orange; text-align: center;" colspan="6">');
  putHtml('Note: The "Server Address" field below must be defined to enable the<br />');
  putHtml('secondary server. Other empty fields use the OpenDNS defaults.');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Server Address:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'DNSCRYPT_2SERVER_ADDRESS', $cur_db);
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="server_address2" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Provider Name:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'DNSCRYPT_2PROVIDER_NAME', $cur_db);
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="provider_name2" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Provider Key:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'DNSCRYPT_2PROVIDER_KEY', $cur_db);
  putHtml('<input type="text" size="80" maxlength="80" value="'.$value.'" name="provider_key2" />');
  putHtml('</td></tr>');

  putHtml('</table>');
  putHtml('</form>');

  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
