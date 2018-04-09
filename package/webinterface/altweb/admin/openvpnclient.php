<?php

// Copyright (C) 2008-2017 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// openvpnclient.php for AstLinux
// 04-15-2009
// 08-13-2010, Added QoS Passthrough, setting passtos
// 02-13-2013, Added OpenVPN 2.3 IPv6 support
// 02-23-2013, Added User/Pass support
// 03-24-2017, Change from OVPNC_NSCERTTYPE to OVPNC_REMOTE_CERT_TLS
//
// System location of /mnt/kd/rc.conf.d directory
$OVPNCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.openvpnclient.conf file
$OVPNCONFFILE = '/mnt/kd/rc.conf.d/gui.openvpnclient.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

require_once '../common/openssl-openvpnclient.php';

$openssl = openvpnclientSETUP();

$auth_method_menu = array (
  '' => 'Certificate',
  'yes' => 'Cert. + User/Pass'
);

$protocol_menu = array (
  'udp' => 'UDP v4',
  'tcp-client' => 'TCP v4',
  'udp6' => 'UDP v6',
  'tcp6-client' => 'TCP v6'
);

$cipher_menu = array (
  '' => 'Use Default',
  'BF-CBC' => 'BF-CBC',
  'AES-128-CBC' => 'AES-128-CBC',
  'AES-192-CBC' => 'AES-192-CBC',
  'AES-256-CBC' => 'AES-256-CBC'
);

$auth_hmac_menu = array (
  '' => 'Use Default',
  'SHA1' => 'SHA-1',
  'SHA256' => 'SHA-256'
);

$nscerttype_menu = array (
  '' => 'No',
  'server' => 'Server'
);

$verbosity_menu = array (
  '1' => 'Low',
  '3' => 'Medium',
  '4' => 'High',
  '0' => 'None'
);

// Function: parseUserPass
//
function parseUserPass($user_pass, $type) {
  $str = '';
  if ($user_pass !== '') {
    $index = 0;
    $match = ($type === 'user') ? 1 : 2;
    $strtokens = explode(' ', $user_pass);
    foreach ($strtokens as $value) {
      if ($value !== '') {
        if (++$index == $match) {
          $str = $value;
          break;
        }
      }
    }
  }
  return($str);
}

// Function: saveOVPNCsettings
//
function saveOVPNCsettings($conf_dir, $conf_file) {
  global $openssl;

  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.openvpnclient.conf - start ###\n###\n");

  $value = 'OVPNC_DEV="'.$_POST['device'].'"';
  fwrite($fp, "### Device\n".$value."\n");

  $value = 'OVPNC_PORT="'.tuq($_POST['port']).'"';
  fwrite($fp, "### Port Number\n".$value."\n");

  $value = 'OVPNC_PROTOCOL="'.$_POST['protocol'].'"';
  fwrite($fp, "### Protocol\n".$value."\n");

  $value = 'OVPNC_VERBOSITY="'.$_POST['verbosity'].'"';
  fwrite($fp, "### Log Verbosity\n".$value."\n");

  $value = 'OVPNC_LZO="'.$_POST['compression'].'"';
  fwrite($fp, "### Compression\n".$value."\n");

  $value = 'OVPNC_QOS="'.$_POST['qos_passthrough'].'"';
  fwrite($fp, "### QoS Passthrough\n".$value."\n");

  $value = 'OVPNC_CIPHER="'.$_POST['cipher_menu'].'"';
  fwrite($fp, "### Cipher\n".$value."\n");

  $value = 'OVPNC_AUTH="'.$_POST['auth_hmac'].'"';
  fwrite($fp, "### Auth HMAC\n".$value."\n");

  if ($_POST['auth_method'] === 'yes' && tuq($_POST['auth_user']) !== '' && trim($_POST['auth_pass']) !== '') {
    $value = 'OVPNC_USER_PASS="'.tuq($_POST['auth_user']).' '.string2RCconfig(trim($_POST['auth_pass'])).'"';
  } else {
    $value = 'OVPNC_USER_PASS=""';
  }
  fwrite($fp, "### Auth User/Pass\n".$value."\n");

  $value = 'OVPNC_REMOTE_CERT_TLS="'.$_POST['nscerttype'].'"';
  fwrite($fp, "### nsCertType\n".$value."\n");

  $value = 'OVPNC_REMOTE="'.tuq($_POST['remote']).'"';
  fwrite($fp, "### Server Network\n".$value."\n");

  $value = 'OVPNC_SERVER="'.tuq($_POST['server']).'"';
  fwrite($fp, "### Server Network\n".$value."\n");

  $value = 'OVPNC_OTHER="';
  fwrite($fp, "### Raw Commands\n".$value."\n");
  $value = stripshellsafe($_POST['other']);
  $value = str_replace(chr(13), '', $value);
  if (($value = trim($value, chr(10))) !== '') {
    fwrite($fp, $value."\n");
  }
  fwrite($fp, '"'."\n");

  if ($openssl !== FALSE) {
    $value = 'OVPNC_CA="'.$openssl['ca_crt'].'"';
    fwrite($fp, "### CA File\n".$value."\n");
    $value = 'OVPNC_CERT="'.$openssl['client_crt'].'"';
    fwrite($fp, "### CERT File\n".$value."\n");
    $value = 'OVPNC_KEY="'.$openssl['client_key'].'"';
    fwrite($fp, "### Key File\n".$value."\n");
    if ($_POST['tls_auth'] === 'yes' && is_file($openssl['tls_auth_key'])) {
      $value = 'OVPNC_TA="'.$openssl['tls_auth_key'].'"';
    } else {
      $value = 'OVPNC_TA=""';
    }
    fwrite($fp, "### TLS-Auth File\n".$value."\n");
  }

  fwrite($fp, "### gui.openvpnclient.conf - end ###\n");
  fclose($fp);

  return($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $result = saveOVPNCsettings($OVPNCONFDIR, $OVPNCONFFILE);
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('openvpnclient', 10, $result, 'init');
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_delete'])) {
    $result = 31;
    if (isset($_POST['confirm_delete'])) {
      opensslDELETEclientkeys($openssl);
    } else {
      $result = 2;
    }
  } elseif (isset($_FILES['creds'])) {
    $tls_auth_key = TRUE;
    $result = 1;
    foreach ($_FILES['creds']['error'] as $key => $error) {
      if ($error == 0) {
        $size = filesize($_FILES['creds']['tmp_name'][$key]);
        if ($size === FALSE || $size > 10000 || $size == 0) {
          $result = 20;
          break;
        }
        $name = basename($_FILES['creds']['name'][$key]);
        if (($len = strlen($name) - 4) < 0) {
          $len = 0;
        }
        if (stripos($name, '.crt', $len) !== FALSE) {
          if ($key !== 'ca_crt' && $key !== 'client_crt') {
            $result = 23;
            break;
          }
        } elseif (stripos($name, '.key', $len) !== FALSE) {
          if ($key !== 'client_key' && $key !== 'tls_auth_key') {
            $result = 23;
            break;
          }
        } else {
          $result = 22;
          break;
        }
      } elseif ($error == 1 || $error == 2) {
        $result = 20;
        break;
      } elseif ($key === 'tls_auth_key') {  // TLS-Auth is optional
        $tls_auth_key = FALSE;
      } else {
        $result = 21;
        break;
      }
    }
    if ($result == 1) {
      $result = 99;
      if ($openssl !== FALSE) {
        $result = 30;
        foreach ($_FILES['creds']['tmp_name'] as $key => $tmp_name) {
          if ($key !== 'tls_auth_key' || $tls_auth_key) {
            if (! move_uploaded_file($tmp_name, $openssl[$key])) {
              $result = 3;
              break;
            }
            if ($key === 'client_key' || $key === 'tls_auth_key') {
              chmod($openssl[$key], 0600);
            }
          }
        }
      }
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  if (is_file($OVPNCONFFILE)) {
    $db = parseRCconf($OVPNCONFFILE);
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
    } elseif ($result == 5) {
      putHtml('<p style="color: red;">File Not Found.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">OpenVPN Client'.statusPROCESS('openvpnclient').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart Client" to apply any changed settings.</p>');
    } elseif ($result == 20) {
      putHtml('<p style="color: red;">File size is not reasonable for a cert or key.</p>');
    } elseif ($result == 21) {
      putHtml('<p style="color: red;">The three files, CA, Cert and Key must be defined. The TLS-Auth Key is optional.</p>');
    } elseif ($result == 22) {
      putHtml('<p style="color: red;">Invalid suffix, only files ending with .crt and .key are allowed.</p>');
    } elseif ($result == 23) {
      putHtml('<p style="color: red;">Incorrect file suffix for file definition.</p>');
    } elseif ($result == 30) {
      putHtml('<p style="color: green;">Client Credentials successfully saved, restart to apply.</p>');
    } elseif ($result == 31) {
      putHtml('<p style="color: green;">Client Credentials successfully deleted.</p>');
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
  <script language="JavaScript" type="text/javascript">
  //<![CDATA[
  function auth_method_change() {
    var form = document.getElementById("iform");
    var user_pass = document.getElementById("user_pass");
    switch (form.auth_method.selectedIndex) {
      case 0: // Certificate
        user_pass.style.visibility = "hidden";
        break;
      case 1: // Cert. + User/Pass
        user_pass.style.visibility = "visible";
        break;
    }
  }
  //]]>
  </script>
  <center>
  <table class="layout"><tr><td><center>
  <form id="iform" method="post" action="<?php echo $myself;?>">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;" colspan="2">
  <h2>OpenVPN Client Configuration:</h2>
  </td></tr><tr><td width="260" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart Client" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="80">&nbsp;</td><td width="100">&nbsp;</td><td width="100">&nbsp;</td><td>&nbsp;</td><td width="100">&nbsp;</td><td width="80">&nbsp;</td></tr>
<?php
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Tunnel Options:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Protocol:');
  putHtml('</td><td style="text-align: left;" colspan="1">');
  $protocol = getVARdef($db, 'OVPNC_PROTOCOL');
  putHtml('<select name="protocol">');
  foreach ($protocol_menu as $key => $value) {
    $sel = ($protocol === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td><td style="text-align: right;" colspan="1">');
  putHtml('Port:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  if (($value = getVARdef($db, 'OVPNC_PORT')) === '') {
    $value = '1194';
  }
  putHtml('<input type="text" size="8" maxlength="10" value="'.$value.'" name="port" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Log Verbosity:');
  putHtml('</td><td style="text-align: left;" colspan="1">');
  $verbosity = getVARdef($db, 'OVPNC_VERBOSITY');
  putHtml('<select name="verbosity">');
  foreach ($verbosity_menu as $key => $value) {
    $sel = ($verbosity === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td><td style="text-align: right;" colspan="1">');
  putHtml('Compression:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  putHtml('<select name="compression">');
  $sel = (getVARdef($db, 'OVPNC_LZO') === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>Yes</option>');
  $sel = (getVARdef($db, 'OVPNC_LZO') === 'no') ? ' selected="selected"' : '';
  putHtml('<option value="no"'.$sel.'>No</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('QoS Passthrough:');
  putHtml('</td><td style="text-align: left;" colspan="1">');
  putHtml('<select name="qos_passthrough">');
  $sel = (getVARdef($db, 'OVPNC_QOS') === 'no') ? ' selected="selected"' : '';
  putHtml('<option value="no"'.$sel.'>No</option>');
  $sel = (getVARdef($db, 'OVPNC_QOS') === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>Yes</option>');
  putHtml('</select>');
  putHtml('</td><td style="text-align: right;" colspan="1">');
  putHtml('Legacy Cipher:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  $cipher = getVARdef($db, 'OVPNC_CIPHER');
  putHtml('<select name="cipher_menu">');
  foreach ($cipher_menu as $key => $value) {
    $sel = ($cipher === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Device:');
  putHtml('</td><td style="text-align: left;" colspan="1">');
  putHtml('<select name="device">');
  $sel = (getVARdef($db, 'OVPNC_DEV') === 'tun2') ? ' selected="selected"' : '';
  putHtml('<option value="tun2"'.$sel.'>tun2</option>');
  $sel = (getVARdef($db, 'OVPNC_DEV') === 'tun3') ? ' selected="selected"' : '';
  putHtml('<option value="tun3"'.$sel.'>tun3</option>');
  putHtml('</select>');
  putHtml('</td><td style="text-align: right;" colspan="1">');
  putHtml('Auth HMAC:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  $auth_hmac = getVARdef($db, 'OVPNC_AUTH');
  putHtml('<select name="auth_hmac">');
  foreach ($auth_hmac_menu as $key => $value) {
    $sel = ($auth_hmac === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Raw Commands:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  echo '<textarea name="other" rows="4" cols="40" wrap="off" class="edititemText">';
  if (($value = getVARdef($db, 'OVPNC_OTHER')) !== '') {
    $value = str_replace(chr(10), chr(13), $value);
    if (($value = trim($value, chr(13))) !== '') {
      echo htmlspecialchars($value), chr(13);
    }
  }
  putHtml('</textarea>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Authentication:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Auth Method:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($auth_method = getVARdef($db, 'OVPNC_USER_PASS')) !== '') {
    $auth_method = 'yes';
  }
  putHtml('<select name="auth_method" onchange="auth_method_change()">');
  foreach ($auth_method_menu as $key => $value) {
    $sel = ($auth_method === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('&nbsp;');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<div id="user_pass" style="visibility: hidden;">');
  $user_pass = getVARdef($db, 'OVPNC_USER_PASS');
  $value = parseUserPass($user_pass, 'user');
  putHtml('User:&nbsp;<input type="text" size="16" maxlength="128" value="'.$value.'" name="auth_user" />');
  $value = parseUserPass($user_pass, 'pass');
  $value = htmlspecialchars(RCconfig2string($value));
  putHtml('Pass:&nbsp;<input type="password" size="16" maxlength="128" value="'.$value.'" name="auth_pass" />');
  putHtml('</div>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Require TLS Cert:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($nscerttype = getVARdef($db, 'OVPNC_REMOTE_CERT_TLS')) === '') {
    $nscerttype = getVARdef($db, 'OVPNC_NSCERTTYPE');
  }
  putHtml('<select name="nscerttype">');
  foreach ($nscerttype_menu as $key => $value) {
    $sel = ($nscerttype === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('<i>(nsCertType)</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Extra TLS-Auth:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $tls_auth = getVARdef($db, 'OVPNC_TA');
  putHtml('<select name="tls_auth">');
  $sel = ($tls_auth === '') ? ' selected="selected"' : '';
  putHtml('<option value=""'.$sel.'>No</option>');
  $sel = ($tls_auth !== '') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>Yes</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Client Mode:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Remote Server Hostname:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'OVPNC_REMOTE');
  putHtml('<input type="text" size="32" maxlength="128" value="'.$value.'" name="remote" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Remote Network IPv4&nbsp;NM:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'OVPNC_SERVER')) === '') {
    $value = '10.8.0.0 255.255.255.0';
  }
  putHtml('<input type="text" size="32" maxlength="128" value="'.$value.'" name="server" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Client Certificate and Key:</strong>');
  putHtml('</td></tr>');
  putHtml('</table>');
  putHtml('</form>');

  if (opensslOPENVPNCis_valid($openssl)) {
    putHtml('<p style="color: green;">Required Client Credentials are defined.</p>');
  } else {
    putHtml('<p style="color: red;">Not all required Client Credential files are defined.</p>');
  }

  putHtml('<form method="post" action="'.$myself.'" enctype="multipart/form-data">');
  putHtml('<table width="90%" class="datatable">');
  putHtml('<tr class="dtrow1"><td width="70" style="text-align: right;">');
  putHtml('<input type="hidden" name="MAX_FILE_SIZE" value="10000" />');
  putHtml('CA:');
  putHtml('</td><td style="text-align: left;">');
  putHtml(getCREDinfo($openssl, 'ca_crt', $str).'<input type="file" name="creds[ca_crt]" />');
  putHtml('</td></tr><tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Cert:');
  putHtml('</td><td style="text-align: left;">');
  putHtml(getCREDinfo($openssl, 'client_crt', $CName).'<input type="file" name="creds[client_crt]" />');
  putHtml('</td></tr><tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Key:');
  putHtml('</td><td style="text-align: left;">');
  putHtml(getCREDinfo($openssl, 'client_key', $str).'<input type="file" name="creds[client_key]" />');
  putHtml('</td></tr><tr class="dtrow1"><td style="text-align: right;">');
  putHtml('TLS-Auth Key:');
  putHtml('</td><td style="text-align: left;">');
  putHtml(getCREDinfo($openssl, 'tls_auth_key', $str).'<input type="file" name="creds[tls_auth_key]" />');
  putHtml('</td></tr><tr class="dtrow1"><td style="text-align: right;">');
  if ($CName !== '') {
    putHtml('CN:');
    putHtml('</td><td style="text-align: left;">');
    putHtml($CName);
  } else {
    putHtml('&nbsp;');
    putHtml('</td><td style="text-align: left;">');
    putHtml('&nbsp;');
  }
  putHtml('</td></tr><tr class="dtrow1"><td style="text-align: center;" colspan="2">');
  putHtml('<input type="submit" name="submit" value="Save New Credentials" />');
  putHtml('</td></tr>');
  putHtml('</table>');
  putHtml('</form>');

  putHtml('<form method="post" action="'.$myself.'">');
  putHtml('<table class="stdtable">');
  putHtml('<tr><td class="dialogText">');
  putHtml('<input type="submit" name="submit_delete" value="Delete Credentials" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="delete" name="confirm_delete" />&nbsp;Confirm');
  putHtml('</td></tr>');
  putHtml('</table>');
  putHtml('</form>');

  putHtml('</center></td></tr></table>');
  putHtml('</center>');
  putHtml('<script language="JavaScript" type="text/javascript">');
  putHtml('//<![CDATA[');
  putHtml('auth_method_change();');
  putHtml('//]]>');
  putHtml('</script>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
