<?php

// Copyright (C) 2008-2013 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// edit.php for AstLinux
// 04-28-2008
// 12-04-2008, Added Reload/Restart Menu
// 02-18-2013, Added OpenVPN Client Config editing
//

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$select_reload = array (
  'reload' => 'Reload Asterisk',
  'iptables' => 'Restart Firewall',
  'dnsmasq' => 'Restart DNS &amp; DHCP',
  'radvd' => 'Restart IPv6 Autoconfig',
  'dynamicdns' => 'Restart Dynamic DNS',
  'ntpd' => 'Restart NTP Time',
  'msmtp' => 'Restart SMTP Mail',
  'openvpn' => 'Restart OpenVPN Server',
  'openvpnclient' => 'Restart OpenVPN Client',
  'racoon' => 'Restart IPsec VPN',
  'pptpd' => 'Restart PPTP VPN Server',
  'ldap' => 'Reload LDAP Client',
  'snmpd' => 'Restart SNMP Server',
  'stunnel' => 'Restart Stunnel Proxy',
  'miniupnpd' => 'Restart Univ. Plug\'n\'Play',
  'apcupsd' => 'Restart UPS Daemon',
  'prosody' => 'Restart XMPP Server',
  'zabbix' => 'Restart Zabbix Monitor',
  'asterisk' => 'Restart Asterisk'
);
if (is_addon_package('fop2')) {
  $select_reload['fop2'] = 'Restart Asterisk FOP2';
  $select_reload['FOP2'] = 'Reload Asterisk FOP2';
}
$select_reload['cron'] = 'Reload Cron for root';

$sys_label = array (
  'dnsmasq.conf' => 'DNSmasq Configuration',
  'misdn-init.conf' => 'mISDN Configuration',
  'ntpd.conf' => 'NTP Time Client/Server',
  'sshd.conf' => 'SSH Server sshd_config',
  'ldap.conf' => 'LDAP Client System Defaults',
  'lighttpd.conf' => 'Web Server Configuration',
  'sensors.conf' => 'Lm_sensors Hardware Monitoring',
  'zaptel.conf' => 'Zaptel System Config',
  'redfone.conf' => 'Redfone foneBRIDGE',
  'webgui-staff-backup.conf' => 'Staff Backup Password',
  'vsftpd.conf' => 'Standalone FTPd Configuration'
);

$ast_label = array (
  'modules.conf' => 'Which Modules are loaded or not',
  'adsi.conf' => 'Analog Display Services Interface',
  'adtranvofr.conf' => 'Voice over Frame Relay',
  'agents.conf' => 'Create and Manage Agents',
  'alarmreceiver.conf' => 'Contact ID Protocol Alarms',
  'alsa.conf' => 'Advanced Linux Sound Architecture',
  'amd.conf' => 'Answering Machine Detection',
  'asterisk.conf' => 'General Asterisk Config',
  'cdr.conf' => 'Call Detail Record Logging',
  'cdr_custom.conf' => 'Custom CDR Logging Format',
  'cdr_manager.conf' => 'Asterisk Manager CDR events',
  'cdr_odbc.conf' => 'CDR via the ODBC interface',
  'cdr_pgsql.conf' => 'CDR data in a PostgreSQL database',
  'cdr_tds.conf' => 'CDR data to a FreeTDS database',
  'codecs.conf' => 'Specify Speex parameters',
  'dnsmgr.conf' => 'Asterisk and DNS lookups',
  'dundi.conf' => 'DUNDi protocol for VoIP phone number',
  'enum.conf' => 'Electronic Numbering ENUM lookups',
  'extconfig.conf' => 'Realtime Database Configuration',
  'extensions.conf' => 'The Master Dialplan',
  'features.conf' => 'Call Parking and Call Options',
  'festival.conf' => 'Text-to-Speech Engine',
  'followme.conf' => 'Configure the FollowMe application',
  'func_odbc.conf' => 'ODBC databases via the dialplan',
  'gtalk.conf' => 'Google Talk',
  'http.conf' => 'HTTP daemon for GUI and AJAM',
  'iax.conf' => 'IAX2 devices and service providers',
  'iaxprov.conf' => 'Provision IAXy device',
  'indications.conf' => 'Worldwide Telephony Sounds',
  'jabber.conf' => 'XMPP Jabber',
  'logger.conf' => 'Type and Verbosity of Logs',
  'manager.conf' => 'Network Asterisk Console',
  'meetme.conf' => 'Conference Rooms',
  'mgcp.conf' => 'Media Gateway Control Protocol',
  'modem.conf' => 'ISDN-BRI via ISDN4Linux driver',
  'musiconhold.conf' => 'Music On Hold',
  'osp.conf' => 'Open Settlement Protocol',
  'oss.conf' => 'Open Sound System',
  'phone.conf' => 'Quicknet PhoneJACK card',
  'privacy.conf' => 'Configures PrivacyManager application',
  'queues.conf' => 'Call Center Queueing System',
  'res_odbc.conf' => 'Table Access within ODBC database',
  'res_snmp.conf' => 'SNMP support in Asterisk',
  'rpt.conf' => 'Radio Repeater Application',
  'rtp.conf' => 'RTP Port Range',
  'say.conf' => 'Spoken Language Grammar Rules',
  'sip.conf' => 'SIP devices and service providers',
  'sip_notify.conf' => 'SIP NOTIFY message support',
  'skinny.conf' => 'Cisco proprietary SCCP',
  'sla.conf' => 'Key System Shared Lines',
  'smdi.conf' => 'Station Message Desk Interface',
  'udptl.conf' => 'T.38 faxing over IP',
  'users.conf' => 'Asterisk GUI Users',
  'voicemail.conf' => 'Asterisk Voicemail System',
  'vpb.conf' => 'Voicetronix cards',
  'zapata.conf' => 'Analog Interface Settings'
);

// Function saveEDITfile()
//
function saveEDITfile($text, $file, $cleanup) {

  $tmpfile = $file.'.bak';
  if (! @copy($file, $tmpfile)) {
    return(FALSE);
  }
  if (get_magic_quotes_gpc()) {
    $data = stripslashes($text);
    $data = str_replace(chr(13), '', $data);
  } else {
    $data = str_replace(chr(13), '', $text);
  }
  if (($ph = @fopen($file, "wb")) === FALSE) {
    if ($cleanup) {
      @unlink($tmpfile);
    }
    return(FALSE);
  }
  if (fwrite($ph, $data) === FALSE) {
    fclose($ph);
    return(FALSE);
  }
  fclose($ph);
  if ($cleanup) {
    @unlink($tmpfile);
  }
  
  return(TRUE);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;                                 
  } elseif (isset($_POST['submit_open'])) {
    $result = 3;
    if (isset($_POST['file_list'])) {
      header('Location: '.$myself.'?file='.$_POST['file_list']);
      exit;
    }
  } elseif (isset($_POST['submit_save'])) {
    if (isset($_POST['openfile']) && isset($_POST['edit_text'])) {
      $file = $_POST['openfile'];
      $text = $_POST['edit_text'];
      if ($file !== '') {
        if (saveEDITfile($text, $file, (getPREFdef($global_prefs, 'edit_keep_bak_files')) !== 'yes')) {
          $result = 0;
        } else {
          $result = 10;
        }
        header('Location: '.$myself.'?file='.$file.'&result='.$result);
        exit;
      } else {
        $result = 3;
      }
    }
  } elseif (isset($_POST['submit_reload'])) {
    $result = 99;
    $process = $_POST['reload_restart'];
    if (isset($_POST['confirm_reload'])) {
      if ($process === 'reload') {
        if (($cmd = getPREFdef($global_prefs, 'system_asterisk_reload_cmdstr')) === '') {
          $cmd = 'module reload';
        }
        $status = asteriskCMD($cmd, '');
        if ($status == 0) {
          $result = 11;
        } elseif ($status == 1101) {
          $result = 1101;
        } elseif ($status == 1102) {
          $result = 1102;
        } else {
          $result = 4;
        }
      } elseif ($process === 'ntpd') {
        $result = restartPROCESS($process, 22, $result, 'init');
      } elseif ($process === 'msmtp') {
        $result = restartPROCESS($process, 31, $result, 'init');
      } elseif ($process === 'racoon') {
        $result = restartPROCESS($process, 23, $result, 'init');
      } elseif ($process === 'openvpn') {
        $result = restartPROCESS($process, 24, $result, 'init');
      } elseif ($process === 'openvpnclient') {
        $result = restartPROCESS($process, 29, $result, 'init');
      } elseif ($process === 'asterisk') {
        $result = restartPROCESS($process, 25, $result);
      } elseif ($process === 'iptables') {
        $result = restartPROCESS($process, 26, $result, 'init');
      } elseif ($process === 'dynamicdns') {
        $result = restartPROCESS($process, 27, $result, 'init');
      } elseif ($process === 'dnsmasq') {
        $result = restartPROCESS($process, 28, $result, 'init');
      } elseif ($process === 'radvd') {
        $result = restartPROCESS($process, 32, $result, 'init');
      } elseif ($process === 'pptpd') {
        $result = restartPROCESS($process, 33, $result, 'init');
      } elseif ($process === 'miniupnpd') {
        $result = restartPROCESS($process, 34, $result, 'init');
      } elseif ($process === 'apcupsd') {
        $result = restartPROCESS($process, 35, $result, 'init');
      } elseif ($process === 'zabbix') {
        $result = restartPROCESS($process, 36, $result, 'init', 4);
      } elseif ($process === 'stunnel') {
        $result = restartPROCESS($process, 37, $result, 'init');
      } elseif ($process === 'prosody') {
        $result = restartPROCESS($process, 38, $result, 'init');
      } elseif ($process === 'snmpd') {
        $result = restartPROCESS($process, 39, $result, 'init');
      } elseif ($process === 'ldap') {
        $result = restartPROCESS($process, 40, $result, 'init');
      } elseif ($process === 'fop2') {
        $result = restartPROCESS($process, 41, $result, 'init');
      } elseif ($process === 'FOP2') {
        $result = restartPROCESS('fop2', 42, $result, 'reload');
      } elseif ($process === 'cron') {
        $result = updateCRON('root', 30, $result);
      }
    } else {
      $result = 7;
      if (isset($_POST['openfile'])) {
        $file = $_POST['openfile'];
        if ($file !== '') {
          header('Location: '.$myself.'?file='.$file.'&reload_restart='.$process.'&result='.$result);
          exit;
        }
      }
      header('Location: '.$myself.'?reload_restart='.$process.'&result='.$result);
      exit;
    }
    if (isset($_POST['openfile'])) {
      $file = $_POST['openfile'];
      if ($file !== '') {
        header('Location: '.$myself.'?file='.$file.'&result='.$result);
        exit;
      }
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  $openfile = isset($_GET['file']) ? $_GET['file'] : '';
  $pos = strrpos($openfile, '/');
  $dir = substr($openfile, 0, $pos);
  
  if ($dir === '/mnt/kd' ||
      $dir === '/mnt/kd/dahdi' ||
      $dir === '/mnt/kd/openvpn' ||
      $dir === '/mnt/kd/openvpn/ccd' ||
      $dir === '/mnt/kd/rc.conf.d' ||
      $dir === '/mnt/kd/crontabs' ||
      $dir === '/mnt/kd/snmp' ||
      $dir === '/mnt/kd/fop2' ||
      $dir === '/mnt/kd/apcupsd' ||
      $dir === '/mnt/kd/prosody' ||
      $dir === '/mnt/kd/docs' ||
      $dir === '/mnt/kd/arno-iptables-firewall' ||
      $dir === '/mnt/kd/arno-iptables-firewall/plugins' ||
      $dir === '/etc/asterisk' ||
      $dir === '/etc/asterisk/includes' ||
      $openfile === '/etc/rc.modules' ||
      $openfile === '/etc/modprobe.d/options.conf' ||
      $openfile === '/etc/udev/rules.d/70-persistent-net.rules') {
    if (! is_writable($openfile)) {
      $openfile = '';
    }
  } else {
    $openfile = '';
  }
  
  if (isset($_GET['reload_restart'])) {
    $reload_restart = $_GET['reload_restart'];
  } else {
    $reload_restart = 'system';
  }

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 0) {
      putHtml('<p style="color: green;">File changes saved: '.$openfile.'</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: orange;">No Action, no file was selected.</p>');
    } elseif ($result == 4 || $result == 1101 || $result == 1102) {
      putHtml('<p style="color: red;">'.asteriskERROR($result).'</p>');
    } elseif ($result == 7) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 8) {
      putHtml('<p style="color: red;">No Action, unable to open file.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: red;">Unable to save changes: '.$openfile.'</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Asterisk Modules Reloaded.</p>');
    } elseif ($result == 22) {
      putHtml('<p style="color: green;">NTP Time has Restarted.</p>');
    } elseif ($result == 23) {
      putHtml('<p style="color: green;">IPsec VPN has Restarted.</p>');
    } elseif ($result == 24) {
      putHtml('<p style="color: green;">OpenVPN Server has Restarted.</p>');
    } elseif ($result == 25) {
      putHtml('<p style="color: green;">Asterisk has Restarted.</p>');
    } elseif ($result == 26) {
      putHtml('<p style="color: green;">Firewall has Restarted.</p>');
    } elseif ($result == 27) {
      putHtml('<p style="color: green;">Dynamic DNS has Restarted.</p>');
    } elseif ($result == 28) {
      putHtml('<p style="color: green;">DNS &amp; DHCP Server has Restarted.</p>');
    } elseif ($result == 29) {
      putHtml('<p style="color: green;">OpenVPN Client has Restarted.</p>');
    } elseif ($result == 30) {
      putHtml('<p style="color: green;">Cron Jobs for root will be reloaded within a minute.</p>');
    } elseif ($result == 31) {
      putHtml('<p style="color: green;">SMTP Mail has Restarted.</p>');
    } elseif ($result == 32) {
      putHtml('<p style="color: green;">IPv6 Autoconfig (radvd) has Restarted.</p>');
    } elseif ($result == 33) {
      putHtml('<p style="color: green;">PPTP VPN Server has Restarted.</p>');
    } elseif ($result == 34) {
      putHtml('<p style="color: green;">Universal Plug\'n\'Play has Restarted.</p>');
    } elseif ($result == 35) {
      putHtml('<p style="color: green;">UPS Daemon has Restarted.</p>');
    } elseif ($result == 36) {
      putHtml('<p style="color: green;">Zabbix Monitoring has Restarted.</p>');
    } elseif ($result == 37) {
      putHtml('<p style="color: green;">Stunnel Proxy has Restarted.</p>');
    } elseif ($result == 38) {
      putHtml('<p style="color: green;">XMPP Server has Restarted.</p>');
    } elseif ($result == 39) {
      putHtml('<p style="color: green;">SNMP Server has Restarted.</p>');
    } elseif ($result == 40) {
      putHtml('<p style="color: green;">LDAP Client Defaults has been Reloaded.</p>');
    } elseif ($result == 41) {
      putHtml('<p style="color: green;">Asterisk Flash Operating Panel2 has Restarted.</p>');
    } elseif ($result == 42) {
      putHtml('<p style="color: green;">Asterisk Flash Operating Panel2 has been Reloaded.</p>');
    } elseif ($result == 99) {
      putHtml('<p style="color: red;">Action Failed.</p>');
    } elseif ($result == 999) {
      putHtml('<p style="color: red;">Permission denied for user "'.$global_user.'".</p>');
    } else {
      putHtml('<p style="color: orange;">No Action.</p>');
    }
  } elseif ($openfile !== '') {
    $sel = '<p style="color: green;">File opened for editing: '.$openfile.'</p>';
    if (($stat = @stat($openfile)) !== FALSE) {
      if ($stat['size'] > 250000) {  // safety limit
        $sel = '<p style="color: red;">File is too large to edit: ';
        $sel .= $openfile;
        $sel .= '</p>';
        $openfile = '';
      }
    }
    putHtml($sel);
  } else {
    putHtml('<p>&nbsp;</p>');
  }
  putHtml("</center>");
?>
  <script language="JavaScript" type="text/javascript" src="/common/murmurhash3_gc.js"></script>
  <script language="JavaScript" type="text/javascript">
  //<![CDATA[
  var old_textSize;
  var old_textHash;

  function setOKexit() {
    var value = document.getElementById("ed").value;
    var cur_textSize = value.length;
    var cur_textHash = murmurhash3_32_gc(value, 6802145);
    if (cur_textSize != old_textSize || cur_textHash != old_textHash) {
      return 'Unsaved changes will be lost. Really leave?';
    }
  }

  function setOKhandler() {
    var value = document.getElementById("ed").value;
    old_textSize = value.length;
    old_textHash = murmurhash3_32_gc(value, 6802145);
    window.onbeforeunload = setOKexit;
  }
  //]]>
  </script>
  <center>
  <table class="layoutNOpad"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>">
  <table class="stdtable">
  <tr><td style="text-align: center;" colspan="3">
  <h2>Edit Configuration Files:</h2>
  </td></tr><tr><td style="text-align: left;">
  <select name="file_list" size="8">
<?php
  putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; System Configuration &mdash;&mdash;&mdash;&mdash;">');
  if (is_writable($file = '/mnt/kd/rc.conf.d/user.conf')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - User System Variables</option>');
  }
  foreach (glob('/mnt/kd/*.conf') as $globfile) {
    if (is_writable($globfile)) {
      $label = basename($globfile);
      $label = isset($sys_label["$label"]) ? $sys_label["$label"] : '/mnt/kd/ System Config File';
      $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
      putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - '.$label.'</option>');
    }
  }
  foreach (glob('/mnt/kd/dahdi/*.conf') as $globfile) {
    if (is_writable($globfile)) {
      $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
      putHtml('<option value="'.$globfile.'"'.$sel.'>dahdi/'.basename($globfile).' - DAHDI System Config</option>');
    }
  }
  if (is_writable($file = '/mnt/kd/openvpn/openvpn.conf')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - OpenVPN Server</option>');
  }
  if (is_writable($file = '/mnt/kd/openvpn/openvpnclient.conf')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - OpenVPN Client</option>');
  }
  if (is_writable($file = '/mnt/kd/ethers')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Assign MAC to IP addresses</option>');
  }
  if (is_writable($file = '/mnt/kd/hosts')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Additional hosts File Entries</option>');
  }
  if (is_writable($file = '/mnt/kd/dnsmasq.static')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Additional DNSmasq Config</option>');
  }
  if (is_writable($file = '/mnt/kd/dnsmasq.leases')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Active DNSmasq Leases</option>');
  }
  if (is_writable($file = '/mnt/kd/blocked-hosts')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Firewall Blocked Hosts</option>');
  }
  if (is_writable($file = '/mnt/kd/crontabs/root')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>crontabs/'.basename($file).' - Cron Jobs for root</option>');
  }
  if (is_writable($file = '/mnt/kd/apcupsd/apcupsd.conf')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>apcupsd/'.basename($file).' - APC UPS Configuration</option>');
  }
  if (is_writable($file = '/mnt/kd/ast-crash')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Safe Asterisk Crash Shell Script</option>');
  }
  foreach (glob('/mnt/kd/rc.*') as $globfile) {
    if ($globfile === '/mnt/kd/rc.local' ||
        $globfile === '/mnt/kd/rc.local.stop' ||
        $globfile === '/mnt/kd/rc.elocal' ||
        $globfile === '/mnt/kd/rc.ledcontrol') {
      if (is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - Startup/Stop Shell Script</option>');
      }
    }
  }
  if (is_writable($file = '/etc/rc.modules')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Startup Modprobe Modules</option>');
  }
  if (is_writable($file = '/etc/modprobe.d/options.conf')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>modprobe.d/'.basename($file).' - Module Options</option>');
  }
  if (is_writable($file = '/etc/udev/rules.d/70-persistent-net.rules')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Net Interface Rules</option>');
  }
  if (is_writable($file = '/mnt/kd/prosody/prosody.conf')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>prosody/'.basename($file).' - XMPP Configuration</option>');
  }
  if (is_writable($file = '/mnt/kd/prosody/sharedgroups.conf')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>prosody/'.basename($file).' - XMPP Shared Groups</option>');
  }
  if (is_writable($file = '/mnt/kd/snmp/snmpd.conf')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>snmp/'.basename($file).' - SNMP Agent Server Config</option>');
  }
  if (is_writable($file = '/mnt/kd/snmp/snmp.conf')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>snmp/'.basename($file).' - SNMP Applications Config</option>');
  }
  putHtml('</optgroup>');
  if (is_dir('/mnt/kd/openvpn/ccd') && count($globfiles = glob('/mnt/kd/openvpn/ccd/*')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; OpenVPN Client Configs &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - X509 CN of OpenVPN Client</option>');
      }
    }
    putHtml('</optgroup>');
  }
  if (is_dir('/mnt/kd/docs') && count($globfiles = glob('/mnt/kd/docs/*')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; Documentation &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - /mnt/kd/docs/ File</option>');
      }
    }
    putHtml('</optgroup>');
  }
  if (is_dir('/mnt/kd/fop2') && count($globfiles = glob('/mnt/kd/fop2/*.cfg')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; Flash Operating Panel2 Configs &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - Asterisk FOP2 Config</option>');
      }
    }
    putHtml('</optgroup>');
  }
  $optgroup = FALSE;
  foreach (glob('/etc/asterisk/*.conf') as $globfile) {
    if (is_writable($globfile)) {
      if (! $optgroup) {
        putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; Asterisk Configuration &mdash;&mdash;&mdash;&mdash;">');
        $optgroup = TRUE;
      }
      $label = basename($globfile);
      $label = isset($ast_label["$label"]) ? $ast_label["$label"] : 'Asterisk Config File';
      $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
      putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - '.$label.'</option>');
    }
  }
  if ($optgroup) {
    foreach (glob('/etc/asterisk/includes/*.conf') as $globfile) {
      if (is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>includes/'.basename($globfile).' - Asterisk Include File</option>');
      }
    }
    if (is_writable($file = '/etc/asterisk/extensions.lua')) {
      $sel = ($file === $openfile) ? ' selected="selected"' : '';
      putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Lua Dialplan</option>');
    }
    if (is_writable($file = '/etc/asterisk/extensions.ael')) {
      $sel = ($file === $openfile) ? ' selected="selected"' : '';
      putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - AEL Dialplan</option>');
    }
    putHtml('</optgroup>');
  }
  if (($plugins = getARNOplugins()) !== FALSE) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; Firewall Plugins &amp; Config &mdash;&mdash;&mdash;&mdash;">');
    foreach ($plugins as $globfile => $value) {
      if (is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - Firewall Plugin</option>');
      }
    }
    if (is_writable($file = '/mnt/kd/arno-iptables-firewall/custom-rules')) {
      $sel = ($file === $openfile) ? ' selected="selected"' : '';
      putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Firewall Custom Rules</option>');
    }
    putHtml('</optgroup>');
  }
?>
  </select>
  </td><td width="20">&nbsp;</td><td style="text-align: left;">
  <input type="submit" class="formbtn" value="&gt;&gt;&nbsp;Open File" name="submit_open" />
  </td></tr></table>
  <table width="100%" class="stdtable">
  <tr><td width="240" style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Changes" name="submit_save" onclick="setOKhandler();" />
  <input type="hidden" value="<?php echo $openfile;?>" name="openfile" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Reload/Restart" name="submit_reload" />
<?php
  putHtml('&ndash;');
  putHtml('<select name="reload_restart">');
  foreach ($select_reload as $key => $value) {
    $sel = ($reload_restart === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="reload" name="confirm_reload" />&nbsp;Confirm');
  putHtml('</td></tr>');
  putHtml('</table>');

  if (($cols = getPREFdef($global_prefs, 'edit_text_cols_cmdstr')) === '') {
    $cols = '95';
  }
  if (($rows = getPREFdef($global_prefs, 'edit_text_rows_cmdstr')) === '') {
    $rows = '30';
  }
  putHtml('<table class="stdtable"><tr><td>');
  echo '<textarea id="ed" name="edit_text" rows="'.$rows.'" cols="'.$cols.'" wrap="off" class="editText">';
  if ($openfile !== '') {
    if (($ph = @fopen($openfile, "rb")) !== FALSE) {
      while (! feof($ph)) {
        if ($line = fgets($ph, 1024)) {
          $line = str_replace(chr(10), chr(13), $line);
          echo htmlspecialchars($line);
        }
      }
      fclose($ph);
    }
  }
  putHtml('</textarea>');
  putHtml('</td></tr></table>');
  putHtml('</form>');
  putHtml('</center></td></tr></table>');
  putHtml('</center>');
  putHtml('<script language="JavaScript" type="text/javascript">');
  putHtml('//<![CDATA[');
  putHtml('setOKhandler();');
  putHtml('//]]>');
  putHtml('</script>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
