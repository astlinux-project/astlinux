<?php

// Copyright (C) 2008-2010 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// ipsec.php for AstLinux
// 09-06-2008
// 08-19-2009, Add IPSEC_PSK_ASSOCIATIONS v2.0 format support
// 11-26-2010, Add certificate support
//
// System location of /mnt/kd/rc.conf.d directory
$IPSECCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.ipsec.conf file
$IPSECCONFFILE = '/mnt/kd/rc.conf.d/gui.ipsec.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

require_once '../common/openssl-ipsec.php';

$openssl = FALSE;

$log_level_menu = array (
  'error' => 'Error',
  'warning' => 'Warning',
  'notify' => 'Notify',
  'info' => 'Info',
  'debug' => 'Debug'
);

$nat_t_menu = array (
  '' => 'Disable',
  'nat' => 'Enable',
  'force' => 'Force'
);

$p1_encrypt_menu = array (
  'aes 128' => 'AES-128',
  'aes 192' => 'AES-192',
  'aes 256' => 'AES-256',
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

$p2_auth_menu = array (
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

$method_menu = array (
  'psk' => 'Preshared Key&nbsp;&nbsp;&nbsp;&gt;&gt;&gt;',
  'rsa' => 'Certificate'
);

// Function: getPhase1str
//
function getPhase1str($encrypt, $hash, $dhgroup, $lifetime) {
  global $p1_encrypt_menu;
  global $p1_hash_menu;
  global $p1_dhgroup_menu;

  $str = $p1_encrypt_menu[$encrypt].' / '.$p1_hash_menu[$hash].' / '.$p1_dhgroup_menu[$dhgroup];
  if ($lifetime === '') {
    $lifetime='none';
  }
  $str .= ' / Lifetime='.$lifetime;

  return(trim($str));
}

// Function: getPhase2str
//
function getPhase2str($encrypt, $auth, $pfsgroup, $lifetime) {
  global $p1_encrypt_menu;
  global $p2_auth_menu;
  global $p2_pfsgroup_menu;

  $str = '';
  foreach ($p1_encrypt_menu as $key => $value) {
    if (inStringList($key, $encrypt, ',')) {
      $str .= ' '.$value;
    }
  }
  $str .= ' /';
  foreach ($p2_auth_menu as $key => $value) {
    if (inStringList($key, $auth, ',')) {
      $str .= ' '.$value;
    }
  }
  $str .= ' /';
  $str .= ' '.$p2_pfsgroup_menu[$pfsgroup];
  if ($lifetime === '') {
    $lifetime='3600';
  }
  $str .= ' / Lifetime='.$lifetime;

  return(trim($str));
}

// Function: getIDstr
//
function getIDstr($key, $peer) {

  $str = '*** Undefined Certificate ***';
  if ($key !== '') {
    $str = 'Preshared Key / Length='.strlen($key);
  } else {
    if (($ssl = ipsecSETUP($peer)) !== FALSE) {
      if (opensslIPSECis_valid($ssl)) {
        getCREDinfo($ssl, 'peer_crt', $CN);
        $str = 'Certificate / CN='.$CN;
      }
    }
  }
  return($str);
}

// Function: saveIPSECsettings
//
function saveIPSECsettings($conf_dir, $conf_file, $db, $delete = NULL) {

  if (is_dir($conf_dir) === FALSE) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.ipsec.conf - start ###\n###\n");

  $value = 'IPSEC_LOGLEVEL="'.$_POST['log_level'].'"';
  fwrite($fp, "### Log Level\n".$value."\n");

  $value = 'IPSEC_PSK_ASSOCIATIONS="';
  fwrite($fp, "### Peer Tunnels\n".$value."\n");
  if (($n = arrayCount($db['data'])) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $skip = FALSE;
      if (! is_null($delete)) {
        for ($j = 0; $j < arrayCount($delete); $j++) {
          if ($delete[$j] == $i) {
            $skip = TRUE;
            break;
          }
        }
      }
      if (! $skip) {
        $value = $db['data'][$i]['local_host'];
        $value .= '~'.$db['data'][$i]['local_net'];
        $value .= '~'.$db['data'][$i]['remote_host'];
        $value .= '~'.$db['data'][$i]['remote_net'];
        $value .= '~'.$db['data'][$i]['key'];
        $value .= '~'.$db['data'][$i]['p1_encrypt'];
        $value .= '~'.$db['data'][$i]['p1_hash'];
        $value .= '~'.$db['data'][$i]['p1_dhgroup'];
        $value .= '~'.$db['data'][$i]['p2_encrypt'];
        $value .= '~'.$db['data'][$i]['p2_auth'];
        $value .= '~'.$db['data'][$i]['p2_pfsgroup'];
        $value .= '~'.$db['data'][$i]['nat_t'];
        $value .= '~'.$db['data'][$i]['auto_establish'];
        $value .= '~'.$db['data'][$i]['p1_lifetime'];
        $value .= '~'.$db['data'][$i]['p2_lifetime'];
        if ($db['data'][$i]['key'] === '') {
          if (($ssl = ipsecSETUP($db['data'][$i]['remote_host'])) !== FALSE) {
            $value .= '~'.$ssl['key_dir'];
            $value .= '~'.basename($ssl['peer_crt']);
            $value .= '~'.basename($ssl['peer_key']);
            $value .= '~'.basename($ssl['ca_crt']);
          }
        }
        fwrite($fp, $value."\n");
      } else {
        ipsecDELETEpeer($db['data'][$i]['remote_host']);
      }
    }
  }
  fwrite($fp, '"'."\n");

  fwrite($fp, "### gui.ipsec.conf - end ###\n");
  fclose($fp);

  return(11);
}

// Function: parseIPSECconf
//
function parseIPSECconf($vars) {
  $id = 0;

  if (($line = getVARdef($vars, 'IPSEC_PSK_ASSOCIATIONS')) !== '') {
    $linetokens = explode("\n", $line);
    foreach ($linetokens as $data) {
      if ($data !== '') {
        if (strpos($data, '~') === FALSE) {
          $datatokens = explode(':', $data);
          $db['data'][$id]['local_host'] = $datatokens[0];
          $db['data'][$id]['local_net'] = $datatokens[1];
          $db['data'][$id]['remote_host'] = $datatokens[2];
          $db['data'][$id]['remote_net'] = $datatokens[3];
          $db['data'][$id]['key'] = $datatokens[4];
          $profile = ($datatokens[5] === 'normal' || $datatokens[5] === '3des');
          $db['data'][$id]['p1_encrypt'] = $profile ? '3des' : 'aes 128';
          $db['data'][$id]['p1_hash'] = $profile ? 'md5' : 'sha256';
          $db['data'][$id]['p1_dhgroup'] = 'modp1024';
          $db['data'][$id]['p2_encrypt'] = $profile ? '3des' : 'aes 128';
          $db['data'][$id]['p2_auth'] = $profile ? 'hmac_md5' : 'hmac_sha256';
          $db['data'][$id]['p2_pfsgroup'] = $profile ? 'modp768' : 'modp1024';
          $db['data'][$id]['nat_t'] = '';
          $db['data'][$id]['auto_establish'] = '';
          $db['data'][$id]['p1_lifetime'] = '';
          $db['data'][$id]['p2_lifetime'] = '';
        } else {
          $datatokens = explode('~', $data);
          $db['data'][$id]['local_host'] = $datatokens[0];
          $db['data'][$id]['local_net'] = $datatokens[1];
          $db['data'][$id]['remote_host'] = $datatokens[2];
          $db['data'][$id]['remote_net'] = $datatokens[3];
          $db['data'][$id]['key'] = $datatokens[4];
          $db['data'][$id]['p1_encrypt'] = $datatokens[5];
          $db['data'][$id]['p1_hash'] = $datatokens[6];
          $db['data'][$id]['p1_dhgroup'] = $datatokens[7];
          $db['data'][$id]['p2_encrypt'] = $datatokens[8];
          $db['data'][$id]['p2_auth'] = $datatokens[9];
          $db['data'][$id]['p2_pfsgroup'] = $datatokens[10];
          $db['data'][$id]['nat_t'] = $datatokens[11];
          $db['data'][$id]['auto_establish'] = isset($datatokens[12]) ? $datatokens[12] : '';
          $db['data'][$id]['p1_lifetime'] = isset($datatokens[13]) ? $datatokens[13] : '';
          $db['data'][$id]['p2_lifetime'] = isset($datatokens[14]) ? $datatokens[14] : '';
        }
        $id++;
      }
    }
  }
  // Sort by Remote Host
  if ($id > 1) {
    foreach ($db['data'] as $key => $row) {
      $remote_host[$key] = $row['remote_host'];
    }
    array_multisort($remote_host, SORT_ASC, SORT_STRING, $db['data']);
  }
  return($db);
}

// Function: addTunnel
//
function addTunnel(&$db, $id) {

  $local_host = tuq($_POST['local_host']);
  $local_net = tuq($_POST['local_net']);
  $remote_host = tuq($_POST['remote_host']);
  $remote_net = tuq($_POST['remote_net']);

  if (($method = $_POST['method']) === 'psk') {
    $key = tuq($_POST['key']);
  } else {
    $key = '';
  }

  if ($remote_host === '') {
    return(FALSE);
  }
  if ($key === '' && $method !== 'rsa') {
    return(1);
  }
  if ($local_host === '' ||
      $local_net === '' ||
      $remote_net === '') {
    return(2);
  }

  $p1_encrypt = $_POST['p1_encrypt'];
  $p1_hash = $_POST['p1_hash'];
  $p1_dhgroup = $_POST['p1_dhgroup'];
  $p1_lifetime = tuq($_POST['p1_lifetime']);

  $p2_encrypt = '';
  if (isset($_POST['p2_encrypt'])) {
    $p2_encrypts = $_POST['p2_encrypt'];
    foreach ($p2_encrypts as $var) {
      $p2_encrypt .= ','.$var;
    }
  }
  $p2_encrypt = trim($p2_encrypt, ' ,');

  $p2_auth = '';
  if (isset($_POST['p2_auth'])) {
    $p2_auths = $_POST['p2_auth'];
    foreach ($p2_auths as $var) {
      $p2_auth .= ','.$var;
    }
  }
  $p2_auth = trim($p2_auth, ' ,');

  $p2_pfsgroup = $_POST['p2_pfsgroup'];
  $p2_lifetime = tuq($_POST['p2_lifetime']);
  $nat_t = $_POST['nat_t'];
  if (($auto_establish = tuq($_POST['auto_establish'])) === 'none' ) {
    $auto_establish = '';
  }

  $db['data'][$id]['local_host'] = $local_host;
  $db['data'][$id]['local_net'] = $local_net;
  $db['data'][$id]['remote_host'] = $remote_host;
  $db['data'][$id]['remote_net'] = $remote_net;
  $db['data'][$id]['key'] = $key;
  $db['data'][$id]['p1_encrypt'] = $p1_encrypt;
  $db['data'][$id]['p1_hash'] = $p1_hash;
  $db['data'][$id]['p1_dhgroup'] = $p1_dhgroup;
  $db['data'][$id]['p2_encrypt'] = $p2_encrypt;
  $db['data'][$id]['p2_auth'] = $p2_auth;
  $db['data'][$id]['p2_pfsgroup'] = $p2_pfsgroup;
  $db['data'][$id]['nat_t'] = $nat_t;
  $db['data'][$id]['auto_establish'] = $auto_establish;
  $db['data'][$id]['p1_lifetime'] = $p1_lifetime;
  $db['data'][$id]['p2_lifetime'] = $p2_lifetime;

  return(TRUE);
}

if (is_file($IPSECCONFFILE)) {
  $vars = parseRCconf($IPSECCONFFILE);
} else {
  $vars = NULL;
}
$db = parseIPSECconf($vars);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $n = arrayCount($db['data']);
    $id = $n;
    for ($i = 0; $i < $n; $i++) {
      if ($db['data'][$i]['remote_host'] === tuq($_POST['remote_host'])) {
        $id = $i;
        break;
      }
    }
    $ok = addTunnel($db, $id);
    $result = saveIPSECsettings($IPSECCONFDIR, $IPSECCONFFILE, $db);
    if ($result == 11) {
      if ($ok === 1) {
        $result = 12;
      } elseif ($ok === 2) {
        $result = 13;
      }
    }
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('racoon', 10, $result, 'init');
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    if (arrayCount($delete) > 0) {
      $result = saveIPSECsettings($IPSECCONFDIR, $IPSECCONFFILE, $db, $delete);
    }
  } elseif (isset($_FILES['creds'])) {
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
          if ($key !== 'ca_crt' && $key !== 'peer_crt') {
            $result = 23;
            break;
          }
        } elseif (stripos($name, '.key', $len) !== FALSE) {
          if ($key !== 'peer_key') {
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
      } else {
        $result = 21;
        break;
      }
    }
    if ($result == 1) {
      $result = 99;
      if (($openssl = ipsecSETUP($_POST['peer'])) !== FALSE) {
        $result = 30;
        foreach ($_FILES['creds']['tmp_name'] as $key => $tmp_name) {
          if (! move_uploaded_file($tmp_name, $openssl[$key])) {
            $result = 3;
            break;
          }
          if ($key === 'peer_key') {
            chmod($openssl[$key], 0600);
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

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 2) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error creating file.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">IPsec VPN'.statusPROCESS('racoon').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart IPsec" to apply any changed settings.</p>');
    } elseif ($result == 12) {
      putHtml('<p style="color: red;">Missing Preshared Key, Changes not applied.</p>');
    } elseif ($result == 13) {
      putHtml('<p style="color: red;">Missing Host/Net, Changes not applied.</p>');
    } elseif ($result == 20) {
      putHtml('<p style="color: red;">File size is not reasonable for a cert or key.</p>');
    } elseif ($result == 21) {
      putHtml('<p style="color: red;">All three files, CA, Cert and Key must be defined.</p>');
    } elseif ($result == 22) {
      putHtml('<p style="color: red;">Invalid suffix, only files ending with .crt and .key are allowed.</p>');
    } elseif ($result == 23) {
      putHtml('<p style="color: red;">Incorrect file suffix for file definition.</p>');
    } elseif ($result == 30) {
      putHtml('<p style="color: green;">Peer Credentials successfully saved, restart to apply.</p>');
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
  <tr><td style="text-align: center;" colspan="3">
  <h2>IPsec Peers Tunnel Configuration:</h2>
  </td></tr><tr><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart IPsec" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />
  </td></tr>
<?php
  putHtml('<tr><td class="dialogText" style="text-align: center;" colspan="3">');
  putHtml('Log Level:');
  if (($log_level = getVARdef($vars, 'IPSEC_LOGLEVEL')) === '') {
    $log_level = 'info';
  }
  putHtml('<select name="log_level">');
  foreach ($log_level_menu as $key => $value) {
    $sel = ($log_level === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');
  putHtml('</table>');

  if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $n = arrayCount($db['data']);
    if ($id < $n && $id >= 0) {
      $ldb = $db['data'][$id];
    }
  }
  if (is_null($ldb)) {
    $ldb['local_host'] = '0.0.0.0';
    $ldb['local_net'] = '';
    $ldb['remote_host'] = '';
    $ldb['remote_net'] = '';
    $ldb['key'] = '';
    $ldb['p1_encrypt'] = 'aes 128';
    $ldb['p1_hash'] = 'sha1';
    $ldb['p1_dhgroup'] = 'modp1024';
    $ldb['p2_encrypt'] = 'aes 128';
    $ldb['p2_auth'] = 'hmac_sha1';
    $ldb['p2_pfsgroup'] = 'modp1024';
    $ldb['nat_t'] = '';
    $ldb['auto_establish'] = 'none';
    $ldb['p1_lifetime'] = 'none';
    $ldb['p2_lifetime'] = '3600';
  }
  $openssl = ipsecSETUP($ldb['remote_host']);

  putHtml('<table width="100%" class="datatable">');
  putHtml('<tr>');

  if (($n = arrayCount($db['data'])) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Remote-Host", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Remote-Net", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Local-Host", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Local-Net", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "NAT-T", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td>', '<a href="'.$myself.'?id='.$i.'" class="actionText">'.$db['data'][$i]['remote_host'].'</a>', '</td>';
      echo '<td>', $db['data'][$i]['remote_net'], '</td>';
      echo '<td>', $db['data'][$i]['local_host'], '</td>';
      echo '<td>', $db['data'][$i]['local_net'], '</td>';
      echo '<td>', $nat_t_menu[$db['data'][$i]['nat_t']], '</td>';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="', $i, '" />', '</td>';
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td class="dialogText" style="text-align: right; font-weight: bold;">Phase 1:</td>';
      echo '<td colspan="5">', getPhase1str($db['data'][$i]['p1_encrypt'], $db['data'][$i]['p1_hash'],
                                            $db['data'][$i]['p1_dhgroup'], $db['data'][$i]['p1_lifetime']), '</td>';
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td class="dialogText" style="text-align: right; font-weight: bold;">Phase 2:</td>';
      echo '<td colspan="5">', getPhase2str($db['data'][$i]['p2_encrypt'], $db['data'][$i]['p2_auth'],
                                            $db['data'][$i]['p2_pfsgroup'], $db['data'][$i]['p2_lifetime']), '</td>';
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td class="dialogText" style="text-align: right; font-weight: bold;">Identity:</td>';
      echo '<td colspan="5">', getIDstr($db['data'][$i]['key'], $db['data'][$i]['remote_host']), '</td>';
      if ($db['data'][$i]['auto_establish'] !== '' && $db['data'][$i]['auto_establish'] !== 'none') {
        putHtml("</tr>");
        echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
        echo '<td class="dialogText" style="text-align: right; font-weight: bold;">Ping:</td>';
        echo '<td colspan="5">', 'Auto-Establish-IP='.$db['data'][$i]['auto_establish'], '</td>';
      }
    }
  } else {
    echo '<td style="text-align: center;">No IPsec Peers are defined.', '</td>';
  }

  putHtml('</tr>');
  putHtml('</table>');

  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr class="dtrow0"><td width="100">&nbsp;</td><td width="100">&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td width="100">&nbsp;</td><td width="100">&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Tunnel Network:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td class="dialogText" style="text-align: right;" colspan="3">');
  putHtml('Remote-Host:<input type="text" size="32" maxlength="64" name="remote_host" value="'.$ldb['remote_host'].'" />');
  putHtml('</td><td class="dialogText" style="text-align: right;" colspan="3">');
  putHtml('Remote-Net:<input type="text" size="32" maxlength="64" name="remote_net" value="'.$ldb['remote_net'].'" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td class="dialogText" style="text-align: right;" colspan="3">');
  putHtml('Local-Host:<input type="text" size="32" maxlength="64" name="local_host" value="'.$ldb['local_host'].'" />');
  putHtml('</td><td class="dialogText" style="text-align: right;" colspan="3">');
  putHtml('Local-Net:<input type="text" size="32" maxlength="64" name="local_net" value="'.$ldb['local_net'].'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td class="dialogText" style="text-align: center;" colspan="3">');
  putHtml('NAT Traversal:');
  $nat_t = $ldb['nat_t'];
  putHtml('<select name="nat_t">');
  foreach ($nat_t_menu as $key => $value) {
    $sel = ($nat_t === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td><td class="dialogText" style="text-align: right;" colspan="3">');
  $value = ($ldb['auto_establish'] === '') ? 'none' : $ldb['auto_establish'];
  putHtml('Auto-Establish-IP:<input type="text" size="32" maxlength="64" name="auto_establish" value="'.$value.'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Phase 1 - Authentication:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Encryption:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $p1_encrypt = $ldb['p1_encrypt'];
  putHtml('<select name="p1_encrypt">');
  foreach ($p1_encrypt_menu as $key => $value) {
    $sel = ($p1_encrypt === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Hash:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $p1_hash = $ldb['p1_hash'];
  putHtml('<select name="p1_hash">');
  foreach ($p1_hash_menu as $key => $value) {
    $sel = ($p1_hash === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('DH Group:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  $p1_dhgroup = $ldb['p1_dhgroup'];
  putHtml('<select name="p1_dhgroup">');
  foreach ($p1_dhgroup_menu as $key => $value) {
    $sel = ($p1_dhgroup === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  $value = ($ldb['p1_lifetime'] === '') ? 'none' : $ldb['p1_lifetime'];
  putHtml('Lifetime:<input type="text" size="8" maxlength="16" name="p1_lifetime" value="'.$value.'" />');
  putHtml('secs');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Phase 2 - SA/Key Exchange:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Encryption:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $p2_encrypt = $ldb['p2_encrypt'];
  foreach ($p1_encrypt_menu as $key => $value) {
    $sel = inStringList($key, $p2_encrypt, ',') ? ' checked="checked"' : '';
    putHtml('<input type="checkbox" name="p2_encrypt[]" value="'.$key.'"'.$sel.' />'.$value);
  }
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Authentication:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $p2_auth = $ldb['p2_auth'];
  foreach ($p2_auth_menu as $key => $value) {
    $sel = inStringList($key, $p2_auth, ',') ? ' checked="checked"' : '';
    putHtml('<input type="checkbox" name="p2_auth[]" value="'.$key.'"'.$sel.' />'.$value);
  }
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('PFS Group:');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  $p2_pfsgroup = $ldb['p2_pfsgroup'];
  putHtml('<select name="p2_pfsgroup">');
  foreach ($p2_pfsgroup_menu as $key => $value) {
    $sel = ($p2_pfsgroup === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td><td style="text-align: left;" colspan="2">');
  $value = ($ldb['p2_lifetime'] === '') ? '3600' : $ldb['p2_lifetime'];
  putHtml('Lifetime:<input type="text" size="8" maxlength="16" name="p2_lifetime" value="'.$value.'" />');
  putHtml('secs');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Tunnel Identity:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Method:');
  putHtml('</td><td class="dialogText" style="text-align: left;" colspan="4">');
  putHtml('<select name="method">');
  foreach ($method_menu as $key => $value) {
    $sel = ($key === 'rsa' && $ldb['key'] === '' && $openssl !== FALSE) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('<input type="password" size="44" maxlength="128" name="key" value="'.$ldb['key'].'" />');
  putHtml('</td></tr>');
  if ($openssl !== FALSE) {
    $valid = opensslIPSECis_valid($openssl);
    $color = $valid ? ' color: green;' : ' color: orange;';
    putHtml('<tr class="dtrow1"><td style="text-align: center;'.$color.'" colspan="6">');
    if ($valid) {
      putHtml('Peer Certificate and Key are defined.');
    } else {
      putHtml('Peer Certificate and Key are not defined.');
    }
    putHtml('</td></tr>');
  }
  putHtml('</table>');

  putHtml('</form>');

  if ($openssl !== FALSE) {
    putHtml('<form method="post" action="'.$myself.'" enctype="multipart/form-data">');
    putHtml('<table width="70%" class="datatable">');
    putHtml('<tr><td class="dialogText" style="text-align: left;" colspan="6">');
    putHtml('<strong>Peer Certificate and Key:</strong>');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow1"><td width="70" style="text-align: right;">');
    putHtml('<input type="hidden" name="MAX_FILE_SIZE" value="10000" />');
    putHtml('<input type="hidden" name="peer" value="'.$ldb['remote_host'].'" />');
    putHtml('CA:');
    putHtml('</td><td style="text-align: left;">');
    putHtml(getCREDinfo($openssl, 'ca_crt', $str).'<input type="file" name="creds[ca_crt]" />');
    putHtml('</td></tr><tr class="dtrow1"><td style="text-align: right;">');
    putHtml('Cert:');
    putHtml('</td><td style="text-align: left;">');
    putHtml(getCREDinfo($openssl, 'peer_crt', $CName).'<input type="file" name="creds[peer_crt]" />');
    putHtml('</td></tr><tr class="dtrow1"><td style="text-align: right;">');
    putHtml('Key:');
    putHtml('</td><td style="text-align: left;">');
    putHtml(getCREDinfo($openssl, 'peer_key', $str).'<input type="file" name="creds[peer_key]" />');
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
    putHtml('<input type="submit" name="submit" value="Save Peer Credentials" />');
    putHtml('</td></tr>');
    putHtml('</table>');
    putHtml('</form>');
  }

  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
