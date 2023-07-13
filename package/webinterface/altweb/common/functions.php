<?php

// Copyright (C) 2008-2023 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// functions.php for AstLinux
// 03-25-2008
// 04-02-2008, Added parseRCconfig()
// 04-03-2008, Added getETHinterfaces()
// 04-04-2008, Added getVARdef()
// 04-10-2008, Added getTITLEname()
// 08-20-2008, Added asteriskCMD()
// 09-06-2008, Added restartPROCESS()
// 12-12-2009, Added systemSHUTDOWN()
// 01-12-2012, Added asteriskURLrepo()
// 01-04-2014, Added statusPROCESS()
// 07-11-2019, Added gen_BackupExcludeSuffix_args()
// 08-16-2019, Added arrayCount()
// 04-24-2020, Added MNT_ASTURW_DIR for /mnt/asturw or /oldroot/mnt/asturw
// 12-13-2020, Replace getdns/stubby with unbound for DNS-over-TLS
//
// System location of prefs file
$KD_PREFS_LOCATION = '/mnt/kd/webgui-prefs.txt';

// System location of R/W overlay filesystem
$MNT_ASTURW_DIR = is_dir('/mnt/asturw') ? '/mnt/asturw' : '/oldroot/mnt/asturw';

// Function: putHtml
// Put html string, with new-line
//
function putHtml($arg) {
  echo $arg, "\n";
}

// Function: putText
// Put text string (htmlspecialcharacters), with new-line
//
function putText($arg) {
  echo htmlspecialchars($arg), "\n";
}

// Function: arrayCount
// Return count($array) for array
// Return 0 for non-array (NULL)
//
function arrayCount($array) {

  return(is_array($array) ? count($array) : 0);
}

// Function: shell
// Like system() without output buffer flush
//
function shell($cmd, &$return_val) {

  return(@exec($cmd, $shell_out, $return_val));
}

// Function: restartPROCESS
//
function restartPROCESS($process, $ret_good, $ret_fail, $start = 'start', $wait = '1') {
  $result = $ret_fail;
  $path = getenv('PATH');
  $pathOK = ($path !== FALSE && $path !== '');

  $cmd = 'cd /root';
  if ($process === 'pppoe') {
    if (is_executable('/usr/sbin/pppoe-restart')) {
      $cmd .= ';/usr/sbin/gen-rc-conf';
      $cmd .= ';/usr/sbin/pppoe-restart >/dev/null 2>/dev/null';
    } else {
      $cmd .= ';/usr/sbin/pppoe-stop >/dev/null 2>/dev/null';
      $cmd .= ';sleep 2';
      $cmd .= ';/usr/sbin/pppoe-start >/dev/null 2>/dev/null';
    }
  } elseif ($start === 'start') {
    $cmd .= ';service '.$process.' stop >/dev/null 2>/dev/null';
    $cmd .= ';sleep '.$wait;
    $cmd .= ';/usr/sbin/gen-rc-conf';
    $cmd .= ';service '.$process.' '.$start.' >/dev/null 2>/dev/null';
  } elseif ($start === 'reload') {
    $cmd .= ';service '.$process.' '.$start.' >/dev/null 2>/dev/null';
  } elseif ($start === 'apply') {
    $cmd .= ';/usr/sbin/gen-rc-conf';
    $cmd .= ';/bin/bash -n /etc/rc.conf >/dev/null 2>/dev/null';
  } elseif ($process === 'iptables') {
    $cmd .= ';/usr/sbin/gen-rc-conf';
    $cmd .= ';service iptables restart >/dev/null 2>/dev/null';
  } else {
    $cmd .= ';service '.$process.' stop >/dev/null 2>/dev/null';
    $cmd .= ';sleep '.$wait;
    $cmd .= ';/usr/sbin/gen-rc-conf';
    if ($process === 'openvpn' || $process === 'openvpnclient' ||
        $process === 'ipsec' ||
        $process === 'wireguard') {
      $cmd .= ';service iptables restart >/dev/null 2>/dev/null';
    }
    $cmd .= ';service '.$process.' '.$start.' >/dev/null 2>/dev/null';
  }

  if ($pathOK) {
    putenv('PATH='.$path.':/sbin:/usr/sbin');
  }
  shell($cmd, $status);
  if ($pathOK) {
    putenv('PATH='.$path);
  }
  if ($status == 0) {
    $result = $ret_good;
  }
  return($result);
}

// Function: statusPROCESS
//
function statusPROCESS($process) {

  $str = '';
  $path = '/var/run/';
  $running = ' has Restarted and is Running';
  $stopped = ' is Stopped';

  if ($process === 'asterisk' || $process === 'prosody' || $process === 'slapd' ||
      $process === 'kamailio' || $process === 'unbound') {
    $path .= $process.'/';
  } elseif ($process === 'dynamicdns') {
    if (is_file($path.'ddclient.pid')) {
      $str = $running;
    } else {
      $str = $stopped;
    }
  } elseif ($process === 'stunnel') {
    if (is_file($path.$process.'/server.pid') || is_file($path.$process.'/client.pid')) {
      $str = $running;
    } else {
      $str = $stopped;
    }
  } elseif ($process === 'avahi') {
    if (is_file($path.'avahi-daemon/pid')) {
      $str = $running;
    } else {
      $str = $stopped;
    }
  } elseif ($process === 'ntpd') {
    if (is_file($path.'chrony/chronyd.pid')) {
      $str = $running;
    } else {
      $str = $stopped;
    }
  } elseif ($process === 'ipsec') {
    if (is_file($path.'charon.pid')) {
      $str = $running;
    } else {
      $str = $stopped;
    }
  } elseif ($process === 'ups') {
    if (is_file($path.'upsmon.pid')) {
      $str = $running;
    } else {
      $str = $stopped;
    }
  } elseif ($process === 'zabbix') {
    if (is_file($path.'zabbix_agentd.pid')) {
      $str = $running;
    } else {
      $str = $stopped;
    }
  } elseif ($process === 'dnscrypt') {
    if (is_file($path.'dnscrypt-proxy.pid')) {
      $str = $running;
    } else {
      $str = $stopped;
    }
  } elseif ($process === 'failover') {
    if (is_file($path.'wan-failover.pid')) {
      $str = $running;
    } else {
      $str = $stopped;
    }
  } elseif ($process === 'wireguard') {
    if (is_file('/var/lock/wireguard.lock')) {
      $str = $running;
    } else {
      $str = $stopped;
    }
  } elseif ($process === 'lxc') {
    if (is_file('/var/lock/lxc.lock')) {
      $str = $running;
    } else {
      $str = $stopped;
    }
  } elseif ($process === 'iptables') {
    if (isFIREWALL()) {
      $str = $running;
    } else {
      $str = $stopped;
    }
  }
  if ($str === '') {
    if (is_file($path.$process.'.pid')) {
      $str = $running;
    } else {
      $str = $stopped;
    }
  }
  return($str);
}

// Function: systemSHUTDOWN
//
function systemSHUTDOWN($myself, $result) {
  $count_down_secs = 30;

  shell('/sbin/poweroff -d4 >/dev/null 2>/dev/null &', $status);
  if ($status == 0) {
    header('Location: '.$myself.'?count_down_secs='.$count_down_secs.'&shutdown&result='.$result);
    exit;
  }
}

// Function: systemREBOOT
//
function systemREBOOT($myself, $result, $setup = FALSE) {
  global $global_prefs;

  $count_down_secs = 130;

  if (($adjust = getPREFdef($global_prefs, 'system_reboot_timer_adjust')) !== '') {
    $count_down_secs += (int)$adjust;
  }

  $arch = system_image_arch();

  $cmd = '/sbin/kernel-reboot';
  if (! is_executable($cmd)
    || ((getPREFdef($global_prefs, 'system_reboot_classic_full') === 'yes') && $arch !== 'genx86_64-vm')
    || ((getPREFdef($global_prefs, 'system_reboot_vm_classic_full') !== 'no') && $arch === 'genx86_64-vm')) {
    $cmd = '/sbin/reboot';
    $count_down_secs += 30;
  }

  shell($cmd.' -d4 >/dev/null 2>/dev/null &', $status);
  if ($status == 0) {
    if ($setup) {
      $count_down_secs += 10;
      $opts = '&setup';
    } else {
      $opts = '';
    }
    header('Location: '.$myself.'?count_down_secs='.$count_down_secs.$opts.'&result='.$result);
    exit;
  }
}

// Function: scheduleREBOOT
//
function scheduleREBOOT($delay) {
  $time = time();

  shell('killall reboot >/dev/null 2>/dev/null', $status);

  $delay -= (int)date('G', $time);
  if ($delay > 0) {
    $delay *= 3600;
    $delay -= ((int)date('i', $time) * 60);
    shell('/sbin/reboot -d'.$delay.' >/dev/null 2>/dev/null &', $status);
  }
  return($status == 0 ? TRUE : FALSE);
}

// Function: session_manual_gc
//
function session_manual_gc() {

  if (! isset($_SESSION)) {
    if (($gc_maxlifetime = (int)ini_get('session.gc_maxlifetime')) > 0) {
      foreach (glob('/tmp/sess_*') as $globfile) {
        if (is_file($globfile)) {
          if ((time() - filemtime($globfile)) > $gc_maxlifetime) {
            @unlink($globfile);
          }
        }
      }
    }
  }
}

// Function: updateCRON
//
function updateCRON($user, $ret_good, $ret_fail) {
  $result = $ret_fail;

  shell('echo "'.$user.'" >/mnt/kd/crontabs/cron.update 2>/dev/null', $status);
  if ($status == 0) {
    $result = $ret_good;
  }
  return($result);
}

// Function: includeTOPICinfo
//
function includeTOPICinfo($topic) {

  $str = '&nbsp;';
  $str .= '<a href="/info.php?topic='.$topic.'" target="_blank">';
  $str .= '<img src="/common/topicinfo.gif" alt="" title="Topic: '.$topic.'" class="topicinfo" /></a>';

  return($str);
}

// Function: inStringList
//
function inStringList($match, $str, $chr = ' ') {

  $strtokens = explode($chr, $str);
  foreach ($strtokens as $value) {
    if ((string)$value === (string)$match) {
      return(TRUE);
    }
  }
  return(FALSE);
}

// Function: secs2minsec
// Change seconds to min:sec format
//
function secs2minsec($secs) {
  $min = (string)((int)($secs / 60));
  $sec = (string)((int)($secs % 60));

  $min = str_pad($min, 1, '0', STR_PAD_LEFT);
  $sec = str_pad($sec, 2, '0', STR_PAD_LEFT);
  $minsec = $min.':'.$sec;

  return($minsec);
}

// Function: secs2hourminsec
// Change seconds to hour:min:sec format
//
function secs2hourminsec($secs) {
  $hour = (string)((int)($secs / 3600));
  $min = (string)((int)(($secs - (3600 * $hour)) / 60));
  $sec = (string)((int)(($secs - (3600 * $hour)) % 60));

  $hour = str_pad($hour, 1, '0', STR_PAD_LEFT);
  $min = str_pad($min, 2, '0', STR_PAD_LEFT);
  $sec = str_pad($sec, 2, '0', STR_PAD_LEFT);
  $hourminsec = $hour.':'.$min.':'.$sec;

  return($hourminsec);
}

// Function: getARNOplugins
//
//
function getARNOplugins() {
  $dir = '/mnt/kd/arno-iptables-firewall/plugins';
  if (! is_dir($dir)) {
    return(FALSE);
  }

  // Find the currently active plugins
  $active = array();
  $active_file = '/var/tmp/aif_active_plugins';
  if (is_file($active_file)) {
    $cmd = "sed -n -r -e 's|^.*/plugins/[0-9][0-9](.*)\\.plugin$|\\1|p' $active_file";
    @exec($cmd, $active);
  }

  $tmpfile = tempnam("/tmp", "PHP_");
  $cmd = 'grep -m1 -H \'^ENABLED=\' '.$dir.'/*.conf |';
  $cmd .= 'sed -e \'s/ENABLED=//\' -e \'s/"//g\'';
  $cmd .= ' >'.$tmpfile;
  @exec($cmd);
  $ph = @fopen($tmpfile, "r");
  while (! feof($ph)) {
    if (($line = trim(fgets($ph, 1024))) !== '') {
      if (($pos = strpos($line, ':')) !== FALSE) {
        $linetokens = explode(':', $line);
        if ($linetokens[1] === '0') {
          $value = '0~Disabled';
        } elseif ($linetokens[1] === '1')  {
          $value = '1~Enabled';
        } else {
          $value = '0~Undefined';
        }
        $plugin_name = basename($linetokens[0], '.conf');
        foreach ($active as $active_name) {
          if ($active_name === $plugin_name) {
            $value = substr($value, 0, 2).'Active';
            break;
          }
        }
        $plugins[$linetokens[0]] = $value;
      }
    }
  }
  fclose($ph);
  @unlink($tmpfile);

  if (is_null($plugins)) {
    return(FALSE);
  }
  return($plugins);
}

// Function: getETHinterfaces
//
//
function getETHinterfaces() {
  $id = 0;
  $output = array();
  $cmd = '/sbin/ip -o link show 2>/dev/null | cut -d\':\' -f2';
  @exec($cmd, $output);
  foreach ($output as $line) {
    $eth = trim($line);
    if (($pos = strpos($eth, '@')) !== FALSE) {
      $eth = substr($eth, 0, $pos);
    }
    if ($eth !== 'lo' &&
        strncmp($eth, 'wg', 2) &&
        strncmp($eth, 'ppp', 3) &&
        strncmp($eth, 'tun', 3) &&
        strncmp($eth, 'tap', 3) &&
        strncmp($eth, 'sit', 3) &&
        strncmp($eth, 'veth', 4) &&
        strncmp($eth, 'ip6tun', 6) &&
        strncmp($eth, 'ip6pd', 5) &&
        strncmp($eth, 'dummy', 5)) {
          $eth_R[$id] = $eth;
          $id++;
    }
  }
  return($eth_R);
}

// Function: getVARdef
//
//
function getVARdef($db, $var, $cur = NULL) {
  $value = '';
  if (is_null($db)) {
    return($value);
  }
  if (isset($db['data']["$var"])) {
    return($db['data']["$var"]);
  }

  // no matches, check for currrent config
  if (is_null($cur)) {
    return($value);
  }
  if (isset($cur['data']["$var"])) {
    return($cur['data']["$var"]);
  }
  return($value);
}

// Function: string2RCconfig
//
function string2RCconfig($str) {

  if (get_magic_quotes_gpc()) {
    $str = stripslashes($str);
  }
  $str = str_replace('\\', '\\\\', $str);
  $str = str_replace('$', '\\$', $str);
  $str = str_replace('`', '\\`', $str);
  $str = str_replace('"', '\\"', $str);
  return($str);
}

// Function: RCconfig2string
//
function RCconfig2string($str) {

  $str = str_replace('\\$', '$', $str);
  $str = str_replace('\\`', '`', $str);
  $str = str_replace('\\"', '"', $str);
  $str = str_replace('\\\\', '\\', $str);
  return($str);
}

// Function: stripshellsafe
//
function stripshellsafe($str) {

  if (get_magic_quotes_gpc()) {
    $str = stripslashes($str);
  }
  $str = str_replace('$', '', $str);
  $str = str_replace('`', '', $str);
  $str = str_replace('"', '', $str);
  $str = str_replace('\\', '', $str);
  return($str);
}

// Function: tuq (Trim Un-Quote for Shell)
//
function tuq($str) {

  $str = stripshellsafe($str);
  $str = trim($str);
  return($str);
}

// Function: tuqp (Trim Un-Quote for Prefs)
//
function tuqp($str) {

  if (get_magic_quotes_gpc()) {
    $str = stripslashes($str);
  }
  $str = str_replace('"', '', $str);
  $str = str_replace('\\', '', $str);
  $str = trim($str);
  return($str);
}

// Function: tuqd (Trim Un-Quote for Data)
//
function tuqd($str) {

  if (get_magic_quotes_gpc()) {
    $str = stripslashes($str);
  }
  $str = str_replace('"', '', $str);
  $str = str_replace('\\', '', $str);
  $str = trim($str);
  return($str);
}

// Function: parseRCconfig
//
function parseRCconf($conffile) {

  $tmpfile = tempnam("/tmp", "PHP_");
  @exec("sed -e 's/^#.*//' -e '/^$/ d' ".$conffile.' >'.$tmpfile);
  $ph = @fopen($tmpfile, "r");
  while (! feof($ph)) {
    if (($line = trim(fgets($ph, 1024))) !== '') {
      if (($pos = strpos($line, '=')) !== FALSE) {
        $var = trim(substr($line, 0, $pos), ' ');
        $line = substr($line, ($pos + 1));
        if (($begin = strpos($line, '"')) !== FALSE) {
          if (($end = strrpos($line, '"')) !== FALSE) {
            if ($begin == $end) {  // multi-line definition, single quote
              while (! feof($ph)) {
                if (($qstr = rtrim(fgets($ph, 1024))) !== '') {
                  if (($end = strrpos($qstr, '"')) !== FALSE && ! ($end > 0 && substr($qstr, $end - 1, 1) === '\\')) {
                    if (($pos = strpos($qstr, '#', $end)) !== FALSE) {
                      $qstr = substr($qstr, 0, $pos);
                    }
                    $line .= "\n".$qstr;
                    break;
                  } else {  // no quote, comments not allowed
                    $line .= "\n".$qstr;
                  }
                }
              }
            } else {  // single-line with quotes
              if (($pos = strpos($line, '#', $end)) !== FALSE) {
                $line = substr($line, 0, $pos);
              }
            }
          }
        } else {  // single-line with no quotes
          if (($pos = strpos($line, '#')) !== FALSE) {
            $line = substr($line, 0, $pos);
          }
        }
        $value = trim($line, ' ');
        if (substr($value, 0, 1) === '"' && substr($value, -1, 1) === '"') {
          $value = substr($value, 1, strlen($value) - 2);
          $value = trim($value, ' ');
        }
        if ($var === 'NTPSERV' || $var === 'NTPSERVS') {
          if (is_file('/mnt/kd/chrony.conf')) {
            $value = '#NTP server is specified in /mnt/kd/chrony.conf';
          }
        }
        if ($var === 'UPS_DRIVER' || $var === 'UPS_DRIVER_PORT') {
          if (is_file('/mnt/kd/ups/ups.conf')) {
            $value = '#UPS driver is specified in /mnt/kd/ups/ups.conf';
          }
        }
        if ($var === 'ASTBACK_PATHS' ||
                  $var === 'ASTBACK_FILE' ||
                  $var === 'AUTOMODS' ||
                  $var === 'ISSUE' ||
                  $var === 'NETISSUE') {
          $var = '';
        }
        if ($var !== '') {
          $db['data']["$var"] = $value;
        }
      }
    }
  }
  fclose($ph);
  @unlink($tmpfile);

  $db['conffile'] = $conffile;
  return($db);
}

// Function: get_HOSTNAME_DOMAIN
//
function get_HOSTNAME_DOMAIN() {
  $hostname_domain = '';

  // System location of gui.network.conf file
  $NETCONFFILE = '/mnt/kd/rc.conf.d/gui.network.conf';

  if (is_file($NETCONFFILE)) {
    $netvars = parseRCconf($NETCONFFILE);
    if (($hostname = getVARdef($netvars, 'HOSTNAME')) !== '') {
      if (($domain = getVARdef($netvars, 'DOMAIN')) !== '') {
        $hostname_domain = $hostname.'.'.$domain;
      }
    }
  }
  return($hostname_domain);
}

// Function: asteriskURLrepo
//
function asteriskURLrepo() {

  $version = trim(shell_exec('/usr/sbin/asterisk -V ; [ ! -f /usr/lib/asterisk/modules/res_pjproject.so ] && echo "se"'));

  $ver = '';
  if (($tokens = preg_split('/[\s.]+/', $version)) !== FALSE) {
    if (isset($tokens[1])) {
      $ver = $tokens[1];
      if ($tokens[count($tokens)-1] === 'se') {
        $ver .= 'se';
      }
    }
  }
  if ($ver == '') {
    $ver = '16';
  }
  $str = 'https://mirror.astlinux-project.org/ast'.$ver.'-firmware-1.x';

  return($str);
}

// Function: asteriskERROR
//
function asteriskERROR($result) {

  if ($result == 1101) {
    $str = 'The "manager.conf" file is not enabled for 127.0.0.1 on port 5038.';
  } elseif ($result == 1102) {
    $str = 'The "manager.conf" file does not have the [webinterface] user defined properly.';
  } else {
    $str = 'Asterisk not responding.';
  }
  return($str);
}

// Function: asteriskMGR
//
function asteriskMGR($cmd, $fname) {

  if (($socket = @fsockopen('127.0.0.1', '5038', $errno, $errstr, 5)) === FALSE) {
    return(1101);
  }
  fputs($socket, "Action: login\r\n");
  fputs($socket, "Username: webinterface\r\n");
  fputs($socket, "Secret: webinterface\r\n");
  fputs($socket, "Events: off\r\n\r\n");

  fputs($socket, "Action: command\r\n");
  fputs($socket, "Command: $cmd\r\n\r\n");

  fputs($socket, "Action: logoff\r\n\r\n");

  stream_set_timeout($socket, 5);
  $info = stream_get_meta_data($socket);
  $login_success = FALSE;
  $output_header = FALSE;
  while (! feof($socket) && ! $info['timed_out']) {
    $line = fgets($socket, 256);
    $info = stream_get_meta_data($socket);
    if (! $login_success) {
      if (strncasecmp($line, 'Response: Success', 17) == 0) {
        $login_success = TRUE;
      } elseif (strncasecmp($line, 'Response: Error', 15) == 0) {
        while (! feof($socket) && ! $info['timed_out']) {
          fgets($socket, 256);
          $info = stream_get_meta_data($socket);
        }
        fclose($socket);
        return(1102);
      }
    }
    if (strncasecmp($line, 'Privilege: Command', 18) == 0) {
      break;
    }
    if (strncasecmp($line, 'Message: Command output follows', 31) == 0) {
      $output_header = TRUE;
      break;
    }
  }
  // begin command data
  if ($fname !== '') {
    if (($fp = @fopen($fname,"wb")) !== FALSE) {
      while (! feof($socket) && ! $info['timed_out']) {
        $line = fgets($socket, 1024);
        $info = stream_get_meta_data($socket);
        if (strncasecmp($line, '--END COMMAND--', 15) == 0) {
          break;
        }
        if ($output_header && strncasecmp($line, 'Output: ', 8) != 0) {
          break;
        }
        fwrite($fp, $output_header ? substr($line, 8) : $line);
      }
      fclose($fp);
    }
  }
  // end command data
  while (! feof($socket) && ! $info['timed_out']) {
    fgets($socket, 256);
    $info = stream_get_meta_data($socket);
  }
  fclose($socket);

  return($info['timed_out'] ? 1103 : 0);
}

// Function: asteriskCMD
//
function asteriskCMD($cmd, $fname) {
  global $global_prefs;

  if (getPREFdef($global_prefs, 'status_asterisk_manager') === 'no') {
    $cmd = str_replace('"', '\"', $cmd);
    if ($fname === '') {
      $fname = '/dev/null';
    }
    shell('/usr/sbin/asterisk -rnx "'.$cmd.'" >'.$fname, $status);
  } else {
    $status = asteriskMGR($cmd, $fname);
  }
  return($status);
}

// Function: parseAstDB
//
function parseAstDB($family) {
  $id = 0;
  $db['family'] = $family;
  $tmpfile = tempnam("/tmp", "PHP_");
  $status = asteriskCMD('database show '.$family, $tmpfile);
  if (($db['status'] = $status) == 0) {
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) !== '') {
        if (($pos = strpos($line, ': ')) !== FALSE) {
          $keystr = substr($line, 0, $pos);
          $valuestr = substr($line, ($pos + 2));
          $keytokens = explode('/', $keystr);
          $db['data'][$id]['key'] = trim($keytokens[2]);
          $db['data'][$id]['value'] = trim($valuestr);
          $id++;
        }
      }
    }
    fclose($ph);
  }
  @unlink($tmpfile);

  return($db);
}

// Function: putAstDB
//
function putAstDB($family, $key, $value) {
  $status = asteriskCMD('database put '.$family.' '.$key.' "'.$value.'"', '');
  return($status);
}

// Function: delAstDB
//
function delAstDB($family, $key) {
  $status = asteriskCMD('database del '.$family.' '.$key, '');
  return($status);
}

// Function: getRebootDelayMenu
//
function getRebootDelayMenu() {
  $start = (int)date('G');
  $start = ($start % 2 == 0) ? $start + 1 : $start + 2;

  $menuitems['Now'] = 0;
  for ($i = 0; $i < 24; $i += 2) {
    $key = str_pad(($start % 24), 2, '0', STR_PAD_LEFT).':00';
    $menuitems[$key] = $start;
    $start += 2;
  }
  $menuitems['Cancel'] = -1;

  return($menuitems);
}

// Function: pad_ipv4_str
//
function pad_ipv4_str($ip) {
  $str = $ip;

  if (strpos($ip, ':') === FALSE && strpos($ip, '.') !== FALSE) {
    $tokens = explode('.', $ip);
    if (arrayCount($tokens) == 4) {
      $str = str_pad($tokens[0], 3, '0', STR_PAD_LEFT).'.'.
             str_pad($tokens[1], 3, '0', STR_PAD_LEFT).'.'.
             str_pad($tokens[2], 3, '0', STR_PAD_LEFT).'.'.
             str_pad($tokens[3], 3, '0', STR_PAD_LEFT);
    }
  }
  return($str);
}

// Function: compressIPV6addr
//
function compressIPV6addr($addr) {
  if (strpos($addr, ':') !== FALSE) {
    return(inet_ntop(inet_pton($addr)));
  }
  return($addr);
}

// Function: expandIPV6addr
//
function expandIPV6addr($addr) {
  if (strpos($addr, ':') !== FALSE) {
    $hex = unpack('H*hex', inet_pton($addr));
    $addr = substr(preg_replace('/([A-Fa-f0-9]{4})/', '$1:', $hex['hex']), 0, -1);
  }
  return($addr);
}

// Function: is_addon_package
//
function is_addon_package($pkg) {

  $pkg_dir = '/stat/var/packages/'.$pkg;
  return(is_dir($pkg_dir));
}

// Function: is_mac2vendor
//
function is_mac2vendor() {

  $mac_vendor_db = '/usr/share/oui-db';
  return(is_dir($mac_vendor_db));
}

// Function: laa_unicast
//
function laa_unicast($mac) {

  $lower_4bits = substr($mac, 1, 1);
  $match_hex = array('2', '6', 'A', 'E');

  return(in_array($lower_4bits, $match_hex));
}

// Function: mac2vendor
//
function mac2vendor($mac) {

  $vendor = '';
  $mac_vendor_db = '/usr/share/oui-db';
  if (is_dir($mac_vendor_db)) {
    $match = strtoupper(str_replace(':', '', $mac));
    $match = substr($match, 0, 6);
    if (strlen($match) == 6) {
      if (($lines = @file($mac_vendor_db.'/xxxxx'.$match[5], FILE_IGNORE_NEW_LINES)) !== FALSE) {
        if (($grep = current(preg_grep("/^$match~/", $lines))) !== FALSE) {
          $vendor = substr($grep, 7);
        } elseif (laa_unicast($match)) {
          $vendor = '(Randomized MAC Address)';
        }
      }
    }
  }
  return($vendor);
}

// Function: gen_BackupExcludeSuffix_args
//
function gen_BackupExcludeSuffix_args($suffix_str) {

  $str = '';

  if ($suffix_str !== '') {
    $suffixes = preg_split('/[ ,]+/', $suffix_str);
    foreach ($suffixes as $suffix) {
      if ($suffix !== '') {
        $suffix = strtr($suffix, '$`[]\'\\', '......');   // map special chars to dot
        $str .= " --exclude '*.$suffix'";
      }
    }
  }
  return($str);
}

// Function: getPREFdef
//
function getPREFdef($db, $var)
{
  $value = '';
  if (isset($db['data']["$var"])) {
    return($db['data']["$var"]);
  }
  return($value);
}

// Function: isDNS_TLS
//
function isDNS_TLS()
{
  return(is_file('/var/run/unbound/unbound.pid'));
}

// Function: isDNSCRYPT
//
function isDNSCRYPT()
{
  return(is_file('/var/run/dnscrypt-proxy.pid'));
}

// Function: isFIREWALL
//
function isFIREWALL()
{
  return(is_file('/var/tmp/aif_active_plugins'));
}

// Function: getTABname
//
function getTABname()
{
  if (isset($_SERVER['SCRIPT_NAME'])) {
    $str_R = basename($_SERVER['SCRIPT_NAME'], '.php');
  } else {
    $str_R = '';
  }
  return($str_R);
}

// Function: getPHPusername
//
function getPHPusername()
{
  if (isset($_SERVER['REMOTE_USER'])) {
    $str_R = $_SERVER['REMOTE_USER'];
  } else {
    $str_R = '';
  }
  return($str_R);
}

// Function: getSYSlocation
//
function getSYSlocation($base = '')
{
  if (($end = strrpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['SCRIPT_NAME'])) === FALSE) {
    $str_R = '';
  } else {
    if (($str_R = substr($_SERVER['SCRIPT_FILENAME'], 0, $end)) !== '') {
      $str_R .= $base;
    }
  }
  return($str_R);
}

// Function: getPASSWDlocation
//
function getPASSWDlocation()
{
  if (($end = strrpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['SCRIPT_NAME'])) === FALSE) {
    $str_R = '';
  } else {
    if (($str_R = substr($_SERVER['SCRIPT_FILENAME'], 0, $end)) !== '') {
      $str_R .= '/admin/.htpasswd';
    }
  }
  return($str_R);
}

// Function: getPREFSlocation
//
function getPREFSlocation()
{
  global $KD_PREFS_LOCATION;

  if (is_file($KD_PREFS_LOCATION)) {
    $str_R = $KD_PREFS_LOCATION;
  } elseif (($end = strrpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['SCRIPT_NAME'])) === FALSE) {
    $str_R = '';
  } else {
    if (($str_R = substr($_SERVER['SCRIPT_FILENAME'], 0, $end)) !== '') {
      $str_R .= '/prefs.txt';
    }
  }
  return($str_R);
}

// Function: parsePrefs
//
function parsePrefs($pfile)
{
  if ($pfile !== '') {
    if (is_file($pfile)) {
      if (($ph = @fopen($pfile, "r")) !== FALSE) {
        while (! feof($ph)) {
          if (($line = trim(fgets($ph, 1024))) !== '') {
            if ($line[0] !== '#') {
              if (($pos = strpos($line, '=')) !== FALSE) {
                $var = trim(substr($line, 0, $pos), ' ');
                $value = trim(substr($line, ($pos + 1)), '" ');
                if ($var !== '' && $value !== '') {
                  $db['data']["$var"] = $value;
                }
              }
            }
          }
        }
        fclose($ph);
      }
    }
  }
  return($db);
}

// Function: system_image_arch
//
function system_image_arch() {

  $arch = '';
  if (($cmdline = trim(@file_get_contents('/proc/cmdline'))) !== '') {
    $tokens = explode(' ', $cmdline);
    foreach ($tokens as $value) {
      $cmd = explode('=', $value);
      if ($cmd[0] === 'astlinux' && $cmd[1] != '') {
        $arch = $cmd[1];
        break;
      }
    }
  }
  return ($arch);
}

// Function: system_timezone
//
function system_timezone() {

  if (($tz = trim(@file_get_contents('/etc/timezone'))) === '') {
    $tz = @date_default_timezone_get();
  }
  return ($tz);
}

// Set system timezone if not in php.ini
if (ini_get('date.timezone') == '') {
  date_default_timezone_set(system_timezone());
}

// Set globals
$global_prefs = parsePrefs(getPREFSlocation());
$global_user = getPHPusername();
$global_admin = ($global_user === '' || $global_user === 'admin');
$global_staff = ($global_admin || $global_user === 'staff');
$global_staff_disable_voicemail = ($global_user === 'staff' && (getPREFdef($global_prefs, 'tab_voicemail_disable_staff') === 'yes'));
$global_staff_disable_monitor = ($global_user === 'staff' && (getPREFdef($global_prefs, 'tab_monitor_disable_staff') === 'yes'));
$global_staff_disable_followme = ($global_user === 'staff' && (getPREFdef($global_prefs, 'tab_followme_disable_staff') === 'yes'));
$global_staff_enable_sqldata = ($global_user === 'staff' && (getPREFdef($global_prefs, 'tab_sqldata_disable_staff') === 'no'));
$global_staff_disable_staff = ($global_user === 'staff' && (getPREFdef($global_prefs, 'tab_staff_disable_staff') === 'yes'));
$global_staff_enable_dnshosts = ($global_user === 'staff' && (getPREFdef($global_prefs, 'tab_dnshosts_disable_staff') === 'no'));
$global_staff_enable_xmpp = ($global_user === 'staff' && (getPREFdef($global_prefs, 'tab_xmpp_disable_staff') === 'no'));
$global_staff_enable_cli = ($global_user === 'staff' && (getPREFdef($global_prefs, 'tab_cli_disable_staff') === 'no'));
?>
