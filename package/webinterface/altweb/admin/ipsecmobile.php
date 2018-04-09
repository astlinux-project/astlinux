<?php

// Copyright (C) 2008-2010 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// ipsecmobile.php for AstLinux
// 11-23-2010
// 12-14-2015, Added Signature Algorithm support
//
// System location of /mnt/kd/rc.conf.d directory
$IPSECMCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.ipsecmobile.conf file
$IPSECMCONFFILE = '/mnt/kd/rc.conf.d/gui.ipsecmobile.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

require_once '../common/openssl-ipsecmobile.php';

require_once '../common/openssl.php';

if (is_file($IPSECMCONFFILE)) {
  $db = parseRCconf($IPSECMCONFFILE);
} else {
  $db = NULL;
}

// Function: ipsecmobile_openssl()
//
function ipsecmobile_openssl($keysize, $algorithm, $dnsname) {
  global $global_prefs;
  // System location of gui.network.conf file
  $NETCONFFILE = '/mnt/kd/rc.conf.d/gui.network.conf';

  if ($keysize === '') {
    $keysize = '2048';
  }
  $opts['keysize'] = (int)$keysize;

  if ($algorithm === '') {
    $algorithm = 'sha256';
  }
  $opts['algorithm'] = $algorithm;
  $opts['dnsname'] = $dnsname;

  if (($countryName = getPREFdef($global_prefs, 'dn_country_name_cmdstr')) === '') {
    $countryName = 'US';
  }
  if (($stateName = getPREFdef($global_prefs, 'dn_state_name_cmdstr')) === '') {
    $stateName = 'Nebraska';
  }
  if (($localityName = getPREFdef($global_prefs, 'dn_locality_name_cmdstr')) === '') {
    $localityName = 'Omaha';
  }
  if (($orgName = getPREFdef($global_prefs, 'dn_org_name_cmdstr')) === '') {
    if (($orgName = getPREFdef($global_prefs, 'title_name_cmdstr')) === '') {
      $orgName = 'AstLinux Management';
    }
  }
  if (($orgUnit = getPREFdef($global_prefs, 'dn_org_unit_cmdstr')) === '') {
    $orgUnit = 'IPsec Mobile Server';
  }
  if (($commonName = getPREFdef($global_prefs, 'dn_common_name_cmdstr')) === '') {
    if (is_file($NETCONFFILE)) {
      $vars = parseRCconf($NETCONFFILE);
      if (($commonName = getVARdef($vars, 'HOSTNAME').'.'.getVARdef($vars, 'DOMAIN')) === '') {
        $commonName = 'pbx.astlinux';
      }
    } else {
      $commonName = 'pbx.astlinux';
    }
  }
  if (($email = getPREFdef($global_prefs, 'dn_email_address_cmdstr')) === '') {
    $email = 'info@astlinux-project.org';
  }
  $ssl = ipsecmobileSETUP($opts, $countryName, $stateName, $localityName, $orgName, $orgUnit, $commonName, $email);
  return($ssl);
}
$key_size = getVARdef($db, 'IPSECM_CERT_KEYSIZE');
$signature_algorithm = getVARdef($db, 'IPSECM_CERT_ALGORITHM');
$dns_name = getVARdef($db, 'IPSECM_CERT_DNSNAME');
$openssl = ipsecmobile_openssl($key_size, $signature_algorithm, $dns_name);

$nat_t_menu = array (
  'off' => 'Disable',
  'on' => 'Enable',
  'force' => 'Force'
);

$log_level_menu = array (
  'error' => 'Error',
  'warning' => 'Warning',
  'notify' => 'Notify',
  'info' => 'Info',
  'debug' => 'Debug'
);

$auth_method_menu = array (
  'rsasig' => 'Certificate',
  'xauth_rsa_server' => 'XAuth RSA'
);

$p1_cypher_menu = array (
  'aes 128' => 'AES 128',
  'aes 192' => 'AES 192',
  'aes 256' => 'AES 256',
  'des' => 'DES',
  '3des' => '3DES',
  'blowfish' => 'Blowfish'
);

$p1_hash_menu = array (
  'md5' => 'MD5',
  'sha1' => 'SHA-1',
  'sha256' => 'SHA-256'
);

$p1_dhgroup_menu = array (
  'modp768' => '768 (1)',
  'modp1024' => '1024 (2)',
  'modp1536' => '1536 (5)',
  'modp2048' => '2048 (14)',
  'modp3072' => '3072 (15)',
  'modp4096' => '4096 (16)',
  'modp6144' => '6144 (17)',
  'modp8192' => '8192 (18)'
);

$p2_hashes_menu = array (
  'hmac_md5' => 'HMAC-MD5',
  'hmac_sha1' => 'HMAC-SHA-1',
  'hmac_sha256' => 'HMAC-SHA-256'
);

$p2_pfsgroup_menu = array (
  'none' => 'None',
  'modp768' => '768 (1)',
  'modp1024' => '1024 (2)',
  'modp1536' => '1536 (5)',
  'modp2048' => '2048 (14)',
  'modp3072' => '3072 (15)',
  'modp4096' => '4096 (16)',
  'modp6144' => '6144 (17)',
  'modp8192' => '8192 (18)'
);

$key_size_menu = array (
  '1024' => '1024 Bits',
  '2048' => '2048 Bits'
);

$signature_algorithm_menu = array (
  'sha1' => 'SHA-1',
  'sha256' => 'SHA-256'
);

// Function: saveIPSECMsettings
//
function saveIPSECMsettings($conf_dir, $conf_file) {
  global $openssl;

  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.ipsecmobile.conf - start ###\n###\n");

  $value = 'IPSEC_LOGLEVEL="'.$_POST['log_level'].'"';
  fwrite($fp, "### Log Level\n".$value."\n");

  $value = 'IPSECM_NAT_TRAVERSAL="'.$_POST['nat_t'].'"';
  fwrite($fp, "### NAT Traversal\n".$value."\n");

  $value = 'IPSECM_STATIC_ROUTES="';
  fwrite($fp, "### Static Routes\n".$value."\n");
  $value = stripshellsafe($_POST['static_routes']);
  $value = str_replace(chr(13), '', $value);
  if (($value = trim($value, chr(10))) !== '') {
    fwrite($fp, $value."\n");
  }
  fwrite($fp, '"'."\n");

  $value = 'IPSECM_AUTH_METHOD="'.$_POST['auth_method'].'"';
  fwrite($fp, "### Auth Method\n".$value."\n");

  $value = 'IPSECM_P1_CYPHER="'.$_POST['p1_cypher'].'"';
  fwrite($fp, "### Phase 1 Encryption\n".$value."\n");

  $value = 'IPSECM_P1_HASH="'.$_POST['p1_hash'].'"';
  fwrite($fp, "### Phase 1 Authentication\n".$value."\n");

  $value = 'IPSECM_P1_DHGROUP="'.$_POST['p1_dhgroup'].'"';
  fwrite($fp, "### Phase 1 DH Group\n".$value."\n");

  $value = 'IPSECM_P1_LIFETIME="'.tuq($_POST['p1_lifetime']).'"';
  fwrite($fp, "### Phase 1 Lifetime\n".$value."\n");

  $value = '';
  if (isset($_POST['p2_cyphers'])) {
    $p2_cyphers = $_POST['p2_cyphers'];
    foreach ($p2_cyphers as $var) {
      $value .= ','.$var;
    }
  }
  $value = 'IPSECM_P2_CYPHERS="'.trim($value, ',').'"';
  fwrite($fp, "### Phase 2 Encryption\n".$value."\n");

  $value = '';
  if (isset($_POST['p2_hashes'])) {
    $p2_hashes = $_POST['p2_hashes'];
    foreach ($p2_hashes as $var) {
      $value .= ','.$var;
    }
  }
  $value = 'IPSECM_P2_HASHES="'.trim($value, ',').'"';
  fwrite($fp, "### Phase 2 Authentication\n".$value."\n");

  $value = 'IPSECM_P2_PFSGROUP="'.$_POST['p2_pfsgroup'].'"';
  fwrite($fp, "### Phase 2 PFS Group\n".$value."\n");

  $value = 'IPSECM_P2_LIFETIME="'.tuq($_POST['p2_lifetime']).'"';
  fwrite($fp, "### Phase 2 Lifetime\n".$value."\n");

  $value = 'IPSECM_CERT_KEYSIZE="'.$_POST['key_size'].'"';
  fwrite($fp, "### Private Key Size\n".$value."\n");

  $value = 'IPSECM_CERT_ALGORITHM="'.$_POST['signature_algorithm'].'"';
  fwrite($fp, "### Signature Algorithm\n".$value."\n");

  $value = 'IPSECM_CERT_DNSNAME="'.str_replace(' ', '', tuq($_POST['dns_name'])).'"';
  fwrite($fp, "### Server Cert DNS Name\n".$value."\n");

if (opensslIPSECMOBILEis_valid($openssl)) {
  $value = 'IPSECM_RSA_PATH="'.$openssl['key_dir'].'"';
  fwrite($fp, "### Certificate Directory\n".$value."\n");
  $value = 'IPSECM_RSA_CA="ca.crt"';
  fwrite($fp, "### CA File\n".$value."\n");
  $value = 'IPSECM_RSA_CERT="server.crt"';
  fwrite($fp, "### CERT File\n".$value."\n");
  $value = 'IPSECM_RSA_KEY="server.key"';
  fwrite($fp, "### Key File\n".$value."\n");
} else {
  $value = isset($_POST['path']) ? tuq($_POST['path']) : '/mnt/kd/ipsec';
  $value = 'IPSECM_RSA_PATH="'.$value.'"';
  fwrite($fp, "### Certificate Directory\n".$value."\n");
  $value = isset($_POST['ca']) ? tuq($_POST['ca']) : 'ca.crt';
  $value = 'IPSECM_RSA_CA="'.$value.'"';
  fwrite($fp, "### CA File\n".$value."\n");
  $value = isset($_POST['cert']) ? tuq($_POST['cert']) : 'server.crt';
  $value = 'IPSECM_RSA_CERT="'.$value.'"';
  fwrite($fp, "### CERT File\n".$value."\n");
  $value = isset($_POST['key']) ? tuq($_POST['key']) : 'server.key';
  $value = 'IPSECM_RSA_KEY="'.$value.'"';
  fwrite($fp, "### Key File\n".$value."\n");
}

  fwrite($fp, "### gui.ipsecmobile.conf - end ###\n");
  fclose($fp);

  return($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $result = saveIPSECMsettings($IPSECMCONFDIR, $IPSECMCONFFILE);
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('racoon', 10, $result, 'init');
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_xauth'])) {
    $result = saveIPSECMsettings($IPSECMCONFDIR, $IPSECMCONFFILE);
    header('Location: /admin/ipsecxauth.php');
    exit;
  } elseif (isset($_POST['submit_new_server'])) {
    $result = 99;
    if (isset($_POST['confirm_new_server'])) {
      opensslDELETEkeys($openssl);
      if (is_file($openssl['config'])) {
        @unlink($openssl['config']);
      }
      // Rebuild openssl.cnf template for new CA
      $key_size = $_POST['key_size'];
      $signature_algorithm = $_POST['signature_algorithm'];
      $dns_name = str_replace(' ', '', tuq($_POST['dns_name']));
      if (($openssl = ipsecmobile_openssl($key_size, $signature_algorithm, $dns_name)) !== FALSE) {
        if (opensslCREATEselfCert($openssl)) {
          if (opensslCREATEserverCert($openssl)) {
            $result = 30;
          }
        }
      }
      saveIPSECMsettings($IPSECMCONFDIR, $IPSECMCONFFILE);
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_delete_all'])) {
    if (isset($_POST['confirm_delete_all'])) {
      opensslDELETEkeys($openssl);
      saveIPSECMsettings($IPSECMCONFDIR, $IPSECMCONFFILE);
      $result = 32;
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_new_client'])) {
    if (($value = tuq($_POST['new_client'])) !== '') {
      if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]*$/', $value)) {
        if (! is_file($openssl['key_dir'].'/'.$value.'.crt') &&
            ! is_file($openssl['key_dir'].'/'.$value.'.key')) {
          if (opensslCREATEclientCert($value, $openssl)) {
            saveIPSECMsettings($IPSECMCONFDIR, $IPSECMCONFFILE);
            $result = 31;
          } else {
            $result = 99;
          }
        } else {
          $result = 38;
        }
      } else {
        $result = 39;
      }
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} elseif (isset($_GET['peer']) && $openssl !== FALSE) {
  $result = 5;
  $client_list = opensslGETclients($openssl);
  foreach ($client_list as $value) {
    if ($value === (string)$_GET['peer']) {
      $result = 1;
      break;
    }
  }
  if (! class_exists('ZipArchive')) {
    $result = 99;
  } elseif ($result == 1) {
    $tmpfile = tempnam("/tmp", "ZIP_");
    $zip = new ZipArchive();
    if ($zip->open($tmpfile, ZIPARCHIVE::OVERWRITE) !== TRUE) {
      @unlink($tmpfile);
      $result = 99;
    } else {
      $zip->addFile($openssl['key_dir'].'/ca.crt', $value.'/ca.crt');
      $zip->addFile($openssl['key_dir'].'/'.$value.'.crt', $value.'/'.$value.'.crt');
      $zip->addFile($openssl['key_dir'].'/'.$value.'.key', $value.'/'.$value.'.key');
      $p12pass = opensslRANDOMpass(12);
      if (($p12 = opensslPKCS12str($openssl, $value, $p12pass)) !== '') {
        $zip->addFromString($value.'/'.$value.'.p12', $p12);
        $zip->addFromString($value.'/README.txt', opensslREADMEstr('p12', $value, $p12pass));
      } else {
        $zip->addFromString($value.'/README.txt', opensslREADMEstr('', $value, $p12pass));
      }
      $zip->close();

      header('Content-Type: application/zip');
      header('Content-Disposition: attachment; filename="credentials-'.$value.'.zip"');
      header('Content-Transfer-Encoding: binary');
      header('Content-Length: '.filesize($tmpfile));
      ob_clean();
      flush();
      @readfile($tmpfile);
      @unlink($tmpfile);
      exit;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

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
      putHtml('<p style="color: green;">IPsec VPN'.statusPROCESS('racoon').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart IPsec" to apply any changed settings.</p>');
    } elseif ($result == 30) {
      putHtml('<p style="color: green;">Settings saved, server credentials automatically generated.</p>');
    } elseif ($result == 31) {
      putHtml('<p style="color: green;">Peer credentials automatically generated.</p>');
    } elseif ($result == 32) {
      putHtml('<p style="color: green;">Settings saved, automatically generated credentials deleted.</p>');
    } elseif ($result == 38) {
      putHtml('<p style="color: red;">Peer name currently exists, specify a unique peer name.</p>');
    } elseif ($result == 39) {
      putHtml('<p style="color: red;">Peer names must be alphanumeric, underbar (_), dash (-), dot (.)</p>');
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
    switch (form.auth_method.selectedIndex) {
      case 0: // Certificate
        form.submit_xauth.style.visibility = "hidden";
        break;
      case 1: // XAuth RSA
        form.submit_xauth.style.visibility = "visible";
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
  <h2>IPsec Mobile Server Configuration:</h2>
  </td></tr><tr><td width="260" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart IPsec" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="140">&nbsp;</td><td width="50">&nbsp;</td><td width="100">&nbsp;</td><td>&nbsp;</td><td width="120">&nbsp;</td><td width="100">&nbsp;</td></tr>
<?php
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Tunnel Options:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('NAT Traversal:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  if (($nat_t = getVARdef($db, 'IPSECM_NAT_TRAVERSAL')) === '') {
    $nat_t = 'off';
  }
  putHtml('<select name="nat_t">');
  foreach ($nat_t_menu as $key => $value) {
    $sel = ($nat_t === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');

  putHtml('</td><td style="text-align: left;" colspan="3">');
  putHtml('Log Level:');
  if (($log_level = getVARdef($db, 'IPSEC_LOGLEVEL')) === '') {
    $log_level = 'info';
  }
  putHtml('<select name="log_level">');
  foreach ($log_level_menu as $key => $value) {
    $sel = ($log_level === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Optional&nbsp;<br />Static Routes:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  putHtml('&nbsp;<i>Local-Net/NN&nbsp;&nbsp;Remote-Net/NN</i><br />');
  echo '<textarea name="static_routes" rows="3" cols="38" wrap="off" class="edititemText">';
  if (($value = getVARdef($db, 'IPSECM_STATIC_ROUTES')) !== '') {
    $value = str_replace(chr(10), chr(13), $value);
    if (($value = trim($value, chr(13))) !== '') {
      echo htmlspecialchars($value), chr(13);
    }
  }
  putHtml('</textarea>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Phase 1 - Authentication:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Auth Method:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  if (($auth_method = getVARdef($db, 'IPSECM_AUTH_METHOD')) === '') {
    $auth_method = 'rsasig';
  }
  putHtml('<select name="auth_method" onchange="auth_method_change()">');
  foreach ($auth_method_menu as $key => $value) {
    $sel = ($auth_method === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td><td style="text-align: left;" colspan="3">');
  putHtml('<input type="submit" value="XAuth Configuration" name="submit_xauth" class="button" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Encryption:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  if (($p1_cypher = getVARdef($db, 'IPSECM_P1_CYPHER')) === '') {
    $p1_cypher = 'aes 128';
  }
  putHtml('<select name="p1_cypher">');
  foreach ($p1_cypher_menu as $key => $value) {
    $sel = ($p1_cypher === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Authentication:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  if (($p1_hash = getVARdef($db, 'IPSECM_P1_HASH')) === '') {
    $p1_hash = 'sha1';
  }
  putHtml('<select name="p1_hash">');
  foreach ($p1_hash_menu as $key => $value) {
    $sel = ($p1_hash === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('DH Group:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  if (($p1_dhgroup = getVARdef($db, 'IPSECM_P1_DHGROUP')) === '') {
    $p1_dhgroup = 'modp1024';
  }
  putHtml('<select name="p1_dhgroup">');
  foreach ($p1_dhgroup_menu as $key => $value) {
    $sel = ($p1_dhgroup === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td><td style="text-align: left;" colspan="3">');
  putHtml('Lifetime:');
  if (($value = getVARdef($db, 'IPSECM_P1_LIFETIME')) === '') {
    $value = '86400';
  }
  putHtml('<input type="text" size="8" maxlength="8" value="'.$value.'" name="p1_lifetime" />');
  putHtml('secs');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Phase 2 - SA/Key Exchange:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Encryption:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  if (($p2_cyphers = getVARdef($db, 'IPSECM_P2_CYPHERS')) === '') {
    $p2_cyphers = 'aes 128';
  }
  foreach ($p1_cypher_menu as $key => $value) {
    $sel = inStringList($key, $p2_cyphers, ',') ? ' checked="checked"' : '';
    putHtml('<input type="checkbox" name="p2_cyphers[]" value="'.$key.'"'.$sel.' />'.$value);
  }
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Authentication:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  if (($p2_hashes = getVARdef($db, 'IPSECM_P2_HASHES')) === '') {
    $p2_hashes = 'hmac_sha1';
  }
  foreach ($p2_hashes_menu as $key => $value) {
    $sel = inStringList($key, $p2_hashes, ',') ? ' checked="checked"' : '';
    putHtml('<input type="checkbox" name="p2_hashes[]" value="'.$key.'"'.$sel.' />'.$value);
  }
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('PFS Group:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  if (($p2_pfsgroup = getVARdef($db, 'IPSECM_P2_PFSGROUP')) === '') {
    $p2_pfsgroup = 'modp1024';
  }
  putHtml('<select name="p2_pfsgroup">');
  foreach ($p2_pfsgroup_menu as $key => $value) {
    $sel = ($p2_pfsgroup === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td><td style="text-align: left;" colspan="3">');
  putHtml('Lifetime:');
  if (($value = getVARdef($db, 'IPSECM_P2_LIFETIME')) === '') {
    $value = '3600';
  }
  putHtml('<input type="text" size="8" maxlength="8" value="'.$value.'" name="p2_lifetime" />');
  putHtml('secs');
  putHtml('</td></tr>');

if ($openssl !== FALSE) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Server Certificate and Key:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Private Key Size:</td><td style="text-align: left;" colspan="4">');
  if (($key_size = getVARdef($db, 'IPSECM_CERT_KEYSIZE')) === '') {
    $key_size = '2048';
  }
  putHtml('<select name="key_size">');
  foreach ($key_size_menu as $key => $value) {
    $sel = ($key_size === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Signature Algorithm:</td><td style="text-align: left;" colspan="4">');
  if (($signature_algorithm = getVARdef($db, 'IPSECM_CERT_ALGORITHM')) === '') {
    $signature_algorithm = 'sha256';
  }
  putHtml('<select name="signature_algorithm">');
  foreach ($signature_algorithm_menu as $key => $value) {
    $sel = ($signature_algorithm === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Server Cert DNS Name:</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'IPSECM_CERT_DNSNAME');
  putHtml('<input type="text" size="24" maxlength="128" value="'.$value.'" name="dns_name" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">');
  putHtml('Create New Certificate and Key:</td><td class="dialogText" style="text-align: left;" colspan="3">');
  putHtml('<input type="submit" value="Create New" name="submit_new_server" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="new_server" name="confirm_new_server" />&nbsp;Confirm</td></tr>');
  if (opensslIPSECMOBILEis_valid($openssl)) {
    putHtml('<tr class="dtrow1"><td style="color: orange; text-align: center;" colspan="6">');
    putHtml('Note: "Create New" revokes all previous peers.</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">');
    putHtml('Manually Specify Certificates and Keys:</td><td class="dialogText" style="text-align: left;" colspan="3">');
    putHtml('<input type="submit" value="Manual" name="submit_delete_all" />');
    putHtml('&ndash;');
    putHtml('<input type="checkbox" value="delete_all" name="confirm_delete_all" />&nbsp;Confirm</td></tr>');

    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
    putHtml('<strong>Peer Certificates and Keys:</strong>');
    putHtml('</td></tr>');
    putHtml('<tr><td style="text-align: right;" colspan="2">');
    putHtml('Create New Peer:</td><td style="text-align: left;" colspan="4">');
    putHtml('<input type="text" size="24" maxlength="32" value="" name="new_client" />');
    putHtml('<input type="submit" value="New Peer" name="submit_new_client" />');
    putHtml('</td></tr>');

    putHtml('<tr><td colspan="6"><center>');
    $data = opensslGETclients($openssl);
    putHtml('<table width="85%" class="datatable">');
    putHtml("<tr>");

    if (($n = count($data)) > 0) {
      echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Peer Name", "</td>";
      echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Credentials", "</td>";
      for ($i = 0; $i < $n; $i++) {
        putHtml("</tr>");
        echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
        echo '<td style="text-align: left;">', $data[$i], '</td>';
        echo '<td style="text-align: center; padding-top: 6px; padding-bottom: 7px;">',
             '<a href="'.$myself.'?peer='.$data[$i].'" class="actionText">Download</a></td>';
      }
    } else {
      echo '<td style="color: orange; text-align: center;">No IPsec Peer Credentials.', '</td>';
    }

    putHtml("</tr>");
    putHtml("</table>");
    putHtml('</center></td></tr>');
  } else {
    putHtml('<tr class="dtrow1"><td style="color: green; text-align: center;" colspan="6">');
    putHtml('Click "Create New" to automatically generate credentials.</td></tr>');
  }
}

if (! opensslIPSECMOBILEis_valid($openssl)) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Certificate and Key Locations:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Directory:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  if (($value = getVARdef($db, 'IPSECM_RSA_PATH')) === '') {
    $value = '/mnt/kd/ipsec';
  }
  putHtml('<input type="text" size="32" maxlength="128" value="'.$value.'" name="path" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('CA File:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  if (($value = getVARdef($db, 'IPSECM_RSA_CA')) === '') {
    $value = 'ca.crt';
  }
  putHtml('<input type="text" size="32" maxlength="128" value="'.$value.'" name="ca" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('CERT File:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  if (($value = getVARdef($db, 'IPSECM_RSA_CERT')) === '') {
    $value = 'server.crt';
  }
  putHtml('<input type="text" size="32" maxlength="128" value="'.$value.'" name="cert" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Key File:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  if (($value = getVARdef($db, 'IPSECM_RSA_KEY')) === '') {
    $value = 'server.key';
  }
  putHtml('<input type="text" size="32" maxlength="128" value="'.$value.'" name="key" />');
  putHtml('</td></tr>');
}

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
