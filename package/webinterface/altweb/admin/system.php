<?php

// Copyright (C) 2008-2023 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// system.php for AstLinux
// 03-26-2008
// 04-02-2008, Added rc.conf display
// 04-09-2008, Added view file
// 07-29-2008, Added staff password generation
// 08-09-2008, Added multiple backup sets
// 09-20-2008, Added firmware check, upgrade, show, revert
// 04-25-2009, Added Restore Basic Configuration
// 12-12-2009, Added System Shutdown/Halt
// 02-01-2010, Added Asterisk Sounds upgrade, remove, show
// 01-16-2011, Added runnix check, upgrade, show, revert
// 07-21-2013, Added Add-On Packages
// 12-16-2017, Updated backup files
// 06-25-2019, Updated backup files
// 07-11-2019, Added Backup Exclude Suffixes support
// 10-20-2020, Added view syslog messages.0 or messages.1
// 02-11-2021, Added backup "Linux Containers (lxc)" menu
// 10-02-2023, Added view "Cron Daemon log" menu
//
// System location of rc.conf file
$CONFFILE = '/etc/rc.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

require_once '../common/users-password.php';

if (($REPOSITORY_URL = tuq(getPREFdef($global_prefs, 'system_firmware_repository_url'))) === '') {
  $REPOSITORY_URL = asteriskURLrepo();
}

if (($SOUNDS_URL = tuq(getPREFdef($global_prefs, 'system_asterisk_sounds_url'))) === '') {
  $SOUNDS_URL = 'https://downloads.asterisk.org/pub/telephony/sounds';
}

$sounds_type_menu = array (
  '' => 'none',
  'core' => 'core',
  'extra' => 'extra',
  'moh' => 'moh'
);

$sounds_lang_menu = array (
  'en' => 'english',
  'en_AU' => 'english-au',
  'en_GB' => 'english-gb',
  'en_NZ' => 'english-nz',
  'nl' => 'dutch',
  'fr' => 'french',
  'de' => 'german',
  'it' => 'italian',
  'ja' => 'japanese',
  'ru' => 'russian',
  'es' => 'spanish',
  'sv' => 'swedish'
);

$sounds_codec_menu = array (
  'ulaw' => 'ulaw',
  'alaw' => 'alaw',
  'gsm' => 'gsm',
  'wav' => 'wav',
  'g729' => 'g729',
  'g722' => 'g722'
);

$addon_package_type_menu = array (
  '' => 'none',
  'fop2' => 'fop2'
);

// Function: putACTIONresult
//
function putACTIONresult($result_str, $status) {
  global $myself;

  if ($status == 0) {
    $result = 100;
  } elseif ($status == 2) {
    $result = 102;
  } else {
    $result = 101;
  }
  if ($result_str === '') {
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
  } elseif ($result == 102) {
    $color = 'orange';
  } else {
    $color = 'red';
  }
  return('<p style="color: '.$color.';">'.$str.'</p>');
}

// Function: uncompressARCHIVE
//
function uncompressARCHIVE($name, $suffix) {

  if ($suffix === '.tar.gz') {
    shell('gunzip -t '.$name.' >/dev/null 2>/dev/null', $status);
    if ($status != 0) {
      @unlink($name);
      return(FALSE);
    }
    shell('gunzip '.$name.' >/dev/null 2>/dev/null', $status);
    if ($status != 0) {
      @unlink($name);
      return(FALSE);
    }
    $name = substr($name, 0, (strlen($name) - 3));
  }
  return($name);
}

// Function: restoreBASICconfig
//
function restoreBASICconfig($name) {

  $target = '/mnt/kd';

  // Helper script
  shell('/usr/sbin/restore-basic-conf "'.$name.'" "'.$target.'" >/dev/null 2>/dev/null', $status);
  if ($status != 0) {
    return(($status != 1) ? 24 : 23);
  }
  return(30);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_password'])) {
    if (isset($_POST['pass1'])) {
      $pass1 = tuqd($_POST['pass1']);
    }
    if (isset($_POST['pass2'])) {
      $pass2 = tuqd($_POST['pass2']);
    }
    if (($user = $_POST['user_pass']) !== '') {
      $result = genHTpasswd($user, $pass1, $pass2, 3);
    }
  } elseif (isset($_POST['submit_view'])) {
    if (($tmpfile = $_POST['view_file']) !== '') {
      if (is_file($tmpfile)) {
        header('Location: /admin/view.php?file='.$tmpfile);
        exit;
      } else {
        $result = 8;
      }
    }
  } elseif (isset($_POST['submit_backup'])) {
    $backup_type = $_POST['backup_type'];
    if (getPREFdef($global_prefs, 'system_backup_compress_gzip') === 'no') {
      $suffix = '.tar';
      $tarcmd = 'tar cf ';
    } else {
      $suffix = '.tar.gz';
      $tarcmd = 'tar czf ';
    }
    if (($backup_name = get_HOSTNAME_DOMAIN()) === '') {
      $backup_name = $_SERVER['SERVER_NAME'];
    }
    if (getPREFdef($global_prefs, 'system_backup_hostname_domain') !== 'yes') {
      if (($pos = strpos($backup_name, '.')) !== FALSE) {
        $backup_name = substr($backup_name, 0, $pos);
      }
    }
    $asturw = (getPREFdef($global_prefs, 'system_backup_asturw') === 'yes') ? '/mnt/kd/asturw'.$suffix : '';
    $prefix = (getPREFdef($global_prefs, 'system_backup_temp_disk') === 'yes') ? '/mnt/kd/.' : '/tmp/';
    $tmpfile = $backup_name.'-'.$backup_type.'-'.date('Y-m-d').$suffix;
    $xsuffix = gen_BackupExcludeSuffix_args(getPREFdef($global_prefs, 'system_backup_exclude_suffix_cmdstr'));
    if ($backup_type === 'basic') {
      $firewall = is_dir('/mnt/kd/arno-iptables-firewall/plugins') ? ' "arno-iptables-firewall/plugins"' : '';
      $firewall .= is_file('/mnt/kd/arno-iptables-firewall/custom-rules') ? ' "arno-iptables-firewall/custom-rules"' : '';
      $phoneprov_base_dir = rtrim(trim(shell_exec('. /etc/rc.conf; echo "${PHONEPROV_BASE_DIR:-/mnt/kd/phoneprov}"')), '/');
      if (is_dir("$phoneprov_base_dir/templates") && (strncmp($phoneprov_base_dir, '/mnt/kd', strlen('/mnt/kd')) == 0)) {
        $templates = ' "'.substr("$phoneprov_base_dir/templates", strlen('/mnt/kd/')).'"';
      } else {
        $templates = '';
      }
      $srcfile = '$(ls -1 /mnt/kd/ | sed -n -e "s/^rc.conf.d$/&/p" -e "s/^ssh_keys$/&/p"';
      $srcfile .= ' -e "s/^.*[.]conf$/&/p" -e "s/^.*[.]script$/&/p" -e "s/^webgui-prefs.txt$/&/p" -e "s/^ast.*/&/p"';
      $srcfile .= ' -e "s/^blocked-hosts$/&/p" -e "s/^dnsmasq.static$/&/p" -e "s/^hosts$/&/p" -e "s/^ethers$/&/p"';
      $srcfile .= ' -e "s/^rc.local$/&/p" -e "s/^rc.local.stop$/&/p" -e "s/^rc.elocal$/&/p" -e "s/^rc.ledcontrol$/&/p"';
      $srcfile .= ' -e "s/^custom-agi$/&/p" -e "s/^avahi$/&/p"';
      $srcfile .= ' -e "s/^crontabs$/&/p" -e "s/^snmp$/&/p" -e "s/^fop2$/&/p" -e "s/^kamailio$/&/p" -e "s/^monit$/&/p"';
      $srcfile .= ' -e "s/^openvpn$/&/p" -e "s/^ipsec$/&/p" -e "s/^wireguard$/&/p"';
      $srcfile .= ' -e "s/^dahdi$/&/p" -e "s/^ssl$/&/p" -e "s/^ups$/&/p" -e "s/^keepalived$/&/p")';
      $srcfile .= $firewall;
      $srcfile .= $templates;
    } elseif ($backup_type === 'cdr') {
      $srcfile = '$(ls -1 /mnt/kd/ | sed -n -e "s/^cdr-.*/&/p")';
      $asturw = '';
    } elseif ($backup_type === 'monitor') {
      $srcfile = 'monitor';
      $asturw = '';
    } elseif ($backup_type === 'voicemail') {
      $srcfile = 'voicemail';
      $asturw = '';
    } elseif ($backup_type === 'lxc') {
      $srcfile = 'lxc';
      $asturw = '';
    } elseif ($backup_type === 'config') {
      $srcfile = '$(ls -1 /mnt/kd/ | sed -e "s/^cdr-.*//" -e "s/^monitor$//" -e "s/^voicemail$//" -e "s/^lxc$//")';
    } elseif ($backup_type === 'unionfs') {
      $srcfile = 'asturw'.$suffix;
      $asturw = '/mnt/kd/asturw'.$suffix;
    } else {
      $srcfile = '$(ls -1 /mnt/kd/)';
    }
    if ($asturw !== '') {
      $excludefile = tempnam("/tmp", "PHP_");
      $excludepath  = 'stat/var/lib/asterisk/sounds/*'."\n";
      $excludepath .= 'stat/var/lib/asterisk/moh/*'."\n";
      $excludepath .= 'stat/var/www/cache/*'."\n";
      $excludepath .= 'stat/var/packages/*'."\n";
      $excludepath .= 'usr/lib/locale/*'."\n";
      @file_put_contents($excludefile, $excludepath);
      shell($tarcmd.$asturw.' -X '.$excludefile.' $(ls -1 '.$MNT_ASTURW_DIR.'/ | sed -e "s/^mnt$//") -C '.$MNT_ASTURW_DIR.' >/dev/null 2>/dev/null', $status);
      @unlink($excludefile);
      if ($status != 0) {
        @unlink($asturw);
        $result = 15;
        header('Location: '.$myself.'?result='.$result);
        exit;
      }
    }
    shell($tarcmd.$prefix.$tmpfile.$xsuffix.' '.$srcfile.' -C /mnt/kd >/dev/null 2>/dev/null', $status);
    if ($asturw !== '') {
      @unlink($asturw);
    }
    if ($status != 0) {
      @unlink($prefix.$tmpfile);
      $result = ($prefix === '/tmp/') ? 16 : 5;
    } else {
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="'.$tmpfile.'"');
      header('Content-Transfer-Encoding: binary');
      header('Content-Length: '.filesize($prefix.$tmpfile));
      ob_end_clean();
      flush();
      @readfile($prefix.$tmpfile);
      @unlink($prefix.$tmpfile);
      exit;
    }
  } elseif (isset($_POST['submit_reload'])) {
    $result = 99;
    if (isset($_POST['confirm_reload'])) {
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
    } else {
      $result = 7;
    }
  } elseif (isset($_POST['submit_reboot'])) {
    $result = 99;
    $delay = (int)$_POST['reboot_delay'];
    if (isset($_POST['confirm_reboot'])) {
      if ($delay == 0) {
        systemREBOOT($myself, 10);
      } else {
        if (scheduleREBOOT($delay)) {
          $result = ($delay > 0) ? 40 : 41;
        } else if ($delay < 0) {
          $result = 1;
        }
      }
    } else {
      $result = 7;
      header('Location: '.$myself.'?reboot_delay='.$delay.'&result='.$result);
      exit;
    }
  } elseif (isset($_POST['submit_shutdown'])) {
    $result = 99;
    if (isset($_POST['confirm_shutdown'])) {
      header('Location: /admin/shutdown.php');
      exit;
    } else {
      $result = 7;
    }
  } elseif (isset($_POST['firmware_submit'])) {
    $result = 99;
    $action = $_POST['firmware_action'];
    if (isset($_POST['firmware_confirm'])) {
      $file = '/usr/sbin/upgrade-run-image';
      $std_err = ' 2>/dev/null';
      if ($action === 'upgrade') {
        $result_str = shell($file.' '.$action.' "'.$REPOSITORY_URL.'"'.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      } elseif ($action === 'show') {
        $result_str = shell($file.' '.$action.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      } elseif ($action === 'revert') {
        $result_str = shell($file.' '.$action.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      } elseif ($action === 'check') {
        $result_str = shell($file.' '.$action.' "'.$REPOSITORY_URL.'"'.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      }
    } else {
      $result = 7;
      header('Location: '.$myself.'?firmware_action='.$action.'&result='.$result);
      exit;
    }
  } elseif (isset($_POST['sounds_submit'])) {
    $result = 99;
    $action = $_POST['sounds_action'];
    if (isset($_POST['sounds_type'], $_POST['sounds_lang'], $_POST['sounds_codec']) &&
        ($_POST['sounds_type'] !== '' || $action === 'show')) {
      $type = tuq($_POST['sounds_type']);
      $lang = tuq($_POST['sounds_lang']);
      $codec = tuq($_POST['sounds_codec']);
      $file = '/usr/sbin/upgrade-asterisk-sounds';
      $std_err = ' 2>/dev/null';
      if ($action === 'upgrade') {
        $result_str = shell($file.' '.$action.' '.$type.' '.$lang.' '.$codec.' "'.$SOUNDS_URL.'"'.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      } elseif ($action === 'remove') {
        $result_str = shell($file.' '.$action.' '.$type.' '.$lang.' '.$codec.' "'.$SOUNDS_URL.'"'.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      } elseif ($action === 'show') {
        $result_str = shell($file.' '.$action.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      }
    } else {
      $result = 9;
      header('Location: '.$myself.'?sounds_action='.$action.'&result='.$result);
      exit;
    }
  } elseif (isset($_POST['addon_package_submit'])) {
    $result = 99;
    $action = $_POST['addon_package_action'];
    if (isset($_POST['addon_package_type']) && ($_POST['addon_package_type'] !== '' || $action === 'show')) {
      $type = tuq($_POST['addon_package_type']);
      $file = '/usr/sbin/upgrade-package';
      $std_err = ' 2>/dev/null';
      if ($action === 'upgrade') {
        $result_str = shell($file.' '.$type.' '.$action.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      } elseif ($action === 'remove') {
        $result_str = shell($file.' '.$type.' '.$action.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      } elseif ($action === 'show') {
        if ($type !== '') {
          $result_str = shell($file.' '.$type.' '.$action.$std_err, $status);
        } else {
          $result_str = shell($file.' '.$action.$std_err, $status);
        }
        putACTIONresult($result_str, $status);
        exit;
      } elseif ($action === 'revert') {
        $result_str = shell($file.' '.$type.' '.$action.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      } elseif ($action === 'check') {
        $result_str = shell($file.' '.$type.' '.$action.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      }
    } else {
      $result = 19;
      header('Location: '.$myself.'?addon_package_action='.$action.'&result='.$result);
      exit;
    }
  } elseif (isset($_POST['runnix_submit'])) {
    $result = 99;
    $action = $_POST['runnix_action'];
    if (isset($_POST['runnix_confirm'])) {
      $file = '/usr/sbin/upgrade-RUNNIX-image';
      $std_err = ' 2>/dev/null';
      if ($action === 'upgrade') {
        $result_str = shell($file.' '.$action.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      } elseif ($action === 'show') {
        $result_str = shell($file.' '.$action.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      } elseif ($action === 'revert') {
        $result_str = shell($file.' '.$action.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      } elseif ($action === 'check') {
        $result_str = shell($file.' '.$action.$std_err, $status);
        putACTIONresult($result_str, $status);
        exit;
      }
    } else {
      $result = 7;
      header('Location: '.$myself.'?runnix_action='.$action.'&result='.$result);
      exit;
    }
  } elseif (isset($_FILES['restore'])) {
    $result = 1;
    $error = $_FILES['restore']['error'];
    $tmp_name = $_FILES['restore']['tmp_name'];
    $name = basename($_FILES['restore']['name']);
    if ($error == 0) {
      $size = filesize($tmp_name);
      if ($size === FALSE || $size > 8000000 || $size == 0) {
        $result = 20;
      } else {
        $suffix = '.tar';
        if (($len = strlen($name) - strlen($suffix)) < 0) {
          $len = 0;
        }
        if (stripos($name, $suffix, $len) === FALSE) {
          $suffix = '.tar.gz';
          if (($len = strlen($name) - strlen($suffix)) < 0) {
            $len = 0;
          }
          if (stripos($name, $suffix, $len) === FALSE) {
            $result = 22;
          }
        }
      }
    } elseif ($error == 1 || $error == 2) {
      $result = 20;
    } else {
      $result = 21;
    }
    if ($result == 1) {
      $result = 99;
      $name = '/mnt/kd/.restore'.$suffix;
      if (move_uploaded_file($tmp_name, $name)) {
        if (($name = uncompressARCHIVE($name, $suffix)) !== FALSE) {
          $result = restoreBASICconfig($name);
        } else {
          $result = 23;
        }
      }
      if ($name !== FALSE) {
        if (is_file($name)) {
          @unlink($name);
        }
      }
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  if (isset($_GET['reboot_delay'])) {
    $reboot_delay = $_GET['reboot_delay'];
  } else {
    $reboot_delay = '0';
  }

  if (isset($_GET['firmware_action'])) {
    $firmware_action = $_GET['firmware_action'];
  } else {
    $firmware_action = 'check';
  }

  if (isset($_GET['sounds_action'])) {
    $sounds_action = $_GET['sounds_action'];
  } else {
    $sounds_action = 'upgrade';
  }

  if (isset($_GET['addon_package_action'])) {
    $addon_package_action = $_GET['addon_package_action'];
  } else {
    $addon_package_action = 'upgrade';
  }

  if (isset($_GET['runnix_action'])) {
    $runnix_action = $_GET['runnix_action'];
  } else {
    $runnix_action = 'check';
  }

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 0) {
      putHtml('<p style="color: green;">Web management password successfully changed.</p>');
    } elseif ($result == 2) {
      putHtml('<p style="color: red;">Passwords do not match.</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Password too short.</p>');
    } elseif ($result == 4 || $result == 1101 || $result == 1102) {
      putHtml('<p style="color: red;">'.asteriskERROR($result).'</p>');
    } elseif ($result == 5) {
      putHtml('<p style="color: red;">Backup Failed.</p>');
    } elseif ($result == 6) {
      putHtml('<p style="color: red;">Unable to calculate web root directory.</p>');
    } elseif ($result == 7) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 8) {
      putHtml('<p style="color: red;">No Action, unable to open file.</p>');
    } elseif ($result == 9) {
      putHtml('<p style="color: red;">No Action, select a sound package type, language and CODEC for this action.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">System is Rebooting... back in <span id="count_down"><script language="JavaScript" type="text/javascript">document.write(count_down_secs);</script></span> seconds.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Asterisk Modules Reloaded.</p>');
    } elseif ($result == 12) {
      putHtml('<p style="color: green;">Web management password successfully changed. System "root" password unchanged.</p>');
    } elseif ($result == 13) {
      putHtml('<p style="color: green;">System "root" password and Web management password successfully changed.</p>');
    } elseif ($result == 15) {
      putHtml('<p style="color: red;">Backup Failed, error archiving unionfs partition.</p>');
    } elseif ($result == 16) {
      putHtml('<p style="color: red;">Backup Failed, click <a href="/admin/prefs.php" class="headerText">Prefs</a>then check "Backup temporary file uses /mnt/kd/ instead of /tmp/"</p>');
    } elseif ($result == 19) {
      putHtml('<p style="color: red;">No Action, select an add-on package type for this action.</p>');
    } elseif ($result == 20) {
      putHtml('<p style="color: red;">File size must be less then 8 MBytes.</p>');
    } elseif ($result == 21) {
      putHtml('<p style="color: red;">A previous Backup .tar.gz or .tar restore file must be defined.</p>');
    } elseif ($result == 22) {
      putHtml('<p style="color: red;">Invalid suffix, only files ending with .tar.gz and .tar are allowed.</p>');
    } elseif ($result == 23) {
      putHtml('<p style="color: red;">Invalid Backup archive, no configuration files were changed.</p>');
    } elseif ($result == 24) {
      putHtml('<p style="color: red;">Error writing configuration files.</p>');
    } elseif ($result == 30) {
      putHtml('<p style="color: green;">New Restored Configuration, Reboot System to apply.</p>');
    } elseif ($result == 40) {
      putHtml('<p style="color: green;">Reboot Scheduled within 24 hours.</p>');
    } elseif ($result == 41) {
      putHtml('<p style="color: green;">Scheduled Reboot Canceled.</p>');
    } elseif ($result == 99) {
      putHtml('<p style="color: red;">Action Failed.</p>');
    } elseif ($result == 100 || $result == 101 || $result == 102) {
      putHtml(getACTIONresult($result));
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
  <tr><td style="text-align: center;" colspan="3">
  <h2>Set Management Passwords:</h2>
  </td></tr><tr><td class="dialogText" style="text-align: right;">
  New&nbsp;Password:<input type="password" size="18" maxlength="32" name="pass1" />
  </td><td width="30">&nbsp;</td><td style="text-align: center;">
  <select name="user_pass">
  <option value="admin">Username:&nbsp;admin</option>
  <option value="staff">Username:&nbsp;staff</option>
  </select>
  </td></tr><tr><td class="dialogText" style="text-align: right;">
  Confirm&nbsp;Password:<input type="password" size="18" maxlength="32" name="pass2" />
  </td><td>&nbsp;</td><td style="text-align: center;">
  <input type="submit" value="Set Password" name="submit_password" />
  </td></tr></table>
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;">
  <h2>View System Files:</h2>
  </td><td style="text-align: center;">
  <h2>Configuration/File Backup:</h2>
  </td></tr><tr><td style="text-align: center;">

  <select name="view_file">
<?php
  putHtml('<optgroup label="&mdash; System Configuration &mdash;">');
  if (is_file($file = '/var/log/messages')) {
    putHtml('<option value="'.$file.'">syslog log messages</option>');
  }
  if (is_file($file = '/var/log/messages.0')) {
    putHtml('<option value="'.$file.'">syslog log messages.0</option>');
  } elseif (is_file($file = '/var/log/messages.1')) {
    putHtml('<option value="'.$file.'">syslog log messages.1</option>');
  }
  if (is_file($file = '/var/log/asterisk/messages')) {
    putHtml('<option value="'.$file.'">asterisk logs</option>');
  }
  if (is_file($file = '/mnt/kd/webgui-staff-activity.log')) {
    putHtml('<option value="'.$file.'">Staff Activity log</option>');
  }
  if (is_file($file = '/var/log/openvpn.log')) {
    putHtml('<option value="'.$file.'">OpenVPN Server log</option>');
  }
  if (is_file($file = '/var/log/openvpnclient.log')) {
    putHtml('<option value="'.$file.'">OpenVPN Client log</option>');
  }
  if (is_file($file = '/var/log/charon.log')) {
    putHtml('<option value="'.$file.'">IPsec strongSwan log</option>');
  }
  if (is_file($file = '/var/log/ups.log')) {
    putHtml('<option value="'.$file.'">UPS Daemon Startup log</option>');
  }
  if (is_file($file = '/var/log/zabbix_agentd.log')) {
    putHtml('<option value="'.$file.'">Zabbix Agent log</option>');
  }
  if (is_file($file = '/var/log/zabbix_proxy.log')) {
    putHtml('<option value="'.$file.'">Zabbix Proxy log</option>');
  }
  if (is_file($file = '/var/log/cron.log')) {
    putHtml('<option value="'.$file.'">Cron Daemon log</option>');
  }
  if (is_file($file = '/stat/etc/rc.conf')) {
    putHtml('<option value="'.$file.'">Default System Variables</option>');
  }
  if (is_file($file = '/mnt/kd/rc.conf.d/user.conf')) {
    putHtml('<option value="'.$file.'">User System Variables</option>');
  }
  if (is_file($file = '/mnt/kd/arno-iptables-firewall/firewall.conf')) {
    putHtml('<option value="'.$file.'">Firewall Defaults</option>');
  }
  if (is_file($file = '/mnt/kd/crontabs/root')) {
    putHtml('<option value="'.$file.'">Cron Jobs for root</option>');
  }
  if (is_file($file = '/var/lib/ntp/chrony.drift')) {
    putHtml('<option value="'.$file.'">NTP drift file</option>');
  }
  if (is_file($file = '/etc/udev/rules.d/70-persistent-net.rules')) {
    putHtml('<option value="'.$file.'">Net Interface Rules</option>');
  }
  if (is_file($file = '/etc/ssh/sshd_config')) {
    putHtml('<option value="'.$file.'">SSH Server sshd_config</option>');
  }
  foreach (glob('/etc/*.conf') as $globfile) {
    if (is_file($globfile)) {
      putHtml('<option value="'.$globfile.'">'.$globfile.'</option>');
    }
  }
  foreach (glob('/etc/dahdi/*.conf') as $globfile) {
    if (is_file($globfile)) {
      putHtml('<option value="'.$globfile.'">'.$globfile.'</option>');
    }
  }
  putHtml('</optgroup>');
  if (is_dir('/mnt/kd/docs')) {
    putHtml('<optgroup label="&mdash; Documentation &mdash;">');
    foreach (glob('/mnt/kd/docs/*') as $globfile) {
      if (is_file($globfile)) {
        putHtml('<option value="'.$globfile.'">'.basename($globfile).'</option>');
      }
    }
    putHtml('</optgroup>');
  }
  $optgroup = FALSE;
  foreach (glob('/etc/asterisk/*.conf') as $globfile) {
    if (is_file($globfile)) {
      if (! $optgroup) {
        putHtml('<optgroup label="&mdash; Asterisk Configuration &mdash;">');
        $optgroup = TRUE;
      }
      putHtml('<option value="'.$globfile.'">'.basename($globfile).'</option>');
    }
  }
  if ($optgroup) {
    if (is_file($file = '/etc/asterisk/extensions.lua')) {
      putHtml('<option value="'.$file.'">'.basename($file).'</option>');
    }
    if (is_file($file = '/etc/asterisk/extensions.ael')) {
      putHtml('<option value="'.$file.'">'.basename($file).'</option>');
    }
    putHtml('</optgroup>');
  }
  putHtml('</select>');

  putHtml('</td><td style="text-align: center;">');
  putHtml('<select name="backup_type">');
  if (($sel = getPREFdef($global_prefs, 'system_backup_exclude_suffix_cmdstr')) !== '') {
    putHtml('<option value="xsuffix" disabled="disabled">Excluding: '.$sel.'</option>');
  }
  $sel = (getPREFdef($global_prefs, 'system_backup_asturw') === 'yes') ? '&amp; unionfs ' : '';
  putHtml('<option value="full">All /mnt/kd/ '.$sel.'files</option>');
?>
  <option value="unionfs">Non-/mnt/kd/ unionfs files</option>
  <option value="basic" selected="selected">Basic Configuration files</option>
  <option value="cdr">Call Detail Records (cdr)</option>
  <option value="monitor">Monitor Recordings (mon)</option>
  <option value="voicemail">Voicemail Messages (vm)</option>
  <option value="lxc">Linux Containers (lxc)</option>
  <option value="config">All except: cdr, mon, vm, lxc</option>
  </select>
  </td></tr><tr><td style="text-align: center;">
  <input type="submit" value="View Selected File" name="submit_view" />
  </td><td style="text-align: center;">
  <input type="submit" value="Download Backup" name="submit_backup" />
  </td></tr><tr><td style="text-align: center;">
  <h2>Reload Asterisk Modules:</h2>
  </td><td style="text-align: center;">
  <h2>Reboot/Restart System:</h2>
  </td></tr><tr><td class="dialogText" style="text-align: center;">
  <input type="submit" value="Reload" name="submit_reload" />
  &ndash;
  <input type="checkbox" value="reload" name="confirm_reload" />&nbsp;Confirm
  </td><td class="dialogText" style="text-align: center;">
<?php
  putHtml('<select name="reboot_delay">');
  $reboot_delay_menu = getRebootDelayMenu();
  foreach ($reboot_delay_menu as $key => $value) {
    $sel = ($reboot_delay == $value) ? ' selected="selected"' : '';
    putHtml('<option value="'.$value.'"'.$sel.'>'.$key.'</option>');
  }
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<input type="submit" value="Reboot" name="submit_reboot" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="reboot" name="confirm_reboot" />&nbsp;Confirm');
  putHtml('</td></tr>');

  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<h2>System Firmware Upgrade:</h2>');
  putHtml('</td></tr><tr><td class="dialogText" style="text-align: center;" colspan="2">');
  putHtml('<select name="firmware_action">');
  $sel = ($firmware_action === 'check') ? ' selected="selected"' : '';
  putHtml('<option value="check"'.$sel.'>Check for New</option>');
  $sel = ($firmware_action === 'upgrade') ? ' selected="selected"' : '';
  putHtml('<option value="upgrade"'.$sel.'>Upgrade with New</option>');
  $sel = ($firmware_action === 'show') ? ' selected="selected"' : '';
  putHtml('<option value="show"'.$sel.'>Show Installed</option>');
  $sel = ($firmware_action === 'revert') ? ' selected="selected"' : '';
  putHtml('<option value="revert"'.$sel.'>Revert to Previous</option>');
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<input type="submit" value="Firmware" name="firmware_submit" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="firmware" name="firmware_confirm" />&nbsp;Confirm');
  putHtml('</td></tr><tr><td class="dialogText" style="text-align: center;" colspan="2">');
  putHtml('<strong>Repository URL:</strong> '.$REPOSITORY_URL);
  putHtml('</td></tr>');

if (is_file('/usr/sbin/upgrade-asterisk-sounds')) {
  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<h2>Asterisk Sounds Packages:</h2>');
  putHtml('</td></tr><tr><td class="dialogText" style="text-align: center;" colspan="2">');
  putHtml('Package:');
  putHtml('<select name="sounds_type">');
  foreach ($sounds_type_menu as $key => $value) {
    putHtml('<option value="'.$key.'">'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<select name="sounds_lang">');
  foreach ($sounds_lang_menu as $key => $value) {
    putHtml('<option value="'.$key.'">'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<select name="sounds_codec">');
  foreach ($sounds_codec_menu as $key => $value) {
    putHtml('<option value="'.$key.'">'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr><tr><td class="dialogText" style="text-align: center;" colspan="2">');
  putHtml('<select name="sounds_action">');
  $sel = ($sounds_action === 'upgrade') ? ' selected="selected"' : '';
  putHtml('<option value="upgrade"'.$sel.'>Upgrade/Install</option>');
  $sel = ($sounds_action === 'remove') ? ' selected="selected"' : '';
  putHtml('<option value="remove"'.$sel.'>Remove</option>');
  $sel = ($sounds_action === 'show') ? ' selected="selected"' : '';
  putHtml('<option value="show"'.$sel.'>Show Installed</option>');
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<input type="submit" value="Sounds Package" name="sounds_submit" />');
  putHtml('</td></tr><tr><td class="dialogText" style="text-align: center;" colspan="2">');
  putHtml('<strong>Sounds Pkg URL:</strong> '.$SOUNDS_URL);
  putHtml('</td></tr>');
}

if (is_file('/usr/sbin/upgrade-package')) {
  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<h2>Add-On Packages:</h2>');
  putHtml('</td></tr><tr><td class="dialogText" style="text-align: center;" colspan="2">');
  putHtml('Package:');
  putHtml('<select name="addon_package_type">');
  foreach ($addon_package_type_menu as $key => $value) {
    putHtml('<option value="'.$key.'">'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<select name="addon_package_action">');
  $sel = ($addon_package_action === 'upgrade') ? ' selected="selected"' : '';
  putHtml('<option value="upgrade"'.$sel.'>Upgrade/Install</option>');
  $sel = ($addon_package_action === 'check') ? ' selected="selected"' : '';
  putHtml('<option value="check"'.$sel.'>Check for New</option>');
  $sel = ($addon_package_action === 'remove') ? ' selected="selected"' : '';
  putHtml('<option value="remove"'.$sel.'>Remove</option>');
  $sel = ($addon_package_action === 'show') ? ' selected="selected"' : '';
  putHtml('<option value="show"'.$sel.'>Show Installed</option>');
  $sel = ($addon_package_action === 'revert') ? ' selected="selected"' : '';
  putHtml('<option value="revert"'.$sel.'>Revert to Previous</option>');
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<input type="submit" value="Add-On Package" name="addon_package_submit" />');
  putHtml('</td></tr>');
}

if (is_file('/usr/sbin/upgrade-RUNNIX-image')) {
  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<h2>RUNNIX Bootloader Upgrade:</h2>');
  putHtml('</td></tr><tr><td class="dialogText" style="text-align: center;" colspan="2">');
  putHtml('<select name="runnix_action">');
  $sel = ($runnix_action === 'check') ? ' selected="selected"' : '';
  putHtml('<option value="check"'.$sel.'>Check for New</option>');
  $sel = ($runnix_action === 'upgrade') ? ' selected="selected"' : '';
  putHtml('<option value="upgrade"'.$sel.'>Upgrade with New</option>');
  $sel = ($runnix_action === 'show') ? ' selected="selected"' : '';
  putHtml('<option value="show"'.$sel.'>Show Installed</option>');
  $sel = ($runnix_action === 'revert') ? ' selected="selected"' : '';
  putHtml('<option value="revert"'.$sel.'>Revert to Previous</option>');
  putHtml('</select>');
  putHtml('&ndash;');
  putHtml('<input type="submit" value="Runnix" name="runnix_submit" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="runnix" name="runnix_confirm" />&nbsp;Confirm');
  putHtml('</td></tr>');
}

  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<h2>System Shutdown/Halt:</h2>');
  putHtml('</td></tr><tr><td class="dialogText" style="text-align: center;" colspan="2">');
  putHtml('<input type="submit" value="Shutdown" name="submit_shutdown" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="shutdown" name="confirm_shutdown" />&nbsp;Confirm');
  putHtml('</td></tr>');

  putHtml('</table>');
  putHtml('</form>');

  putHtml('<form method="post" action="'.$myself.'" enctype="multipart/form-data">');
  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr><td style="text-align: center;">');
  putHtml('<h2>Restore Basic Configuration:</h2>');
  putHtml('<input type="hidden" name="MAX_FILE_SIZE" value="8000000" />');
  putHtml('</td></tr><tr><td style="text-align: center;">');
  putHtml('<input type="file" name="restore" />');
  putHtml('&ndash;');
  putHtml('<input type="submit" name="submit" value="Restore Configuration" />');
  putHtml('</td></tr>');
  putHtml('</table>');
  putHtml('</form>');

  $db = parseRCconf($CONFFILE);

  putHtml('<h2>System Configuration Variables:</h2>');
  putHtml('<table width="100%" class="datatable">');
  putHtml('<tr>');

  if (arrayCount($db['data']) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Variable", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Configuration Value", "</td>";
    $i = 0;
    foreach ($db['data'] as $var => $value) {
      putHtml("</tr>");
      echo '<tr ', ($i++ % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td>', $var, '</td>';
      if ($value === '') {
        $value = '""';
      } elseif ($var === 'PPPOEPASS' ||
          $var === 'SMTP_PASS' ||
          $var === 'DDPASS' ||
          $var === 'GUI_FIREWALL_RULES' ||
          $var === 'STATICHOSTS' ||
          $var === 'LDAP_SERVER_PASS' ||
          $var === 'PPTP_USER_PASS' ||
          $var === 'OVPN_USER_PASS' ||
          $var === 'OVPNC_USER_PASS' ||
          $var === 'UPS_MONITOR_PASS' ||
          $var === 'IPSECM_XAUTH_USER_PASS' ||
          $var === 'IPSEC_PSK_ASSOCIATIONS') {
        $value = '********';
      } elseif (strlen($value) > 56) {
        $value = wordwrap(htmlspecialchars($value), 50, '<br />', TRUE);
      } else {
        $value = htmlspecialchars($value);
      }
      echo '<td>', $value, '</td>';
    }
  } else {
    echo '<td style="text-align: center;">No Configuration Entries for file: ', $db['conffile'], '</td>';
  }
  putHtml("</tr>");
  putHtml("</table>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
