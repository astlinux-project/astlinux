<?php

// Copyright (C) 2014-2021 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// failover.php for AstLinux
// 11-06-2014
// 11-01-2015, Added DHCPv6 option
// 10-07-2021, Added CAKE traffic shaping support
//
// System location of rc.conf file
$CONFFILE = '/etc/rc.conf';
// System location of /mnt/kd/rc.conf.d directory
$FAILOVERCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.failover.conf file
$FAILOVERCONFFILE = '/mnt/kd/rc.conf.d/gui.failover.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$verbosity_menu = array (
  '3' => 'error',
  '6' => 'info',
  '9' => 'debug'
);

$test_interval_menu = array (
  '5' => '5 secs',
  '10' => '10 secs',
  '15' => '15 secs',
  '20' => '20 secs',
  '30' => '30 secs',
  '40' => '40 secs',
  '60' => '60 secs',
  '90' => '90 secs',
  '120' => '120 secs'
);

$max_latency_menu = array (
  '100' => '0.1 sec',
  '200' => '0.2 sec',
  '300' => '0.3 sec',
  '400' => '0.4 sec',
  '500' => '0.5 sec',
  '1000' => '1.0 sec',
  '1500' => '1.5 secs',
  '2000' => '2.0 secs',
  '3000' => '3.0 secs'
);

$threshold_menu = array (
  '2' => '2 failed',
  '3' => '3 failed',
  '4' => '4 failed',
  '5' => '5 failed',
  '6' => '6 failed',
  '7' => '7 failed',
  '8' => '8 failed'
);

$primary_delay_menu = array (
  '10' => '10 seconds',
  '20' => '20 seconds',
  '30' => '30 seconds',
  '60' => '1 minute',
  '120' => '2 minutes',
  '300' => '5 minutes'
);

$secondary_delay_menu = array (
  '30' => '30 seconds',
  '60' => '1 minute',
  '120' => '2 minutes',
  '300' => '5 minutes',
  '600' => '10 minutes',
  '1200' => '20 minutes',
  '1800' => '30 minutes',
  '3600' => '60 minutes'
);

$cake_llt = array (
  '' => 'disabled',
  'ethernet' => 'Ethernet: ethernet',
  'docsis' => 'Ethernet: docsis',
  'pppoe-ptm' => 'VDSL2: pppoe-ptm',
  'bridged-ptm' => 'VDSL2: bridged-ptm',
  'pppoe-vcmux' => 'ADSL: pppoe-vcmux',
  'pppoe-llcsnap' => 'ADSL: pppoe-llcsnap',
  'bridged-vcmux' => 'ADSL: bridged-vcmux',
  'bridged-llcsnap' => 'ADSL: bridged-llcsnap',
  'conservative' => 'High Overhead: conservative'
);

$cake_ack_filter = array (
  '' => 'disabled',
  'ack-filter' => 'enabled'
);

// Function: saveFAILOVERsettings
//
function saveFAILOVERsettings($conf_dir, $conf_file) {
  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.failover.conf - start ###\n###\n");

  $value = 'WAN_FAILOVER_ENABLE="'.$_POST['enable'].'"';
  fwrite($fp, "### WAN Failover Enable\n".$value."\n");

  $value = 'WAN_FAILOVER_VERBOSITY="'.$_POST['verbosity'].'"';
  fwrite($fp, "### Log Level\n".$value."\n");

  $value = 'WAN_FAILOVER_TARGETS="'.tuq($_POST['targets']).'"';
  fwrite($fp, "### Test Targets\n".$value."\n");

  fwrite($fp, "### Timing and intervals\n");
  $value = 'WAN_FAILOVER_TEST_INTERVAL="'.$_POST['test_interval'].'"';
  fwrite($fp, $value."\n");
  $value = 'WAN_FAILOVER_THRESHOLD="'.$_POST['threshold'].'"';
  fwrite($fp, $value."\n");
  $value = 'WAN_FAILOVER_MAX_LATENCY="'.$_POST['max_latency'].'"';
  fwrite($fp, $value."\n");
  $value = 'WAN_FAILOVER_PRIMARY_DELAY="'.$_POST['primary_delay'].'"';
  fwrite($fp, $value."\n");
  $value = 'WAN_FAILOVER_SECONDARY_DELAY="'.$_POST['secondary_delay'].'"';
  fwrite($fp, $value."\n");

  fwrite($fp, "### Email Notify\n");
  $value = 'WAN_FAILOVER_NOTIFY="'.tuq($_POST['notify']).'"';
  fwrite($fp, $value."\n");
  $value = 'WAN_FAILOVER_NOTIFY_FROM="'.tuq($_POST['notify_from']).'"';
  fwrite($fp, $value."\n");

  fwrite($fp, "### Secondary Gateway\n");
  $value = 'WAN_FAILOVER_SECONDARY_GW="'.tuq($_POST['secondary_gw_ipv4']).'"';
  fwrite($fp, $value."\n");
  $value = 'WAN_FAILOVER_SECONDARY_GWIPV6="'.tuq($_POST['secondary_gw_ipv6']).'"';
  fwrite($fp, $value."\n");

  if ($_POST['ip_type'] === 'dhcp' || $_POST['ip_type'] === 'dhcp-dhcpv6') {
    $value = 'EXT2IP=""';
  } else {
    $value = 'EXT2IP="'.tuq($_POST['static_ip']).'"';
  }
  fwrite($fp, "### Static IPv4\n".$value."\n");

  if ($_POST['ip_type'] === 'dhcp' || $_POST['ip_type'] === 'dhcp-dhcpv6') {
    $value = 'EXT2NM=""';
  } else {
    $value = 'EXT2NM="'.tuq($_POST['mask_ip']).'"';
  }
  fwrite($fp, "### Static IPv4 NetMask\n".$value."\n");

  if ($_POST['ip_type'] === 'dhcp' || $_POST['ip_type'] === 'dhcp-dhcpv6') {
    $value = 'EXT2GW=""';
  } else {
    $value = 'EXT2GW="'.tuq($_POST['gateway_ip']).'"';
  }
  fwrite($fp, "### Static IPv4 Gateway\n".$value."\n");

  if ($_POST['ip_type'] === 'dhcp-dhcpv6' || $_POST['ip_type'] === 'static-dhcpv6') {
    $value = 'EXT2DHCPV6_CLIENT_ENABLE="yes"';
  } else {
    $value = 'EXT2DHCPV6_CLIENT_ENABLE="no"';
  }
  fwrite($fp, "### DHCPv6\n".$value."\n");

  $value = tuq($_POST['static_ipv6']);
  if ($value !== '' && strpos($value, '/') === FALSE) {
    $value="$value/64";
  }
  $value = 'EXT2IPV6="'.$value.'"';
  fwrite($fp, "### Static IPv6\n".$value."\n");

  $value = tuq($_POST['gateway_ipv6']);
  if (($pos = strpos($value, '/')) !== FALSE) {
    $value=substr($value, 0, $pos);
  }
  $value = 'EXT2GWIPV6="'.$value.'"';
  fwrite($fp, "### Static IPv6 Gateway\n".$value."\n");

  $value = 'EXT2ROUTES="'.tuq($_POST['routes_ipv4']).'"';
  fwrite($fp, "### IPv4 Destination Routes\n".$value."\n");

  $value = 'EXT2ROUTESIPV6="'.tuq($_POST['routes_ipv6']).'"';
  fwrite($fp, "### IPv6 Destination Routes\n".$value."\n");

  fwrite($fp, "### Traffic Shaping\n");
  $value = 'EXT2SHAPE="'.$_POST['shape'].'"';
  fwrite($fp, $value."\n");
  $value = 'EXT2SHAPE_UP="'.tuq($_POST['shape_up']).'"';
  fwrite($fp, $value."\n");
  $value = 'EXT2SHAPE_TUNE_CAKE="'.trim($_POST['cake_llt'].' '.$_POST['cake_ack_filter']).'"';
  fwrite($fp, $value."\n");

  fwrite($fp, "### gui.failover.conf - end ###\n");
  fclose($fp);

  return($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $result = saveFAILOVERsettings($FAILOVERCONFDIR, $FAILOVERCONFFILE);
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('failover', 10, $result, 'init');
    } else {
      $result = 2;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  if (is_file($FAILOVERCONFFILE)) {
    $db = parseRCconf($FAILOVERCONFFILE);
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
      putHtml('<p style="color: green;">WAN Failover'.statusPROCESS('failover').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart Failover" to apply WAN Failover settings, a "Reboot" is required for External Failover Interface changes.</p>');
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
  <h2>WAN Failover Configuration:</h2>
  </td></tr><tr><td width="240" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart Failover" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="60">&nbsp;</td><td width="100">&nbsp;</td><td width="100">&nbsp;</td><td>&nbsp;</td><td width="100">&nbsp;</td><td width="80">&nbsp;</td></tr>
<?php
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>WAN Failover:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Failover:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="enable">');
  $value = getVARdef($db, 'WAN_FAILOVER_ENABLE', $cur_db);
  putHtml('<option value="no">disabled</option>');
  $sel = ($value === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Log Level:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="verbosity">');
  if (($verbosity = getVARdef($db, 'WAN_FAILOVER_VERBOSITY', $cur_db)) === '') {
    $verbosity = '6';
  }
  foreach ($verbosity_menu as $key => $value) {
    $sel = ($verbosity === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Target IPv4 Hosts:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'WAN_FAILOVER_TARGETS', $cur_db)) === '') {
    $value = '8.8.4.4 4.2.2.3';
  }
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="targets" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Test Interval:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="test_interval">');
  if (($test_interval = getVARdef($db, 'WAN_FAILOVER_TEST_INTERVAL', $cur_db)) === '') {
    $test_interval = '20';
  }
  foreach ($test_interval_menu as $key => $value) {
    $sel = ($test_interval === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('between tests');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Threshold:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="threshold">');
  if (($threshold = getVARdef($db, 'WAN_FAILOVER_THRESHOLD', $cur_db)) === '') {
    $threshold = '3';
  }
  foreach ($threshold_menu as $key => $value) {
    $sel = ($threshold === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('tests for Failover');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Max Latency:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="max_latency">');
  if (($max_latency = getVARdef($db, 'WAN_FAILOVER_MAX_LATENCY', $cur_db)) === '') {
    $max_latency = '1000';
  }
  foreach ($max_latency_menu as $key => $value) {
    $sel = ($max_latency === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('per target host');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Primary Delay:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="primary_delay">');
  if (($primary_delay = getVARdef($db, 'WAN_FAILOVER_PRIMARY_DELAY', $cur_db)) === '') {
    $primary_delay = '60';
  }
  foreach ($primary_delay_menu as $key => $value) {
    $sel = ($primary_delay === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('following Failback');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Secondary Delay:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="secondary_delay">');
  if (($secondary_delay = getVARdef($db, 'WAN_FAILOVER_SECONDARY_DELAY', $cur_db)) === '') {
    $secondary_delay = '600';
  }
  foreach ($secondary_delay_menu as $key => $value) {
    $sel = ($secondary_delay === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('following Failover');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Notify Email Addresses<br />To:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'WAN_FAILOVER_NOTIFY', $cur_db);
  putHtml('<input type="text" size="48" maxlength="256" value="'.$value.'" name="notify" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Notify Email Address<br />From:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'WAN_FAILOVER_NOTIFY_FROM', $cur_db);
  putHtml('<input type="text" size="48" maxlength="128" value="'.$value.'" name="notify_from" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="color: orange; text-align: center;" colspan="6">');
  putHtml('Note: Leave the next two fields empty to use the<br />External Failover Interface (default).');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Secondary Gateway IPv4:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'WAN_FAILOVER_SECONDARY_GW', $cur_db);
  putHtml('<input type="text" size="18" maxlength="15" value="'.$value.'" name="secondary_gw_ipv4" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Secondary Gateway IPv6:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'WAN_FAILOVER_SECONDARY_GWIPV6', $cur_db);
  putHtml('<input type="text" size="38" maxlength="39" value="'.$value.'" name="secondary_gw_ipv6" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>External Failover Interface:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Connection Type:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="ip_type">');
  putHtml('<option value="dhcp">DHCP</option>');
  $sel = (getVARdef($db, 'EXT2IP', $cur_db) === '' && getVARdef($db, 'EXT2DHCPV6_CLIENT_ENABLE', $cur_db) === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="dhcp-dhcpv6"'.$sel.'>DHCP/DHCPv6</option>');
  $sel = (getVARdef($db, 'EXT2IP', $cur_db) !== '' && getVARdef($db, 'EXT2DHCPV6_CLIENT_ENABLE', $cur_db) !== 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="static"'.$sel.'>Static IP</option>');
  $sel = (getVARdef($db, 'EXT2IP', $cur_db) !== '' && getVARdef($db, 'EXT2DHCPV6_CLIENT_ENABLE', $cur_db) === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="static-dhcpv6"'.$sel.'>Static IPv4/DHCPv6</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Static IPv4:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'EXT2IP', $cur_db);
  putHtml('<input type="text" size="18" maxlength="15" value="'.$value.'" name="static_ip" />');
  putHtml('<i>(IPv4 cleared for DHCP)</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('IPv4 NetMask:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'EXT2NM', $cur_db);
  putHtml('<input type="text" size="18" maxlength="15" value="'.$value.'" name="mask_ip" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('IPv4 Gateway:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'EXT2GW', $cur_db);
  putHtml('<input type="text" size="18" maxlength="15" value="'.$value.'" name="gateway_ip" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Static IPv6/nn:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'EXT2IPV6', $cur_db);
  putHtml('<input type="text" size="38" maxlength="43" value="'.$value.'" name="static_ipv6" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('IPv6 Gateway:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'EXT2GWIPV6', $cur_db);
  putHtml('<input type="text" size="38" maxlength="39" value="'.$value.'" name="gateway_ipv6" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>External Failover Destination Routes:</strong>');
  putHtml('<i>(1.2.3.4 9.9.9.0/30)</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('IPv4 Routes:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'EXT2ROUTES', $cur_db);
  putHtml('<input type="text" size="48" maxlength="256" value="'.$value.'" name="routes_ipv4" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('IPv6 Routes:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = getVARdef($db, 'EXT2ROUTESIPV6', $cur_db);
  putHtml('<input type="text" size="48" maxlength="256" value="'.$value.'" name="routes_ipv6" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>External Failover Traffic Shaper:</strong>');
  putHtml('<i>(egress)</i>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Traffic Shaping:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="shape">');
  $value = getVARdef($db, 'EXT2SHAPE', $cur_db);
  putHtml('<option value="no">disabled</option>');
  $sel = ($value === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Uplink Speed:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'EXT2SHAPE_UP', $cur_db)) === '') {
    $value = '10000';
  }
  putHtml('<input type="text" size="8" maxlength="7" value="'.$value.'" name="shape_up" />');
  putHtml('K bits-per-second');
  putHtml('</td></tr>');

  $tune_cake = getVARdef($db, 'EXT2SHAPE_TUNE_CAKE', $cur_db);
  $tune_cake_array = preg_split('/\s+/', $tune_cake, -1, PREG_SPLIT_NO_EMPTY);
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Link Layer Tuning:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="cake_llt">');
  foreach ($cake_llt as $key => $value) {
    $sel = (in_array($key, $tune_cake_array)) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('ACK Filter:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="cake_ack_filter">');
  foreach ($cake_ack_filter as $key => $value) {
    $sel = (in_array($key, $tune_cake_array)) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('</table>');
  putHtml('</form>');

  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
