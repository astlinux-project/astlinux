<?php

// Copyright (C) 2008-2012 Lonnie Abelbeck
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
//
// System location of OpenVPN Client logfile
$OVPNCLOGFILE = '/var/log/openvpnclient-status.log';
// System location of OpenVPN Server logfile
$OVPNLOGFILE = '/var/log/openvpn-status.log';
// System location of MINIUPNPD leases
$MINIUPNPDLEASES = '/var/db/upnp.leases';
// System location of DNSMASQ leases
$DNSMASQLEASES = '/var/db/dnsmasq.leases';
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
  $status['ntpd'] = 0;
  $status['miniupnpd'] = 0;
  $status['dnsmasq'] = 0;
  $status['openvpn'] = 0;
  $status['racoon'] = 0;
  $status['pptpd'] = 0;
  $status['apcupsd'] = 0;
  $status['syslogd'] = 0;
  $status['zabbix_agentd'] = 0;
  $status['zabbix_proxy'] = 0;
  $status['reboot'] = 0;
  
  $tmpfile = tempnam("/tmp", "PHP_");
  @exec('ps >'.$tmpfile);
  $ph = @fopen($tmpfile, "r");
  while (! feof($ph)) {
    if ($line = trim(fgets($ph, 1024))) {
      if (strpos($line, ' asterisk') !== FALSE || strpos($line, '/asterisk') !== FALSE) {
      	$status['asterisk']++;
      } elseif (strpos($line, ' ntpd') !== FALSE || strpos($line, '/ntpd') !== FALSE) {
      	$status['ntpd']++;
      } elseif (strpos($line, ' miniupnpd') !== FALSE || strpos($line, '/miniupnpd') !== FALSE) {
      	$status['miniupnpd']++;
      } elseif (strpos($line, ' dnsmasq') !== FALSE || strpos($line, '/dnsmasq') !== FALSE) {
      	$status['dnsmasq']++;
      } elseif (strpos($line, ' syslogd') !== FALSE || strpos($line, '/syslogd') !== FALSE) {
      	$status['syslogd']++;
      } elseif (strpos($line, ' openvpn') !== FALSE || strpos($line, '/openvpn') !== FALSE) {
      	$status['openvpn']++;
      } elseif (strpos($line, ' racoon') !== FALSE || strpos($line, '/racoon') !== FALSE) {
      	$status['racoon']++;
      } elseif (strpos($line, ' pptpd') !== FALSE || strpos($line, '/pptpd') !== FALSE) {
      	$status['pptpd']++;
      } elseif (strpos($line, ' apcupsd') !== FALSE || strpos($line, '/apcupsd') !== FALSE) {
      	$status['apcupsd']++;
      } elseif (strpos($line, ' zabbix_agentd ') !== FALSE || strpos($line, '/zabbix_agentd ') !== FALSE) {
      	$status['zabbix_agentd']++;
      } elseif (strpos($line, ' zabbix_proxy ') !== FALSE || strpos($line, '/zabbix_proxy ') !== FALSE) {
      	$status['zabbix_proxy']++;
      } elseif (strpos($line, 'reboot -d') !== FALSE) {
      	$status['reboot']++;
      }
    }
  }
  fclose($ph);
  @unlink($tmpfile);
  
  return($status);
}

// Function: parseUPSstatus
//
function parseUPSstatus($log) {

  $cid = 0;
  if (($ph = @fopen($log, "r")) !== FALSE) {
    while (! feof($ph)) {
      if ($line = rtrim(fgets($ph, 1024))) {
        if (preg_match('/^([A-Z][A-Z]*)[ :]*(.*)$/', $line, $ips)) {
          switch ($ips[1]) {
          case 'MODEL':
          case 'STATUS':
          case 'LINEV':
          case 'LOADPCT':
          case 'BCHARGE':
          case 'TIMELEFT':
            $status['status'][$cid]["$ips[1]"] = str_replace('Percent', '%', $ips[2]);
            break;
          }
        }
      }
    }
    fclose($ph);
  }
  return($status);
}

// Function: parseIPSECstates
//
function parseIPSECstates($log) {

  $cid = -1;
  if (($ph = @fopen($log, "r")) !== FALSE) {
    while (! feof($ph)) {
      if ($line = rtrim(fgets($ph, 1024))) {
        if (preg_match('/^([0-9a-fA-F][0-9a-fA-F.:]*).* ([0-9a-fA-F][0-9a-fA-F.:]*)/', $line, $ips)) {
          $cid++;
          $status['states'][$cid]['src'] = $ips[1];
          $status['states'][$cid]['dst'] = $ips[2];
          $status['states'][$cid]['created'] = '';
          $status['states'][$cid]['lifetime'] = 0;
          $status['states'][$cid]['age'] = 0;
          $status['states'][$cid]['bytes'] = 0;
          $status['states'][$cid]['type'] = '';
        } elseif ($cid >= 0) {
          $line = ltrim($line);
          if (preg_match('/^created:(.*)current:/', $line, $data)) {
            $status['states'][$cid]['created'] = trim($data[1]);
          } elseif (preg_match('/^diff:[^0-9]*([0-9][0-9]*)[^0-9]*([0-9][0-9]*)/', $line, $data)) {
            $status['states'][$cid]['age'] = (float)$data[1];
            $status['states'][$cid]['lifetime'] = (float)$data[2];
          } elseif (preg_match('/^current:[^0-9]*([0-9][0-9]*)/', $line, $data)) {
            $status['states'][$cid]['bytes'] = (float)$data[1];
          } elseif (preg_match('/^(.*)spi=/', $line, $data)) {
            $status['states'][$cid]['type'] = trim($data[1]);
          }
        }
      }
    }
    fclose($ph);
  }
  return($status);
}

// Function: parseFIREWALLstates
//
function parseFIREWALLstates(&$status, $log) {
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
      if ($line = trim(fgets($ph, 1024))) {
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
            }
          }
          if ($unique) {
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

  shell('grep -q "^dhcp-leasefile=" /etc/dnsmasq.conf 2>/dev/null', $status);

  return($status == 0);
}

// Function: noASTURWstorage
//
function noASTURWstorage() {

  if (noASTLINUX()) {
    $status = 0;
  } else {
    shell('mount 2>/dev/null | grep -q "/oldroot/mnt/asturw"', $status);
  }

  return($status != 0);
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

// Function: parseMINIUPNPDleases
//
function parseMINIUPNPDleases($log) {
  $cid = 0;
  if (($ph = @fopen($log, "r")) !== FALSE) {
    while (! feof($ph)) {
      if ($line = trim(fgets($ph, 1024))) {
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
  return($status);
}

// Function: parseDNSMASQleases
//
function parseDNSMASQleases($log) {
  $cid = 0;
  $cur_time = time();
  if (($ph = @fopen($log, "r")) !== FALSE) {
    while (! feof($ph)) {
      if ($line = trim(fgets($ph, 1024))) {
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
      $ip[$key] = $row['ip'];
    }
    array_multisort($ip, SORT_ASC, SORT_STRING, $status['clients']);
  }
  return($status);
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
      if ($line = trim(fgets($ph, 1024))) {
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
  return($status);
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
      if ($line = trim(fgets($ph, 1024))) {
        $linetokens = explode(',', $line);
        if ($linetokens[0] === "TITLE") {
          $status['title'] = $linetokens[1];
        } elseif ($linetokens[0] === "TIME") {
          $status['time'] = $linetokens[1];
        } elseif ($linetokens[0] === "HEADER" && $linetokens[1] === "CLIENT_LIST") {
          $status['header']['CommonName'] = $linetokens[2];
          $status['header']['RealAddr'] = $linetokens[3];
          $status['header']['VirtAddr'] = $linetokens[4];
          $status['header']['BytesRcv'] = $linetokens[5];
          $status['header']['BytesSnd'] = $linetokens[6];
          $status['header']['Since'] = $linetokens[7];
        } elseif ($linetokens[0] === "CLIENT_LIST") {
          $status['clients'][$cid]['CommonName'] = $linetokens[1];
          $status['clients'][$cid]['RealAddr'] = $linetokens[2];
          $status['clients'][$cid]['VirtAddr'] = $linetokens[3];
          $status['clients'][$cid]['BytesRcv'] = $linetokens[4];
          $status['clients'][$cid]['BytesSnd'] = $linetokens[5];
          $status['clients'][$cid]['Since'] = $linetokens[6];
          $cid++;
        }
      }
    }
    fclose($ph);
  }
  return($status);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (isset($_POST['submit_pppoe'])) {
    $result = restartPROCESS('pppoe', 10, 99);
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'all';
require_once $COMMON.'header.php';

$daemon = getDaemons();

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
} elseif (noASTERISKsounds($daemon['asterisk'])) {
  putHtml('<p style="color: red;">Notice: No Core Asterisk Sounds, click <a href="/admin/system.php" class="headerText">System</a>then via "Asterisk Sounds Packages", Upgrade/Install "core" and "moh" sounds.</p>');
} else {
  if (($HTPASSWD = getPASSWDlocation()) !== '') {
    if (is_file($HTPASSWD) || getPREFdef($global_prefs, 'status_password_warning') === 'no') {
      putHtml("<p>&nbsp;</p>");
    } else {
      putHtml('<p style="color: orange;">Warning: Management Password is not set, click <a href="/admin/system.php" class="headerText">System</a>to set the password.</p>');
    }
  } else {
    putHtml('<p style="color: orange;">Warning: Unable to locate base web directory.</p>');
  }
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
// DNS
$rconf = is_file('/etc/resolv-extern.conf') ? '/etc/resolv-extern.conf' : '/etc/resolv.conf';
if (($fp = @fopen($rconf, 'r')) !== FALSE) {
  while (! feof($fp)) {
    if ($line = trim(fgets($fp, 1024))) {
      echo '<tr ', ($i++ % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'DNS:', '</td>';
      echo '<td style="text-align: left;" colspan="3">', $line, '</td>';
      putHtml('</tr>');
    }
  }
  fclose($fp);
}
// System Time
echo '<tr ', ($i++ % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'System Time:', '</td>';
echo '<td style="text-align: left;">', date('l, M d H:i:s T Y'), '</td>';
$line = trim(shell_exec('grep "^Mem[TF]" /proc/meminfo 2>/dev/null'));
$mstr = '';
if (($pos = strpos($line, 'MemTotal')) !== FALSE) {
  $mem = (int)(((int)trim(substr($line, $pos + 9)) + 512)/1024);
  $mstr .= $mem.' MB, ';
}
if (($pos = strpos($line, 'MemFree')) !== FALSE) {
  $mem = (int)(((int)trim(substr($line, $pos + 8)) + 512)/1024);
  $mstr .= 'Free '.$mem.' MB';
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
    $line = trim(fgets($fp, 1024));
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
  echo $line, '</td>';
} else {
  echo '<td>&nbsp;</td><td>&nbsp;</td>';
}
putHtml('</tr>');
// gui version
echo '<tr ', ($i++ % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'GUI Version:', '</td>';
echo '<td style="text-align: left;">', $GUI_VERSION, '</td>';
// license
echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', 'License:', '</td>';
$license = (getPREFdef($global_prefs, 'status_require_auth') === 'yes') ? '/admin/license.php' : '/license.php';
echo '<td style="text-align: left;">', '<a href="'.$license.'" class="headerText">Show Licenses</a>', '</td>';
putHtml('</tr>');
// end of block
putHtml('</table>');
putHtml('</center></td></tr></table></center>');

if (is_file('/etc/ppp/pppoe.conf')) {
  if (getPREFdef($global_prefs, 'status_pppoe_connection') !== 'no') {
    putHtml('<form method="post" action="'.$myself.'">');
    putHtml("<h2>PPPoE Connection Status:");
    putHtml('<input type="submit" value="Restart PPPoE" name="submit_pppoe" />');
    putHtml("</h2>");
    putHtml("</form>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    shell('/usr/sbin/pppoe-status >'.$tmpfile, $status);
    if ($status == 0) {
      $ph = @fopen($tmpfile, "r");
      while (! feof($ph)) {
        if ($line = trim(fgets($ph, 1024))) {
          putText($line);
        }
      }
      fclose($ph);
    } else {
      putText('No pppoe-status available.');
    }
    @unlink($tmpfile);
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

if (($cmd = getPREFdef($global_prefs, 'status_disk_usage')) !== 'no') {
  putHtml("<h2>Disk Usage:</h2>");
  putHtml("<pre>");
  $tmpfile = tempnam("/tmp", "PHP_");
  @exec('df -h /tmp | grep "^ *[A-Z][a-z]" >'.$tmpfile);
  @exec('df -h | grep "^/dev/[sh]d[a-h][0-9]" | sort -k 1,1 >>'.$tmpfile);
  $ph = @fopen($tmpfile, "r");
  while (! feof($ph)) {
    if ($line = trim(fgets($ph, 1024))) {
      putText($line);
    }
  }
  fclose($ph);
  @unlink($tmpfile);
  putHtml("</pre>");
}

if ($daemon['ntpd'] > 0) {
  if (getPREFdef($global_prefs, 'status_ntp_sessions') !== 'no') {
    putHtml("<h2>NTP Peer States:</h2>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    shell('ntpq -pn 127.0.0.1 >'.$tmpfile, $status);
    if ($status == 0) {
      $ph = @fopen($tmpfile, "r");
      while (! feof($ph)) {
        if ($line = rtrim(fgets($ph, 1024))) {
          putText($line);
        }
      }
      fclose($ph);
    } else {
      putText('No NTP peers found');
    }
    @unlink($tmpfile);
    putHtml("</pre>");
  }
}

if ($daemon['miniupnpd'] > 0) {
  if (getPREFdef($global_prefs, 'status_show_miniupnpd_leases') !== 'no') { // currently not set in the Prefs tab
    putHtml("<h2>Universal Plug'n'Play (NAT-PMP and UPnP) Leases:</h2>");
  
    if (is_null($upnp = parseMINIUPNPDleases($MINIUPNPDLEASES))) {
      putHtml("<pre>");
      putText('No Active NAT-PMP or UPnP Leases');
      putHtml("</pre>");
    } else {
      putHtml('<pre><table class="statusdatatable">');
      putHtml("<tr>");
      $cur_time = time();
      if (($n = count($upnp['clients'])) > 0) {
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
  
    if (is_null($dhcp = parseDNSMASQleases($DNSMASQLEASES))) {
      putHtml("<pre>");
      putText('No Active DHCP Leases');
      putHtml("</pre>");
    } else {
      $is_mac2vendor = is_mac2vendor();
      putHtml('<pre><table class="statusdatatable">');
      putHtml("<tr>");
      if (($n = count($dhcp['clients'])) > 0) {
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
      }
      putHtml("</tr>");
      putHtml("</table></pre>");
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

    if (($n = count($openvpn['clients'])) > 0) {
      putHtml('<pre><table class="statusdatatable">');
      putHtml("<tr>");
      echo "<td>", $openvpn['header']['CommonName'], "</td>";
      echo "<td>", $openvpn['header']['RealAddr'], "</td>";
      echo "<td>", $openvpn['header']['VirtAddr'], "</td>";
      echo "<td>", $openvpn['header']['BytesRcv'], "</td>";
      echo "<td>", $openvpn['header']['BytesSnd'], "</td>";
      echo "<td>", $openvpn['header']['Since'], "</td>";
      for ($i = 0; $i < $n; $i++) {
        putHtml("</tr><tr>");
        echo "<td>", $openvpn['clients'][$i]['CommonName'], "</td>";
        echo "<td>", $openvpn['clients'][$i]['RealAddr'], "</td>";
        echo "<td>", $openvpn['clients'][$i]['VirtAddr'], "</td>";
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

if ($daemon['racoon'] > 0) {
  if (getPREFdef($global_prefs, 'status_ipsec_associations') !== 'no') {
    putHtml("<h2>IPsec Associations:</h2>");
    $tmpfile = tempnam("/tmp", "PHP_");
    shell('/usr/sbin/setkey -D | grep -e "^[0-9]" -e " mode=" -e "^[^a-z]*created:" -e "^[^a-z]*diff:" -e "^[^a-z]*current:" >'.$tmpfile, $status);
  
    if (is_null($ipsec = parseIPSECstates($tmpfile))) {
      putHtml("<pre>");
      putText('No Associations found');
      putHtml("</pre>");
    } else {
      putHtml('<pre><table class="statusdatatable">');
      putHtml("<tr>");
      if (($n = count($ipsec['states'])) > 0) {
        echo "<td>", "Source", "</td>";
        echo "<td>", "Destination", "</td>";
        echo "<td>", "Created", "</td>";
        echo "<td>", "Lifetime", "</td>";
        echo "<td>", "Age", "</td>";
        echo "<td>", "Bytes", "</td>";
        echo "<td>", "Type", "</td>";
        for ($i = 0; $i < $n; $i++) {
          putHtml("</tr><tr>");
          echo "<td>", $ipsec['states'][$i]['src'], "</td>";
          echo "<td>", $ipsec['states'][$i]['dst'], "</td>";
          echo "<td>", $ipsec['states'][$i]['created'], "</td>";
          echo "<td>", $ipsec['states'][$i]['lifetime'], "</td>";
          echo "<td>", $ipsec['states'][$i]['age'], "</td>";
          echo "<td>", $ipsec['states'][$i]['bytes'], "</td>";
          echo "<td>", $ipsec['states'][$i]['type'], "</td>";
        }
      }
      putHtml("</tr>");
      putHtml("</table></pre>");
    }
    @unlink($tmpfile);
  }
}

if ($daemon['pptpd'] > 0) {
  if (getPREFdef($global_prefs, 'status_pptp_server') !== 'no') {
    putHtml("<h2>PPTP VPN Server Status:</h2>");
    putHtml("<pre>");
    $line = trim(shell_exec('. /etc/rc.conf; echo "$PPTP_SUBNET"'));
    putText('PPTP VPN Server is active on subnet: '.$line);
    putHtml("</pre>");
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
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if ($line = trim(fgets($ph, 1024))) {
        if (strncasecmp($line, 'verbosity ', 10) && stripos($line, 'command is deprecated') === FALSE) {
          putText($line);
        }
      }
    }
    fclose($ph);
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
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if ($line = trim(fgets($ph, 1024))) {
        if (strncasecmp($line, 'verbosity ', 10)) {
          putText($line);
        }
      }
    }
    fclose($ph);
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
      if ($line = trim(fgets($ph, 1024))) {
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
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if ($line = trim(fgets($ph, 1024))) {
        if (strncasecmp($line, 'verbosity ', 10)) {
          putText($line);
        }
      }
    }
    fclose($ph);
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
      if ($line = trim(fgets($ph, 1024))) {
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
    if (($cmd = getPREFdef($global_prefs, 'status_voicemail_users_cmdstr')) === '') {
      $cmd = 'voicemail show users';
    }
    putHtml("<h2>Voicemail User Status:</h2>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    asteriskCMD($cmd, $tmpfile);
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if ($line = trim(fgets($ph, 1024))) {
        if (strncasecmp($line, 'verbosity ', 10) && stripos($line, 'command is deprecated') === FALSE) {
          putText($line);
        }
      }
    }
    fclose($ph);
    @unlink($tmpfile);
    putHtml("</pre>");
  }
  
  if ($status == 0 && getPREFdef($global_prefs, 'status_dahdi_show_status') === 'yes') {
    if (($cmd = getPREFdef($global_prefs, 'status_dahdi_status_cmdstr')) === '') {
      $cmd = 'dahdi show status';
    }
    putHtml("<h2>DAHDI Status:</h2>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    if ($cmd === 'wanrouter status' || $cmd === 'wanrouter summary') {
      shell('/usr/sbin/'.$cmd.' >'.$tmpfile, $ret_status);
    } else {
      asteriskCMD($cmd, $tmpfile);
    }
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if ($line = trim(fgets($ph, 1024))) {
        if (strncasecmp($line, 'verbosity ', 10)) {
          putText($line);
        }
      }
    }
    fclose($ph);
    @unlink($tmpfile);
    putHtml("</pre>");
  }
  
  if ($status == 0 && getPREFdef($global_prefs, 'status_jabber_show_status') === 'yes') {
    if (($cmd = getPREFdef($global_prefs, 'status_jabber_status_cmdstr')) === '') {
      $cmd = 'jabber show connections';
    }
    putHtml("<h2>Jabber Status:</h2>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    asteriskCMD($cmd, $tmpfile);
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if ($line = trim(fgets($ph, 1024))) {
        if (strncasecmp($line, 'verbosity ', 10)) {
          putText($line);
        }
      }
    }
    fclose($ph);
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
      $ph = @fopen($tmpfile, "r");
      while (! feof($ph)) {
        if ($line = trim(fgets($ph, 1024))) {
          if (strncasecmp($line, 'verbosity ', 10)) {
            putText($line);
          }
        }
      }
      fclose($ph);
      @unlink($tmpfile);
    }
    putHtml("</pre>");
  }
}

if (is_file('/var/lock/aif_adaptive_ban.lock')) {
  if (getPREFdef($global_prefs, 'status_show_adaptive_ban') === 'yes') {
    putHtml("<h2>Adaptive Ban Plugin Status:</h2>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    $ipv6 = trim(shell_exec('. /etc/rc.conf; if [ "$IPV6" = "yes" ]; then echo "1"; else echo "0"; fi'));
    $cmd = '/usr/share/arno-iptables-firewall/plugins/adaptive-ban-helper status /usr/sbin/iptables /usr/sbin/ip6tables '.$ipv6;
    shell($cmd.' >'.$tmpfile, $status);
  
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if ($line = trim(fgets($ph, 1024))) {
        putText($line);
      }
    }
    fclose($ph);
    @unlink($tmpfile);
    putHtml("</pre>");
  }
}

if (is_file($CONNTRACK)) {
  if (getPREFdef($global_prefs, 'status_show_firewall_states') === 'yes') {
    putHtml("<h2>Firewall States:</h2>");
    $tmpfile = tempnam("/tmp", "PHP_");
    shell('grep -v "127\.0\.0\.1" '.$CONNTRACK.' | tail -n 500 >'.$tmpfile, $status);
  
    parseFIREWALLstates($firewall, $tmpfile);
    if (is_null($firewall)) {
      putHtml("<pre>");
      putText('No Active Firewall States');
      putHtml("</pre>");
    } else {
      putHtml('<pre><table class="statusdatatable">');
      putHtml("<tr>");
      if (($n = min(count($firewall['states']), 100)) > 0) {
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
      }
      putHtml("</tr>");
      putHtml("</table></pre>");
    }
    @unlink($tmpfile);
  }
}

if ($daemon['apcupsd'] > 0) {
  if (getPREFdef($global_prefs, 'status_ups_show_status') !== 'no') {
    putHtml("<h2>APC UPS Status:</h2>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    shell('/usr/sbin/apcaccess status >'.$tmpfile, $status);
    
    if (is_null($apcupsd = parseUPSstatus($tmpfile))) {
      putText('No UPS Status available');
      putText('');
    } else {
      putHtml('<table class="statusdatatable">');
      putHtml("<tr>");
      if (($n = count($apcupsd['status'])) > 0) {
        echo "<td>", "Model", "</td>";
        echo "<td>", "Status", "</td>";
        echo "<td>", "Utility", "</td>";
        echo "<td>", "UPS Load", "</td>";
        echo "<td>", "Battery Chg.", "</td>";
        echo "<td>", "Batt. Runtime", "</td>";
        for ($i = 0; $i < $n; $i++) {
          putHtml("</tr><tr>");
          echo "<td>", $apcupsd['status'][$i]['MODEL'], "</td>";
          echo "<td>", $apcupsd['status'][$i]['STATUS'], "</td>";
          echo "<td>", $apcupsd['status'][$i]['LINEV'], "</td>";
          echo "<td>", $apcupsd['status'][$i]['LOADPCT'], "</td>";
          echo "<td>", $apcupsd['status'][$i]['BCHARGE'], "</td>";
          echo "<td>", $apcupsd['status'][$i]['TIMELEFT'], "</td>";
        }
      }
      putHtml("</tr>");
      putHtml("</table>");
    }
    
    putText('Recent UPS Events:');
    putText('==================');
    shell('tail -n4 /var/log/apcupsd.events >'.$tmpfile, $status);
  
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if ($line = trim(fgets($ph, 1024))) {
        putText($line);
      }
    }
    fclose($ph);
    @unlink($tmpfile);
    putHtml("</pre>");
  }
}

if (is_executable('/usr/bin/sensors')) {
  if (getPREFdef($global_prefs, 'status_show_hardware_monitoring') === 'yes') {
    putHtml("<h2>Hardware Monitoring:</h2>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    shell('/usr/bin/sensors >'.$tmpfile, $status);
  
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if ($line = rtrim(fgets($ph, 1024))) {
        putText($line);
      }
    }
    fclose($ph);
    @unlink($tmpfile);
    putHtml("</pre>");
  }
}

if ($daemon['syslogd'] > 0) {
  if (getPREFdef($global_prefs, 'status_show_system_logs') !== 'no') {
    putHtml("<h2>Latest System Logs:</h2>");
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    if (($str = getPREFdef($global_prefs, 'status_exclude_logs_cmdstr')) !== '') {
      $exclude_words = explode(' ', $str);
      $exclude_logs = '';
      foreach ($exclude_words as $exclude_word) {
        if ($exclude_word !== '') {
          $exclude_word = strtr($exclude_word, '$`[]\'\\', '......');   // map special chars to dot
          $exclude_logs .= " -e '$exclude_word'";
        }
      }
      shell('grep -v -i '.$exclude_logs.' /var/log/messages | tail -n 30 >'.$tmpfile, $status);
    } else {
      shell('tail -n 30 /var/log/messages >'.$tmpfile, $status);
    }
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if ($line = trim(fgets($ph, 1024))) {
        putText($line);
      }
    }
    fclose($ph);
    @unlink($tmpfile);
    putHtml("</pre>");
  }
}
putHtml('<p style="color: orange;">Asterisk&reg; is a registered trademark of Digium, Inc.</p>');

} // End of HTTP GET
require_once $COMMON.'footer.php';

?>
