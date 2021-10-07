<?php

// Copyright (C) 2008-2018 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// firewall.php for AstLinux
// 11-18-2008
// 11-24-2008, Added support for arno firewall version 1.9
// 11-25-2008, Added Traffic Shaping support
// 12-12-2008, Added DMZ support
// 01-30-2009, Added Block Hosts
// 03-02-2009, Added Arno Upgrade
// 07-14-2009, Added LAN to LAN support
// 08-23-2009, Added TCP/UDP protocol
// 10-14-2010, Added IPv6 support
// 03-28-2012, Added NAT EXT support
// 07-16-2012, Added "Pass LAN->EXT" and "Pass DMZ->EXT" actions
// 01-27-2014, Added "Log Denied DMZ interface packets"
// 06-08-2014, Added support for multiple "Allow OpenVPN" LAN interfaces
// 06-12-2016, Added "Pass LAN->LAN" action
// 07-10-2016, Added Deny LAN to DMZ for specified LAN Interfaces
// 09-14-2016, Added BLOCK_NETSET_DIR support
// 01-05-2017, Added BLOCKED_HOST_LOG direction support
// 11-06-2017, Added WIREGUARD_ALLOWLAN support
// 10-12-2018, Added WIREGUARD_ALLOW_OPENVPN support
// 10-04-2021, Added CAKE traffic shaper support
//
// System location of /mnt/kd/rc.conf.d directory
$FIREWALLCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.firewall.conf file
$FIREWALLCONFFILE = '/mnt/kd/rc.conf.d/gui.firewall.conf';
// Traffic Shaper Plugin
$TRAFFIC_SHAPER_PLUGIN = 'traffic-shaper';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$action_label = array (
  'NAT_EXT_LAN' => 'NAT EXT->LAN',
  'NAT_EXT_DMZ' => 'NAT EXT->DMZ',
  'PASS_EXT_LOCAL' => 'Pass EXT->Local',
  'PASS_EXT_LAN' => 'Pass EXT->LAN',
  'PASS_EXT_DMZ' => 'Pass EXT->DMZ',
  'PASS_DMZ_LOCAL' => 'Pass DMZ->Local',
  'PASS_DMZ_LAN' => 'Pass DMZ->LAN',
  'PASS_LAN_LAN' => 'Pass LAN->LAN',
  'DENY_LAN_EXT' => 'Deny LAN->EXT',
  'DENY_LAN_LOCAL' => 'Deny LAN->Local',
  'DENY_LOCAL_EXT' => 'Deny Local->EXT',
  'DENY_EXT_DMZ' => 'Deny EXT->DMZ',
  'DENY_DMZ_EXT' => 'Deny DMZ->EXT',
  'PASS_LAN_EXT' => 'Pass LAN->EXT',
  'PASS_DMZ_EXT' => 'Pass DMZ->EXT',
  'LOG_LOCAL_OUT' => 'Log Local Out',
  'LOG_LOCAL_IN' => 'Log Local In'
);

$action_arno = array (
  'NAT_EXT_LAN' => 'NAT_FORWARD_xxx',
  'NAT_EXT_DMZ' => 'NAT_FORWARD_xxx',
  'PASS_EXT_LOCAL' => 'HOST_OPEN_xxx',
  'PASS_EXT_LAN' => 'INET_FORWARD_xxx',
  'PASS_EXT_DMZ' => 'INET_DMZ_HOST_OPEN_xxx',
  'PASS_DMZ_LOCAL' => 'DMZ_HOST_OPEN_xxx',
  'PASS_DMZ_LAN' => 'DMZ_LAN_HOST_OPEN_xxx',
  'PASS_LAN_LAN' => 'LAN_LAN_HOST_OPEN_xxx',
  'DENY_LAN_EXT' => 'LAN_INET_HOST_DENY_xxx',
  'DENY_LAN_LOCAL' => 'LAN_HOST_DENY_xxx',
  'DENY_LOCAL_EXT' => 'HOST_DENY_xxx_OUTPUT',
  'DENY_EXT_DMZ' => 'INET_DMZ_HOST_DENY_xxx',
  'DENY_DMZ_EXT' => 'DMZ_INET_HOST_DENY_xxx',
  'PASS_LAN_EXT' => 'LAN_INET_HOST_OPEN_xxx',
  'PASS_DMZ_EXT' => 'DMZ_INET_HOST_OPEN_xxx',
  'LOG_LOCAL_OUT' => 'LOG_HOST_OUTPUT_xxx',
  'LOG_LOCAL_IN' => 'LOG_HOST_INPUT_xxx'
);

$proto_label = array (
  'TCP' => 'TCP',
  'UDP' => 'UDP',
  'TCP/UDP' => 'TCP/UDP',
  '50' => 'ESP',
  '51' => 'AH',
  '47' => 'GRE',
  '1' => 'ICMP',
  '58' => 'ICMPv6',
  '41' => '6to4'
);

$lan_permutations_label = array (
  'INTIF' => '1st',
  'INT2IF' => '2nd',
  'INT3IF' => '3rd',
  'INT4IF' => '4th',
  'INTIF INT2IF' => '1st, 2nd',
  'INTIF INT3IF' => '1st, 3rd',
  'INTIF INT4IF' => '1st, 4th',
  'INT2IF INT3IF' => '2nd, 3rd',
  'INT2IF INT4IF' => '2nd, 4th',
  'INT3IF INT4IF' => '3rd, 4th',
  'INTIF INT2IF INT3IF' => '1st, 2nd, 3rd',
  'INTIF INT2IF INT4IF' => '1st, 2nd, 4th',
  'INTIF INT3IF INT4IF' => '1st, 3rd, 4th',
  'INT2IF INT3IF INT4IF' => '2nd, 3rd, 4th',
  'INTIF INT2IF INT3IF INT4IF' => '1st, 2nd, 3rd, 4th'
);

$allowlans_label = array (
  'INTIF INT2IF' => '1st + 2nd',
  'INTIF INT3IF' => '1st + 3rd',
  'INTIF INT4IF' => '1st + 4th',
  'INT2IF INT3IF' => '2nd + 3rd',
  'INT2IF INT4IF' => '2nd + 4th',
  'INT3IF INT4IF' => '3rd + 4th',
  'INTIF INT2IF INT3IF' => '1st + 2nd + 3rd',
  'INTIF INT2IF INT4IF' => '1st + 2nd + 4th',
  'INTIF INT3IF INT4IF' => '1st + 3rd + 4th',
  'INT2IF INT3IF INT4IF' => '2nd + 3rd + 4th',
  'INTIF INT2IF INT3IF INT4IF' => '1st + 2nd + 3rd + 4th',
  'INTIF INT2IF~INTIF INT3IF' => '1st + 2nd, 1st + 3rd',
  'INTIF INT2IF~INTIF INT4IF' => '1st + 2nd, 1st + 4th',
  'INTIF INT2IF~INT2IF INT3IF' => '1st + 2nd, 2nd + 3rd',
  'INTIF INT2IF~INT2IF INT4IF' => '1st + 2nd, 2nd + 4th',
  'INTIF INT2IF~INT3IF INT4IF' => '1st + 2nd, 3rd + 4th',
  'INTIF INT3IF~INTIF INT4IF' => '1st + 3rd, 1st + 4th',
  'INTIF INT3IF~INT2IF INT3IF' => '1st + 3rd, 2nd + 3rd',
  'INTIF INT3IF~INT2IF INT4IF' => '1st + 3rd, 2nd + 4th',
  'INTIF INT3IF~INT3IF INT4IF' => '1st + 3rd, 3rd + 4th',
  'INTIF INT4IF~INT2IF INT3IF' => '1st + 4th, 2nd + 3rd',
  'INTIF INT4IF~INT2IF INT4IF' => '1st + 4th, 2nd + 4th',
  'INTIF INT4IF~INT3IF INT4IF' => '1st + 4th, 3rd + 4th',
  'INT2IF INT3IF~INT2IF INT4IF' => '2nd + 3rd, 2nd + 4th',
  'INT2IF INT3IF~INT3IF INT4IF' => '2nd + 3rd, 3rd + 4th',
  'INT2IF INT4IF~INT3IF INT4IF' => '2nd + 4th, 3rd + 4th'
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

$lan_default_policy_label = array (
  '0' => 'Pass LAN->EXT',
  '1' => 'Deny LAN->EXT'
);

$dmz_default_policy_label = array (
  '0' => 'Pass DMZ->EXT',
  '1' => 'Deny DMZ->EXT'
);

$log_blocked_label = array (
  '0' => 'Disabled',
  '1' => 'Inbound &amp; Outbound',
  '2' => 'Inbound only',
  '3' => 'Outbound only'
);

// Get arno firewall version
//$MY_VERSION = trim(shell_exec('grep -m1 \'^MY_VERSION=\' /usr/sbin/arno-iptables-firewall | sed -e \'s/MY_VERSION=//\' -e \'s/"//g\''));
//$arno_vers = (strncmp($MY_VERSION, '1.8.', 4) == 0) ? 18 : 19;
//

// Function: getARNOvars
//
function getARNOvars($db) {
  global $action_label;
  global $action_arno;

  $sep = '~';
  $col = '~';

  foreach ($action_label as $key => $value) {
    $tcp = '';
    $udp = '';
    $ip = '';

    if (($n = arrayCount($db['data'])) > 0) {
      for ($i = 0; $i < $n; $i++) {
        $data = $db['data'][$i];
        if ($data['enabled'] !== '0' && $data['action'] === $key) {
          $is_ip = is_numeric($data['proto']);
          switch ($data['action']) {
          case 'PASS_EXT_LOCAL':
          case 'PASS_DMZ_LOCAL':
          case 'DENY_LAN_LOCAL':
          case 'LOG_LOCAL_IN':
            if ($is_ip) {
              $str = $data['s_addr'].$sep.$data['proto'];
            } else {
              $str = $data['s_addr'].$sep.$data['s_lport'];
              if ($data['s_uport'] !== '') {
                $str .= ':'.$data['s_uport'];
              }
            }
            break;
          case 'NAT_EXT_LAN':
          case 'NAT_EXT_DMZ':
            $str = ($data['e_addr'] === '' || $data['e_addr'] === '0/0') ? '' : $data['e_addr'].'#';
            if ($is_ip) {
              $str .= $data['s_addr'].$col.$data['proto'].'>'.$data['d_addr'];
            } else {
              $str .= $data['s_addr'].$col.$data['s_lport'];
              if ($data['s_uport'] !== '') {
                $str .= ':'.$data['s_uport'];
              }
              $str .= '>'.$data['d_addr'];
              if ($data['d_lport'] !== '') {
                $str .= $col.$data['d_lport'];
              }
            }
            break;
          case 'PASS_EXT_LAN':
          case 'PASS_EXT_DMZ':
          case 'DENY_LAN_EXT':
          case 'DENY_EXT_DMZ':
          case 'DENY_DMZ_EXT':
          case 'PASS_DMZ_LAN':
          case 'PASS_LAN_LAN':
          case 'PASS_LAN_EXT':
          case 'PASS_DMZ_EXT':
            if ($is_ip) {
              $str = $data['s_addr'].'>'.$data['d_addr'].$col.$data['proto'];
            } else {
              $str = $data['s_addr'].'>'.$data['d_addr'];
              if ($data['d_lport'] !== '') {
                $str .= $col.$data['d_lport'];
                if ($data['d_uport'] !== '') {
                  $str .= ':'.$data['d_uport'];
                }
              }
            }
            break;
          case 'DENY_LOCAL_EXT':
          case 'LOG_LOCAL_OUT':
            if ($is_ip) {
              $str = $data['d_addr'].$sep.$data['proto'];
            } else {
              $str = $data['d_addr'].$sep.$data['d_lport'];
              if ($data['d_uport'] !== '') {
                $str .= ':'.$data['d_uport'];
              }
            }
            break;
          default:
            $str = '';
          }
          if ($is_ip) {
            $ip .= $str.' ';
          } elseif ($data['proto'] === 'TCP') {
            $tcp .= $str.' ';
          } elseif ($data['proto'] === 'UDP') {
            $udp .= $str.' ';
          } elseif ($data['proto'] === 'TCP/UDP') {
            $tcp .= $str.' ';
            $udp .= $str.' ';
          }
        }
      }
    }
    $var = str_replace('xxx', 'TCP', $action_arno[$key]);
    $arno[$var] = trim(isset($arno[$var]) ? $tcp.$arno[$var] : $tcp);
    $var = str_replace('xxx', 'UDP', $action_arno[$key]);
    $arno[$var] = trim(isset($arno[$var]) ? $udp.$arno[$var] : $udp);
    $var = str_replace('xxx', 'IP', $action_arno[$key]);
    $arno[$var] = trim(isset($arno[$var]) ? $ip.$arno[$var] : $ip);
  }
  return($arno);
}

// Function: saveFIREWALLsettings
//
function saveFIREWALLsettings($conf_dir, $conf_file, $db, $delete = NULL) {
  global $TRAFFIC_SHAPER_FILE;
  global $TRAFFIC_SHAPER_ENABLE;

  if (is_dir($conf_dir) === FALSE) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.firewall.conf - start ###\n###\n");

  $value = 'GUI_FIREWALL_RULES="';
  fwrite($fp, "### Generic Firewall Rules\n".$value."\n");
  if (($n = arrayCount($db['data'])) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $skip = FALSE;
      if (! is_null($delete)) {
        for ($j = 0; $j < arrayCount($delete); $j++) {
          if ($delete[$j] == $i) {
            $db['data'][$i]['enabled'] = '0';  // required for getARNOvars
            $skip = TRUE;
            break;
          }
        }
      }
      if (! $skip) {
        $value = $db['data'][$i]['enabled'];
        $value .= '~'.$db['data'][$i]['action'];
        $value .= '~'.$db['data'][$i]['proto'];
        $value .= '~'.$db['data'][$i]['s_addr'];
        $value .= '~'.$db['data'][$i]['s_lport'];
        $value .= '~'.$db['data'][$i]['s_uport'];
        $value .= '~'.$db['data'][$i]['d_addr'];
        $value .= '~'.$db['data'][$i]['d_lport'];
        $value .= '~'.$db['data'][$i]['d_uport'];
        $value .= '~';
        if ($db['data'][$i]['comment'] !== '') {
          $value .= str_replace('~', '-', $db['data'][$i]['comment']);
        }
        $value .= '~'.$db['data'][$i]['e_addr'];
        fwrite($fp, $value."\n");
      }
    }
  }
  fwrite($fp, '"'."\n");

  $arno = getARNOvars($db);
  foreach ($arno as $key => $value) {
    if ($value !== '') {
      fwrite($fp, $key.'="'.$value.'"'."\n");
    }
  }

  fwrite($fp, "### Reset Unused\n");
  $value = 'OPEN_TCP=""';
  fwrite($fp, $value."\n");
  $value = 'OPEN_UDP=""';
  fwrite($fp, $value."\n");

  fwrite($fp, "### Options\n");
  $value = 'LAN_INET_DEFAULT_POLICY_DROP="'.$_POST['lan_DP'].'"';
  fwrite($fp, $value."\n");
  $value = 'DMZ_INET_DEFAULT_POLICY_DROP="'.$_POST['dmz_DP'].'"';
  fwrite($fp, $value."\n");
  $value = 'DMZ_DENYLAN="'.(isset($_POST['is_dmz_denylan']) ? $_POST['dmz_denylan'] : '').'"';
  fwrite($fp, $value."\n");
  $value = 'ALLOWLANS="'.(isset($_POST['is_allowlans']) ? $_POST['allowlans'] : '').'"';
  fwrite($fp, $value."\n");
  $value = 'OVPNC_ALLOWLAN="'.(isset($_POST['is_ovpnc_allowlan']) ? $_POST['ovpnc_allowlan'] : '').'"';
  fwrite($fp, $value."\n");
  $value = 'OVPN_ALLOWLAN="'.(isset($_POST['is_ovpn_allowlan']) ? $_POST['ovpn_allowlan'] : '').'"';
  fwrite($fp, $value."\n");
  $value = 'WIREGUARD_ALLOWLAN="'.(isset($_POST['is_wireguard_allowlan']) ? $_POST['wireguard_allowlan'] : '').'"';
  fwrite($fp, $value."\n");
  $value = 'WIREGUARD_ALLOW_OPENVPN="'.(isset($_POST['wireguard_allow_openvpn']) ? 'yes' : 'no').'"';
  fwrite($fp, $value."\n");
  $value = 'OPEN_ICMP='.(isset($_POST['allow_icmp']) ? '1' : '0');
  fwrite($fp, $value."\n");
  $value = 'OPEN_ICMPV6='.(isset($_POST['allow_icmpv6']) ? '1' : '0');
  fwrite($fp, $value."\n");

  fwrite($fp, "### Logging\n");
  $value = 'DMZ_INPUT_DENY_LOG='.(isset($_POST['log_dmz']) ? '1' : '0');
  fwrite($fp, $value."\n");
  $value = 'DMZ_OUTPUT_DENY_LOG='.(isset($_POST['log_dmz']) ? '1' : '0');
  fwrite($fp, $value."\n");
  $value = 'ICMP_REQUEST_LOG='.(isset($_POST['log_icmp']) ? '1' : '0');
  fwrite($fp, $value."\n");
  $value = 'PRIV_TCP_LOG='.(isset($_POST['log_tcp']) ? '1' : '0');
  fwrite($fp, $value."\n");
  $value = 'UNPRIV_TCP_LOG='.(isset($_POST['log_tcp']) ? '1' : '0');
  fwrite($fp, $value."\n");
  $value = 'PRIV_UDP_LOG='.(isset($_POST['log_udp']) ? '1' : '0');
  fwrite($fp, $value."\n");
  $value = 'UNPRIV_UDP_LOG='.(isset($_POST['log_udp']) ? '1' : '0');
  fwrite($fp, $value."\n");
  $value = 'OTHER_IP_LOG='.(isset($_POST['log_other']) ? '1' : '0');
  fwrite($fp, $value."\n");
  $value = 'IGMP_LOG='.(isset($_POST['log_other']) ? '1' : '0'); // igmp tied to other_ip
  fwrite($fp, $value."\n");
  $value = 'FORWARD_DROP_LOG='.(isset($_POST['log_forward']) ? '1' : '0');
  fwrite($fp, $value."\n");

  if (isset($_POST['shaper_enable_type'])) {
    fwrite($fp, "### Traffic Shaping\n");
    $value = 'SHAPETYPE="'.$_POST['shaper_enable_type'].'"';
    fwrite($fp, $value."\n");
    if (($value = tuq($_POST['shaper_extdown'])) === '' || $_POST['shaper_extdown_enable'] === 'no') {
      $value = '0';
    }
    $value = 'EXTDOWN="'.$value.'"';
    fwrite($fp, $value."\n");
    $value = 'EXTUP="'.tuq($_POST['shaper_extup']).'"';
    fwrite($fp, $value."\n");
    $value = 'VOIPPORTS="'.tuq($_POST['shaper_voipports']).'"';
    fwrite($fp, $value."\n");
    $value = 'EXTSHAPE_TUNE_CAKE="'.trim($_POST['cake_llt'].' '.$_POST['cake_ack_filter']).'"';
    fwrite($fp, $value."\n");
  }

  fwrite($fp, "### Block All Traffic\n");
  $value = 'BLOCK_HOSTS="'.tuq($_POST['hosts_blocked']).'"';
  fwrite($fp, $value."\n");
  if (isset($_POST['file_blocked'])) {
    $value = 'BLOCK_HOSTS_FILE="/mnt/kd/blocked-hosts"';
    fwrite($fp, $value."\n");
  }
  if (isset($_POST['block_netset_dir'])) {
    $value = 'BLOCK_NETSET_DIR="/mnt/kd/blocklists"';
    fwrite($fp, $value."\n");
  }
  $value = 'BLOCKED_HOST_LOG="'.$_POST['log_blocked'].'"';
  fwrite($fp, $value."\n");

  fwrite($fp, "### gui.firewall.conf - end ###\n");
  fclose($fp);

  if (! is_null($TRAFFIC_SHAPER_FILE)) {
    if (isset($_POST['shaper_enable_type'])) {
      $shaper_enable = ($_POST['shaper_enable_type'] === '') ? '0' : '1';
      if ($TRAFFIC_SHAPER_ENABLE !== $shaper_enable) {
        editTRAFFICshaper($TRAFFIC_SHAPER_FILE, $shaper_enable);
      }
    }
  }

  return(11);
}

// Function: editTRAFFICshaper
//
function editTRAFFICshaper($fname, $shaper_enable) {

  shell('sed -i \'/^ENABLED=/ s/=.*/='.$shaper_enable.'/\' '.$fname.' >/dev/null', $status);

  return($status);
}

// Function: upgradeARNOfirewall
//
function upgradeARNOfirewall($action) {
  $file = '/usr/sbin/upgrade-arno-firewall';
  $status = 99;

  if (is_file($file)) {
    shell("$file $action >/dev/null 2>/dev/null", $status);
  }

  return($status);
}

// Function: parseFIREWALLconf
//
function parseFIREWALLconf($vars) {
  $id = 0;
  $delim = '~';

  if (($line = getVARdef($vars, 'GUI_FIREWALL_RULES')) !== '') {
    $linetokens = explode("\n", $line);
    foreach ($linetokens as $data) {
      if ($data !== '') {
        if ($id == 0 && strpos($data, '~') === FALSE) {
          $delim = ':';  // old format
        }
        $datatokens = explode($delim, $data);
        $db['data'][$id]['enabled'] = $datatokens[0];
        $db['data'][$id]['action'] = $datatokens[1];
        $db['data'][$id]['proto'] = $datatokens[2];
        $db['data'][$id]['s_addr'] = $datatokens[3];
        $db['data'][$id]['s_lport'] = $datatokens[4];
        $db['data'][$id]['s_uport'] = $datatokens[5];
        $db['data'][$id]['d_addr'] = $datatokens[6];
        $db['data'][$id]['d_lport'] = $datatokens[7];
        $db['data'][$id]['d_uport'] = $datatokens[8];
        $db['data'][$id]['comment'] = (isset($datatokens[9]) ? $datatokens[9] : '');
        $db['data'][$id]['e_addr'] = (isset($datatokens[10]) ? $datatokens[10] : '');
        $id++;
      }
    }
  }

  // Sort by action first, then by hash data
  if ($id > 1) {
    foreach ($db['data'] as $key => $row) {
      $action[$key] = $row['action'];
      $hash[$key] = $row['proto'].$row['s_addr'].$row['s_lport'].$row['d_addr'].$row['d_lport'];
    }
    array_multisort($action, SORT_ASC, SORT_STRING, $hash, SORT_ASC, SORT_STRING, $db['data']);
  }

  return($db);
}

// Function: existFWRule
//
function existFWRule($db, $action, $proto, $s_addr, $s_lport, $s_uport, $d_addr, $d_lport, $d_uport, $e_addr) {

  if (($n = arrayCount($db['data'])) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $data = $db['data'][$i];
      if ($data['action'] === $action &&
          $data['proto'] === $proto &&
          $data['s_addr'] === $s_addr &&
          $data['s_lport'] === $s_lport &&
          $data['s_uport'] === $s_uport &&
          $data['d_addr'] === $d_addr &&
          $data['d_lport'] === $d_lport &&
          $data['d_uport'] === $d_uport &&
         ($data['e_addr'] === $e_addr || ($data['e_addr'] === '' && $e_addr === '0/0'))) {
        return($i);
      }
    }
  }
  return(FALSE);
}

// Function: addFWRule
//
function addFWRule(&$db, $id) {
  $action = $_POST['action'];
  $proto = $_POST['proto'];
  $s_addr = isset($_POST['s_addr']) ? str_replace(' ', '', tuq($_POST['s_addr'])) : '';
  $s_lport = isset($_POST['s_lport']) ? str_replace(' ', '', tuq($_POST['s_lport'])) : '';
  $s_uport = isset($_POST['s_uport']) ? str_replace(' ', '', tuq($_POST['s_uport'])) : '';
  $d_addr = isset($_POST['d_addr']) ? str_replace(' ', '', tuq($_POST['d_addr'])) : '';
  $d_lport = isset($_POST['d_lport']) ? str_replace(' ', '', tuq($_POST['d_lport'])) : '';
  $d_uport = isset($_POST['d_uport']) ? str_replace(' ', '', tuq($_POST['d_uport'])) : '';
  $e_addr = isset($_POST['e_addr']) ? str_replace(' ', '', tuq($_POST['e_addr'])) : '';
  $comment = isset($_POST['comment']) ? tuq($_POST['comment']) : '';

  switch ($action) {
  case 'PASS_EXT_LOCAL':
  case 'PASS_DMZ_LOCAL':
    if (($proto === 'TCP' || $proto === 'UDP' || $proto === 'TCP/UDP') && $s_lport === '') {
      return(FALSE);
    }
  case 'DENY_LAN_LOCAL':
  case 'LOG_LOCAL_IN':
    if ($s_addr === '') {
      return(FALSE);
    }
    $d_addr = '';
    $d_lport = '';
    $d_uport = '';
    $e_addr = '';
    break;
  case 'NAT_EXT_LAN':
  case 'NAT_EXT_DMZ':
    if (($proto === 'TCP' || $proto === 'UDP' || $proto === 'TCP/UDP') && $s_lport === '') {
      return(FALSE);
    }
    if ($s_addr === '' || $d_addr === '' || $d_addr === '0/0') {
      return(FALSE);
    }
    if ($e_addr === '') {
      $e_addr = '0/0';
    }
    $d_uport = '';
    break;
  case 'PASS_EXT_LAN':
  case 'PASS_EXT_DMZ':
  case 'DENY_LAN_EXT':
  case 'DENY_EXT_DMZ':
  case 'DENY_DMZ_EXT':
  case 'PASS_DMZ_LAN':
  case 'PASS_LAN_LAN':
  case 'PASS_LAN_EXT':
  case 'PASS_DMZ_EXT':
    if ($s_addr === '' || $d_addr === '') {
      return(FALSE);
    }
    if ($d_lport === '' && $d_uport !== '') {
      return(FALSE);
    }
    $s_lport = '';
    $s_uport = '';
    $e_addr = '';
    break;
  case 'DENY_LOCAL_EXT':
  case 'LOG_LOCAL_OUT':
    if ($d_addr === '') {
      return(FALSE);
    }
    $s_addr = '';
    $s_lport = '';
    $s_uport = '';
    $e_addr = '';
    break;
  default:
    return(0);
  }
  if (is_numeric($proto)) {
    $s_lport = '';
    $s_uport = '';
    $d_lport = '';
    $d_uport = '';
  }

  if (($eid = existFWRule($db, $action, $proto, $s_addr, $s_lport, $s_uport, $d_addr, $d_lport, $d_uport, $e_addr)) !== FALSE) {
    $db['data'][$eid]['comment'] = $comment;
    return(0);
  }

  $db['data'][$id]['enabled'] = '1';
  $db['data'][$id]['action'] = $action;
  $db['data'][$id]['proto'] = $proto;
  $db['data'][$id]['s_addr'] = $s_addr;
  $db['data'][$id]['s_lport'] = $s_lport;
  $db['data'][$id]['s_uport'] = $s_uport;
  $db['data'][$id]['d_addr'] = $d_addr;
  $db['data'][$id]['d_lport'] = $d_lport;
  $db['data'][$id]['d_uport'] = $d_uport;
  $db['data'][$id]['comment'] = $comment;
  $db['data'][$id]['e_addr'] = $e_addr;

  return(TRUE);
}

$TRAFFIC_SHAPER_FILE = NULL;
$TRAFFIC_SHAPER_ENABLE = NULL;
if (($plugins = getARNOplugins()) !== FALSE) {
  foreach ($plugins as $key => $value) {
    if (basename($key, '.conf') === $TRAFFIC_SHAPER_PLUGIN) {
      $TRAFFIC_SHAPER_FILE = $key;
      $TRAFFIC_SHAPER_ENABLE = substr($value, 0, 1);
      break;
    }
  }
}

if (is_file($FIREWALLCONFFILE)) {
  $vars = parseRCconf($FIREWALLCONFFILE);
} else {
  $vars = NULL;
}
$db = parseFIREWALLconf($vars);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $disabled = $_POST['disabled'];
    $id = arrayCount($db['data']);
    for ($i = 0; $i < $id; $i++) {
      $db['data'][$i]['enabled'] = '1';
      if (arrayCount($disabled) > 0) {
        for ($j = 0; $j < arrayCount($disabled); $j++) {
          if ($disabled[$j] == $i) {
            $db['data'][$i]['enabled'] = '0';
            break;
          }
        }
      }
    }
    $ar_result = addFWRule($db, $id);
    $result = saveFIREWALLsettings($FIREWALLCONFDIR, $FIREWALLCONFFILE, $db);
    if ($result == 11 && $ar_result !== 0) {
      $result = $ar_result ? 21 : 4;
    }
  } elseif (isset($_POST['submit_restart'])) {
    if (upgradeARNOfirewall('check') == 0) {
      $result = 5;
      header('Location: '.$myself.'?upgrade&result='.$result);
      exit;
    }
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('iptables', 10, 90, 'init');
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_upgrade'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      if (upgradeARNOfirewall('upgrade') == 0) {
        $result = restartPROCESS('iptables', 20, 91, 'init');
      }
    } else {
      $result = 2;
      header('Location: '.$myself.'?upgrade&result='.$result);
      exit;
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    if (arrayCount($delete) > 0) {
      $result = saveFIREWALLsettings($FIREWALLCONFDIR, $FIREWALLCONFFILE, $db, $delete);
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
      putHtml('<p style="color: red;">Adding Firewall Rule failed, required parameters missing or invalid.</p>');
    } elseif ($result == 5) {
      putHtml('<p style="color: red;">Firewall support files require upgrading, click &quot;Upgrade/Restart Firewall&quot; to proceed.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">Firewall'.statusPROCESS('iptables').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart Firewall" to apply any changed settings.</p>');
    } elseif ($result == 20) {
      putHtml('<p style="color: green;">Firewall is Upgraded and Restarted. Previous files are in: /mnt/kd/arno-iptables-OLD</p>');
    } elseif ($result == 21) {
      putHtml('<p style="color: green;">New Firewall Rule Added, click "Restart Firewall" to apply any changed settings.</p>');
    } elseif ($result == 90) {
      putHtml('<p style="color: red;">Firewall has Restarted. WARNING: Not all firewall rules are applied.</p>');
    } elseif ($result == 91) {
      putHtml('<p style="color: red;">Firewall is Upgraded and Restarted. WARNING: Not all firewall rules are applied.</p>');
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
  <script language="JavaScript" type="text/javascript">
  //<![CDATA[
  function action_change() {
    var form = document.getElementById("iform");
    var nat_ext = document.getElementById("nat_ext");
    switch (form.action.selectedIndex) {
      case 0: // -- select --
        form.s_addr.disabled = 1;
        form.s_lport.disabled = 1;
        form.s_uport.disabled = 1;
        form.d_addr.disabled = 1;
        form.d_lport.disabled = 1;
        form.d_uport.disabled = 1;
        form.comment.disabled = 1;
        nat_ext.style.visibility = "hidden";
        break;
      case 3:  // PASS_EXT_LOCAL
      case 6:  // PASS_DMZ_LOCAL
      case 10:  // DENY_LAN_LOCAL
      case 17:  // LOG_LOCAL_IN
        form.s_addr.disabled = 0;
        form.s_lport.disabled = 0;
        form.s_uport.disabled = 0;
        form.comment.disabled = 0;
        form.d_addr.value = "";
        form.d_addr.disabled = 1;
        form.d_lport.value = "";
        form.d_lport.disabled = 1;
        form.d_uport.value = "";
        form.d_uport.disabled = 1;
        nat_ext.style.visibility = "hidden";
        break;
      case 1:  // NAT_EXT_LAN
      case 2:  // NAT_EXT_DMZ
        form.s_addr.disabled = 0;
        form.d_addr.disabled = 0;
        form.s_lport.disabled = 0;
        form.s_uport.disabled = 0;
        form.d_lport.disabled = 0;
        form.comment.disabled = 0;
        form.d_uport.value = "";
        form.d_uport.disabled = 1;
        nat_ext.style.visibility = "visible";
        break;
      case 4:  // PASS_EXT_LAN
      case 5:  // PASS_EXT_DMZ
      case 7:  // PASS_DMZ_LAN
      case 8:  // PASS_LAN_LAN
      case 9:  // DENY_LAN_EXT
      case 12:  // DENY_EXT_DMZ
      case 13:  // DENY_DMZ_EXT
      case 14:  // PASS_LAN_EXT
      case 15:  // PASS_DMZ_EXT
        form.s_addr.disabled = 0;
        form.d_addr.disabled = 0;
        form.d_lport.disabled = 0;
        form.d_uport.disabled = 0;
        form.comment.disabled = 0;
        form.s_lport.value = "";
        form.s_lport.disabled = 1;
        form.s_uport.value = "";
        form.s_uport.disabled = 1;
        nat_ext.style.visibility = "hidden";
        break;
      case 11:  // DENY_LOCAL_EXT
      case 16:  // LOG_LOCAL_OUT
        form.d_addr.disabled = 0;
        form.d_lport.disabled = 0;
        form.d_uport.disabled = 0;
        form.comment.disabled = 0;
        form.s_addr.value = "";
        form.s_addr.disabled = 1;
        form.s_lport.value = "";
        form.s_lport.disabled = 1;
        form.s_uport.value = "";
        form.s_uport.disabled = 1;
        nat_ext.style.visibility = "hidden";
        break;
    }
    switch (form.proto.selectedIndex) {
      case 0:  // TCP
      case 1:  // UDP
      case 2:  // TCP/UDP
        break;
      default:
        form.s_lport.value = "";
        form.s_lport.disabled = 1;
        form.s_uport.value = "";
        form.s_uport.disabled = 1;
        form.d_lport.value = "";
        form.d_lport.disabled = 1;
        form.d_uport.value = "";
        form.d_uport.disabled = 1;
      	break;
    }
  }
  //]]>
  </script>
  <center>
  <table class="layout"><tr><td><center>
  <form id="iform" method="post" action="<?php echo $myself;?>">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;" colspan="3">
  <h2>Firewall Configuration:</h2>
  </td></tr><tr><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td class="dialogText" style="text-align: center;">
<?php
  if (isset($_GET['upgrade'])) {
    putHtml('<input type="submit" class="formbtn" value="Upgrade/Restart Firewall" name="submit_upgrade" />');
  } else {
    putHtml('<input type="submit" class="formbtn" value="Restart Firewall" name="submit_restart" />');
  }
?>
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />
  </td></tr>
  </table>

<?php
  if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $n = arrayCount($db['data']);
    if ($id < $n && $id >= 0) {
      $ldb = $db['data'][$id];
    }
  }
  if (is_null($ldb)) {
    $ldb['s_addr'] = '0/0';
    $ldb['d_addr'] = '0/0';
    $ldb['e_addr'] = '0/0';
    $ldb['comment'] = '';
  } else {
    if ($ldb['e_addr'] === '') {
      $ldb['e_addr'] = '0/0';
    }
  }
  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr class="dtrow0"><td>&nbsp;</td></tr>');
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;">');
  putHtml('<strong>Firewall Rules:</strong>');
  putHtml('</td></tr></table>');
  putHtml('<table class="stdtable">');
  putHtml('<tr><td class="dialogText">');
  putHtml('Action:');
  putHtml('<select name="action" onchange="action_change()">');
  putHtml('<option value="">--- select ---</option>');
  foreach ($action_label as $key => $value) {
    $sel = ($ldb['action'] === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td><td class="dialogText" style="text-align: right;">');
  putHtml('Source:<input type="text" size="40" maxlength="64" name="s_addr" value="'.$ldb['s_addr'].'" />');
  putHtml('</td><td class="dialogText" style="text-align: right;">');
  putHtml('Port:<input type="text" size="18" maxlength="64" name="s_lport" value="'.$ldb['s_lport'].'" />');
  putHtml('&ndash;&nbsp;<input type="text" size="6" maxlength="5" name="s_uport" value="'.$ldb['s_uport'].'" />');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText">');
  putHtml('Protocol:');
  putHtml('<select name="proto" onchange="action_change()">');
  foreach ($proto_label as $key => $value) {
    $sel = ($ldb['proto'] === (string)$key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td><td class="dialogText" style="text-align: right;">');
  putHtml('Destination:<input type="text" size="40" maxlength="64" name="d_addr" value="'.$ldb['d_addr'].'" />');
  putHtml('</td><td class="dialogText" style="text-align: right;">');
  putHtml('Port:<input type="text" size="18" maxlength="64" name="d_lport" value="'.$ldb['d_lport'].'" />');
  putHtml('&ndash;&nbsp;<input type="text" size="6" maxlength="5" name="d_uport" value="'.$ldb['d_uport'].'" />');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText">');
  putHtml('<div id="nat_ext" style="visibility: hidden;">');
  putHtml('NAT EXT:');
  putHtml('<input type="text" size="16" maxlength="18" name="e_addr" value="'.$ldb['e_addr'].'" />');
  putHtml('</div>');
  putHtml('</td><td colspan="2" class="dialogText" style="text-align: right;">');
  putHtml('Comment <i>(optional)</i>:<input type="text" size="64" maxlength="64" name="comment" value="'.htmlspecialchars($ldb['comment']).'" />');
  putHtml('</td></tr>');
  putHtml('<tr><td colspan="3" class="dialogText" style="text-align: center;">');
  putHtml('0/0 = Any Host&nbsp;&nbsp;&nbsp;0-65535 = Any Port&nbsp;&nbsp;&nbsp;p1,p2,p3-p4 = Multiple Ports');
  putHtml('</td></tr>');
  putHtml('</table>');

  putHtml('<table width="100%" class="datatable">');
  putHtml('<tr>');

  if (($n = arrayCount($db['data'])) > 0) {
    echo '<td>&nbsp;</td>';
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Action", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Protocol", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Source", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Port", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Disabled", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td>', '<a href="'.$myself.'?id='.$i.'" class="actionText">&nbsp;+&nbsp;</a>', '</td>';
      echo '<td>', $action_label[$db['data'][$i]['action']], '</td>';
      echo '<td style="text-align: center;">', $proto_label[$db['data'][$i]['proto']], '</td>';
      echo '<td>', $db['data'][$i]['s_addr'], '</td>';
      if ($db['data'][$i]['s_uport'] === '') {
        echo '<td>', $db['data'][$i]['s_lport'], '</td>';
      } else {
        echo '<td>', $db['data'][$i]['s_lport'], '&ndash;', $db['data'][$i]['s_uport'], '</td>';
      }
      $sel = ($db['data'][$i]['enabled'] === '0') ? ' checked="checked"' : '';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="disabled[]" value="', $i, '"'.$sel.' />', '</td>';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="', $i, '" />', '</td>';
      if ($db['data'][$i]['d_addr'] !== '') {
        putHtml("</tr>");
        echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
        echo '<td>&nbsp;</td>';
        echo '<td colspan="2" class="dialogText" style="font-weight: bold;text-align: right;">Destination:</td>';
        echo '<td>', $db['data'][$i]['d_addr'], '</td>';
        if ($db['data'][$i]['d_uport'] === '') {
          echo '<td>', $db['data'][$i]['d_lport'], '</td>';
        } else {
          echo '<td>', $db['data'][$i]['d_lport'], '&ndash;', $db['data'][$i]['d_uport'], '</td>';
        }
        echo '<td colspan="2">&nbsp;</td>';
      }
      if ($db['data'][$i]['e_addr'] !== '' && $db['data'][$i]['e_addr'] !== '0/0') {
        putHtml("</tr>");
        echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
        echo '<td>&nbsp;</td>';
        echo '<td colspan="2" class="dialogText" style="font-weight: bold;text-align: right;">NAT EXT:</td>';
        echo '<td colspan="4">', $db['data'][$i]['e_addr'], '</td>';
      }
      if ($db['data'][$i]['comment'] !== '') {
        putHtml("</tr>");
        echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
        echo '<td>&nbsp;</td>';
        echo '<td colspan="2" class="dialogText" style="font-weight: bold;text-align: right;">Comment:</td>';
        echo '<td colspan="4">', htmlspecialchars($db['data'][$i]['comment']), '</td>';
      }
    }
  } else {
    echo '<td style="text-align: center;">No Firewall Rules are defined.', '</td>';
  }
  putHtml('</tr>');
  putHtml('<tr><td colspan="7">&nbsp;</td></tr>');
  putHtml('</table>');

if (! is_null($TRAFFIC_SHAPER_FILE)) {
  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Traffic Shaping:</strong>');
  $shapetype = getVARdef($vars, 'SHAPETYPE');
  putHtml('<select name="shaper_enable_type">');
  $sel = ($TRAFFIC_SHAPER_ENABLE === '0') ? ' selected="selected"' : '';
  putHtml('<option value=""'.$sel.'>Disabled</option>');
  $sel = ($TRAFFIC_SHAPER_ENABLE === '1' && $shapetype === 'htb') ? ' selected="selected"' : '';
  putHtml('<option value="htb"'.$sel.'>Enabled&nbsp;[htb]</option>');
  $sel = ($TRAFFIC_SHAPER_ENABLE === '1' && $shapetype === 'hfsc') ? ' selected="selected"' : '';
  putHtml('<option value="hfsc"'.$sel.'>Enabled&nbsp;[hfsc]</option>');
  $sel = ($TRAFFIC_SHAPER_ENABLE === '1' && $shapetype === 'cake') ? ' selected="selected"' : '';
  putHtml('<option value="cake"'.$sel.'>Enabled&nbsp;[cake]</option>');
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td width="175" style="text-align: right;">');
  putHtml('Downlink Speed:');
  putHtml('</td><td style="text-align: left;">');
  if ((int)($value = getVARdef($vars, 'EXTDOWN')) == 0) {
    $value = '';
  }
  putHtml('<input type="text" size="8" maxlength="6" value="'.$value.'" name="shaper_extdown" />');
  putHtml('<select name="shaper_extdown_enable">');
  $sel = ($value === '') ? ' selected="selected"' : '';
  putHtml('<option value="no"'.$sel.'>Disabled</option>');
  $sel = ($value !== '') ? ' selected="selected"' : '';
  putHtml('<option value="yes"'.$sel.'>K bits-per-second</option>');
  putHtml('</select>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Uplink Speed:');
  putHtml('</td><td style="text-align: left;">');
  if (($value = getVARdef($vars, 'EXTUP')) === '') {
    $value = '10000';
  }
  putHtml('<input type="text" size="8" maxlength="7" value="'.$value.'" name="shaper_extup" />');
  putHtml('<select name="shaper_extup_enable">');
  putHtml('<option value="yes">K bits-per-second</option>');
  putHtml('</select>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('VoIP UDP Ports:');
  putHtml('</td><td style="text-align: left;">');
  if (($value = getVARdef($vars, 'VOIPPORTS')) === '') {
    $value = '16384:16639';
  }
  putHtml('<input type="text" size="56" maxlength="128" value="'.$value.'" name="shaper_voipports" />');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('<strong>CAKE Tuning Options</strong>');
  putHtml('</td><td style="text-align: left;">&nbsp;');
  putHtml('</td></tr>');
  $tune_cake = getVARdef($vars, 'EXTSHAPE_TUNE_CAKE');
  $tune_cake_array = preg_split('/\s+/', $tune_cake, -1, PREG_SPLIT_NO_EMPTY);
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Link Layer Tuning:');
  putHtml('</td><td style="text-align: left;">');
  putHtml('<select name="cake_llt">');
  foreach ($cake_llt as $key => $value) {
    $sel = (in_array($key, $tune_cake_array)) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('ACK Filter:');
  putHtml('</td><td style="text-align: left;">');
  putHtml('<select name="cake_ack_filter">');
  foreach ($cake_ack_filter as $key => $value) {
    $sel = (in_array($key, $tune_cake_array)) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');
  putHtml('</table>');
}

  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Firewall Options:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td width="75" style="text-align: right;">&nbsp;</td><td>');
  putHtml('Default Policy for LAN to EXT:');
  $lan_DP = getVARdef($vars, 'LAN_INET_DEFAULT_POLICY_DROP');
  putHtml('<select name="lan_DP">');
  foreach ($lan_default_policy_label as $key => $value) {
    $sel = ($lan_DP == $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td width="75" style="text-align: right;">&nbsp;</td><td>');
  putHtml('Default Policy for DMZ to EXT:');
  $dmz_DP = getVARdef($vars, 'DMZ_INET_DEFAULT_POLICY_DROP');
  putHtml('<select name="dmz_DP">');
  foreach ($dmz_default_policy_label as $key => $value) {
    $sel = ($dmz_DP == $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td width="75" style="text-align: right;">');
  $dmz_denylan = getVARdef($vars, 'DMZ_DENYLAN');
  $sel = ($dmz_denylan !== '') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="is_dmz_denylan" name="is_dmz_denylan"'.$sel.' /></td><td>Deny LAN to DMZ for the');
  putHtml('<select name="dmz_denylan">');
  foreach ($lan_permutations_label as $key => $value) {
    $sel = ($dmz_denylan === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('LAN Interface(s)</td></tr>');

  putHtml('<tr class="dtrow1"><td width="75" style="text-align: right;">');
  $allowlans = getVARdef($vars, 'ALLOWLANS');
  $sel = ($allowlans !== '') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="is_allowlans" name="is_allowlans"'.$sel.' /></td><td>Allow LAN to LAN for the');
  putHtml('<select name="allowlans">');
  foreach ($allowlans_label as $key => $value) {
    $sel = ($allowlans === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('LAN Interfaces</td></tr>');

  putHtml('<tr class="dtrow1"><td width="75" style="text-align: right;">');
  $ovpn_allowlan = getVARdef($vars, 'OVPNC_ALLOWLAN');
  $sel = ($ovpn_allowlan !== '') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="is_ovpnc_allowlan" name="is_ovpnc_allowlan"'.$sel.' /></td><td>Allow OpenVPN Client tunnel to the');
  putHtml('<select name="ovpnc_allowlan">');
  foreach ($lan_permutations_label as $key => $value) {
    $sel = ($ovpn_allowlan === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('LAN Interface(s)</td></tr>');

  putHtml('<tr class="dtrow1"><td width="75" style="text-align: right;">');
  $ovpn_allowlan = getVARdef($vars, 'OVPN_ALLOWLAN');
  $sel = ($ovpn_allowlan !== '') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="is_ovpn_allowlan" name="is_ovpn_allowlan"'.$sel.' /></td><td>Allow OpenVPN Server tunnel to the');
  putHtml('<select name="ovpn_allowlan">');
  foreach ($lan_permutations_label as $key => $value) {
    $sel = ($ovpn_allowlan === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('LAN Interface(s)</td></tr>');

  putHtml('<tr class="dtrow1"><td width="75" style="text-align: right;">');
  $wireguard_allowlan = getVARdef($vars, 'WIREGUARD_ALLOWLAN');
  $sel = ($wireguard_allowlan !== '') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="is_wireguard_allowlan" name="is_wireguard_allowlan"'.$sel.' /></td><td>Allow WireGuard VPN tunnel to the');
  putHtml('<select name="wireguard_allowlan">');
  foreach ($lan_permutations_label as $key => $value) {
    $sel = ($wireguard_allowlan === $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('LAN Interface(s)</td></tr>');

  putHtml('<tr class="dtrow1"><td width="75" style="text-align: right;">');
  $sel = (getVARdef($vars, 'WIREGUARD_ALLOW_OPENVPN') === 'yes') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="wireguard_allow_openvpn" name="wireguard_allow_openvpn"'.$sel.' /></td><td>Allow WireGuard VPN tunnel to the OpenVPN tunnel(s)</td></tr>');

  putHtml('<tr class="dtrow1"><td width="75" style="text-align: right;">');
  $sel = (getVARdef($vars, 'OPEN_ICMP') == 1) ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="allow_icmp" name="allow_icmp"'.$sel.' /></td><td>Allow IPv4 ICMP (ping) on External (EXT) Interface</td></tr>');
  putHtml('<tr class="dtrow1"><td width="75" style="text-align: right;">');
  $value = getVARdef($vars, 'OPEN_ICMPV6');
  $sel = ($value == 1 || $value === '') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="allow_icmpv6" name="allow_icmpv6"'.$sel.' /></td><td>Allow IPv6 ICMPv6 on External (EXT) Interface</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getVARdef($vars, 'DMZ_INPUT_DENY_LOG') == 1) ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="log_dmz" name="log_dmz"'.$sel.' /></td><td>Log Denied DMZ interface packets</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getVARdef($vars, 'ICMP_REQUEST_LOG') == 1) ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="log_icmp" name="log_icmp"'.$sel.' /></td><td>Log Denied ICMP (ping) attempts</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getVARdef($vars, 'PRIV_TCP_LOG') == 1) ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="log_tcp" name="log_tcp"'.$sel.' /></td><td>Log Denied TCP attempts to privileged and unprivileged ports</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getVARdef($vars, 'PRIV_UDP_LOG') == 1) ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="log_udp" name="log_udp"'.$sel.' /></td><td>Log Denied UDP attempts to privileged and unprivileged ports</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getVARdef($vars, 'OTHER_IP_LOG') == 1) ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="log_other" name="log_other"'.$sel.' /></td><td>Log Denied non-TCP/UDP/ICMP attempts</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  $sel = (getVARdef($vars, 'FORWARD_DROP_LOG') == 1) ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="log_forward" name="log_forward"'.$sel.' /></td><td>Log Denied attempts to forward packets</td></tr>');
  putHtml('</table>');

  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Block All Traffic by Host/CIDR:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="2">');
  putHtml('&nbsp;&nbsp;Block Host/CIDR:');
  $value = getVARdef($vars, 'BLOCK_HOSTS');
  putHtml('<input type="text" size="68" maxlength="200" value="'.$value.'" name="hosts_blocked" /></td></tr>');
  putHtml('<tr class="dtrow1"><td width="75" style="text-align: right;">');
  $sel = (getVARdef($vars, 'BLOCK_HOSTS_FILE') === '/mnt/kd/blocked-hosts') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="file_blocked" name="file_blocked"'.$sel.' /></td><td>Block Host/CIDR using the file /mnt/kd/blocked-hosts</td></tr>');
  putHtml('<tr class="dtrow1"><td width="75" style="text-align: right;">');
  $sel = (getVARdef($vars, 'BLOCK_NETSET_DIR') === '/mnt/kd/blocklists') ? ' checked="checked"' : '';
  putHtml('<input type="checkbox" value="block_netset_dir" name="block_netset_dir"'.$sel.' /></td><td>Block Host/CIDR using *.netset file(s) in the directory /mnt/kd/blocklists</td></tr>');

  putHtml('<tr class="dtrow1"><td width="75" style="text-align: right;">&nbsp;</td><td>');
  putHtml('Log Denied attempts by a blocked host:');
  $log_blocked = getVARdef($vars, 'BLOCKED_HOST_LOG');
  putHtml('<select name="log_blocked">');
  foreach ($log_blocked_label as $key => $value) {
    $sel = ($log_blocked == $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('</table>');
  putHtml('</form>');
  putHtml('</center></td></tr></table>');
  putHtml('</center>');
  putHtml('<script language="JavaScript" type="text/javascript">');
  putHtml('//<![CDATA[');
  putHtml('action_change();');
  putHtml('//]]>');
  putHtml('</script>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
