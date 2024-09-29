<?php

// Copyright (C) 2015-2021 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// phoneprov.php for AstLinux
// 03-14-2014
// 08-02-2015, Add Status, Reload and Reboot links
// 08-04-2015, Add pjsip support
// 07-30-2018, Display PHONEPROV_GW_IP from user.conf
// 01-19-2021, Add a lockfile for "Generate Files"
//
// System location of /mnt/kd/rc.conf.d directory
$PHONEPROVCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.phoneprov.conf file
$PHONEPROVCONFFILE = '/mnt/kd/rc.conf.d/gui.phoneprov.conf';
// System location of user.conf file
$USERCONFFILE = '/mnt/kd/rc.conf.d/user.conf';
// Asterisk sip_notify config file
$ASTERISK_SIP_NOTIFY_CONF = '/etc/asterisk/sip_notify.conf';
// Asterisk pjsip_notify config file
$ASTERISK_PJSIP_NOTIFY_CONF = '/etc/asterisk/pjsip_notify.conf';

$family = "phoneprov";
$myself = $_SERVER['PHP_SELF'];

$info_data_mac = '';

require_once '../common/functions.php';

$MAXNUM = (int)getPREFdef($global_prefs, 'phoneprov_extensions_displayed');
if ($MAXNUM <= 0) {
  $MAXNUM = 2;
} elseif ($MAXNUM > 6) {
  $MAXNUM = 6;
}

$gw_if_menu = array (
  'INTIF' => '1st LAN Interface',
  'INT2IF' => '2nd LAN Interface',
  'INT3IF' => '3rd LAN Interface',
  'INT4IF' => '4th LAN Interface',
  'EXTIF' => 'External Interface'
);

$sip_notify_reload = array (
  'aastra' => 'aastra-check-cfg',
  'cisco' => 'cisco-check-cfg',
  'grandstream' => 'snom-check-cfg',
  'linksys' => 'linksys-warm-restart',
  'mitel' => 'snom-check-cfg',
  'polycom' => 'polycom-check-cfg',
  'sipura' => 'sipura-check-cfg',
  'snom' => 'snom-check-cfg',
  'yealink' => 'snom-check-cfg'
);

$sip_notify_reboot = array (
  'cisco' => 'linksys-cold-restart',
  'grandstream' => 'snom-reboot',
  'linksys' => 'linksys-cold-restart',
  'mitel' => 'snom-reboot',
  'snom' => 'snom-reboot',
  'yealink' => 'snom-reboot'
);

// Function: isMACinSQL
//
function isMACinSQL($mac) {

  if (! class_exists('PDO')) {
    return(FALSE);
  }

  $db_file = '/mnt/kd/asterisk-odbc.sqlite3';
  if (! is_file("$db_file")) {
    return(FALSE);
  }

  $sql = array();
  try {
    $pdo_db = new PDO("sqlite:$db_file");
    $pdo_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql_str = "SELECT * FROM phoneprov WHERE mac_addr='$mac'";
    foreach ($pdo_db->query($sql_str) as $row) {
      if ($row['account'] != '') {
        $sql['account'] = $row['account'];
        $sql['model'] = ($row['model'] != '') ? $row['model'] : 'unknown';
        $sql['vendor'] = ($row['vendor'] != '') ? $row['vendor'] : 'unknown';
        $sql['sip_driver'] = ($row['sip_driver'] != '') ? $row['sip_driver'] : 'sip';
        break;
      }
    }
    $pdo_db = NULL;
  } catch (PDOException $e) {
    return(FALSE);
  }

  if (! isset($sql['account'])) {
    return(FALSE);
  }
  return($sql);
}

// Function: isMACinfo
//
function isMACinfo($mac, $sql) {

  if ($sql['sip_driver'] === 'pjsip') {
    return(FALSE);
  }
  if (($text = asteriskCMDtext('sip show peer '.$sql['account'])) === FALSE) {
    return(FALSE);
  }

  $match = array (
    'useragent',
    'addr->ip',
    'status'
  );

  $info = array();
  foreach ($match as $value) {
    foreach ($text as $line) {
      $strtokens = explode(':', $line, 2);
      $label = trim($strtokens[0]);
      if ($value === strtolower($label)) {
        if (($field = trim($strtokens[1])) !== '') {
          $info[$label] = $field;
        }
        break;
      }
    }
  }
  if (arrayCount($info) < 1) {
    return(FALSE);
  }
  return($info);
}

// Function: addSpecialInfo
//
function addSpecialInfo($label, $field) {
  $str = '';

  if (strtolower($label) === 'addr->ip') {
    if (preg_match('/^([0-9]+[.][0-9]+[.][0-9]+[.][0-9]+).*$/', $field, $ips)) {
      $str = '&nbsp;&nbsp;<a href="http://'.$ips[1].'/" class="headerText" title="Phone Web Admin" target="_blank">Admin</a>';
    }
  }
  return($str);
}

// Function: isMACreload
//
function isMACreload($mac, $sql, $map) {
  global $ASTERISK_SIP_NOTIFY_CONF;
  global $ASTERISK_PJSIP_NOTIFY_CONF;

  $model = $sql['model'].'-reload';
  $vendor = $sql['vendor'].'-reload';
  $name = ($sql['sip_driver'] === 'pjsip') ? $ASTERISK_PJSIP_NOTIFY_CONF : $ASTERISK_SIP_NOTIFY_CONF;
  if (is_file($name)) {
    $cmd = 'grep -q "^\['.$model.'\]" '.$name;
    shell($cmd.' >/dev/null 2>/dev/null', $status);
    if ($status == 0) {
      return($model);
    }
    $cmd = 'grep -q "^\['.$vendor.'\]" '.$name;
    shell($cmd.' >/dev/null 2>/dev/null', $status);
    if ($status == 0) {
      return($vendor);
    }
    if (isset($map[$sql['vendor']])) {
      return($map[$sql['vendor']]);
    }
  }
  return(FALSE);
}

// Function: isMACreboot
//
function isMACreboot($mac, $sql, $map) {
  global $ASTERISK_SIP_NOTIFY_CONF;
  global $ASTERISK_PJSIP_NOTIFY_CONF;

  $model = $sql['model'].'-reboot';
  $vendor = $sql['vendor'].'-reboot';
  $name = ($sql['sip_driver'] === 'pjsip') ? $ASTERISK_PJSIP_NOTIFY_CONF : $ASTERISK_SIP_NOTIFY_CONF;
  if (is_file($name)) {
    $cmd = 'grep -q "^\['.$model.'\]" '.$name;
    shell($cmd.' >/dev/null 2>/dev/null', $status);
    if ($status == 0) {
      return($model);
    }
    $cmd = 'grep -q "^\['.$vendor.'\]" '.$name;
    shell($cmd.' >/dev/null 2>/dev/null', $status);
    if ($status == 0) {
      return($vendor);
    }
    if (isset($map[$sql['vendor']])) {
      return($map[$sql['vendor']]);
    }
  }
  return(FALSE);
}

// Function: asteriskCMDtext
//
function asteriskCMDtext($cmd) {

  $tmpfile = tempnam("/tmp", "PHP_");
  if (asteriskCMD($cmd, $tmpfile) == 0) {
    $text = @file($tmpfile, FILE_IGNORE_NEW_LINES);
  } else {
    $text = FALSE;
  }
  @unlink($tmpfile);
  return($text);
}

// Function: putACTIONresult
//
function putACTIONresult($result_str, $status) {
  global $myself;

  if ($status == 0) {
    $result = 100;
  } else {
    $result = 101;
  }
  if ($result == 100) {
    $result_str = 'Phone Provisioning files are generated.';
  } elseif ($result_str === '') {
    $result_str = 'Error';
  }
  header('Location: '.$myself.'?result_str='.rawurlencode($result_str).'&result='.$result);
}

// Function: getACTIONresult
//
function getACTIONresult($result) {
  $str = 'No Action.';

  if (isset($_GET['result_str'])) {
    $str = rawurldecode($_GET['result_str']);
  }
  if ($result == 100) {
    $color = 'green';
  } else {
    $color = 'red';
  }
  return('<p style="color: '.$color.';">'.$str.'</p>');
}

// Function: getPHONEPROVtemplates
//
function getPHONEPROVtemplates($dir) {

  if (! is_dir($dir)) {
    return(FALSE);
  }

  $id = 0;
  foreach (glob($dir.'/*.conf') as $globfile) {
    $templates[$id]['name'] = basename($globfile, '.conf');
    $menu = trim(shell_exec('sed -r -n -e "1,/^\[/ s/^menu_name *=(.*)$/\1/p" "'.$globfile.'" 2>/dev/null'));
    if ($menu === '') {
      $menu = $templates[$id]['name'];
    } elseif (strlen($menu) > 40) {
      $menu = htmlspecialchars(substr($menu, 0, 40)).'&hellip;';
    }
    $templates[$id]['menu'] = $menu;
    $id++;
  }

  if ($id == 0) {
    return(FALSE);
  }
  return($templates);
}

// Function: savePHONEPROVsettings
//
function savePHONEPROVsettings($conf_dir, $conf_file) {

  if (is_dir($conf_dir) === FALSE) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.phoneprov.conf - start ###\n###\n");

  $value = 'PHONEPROV_GW_IF="'.$_POST['gw_if'].'"';
  fwrite($fp, $value."\n");

  fwrite($fp, "### gui.phoneprov.conf - end ###\n");
  fclose($fp);

  return(11);
}

// Function: phoneprovDBtoDATA
//
function phoneprovDBtoDATA($db) {
  global $MAXNUM;
  $id = 0;

  if (($n = arrayCount($db['data'])) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $data[$id]['mac'] = $db['data'][$i]['key'];
      $datatokens = explode(' ', $db['data'][$i]['value']);
      $data[$id]['enabled'] = $datatokens[0];
      $data[$id]['orig_enabled'] = $datatokens[0];
      $data[$id]['template'] = $datatokens[1];
      $exttokens = explode(';', $datatokens[2]);
      for ($j = 0; $j < $MAXNUM; $j++) {
        $ext_cid = explode('/', (isset($exttokens[$j]) ? $exttokens[$j] : ''), 2);  // Include any /'s in 'cid'
        $data[$id]['ext'][$j] = (isset($ext_cid[0]) ? $ext_cid[0] : '');
        $data[$id]['cid'][$j] = (isset($ext_cid[1]) ? $ext_cid[1] : '');
      }
      $data[$id]['password'] = $datatokens[3];
      $data[$id]['account'] = (isset($datatokens[4]) ? $datatokens[4] : '');
      $id++;
    }
  }

  // Sort by 1st Extension
  if ($id > 1) {
    foreach ($data as $key => $row) {
      $ext[$key] = $row['ext'][0];
    }
    array_multisort($ext, SORT_ASC, SORT_NUMERIC, $data);
  }

  return($data);
}

// Function: delPHONEPROVmac
//
function delPHONEPROVmac($family, $key) {

  $err = delAstDB($family, $key);

  return($err);
}

// Function: expandPHONEPROVexttext
//
function expandPHONEPROVexttext($data) {

  $str = str_replace(';', ' ', expandPHONEPROVext($data));

  return($str);
}

// Function: expandPHONEPROVext
//
function expandPHONEPROVext($data) {
  global $MAXNUM;
  $str = '';

  for ($j = 0; $j < $MAXNUM; $j++) {
    if ($data['ext'][$j] !== '') {
      if ($str !== '') {
        $str .= ';';
      }
      $str .= $data['ext'][$j];
      if ($data['cid'][$j] !== '') {
        $str .= '/'.$data['cid'][$j];
      }
    }
  }
  return($str);
}

// Function: get_importPHONEPROVfile
//
function get_importPHONEPROVfile() {

  $conf_files = array ('/mnt/kd/massdeployment.conf',
                       '/mnt/kd/webgui-massdeployment.conf',
                       '/mnt/kd/phoneprov/massdeployment.conf');

  $conf_file = '';
  foreach ($conf_files as $value) {
    if (is_file($value)) {
      $conf_file = $value;
      break;
    }
  }
  return($conf_file);
}

// Function: importPHONEPROVfiles
//
function importPHONEPROVfiles($family) {
  $result = 99;

  if (($conf_file = get_importPHONEPROVfile()) === '') {
    return(3);
  }
  if (($fp = @fopen($conf_file, "r")) === FALSE) {
    return(3);
  }
  while (! feof($fp)) {
    if (($line = trim(fgets($fp, 1024))) !== '') {
      if ($line[0] !== '#') {
        if (preg_match('/^([^ \t]+)[ \t]+([^ \t]+)[ \t]+([^ \t]+)[ \t]+([^ \t]+)(.*)$/', $line, $tokens)) {
          $mac = strtolower($tokens[2]);
          if (preg_match('/^([0-9a-f]{2}:){5}([0-9a-f]{2})$/', $mac)) {
            $enabled = '1';
            $template = $tokens[1];
            $ext_cid = $tokens[3];
            $password = $tokens[4];
            $account = trim($tokens[5]);
            $result = addPHONEPROVmac($family, $mac, $enabled, $template, $ext_cid, $password, $account);
          }
        }
      }
    }
  }
  fclose($fp);

  return($result);
}

// Function: savePHONEPROVenabled
//
function savePHONEPROVenabled($family, $data) {
  $err = 0;

  if (($n = arrayCount($data)) > 0) {
    for ($i = 0; $i < $n; $i++) {
      if ($data[$i]['enabled'] !== $data[$i]['orig_enabled']) {
        $mac = $data[$i]['mac'];
        $enabled = $data[$i]['enabled'];
        $template = $data[$i]['template'];
        $ext_cid = expandPHONEPROVext($data[$i]);
        $password = $data[$i]['password'];
        $account = $data[$i]['account'];
        $err = addPHONEPROVmac($family, $mac, $enabled, $template, $ext_cid, $password, $account);
      }
    }
  }
  return($err);
}

// Function: addPHONEPROVmac
//
function addPHONEPROVmac($family, $key, $enabled, $template, $ext_cid, $password, $account) {

  $value = $enabled.' '.$template.' '.$ext_cid.' '.$password;
  if ($account !== '') {
    $value .= ' '.$account;
  }

  $err = putAstDB($family, $key, $value);

  return($err);
}

// Function: lockfile_cleanup
// (shutdown_function)
function lockfile_cleanup($lock_file)
{
  if (is_file($lock_file)) {
    unlink($lock_file);
  }
}

// Function: generatePHONEPROVfiles
//
function generatePHONEPROVfiles($data, $reload, &$result_str, &$status) {
  $result_str = '';
  $status = 0;
  $lock_file = '/var/lock/webgui-phoneprov.lock';
  $conf_file = '/mnt/kd/webgui-massdeployment.conf';

  if (($fp = @fopen($lock_file, 'x')) === FALSE) {
    $result_str = 'Locked by another user, try again later.';
    $status = 1;
    return(3);
  }
  $remote_addr = $_SERVER['REMOTE_ADDR'];
  fwrite($fp, $remote_addr."\n");
  fclose($fp);
  register_shutdown_function('lockfile_cleanup', $lock_file);

  if (($fp = @fopen($conf_file, 'wb')) === FALSE) {
    $status = 1;
    return(3);
  }
  fwrite($fp, "### AstLinux Web Interface - Phone Provisioning - Mass Deployment ###\n###\n");

  if (($n = arrayCount($data)) > 0) {
    for ($i = 0; $i < $n; $i++) {
      if ($data[$i]['enabled'] === '1') {
        $template = $data[$i]['template'];
        $mac = $data[$i]['mac'];
        $ext_cid = expandPHONEPROVext($data[$i]);
        $password = $data[$i]['password'];
        $account = $data[$i]['account'];

        $str = $template.' '.$mac.' '.$ext_cid.' '.$password;
        if ($account !== '') {
          $str .= ' '.$account;
        }
        fwrite($fp, $str."\n");
      }
    }
  }
  fclose($fp);

  $cmd = '/usr/sbin/phoneprov-massdeployment';
  if (! is_file($cmd)) {
    $result_str = 'phoneprov-massdeployment: not found';
    $status = 1;
    return(3);
  }
  if ($reload) {
    $cmd .= ' -f -r ';
  } else {
    $cmd .= ' -f ';
  }
  $cmd .= $conf_file.' 2>&1 1>/dev/null';
  $cmd .= '; rtn=$?';
  $cmd .= '; rm -f '.$lock_file;
  $cmd .= '; exit $rtn';

  @exec('cd /root;/usr/sbin/gen-rc-conf;'.$cmd, $result_array, $status);
  $result_str = '';
  foreach ($result_array as $value) {
    $result_str .= $value.' ';
  }
  $result_str = trim($result_str);

  return(0);
}

$db = parseAstDB($family);

$data = phoneprovDBtoDATA($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_staff) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $disabled = $_POST['disabled'];
    if (($n = arrayCount($data)) > 0) {
      for ($i = 0; $i < $n; $i++) {
        $data[$i]['enabled'] = '1';
        if (($m = arrayCount($disabled)) > 0) {
          for ($j = 0; $j < $m; $j++) {
            if ($disabled[$j] === $data[$i]['mac']) {
              $data[$i]['enabled'] = '0';
              break;
            }
          }
        }
      }
    }
    $result = savePHONEPROVsettings($PHONEPROVCONFDIR, $PHONEPROVCONFFILE);
    savePHONEPROVenabled($family, $data);

    $mac = strtolower(tuq($_POST['mac']));
    if ($mac !== '') {
      if (preg_match('/^([0-9a-f]{2}:){5}([0-9a-f]{2})$/', $mac)) {
        $template = $_POST['template'];
        if ($template !== '') {
          for ($i = 0; $i < $MAXNUM; $i++) {
            $pdata['ext'][$i] = preg_replace('/[^0-9#*]*([0-9#*]*).*/', '$1', tuq($_POST["ext$i"]));
            $pdata['cid'][$i] = str_replace(' ', '_', tuq($_POST["cid$i"]));
          }
          $ext_cid = expandPHONEPROVext($pdata);
          if ($ext_cid !== '') {
            $enabled = '1';
            if (($m = arrayCount($disabled)) > 0) {
              for ($j = 0; $j < $m; $j++) {
                if ($disabled[$j] === $mac) {
                  $enabled = '0';
                  break;
                }
              }
            }
            $password = str_replace(' ', '', tuq($_POST['password']));
            $account = str_replace(' ', '', tuq($_POST['account']));
            if ($password === '') {
              $password = base64_encode(openssl_random_pseudo_bytes(12));
            }
            if (($result = addPHONEPROVmac($family, $mac, $enabled, $template, $ext_cid, $password, $account)) == 0) {
              $result = 11;
            }
          } else {
            $result = 7;
          }
        } else {
          $result = 6;
        }
        if ($result != 11) {
          header('Location: '.$myself.'?key='.rawurlencode($mac).'&result='.$result);
          exit;
        }
      } else {
        $result = 5;
      }
    }
  } elseif (isset($_POST['submit_generate'])) {
    $result = 99;
    if (isset($_POST['confirm_generate'])) {
      generatePHONEPROVfiles($data, isset($_POST['reload_dialplan_sip']), $result_str, $status);
      putACTIONresult($result_str, $status);
      exit;
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    for ($i = 0; $i < arrayCount($delete); $i++) {
      $result = delPHONEPROVmac($family, $delete[$i]);
    }
  } elseif (isset($_POST['submit_import'])) {
    $result = importPHONEPROVfiles($family);
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'staff';
require_once '../common/header.php';

  putHtml('<center>');
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 0) {
      putHtml('<p style="color: green;">Action Successful.</p>');
    } elseif ($result == 1) {
      putHtml('<p style="color: orange;">No Action.</p>');
    } elseif ($result == 2) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error.</p>');
    } elseif ($result == 4 || $result == 1101 || $result == 1102) {
      putHtml('<p style="color: red;">'.asteriskERROR($result).'</p>');
    } elseif ($result == 5) {
      putHtml('<p style="color: red;">Not a valid MAC Address, format ff:ff:ff:ff:ff:ff</p>');
    } elseif ($result == 6) {
      putHtml('<p style="color: red;">No Template is selected.</p>');
    } elseif ($result == 7) {
      putHtml('<p style="color: red;">No Extension(s) are defined.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Generate Files" to apply any changed settings.</p>');
    } elseif ($result == 99) {
      putHtml('<p style="color: red;">Action Failed.</p>');
    } elseif ($result == 100 || $result == 101) {
      putHtml(getACTIONresult($result));
    } elseif ($result == 999) {
      putHtml('<p style="color: red;">Permission denied for user "'.$global_user.'".</p>');
    } else {
      putHtml('<p>&nbsp;</p>');
    }
  } elseif (isset($_GET['info'])) {
    $mac = rawurldecode($_GET['info']);
    if (($sql = isMACinSQL($mac)) !== FALSE) {
      if (($info_data = isMACinfo($mac, $sql)) !== FALSE) {
        $info_data_mac = $mac;
        putHtml('<p>&nbsp;</p>');
      } else {
        putHtml('<p style="color: red;">Status Action Failed.</p>');
      }
    } else {
      putHtml('<p style="color: red;">SQL Action Failed.</p>');
    }
  } elseif (isset($_GET['reload'])) {
    $mac = rawurldecode($_GET['reload']);
    if (($sql = isMACinSQL($mac)) !== FALSE) {
      if (($sip_notify = isMACreload($mac, $sql, $sip_notify_reload)) !== FALSE) {
        $notify_cmd = ($sql['sip_driver'] === 'pjsip') ? 'pjsip send notify '.$sip_notify.' endpoint '
                                                       : 'sip notify '.$sip_notify.' ';
        $notify_cmd .= $sql['account'];
        if (($sip_notify_text = asteriskCMDtext($notify_cmd)) !== FALSE) {
          putHtml('<p>'.$sip_notify_text[0].'</p>');
        } else {
          putHtml('<p style="color: red;">Reload Action Failed.</p>');
        }
      } else {
        putHtml('<p style="color: red;">Reload Action Failed.</p>');
      }
    } else {
      putHtml('<p style="color: red;">SQL Action Failed.</p>');
    }
  } elseif (isset($_GET['reboot'])) {
    $mac = rawurldecode($_GET['reboot']);
    if (($sql = isMACinSQL($mac)) !== FALSE) {
      if (($sip_notify = isMACreboot($mac, $sql, $sip_notify_reboot)) !== FALSE) {
        $notify_cmd = ($sql['sip_driver'] === 'pjsip') ? 'pjsip send notify '.$sip_notify.' endpoint '
                                                       : 'sip notify '.$sip_notify.' ';
        $notify_cmd .= $sql['account'];
        if (($sip_notify_text = asteriskCMDtext($notify_cmd)) !== FALSE) {
          putHtml('<p>'.$sip_notify_text[0].'</p>');
        } else {
          putHtml('<p style="color: red;">Reboot Action Failed.</p>');
        }
      } else {
        putHtml('<p style="color: red;">Reboot Action Failed.</p>');
      }
    } else {
      putHtml('<p style="color: red;">SQL Action Failed.</p>');
    }
  } else {
    putHtml('<p>&nbsp;</p>');
  }
  putHtml('</center>');
?>
  <center>
  <table class="layout"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>">
<?php
  $phoneprov_base_dir = trim(shell_exec('. /etc/rc.conf; echo "${PHONEPROV_BASE_DIR:-/mnt/kd/phoneprov}"'));

  if (is_file($PHONEPROVCONFFILE)) {
    $vars = parseRCconf($PHONEPROVCONFFILE);
  } else {
    $vars = NULL;
  }
  if (is_file($USERCONFFILE)) {
    $user_vars = parseRCconf($USERCONFFILE);
  } else {
    $user_vars = NULL;
  }

  $ldata['template'] = '';
  for ($i = 0; $i < $MAXNUM; $i++) {
    $ldata['ext'][$i] = '';
    $ldata['cid'][$i] = '';
  }
  $ldata['password'] = '';
  $ldata['account'] = '';

  if (isset($_GET['key'])) {
    $mac = rawurldecode($_GET['key']);
    if (($n = arrayCount($data)) > 0) {
      for ($i = 0; $i < $n; $i++) {
        if ($mac === $data[$i]['mac']) {
          $ldata = $data[$i];
          break;
        }
      }
    }
  } else {
    $mac = '';
  }

  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr><td style="text-align: center;" colspan="3">');
  putHtml('<h2>Phone Provisioning - Mass Deployment:</h2>');
  putHtml('</td></tr><tr><td style="text-align: center;">');
if (($templates = getPHONEPROVtemplates("$phoneprov_base_dir/templates")) !== FALSE) {
  putHtml('<input type="submit" class="formbtn" value="Save Changes" name="submit_save" />');

  putHtml('</td><td style="text-align: center;">');
  putHtml('<input type="submit" class="formbtn" value="Generate Files" name="submit_generate" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="reload_dialplan_sip" name="reload_dialplan_sip" />&nbsp;Reload Dialplan/SIP');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="generate" name="confirm_generate" />&nbsp;Confirm');
  putHtml('</td><td style="text-align: center;">');
  putHtml('<input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />');
  putHtml('</td></tr>');

  putHtml('<tr><td style="text-align: center;" colspan="3">');
  putHtml('Gateway Interface:');
  $gw_if = getVARdef($vars, 'PHONEPROV_GW_IF');
  putHtml('<select name="gw_if">');
  foreach ($gw_if_menu as $key => $value) {
    $sel = ($gw_if == $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  if (($gw_ip = getVARdef($user_vars, 'PHONEPROV_GW_IP')) !== '') {
    putHtml('<tr><td style="text-align: center;" colspan="3">');
    putHtml('Gateway IPv4:&nbsp;'.$gw_ip);
    putHtml('</td></tr>');
  }
  putHtml('</table>');

  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr class="dtrow0"><td width="350">&nbsp;</td><td>&nbsp;</td></tr>');
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Phone Configuration:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('MAC Address:');
  putHtml('<input type="text" size="20" maxlength="17" name="mac" value="'.$mac.'" />');
  putHtml('</td><td style="text-align: center;">');
  putHtml('Template:');
  $template = $ldata['template'];
  putHtml('<select name="template">');
  putHtml('<option value="">--- select ---</option>');
  foreach ($templates as $value) {
    $sel = ($template === $value['name']) ? ' selected="selected"' : '';
    putHtml('<option value="'.$value['name'].'"'.$sel.'>['.$value['name'].'] '.$value['menu'].'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: center;" colspan="2">');
  putHtml('Extension(s) / CID Name(s) <i>(optional)</i>:');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: center;" colspan="2">');
  for ($i = 0; $i < $MAXNUM; $i++) {
    echo '<input type="text" size="10" maxlength="64" name="ext'.$i.'" value="'.$ldata['ext'][$i].'" />/';
    echo '<input type="text" size="24" maxlength="64" name="cid'.$i.'" value="'.$ldata['cid'][$i].'" />';
    if ((($i + 1) % 2) == 0) {
      putHtml('<br />');
    } else {
      putHtml('&nbsp;&nbsp;');
    }
  }
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Password:');
  putHtml('<input type="text" size="24" maxlength="128" name="password" value="'.$ldata['password'].'" />');
  putHtml('</td><td style="text-align: center;">');
  putHtml('Account <i>(optional)</i>:');
  putHtml('<input type="text" size="24" maxlength="128" name="account" value="'.$ldata['account'].'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Phone Database:</strong>');
  putHtml('</td></tr>');
  putHtml('</table>');

  putHtml('<table width="100%" class="datatable">');
  putHtml("<tr>");

  if (($n = arrayCount($data)) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "MAC Address", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Template", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Extensions", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Password", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Account", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Disabled", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      $mac = $data[$i]['mac'];
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td'.($info_data_mac === $mac ? ' id="to_status"' : '').'><a href="'.$myself.'?key='.rawurlencode($mac).'" class="actionText">'.$mac.'</a>', '</td>';
      echo '<td>', htmlspecialchars($data[$i]['template']), '</td>';
      echo '<td>', wordwrap(htmlspecialchars(expandPHONEPROVexttext($data[$i])), 10, '<br />', FALSE), '</td>';
      echo '<td>', htmlspecialchars(substr($data[$i]['password'], 0, 6)), '&hellip;', '</td>';
      echo '<td>', htmlspecialchars($data[$i]['account']), '</td>';
      $sel = ($data[$i]['enabled'] === '0') ? ' checked="checked"' : '';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="disabled[]" value="', $mac, '"'.$sel.' />', '</td>';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="', $mac, '" />', '</td>';
      if (($sql = isMACinSQL($mac)) !== FALSE) {
        putHtml("</tr>");
        echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
        echo '<td style="text-align: right;" colspan="7">';
        if ($sql['sip_driver'] !== 'pjsip') {
          echo '&nbsp;<a href="'.$myself.'?info='.rawurlencode($mac).'&amp;#to_status" class="headerText" title="Show SIP Peer Info">Status</a>';
        }
        echo '&nbsp;<a href="'.$myself.'?reload='.rawurlencode($mac).'" class="headerText" title="Send SIP Notify to Reload Config">Reload</a>';
        echo '&nbsp;<a href="'.$myself.'?reboot='.rawurlencode($mac).'" class="headerText" title="Send SIP Notify to Reboot Phone">Reboot</a>';
        echo '</td>';
        if ($info_data_mac === $mac) {
          foreach ($info_data as $info_label => $info_field) {
            putHtml("</tr>");
            echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
            echo '<td style="text-align: right;">'.htmlspecialchars($info_label).':</td>';
            echo '<td colspan="6">';
            echo htmlspecialchars($info_field).addSpecialInfo($info_label, $info_field);
            echo '</td>';
          }
        }
      }
    }
  } else {
    if ($db['status'] == 0) {
      echo '<td style="text-align: center;">No Database Entries for: ', $db['family'], '</td>';
      if (($fname = get_importPHONEPROVfile()) !== '') {
        putHtml('</tr><tr><td style="text-align: center;">');
        putHtml('<input type="submit" class="formbtn" value="Import '.htmlspecialchars($fname).'" name="submit_import" /></td>');
      }
    } else {
      echo '<td style="text-align: center; color: red;">', asteriskERROR($db['status']), '</td>';
    }
  }
} else {
  echo '<p style="color: red;">No Templates found in: ', "$phoneprov_base_dir/templates", '</p></td>';
}
  putHtml("</tr>");
  putHtml("</table>");
  putHtml("</form>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
