<?php

// Copyright (C) 2008-2022 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// status.inc for AstLinux
// 03-01-2007
// 01-15-2008, Added ntpq support
// 01-21-2008, Added core show channels support
// 02-13-2008, Improved ntpq display format
// 03-31-2008, Reworked system stats, ntpd and syslog sections
// 04-08-2008, Added Preferneces Support
// 04-10-2008, Added Password Warning
// 04-12-2008, Added PPPoE Status
// 04-18-2008, Changed System Time to use PHP date
// 08-10-2008, Added Disk Usage and GUI Version
// 08-30-2008, Allow status.php to be authenticated
// 02-11-2009, Added Firewall States
// 04-14-2009, Added OpenVPN Client Status
// 06-15-2009, Added IPsec Associations
// 11-06-2009, Added Reboot Scheduled notice
// 05-05-2011, Added DAHDI Status and Hardware Monitoring sections
// 08-10-2011, Added APC UPS Status
// 01-02-2012, Added Jabber Status
// 07-07-2012, Added UPnP Status
// 09-28-2012, Added Adaptive Ban Plugin Status
// 09-28-2012, Added Latest System Logs/Hide Log Words
// 09-28-2012, Added Custom Asterisk Command
// 01-20-2013, Added XMPP Server Status
// 01-03-2014, Added NUT UPS Monitoring Status
// 07-02-2014, Added "Show PPPoE Logs" button
// 09-04-2014, Added Kamailio SIP Server Status
// 10-28-2014, Added S.M.A.R.T Monitoring Status
// 11-06-2014, Added Failover Status
// 10-14-2016, Added Check for default admin password
// 12-01-2016, Added chronyc to replace ntpq
// 05-15-2017, Added IPv6 Prefixes/Address
// 07-16-2017, Added ACME Certificates
// 11-06-2017, Added WireGuard VPN Status
// 03-13-2018, Added Hardware system-vendor info
// 04-25-2018, Performance improvement using asynchronous system calls
// 11-23-2018, Improved WireGuard VPN Status
// 02-10-2019, Added DNSMASQ lease DB support
// 06-26-2019, Added ChangeLog display
// 02-21-2020, Remove PPTP VPN support
// 05-10-2020, Added Linux Containers (LXC)
// 07-08-2020, Added Firewall status indicator
// 01-05-2021, pppoe-status, accept return values of 0 and 1
// 02-04-2021, Remove IPsec (racoon) VPN support
// 04-22-2022, Added UEFI indicator
// 09-13-2022, Changed "Voicemail User", "DAHDI" and "Jabber" to custom Asterisk Name/Command status
//
// System location of OpenVPN Client logfile
$OVPNCLOGFILE = '/var/log/openvpnclient-status.log';
// System location of OpenVPN Server logfile
$OVPNLOGFILE = '/var/log/openvpn-status.log';
// System location of MINIUPNPD leases
$MINIUPNPDLEASES = '/var/db/upnp.leases';
// System location of DNSMASQ leases
$DNSMASQLEASES = '/var/db/dnsmasq.leases';
// System location of DNSMASQ lease DB's
$DNSMASQLEASEDB = '/var/db/dnsmasq-lease.db';
$DNSMASQLEASE6DB = '/var/db/dnsmasq-lease6.db';
// System location of nf_conntrack file
$CONNTRACK = '/proc/net/nf_conntrack';
//
// -- openvpn.conf snippet --
// status-version 2
// status /var/log/openvpn-status.log
// -- end snippet --
//

$myself = $_SERVER['PHP_SELF'];

require_once $COMMON.'functions.php';

require_once $COMMON.'version.php';

if (getPREFdef($global_prefs, 'status_require_auth') === 'yes') {
  if (! $STATUS_AUTH) {
    header('Location: /admin/status.php');
    exit;
  }
}

// Function: getDaemons
// Find running daemons
//
function getDaemons() {

  $status['asterisk'] = 0;
  $status['chronyd'] = 0;
  $status['miniupnpd'] = 0;
  $status['dnsmasq'] = 0;
  $status['openvpn'] = 0;
  $status['charon'] = 0;
  $status['upsmon'] = 0;
  $status['syslogd'] = 0;
  $status['zabbix_agentd'] = 0;
  $status['zabbix_proxy'] = 0;
  $status['kamailio'] = 0;
  $status['reboot'] = 0;

  $output = array();
  @exec('ps -o comm=""', $output);

  foreach ($output as $line) {
    foreach ($status as $key => $value) {
      if ($key === $line) {
        $status["$key"]++;
        break;
      }
    }
  }
  return($status);
}

// Function: outputASTERISKcommand
// Retain embedded empty lines
//
function outputASTERISKcommand($filename) {

  $ph = @fopen($filename, "r");
  $valid_line = FALSE;
  $empty_lines = 0;
  while (! feof($ph)) {
    $line = trim(fgets($ph, 1024));
    if (strncasecmp($line, 'verbosity ', 10) && stripos($line, 'command is deprecated') === FALSE) {
      if ($line !== '') {
        if ($valid_line) {
          while ($empty_lines > 0) {
            putText('');
            $empty_lines--;
          }
        }
        putText($line);
        $valid_line = TRUE;
      } else {
        $empty_lines++;
      }
    }
  }
  fclose($ph);
}

// Function: parseUPSstatus
//
function parseUPSstatus($log) {

  $cid = 0;
  if (($ph = @fopen($log, "r")) !== FALSE) {
    while (! feof($ph)) {
      if (($line = rtrim(fgets($ph, 1024))) !== '') {
        if (preg_match('/^([a-z][a-z.]*)[ :]*(.*)$/', $line, $ips)) {
          switch ($ips[1]) {
          case 'ups.model':
          case 'ups.status':
          case 'input.voltage':
          case 'ups.load':
          case 'battery.charge':
          case 'battery.runtime':
            $status['status'][$cid]["$ips[1]"] = $ips[2];
            break;
          }
        }
      }
    }
    fclose($ph);
  }
  return(isset($status) ? $status : NULL);
}

// Function: parseFIREWALLstates
//
function parseFIREWALLstates(&$status, $log, $max) {
  global $global_prefs;

  $proto = array (
    1  => 'ICMP',
    6  => 'TCP',
    17 => 'UDP',
    41 => '6to4',
    47 => 'GRE',
    50 => 'ESP',
    51 => 'AH'
  );
  if (($str = getPREFdef($global_prefs, 'status_firewall_sports_cmdstr')) !== '') {
    $hide_sports = explode(' ', $str);
  }
  if (($str = getPREFdef($global_prefs, 'status_firewall_dports_cmdstr')) !== '') {
    $hide_dports = explode(' ', $str);
  }
  $cid = 0;
  if (($ph = @fopen($log, "r")) !== FALSE) {
    while (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) !== '') {
        $linetokens = preg_split('/ +/', $line);
        if (isset($linetokens[3]) && isset($proto[$linetokens[3]])) {
          $bytes = 0;
          $packets = 0;
          $src = '';
          $sport = '';
          $dst = '';
          $dport = '';
          $protocol = $proto[$linetokens[3]];
          $ttl = $linetokens[4];
          $pairs = array_slice($linetokens, 5);
          foreach ($pairs as $pair) {
            if (strncmp($pair, 'src=', 4) == 0) {
              if ($src === '') {
                $src = compressIPV6addr(substr($pair, 4));
              }
            } elseif (strncmp($pair, 'dst=', 4) == 0) {
              if ($dst === '') {
                $dst = compressIPV6addr(substr($pair, 4));
              }
            } elseif (strncmp($pair, 'sport=', 6) == 0) {
              if ($sport === '') {
                $sport = substr($pair, 6);
                if (isset($hide_sports)) {
                  if (in_array($sport, $hide_sports)) {
                    continue 2; // next line
                  }
                }
              }
            } elseif (strncmp($pair, 'dport=', 6) == 0) {
              if ($dport === '') {
                $dport = substr($pair, 6);
                if (isset($hide_dports)) {
                  if (in_array($dport, $hide_dports)) {
                    continue 2; // next line
                  }
                }
              }
            } elseif (strncmp($pair, 'bytes=', 6) == 0) {
              $bytes += (float)substr($pair, 6);
            } elseif (strncmp($pair, 'packets=', 8) == 0) {
              $packets += (float)substr($pair, 8);
            }
          }

          $unique = TRUE;
          $xid = $max;
          $xbytes = $bytes;
          for ($i = 0; $i < $cid; $i++) {
            if ($status['states'][$i]['src'] === $src &&
                $status['states'][$i]['dst'] === $dst &&
                $status['states'][$i]['dport'] === $dport &&
                $status['states'][$i]['proto'] === $protocol) {
              $status['states'][$i]['nsports']++;
              // replace match if newer
              if ($status['states'][$i]['ttl'] < $ttl) {
                $status['states'][$i]['ttl'] = $ttl;
                $status['states'][$i]['bytes'] = $bytes;
                $status['states'][$i]['packets'] = $packets;
                $status['states'][$i]['sport'] = $sport;
              }
              $unique = FALSE;
              break;
            } elseif ($status['states'][$i]['bytes'] < $xbytes) {
              $xid = $i;
              $xbytes = $status['states'][$i]['bytes'];
            }
          }
          if ($unique) {
            if ($cid < $max) {
              $status['states'][$cid]['bytes'] = $bytes;
              $status['states'][$cid]['packets'] = $packets;
              $status['states'][$cid]['src'] = $src;
              $status['states'][$cid]['sport'] = $sport;
              $status['states'][$cid]['nsports'] = 1;
              $status['states'][$cid]['dst'] = $dst;
              $status['states'][$cid]['dport'] = $dport;
              $status['states'][$cid]['proto'] = $protocol;
              $status['states'][$cid]['ttl'] = $ttl;
              $cid++;
            } elseif ($xid < $max) {
              $status['states'][$xid]['bytes'] = $bytes;
              $status['states'][$xid]['packets'] = $packets;
              $status['states'][$xid]['src'] = $src;
              $status['states'][$xid]['sport'] = $sport;
              $status['states'][$xid]['nsports'] = 1;
              $status['states'][$xid]['dst'] = $dst;
              $status['states'][$xid]['dport'] = $dport;
              $status['states'][$xid]['proto'] = $protocol;
              $status['states'][$xid]['ttl'] = $ttl;
            }
          }
        }
      }
    }
    fclose($ph);
  }
  // Sort by decending Bytes
  unset($bytes);
  if ($cid > 1) {
    foreach ($status['states'] as $key => $row) {
      $bytes[$key] = $row['bytes'];
    }
    array_multisort($bytes, SORT_DESC, SORT_NUMERIC, $status['states']);
  }
}

// Function: isDHCPactive
//
function isDHCPactive() {

  shell('grep -q "^dhcp-leasefile=" /etc/dnsmasq.conf 2>/dev/null || grep -q "^dhcp-leasefile=" /etc/dnsmasq.static 2>/dev/null', $status);

  return($status == 0);
}

// Function: noASTURWstorage
//
function noASTURWstorage() {
  global $MNT_ASTURW_DIR;

  shell('mount 2>/dev/null | grep -q "'.$MNT_ASTURW_DIR.'"', $status);

  return($status != 0);
}

// Function: adminDEFAULTpassword
//
function adminDEFAULTpassword() {

  $status = 0;

  if (($HTPASSWD = getPASSWDlocation()) !== '') {
    if (is_file($HTPASSWD)) {
      shell("/usr/sbin/check-default-passwd admin '$HTPASSWD' >/dev/null 2>/dev/null", $status);
    }
  }
  return($status == 0);
}

// Function: noASTERISKsounds
//
function noASTERISKsounds($asterisk) {
  $status = 0;

  if ($asterisk > 0) {
    shell("find /var/lib/asterisk/sounds -name 'vm-*' 2>/dev/null | grep -q '/vm-'", $status);
  }

  return($status != 0);
}

// Function: DNS_Firewall_add_columns_html
//
function DNS_Firewall_add_columns_html(&$first) {

  if ($first) {
    if (isFIREWALL()) {
      $str = 'Enabled';
      $color = 'green';
    } else {
      $str = 'Disabled';
      $color = 'red';
    }
    echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'Firewall:', '</td>';
    echo '<td style="text-align: left; color: '.$color.';">', $str, '</td>';
    $first = FALSE;
  } else {
    echo '<td>&nbsp;</td><td>&nbsp;</td>';
  }
}

// Function: getIPV6address
//
function getIPV6address() {

  if (! is_file('/proc/net/if_inet6')) {
    return(NULL);
  }

  $cmd  = '. /etc/rc.conf';
  $cmd .= ';/sbin/ip -6 -o addr show dev "$EXTIF" scope global 2>/dev/null';
  $cmd .= ' | awk \'$3 == "inet6" { split($4, field, "/"); print field[1]; nextfile; }\'';
  $cmd .= ';echo "#next#"';
  $cmd .= ';/sbin/ip -6 -o addr show scope global 2>/dev/null';
  $cmd .= ' | awk \'$2 != "\'"$EXTIF"\'" && $2 != "\'"$EXT2IF"\'" && $3 == "inet6" { print $4; }\'';

  $output = array();
  @exec($cmd, $output);

  $status['address'] = '';

  $state = 0;
  $pnum = 0;
  foreach ($output as $line) {
    if ($line[0] === '#') {
      $state++;
      continue;
    }
    if ($state == 0) {          // IPv6 Address
      $status['address'] = $line;
    } elseif ($state == 1) {    // IPv6 Prefixes
      if (preg_match('/^[0-9a-fA-F:]+\/([0-9]+)/', $line, $match)) {
        if ($match[1] >= 32 && $match[1] <= 64) {
          $status['prefixes'][$pnum] = $line;
          $pnum++;
        }
      }
    }
  }
  return($status);
}

// Function: parseMINIUPNPDleases
//
function parseMINIUPNPDleases($log) {
  $cid = 0;
  if (($ph = @fopen($log, "r")) !== FALSE) {
    while (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) !== '') {
        $linetokens = explode(':', $line);
        if (isset($linetokens[4])) {
          $status['clients'][$cid]['proto'] = $linetokens[0];
          $status['clients'][$cid]['p_port'] = $linetokens[1];
          $status['clients'][$cid]['l_ip'] = $linetokens[2];
          $status['clients'][$cid]['l_port'] = $linetokens[3];
          $status['clients'][$cid]['expire'] = $linetokens[4];
          $status['clients'][$cid]['desc'] = $linetokens[5];
          $cid++;
        }
      }
    }
    fclose($ph);
  }
  // Sort by Public Port
  if ($cid > 1) {
    foreach ($status['clients'] as $key => $row) {
      $p_port[$key] = $row['p_port'];
    }
    array_multisort($p_port, SORT_ASC, SORT_STRING, $status['clients']);
  }
  return(isset($status) ? $status : NULL);
}

// Function: parseDNSMASQleaseDB
//
function parseDNSMASQleaseDB($log, $ip6, $dhcp = NULL) {

  if (! is_file($log)) {
    return(NULL);
  }

  $cid = 0;
  if (($ph = @fopen($log, "r")) !== FALSE) {
    while (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) !== '') {
        $linetokens = explode('~', $line, 8);
        if (isset($linetokens[4]) && ($linetokens[4] > 0)) {
          $status['clients'][$cid]['mac'] = $ip6 ? $linetokens[2] : $linetokens[0];
          $status['clients'][$cid]['ip'] = $linetokens[1];
          // Interface is [3]
          $status['clients'][$cid]['time'] = $linetokens[4];
          $status['clients'][$cid]['hostname'] = $linetokens[5];
          $status['clients'][$cid]['vendor'] = $linetokens[6];
          $clientname = $linetokens[7];
          if (! is_null($dhcp) && $clientname == '' && (($mac = $status['clients'][$cid]['mac']) != '')) {
            foreach ($dhcp['clients'] as $key => $row) {
              if ($mac === $row['mac']) {
                $clientname = $row['clientname'];
                break;
              }
            }
          }
          $status['clients'][$cid]['clientname'] = $clientname;
          $cid++;
        }
      }
    }
    fclose($ph);
  }
  // Sort by IP Address
  if ($cid > 1) {
    foreach ($status['clients'] as $key => $row) {
      $ip[$key] = $ip6 ? expandIPV6addr($row['ip']) : pad_ipv4_str($row['ip']);
    }
    array_multisort($ip, SORT_ASC, SORT_STRING, $status['clients']);
  }
  return(isset($status) ? $status : NULL);
}

// Function: parseDNSMASQleases
//
function parseDNSMASQleases($log) {
  $cid = 0;
  $cur_time = time();
  if (($ph = @fopen($log, "r")) !== FALSE) {
    while (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) !== '') {
        $linetokens = explode(' ', $line);
        if (isset($linetokens[0]) && ($linetokens[0] > $cur_time)) {
          $status['clients'][$cid]['expire'] = $linetokens[0];
          $status['clients'][$cid]['mac'] = $linetokens[1];
          $status['clients'][$cid]['ip'] = $linetokens[2];
          $status['clients'][$cid]['name'] = $linetokens[3];
          $cid++;
        }
      }
    }
    fclose($ph);
  }
  // Sort by IP Address
  if ($cid > 1) {
    foreach ($status['clients'] as $key => $row) {
      $ip[$key] = pad_ipv4_str($row['ip']);
    }
    array_multisort($ip, SORT_ASC, SORT_STRING, $status['clients']);
  }
  return(isset($status) ? $status : NULL);
}

// Function: parseOpenVPNClog
// Read OpenVPN Client status (version 1) file
// /etc/openvpnclient.conf must include:
// status-version 1
// status /var/log/openvpnclient-status.log
//
function parseOpenVPNClog($log) {

  if (($ph = @fopen($log, "r")) !== FALSE) {
    while (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) !== '') {
        $linetokens = explode(',', $line);
        if (strncasecmp($linetokens[0], 'UPDATED', 7) == 0) {
          $status['updated'] = $linetokens[1];
        } elseif (strncasecmp($linetokens[0], 'TCP/UDP READ', 12) == 0) {
          $status['BytesRcv'] = $linetokens[1];
        } elseif (strncasecmp($linetokens[0], 'TCP/UDP WRITE', 13) == 0) {
          $status['BytesSnd'] = $linetokens[1];
        } elseif (strncasecmp($linetokens[0], 'AUTH READ', 9) == 0) {
          $status['BytesAuth'] = $linetokens[1];
        }
      }
    }
    fclose($ph);
    $remote = trim(shell_exec('awk \'/^remote / { print $2, $3; nextfile; }\' /etc/openvpnclient.conf 2>/dev/null'));
    if ($remote !== '') {
      $status['remote'] = $remote;
    }
  }
  return(isset($status) ? $status : NULL);
}

// Function: parseOpenVPNlog
// Read OpenVPN Server status (version 2) file
// /etc/openvpn.conf must include:
// status-version 2
// status /var/log/openvpn-status.log
//
function parseOpenVPNlog($log) {
  $cid = 0;
  if (($ph = @fopen($log, "r")) !== FALSE) {
    while (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) !== '') {
        $linetokens = explode(',', $line);
        if ($linetokens[0] === "TITLE") {
          $status['title'] = $linetokens[1];
        } elseif ($linetokens[0] === "TIME") {
          $status['time'] = $linetokens[1];
        } elseif ($linetokens[0] === "HEADER" && $linetokens[1] === "CLIENT_LIST") {
          $status['header']['CommonName'] = $linetokens[2];
          $status['header']['RealAddr'] = $linetokens[3];
          $status['header']['VirtAddr'] = $linetokens[4];
          $status['header']['Virt6Addr'] = $linetokens[5];
          $status['header']['BytesRcv'] = $linetokens[6];
          $status['header']['BytesSnd'] = $linetokens[7];
          $status['header']['Since'] = $linetokens[8];
        } elseif ($linetokens[0] === "CLIENT_LIST") {
          $status['clients'][$cid]['CommonName'] = $linetokens[1];
          $status['clients'][$cid]['RealAddr'] = $linetokens[2];
          $status['clients'][$cid]['VirtAddr'] = $linetokens[3];
          $status['clients'][$cid]['Virt6Addr'] = $linetokens[4];
          $status['clients'][$cid]['BytesRcv'] = $linetokens[5];
          $status['clients'][$cid]['BytesSnd'] = $linetokens[6];
          $status['clients'][$cid]['Since'] = $linetokens[7];
          $cid++;
        }
      }
    }
    fclose($ph);
  }
  return(isset($status) ? $status : NULL);
}

// Function: parseWireGuard
//
function parseWireGuard($data) {

  $cur_time = time();
  if (isset($data['handshake'])) {
    foreach($data['handshake'] as $line) {
      if (preg_match('/^([^ \t]+)[ \t]*([0-9]+).*$/', $line, $ips)) {
        // Stale if current Unix Epoch is greater than 3600 seconds of lastest-handshake
        // Inactive if current Unix Epoch is greater than 135 seconds of lastest-handshake
        $delta_time = $cur_time - (int)$ips[2];
        if ($delta_time > 3600) {
          $status['activity']["$ips[1]"] = 'stale';
        } elseif ($delta_time > 135) {
          $status['activity']["$ips[1]"] = 'inactive';
        } else {
          $status['activity']["$ips[1]"] = 'active';
        }
      }
    }
  }
  if (isset($data['clients'])) {
    foreach($data['clients'] as $line) {
      $linetokens = explode(':', $line, 2);
      if ($linetokens[0] !== '' && $linetokens[1] !== '') {
        if (preg_match('/^PublicKey *= *([^ ]+).*$/i', $linetokens[1], $ips)) {
          $client = basename($linetokens[0], '.peer');
          $pub_key = $ips[1];
          // Use only the first unique PublicKey encountered
          if (! isset($status['client']["$pub_key"])) {
            $status['client']["$pub_key"] = $client;
          }
        }
      }
    }
  }
  $peer = '';
  if (isset($data['wg_show'])) {
    foreach($data['wg_show'] as $line) {
      if ($line === '') {
        continue;
      }
      if ($line[0] !== ' ') {
        if (preg_match('/^peer:[ \t]*([^ \t]+).*$/', $line, $ips)) {
          $id = ($peer !== '') ? $id + 1 : 0;
          $peer = $ips[1];
          $status['peers'][$id]['peer'] = isset($status['client']["$peer"]) ? $status['client']["$peer"] : substr($peer, 0, 6);
          $status['peers'][$id]['activity'] = isset($status['activity']["$peer"]) ? $status['activity']["$peer"] : 'unknown';
        }
      } elseif ($peer !== '') {
        if (preg_match('/^ +([^:]+): *(.+)$/', $line, $ips)) {
          if ($ips[1] === 'endpoint') {
            $status['peers'][$id]['endpoint'] = $ips[2];
            if ($status['peers'][$id]['activity'] !== 'stale') {
              $status['info']['endpoint'] = TRUE;
            }
          } elseif ($ips[1] === 'transfer') {
            $status['peers'][$id]['transfer'] = $ips[2];
          } elseif ($ips[1] === 'latest handshake') {
            $status['peers'][$id]['handshake'] = $ips[2];
          } elseif ($ips[1] === 'allowed ips') {
            $status['peers'][$id]['allowedips'] = $ips[2];
          }
        }
      }
    }
  }
  return($status);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (isset($_POST['submit_pppoe'])) {
    $result = restartPROCESS('pppoe', 10, 99);
  } elseif (isset($_POST['show_pppoe_logs'])) {
    header('Location: '.$myself.'?show_pppoe_logs');
    exit;
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'all';
require_once $COMMON.'header.php';

$daemon = getDaemons();

if (is_file('/var/lock/lxc.lock')) {
  if (getPREFdef($global_prefs, 'status_linux_containers') !== 'no') {
    $cmd = '/usr/bin/lxc-ls -f -F "NAME,STATE,AUTOSTART,RAM,INTERFACE,IPV4,IPV6"';
    $status_linux_containers = @popen($cmd, 'r');
  }
}
if (getPREFdef($global_prefs, 'status_disk_usage') !== 'no') {
    $cmd = 'df -h /tmp | grep "^ *[A-Z][a-z]"';
    $cmd .= '; df -h | grep "^/dev/[sv]d[a-h][0-9]" | sort -k 1,1';
    $status_disk_usage = @popen($cmd, 'r');
}
if (is_dir('/mnt/kd/acme')) {
  if (getPREFdef($global_prefs, 'status_show_acme_certificates') === 'yes') {
    $cmd = '/usr/sbin/acme-client --list';
    $status_show_acme_certificates = @popen($cmd, 'r');
  }
}
if ($daemon['chronyd'] > 0) {
  if (getPREFdef($global_prefs, 'status_ntp_sessions') !== 'no') {
    // use "chronyc -N sources" if supported
    $cmd = 'if chronyc -N -v >/dev/null 2>&1; then chronyc -N sources; else chronyc sources; fi';
    $status_ntp_sessions = @popen($cmd, 'r');
  }
}
if ($daemon['charon'] > 0) {
  if (getPREFdef($global_prefs, 'status_ipsec_associations') !== 'no') {
    $cmd = '/usr/sbin/ipsec status';
    $status_ipsec_associations = @popen($cmd, 'r');
  }
}
if (is_file('/var/lock/wireguard.lock')) {
  if (getPREFdef($global_prefs, 'status_wireguard_vpn') !== 'no') {
    $cmd = '. /etc/rc.conf; wg_if="${WIREGUARD_IF:-wg0}"';
    $cmd .= '; /usr/bin/wg show $wg_if latest-handshakes';
    $cmd .= '; echo "#next#"';
    $cmd .= '; awk \'/^\[[Pp]eer\]/{p=1; next} /^\[/{p=0; next} !/^[Pp]ublic[Kk]ey *= *[^ ][^ ].*$/{next} p==1{printf "%s:", FILENAME} p\' /mnt/kd/wireguard/peer/$wg_if.clients/*.peer';
    $cmd .= '; echo "#next#"';
    $cmd .= '; /usr/bin/wg show $wg_if | sed -n \'/^peer/,$ p\'';
    $status_wireguard_vpn = @popen($cmd, 'r');
  }
}
if (is_file('/var/run/prosody/prosody.pid')) {
  if (getPREFdef($global_prefs, 'status_show_xmpp_server') !== 'no') {
    $cmd = '. /etc/rc.conf; if [ "$XMPP_ENABLE_S2S" = "yes" ]';
    $cmd .= "; then /usr/bin/prosodycmd 'server:version()' 'server:uptime()' 'c2s:show()' 's2s:show()'";
    $cmd .= "; else /usr/bin/prosodycmd 'server:version()' 'server:uptime()' 'c2s:show()'";
    $cmd .= '; fi';
    $status_show_xmpp_server = @popen($cmd, 'r');
  }
}
if (is_file('/var/lock/aif_adaptive_ban.lock')) {
  if (getPREFdef($global_prefs, 'status_show_adaptive_ban') === 'yes') {
    $cmd = '. /etc/rc.conf; if [ "$IPV6" = "yes" ]; then ipv6="1"; else ipv6="0"; fi';
    $cmd .= '; /usr/share/arno-iptables-firewall/plugins/adaptive-ban-helper status /usr/sbin/iptables /usr/sbin/ip6tables $ipv6';
    $status_show_adaptive_ban = @popen($cmd, 'r');
  }
}
if (is_executable('/usr/bin/sensors')) {
  if (getPREFdef($global_prefs, 'status_show_hardware_monitoring') === 'yes') {
    $cmd = '/usr/bin/sensors';
    $status_show_hardware_monitoring = @popen($cmd, 'r');
  }
}
if (is_executable('/usr/sbin/smart-status')) {
  if (getPREFdef($global_prefs, 'status_show_smart_monitoring') === 'yes') {
    $cmd = '/usr/sbin/smart-status attributes';
    $status_show_smart_monitoring = @popen($cmd, 'r');
  }
}
if ($daemon['syslogd'] > 0) {
  if (getPREFdef($global_prefs, 'status_show_system_logs') !== 'no') {
    if (($str = getPREFdef($global_prefs, 'status_exclude_logs_cmdstr')) !== '') {
      $exclude_words = explode(' ', $str);
      $exclude_logs = '';
      foreach ($exclude_words as $exclude_word) {
        if ($exclude_word !== '') {
          $exclude_word = strtr($exclude_word, '$`[]\'\\', '......');   // map special chars to dot
          $exclude_logs .= " -e '$exclude_word'";
        }
      }
      $cmd = 'grep -v -i '.$exclude_logs.' /var/log/messages | tail -n 30';
    } else {
      $cmd = 'tail -n 30 /var/log/messages';
    }
    $status_show_system_logs = @popen($cmd, 'r');
  }
}

putHtml("<center>");
if (isset($_GET['result'])) {
  $result = $_GET['result'];
  if ($result == 10) {
    putHtml('<p style="color: green;">PPPoE has Restarted.</p>');
  } elseif ($result == 99) {
    putHtml('<p style="color: red;">PPPoE Restart Failed.</p>');
  } else {
    putHtml('<p style="color: orange;">No Action.</p>');
  }
} elseif (noASTURWstorage()) {
  putHtml('<p style="color: red;">Notice: No Persistent File Storage, click <a href="/admin/setup.php" class="headerText">Installation Setup</a></p>');
} elseif (adminDEFAULTpassword()) {
  putHtml('<p style="color: red;">Notice: Management Password is a known default, click <a href="/admin/system.php" class="headerText">System</a>to change the "admin" password.</p>');
} elseif (noASTERISKsounds($daemon['asterisk'])) {
  putHtml('<p style="color: red;">Notice: No Core Asterisk Sounds, click <a href="/admin/system.php" class="headerText">System</a>then via "Asterisk Sounds Packages", Upgrade/Install "core" and "moh" sounds.</p>');
} else {
  putHtml("<p>&nbsp;</p>");
}
putHtml("</center>");

$i = 0;
putHtml('<center><table width="100%" class="status"><tr><td><center>');
putHtml('<table width="100%" class="datatable">');
// Hostname
echo '<tr ', ($i++ % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'Hostname:', '</td>';
echo '<td style="text-align: left;">', $_SERVER['SERVER_NAME'], '</td>';
// IP Address
$line = trim(shell_exec('. /etc/rc.conf;/sbin/ip -o addr show dev "$EXTIF" 2>/dev/null | awk \'$3 == "inet" { split($4, field, "/"); print field[1]; }\''));
if ($line !== '') {
  echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'IPv4 Address:', '</td>';
  echo '<td style="text-align: left;">', $line, '</td>';
} else {
  echo '<td>&nbsp;</td><td>&nbsp;</td>';
}
putHtml('</tr>');
// IPv6
if (! is_null($ipv6_addr = getIPV6address())) {
  echo '<tr ', ($i++ % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
  if ((arrayCount($ipv6_addr['prefixes'])) > 0) {
    echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'IPv6 Prefixes:', '</td>';
    echo '<td style="text-align: left;">';
    foreach ($ipv6_addr['prefixes'] as $line) {
      echo $line, '<br />';
    }
    echo '</td>';
  } else {
    echo '<td>&nbsp;</td><td>&nbsp;</td>';
  }
  echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'IPv6 Address:', '</td>';
  echo '<td style="text-align: left;">', ($ipv6_addr['address'] !== '' ? $ipv6_addr['address'] : 'Link-local'), '</td>';
  putHtml('</tr>');
}

// DNS
$first_line = TRUE;
if (isDNS_TLS() || isDNSCRYPT()) {
  echo '<tr ', ($i++ % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
  echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'DNS:', '</td>';
  echo '<td style="text-align: left;">', (isDNS_TLS() ? 'DNS-TLS' : 'DNSCrypt').' Proxy Server Enabled', '</td>';
  DNS_Firewall_add_columns_html($first_line);
  putHtml('</tr>');
} else {
  $rconf = is_file('/etc/resolv-extern.conf') ? '/etc/resolv-extern.conf' : '/etc/resolv.conf';
  if (($fp = @fopen($rconf, 'r')) !== FALSE) {
    while (! feof($fp)) {
      if (($line = trim(fgets($fp, 1024))) !== '') {
        echo '<tr ', ($i++ % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
        echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'DNS:', '</td>';
        echo '<td style="text-align: left;">', $line, '</td>';
        DNS_Firewall_add_columns_html($first_line);
        putHtml('</tr>');
      }
    }
    fclose($fp);
  }
}
unset($first_line);

// System Time
echo '<tr ', ($i++ % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'System Time:', '</td>';
echo '<td style="text-align: left;">', date('l, M d H:i:s T Y'), '</td>';
$line = trim(shell_exec('grep "^Mem[TA]" /proc/meminfo 2>/dev/null'));
$mstr = '';
if (($pos = strpos($line, 'MemTotal:')) !== FALSE) {
  $mem = (int)(((int)trim(substr($line, $pos + 9)) + 512)/1024);
  $mstr .= $mem.' MB, ';
}
if (($pos = strpos($line, 'MemAvailable:')) !== FALSE) {
  $mem = (int)(((int)trim(substr($line, $pos + 13)) + 512)/1024);
  $mstr .= 'Available '.$mem.' MB';
}
echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'RAM Memory:', '</td>';
echo '<td style="text-align: left;">', $mstr, '</td>';
putHtml('</tr>');
// uptime
$line = trim(shell_exec('uptime'));
if (preg_match('/^.*up *(.*)load average: *(.*)/i', $line, $matches)) {
  $delayreboot = ($daemon['reboot'] > 0) ? ' - REBOOT SCHEDULED' : '';
  echo '<tr ', ($i++ % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
  echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'System Uptime:', '</td>';
  echo '<td style="text-align: left;">', rtrim($matches[1], ', ').$delayreboot, '</td>';
  echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'Load Average:', '</td>';
  echo '<td style="text-align: left;">', $matches[2], '</td>';
  putHtml('</tr>');
}
// astlinux-release
echo '<tr ', ($i++ % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'AstLinux Release:', '</td>';
echo '<td style="text-align: left;">';
if (($fp = @fopen('/etc/astlinux-release', 'r')) !== FALSE) {
  if (! feof($fp)) {
    $line = trim(fgets($fp, 1024)).' '.trim(shell_exec('uname -m'));
    if ($daemon['asterisk'] > 0) {
      $line .= ' - '.trim(shell_exec('/usr/sbin/asterisk -V'));
    }
    echo $line;
  }
  fclose($fp);
}
putHtml('</td>');
if (is_file('/oldroot/cdrom/runnix')) {
  echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'Runnix Release:', '</td>';
  echo '<td style="text-align: left;">';
  $line = '';
  if (($fp = @fopen('/oldroot/cdrom/ver', 'r')) !== FALSE) {
    if (! feof($fp)) {
      $line = trim(fgets($fp, 1024));
    }
    fclose($fp);
  } else {
    $line = 'runnix-0.2.1';
  }
  if (is_file('/stat/etc/docs/ChangeLog.txt')) {
    echo $line, '&nbsp;&nbsp;&ndash;&nbsp;&nbsp;';
    echo '<a href="/info.php?topic=ChangeLog" target="_blank" class="headerText">ChangeLog</a>', '</td>';
  } else {
    echo $line, '</td>';
  }
} else {
  echo '<td>&nbsp;</td><td>&nbsp;</td>';
}
putHtml('</tr>');

$license = (getPREFdef($global_prefs, 'status_require_auth') === 'yes') ? '/admin/license.php' : '/license.php';
if (is_file('/etc/system-vendor')) {
  // hardware
  echo '<tr ', ($i++ % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
  echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'Hardware:', '</td>';
  echo '<td style="text-align: left;">', htmlspecialchars(trim(@file_get_contents('/etc/system-vendor'))).(is_dir('/sys/firmware/efi') ? ' - UEFI' : ''), '</td>';
  // gui version - license
  echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'GUI Version:', '</td>';
  echo '<td style="text-align: left;">', $GUI_VERSION, '&nbsp;&nbsp;&ndash;&nbsp;&nbsp;';
  echo '<a href="'.$license.'" class="headerText">Show Licenses</a>', '</td>';
  putHtml('</tr>');
} else {
  // gui version
  echo '<tr ', ($i++ % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
  echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'GUI Version:', '</td>';
  echo '<td style="text-align: left;">', $GUI_VERSION, '</td>';
  // license
  echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'License:', '</td>';
  echo '<td style="text-align: left;">', '<a href="'.$license.'" class="headerText">Show Licenses</a>', '</td>';
  putHtml('</tr>');
}
// end of block
putHtml('</table>');
putHtml('</center></td></tr></table></center>');

if (is_file('/etc/ppp/pppoe.conf')) {
  if (getPREFdef($global_prefs, 'status_pppoe_connection') !== 'no') {
    putHtml('<form method="post" action="'.$myself.'">');
    putHtml("<h2>PPPoE Connection Status:");
    putHtml('<input type="submit" value="Restart PPPoE" name="submit_pppoe" />');
    if (! isset($_GET['show_pppoe_logs'])) {
      putHtml('<input type="submit" value="Show PPPoE Logs" name="show_pppoe_logs" />');
    }
    putHtml("</h2>");
    putHtml("</form>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    shell('/usr/sbin/pppoe-status >'.$tmpfile, $status);
    if ($status == 0 || $status == 1) {
      $ph = @fopen($tmpfile, "r");
      while (! feof($ph)) {
        if (($line = trim(fgets($ph, 1024))) !== '') {
          putText($line);
        }
      }
      fclose($ph);
    } else {
      putText('No pppoe-status available.');
    }
    if (isset($_GET['show_pppoe_logs'])) {
      putText('');
      putText('Recent PPPoE Logs:');
      putText('==================');
      $cmd = 'grep -e "pppd\[[0-9]*]:" /var/log/messages';
      $cmd .= ' | grep -v -i -e "pado packets" -e "pppoe discovery"';
      $cmd .= ' | tail -n 30';
      shell($cmd.' >'.$tmpfile, $status);

      $ph = @fopen($tmpfile, "r");
      while (! feof($ph)) {
        if (($line = trim(fgets($ph, 1024))) !== '') {
          putText($line);
        }
      }
      fclose($ph);
    }
    @unlink($tmpfile);
    putHtml("</pre>");
  }
}

if (isset($status_linux_containers)) {
  if ($status_linux_containers !== FALSE) {
    putHtml("<h2>Linux Containers:</h2>");
    putHtml("<pre>");

    $valid_line = FALSE;
    while (! feof($status_linux_containers)) {
      if (($line = rtrim(fgets($status_linux_containers, 1024))) !== '') {
        putText($line);
        $valid_line = TRUE;
      }
    }
    pclose($status_linux_containers);

    if (! $valid_line) {
      putText('No Linux Containers have been created');
    }
    putHtml("</pre>");
  }
}

if (is_file('/etc/zabbix_agentd.conf')) {
  putHtml("<h2>Zabbix Monitoring Status:</h2>");
  putHtml("<pre>");
  putText('Zabbix Agent: '.($daemon['zabbix_agentd'] == 0 ? 'ERROR - No' : $daemon['zabbix_agentd']).' active processes');
  if (is_file('/etc/zabbix_proxy.conf')) {
    putText('Zabbix Proxy: '.($daemon['zabbix_proxy'] == 0 ? 'ERROR - No' : $daemon['zabbix_proxy']).' active processes');
  }
  putHtml("</pre>");
}

if (isset($status_disk_usage)) {
  if ($status_disk_usage !== FALSE) {
    putHtml("<h2>Disk Usage:</h2>");
    putHtml("<pre>");

    while (! feof($status_disk_usage)) {
      if (($line = rtrim(fgets($status_disk_usage, 1024))) !== '') {
        putText($line);
      }
    }
    pclose($status_disk_usage);

    putHtml("</pre>");
  }
}

if (is_file('/var/run/wan-failover.pid') && is_file('/var/log/wan-failover-status.log')) {
  if (getPREFdef($global_prefs, 'status_show_wan_failover') !== 'no') {
    putHtml("<h2>WAN Failover Status:</h2>");
    putHtml("<pre>");

    $output = array();
    @exec('cat /var/log/wan-failover-status.log', $output);
    foreach ($output as $line) {
      putText(rtrim($line));
    }
    unset($output);
    putHtml("</pre>");
  }
}

if (isset($status_show_acme_certificates)) {
  if ($status_show_acme_certificates !== FALSE) {
    putHtml("<h2>ACME Certificates:</h2>");
    putHtml("<pre>");

    while (! feof($status_show_acme_certificates)) {
      if (($line = fgets($status_show_acme_certificates, 1024)) !== FALSE) {
        putText(rtrim($line));
      }
    }
    pclose($status_show_acme_certificates);

    putHtml("</pre>");
  }
}

if (isset($status_ntp_sessions)) {
  if ($status_ntp_sessions !== FALSE) {
    putHtml("<h2>NTP Time Sources:</h2>");
    putHtml("<pre>");

    if (($line = fgets($status_ntp_sessions, 1024)) !== FALSE) {
      if (strncmp($line, '210', 3) != 0) {
        putText(rtrim($line));
      }
    }
    while (! feof($status_ntp_sessions)) {
      if (($line = fgets($status_ntp_sessions, 1024)) !== FALSE) {
        putText(rtrim($line));
      }
    }
    pclose($status_ntp_sessions);

    putHtml("</pre>");
  }
}

if ($daemon['miniupnpd'] > 0) {
  if (getPREFdef($global_prefs, 'status_show_miniupnpd_leases') !== 'no') { // currently not set in the Prefs tab
    putHtml("<h2>Universal Plug'n'Play (NAT-PMP/PCP and UPnP) Leases:</h2>");

    if (is_null($upnp = parseMINIUPNPDleases($MINIUPNPDLEASES))) {
      putHtml("<pre>");
      putText('No Active NAT-PMP/PCP or UPnP Leases');
      putHtml("</pre>");
    } else {
      putHtml('<pre><table class="statusdatatable">');
      putHtml("<tr>");
      $cur_time = time();
      if (($n = arrayCount($upnp['clients'])) > 0) {
        echo "<td>", "Public Port", "</td>";
        echo "<td>", "Protocol", "</td>";
        echo "<td>", "Local IPv4", "</td>";
        echo "<td>", "Local Port", "</td>";
        echo "<td>", "Expire Time", "</td>";
        echo "<td>", "Description", "</td>";
        for ($i = 0; $i < $n; $i++) {
          putHtml("</tr><tr>");
          echo "<td>", $upnp['clients'][$i]['p_port'], "</td>";
          echo "<td>", $upnp['clients'][$i]['proto'], "</td>";
          echo "<td>", $upnp['clients'][$i]['l_ip'], "</td>";
          echo "<td>", $upnp['clients'][$i]['l_port'], "</td>";
          if ($upnp['clients'][$i]['expire'] > $cur_time) {
            echo "<td>", date('Y-m-d H:i:s', $upnp['clients'][$i]['expire']), "</td>";
          } else {
            echo "<td>N/A</td>";
          }
          echo "<td>", htmlspecialchars($upnp['clients'][$i]['desc']), "</td>";
        }
      }
      putHtml("</tr>");
      putHtml("</table></pre>");
    }
  }
}

if ($daemon['dnsmasq'] > 0) {
  if (getPREFdef($global_prefs, 'status_show_dhcp_leases') !== 'no' && isDHCPactive()) {
    putHtml("<h2>DHCP Leases:</h2>");

    if (! is_null($dhcp = parseDNSMASQleaseDB($DNSMASQLEASEDB, FALSE))) {
      foreach (array(FALSE, TRUE) as $dhcpv6) {
        if ($dhcpv6 && is_null($dhcp = parseDNSMASQleaseDB($DNSMASQLEASE6DB, TRUE, $dhcp))) {
          break;
        }
        putHtml('<pre><table class="statusdatatable">');
        putHtml("<tr>");
        if (($n = arrayCount($dhcp['clients'])) > 0) {
          echo "<td>", ($dhcpv6 ? "IPv6" : "IPv4")." Address", "</td>";
          echo "<td>", "MAC Address", "</td>";
          echo "<td>", "Host Name", "</td>";
          echo "<td>", "Client Name", "</td>";
          echo "<td>", "Start Time", "</td>";
          echo "<td>", "Vendor Name", "</td>";
          for ($i = 0; $i < $n; $i++) {
            putHtml("</tr><tr>");
            echo "<td>", $dhcp['clients'][$i]['ip'], "</td>";
            echo "<td>", $dhcp['clients'][$i]['mac'], "</td>";
            echo "<td>", $dhcp['clients'][$i]['hostname'], "</td>";
            echo "<td>", htmlspecialchars($dhcp['clients'][$i]['clientname']), "</td>";
            echo "<td>", date('Y-m-d H:i:s', $dhcp['clients'][$i]['time']), "</td>";
            echo "<td>", htmlspecialchars($dhcp['clients'][$i]['vendor']), "</td>";
          }
          putHtml("</tr><tr>");
          echo '<td colspan="5">'.$n.' Active '.($dhcpv6 ? "DHCPv6" : "DHCP").' Leases</td>';
        }
        putHtml("</tr>");
        putHtml("</table></pre>");
      }
    } elseif (! is_null($dhcp = parseDNSMASQleases($DNSMASQLEASES))) {
      $is_mac2vendor = is_mac2vendor();
      putHtml('<pre><table class="statusdatatable">');
      putHtml("<tr>");
      if (($n = arrayCount($dhcp['clients'])) > 0) {
        echo "<td>", "IPv4 Address", "</td>";
        echo "<td>", "MAC Address", "</td>";
        echo "<td>", "Host Name", "</td>";
        echo "<td>", "Expire Time", "</td>";
        if ($is_mac2vendor) {
          echo "<td>", "Vendor Name", "</td>";
        }
        for ($i = 0; $i < $n; $i++) {
          putHtml("</tr><tr>");
          echo "<td>", $dhcp['clients'][$i]['ip'], "</td>";
          echo "<td>", $dhcp['clients'][$i]['mac'], "</td>";
          echo "<td>", $dhcp['clients'][$i]['name'], "</td>";
          echo "<td>", date('Y-m-d H:i:s', $dhcp['clients'][$i]['expire']), "</td>";
          if ($is_mac2vendor) {
            echo "<td>", htmlspecialchars(mac2vendor($dhcp['clients'][$i]['mac'])), "</td>";
          }
        }
        putHtml("</tr><tr>");
        echo '<td colspan="5">'.$n.' Active DHCP Leases</td>';
      }
      putHtml("</tr>");
      putHtml("</table></pre>");
    } else {
      putHtml("<pre>");
      putText('No Active DHCP Leases');
      putHtml("</pre>");
    }
  }
}

if ($daemon['openvpn'] > 0 && getPREFdef($global_prefs, 'status_openvpn_client_server') !== 'no') {
  if (is_file('/var/run/openvpnclient.pid') && !is_null($openvpnclient = parseOpenVPNClog($OVPNCLOGFILE))) {
    putHtml("<h2>OpenVPN Client Status:</h2>");
    putHtml("<pre>");
    putText('Active OpenVPN Client tunnel to Remote Server '.$openvpnclient['remote']);
    if (isset($openvpnclient['updated'])) {
      putText($openvpnclient['updated'].' - Bytes Received: '.$openvpnclient['BytesRcv'].' - Bytes Sent: '.$openvpnclient['BytesSnd'].' - Bytes Auth: '.$openvpnclient['BytesAuth']);
    }
    putHtml("</pre>");
  }
  if (is_file('/var/run/openvpn.pid') && !is_null($openvpn = parseOpenVPNlog($OVPNLOGFILE))) {
    putHtml("<h2>OpenVPN Server Status:</h2>");

    if (isset($openvpn['clients']) && ($n = arrayCount($openvpn['clients'])) > 0) {
      putHtml('<pre><table class="statusdatatable">');
      putHtml("<tr>");
      echo "<td>", $openvpn['header']['CommonName'], "</td>";
      echo "<td>", $openvpn['header']['RealAddr'], "</td>";
      echo "<td>", $openvpn['header']['VirtAddr'], "</td>";
      echo "<td>", $openvpn['header']['Virt6Addr'], "</td>";
      echo "<td>", $openvpn['header']['BytesRcv'], "</td>";
      echo "<td>", $openvpn['header']['BytesSnd'], "</td>";
      echo "<td>", $openvpn['header']['Since'], "</td>";
      for ($i = 0; $i < $n; $i++) {
        putHtml("</tr><tr>");
        echo "<td>", $openvpn['clients'][$i]['CommonName'], "</td>";
        echo "<td>", $openvpn['clients'][$i]['RealAddr'], "</td>";
        echo "<td>", $openvpn['clients'][$i]['VirtAddr'], "</td>";
        echo "<td>", $openvpn['clients'][$i]['Virt6Addr'], "</td>";
        echo "<td>", $openvpn['clients'][$i]['BytesRcv'], "</td>";
        echo "<td>", $openvpn['clients'][$i]['BytesSnd'], "</td>";
        echo "<td>", $openvpn['clients'][$i]['Since'], "</td>";
      }
      putHtml("</tr>");
      putHtml("</table></pre>");
    } else {
      putHtml("<pre>");
      putText('No Active Clients connected to this OpenVPN Server');
      putHtml("</pre>");
    }
  }
}

if (isset($status_ipsec_associations)) {
  if ($status_ipsec_associations !== FALSE) {
    putHtml("<h2>IPsec Associations (strongSwan):</h2>");
    putHtml("<pre>");

    while (! feof($status_ipsec_associations)) {
      if (($line = fgets($status_ipsec_associations, 1024)) !== FALSE) {
        putText(rtrim($line));
      }
    }
    pclose($status_ipsec_associations);

    putHtml("</pre>");
  }
}

if (isset($status_wireguard_vpn)) {
  if ($status_wireguard_vpn !== FALSE) {
    putHtml("<h2>WireGuard VPN Status:</h2>");

    $wireguard_data = array();
    $wg_state = 0;
    while (! feof($status_wireguard_vpn)) {
      if (($line = fgets($status_wireguard_vpn, 1024)) !== FALSE) {
        if (($line = rtrim($line)) === '#next#') {
          $wg_state++;
          continue;
        }
        if ($wg_state == 0) {          // peer<TAB>latest-handshake
          $wireguard_data['handshake'][] = $line;
        } elseif ($wg_state == 1) {    // filename<COLON>PublicKey, for client names
          $wireguard_data['clients'][] = $line;
        } elseif ($wg_state == 2) {    // wg show
          $wireguard_data['wg_show'][] = $line;
        }
      }
    }
    pclose($status_wireguard_vpn);
    $wireguard = parseWireGuard($wireguard_data);
    unset($wireguard_data);

    if (isset($wireguard['peers'], $wireguard['info']['endpoint']) && ($n = arrayCount($wireguard['peers'])) > 0) {
      putHtml('<pre><table class="statusdatatable">');
      putHtml("<tr>");
      echo "<td>", "Peer", "</td>";
      echo "<td>", "Endpoint", "</td>";
      echo "<td>", "Activity", "</td>";
      echo "<td>", "Transfer", "</td>";
      echo "<td>", "Handshake", "</td>";
      for ($i = 0; $i < $n; $i++) {
        if (! isset($wireguard['peers'][$i]['endpoint']) || $wireguard['peers'][$i]['activity'] === 'stale') {
          continue;
        }
        putHtml("</tr><tr>");
        echo "<td>", $wireguard['peers'][$i]['peer'], "</td>";
        echo "<td>", $wireguard['peers'][$i]['endpoint'], "</td>";
        echo "<td>", $wireguard['peers'][$i]['activity'], "</td>";
        echo "<td>", $wireguard['peers'][$i]['transfer'], "</td>";
        echo "<td>", $wireguard['peers'][$i]['handshake'], "</td>";
        if (isset($wireguard['peers'][$i]['allowedips'])) {
          putHtml("</tr><tr>");
          echo "<td>&nbsp;</td>";
          echo '<td colspan="4">', "Allowed-IPs:&nbsp;", $wireguard['peers'][$i]['allowedips'], "</td>";
        }
      }
      putHtml("</tr>");
      putHtml("</table></pre>");
    } elseif (isset($wireguard['peers'])) {
      putHtml("<pre>");
      putText('No Tunnels established within the last hour using the WireGuard VPN');
      putHtml("</pre>");
    } else {
      putHtml("<pre>");
      putText('No Peers configured for WireGuard');
      putHtml("</pre>");
    }
  }
}

if ($daemon['asterisk'] > 0) {
  if (($cmd = getPREFdef($global_prefs, 'status_active_chan_cmdstr')) === '') {
    $cmd = 'core show channels';
  }
  $tmpfile = tempnam("/tmp", "PHP_");
  $status = asteriskCMD($cmd, $tmpfile);

  if ($status == 0 && getPREFdef($global_prefs, 'status_show_active_chan') !== 'no') {
    putHtml("<h2>Active Channels:</h2>");
    putHtml('<pre>');
    outputASTERISKcommand($tmpfile);
    putHtml('</pre>');
  } elseif ($status != 0) {
    putHtml("<h2>Asterisk:</h2>");
    putHtml('<pre style="color: red;">');
    putText(asteriskERROR($status));
    putHtml('</pre>');
  }
  @unlink($tmpfile);

  if ($status == 0 && getPREFdef($global_prefs, 'status_sip_show_registry') !== 'no') {
    putHtml("<h2>SIP Trunk Registrations:</h2>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    asteriskCMD('sip show registry', $tmpfile);
    outputASTERISKcommand($tmpfile);
    @unlink($tmpfile);
    putHtml("</pre>");
  }

  if ($status == 0 && getPREFdef($global_prefs, 'status_sip_show_peers') !== 'no') {
    putHtml("<h2>SIP Peer Status:</h2>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    asteriskCMD('sip show peers', $tmpfile);
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) !== '') {
        if (strncasecmp($line, 'verbosity ', 10) &&
            ((getPREFdef($global_prefs, 'status_exclude_extensions') !== 'yes') || !preg_match('/^[1-9]...\/[1-9].../', $line))) {
          putText($line);
        }
      }
    }
    fclose($ph);
    @unlink($tmpfile);
    putHtml("</pre>");
  }

  if ($status == 0 && getPREFdef($global_prefs, 'status_iax2_show_registry') === 'yes') {
    putHtml("<h2>IAX2 Trunk Registrations:</h2>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    asteriskCMD('iax2 show registry', $tmpfile);
    outputASTERISKcommand($tmpfile);
    @unlink($tmpfile);
    putHtml("</pre>");
  }

  if ($status == 0 && getPREFdef($global_prefs, 'status_iax2_show_peers') === 'yes') {
    putHtml("<h2>IAX2 Peer Status:</h2>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    asteriskCMD('iax2 show peers', $tmpfile);
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) !== '') {
        if (strncasecmp($line, 'verbosity ', 10) &&
            ((getPREFdef($global_prefs, 'status_exclude_extensions') !== 'yes') || !preg_match('/^[1-9]...\/[1-9].../', $line))) {
          putText($line);
        }
      }
    }
    fclose($ph);
    @unlink($tmpfile);
    putHtml("</pre>");
  }

  if ($status == 0 && getPREFdef($global_prefs, 'status_voicemail_show_users') === 'yes') {
    if (($cmd = getPREFdef($global_prefs, 'status_voicemail_users_name_cmdstr')) === '') {
      $cmd = 'Voicemail User Status';
    }
    putHtml("<h2>".htmlspecialchars($cmd).":</h2>");
    putHtml("<pre>");
    if (($cmd = getPREFdef($global_prefs, 'status_voicemail_users_cmdstr')) === '') {
      $cmd = 'voicemail show users';
    }
    $tmpfile = tempnam("/tmp", "PHP_");
    asteriskCMD($cmd, $tmpfile);
    outputASTERISKcommand($tmpfile);
    @unlink($tmpfile);
    putHtml("</pre>");
  }

  if ($status == 0 && getPREFdef($global_prefs, 'status_dahdi_show_status') === 'yes') {
    if (($cmd = getPREFdef($global_prefs, 'status_dahdi_status_name_cmdstr')) === '') {
      $cmd = 'DAHDI Status';
    }
    putHtml("<h2>".htmlspecialchars($cmd).":</h2>");
    putHtml("<pre>");
    if (($cmd = getPREFdef($global_prefs, 'status_dahdi_status_cmdstr')) === '') {
      $cmd = 'dahdi show status';
    }
    $tmpfile = tempnam("/tmp", "PHP_");
    asteriskCMD($cmd, $tmpfile);
    outputASTERISKcommand($tmpfile);
    @unlink($tmpfile);
    putHtml("</pre>");
  }

  if ($status == 0 && getPREFdef($global_prefs, 'status_jabber_show_status') === 'yes') {
    if (($cmd = getPREFdef($global_prefs, 'status_jabber_status_name_cmdstr')) === '') {
      $cmd = 'Jabber Status';
    }
    putHtml("<h2>".htmlspecialchars($cmd).":</h2>");
    putHtml("<pre>");
    if (($cmd = getPREFdef($global_prefs, 'status_jabber_status_cmdstr')) === '') {
      $cmd = 'jabber show connections';
    }
    $tmpfile = tempnam("/tmp", "PHP_");
    asteriskCMD($cmd, $tmpfile);
    outputASTERISKcommand($tmpfile);
    @unlink($tmpfile);
    putHtml("</pre>");
  }

  if ($status == 0 && getPREFdef($global_prefs, 'status_custom_asterisk_status') === 'yes') {
    if (($cmd = getPREFdef($global_prefs, 'status_custom_asterisk_name_cmdstr')) === '') {
      $cmd = 'Asterisk Command Status';
    }
    putHtml("<h2>".htmlspecialchars($cmd).":</h2>");
    putHtml("<pre>");
    if (($cmd = getPREFdef($global_prefs, 'status_custom_asterisk_cmdstr')) !== '') {
      $tmpfile = tempnam("/tmp", "PHP_");
      asteriskCMD($cmd, $tmpfile);
      outputASTERISKcommand($tmpfile);
      @unlink($tmpfile);
    }
    putHtml("</pre>");
  }
}

if ($daemon['kamailio'] > 0) {
  if (getPREFdef($global_prefs, 'status_show_kamailio_server') !== 'no') {
    putHtml("<h2>Kamailio SIP Server Status:</h2>");
    putHtml("<pre>");

    $output = array();
    @exec('kamcmd core.version; kamcmd tm.stats', $output);
    foreach ($output as $line) {
      $line = trim($line);
      if ($line === '{') {
        $line = '==============================';
      } elseif ($line === '}') {
        continue;
      }
      putText($line);
    }
    unset($output);
    putHtml("</pre>");
  }
}

if (isset($status_show_xmpp_server)) {
  if ($status_show_xmpp_server !== FALSE) {
    putHtml("<h2>XMPP Server Status:</h2>");
    putHtml("<pre>");

    if (! feof($status_show_xmpp_server)) {
      if (($line = rtrim(fgets($status_show_xmpp_server, 1024))) !== '') {
        putText('Prosody Version: '.$line);
      }
    }
    while (! feof($status_show_xmpp_server)) {
      if (($line = rtrim(fgets($status_show_xmpp_server, 1024))) !== '') {
        putText($line);
      }
    }
    pclose($status_show_xmpp_server);

    putHtml("</pre>");
  }
}

if (isset($status_show_adaptive_ban)) {
  if ($status_show_adaptive_ban !== FALSE) {
    putHtml("<h2>Adaptive Ban Plugin Status:</h2>");
    putHtml("<pre>");

    while (! feof($status_show_adaptive_ban)) {
      if (($line = trim(fgets($status_show_adaptive_ban, 1024))) !== '') {
        putText($line);
      }
    }
    pclose($status_show_adaptive_ban);

    putHtml("</pre>");
  }
}

if (is_file($CONNTRACK)) {
  if (getPREFdef($global_prefs, 'status_show_firewall_states') === 'yes') {
    putHtml("<h2>Firewall States:</h2>");
    $tmpfile = tempnam("/tmp", "PHP_");
    shell('head -n 2500 '.$CONNTRACK.' | grep -v "127\.0\.0\.1" >'.$tmpfile, $status);

    parseFIREWALLstates($firewall, $tmpfile, 100);
    if (is_null($firewall)) {
      putHtml("<pre>");
      putText('No Active Firewall States');
      putHtml("</pre>");
    } else {
      if (($total_firewall_states = trim(@file_get_contents('/proc/sys/net/netfilter/nf_conntrack_count'))) === '') {
        $total_firewall_states = 'Unknown';
      }
      putHtml('<pre><table class="statusdatatable">');
      putHtml("<tr>");
      if (($n = arrayCount($firewall['states'])) > 0) {
        echo "<td>", "Source", "</td>";
        echo "<td>", "Port (#'s)", "</td>";
        echo "<td>", "Destination", "</td>";
        echo "<td>", "Port", "</td>";
        echo "<td>", "Protocol", "</td>";
        echo "<td>", "Packets", "</td>";
        echo "<td>", "Bytes", "</td>";
        echo '<td style="text-align: right;">', "TTL", "</td>";
        for ($i = 0; $i < $n; $i++) {
          putHtml("</tr><tr>");
          echo "<td>", $firewall['states'][$i]['src'], "</td>";
          echo "<td>", $firewall['states'][$i]['sport'], ($firewall['states'][$i]['nsports'] > 1 ? '&nbsp;('.$firewall['states'][$i]['nsports'].')' : ''), "</td>";
          echo "<td>", $firewall['states'][$i]['dst'], "</td>";
          echo "<td>", $firewall['states'][$i]['dport'], "</td>";
          echo '<td style="text-align: center;">', $firewall['states'][$i]['proto'], "</td>";
          echo "<td>", $firewall['states'][$i]['packets'], "</td>";
          echo "<td>", $firewall['states'][$i]['bytes'], "</td>";
          echo '<td style="text-align: right;">', secs2minsec($firewall['states'][$i]['ttl']), "</td>";
        }
        putHtml("</tr><tr>");
        echo '<td colspan="8">'.$total_firewall_states.' Total Firewall States</td>';
      }
      putHtml("</tr>");
      putHtml("</table></pre>");
    }
    @unlink($tmpfile);
  }
}

if ($daemon['upsmon'] > 0) {
  if (getPREFdef($global_prefs, 'status_ups_show_status') !== 'no') {
    putHtml("<h2>UPS Monitoring Status:</h2>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    shell('/usr/bin/upsc $(awk \'/^MONITOR / { print $2; nextfile; }\' /etc/ups/upsmon.conf) >'.$tmpfile, $status);

    if (is_null($upsmon = parseUPSstatus($tmpfile))) {
      putText('No UPS Status available');
      putText('');
    } else {
      putHtml('<table class="statusdatatable">');
      putHtml("<tr>");
      if (($n = arrayCount($upsmon['status'])) > 0) {
        echo "<td>", "Model", "</td>";
        echo "<td>", "Status", "</td>";
        echo "<td>", "Utility", "</td>";
        echo "<td>", "UPS Load", "</td>";
        echo "<td>", "Battery Chg.", "</td>";
        echo "<td>", "Batt. Runtime", "</td>";
        for ($i = 0; $i < $n; $i++) {
          putHtml("</tr><tr>");
          echo "<td>", $upsmon['status'][$i]['ups.model'], "</td>";
          echo "<td>", $upsmon['status'][$i]['ups.status'], "</td>";
          echo "<td>", $upsmon['status'][$i]['input.voltage'].' Volts', "</td>";
          echo "<td>", $upsmon['status'][$i]['ups.load'].' %', "</td>";
          echo "<td>", $upsmon['status'][$i]['battery.charge'].' %', "</td>";
          echo "<td>", (int)(((int)$upsmon['status'][$i]['battery.runtime'] + 30) / 60).' Minutes', "</td>";
        }
      }
      putHtml("</tr>");
      putHtml("</table>");
    }

    putText('Recent UPS Events:');
    putText('==================');
    shell('tail -n4 /var/log/ups-status.log >'.$tmpfile, $status);

    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) !== '') {
        putText($line);
      }
    }
    fclose($ph);
    @unlink($tmpfile);
    putHtml("</pre>");
  }
}

if (isset($status_show_hardware_monitoring)) {
  if ($status_show_hardware_monitoring !== FALSE) {
    putHtml("<h2>Hardware Monitoring:</h2>");
    putHtml("<pre>");

    while (! feof($status_show_hardware_monitoring)) {
      if (($line = rtrim(fgets($status_show_hardware_monitoring, 1024))) !== '') {
        putText($line);
      }
    }
    pclose($status_show_hardware_monitoring);

    putHtml("</pre>");
  }
}

if (isset($status_show_smart_monitoring)) {
  if ($status_show_smart_monitoring !== FALSE) {
    putHtml("<h2>S.M.A.R.T Monitoring:</h2>");
    putHtml("<pre>");

    while (! feof($status_show_smart_monitoring)) {
      if (($line = fgets($status_show_smart_monitoring, 1024)) !== FALSE) {
        putText(rtrim($line));
      }
    }
    pclose($status_show_smart_monitoring);

    putHtml("</pre>");
  }
}

if (isset($status_show_system_logs)) {
  if ($status_show_system_logs !== FALSE) {
    putHtml("<h2>Latest System Logs:</h2>");
    putHtml("<pre>");

    while (! feof($status_show_system_logs)) {
      if (($line = rtrim(fgets($status_show_system_logs, 1024))) !== '') {
        putText($line);
      }
    }
    pclose($status_show_system_logs);

    putHtml("</pre>");
  }
}
putHtml('<p style="color: orange;">Asterisk&reg; is a registered trademark of Digium, Inc. - A Sangoma Company</p>');

} // End of HTTP GET
require_once $COMMON.'footer.php';

?>
