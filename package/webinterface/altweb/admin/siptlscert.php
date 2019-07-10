<?php

// Copyright (C) 2008-2017 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// siptlscert.php for AstLinux
// 11-12-2012
// 12-14-2015, Added Signature Algorithm support
// 07-12-2017, Added ACME warning
//
// System location of /mnt/kd/rc.conf.d directory
$SIPTLSCERTCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.siptlscert.conf file
$SIPTLSCERTCONFFILE = '/mnt/kd/rc.conf.d/gui.siptlscert.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

require_once '../common/openssl-sip-tls.php';

require_once '../common/openssl.php';

if (is_file($SIPTLSCERTCONFFILE)) {
  $db = parseRCconf($SIPTLSCERTCONFFILE);
} else {
  $db = NULL;
}

// Function: siptlscert_openssl()
//
function siptlscert_openssl($keysize, $algorithm, $dnsname) {
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
    $orgUnit = 'Asterisk SIP-TLS Server';
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
  $ssl = sip_tls_SETUP($opts, $countryName, $stateName, $localityName, $orgName, $orgUnit, $commonName, $email);
  return($ssl);
}
$key_size = getVARdef($db, 'SIPTLSCERT_CERT_KEYSIZE');
$signature_algorithm = getVARdef($db, 'SIPTLSCERT_CERT_ALGORITHM');
$dns_name = getVARdef($db, 'SIPTLSCERT_CERT_DNSNAME');
$openssl = siptlscert_openssl($key_size, $signature_algorithm, $dns_name);

$key_size_menu = array (
  '1024' => '1024 Bits',
  '2048' => '2048 Bits'
);

$signature_algorithm_menu = array (
  'sha1' => 'SHA-1',
  'sha256' => 'SHA-256'
);

// Function: saveSIPTLSCERTsettings
//
function saveSIPTLSCERTsettings($conf_dir, $conf_file) {
  global $openssl;

  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.siptlscert.conf - start ###\n###\n");

  $value = 'SIPTLSCERT_CERT_KEYSIZE="'.$_POST['key_size'].'"';
  fwrite($fp, "### Private Key Size\n".$value."\n");

  $value = 'SIPTLSCERT_CERT_ALGORITHM="'.$_POST['signature_algorithm'].'"';
  fwrite($fp, "### Signature Algorithm\n".$value."\n");

  $value = 'SIPTLSCERT_CERT_DNSNAME="'.str_replace(' ', '', tuq($_POST['dns_name'])).'"';
  fwrite($fp, "### Server Cert DNS Name\n".$value."\n");

  fwrite($fp, "### gui.siptlscert.conf - end ###\n");
  fclose($fp);

  return($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $result = saveSIPTLSCERTsettings($SIPTLSCERTCONFDIR, $SIPTLSCERTCONFFILE);
  } elseif (isset($_POST['submit_edit_sip'])) {
    $result = saveSIPTLSCERTsettings($SIPTLSCERTCONFDIR, $SIPTLSCERTCONFFILE);
    if (is_writable($file = '/etc/asterisk/sip.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    }
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
      if (($openssl = siptlscert_openssl($key_size, $signature_algorithm, $dns_name)) !== FALSE) {
        if (opensslCREATEselfCert($openssl)) {
          if (opensslCREATEserverCert($openssl)) {
            $result = 30;
          }
        }
      }
      saveSIPTLSCERTsettings($SIPTLSCERTCONFDIR, $SIPTLSCERTCONFFILE);
    } else {
      $result = 2;
    }
//  } elseif (isset($_POST['submit_new_client'])) {
//    if (($value = tuq($_POST['new_client'])) !== '') {
//      if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]*$/', $value)) {
//        if (! is_file($openssl['key_dir'].'/'.$value.'.crt') &&
//            ! is_file($openssl['key_dir'].'/'.$value.'.key')) {
//          if (opensslCREATEclientCert($value, $openssl)) {
//            saveSIPTLSCERTsettings($SIPTLSCERTCONFDIR, $SIPTLSCERTCONFFILE);
//            $result = 31;
//          } else {
//            $result = 99;
//          }
//        } else {
//          $result = 38;
//        }
//      } else {
//        $result = 39;
//      }
//    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} elseif (isset($_GET['client']) && $openssl !== FALSE) {
  $result = 5;
//  $client_list = opensslGETclients($openssl);
//  foreach ($client_list as $value) {
//    if ($value === (string)$_GET['client']) {
//      $result = 1;
//      break;
//    }
//  }
// No clients, only server credentials
  if ('server' === (string)$_GET['client']) {
    $value = 'asterisk-sip-tls';
    $result = 1;
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
//      $zip->addFile($openssl['key_dir'].'/'.$value.'.crt', $value.'/'.$value.'.crt');
//      $zip->addFile($openssl['key_dir'].'/'.$value.'.key', $value.'/'.$value.'.key');
//      $p12pass = opensslRANDOMpass(12);
//      if (($p12 = opensslPKCS12str($openssl, $value, $p12pass)) !== '') {
//        $zip->addFromString($value.'/'.$value.'.p12', $p12);
//        $zip->addFromString($value.'/README.txt', opensslREADMEstr('p12', $value, $p12pass));
//      } else {
//        $zip->addFromString($value.'/README.txt', opensslREADMEstr('', $value, $p12pass));
//      }
      $readme = "Asterisk SIP-TLS Server \"".$openssl['dn']['commonName']."\" Credentials.\n\n";
      $readme .= "ca.crt - A self-signed Certificate Authority (CA).\n\n";
      $zip->addFromString($value.'/README.txt', $readme);
      $zip->close();

      header('Content-Type: application/zip');
      header('Content-Disposition: attachment; filename="credentials-'.$value.'.zip"');
      header('Content-Transfer-Encoding: binary');
      header('Content-Length: '.filesize($tmpfile));
      ob_end_clean();
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
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, applies to next "Create New".</p>');
    } elseif ($result == 30) {
      putHtml('<p style="color: green;">Settings saved, server credentials automatically generated.</p>');
    } elseif ($result == 31) {
      putHtml('<p style="color: green;">Client credentials automatically generated.</p>');
    } elseif ($result == 38) {
      putHtml('<p style="color: red;">Client name currently exists, specify a unique client name.</p>');
    } elseif ($result == 39) {
      putHtml('<p style="color: red;">Client names must be alphanumeric, underbar (_), dash (-), dot (.)</p>');
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
  <form id="iform" method="post" action="<?php echo $myself;?>">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;" colspan="2">
  <h2>Self-Signed SIP-TLS Server Certificate:</h2>
  </td></tr><tr><td width="240" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" value="Edit Asterisk sip.conf" name="submit_edit_sip" class="button" />
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="140">&nbsp;</td><td width="50">&nbsp;</td><td width="100">&nbsp;</td><td>&nbsp;</td><td width="100">&nbsp;</td><td width="80">&nbsp;</td></tr>
<?php
if (is_dir('/mnt/kd/acme')) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>ACME (Let\'s Encrypt) Certificate Exists!</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="color: red; text-align: center;" colspan="6">');
  putHtml('Warning: "Create New" may overwrite deployed ACME credentials.</td></tr>');
}
if ($openssl !== FALSE) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Server Certificate and Key:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Private Key Size:</td><td style="text-align: left;" colspan="4">');
  if (($key_size = getVARdef($db, 'SIPTLSCERT_CERT_KEYSIZE')) === '') {
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
  if (($signature_algorithm = getVARdef($db, 'SIPTLSCERT_CERT_ALGORITHM')) === '') {
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
  if (($value = getVARdef($db, 'SIPTLSCERT_CERT_DNSNAME')) === '') {
    $value = getPREFdef($global_prefs, 'dn_common_name_cmdstr');
  }
  putHtml('<input type="text" size="24" maxlength="128" value="'.$value.'" name="dns_name" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">');
  putHtml('Create New Certificate and Key:</td><td class="dialogText" style="text-align: left;" colspan="3">');
  putHtml('<input type="submit" value="Create New" name="submit_new_server" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="new_server" name="confirm_new_server" />&nbsp;Confirm</td></tr>');
  if (opensslSIPTLSis_valid($openssl)) {
    putHtml('<tr class="dtrow1"><td style="color: orange; text-align: center;" colspan="6">');
    putHtml('Note: "Create New" revokes all previous credentials.</td></tr>');

    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
    putHtml('<strong>Server Certificate Authority (CA):</strong>');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">');
    putHtml('Server Certificate Authority (CA):');
    echo '</td><td style="text-align: left; padding-top: 6px; padding-bottom: 7px;" colspan="3">',
         '<a href="'.$myself.'?client=server" class="actionText">Download</a>';
    putHtml('</td></tr>');
//    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
//    putHtml('<strong>Client Certificates and Keys:</strong>');
//    putHtml('</td></tr>');
//    putHtml('<tr><td style="text-align: right;" colspan="2">');
//    putHtml('Create New Client:</td><td style="text-align: left;" colspan="4">');
//    putHtml('<input type="text" size="24" maxlength="32" value="" name="new_client" />');
//    putHtml('<input type="submit" value="New Client" name="submit_new_client" />');
//    putHtml('</td></tr>');
//
//    putHtml('<tr><td colspan="6"><center>');
//    $data = opensslGETclients($openssl);
//    putHtml('<table width="85%" class="datatable">');
//    putHtml("<tr>");
//
//    if (($n = count($data)) > 0) {
//      echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Client Name", "</td>";
//      echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Credentials", "</td>";
//      for ($i = 0; $i < $n; $i++) {
//        putHtml("</tr>");
//        echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
//        echo '<td style="text-align: left;">', $data[$i], '</td>';
//        echo '<td style="text-align: center; padding-top: 6px; padding-bottom: 7px;">',
//             '<a href="'.$myself.'?client='.$data[$i].'" class="actionText">Download</a></td>';
//      }
//    } else {
//      echo '<td style="color: orange; text-align: center;">No SIP-TLS Client Credentials.', '</td>';
//    }
//
//    putHtml("</tr>");
//    putHtml("</table>");
//    putHtml('</center></td></tr>');
    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">&nbsp;</td></tr>');
    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
    putHtml('<strong>Related Asterisk sip.conf snippet:</strong>');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left; padding-top: 0px; padding-bottom: 0px;" colspan="6">');
    putHtml('<pre>');
    putText(';------------- TLS settings -------------');
    putText('tlscertfile=/mnt/kd/ssl/sip-tls/keys/server.crt');
    putText('');
    putText('tlsprivatekey=/mnt/kd/ssl/sip-tls/keys/server.key');
    putHtml('</pre>');
    putHtml('</td></tr>');
  } else {
    putHtml('<tr class="dtrow1"><td style="color: green; text-align: center;" colspan="6">');
    putHtml('Click "Create New" to automatically generate credentials.</td></tr>');
  }
}

  putHtml('</table>');
  putHtml('</form>');
  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
