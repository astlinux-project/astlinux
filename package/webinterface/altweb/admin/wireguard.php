<?php

// Copyright (C) 2017 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// wireguard.php for AstLinux
// 11-07-2017
//
// System location of /mnt/kd/rc.conf.d directory
$WIREGUARDCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.wireguard.conf file
$WIREGUARDCONFFILE = '/mnt/kd/rc.conf.d/gui.wireguard.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$wg_if_menu = array (
  'wg0' => 'wg0'
);

if (is_file($WIREGUARDCONFFILE)) {
  $db = parseRCconf($WIREGUARDCONFFILE);
} else {
  $db = NULL;
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

  $value = 'WIREGUARD_IF="'.$_POST['wireguard_if'].'"';
  fwrite($fp, "### WireGuard interface\n".$value."\n");

  $value = tuq($_POST['wireguard_mtu']);
  if ($value === 'default' || $value < 100 || $value > 1500) {
    $value = '';
  }
  $value = 'WIREGUARD_MTU="'.$value.'"';
  fwrite($fp, "### WireGuard interface MTU\n".$value."\n");

  $value = 'WIREGUARD_TUNNEL_HOSTS="'.tuq($_POST['wireguard_tunnel_hosts']).'"';
  fwrite($fp, "### Allowed External Hosts\n".$value."\n");

  fwrite($fp, "### gui.wireguard.conf - end ###\n");
  fclose($fp);
  
  return($result);
}

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
    if (is_writable($file = '/mnt/kd/wireguard/'.$_POST['wireguard_if'].'.conf')) {
      header('Location: /admin/edit.php?file='.$file);
      exit;
    } else {
      $result = 5;
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
      putHtml('<p style="color: red;">Peer config file not found.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">WireGuard VPN'.statusPROCESS('wireguard').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart VPN" to apply any changed settings.</p>');
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
  </td></tr><tr><td width="160" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart VPN" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td><td class="dialogText" style="text-align: right;">
  <input type="submit" value="Edit Peer Config" name="submit_edit_wireguard" class="button" />
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="120">&nbsp;</td><td width="50">&nbsp;</td><td width="100">&nbsp;</td><td>&nbsp;</td><td width="100">&nbsp;</td><td width="80">&nbsp;</td></tr>
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

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Interface Options:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Interface Device:</td><td style="text-align: left;" colspan="4">');
  if (($wg_if = getVARdef($db, 'WIREGUARD_IF')) === '') {
    $wg_if = 'wg0';
  }
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

  if (($public_key = trim(shell_exec("/usr/bin/wg show '$wg_if' public-key 2>/dev/null"))) !== '') {
    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
    putHtml('<strong>This Peer\'s Public Key:</strong>');
    putHtml('</td></tr>');

    putHtml('<tr class="dtrow1"><td class="dialogText" style="text-align: center; padding-top: 0px; padding-bottom: 0px;" colspan="6">');
    putHtml("<pre>$public_key</pre>");
    putHtml('</td></tr>');
  }

  putHtml('</table>');
  putHtml('</form>');
  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
