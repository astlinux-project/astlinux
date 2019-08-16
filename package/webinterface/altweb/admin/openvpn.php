<?php

// Copyright (C) 2008-2018 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// openvpn.php for AstLinux
// 09-06-2008
// 12-27-2008, Added Certificate Support
// 02-06-2009, Added tls-verify, temporarily disable clients
// 08-13-2010, Added QoS Passthrough, setting passtos
// 01-03-2013, Added private keysize support
// 02-13-2013, Added OpenVPN 2.3 IPv6 support
// 12-14-2015, Added Signature Algorithm support
// 10-08-2018, Replace .ovpn ns-cert-type with remote-cert-tls
// 10-09-2018, Disable Compression by default
//
// System location of /mnt/kd/rc.conf.d directory
$OVPNCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.openvpn.conf file
$OVPNCONFFILE = '/mnt/kd/rc.conf.d/gui.openvpn.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

require_once '../common/openssl-openvpn.php';

require_once '../common/openssl.php';

if (is_file($OVPNCONFFILE)) {
  $db = parseRCconf($OVPNCONFFILE);
} else {
  $db = NULL;
}

// Function: openvpn_openssl()
//
function openvpn_openssl($keysize, $algorithm) {
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
    $orgUnit = 'OpenVPN Server';
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
  $ssl = openvpnSETUP($opts, $countryName, $stateName, $localityName, $orgName, $orgUnit, $commonName, $email);
  return($ssl);
}
$key_size = getVARdef($db, 'OVPN_CERT_KEYSIZE');
$signature_algorithm = getVARdef($db, 'OVPN_CERT_ALGORITHM');
$openssl = openvpn_openssl($key_size, $signature_algorithm);

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

$verbosity_menu = array (
  '1' => 'Low',
  '3' => 'Medium',
  '4' => 'High',
  '0' => 'None'
);

$auth_method_menu = array (
  'no' => 'Certificate',
  'yes' => 'Cert. + User/Pass'
);

$protocol_menu = array (
  'udp' => 'UDP v4',
  'tcp-server' => 'TCP v4',
  'udp6' => 'UDP v4/v6',
  'tcp6-server' => 'TCP v4/v6'
);

$key_size_menu = array (
  '1024' => '1024 Bits',
  '2048' => '2048 Bits'
);

$signature_algorithm_menu = array (
  'sha1' => 'SHA-1',
  'sha256' => 'SHA-256'
);

$topology_menu = array (
  '' => 'Use Default',
  'net30' => '[net30] older, OpenVPN 2.0 default',
  'p2p' => '[p2p] point-to-point, no Windows clients',
  'subnet' => '[subnet] latest, requires OpenVPN 2.1+ clients'
);

$client_isolation_menu = array (
  'no'  => 'Pass Client->Client traffic',
  'yes' => 'Deny Client->Client traffic'
);

// Function: saveOVPNsettings
//
function saveOVPNsettings($conf_dir, $conf_file, $disabled = NULL) {
  global $openssl;

  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.openvpn.conf - start ###\n###\n");

  $value = 'OVPN_USER_PASS_VERIFY="'.$_POST['auth_method'].'"';
  fwrite($fp, "### Auth Method\n".$value."\n");

  $value = 'OVPN_DEV="'.$_POST['device'].'"';
  fwrite($fp, "### Device\n".$value."\n");

  $value = 'OVPN_PORT="'.tuq($_POST['port']).'"';
  fwrite($fp, "### Port Number\n".$value."\n");

  $value = 'OVPN_PROTOCOL="'.$_POST['protocol'].'"';
  fwrite($fp, "### Protocol\n".$value."\n");

  $value = 'OVPN_VERBOSITY="'.$_POST['verbosity'].'"';
  fwrite($fp, "### Log Verbosity\n".$value."\n");

  $value = 'OVPN_LZO="'.$_POST['compression'].'"';
  fwrite($fp, "### Compression\n".$value."\n");

  $value = 'OVPN_QOS="'.$_POST['qos_passthrough'].'"';
  fwrite($fp, "### QoS Passthrough\n".$value."\n");

  $value = 'OVPN_CIPHER="'.$_POST['cipher_menu'].'"';
  fwrite($fp, "### Cipher\n".$value."\n");

  $value = 'OVPN_AUTH="'.$_POST['auth_hmac'].'"';
  fwrite($fp, "### Auth HMAC\n".$value."\n");

  $value = 'OVPN_TUNNEL_HOSTS="'.tuq($_POST['tunnel_external_hosts']).'"';
  fwrite($fp, "### Allowed External Hosts\n".$value."\n");

  $value = 'OVPN_CLIENT_ISOLATION="'.$_POST['isolation'].'"';
  fwrite($fp, "### Client Isolation\n".$value."\n");

  $value = 'OVPN_HOSTNAME="'.tuq($_POST['server_hostname']).'"';
  fwrite($fp, "### Server Hostname\n".$value."\n");

  $value = 'OVPN_SERVER="'.tuq($_POST['server']).'"';
  fwrite($fp, "### Server IPv4 Network\n".$value."\n");

  $value = 'OVPN_SERVERV6="'.tuq($_POST['serverv6']).'"';
  fwrite($fp, "### Server IPv6 Network\n".$value."\n");

  $value = 'OVPN_TOPOLOGY="'.$_POST['topology'].'"';
  fwrite($fp, "### Topology\n".$value."\n");

  $value = 'OVPN_PUSH="';
  fwrite($fp, "### Server Push\n".$value."\n");
  $value = stripshellsafe($_POST['push']);
  $value = str_replace(chr(13), '', $value);
  if (($value = trim($value, chr(10))) !== '') {
    fwrite($fp, $value."\n");
  }
  fwrite($fp, '"'."\n");

  $value = 'OVPN_OTHER="';
  fwrite($fp, "### Raw Commands\n".$value."\n");
  $value = stripshellsafe($_POST['other']);
  $value = str_replace(chr(13), '', $value);
  if (($value = trim($value, chr(10))) !== '') {
    fwrite($fp, $value."\n");
  }
  fwrite($fp, '"'."\n");

  $value = 'OVPN_CERT_KEYSIZE="'.$_POST['key_size'].'"';
  fwrite($fp, "### Private Key Size\n".$value."\n");

  $value = 'OVPN_CERT_ALGORITHM="'.$_POST['signature_algorithm'].'"';
  fwrite($fp, "### Signature Algorithm\n".$value."\n");

if (opensslOPENVPNis_valid($openssl)) {
  $value = 'OVPN_CA="'.$openssl['key_dir'].'/ca.crt"';
  fwrite($fp, "### CA File\n".$value."\n");
  $value = 'OVPN_CERT="'.$openssl['key_dir'].'/server.crt"';
  fwrite($fp, "### CERT File\n".$value."\n");
  $value = 'OVPN_KEY="'.$openssl['key_dir'].'/server.key"';
  fwrite($fp, "### Key File\n".$value."\n");
  $value = 'OVPN_DH="'.$openssl['dh_pem'].'"';
  fwrite($fp, "### DH File\n".$value."\n");
  if ($_POST['tls_auth'] === 'yes' && openvpnCREATEtls_auth($openssl)) {
    $value = 'OVPN_TA="'.$openssl['key_dir'].'/ta.key"';
  } else {
    $value = 'OVPN_TA=""';
  }
  fwrite($fp, "### TLS-Auth File\n".$value."\n");
  if (! is_null($disabled)) {
    if (arrayCount($disabled) > 0) {
      $value = 'OVPN_VALIDCLIENTS="';
      fwrite($fp, "### Valid Clients\n".$value."\n");
      $client_list = opensslGETclients($openssl);
      foreach ($client_list as $value) {
        foreach ($disabled as $disable) {
          if ($value === $disable) {
            $value = '';
            break;
          }
        }
        if ($value !== '') {
          fwrite($fp, $value."\n");
        }
      }
      fwrite($fp, '"'."\n");
    }
  }
} else {
  $base = '/mnt/kd/openvpn/easy-rsa/keys';
  $value = isset($_POST['ca']) ? tuq($_POST['ca']) : $base.'/ca.crt';
  $value = 'OVPN_CA="'.$value.'"';
  fwrite($fp, "### CA File\n".$value."\n");
  $value = isset($_POST['cert']) ? tuq($_POST['cert']) : $base.'/server.crt';
  $value = 'OVPN_CERT="'.$value.'"';
  fwrite($fp, "### CERT File\n".$value."\n");
  $value = isset($_POST['key']) ? tuq($_POST['key']) : $base.'/server.key';
  $value = 'OVPN_KEY="'.$value.'"';
  fwrite($fp, "### Key File\n".$value."\n");
  $value = isset($_POST['dh']) ? tuq($_POST['dh']) : $base.'/dh1024.pem';
  $value = 'OVPN_DH="'.$value.'"';
  fwrite($fp, "### DH File\n".$value."\n");
  if ($_POST['tls_auth'] === 'yes') {
    $value = isset($_POST['ta']) ? tuq($_POST['ta']) : $base.'/ta.key';
    $value = 'OVPN_TA="'.$value.'"';
  } else {
    $value = 'OVPN_TA=""';
  }
  fwrite($fp, "### TLS-Auth File\n".$value."\n");
}

  fwrite($fp, "### gui.openvpn.conf - end ###\n");
  fclose($fp);

  return($result);
}

// Function: isClientDisabled
//
function isClientDisabled($vars, $client) {

  if (($line = getVARdef($vars, 'OVPN_VALIDCLIENTS')) === '') {
    return(FALSE);
  }
  $linetokens = explode("\n", $line);
  foreach ($linetokens as $data) {
    if ($data !== '') {
      $datatokens = explode('~', $data);
      if ($datatokens[0] === $client) {
        return(FALSE);
      }
    }
  }
  return(TRUE);
}

// Function: ovpnProfile
//
function ovpnProfile($db, $ssl, $client, &$ta_file) {

  $default = array (
    'client',
    'remote-cert-tls server',
    'nobind',
    'persist-key',
    'persist-tun',
    'dev tun',
    'verb 3'
  );

  $ca_file = $ssl['key_dir'].'/ca.crt';
  if (($ta_file = getVARdef($db, 'OVPN_TA')) !== '') {
    if (! is_file($ta_file)) {
      $ta_file = '';
    }
  }

  if (($server_hostname = getVARdef($db, 'OVPN_HOSTNAME')) === '') {
    $server_hostname = get_HOSTNAME_DOMAIN();
  }
  if (($port = getVARdef($db, 'OVPN_PORT')) === '') {
    return(FALSE);
  }
  if (($protocol = substr(getVARdef($db, 'OVPN_PROTOCOL'), 0 , 3)) === '') {
    return(FALSE);
  }

  $str = '';
  foreach (explode(' ', $server_hostname) as $hostname) {
    if ($hostname !== '') {
      $str .= "remote $hostname $port $protocol\n";
    }
  }

  if (getVARdef($db, 'OVPN_LZO') === 'yes') {
    $str .= "comp-lzo yes\n";
  }
  if (getVARdef($db, 'OVPN_USER_PASS_VERIFY') === 'yes') {
    $str .= "auth-user-pass\n";
    $str .= "auth-retry interact\n";
  }
  if (($cipher = getVARdef($db, 'OVPN_CIPHER')) !== '') {
    $str .= "cipher $cipher\n";
  }
  if (($auth_hmac = getVARdef($db, 'OVPN_AUTH')) !== '') {
    $str .= "auth $auth_hmac\n";
  }
  if ($ta_file !== '') {
    $str .= "key-direction 1\n";
  }
  foreach ($default as $value) {
    $str .= "$value\n";
  }
  if (($caStr = @file_get_contents($ca_file)) !== FALSE) {
    $str .= "<ca>\n";
    $str .= $caStr;
    $str .= "</ca>\n";
  }
  if ($client !== '') {
    if (($certStr = @file_get_contents($ssl['key_dir'].'/'.$client.'.crt')) !== FALSE) {
      $str .= "<cert>\n";
      $str .= $certStr;
      $str .= "</cert>\n";
    }
    if (($keyStr = @file_get_contents($ssl['key_dir'].'/'.$client.'.key')) !== FALSE) {
      $str .= "<key>\n";
      $str .= $keyStr;
      $str .= "</key>\n";
    }
  }
  if ($ta_file !== '') {
    if (($taStr = @file_get_contents($ta_file)) !== FALSE) {
      $str .= "<tls-auth>\n";
      $str .= $taStr;
      $str .= "</tls-auth>\n";
    }
  }
  return($str);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $disabled = isset($_POST['disabled']) ? $_POST['disabled'] : NULL;
    $result = saveOVPNsettings($OVPNCONFDIR, $OVPNCONFFILE, $disabled);
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('openvpn', 10, $result, 'init');
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_user_pass'])) {
    $disabled = isset($_POST['disabled']) ? $_POST['disabled'] : NULL;
    $result = saveOVPNsettings($OVPNCONFDIR, $OVPNCONFFILE, $disabled);
    header('Location: /admin/openvpnuserpass.php');
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
      if (($openssl = openvpn_openssl($key_size, $signature_algorithm)) !== FALSE) {
        if (opensslCREATEselfCert($openssl)) {
          if (opensslCREATEserverCert($openssl)) {
            if (opensslCREATEdh_pem($openssl)) {
              $result = 30;
            }
          }
        }
      }
      saveOVPNsettings($OVPNCONFDIR, $OVPNCONFFILE);
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_delete_all'])) {
    if (isset($_POST['confirm_delete_all'])) {
      opensslDELETEkeys($openssl);
      saveOVPNsettings($OVPNCONFDIR, $OVPNCONFFILE);
      $result = 32;
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_new_client'])) {
    if (($value = tuq($_POST['new_client'])) !== '') {
      if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]*$/', $value)) {
        if ($value !== 'ta' &&
            ! is_file($openssl['key_dir'].'/'.$value.'.crt') &&
            ! is_file($openssl['key_dir'].'/'.$value.'.key')) {
          if (opensslCREATEclientCert($value, $openssl)) {
            $disabled = isset($_POST['disabled']) ? $_POST['disabled'] : NULL;
            saveOVPNsettings($OVPNCONFDIR, $OVPNCONFFILE, $disabled);
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
} elseif (isset($_GET['client']) && $openssl !== FALSE) {
  $result = 5;
  $client_list = opensslGETclients($openssl);
  foreach ($client_list as $value) {
    if ($value === (string)$_GET['client']) {
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
        if (($ovpn = ovpnProfile($db, $openssl, $value, $tls_auth_file)) !== FALSE) {
          $zip->addFromString($value.'/openvpn-cert-key/'.$value.'.ovpn', $ovpn);
          if (($ovpn = preg_filter('/<cert>.*<\/cert>/s', '', $ovpn)) !== NULL) {
            if (($ovpn = preg_filter('/<key>.*<\/key>/s', '', $ovpn)) !== NULL) {
              $zip->addFromString($value.'/openvpn-nocert-nokey/'.$value.'.ovpn', $ovpn);
              if (($ovpn = preg_filter('/<ca>.*<\/ca>/s', '', $ovpn)) !== NULL) {
                $ovpn .= "pkcs12 $value.p12\n";
                $zip->addFromString($value.'/openvpn-pkcs12/'.$value.'.ovpn', $ovpn);
                $zip->addFromString($value.'/openvpn-pkcs12/'.$value.'.p12', $p12);
              }
            }
          }
          if ($tls_auth_file !== '') {
            $zip->addFile($tls_auth_file, $value.'/'.$value.'-ta.key');
            $zip->addFromString($value.'/README.txt', opensslREADMEstr('ovpn-ta', $value, $p12pass));
          } else {
            $zip->addFromString($value.'/README.txt', opensslREADMEstr('ovpn', $value, $p12pass));
          }
        } else {
          $zip->addFromString($value.'/README.txt', opensslREADMEstr('p12', $value, $p12pass));
        }
      } else {
        $zip->addFromString($value.'/README.txt', opensslREADMEstr('', $value, $p12pass));
      }
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
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">OpenVPN Server'.statusPROCESS('openvpn').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart Server" to apply any changed settings.</p>');
    } elseif ($result == 30) {
      putHtml('<p style="color: green;">Settings saved, server credentials automatically generated.</p>');
    } elseif ($result == 31) {
      putHtml('<p style="color: green;">Client credentials automatically generated.</p>');
    } elseif ($result == 32) {
      putHtml('<p style="color: green;">Settings saved, automatically generated credentials deleted.</p>');
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
  <script language="JavaScript" type="text/javascript">
  //<![CDATA[
  function auth_method_change() {
    var form = document.getElementById("iform");
    switch (form.auth_method.selectedIndex) {
      case 0: // Certificate
        form.submit_user_pass.style.visibility = "hidden";
        break;
      case 1: // Cert. + User/Pass
        form.submit_user_pass.style.visibility = "visible";
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
  <h2>OpenVPN Server Configuration:</h2>
  </td></tr><tr><td width="260" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart Server" name="submit_restart" />
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
  $protocol = getVARdef($db, 'OVPN_PROTOCOL');
  putHtml('<select name="protocol">');
  foreach ($protocol_menu as $key => $value) {
    $sel = ($protocol === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td><td style="text-align: right;" colspan="1">');
  putHtml('Port:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  if (($value = getVARdef($db, 'OVPN_PORT')) === '') {
    $value = '1194';
  }
  putHtml('<input type="text" size="8" maxlength="10" value="'.$value.'" name="port" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Log Verbosity:');
  putHtml('</td><td style="text-align: left;" colspan="1">');
  $verbosity = getVARdef($db, 'OVPN_VERBOSITY');
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
  $sel = (getVARdef($db, 'OVPN_LZO') === 'no') ? ' selected="selected"' : '';
  putHtml('<option value="no"'.$sel.'>Off</option>');
  $sel = (getVARdef($db, 'OVPN_LZO') === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>LZO</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('QoS Passthrough:');
  putHtml('</td><td style="text-align: left;" colspan="1">');
  putHtml('<select name="qos_passthrough">');
  $sel = (getVARdef($db, 'OVPN_QOS') === 'no') ? ' selected="selected"' : '';
  putHtml('<option value="no"'.$sel.'>No</option>');
  $sel = (getVARdef($db, 'OVPN_QOS') === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>Yes</option>');
  putHtml('</select>');
  putHtml('</td><td style="text-align: right;" colspan="1">');
  putHtml('Legacy Cipher:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  $cipher = getVARdef($db, 'OVPN_CIPHER');
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
  $sel = (getVARdef($db, 'OVPN_DEV') === 'tun0') ? ' selected="selected"' : '';
  putHtml('<option value="tun0"'.$sel.'>tun0</option>');
  $sel = (getVARdef($db, 'OVPN_DEV') === 'tun1') ? ' selected="selected"' : '';
  putHtml('<option value="tun1"'.$sel.'>tun1</option>');
  putHtml('</select>');
  putHtml('</td><td style="text-align: right;" colspan="1">');
  putHtml('Auth HMAC:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  $auth_hmac = getVARdef($db, 'OVPN_AUTH');
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
  $var_types = array('OVPN_OTHER1', 'OVPN_OTHER2', 'OVPN_OTHER');
  foreach ($var_types as $var_type) {
    if (($value = getVARdef($db, $var_type)) !== '') {
      $value = str_replace(chr(10), chr(13), $value);
      if (($value = trim($value, chr(13))) !== '') {
        echo htmlspecialchars($value), chr(13);
      }
    }
  }
  putHtml('</textarea>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Authentication:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Auth Method:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  if (($auth_method = getVARdef($db, 'OVPN_USER_PASS_VERIFY')) === '') {
    $auth_method = 'no';
  }
  putHtml('<select name="auth_method" onchange="auth_method_change()">');
  foreach ($auth_method_menu as $key => $value) {
    $sel = ($auth_method === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  putHtml('<input type="submit" value="User/Pass" name="submit_user_pass" class="button" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Extra TLS-Auth:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $tls_auth = getVARdef($db, 'OVPN_TA');
  putHtml('<select name="tls_auth">');
  $sel = ($tls_auth === '') ? ' selected="selected"' : '';
  putHtml('<option value=""'.$sel.'>No</option>');
  $sel = ($tls_auth !== '') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>Yes</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Firewall Options:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('External Hosts:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'OVPN_TUNNEL_HOSTS')) === '') {
    $value = '0/0';
  }
  putHtml('<input type="text" size="48" maxlength="200" name="tunnel_external_hosts" value="'.$value.'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Client Isolation:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $isolation = getVARdef($db, 'OVPN_CLIENT_ISOLATION');
  putHtml('<select name="isolation">');
  foreach ($client_isolation_menu as $key => $value) {
    $sel = ($isolation === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Server Mode:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Server Hostname(s):');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($server_hostname = getVARdef($db, 'OVPN_HOSTNAME')) === '') {
    $server_hostname = get_HOSTNAME_DOMAIN();
  }
  putHtml('<input type="text" size="48" maxlength="200" value="'.$server_hostname.'" name="server_hostname" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Network IPv4 NM:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'OVPN_SERVER')) === '') {
    $value = '10.8.0.0 255.255.255.0';
  }
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="server" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Network IPv6/nn:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'OVPN_SERVERV6');
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="serverv6" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Topology:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $topology = getVARdef($db, 'OVPN_TOPOLOGY');
  putHtml('<select name="topology">');
  foreach ($topology_menu as $key => $value) {
    $sel = ($topology === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('"push":');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  echo '<textarea name="push" rows="6" cols="56" wrap="off" class="edititemText">';
  $var_types = array('OVPN_PUSH1', 'OVPN_PUSH2', 'OVPN_PUSH3', 'OVPN_PUSH4', 'OVPN_PUSH');
  foreach ($var_types as $var_type) {
    if (($value = getVARdef($db, $var_type)) !== '') {
      $value = str_replace(chr(10), chr(13), $value);
      if (($value = trim($value, chr(13))) !== '') {
        echo htmlspecialchars($value), chr(13);
      }
    }
  }
  putHtml('</textarea>');
  putHtml('</td></tr>');

if ($openssl !== FALSE) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Server Certificate and Key:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Private Key Size:</td><td style="text-align: left;" colspan="4">');
  if (($key_size = getVARdef($db, 'OVPN_CERT_KEYSIZE')) === '') {
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
  if (($signature_algorithm = getVARdef($db, 'OVPN_CERT_ALGORITHM')) === '') {
    $signature_algorithm = 'sha256';
  }
  putHtml('<select name="signature_algorithm">');
  foreach ($signature_algorithm_menu as $key => $value) {
    $sel = ($signature_algorithm === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">');
  putHtml('Create New Certificate and Key:</td><td class="dialogText" style="text-align: left;" colspan="3">');
  $msg = '';
  if (! is_file($openssl['dh_pem'])) {
    $msg .= ' onclick="alert(\'';
    $msg .= 'The dh1024.pem file must be generated.\n';
    $msg .= '(1024 bit long safe prime number)\n\n';
    $msg .= 'This is going to take a long time, anywhere from 30 seconds to 10+ minutes. ';
    $msg .= 'If your browser times-out with a blank screen, reload your browser page and the previous form data will be re-submitted.';
    $msg .= '\')"';
  }
  putHtml('<input type="submit" value="Create New" name="submit_new_server"'.$msg.' />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="new_server" name="confirm_new_server" />&nbsp;Confirm</td></tr>');
  if (opensslOPENVPNis_valid($openssl)) {
    putHtml('<tr class="dtrow1"><td style="color: orange; text-align: center;" colspan="6">');
    putHtml('Note: "Create New" revokes all previous clients.</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="3">');
    putHtml('Manually Specify Certificates and Keys:</td><td class="dialogText" style="text-align: left;" colspan="3">');
    putHtml('<input type="submit" value="Manual" name="submit_delete_all" />');
    putHtml('&ndash;');
    putHtml('<input type="checkbox" value="delete_all" name="confirm_delete_all" />&nbsp;Confirm</td></tr>');

    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
    putHtml('<strong>Client Certificates and Keys:</strong>');
    putHtml('</td></tr>');
    putHtml('<tr><td style="text-align: right;" colspan="2">');
    putHtml('Create New Client:</td><td style="text-align: left;" colspan="4">');
    putHtml('<input type="text" size="24" maxlength="32" value="" name="new_client" />');
    putHtml('<input type="submit" value="New Client" name="submit_new_client" />');
    putHtml('</td></tr>');

    putHtml('<tr><td colspan="6"><center>');
    $data = opensslGETclients($openssl);
    putHtml('<table width="85%" class="datatable">');
    putHtml("<tr>");

    if (($n = arrayCount($data)) > 0) {
      echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Client Name", "</td>";
      echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Credentials", "</td>";
      echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Disabled", "</td>";
      for ($i = 0; $i < $n; $i++) {
        putHtml("</tr>");
        echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
        echo '<td style="text-align: left;">', $data[$i], '</td>';
        echo '<td style="text-align: center;">', '<a href="'.$myself.'?client='.$data[$i].'" class="actionText">Download</a></td>';
        $sel = isClientDisabled($db, $data[$i]) ? ' checked="checked"' : '';
        echo '<td style="text-align: center;">', '<input type="checkbox" name="disabled[]" value="'.$data[$i].'"'.$sel.' />', '</td>';
      }
    } else {
      echo '<td style="color: orange; text-align: center;">No Client Credentials.', '</td>';
    }

    putHtml("</tr>");
    putHtml("</table>");
    putHtml('</center></td></tr>');
  } else {
    putHtml('<tr class="dtrow1"><td style="color: green; text-align: center;" colspan="6">');
    putHtml('Click "Create New" to automatically generate credentials.</td></tr>');
  }
}

if (! opensslOPENVPNis_valid($openssl)) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Certificate and Key Locations:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('CA File:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  if (($value = getVARdef($db, 'OVPN_CA')) === '') {
    $value = '/mnt/kd/openvpn/easy-rsa/keys/ca.crt';
  }
  putHtml('<input type="text" size="64" maxlength="128" value="'.$value.'" name="ca" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('CERT File:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  if (($value = getVARdef($db, 'OVPN_CERT')) === '') {
    $value = '/mnt/kd/openvpn/easy-rsa/keys/server.crt';
  }
  putHtml('<input type="text" size="64" maxlength="128" value="'.$value.'" name="cert" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Key File:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  if (($value = getVARdef($db, 'OVPN_KEY')) === '') {
    $value = '/mnt/kd/openvpn/easy-rsa/keys/server.key';
  }
  putHtml('<input type="text" size="64" maxlength="128" value="'.$value.'" name="key" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('DH File:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  if (($value = getVARdef($db, 'OVPN_DH')) === '') {
    $value = '/mnt/kd/openvpn/easy-rsa/keys/dh1024.pem';
  }
  putHtml('<input type="text" size="64" maxlength="128" value="'.$value.'" name="dh" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('TLS-Auth File:');
  putHtml('</td><td style="text-align: left;" colspan="5">');
  $value = getVARdef($db, 'OVPN_TA');
  putHtml('<input type="text" size="64" maxlength="128" value="'.$value.'" name="ta" />');
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
