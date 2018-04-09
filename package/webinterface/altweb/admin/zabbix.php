<?php

// Copyright (C) 2012 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// zabbix.php for AstLinux
// 08-31-2012
//
// System location of rc.conf file
$CONFFILE = '/etc/rc.conf';
// System location of /mnt/kd/rc.conf.d directory
$ZABBIXCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.zabbix.conf file
$ZABBIXCONFFILE = '/mnt/kd/rc.conf.d/gui.zabbix.conf';
// Zabbix proxy executable location
$ZABBIX_PROXY_EXE = '/usr/bin/zabbix_proxy';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$startagents_menu = array (
  '1' => '1',
  '2' => '2',
  '3' => '3',
  '4' => '4',
  '8' => '8',
  '12' => '12',
  '16' => '16'
);

$debuglevel_menu = array (
  '0' => 'none',
  '1' => 'critical',
  '2' => 'error',
  '3' => 'warning',
  '4' => 'debug'
);

$timeout_menu = array (
  '1' => '1',
  '2' => '2',
  '3' => '3',
  '4' => '4',
  '5' => '5',
  '10' => '10',
  '20' => '20',
  '30' => '30'
);

// Function: saveZABBIXsettings
//
function saveZABBIXsettings($conf_dir, $conf_file) {
  global $ZABBIX_PROXY_EXE;

  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.zabbix.conf - start ###\n###\n");

  $value = 'GUI_ZABBIX_DATA="'.$_POST['zabbix_enabled'].'~'.tuq($_POST['zabbix_server']).'"';
  fwrite($fp, "### GUI Data\n".$value."\n");

  if ($_POST['zabbix_enabled'] == '1') {
    $value = 'ZABBIX_SERVER="'.tuq($_POST['zabbix_server']).'"';
  } else {
    $value = 'ZABBIX_SERVER=""';
  }
  fwrite($fp, "### Server\n".$value."\n");

  $value = 'ZABBIX_SERVER_PORT="'.tuq($_POST['zabbix_server_port']).'"';
  fwrite($fp, "### Server Port\n".$value."\n");

  $value = 'ZABBIX_HOSTNAME="'.tuq($_POST['agent_hostname']).'"';
  fwrite($fp, "### Agent Hostname\n".$value."\n");

  $value = 'ZABBIX_LISTENPORT="'.tuq($_POST['agent_listenport']).'"';
  fwrite($fp, "### Agent Listen Port\n".$value."\n");

  $value = 'ZABBIX_STARTAGENTS="'.$_POST['zabbix_startagents'].'"';
  fwrite($fp, "### StartAgents\n".$value."\n");

  $value = 'ZABBIX_DEBUGLEVEL="'.$_POST['zabbix_debuglevel'].'"';
  fwrite($fp, "### DebugLevel\n".$value."\n");

  $value = 'ZABBIX_TIMEOUT="'.$_POST['zabbix_timeout'].'"';
  fwrite($fp, "### Timeout\n".$value."\n");

if (is_file($ZABBIX_PROXY_EXE)) {

  $value = 'ZABBIX_PROXY="'.$_POST['zabbix_proxy'].'"';
  fwrite($fp, "### Proxy Enable\n".$value."\n");

  $value = 'ZABBIX_PROXY_HOSTNAME="'.tuq($_POST['zabbix_proxy_hostname']).'"';
  fwrite($fp, "### Proxy Hostname\n".$value."\n");

  $value = 'ZABBIX_PROXY_LISTENPORT="'.tuq($_POST['proxy_listenport']).'"';
  fwrite($fp, "### Proxy Listen Port\n".$value."\n");

  $value = 'ZABBIX_PROXY_AGENT="'.$_POST['zabbix_proxy_agent'].'"';
  fwrite($fp, "### Route Agent via Proxy\n".$value."\n");
}

  fwrite($fp, "### gui.zabbix.conf - end ###\n");
  fclose($fp);

  return($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $result = saveZABBIXsettings($ZABBIXCONFDIR, $ZABBIXCONFFILE);
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('zabbix', 10, $result, 'init', 4);
    } else {
      $result = 2;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  if (is_file($ZABBIXCONFFILE)) {
    $db = parseRCconf($ZABBIXCONFFILE);
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
      putHtml('<p style="color: green;">Zabbix Monitoring'.statusPROCESS('zabbix').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart Zabbix" to apply any changed settings.</p>');
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
  <h2>Zabbix Monitoring Configuration:</h2>
  </td></tr><tr><td width="240" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart Zabbix" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="60">&nbsp;</td><td width="100">&nbsp;</td><td width="100">&nbsp;</td><td>&nbsp;</td><td width="100">&nbsp;</td><td width="80">&nbsp;</td></tr>
<?php
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Zabbix Server:</strong>');
  putHtml('</td></tr>');

  if (($value = getVARdef($db, 'GUI_ZABBIX_DATA')) === '') {
    if (($value = getVARdef($db, 'ZABBIX_SERVER', $cur_db)) !== '') {
      $value = '1~'.$value;
    } else {
      $value = '0~';
    }
  }
  $datatokens = explode('~', $value, 2);

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Monitoring:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="zabbix_enabled">');
  $sel = ($datatokens[0] == '0') ? ' selected="selected"' : '';
  putHtml('<option value="0"'.$sel.'>disabled</option>');
  $sel = ($datatokens[0] == '1') ? ' selected="selected"' : '';
  putHtml('<option value="1"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Server:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  $value = $datatokens[1];
  putHtml('<input type="text" size="56" maxlength="128" value="'.$value.'" name="zabbix_server" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Server Port:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'ZABBIX_SERVER_PORT', $cur_db)) === '') {
    $value = '10051';
  }
  putHtml('<input type="text" size="8" maxlength="12" value="'.$value.'" name="zabbix_server_port" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Zabbix Agent:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Agent Hostname:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'ZABBIX_HOSTNAME', $cur_db)) === '') {
    $value = getVARdef($db, 'HOSTNAME', $cur_db);
  }
  putHtml('<input type="text" size="32" maxlength="128" value="'.$value.'" name="agent_hostname" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Agent ListenPort:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'ZABBIX_LISTENPORT', $cur_db)) === '') {
    $value = '10050';
  }
  putHtml('<input type="text" size="8" maxlength="12" value="'.$value.'" name="agent_listenport" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('StartAgents:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="zabbix_startagents">');
  if (($startagents = getVARdef($db, 'ZABBIX_STARTAGENTS', $cur_db)) === '') {
    $startagents = '3';
  }
  foreach ($startagents_menu as $key => $value) {
    $sel = ($startagents === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('processes');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('DebugLevel:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="zabbix_debuglevel">');
  if (($debuglevel = getVARdef($db, 'ZABBIX_DEBUGLEVEL', $cur_db)) === '') {
    $debuglevel = '3';
  }
  foreach ($debuglevel_menu as $key => $value) {
    $sel = ($debuglevel === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Timeout:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="zabbix_timeout">');
  if (($timeout = getVARdef($db, 'ZABBIX_TIMEOUT', $cur_db)) === '') {
    $timeout = '3';
  }
  foreach ($timeout_menu as $key => $value) {
    $sel = ($timeout === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('secs');
  putHtml('</td></tr>');

if (is_file($ZABBIX_PROXY_EXE)) {

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Zabbix Proxy:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Proxy:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="zabbix_proxy">');
  $sel = (getVARdef($db, 'ZABBIX_PROXY', $cur_db) === 'no') ? ' selected="selected"' : '';
  putHtml('<option value="no"'.$sel.'>disabled</option>');
  $sel = (getVARdef($db, 'ZABBIX_PROXY', $cur_db) === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Proxy Hostname:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'ZABBIX_PROXY_HOSTNAME', $cur_db)) === '') {
    $value = 'proxy-'.getVARdef($db, 'HOSTNAME', $cur_db);
  }
  putHtml('<input type="text" size="32" maxlength="128" value="'.$value.'" name="zabbix_proxy_hostname" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Proxy ListenPort:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  if (($value = getVARdef($db, 'ZABBIX_PROXY_LISTENPORT', $cur_db)) === '') {
    $value = '10051';
  }
  putHtml('<input type="text" size="8" maxlength="12" value="'.$value.'" name="proxy_listenport" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Route Agent via Proxy:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="zabbix_proxy_agent">');
  $sel = (getVARdef($db, 'ZABBIX_PROXY_AGENT', $cur_db) === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  $sel = (getVARdef($db, 'ZABBIX_PROXY_AGENT', $cur_db) === 'no') ? ' selected="selected"' : '';
  putHtml('<option value="no"'.$sel.'>disabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');
}

  putHtml('</table>');
  putHtml('</form>');

  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
