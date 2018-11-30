<?php

// Copyright (C) 2017-2018 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// wireguard.php for AstLinux
// 11-07-2017
// 11-12-2018, Add Mobile Client defaults
// 11-15-2018, Add Mobile Client credentials
//
// System location of /mnt/kd/rc.conf.d directory
$WIREGUARDCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.wireguard.conf file
$WIREGUARDCONFFILE = '/mnt/kd/rc.conf.d/gui.wireguard.conf';
// WireGuard runtime lock file
$WG_LOCK_FILE = '/var/lock/wireguard.lock';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$wg_if_menu = array (
  'wg0' => 'wg0'
);

$wg_redirect_ports_menu = array (
  ''  => 'none',
  '443' => 'UDP/443',
  '80,443' => 'UDP/80,443',
  '443,4500' => 'UDP/443,4500',
  '443,5223' => 'UDP/443,5223',
  '4500,5223' => 'UDP/4500,5223',
  '80,443,4500' => 'UDP/80,443,4500',
  '80,443,5223' => 'UDP/80,443,5223',
  '80,443,4500,5223' => 'UDP/80,443,4500,5223'
);

$wg_peer_isolation_menu = array (
  'no'  => 'Pass Peer->Peer traffic',
  'yes' => 'Deny Peer->Peer traffic'
);

$wg_client_routing_menu = array (
  'split' => 'Split: Route only to 1st LAN over VPN',
  'full' => 'Full: Route all client traffic over VPN'
);

if (is_file($WIREGUARDCONFFILE)) {
  $db = parseRCconf($WIREGUARDCONFFILE);
} else {
  $db = NULL;
}

// Function: wireguardREADMEstr()
//
function wireguardREADMEstr($clientName) {

  $readme = "Mobile Client \"$clientName\" Credentials for WireGuard\n\n";
  $readme .= "$clientName.conf - The Mobile Client's WireGuard configuration in plain text.\n\n";
  $readme .= "$clientName.png - A PNG graphics file containing a QR code of the $clientName.conf text.\n";
  $readme .= "Scanning the QR code with your mobile device is a secure method to import the credentials.\n\n";
  $readme .= "Note: Files '$clientName.conf' and '$clientName.png' and not encrypted and must be kept secure.\n\n";
  $readme .= "Note: While the QR code PNG file looks obfuscated to the human eye, keep it secure.\n\n";

  return($readme);
}

// Function: wireguardGENconf
//
function wireguardGENconf($client, $conf) {

  $cmd = '/usr/sbin/wireguard-mobile-client show remote '.$client;
  shell($cmd.' >'.$conf.' 2>/dev/null', $status);
  if ($status == 0) {
    return(TRUE);
  }
  return(FALSE);
}

// Function: wireguardGENpng
//
function wireguardGENpng($conf, $png) {

  if (is_file('/usr/bin/qrencode')) {
    $cmd = '/usr/bin/qrencode -o '.$png.' < '.$conf;
    shell($cmd.' >/dev/null 2>/dev/null', $status);
    if ($status == 0) {
      return(TRUE);
    }
  }
  return(FALSE);
}

// Function: add_client
//
function add_client($client) {

  $result = 7;

  $cmd = '/usr/sbin/wireguard-mobile-client add '.$client;
  shell($cmd.' >/dev/null 2>/dev/null', $status);
  if ($status == 0) {
    $result = 21;
  }
  return($result);
}

// Function: remove_client
//
function remove_client($delete) {

  $result = 0;

  foreach ($delete as $client) {
    $cmd = '/usr/sbin/wireguard-mobile-client remove '.$client;
    shell($cmd.' >/dev/null 2>/dev/null', $status);
    if ($status == 0) {
      $result = 20;
    } else {
      $result = 6;
      break;
    }
  }
  return($result);
}

// Function: wireguard_SETUP
//
function wireguard_SETUP($if) {

  $wg['config_dir'] = '/mnt/kd/wireguard';
  $wg['clients_peer_dir'] = $wg['config_dir'].'/peer/'.$if.'.clients';
  $wg['clients_keys_dir'] = $wg['config_dir'].'/keys/'.$if.'.clients';

  $wg['client_cmd'] = '/usr/sbin/wireguard-mobile-client';

  return($wg);
}

// Function: wireguardGETclients
//
function wireguardGETclients($wg) {

  $client_list = array();

  if (is_dir($wg['clients_peer_dir'])) {
    foreach (glob($wg['clients_peer_dir'].'/*.peer') as $peer) {
      if (is_file($peer)) {
        $client = basename($peer, '.peer');
        if (is_file($wg['clients_keys_dir'].'/'.$client.'.privatekey')) {
          $client_list[] = $client;
        }
      }
    }
  }
  return($client_list);
}

// Function: saveWIREGUARDsettings
//
function saveWIREGUARDsettings($conf_dir, $conf_file) {
  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.wireguard.conf - start ###\n###\n");

  $value = 'WIREGUARD_IP="'.tuq($_POST['wireguard_ip']).'"';
  fwrite($fp, "### WireGuard VPN IP\n".$value."\n");

  $value = 'WIREGUARD_NM="'.tuq($_POST['wireguard_nm']).'"';
  fwrite($fp, "### WireGuard VPN NM\n".$value."\n");

  $value = tuq($_POST['wireguard_ipv6']);
  if ($value !== '' && strpos($value, '/') === FALSE) {
    $value="$value/64";
  }
  $value = 'WIREGUARD_IPV6="'.$value.'"';
  fwrite($fp, "### WireGuard VPN IPv6/nn\n".$value."\n");

  $value = 'WIREGUARD_ROUTES="'.tuq($_POST['wireguard_routes']).'"';
  fwrite($fp, "### WireGuard VPN Routes\n".$value."\n");

  $value = 'WIREGUARD_AUTO_ROUTES="'.(isset($_POST['wireguard_auto_routes']) ? 'yes' : 'no').'"';
  fwrite($fp, "### WireGuard Auto Routes\n".$value."\n");

  $value = 'WIREGUARD_DNS_UPDATE="'.(isset($_POST['wireguard_dns_update']) ? 'yes' : 'no').'"';
  fwrite($fp, "### Continually Update DNS Endpoints\n".$value."\n");

  $value = 'WIREGUARD_IF="'.$_POST['wireguard_if'].'"';
  fwrite($fp, "### WireGuard interface\n".$value."\n");

  $value = tuq($_POST['wireguard_mtu']);
  if ($value === 'default' || $value < 100 || $value > 1500) {
    $value = '';
  }
  $value = 'WIREGUARD_MTU="'.$value.'"';
  fwrite($fp, "### WireGuard interface MTU\n".$value."\n");

  $value = 'WIREGUARD_LISTEN_PORT="'.tuq($_POST['wireguard_listen_port']).'"';
  fwrite($fp, "### UDP Listen Port\n".$value."\n");

  $value = 'WIREGUARD_TUNNEL_HOSTS="'.tuq($_POST['wireguard_tunnel_hosts']).'"';
  fwrite($fp, "### Allowed External Hosts\n".$value."\n");

  $value = 'WIREGUARD_REDIRECT_PORTS="'.$_POST['redirect_ports'].'"';
  fwrite($fp, "### Redirect Ports\n".$value."\n");

  $value = 'WIREGUARD_PEER_ISOLATION="'.$_POST['isolation'].'"';
  fwrite($fp, "### Peer Isolation\n".$value."\n");

  $value = tuq($_POST['wireguard_hostname']);
  if ($value !== '' && strpos($value, ':') !== FALSE) {
    $value = '['.trim($value, '[]').']';  // [ipv6]
  }
  $value = 'WIREGUARD_HOSTNAME="'.$value.'"';
  fwrite($fp, "### Mobile Client Server\n".$value."\n");

  $value = 'WIREGUARD_CLIENT_ROUTING="'.$_POST['wireguard_client_routing'].'"';
  fwrite($fp, "### Mobile Client Routing\n".$value."\n");

  fwrite($fp, "### gui.wireguard.conf - end ###\n");
  fclose($fp);

  return($result);
}

if (($wg_if = getVARdef($db, 'WIREGUARD_IF')) === '') {
  $wg_if = 'wg0';
}
$wg = wireguard_SETUP($wg_if);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $result = saveWIREGUARDsettings($WIREGUARDCONFDIR, $WIREGUARDCONFFILE);
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('wireguard', 10, $result, 'init');
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_edit_wireguard'])) {
    $result = saveWIREGUARDsettings($WIREGUARDCONFDIR, $WIREGUARDCONFFILE);
    if (is_writable($file = '/mnt/kd/wireguard/peer/'.$_POST['wireguard_if'].'.peer')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    } else {
      $result = 5;
    }
  } elseif (isset($_POST['submit_delete_client'])) {
    saveWIREGUARDsettings($WIREGUARDCONFDIR, $WIREGUARDCONFFILE);
    $delete = $_POST['delete'];
    if (count($delete) > 0) {
      $result = remove_client($delete);
    } else {
      $result = 0;
    }
  } elseif (isset($_POST['submit_new_client'])) {
    saveWIREGUARDsettings($WIREGUARDCONFDIR, $WIREGUARDCONFFILE);
    if (($value = tuq($_POST['new_client'])) !== '') {
      if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]*$/', $value)) {
        if (! is_file($wg['clients_peer_dir'].'/'.$value.'.peer')) {
          $result = add_client($value);
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
} elseif (isset($_GET['client'])) {
  $result = 4;
  $client_list = wireguardGETclients($wg);
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
      $tmp_conf = tempnam("/tmp", "CONF_");
      $tmp_png = tempnam("/tmp", "PNG_");
      if (wireguardGENconf($value, $tmp_conf)) {
        $zip->addFile($tmp_conf, $value.'/'.$value.'.conf');
        if (wireguardGENpng($tmp_conf, $tmp_png)) {
          $zip->addFile($tmp_png, $value.'/'.$value.'.png');
        }
      }
      $zip->addFromString($value.'/README.txt', wireguardREADMEstr($value));
      $zip->close();
      @unlink($tmp_conf);
      @unlink($tmp_png);

      header('Content-Type: application/zip');
      header('Content-Disposition: attachment; filename="wg-credentials-'.$value.'.zip"');
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
    } elseif ($result == 4) {
      putHtml('<p style="color: red;">File Not Found.</p>');
    } elseif ($result == 5) {
      putHtml('<p style="color: red;">Peer config file not found.</p>');
    } elseif ($result == 6) {
      putHtml('<p style="color: red;">Mobile Client could not be deleted.</p>');
    } elseif ($result == 7) {
      putHtml('<p style="color: red;">Mobile Client could not be added.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">WireGuard VPN'.statusPROCESS('wireguard').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart VPN" to apply any changed settings.</p>');
    } elseif ($result == 20) {
      putHtml('<p style="color: green;">Mobile Client(s) successfully deleted, WireGuard config has been updated in realtime.</p>');
    } elseif ($result == 21) {
      putHtml('<p style="color: green;">Mobile Client successfully added, WireGuard config has been updated in realtime.</p>');
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
  <tr><td style="text-align: center;" colspan="3">
  <h2>WireGuard VPN Configuration:</h2>
  </td></tr><tr><td width="175" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart VPN" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td><td class="dialogText" style="text-align: right;">
  <input type="submit" value="Edit Peer Config" name="submit_edit_wireguard" class="button" />
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="110">&nbsp;</td><td width="50">&nbsp;</td><td width="150">&nbsp;</td><td>&nbsp;</td><td width="100">&nbsp;</td><td width="120">&nbsp;</td></tr>
<?php
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Tunnel Options:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('IPv4 Address:</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'WIREGUARD_IP');
  putHtml('<input type="text" size="16" maxlength="15" value="'.$value.'" name="wireguard_ip" />');
  putHtml('<i>(Required)</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('IPv4 NetMask:</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'WIREGUARD_NM')) === '') {
    $value = '255.255.255.0';
  }
  putHtml('<input type="text" size="16" maxlength="15" value="'.$value.'" name="wireguard_nm" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('IPv6/nn Address:</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'WIREGUARD_IPV6');
  putHtml('<input type="text" size="48" maxlength="43" value="'.$value.'" name="wireguard_ipv6" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('IPv4/IPv6 Routes:</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'WIREGUARD_ROUTES');
  putHtml('<input type="text" size="48" maxlength="256" value="'.$value.'" name="wireguard_routes" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Automatic Routes:</td><td style="text-align: left;" colspan="4">');
  $sel = (getVARdef($db, 'WIREGUARD_AUTO_ROUTES') !== 'no') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="wireguard_auto_routes" name="wireguard_auto_routes"'.$sel.' />');
  putHtml('Create routes for Allowed IP\'s for all peers');
  putHtml('<br /><br />');
  putHtml('<i>(Ignored if "IPv4/IPv6 Routes" is defined)</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('DNS Update:</td><td style="text-align: left;" colspan="4">');
  $sel = (getVARdef($db, 'WIREGUARD_DNS_UPDATE') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="wireguard_dns_update" name="wireguard_dns_update"'.$sel.' />');
  putHtml('Continually Update DNS Endpoints for peers');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Interface Options:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Interface Device:</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="wireguard_if">');
  foreach ($wg_if_menu as $key => $value) {
    $sel = ($wg_if === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Interface MTU:</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'WIREGUARD_MTU')) === '') {
    $value = 'default';
  }
  putHtml('<input type="text" size="8" maxlength="8" value="'.$value.'" name="wireguard_mtu" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('UDP Listen Port:</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'WIREGUARD_LISTEN_PORT')) === '') {
    $value = '51820';
  }
  putHtml('<input type="text" size="8" maxlength="5" value="'.$value.'" name="wireguard_listen_port" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Firewall Options:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('External Hosts:</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'WIREGUARD_TUNNEL_HOSTS')) === '') {
    $value = '0/0';
  }
  putHtml('<input type="text" size="48" maxlength="256" value="'.$value.'" name="wireguard_tunnel_hosts" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Redirect Ports:</td><td style="text-align: left;" colspan="4">');
  $redirect_ports = getVARdef($db, 'WIREGUARD_REDIRECT_PORTS');
  putHtml('<select name="redirect_ports">');
  foreach ($wg_redirect_ports_menu as $key => $value) {
    $sel = ($redirect_ports === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('to UDP Listen Port');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Peer Isolation:</td><td style="text-align: left;" colspan="4">');
  $isolation = getVARdef($db, 'WIREGUARD_PEER_ISOLATION');
  putHtml('<select name="isolation">');
  foreach ($wg_peer_isolation_menu as $key => $value) {
    $sel = ($isolation === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Mobile Client Defaults:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Server Hostname:</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'WIREGUARD_HOSTNAME')) === '') {
    $value = get_HOSTNAME_DOMAIN();
  }
  putHtml('<input type="text" size="48" maxlength="256" value="'.$value.'" name="wireguard_hostname" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Client Routing:</td><td style="text-align: left;" colspan="4">');
  if (($wg_client_routing = getVARdef($db, 'WIREGUARD_CLIENT_ROUTING')) === '') {
    $wg_client_routing = 'split';
  }
  putHtml('<select name="wireguard_client_routing">');
  foreach ($wg_client_routing_menu as $key => $value) {
    $sel = ($wg_client_routing === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');


if (is_file($WG_LOCK_FILE) && trim(@file_get_contents($WG_LOCK_FILE)) === $wg_if) {
  if (is_file($wg['client_cmd'])) {
    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
    putHtml('<strong>Mobile Client Credentials:</strong>');
    putHtml('</td></tr>');
    putHtml('<tr><td style="text-align: right;" colspan="2">');
    putHtml('Create New Client:</td><td style="text-align: left;" colspan="3">');
    putHtml('<input type="text" size="24" maxlength="32" value="" name="new_client" />');
    putHtml('<input type="submit" value="New Client" name="submit_new_client" />');
    putHtml('</td><td style="text-align: center;">');
    putHtml('<input type="submit" value="Delete Checked" name="submit_delete_client" />');
    putHtml('</td></tr>');

    putHtml('<tr><td colspan="6"><center>');
    $data = wireguardGETclients($wg);
    putHtml('<table width="85%" class="datatable">');
    putHtml("<tr>");

    if (($n = count($data)) > 0) {
      echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Client Name", "</td>";
      echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Configuration", "</td>";
      echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Credentials", "</td>";
      echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
      for ($i = 0; $i < $n; $i++) {
        putHtml("</tr>");
        echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
        echo '<td style="text-align: left;">', $data[$i], '</td>';
        echo '<td style="text-align: center;">', '<a href="/admin/edit.php?file='.$wg['clients_peer_dir'].'/'.$data[$i].'.peer" class="actionText">Edit Peer</a></td>';
        echo '<td style="text-align: center;">', '<a href="'.$myself.'?client='.$data[$i].'" class="actionText">Download</a></td>';
        echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="'.$data[$i].'" />', '</td>';
      }
    } else {
      echo '<td style="color: orange; text-align: center;">No Mobile Client Credentials.', '</td>';
    }

    putHtml("</tr>");
    putHtml("</table>");
    putHtml('</center></td></tr>');

    putHtml('<tr><td style="padding-top: 0px; padding-bottom: 0px;" colspan="6">');
    putHtml('&nbsp;');
    putHtml('</td></tr>');
  }
  if (($public_key = trim(shell_exec("/usr/bin/wg show '$wg_if' public-key 2>/dev/null"))) !== '') {
    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
    putHtml('<strong>This Peer\'s Public Key:</strong>');
    putHtml('</td></tr>');

    putHtml('<tr class="dtrow1"><td class="dialogText" style="text-align: center; padding-top: 0px; padding-bottom: 0px;" colspan="6">');
    putHtml("<pre>$public_key</pre>");
    putHtml('</td></tr>');
  }
}

  putHtml('</table>');
  putHtml('</form>');
  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
