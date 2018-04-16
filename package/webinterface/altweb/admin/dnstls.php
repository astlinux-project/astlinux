<?php

// Copyright (C) 2018 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// dnstls.php for AstLinux
// 04-14-2018
//
// System location of rc.conf file
$CONFFILE = '/etc/rc.conf';
// System location of /mnt/kd/rc.conf.d directory
$DNSTLSCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.dnstls.conf file
$DNSTLSCONFFILE = '/mnt/kd/rc.conf.d/gui.dnstls.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

// Function: saveDNSTLSsettings
//
function saveDNSTLSsettings($conf_dir, $conf_file) {
  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.dnstls.conf - start ###\n###\n");

  $value = 'DNS_TLS_PROXY="'.$_POST['proxy'].'"';
  fwrite($fp, "### DNS-TLS Enable\n".$value."\n");

  $value = tuq(str_replace(chr(13), ' ', $_POST['dns_tls_servers']));
  $value = str_replace(chr(10), '', $value);
  if (strlen($value) > 512) {  // sanity check
    $value = substr($value, 0, 512);
  }
  $value = 'DNS_TLS_SERVERS="'.$value.'"';
  fwrite($fp, "### Upstream Servers\n".$value."\n");

  fwrite($fp, "### gui.dnstls.conf - end ###\n");
  fclose($fp);

  return($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $result = saveDNSTLSsettings($DNSTLSCONFDIR, $DNSTLSCONFFILE);
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('stubby', 10, $result, 'init');
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

  if (is_file($DNSTLSCONFFILE)) {
    $db = parseRCconf($DNSTLSCONFFILE);
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
      putHtml('<p style="color: green;">DNS-TLS Proxy Server'.statusPROCESS('stubby').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart DNS-TLS" to apply any changed settings.</p>');
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
  <h2>DNS-TLS Proxy Server Configuration:</h2>
  </td></tr><tr><td width="280" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart DNS-TLS" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="60">&nbsp;</td><td width="100">&nbsp;</td><td width="100">&nbsp;</td><td>&nbsp;</td><td width="100">&nbsp;</td><td width="80">&nbsp;</td></tr>
<?php
if (isDNSCRYPT()) {
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>The alternate DNSCrypt Proxy Server is running!</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="color: red; text-align: center;" colspan="6">');
  putHtml('Warning: Both DNS-TLS and DNSCrypt can\'t be active simultaneously.</td></tr>');
}
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>DNS-TLS Proxy Server:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('DNS-TLS:');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<select name="proxy">');
  $value = getVARdef($db, 'DNS_TLS_PROXY', $cur_db);
  putHtml('<option value="no">disabled</option>');
  $sel = ($value === 'yes') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>enabled</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
  putHtml('<strong>Upstream Recursive Server(s):</strong>');
  putHtml('</td></tr>');

if (is_file('/mnt/kd/stubby/stubby.yml')) {
  putHtml('<tr class="dtrow1"><td style="color: orange; text-align: center;" colspan="6">');
  putHtml('Note: Configuration overridden by file: /mnt/kd/stubby/stubby.yml');
  putHtml('</td></tr>');
}
  putHtml('<tr class="dtrow1"><td style="text-align: right;" colspan="2">');
  putHtml('Server(s):');
  putHtml('</td><td style="text-align: left;" colspan="4">');
  putHtml('<i>(IPv4/IPv6~Auth_Name~Optional_Port)</i><br />');
  echo '<textarea name="dns_tls_servers" rows="4" cols="56" wrap="off" class="edititemText">';
  if (($value = getVARdef($db, 'DNS_TLS_SERVERS', $cur_db)) === '') {
    $value = '9.9.9.9~dns.quad9.net 149.112.112.112~dns.quad9.net';
  }
  foreach (explode(' ', $value) as $server) {
    if ($server !== '') {
      echo htmlspecialchars($server), chr(13);
    }
  }
  putHtml('</textarea>');
  putHtml('</td></tr>');

  putHtml('</table>');
  putHtml('</form>');

  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
