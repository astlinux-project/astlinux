<?php

// Copyright (C) 2008-2025 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// edit.php for AstLinux
// 04-28-2008
// 12-04-2008, Added Reload/Restart Menu
// 02-18-2013, Added OpenVPN Client Config editing
// 09-06-2013, Added Shortcut support
// 06-07-2016, Added Avahi mDNS/DNS-SD support
// 09-21-2016, Added Reload Firewall Blocklist
// 11-14-2016, Added IPsec strongSwan support
// 02-16-2017, Added Restart FTP Server support
// 11-06-2017, Added WireGuard VPN Support
// 07-25-2018, Added Keepalived Support
// 11-12-2018, Added WireGuard VPN Mobile Client support
// 06-13-2019, Added Reload WireGuard VPN
// 07-30-2019, Added CodeMirror text editing
// 08-24-2019, Added Apply user.conf variables
// 02-21-2020, Remove PPTP VPN support
// 05-10-2020, Added Linux Containers (LXC)
// 02-04-2021, Remove IPsec (racoon) VPN support
// 06-13-2025, Added editing /var/db/dnsmasq-lease.db and dnsmasq-lease6.db
//

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$select_reload = array (
  'APPLY' => 'Apply user.conf variables',
  'ASTERISK' => 'Reload Asterisk',
  'iptables' => 'Restart Firewall',
  'dnsmasq' => 'Restart DNS &amp; DHCP',
  'dynamicdns' => 'Restart Dynamic DNS',
  'ntpd' => 'Restart NTP Time',
  'msmtp' => 'Restart SMTP Mail',
  'openvpn' => 'Restart OpenVPN Server',
  'openvpnclient' => 'Restart OpenVPN Client',
  'ipsec' => 'Restart IPsec strongSwan',
  'wireguard' => 'Restart WireGuard VPN',
  'WIREGUARD' => 'Reload WireGuard VPN',
  'fossil' => 'Restart Fossil Server',
  'vsftpd' => 'Restart FTP Server',
  'ldap' => 'Reload LDAP Client',
  'slapd' => 'Restart LDAP Server',
  'avahi' => 'Restart mDNS/DNS-SD',
  'monit' => 'Restart Monit Monitor',
  'darkstat' => 'Restart NetStat Server',
  'snmpd' => 'Restart SNMP Server',
  'stunnel' => 'Restart Stunnel Proxy',
  'miniupnpd' => 'Restart Univ. Plug\'n\'Play',
  'ups' => 'Restart UPS Daemon',
  'prosody' => 'Restart XMPP Server',
  'zabbix' => 'Restart Zabbix Monitor',
  'asterisk' => 'Restart Asterisk'
);
if (is_file('/etc/init.d/keepalived')) {
  $select_reload['keepalived'] = 'Restart Keepalived';
}
if (is_file('/etc/init.d/lxc')) {
  $select_reload['lxc'] = 'Restart Linux Containers';
}
if (is_addon_package('fop2')) {
  $select_reload['fop2'] = 'Restart Asterisk FOP2';
  $select_reload['FOP2'] = 'Reload Asterisk FOP2';
}
if (is_file('/etc/init.d/kamailio')) {
  $select_reload['kamailio'] = 'Restart Kamailio';
}
$select_reload['IPTABLES'] = 'Reload Firewall Blocklist';
$select_reload['cron'] = 'Reload Cron for root';
if (! is_file('/etc/init.d/zabbix')) {
  unset($select_reload['zabbix']);
}

$sys_label = array (
  'ddclient.conf' => 'DDclient Dynamic DNS Config',
  'dnsmasq.conf' => 'DNSmasq Configuration',
  'misdn-init.conf' => 'mISDN Configuration',
  'msmtp-aliases.conf' => 'SMTP Local Aliases',
  'chrony.conf' => 'NTP Time Client/Server',
  'sshd.conf' => 'SSH Server sshd_config',
  'ldap.conf' => 'LDAP Client System Defaults',
  'slapd.conf' => 'LDAP Server Configuration',
  'lighttpd.conf' => 'Web Server Configuration',
  'php.ini.conf' => 'PHP Runtime Configuration',
  'sensors.conf' => 'Lm_sensors Hardware Monitoring',
  'zaptel.conf' => 'Zaptel System Config',
  'redfone.conf' => 'Redfone foneBRIDGE',
  'webgui-staff-backup.conf' => 'Staff Backup Password',
  'massdeployment.conf' => 'IP Phone Deployment Data',
  'webgui-massdeployment.conf' => 'IP Phone Deployment Data',
  'vsftpd.conf' => 'FTP Server Configuration'
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

// Function menuSelectHint()
//
function menuSelectHint($file) {

  $menu = 'APPLY';

  if ($file !== '') {
    $hints = explode('/', $file);
    // Note: the leading file / sets hints[0] to ''
    if ($hints[1] === 'mnt' && $hints[2] === 'kd') {
      $hint = isset($hints[3]) ? $hints[3] : '';
    } elseif ($hints[1] === 'var' && $hints[2] === 'db') {
      $hint = isset($hints[3]) ? $hints[3] : '';
    } elseif ($hints[1] === 'etc') {
      $hint = isset($hints[2]) ? $hints[2] : '';
    } else {
      $hint = '';
    }
    if ($hint !== '') {
      $menu_hint = array (
        'ddclient.conf' => 'dynamicdns',
        'dnsmasq.static' => 'dnsmasq',
        'dnsmasq.leases' => 'dnsmasq',
        'dnsmasq-lease.db' => 'dnsmasq',
        'dnsmasq-lease6.db' => 'dnsmasq',
        'chrony.conf' => 'ntpd',
        'msmtp-aliases.conf' => 'msmtp',
        'ldap.conf' => 'ldap',
        'slapd.conf' => 'slapd',
        'vsftpd.conf' => 'vsftpd',
        'blocklists' => 'IPTABLES',
        'crontabs' => 'cron',
        'prosody' => 'prosody',
        'snmp' => 'snmpd',
        'keepalived' => 'keepalived',
        'lxc' => 'lxc',
        'openvpn' => 'openvpn',
        'ipsec' => 'ipsec',
        'wireguard' => 'WIREGUARD',
        'avahi' => 'avahi',
        'ups' => 'ups',
        'monit' => 'monit',
        'fop2' => 'FOP2',
        'kamailio' => 'kamailio',
        'asterisk' => 'ASTERISK',
        'arno-iptables-firewall' => 'iptables'
      );
      if (isset($menu_hint[$hint])) {
        $menu = $menu_hint[$hint];
      }
    }
  }

  return($menu);
}

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
  } elseif (isset($_POST['submit_fossil'])) {
    header('Location: /admin/fossilcmd.php');
    exit;
  } elseif (isset($_POST['submit_reload'])) {
    $result = 99;
    $process = $_POST['reload_restart'];
    if (isset($_POST['confirm_reload'])) {
      if ($process === 'ASTERISK') {
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
      } elseif ($process === 'miniupnpd') {
        $result = restartPROCESS($process, 34, $result, 'init');
      } elseif ($process === 'ups') {
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
      } elseif ($process === 'slapd') {
        $result = restartPROCESS($process, 43, $result, 'init');
      } elseif ($process === 'darkstat') {
        $result = restartPROCESS($process, 44, $result, 'init');
      } elseif ($process === 'kamailio') {
        $result = restartPROCESS($process, 45, $result, 'init');
      } elseif ($process === 'monit') {
        $result = restartPROCESS($process, 46, $result, 'init');
      } elseif ($process === 'fossil') {
        $result = restartPROCESS($process, 47, $result, 'init');
      } elseif ($process === 'avahi') {
        $result = restartPROCESS($process, 48, $result, 'init');
      } elseif ($process === 'ipsec') {
        $result = restartPROCESS($process, 49, $result, 'init');
      } elseif ($process === 'vsftpd') {
        $result = restartPROCESS($process, 50, $result, 'init');
      } elseif ($process === 'wireguard') {
        $result = restartPROCESS($process, 51, $result, 'init');
      } elseif ($process === 'WIREGUARD') {
        $result = restartPROCESS('wireguard', 52, $result, 'reload');
      } elseif ($process === 'keepalived') {
        $result = restartPROCESS($process, 53, $result, 'init');
      } elseif ($process === 'lxc') {
        $result = restartPROCESS($process, 54, $result, 'init');
      } elseif ($process === 'IPTABLES') {
        $result = restartPROCESS('iptables', 66, $result, 'reload');
      } elseif ($process === 'APPLY') {
        $result = restartPROCESS('', 67, 97, 'apply');
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
  $pos = strrpos($dir, '/');
  $dir_up = substr($dir, 0, $pos);

  if ($dir === '/mnt/kd' ||
      $dir === '/mnt/kd/dahdi' ||
      $dir === '/mnt/kd/openvpn' ||
      $dir === '/mnt/kd/openvpn/ccd' ||
      $dir === '/mnt/kd/ipsec/strongswan' ||
      $dir === '/mnt/kd/wireguard/peer' ||
      $dir === '/mnt/kd/wireguard/peer/wg0.clients' ||
      $dir === '/mnt/kd/rc.conf.d' ||
      $dir === '/mnt/kd/crontabs' ||
      $dir === '/mnt/kd/snmp' ||
      $dir === '/mnt/kd/fop2' ||
      $dir === '/mnt/kd/kamailio' ||
      $dir === '/mnt/kd/avahi' ||
      $dir === '/mnt/kd/avahi/services' ||
      $dir === '/mnt/kd/monit' ||
      $dir === '/mnt/kd/monit/monit.d' ||
      $dir === '/mnt/kd/ups' ||
      $dir === '/mnt/kd/prosody' ||
      $dir === '/mnt/kd/unbound' ||
      $dir === '/mnt/kd/docs' ||
      $dir === '/mnt/kd/arno-iptables-firewall' ||
      $dir === '/mnt/kd/arno-iptables-firewall/plugins' ||
      $dir === '/mnt/kd/blocklists' ||
      $dir === '/mnt/kd/phoneprov/templates' ||
      $dir === '/mnt/kd/keepalived' ||
      $dir === '/etc/asterisk' ||
      $dir === '/etc/asterisk/includes' ||
      $dir_up === '/mnt/kd/lxc/container' ||
      $openfile === '/etc/rc.modules' ||
      $openfile === '/etc/modprobe.d/options.conf' ||
      $openfile === '/etc/udev/rules.d/70-persistent-net.rules' ||
      $openfile === '/var/db/dnsmasq-lease.db' ||
      $openfile === '/var/db/dnsmasq-lease6.db' ||
      $openfile === '/stat/var/packages/fop2/html/js/presence.js') {
    if (! is_writable($openfile)) {
      $openfile = '';
    }
  } else {
    $openfile = '';
  }

  if (isset($_GET['reload_restart'])) {
    $reload_restart = $_GET['reload_restart'];
  } else {
    $reload_restart = menuSelectHint($openfile);
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
      putHtml('<p style="color: green;">NTP Time'.statusPROCESS('ntpd').'.</p>');
    } elseif ($result == 24) {
      putHtml('<p style="color: green;">OpenVPN Server'.statusPROCESS('openvpn').'.</p>');
    } elseif ($result == 25) {
      putHtml('<p style="color: green;">Asterisk'.statusPROCESS('asterisk').'.</p>');
    } elseif ($result == 26) {
      putHtml('<p style="color: green;">Firewall'.statusPROCESS('iptables').'.</p>');
    } elseif ($result == 27) {
      putHtml('<p style="color: green;">Dynamic DNS'.statusPROCESS('dynamicdns').'.</p>');
    } elseif ($result == 28) {
      putHtml('<p style="color: green;">DNS &amp; DHCP Server'.statusPROCESS('dnsmasq').'.</p>');
    } elseif ($result == 29) {
      putHtml('<p style="color: green;">OpenVPN Client'.statusPROCESS('openvpnclient').'.</p>');
    } elseif ($result == 30) {
      putHtml('<p style="color: green;">Cron Jobs for root will be reloaded within a minute.</p>');
    } elseif ($result == 31) {
      putHtml('<p style="color: green;">SMTP Mail has Restarted.</p>');
    } elseif ($result == 34) {
      putHtml('<p style="color: green;">Universal Plug\'n\'Play'.statusPROCESS('miniupnpd').'.</p>');
    } elseif ($result == 35) {
      putHtml('<p style="color: green;">UPS Daemon'.statusPROCESS('ups').'.</p>');
    } elseif ($result == 36) {
      putHtml('<p style="color: green;">Zabbix Monitoring'.statusPROCESS('zabbix').'.</p>');
    } elseif ($result == 37) {
      putHtml('<p style="color: green;">Stunnel Proxy'.statusPROCESS('stunnel').'.</p>');
    } elseif ($result == 38) {
      putHtml('<p style="color: green;">XMPP Server'.statusPROCESS('prosody').'.</p>');
    } elseif ($result == 39) {
      putHtml('<p style="color: green;">SNMP Server'.statusPROCESS('snmpd').'.</p>');
    } elseif ($result == 40) {
      putHtml('<p style="color: green;">LDAP Client Defaults has been Reloaded.</p>');
    } elseif ($result == 41) {
      putHtml('<p style="color: green;">Asterisk Flash Operating Panel2'.statusPROCESS('fop2').'.</p>');
    } elseif ($result == 42) {
      putHtml('<p style="color: green;">Asterisk Flash Operating Panel2 has been Reloaded.</p>');
    } elseif ($result == 43) {
      putHtml('<p style="color: green;">LDAP Server'.statusPROCESS('slapd').'.</p>');
    } elseif ($result == 44) {
      putHtml('<p style="color: green;">NetStat Server (darkstat)'.statusPROCESS('darkstat').'.</p>');
    } elseif ($result == 45) {
      putHtml('<p style="color: green;">Kamailio SIP Server'.statusPROCESS('kamailio').'.</p>');
    } elseif ($result == 46) {
      putHtml('<p style="color: green;">Monit Monitoring'.statusPROCESS('monit').'.</p>');
    } elseif ($result == 47) {
      putHtml('<p style="color: green;">Fossil Server'.statusPROCESS('fossil').'.</p>');
    } elseif ($result == 48) {
      putHtml('<p style="color: green;">mDNS/DNS-SD (Avahi)'.statusPROCESS('avahi').'.</p>');
    } elseif ($result == 49) {
      putHtml('<p style="color: green;">IPsec VPN (strongSwan)'.statusPROCESS('ipsec').'.</p>');
    } elseif ($result == 50) {
      putHtml('<p style="color: green;">FTP Server'.statusPROCESS('vsftpd').'.</p>');
    } elseif ($result == 51) {
      putHtml('<p style="color: green;">WireGuard VPN'.statusPROCESS('wireguard').'.</p>');
    } elseif ($result == 52) {
      putHtml('<p style="color: green;">WireGuard VPN has been Reloaded.</p>');
    } elseif ($result == 53) {
      putHtml('<p style="color: green;">Keepalived'.statusPROCESS('keepalived').'.</p>');
    } elseif ($result == 54) {
      putHtml('<p style="color: green;">Linux Containers'.statusPROCESS('lxc').'.</p>');
    } elseif ($result == 66) {
      putHtml('<p style="color: green;">Firewall Blocklist has been Reloaded.</p>');
    } elseif ($result == 67) {
      putHtml('<p style="color: green;">User System Variables applied with user.conf file.</p>');
    } elseif ($result == 97) {
      putHtml('<p style="color: red;">Syntax error in system variables when applying user.conf file.</p>');
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

  $codemirror_files = array (
    'lib/codemirror.js',
    'lib/codemirror.css',
    'addon/search/search.js',
    'addon/search/searchcursor.js',
    'addon/comment/comment.js',
    'addon/dialog/dialog.js',
    'addon/dialog/dialog.css',
    'addon/display/fullscreen.js',
    'addon/display/fullscreen.css',
    'mode/asterisk/asterisk.js',
    'mode/properties/properties.js',
    'mode/shell/shell.js',
    'mode/xml/xml.js'
  );
  if (($cm_theme = getPREFdef($global_prefs, 'edit_text_codemirror_theme')) !== '') {
    $codemirror_files[] = "theme/$cm_theme.css";
  }
  foreach ($codemirror_files as $cm_file) {
    $cm_ext = pathinfo($cm_file, PATHINFO_EXTENSION);
    if ($cm_ext === 'css') {
      putHtml('<link rel="stylesheet" href="/common/codemirror/'.$cm_file.'" type="text/css" />');
    }
  }
  foreach ($codemirror_files as $cm_file) {
    $cm_ext = pathinfo($cm_file, PATHINFO_EXTENSION);
    if ($cm_ext === 'js') {
      putHtml('<script language="JavaScript" type="text/javascript" src="/common/codemirror/'.$cm_file.'"></script>');
    }
  }
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
    window.onload = function() {
      document.getElementById("list").focus();
    };
    var value = document.getElementById("ed").value;
    old_textSize = value.length;
    old_textHash = murmurhash3_32_gc(value, 6802145);
    window.onbeforeunload = setOKexit;
  }

  function useCodeMirror(name, theme) {
    var ta = document.getElementById("ed");
    var cm = CodeMirror.fromTextArea(ta, {
      lineNumbers: true
    });
    if (theme != "") {
      if (theme == "solarized") {
        theme += " light";
      }
      cm.setOption("theme", theme);
    }
    if (name.search('/asterisk/.*[.]conf$') >= 0) {
      cm.setOption("mode", "text/x-asterisk");
    } else if (name.search('[.]xml$') >= 0) {
      cm.setOption("mode", "text/xml");
    } else if (name.search('^/mnt/kd/rc[.]') >= 0 ||
               name.search('[.]script$') >= 0 ||
               name.search('[.]sh$') >= 0 ||
               name.search('/arno-iptables-firewall/(.*[.]conf$|custom-rules)') >= 0) {
      cm.setOption("mode", "text/x-sh");
    } else {
      cm.setOption("mode", "text/x-ini");
    }
    cm.setSize((ta.cols * 1.1) * cm.defaultCharWidth(), ta.rows * cm.defaultTextHeight() + 6);
    // Tab key toggles fullscreen, Esc returns
    cm.setOption("extraKeys", {
      'Cmd-/'  : 'toggleComment',
      'Ctrl-/' : 'toggleComment',
      'Cmd-.'  : 'toggleComment',
      'Ctrl-.' : 'toggleComment',
      'Tab'    : function(cm) {
        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
      },
      'Esc'    : function(cm) {
        if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
      }
    });
    // Update TextArea value with every CM change
    cm.on("change", function(cm, change) {
      ta.value = cm.getValue();
    });
    // Desktop Safari wants a relatedTarget, else fullscreen exits incorrectly
    cm.on("focus", function(cm, change) {
      if (change.relatedTarget == null && window.safari !== undefined) {
        document.getElementById("list").focus();
        document.getElementById("list").blur();
      }
    });
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
  <select id="list" name="file_list" size="8">
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
  if (is_writable($file = '/var/db/dnsmasq-lease.db')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Active DNSmasq IPv4 Leases</option>');
  }
  if (is_writable($file = '/var/db/dnsmasq-lease6.db')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Active DNSmasq IPv6 Leases</option>');
  }
  if (is_writable($file = '/mnt/kd/blocked-hosts')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Firewall Blocked Hosts</option>');
  }
  if (is_writable($file = '/mnt/kd/blocklists/blocked-hosts.netset')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Firewall IPv4 Blocklist Set</option>');
  }
  if (is_writable($file = '/mnt/kd/blocklists/blocked-hostsv6.netset')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Firewall IPv6 Blocklist Set</option>');
  }
  if (is_writable($file = '/mnt/kd/blocklists/whitelist.netset')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Firewall IPv4 Whitelist Set</option>');
  }
  if (is_writable($file = '/mnt/kd/blocklists/whitelistv6.netset')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Firewall IPv6 Whitelist Set</option>');
  }
  if (is_writable($file = '/mnt/kd/crontabs/root')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>crontabs/'.basename($file).' - Cron Jobs for root</option>');
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
  if (is_writable($file = '/mnt/kd/keepalived/keepalived.conf')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>keepalived/'.basename($file).' - Keepalived Config</option>');
  }
  if (is_writable($file = '/mnt/kd/unbound/unbound.conf')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>unbound/'.basename($file).' - DNS-TLS Unbound Config</option>');
  }
  if (is_writable($file = '/mnt/kd/dhcp6c.script')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - DHCPv6 Client Shell Script</option>');
  }
  if (is_writable($file = '/mnt/kd/tarsnap-backup.script')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Tarsnap Backup Shell Script</option>');
  }
  if (is_writable($file = '/mnt/kd/wan-failover.script')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - WAN Failover Shell Script</option>');
  }
  if (is_writable($file = '/mnt/kd/wan-failover-exit.script')) {
    $sel = ($file === $openfile) ? ' selected="selected"' : '';
    putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - WAN Failover Exit Shell Script</option>');
  }
  putHtml('</optgroup>');
  if (is_dir('/mnt/kd/lxc/container') && arrayCount($globfiles = glob('/mnt/kd/lxc/container/*/config')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; Linux Containers Configs &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        $lxc_name = basename(strstr($globfile, '/config', TRUE));
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.$lxc_name.' - Linux Container Config</option>');
      }
    }
    putHtml('</optgroup>');
  }
  if (is_dir('/mnt/kd/openvpn/ccd') && arrayCount($globfiles = glob('/mnt/kd/openvpn/ccd/*')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; OpenVPN Client Configs &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - X509 CN of OpenVPN Client</option>');
      }
    }
    putHtml('</optgroup>');
  }
  if (is_dir('/mnt/kd/ipsec/strongswan') && arrayCount($globfiles = glob('/mnt/kd/ipsec/strongswan/*')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; IPsec strongSwan Configs &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - IPsec strongSwan Config</option>');
      }
    }
    putHtml('</optgroup>');
  }
  if (is_dir('/mnt/kd/wireguard/peer') && arrayCount($globfiles = glob('/mnt/kd/wireguard/peer/*.peer')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; WireGuard VPN Configs &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - WireGuard VPN Peer Config</option>');
      }
    }
    if (is_dir('/mnt/kd/wireguard/peer/wg0.clients') && arrayCount($globfiles = glob('/mnt/kd/wireguard/peer/wg0.clients/*.peer')) > 0) {
      foreach ($globfiles as $globfile) {
        if (is_file($globfile) && is_writable($globfile)) {
          $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
          putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - WireGuard VPN Mobile Client Config</option>');
        }
      }
    }
    if (is_writable($file = '/mnt/kd/wireguard.script')) {
      $sel = ($file === $openfile) ? ' selected="selected"' : '';
      putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - WireGuard VPN Shell Script</option>');
    }
    putHtml('</optgroup>');
  }
  if (is_dir('/mnt/kd/avahi') && arrayCount($globfiles = glob('/mnt/kd/avahi/*')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; Avahi mDNS/DNS-SD Configs &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - Avahi Daemon Configuration</option>');
      }
    }
    if (is_dir('/mnt/kd/avahi/services') && arrayCount($globfiles = glob('/mnt/kd/avahi/services/*.service')) > 0) {
      foreach ($globfiles as $globfile) {
        if (is_file($globfile) && is_writable($globfile)) {
          $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
          putHtml('<option value="'.$globfile.'"'.$sel.'>services/'.basename($globfile).' - Avahi Service</option>');
        }
      }
    }
    putHtml('</optgroup>');
  }
  if (is_dir('/mnt/kd/ups') && arrayCount($globfiles = glob('/mnt/kd/ups/*.conf')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; UPS Monitoring Configs &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - NUT UPS Configuration</option>');
      }
    }
    if (is_writable($file = '/mnt/kd/ups/upsd.users')) {
      $sel = ($file === $openfile) ? ' selected="selected"' : '';
      putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - NUT UPS Configuration</option>');
    }
    putHtml('</optgroup>');
  }
  if (is_dir('/mnt/kd/monit/monit.d') && arrayCount($globfiles = glob('/mnt/kd/monit/monit.d/*.conf')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; Monit Monitoring Configs &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>monit.d/'.basename($globfile).' - Monit Configuration</option>');
      }
    }
    if (is_writable($file = '/mnt/kd/monit/monitrc')) {
      $sel = ($file === $openfile) ? ' selected="selected"' : '';
      putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - Monit Base Configuration</option>');
    }
    putHtml('</optgroup>');
  }
  if (is_dir('/mnt/kd/docs') && arrayCount($globfiles = glob('/mnt/kd/docs/*')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; Documentation &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - /mnt/kd/docs/ File</option>');
      }
    }
    putHtml('</optgroup>');
  }
  if (is_dir('/mnt/kd/fop2') && arrayCount($globfiles = glob('/mnt/kd/fop2/*.cfg')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; Flash Operating Panel2 Configs &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - Asterisk FOP2 Config</option>');
      }
    }
    if (is_writable($file = '/stat/var/packages/fop2/html/js/presence.js')) {
      $sel = ($file === $openfile) ? ' selected="selected"' : '';
      putHtml('<option value="'.$file.'"'.$sel.'>html/js/'.basename($file).' - FOP2 Global Options</option>');
    }
    putHtml('</optgroup>');
  }
  if (is_dir('/mnt/kd/kamailio') && arrayCount($globfiles = glob('/mnt/kd/kamailio/*.cfg')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; Kamailio Configs &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - Kamailio Configuration</option>');
      }
    }
    putHtml('</optgroup>');
  }
  if (is_dir('/mnt/kd/phoneprov/templates') && arrayCount($globfiles = glob('/mnt/kd/phoneprov/templates/*.conf')) > 0) {
    putHtml('<optgroup label="&mdash;&mdash;&mdash;&mdash; IP Phone Provisioning Templates &mdash;&mdash;&mdash;&mdash;">');
    foreach ($globfiles as $globfile) {
      if (is_file($globfile) && is_writable($globfile)) {
        $sel = ($globfile === $openfile) ? ' selected="selected"' : '';
        putHtml('<option value="'.$globfile.'"'.$sel.'>'.basename($globfile).' - IP Phone Template Config</option>');
      }
    }
    if (is_writable($file = '/mnt/kd/phoneprov-reload.script')) {
      $sel = ($file === $openfile) ? ' selected="selected"' : '';
      putHtml('<option value="'.$file.'"'.$sel.'>'.basename($file).' - PhoneProv Reload Shell Script</option>');
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

  putHtml('</select>');
  putHtml('</td><td width="20">&nbsp;</td><td style="text-align: left;">');
  putHtml('<input type="submit" class="formbtn" value="&gt;&gt;&nbsp;Open File" name="submit_open" />');
  if (getPREFdef($global_prefs, 'tab_fossil_show') === 'yes') {
    putHtml('<br /><br />');
    putHtml('<input type="submit" class="formbtn" value="Fossil Commands" name="submit_fossil" />');
  }
  putHtml('</td></tr></table>');

  if (($shortcut_str = getPREFdef($global_prefs, 'edit_text_shortcut_cmdstr')) !== '') {
    putHtml('<table width="100%" class="stdtable">');
    putHtml('<tr><td width="400" style="text-align: center;">');
    foreach (explode(' ', $shortcut_str) as $shortcut) {
      if ($shortcut !== '') {
        if (($pos = strpos($shortcut, '~')) !== FALSE) {
          $shortcut_label = substr($shortcut, $pos + 1);
          $shortcut = substr($shortcut, 0, $pos);
        } else {
          $shortcut_label = basename($shortcut);
        }
        putHtml('<a href="'.$myself.'?file='.$shortcut.'" class="headerText">'.htmlspecialchars($shortcut_label).'</a>');
      }
    }
    putHtml('</td></tr></table>');
  }
?>
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
        if (($line = fgets($ph, 1024)) != '') {
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
  if ((getPREFdef($global_prefs, 'disable_codemirror_editor')) !== 'yes') {
    putHtml('useCodeMirror("'.$openfile.'", "'.$cm_theme.'");');
  }
  putHtml('//]]>');
  putHtml('</script>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
