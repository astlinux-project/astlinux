<?php

// Copyright (C) 2008-2019 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// staff.php for AstLinux
// 12-12-2009
// 01-21-2013, Add Restart Asterisk
// 01-15-2016, Add Restart FOP2
// 01-18-2016, Add Primary /mnt/kd/ files Backup
// 07-11-2019, Added Backup Exclude Suffixes support
//
// System location of webgui-staff-backup.conf
$CONFFILE = '/mnt/kd/webgui-staff-backup.conf';
// System location of webgui-staff-activity.log
$LOGFILE = '/mnt/kd/webgui-staff-activity.log';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

// Function: isDownloadValid
//
function isDownloadValid($fname) {

  if (! is_file($fname)) {
    return(FALSE);
  }

  $len = filesize($fname);
  if ($len === FALSE || $len < 6 || $len > 512) {
    return(FALSE);
  }

  return(TRUE);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_staff) {
    $result = 999;
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
    $prefix = '/mnt/kd/.';
    $tmpfile = $backup_name.'-'.$backup_type.'-'.date('Y-m-d').$suffix;
    $xsuffix = gen_BackupExcludeSuffix_args(getPREFdef($global_prefs, 'system_backup_exclude_suffix_cmdstr'));
    if ($backup_type === 'primary') {
      $wanpipe = '';
      foreach (glob('/mnt/kd/wanpipe/*.conf') as $globfile) {
        $wanpipe .= ' "wanpipe/'.basename($globfile).'"';
      }
      $templates = (is_dir('/mnt/kd/phoneprov/templates')) ? ' "phoneprov/templates"' : '';
      $srcfile = '$(ls -1 /mnt/kd/ | sed -e "s/^cdr-.*//" -e "s/^monitor$//" -e "s/^voicemail$//"';
      $srcfile .= ' -e "s/^bin$//" -e "s/^lxc$//" -e "s/^.*[.]bak$//" -e "s/^log.*//" -e "s/^backup.*//"';
      $srcfile .= ' -e "s/^wanpipe$//" -e "s/^fossil$//" -e "s/^phoneprov$//" -e "s/^tftpboot$//" -e "s/^lost[+]found$//")';
      $srcfile .= $wanpipe;
      $srcfile .= $templates;
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
      $result = 5;
    } else {
      $aesfile = $tmpfile.'.aes';
      shell('openssl enc -aes-256-cbc -salt -in '.$prefix.$tmpfile.' -out '.$prefix.$aesfile.' -pass file:'.$CONFFILE.' >/dev/null 2>/dev/null', $status);
      @unlink($prefix.$tmpfile);
      if ($status != 0) {
        @unlink($prefix.$aesfile);
        $result = 5;
      } else {
        $aessize = filesize($prefix.$aesfile);
        $mesg = date('Y-m-d H:i:s');
        $mesg .= '  BACKUP';
        $mesg .= '  File: '.$aesfile;
        $mesg .= '  Size: '.$aessize;
        $mesg .= '  Remote Address: '.$_SERVER['REMOTE_ADDR'];
        $mesg .= '  AES-256-CBC-Password:';
        // Use system call so password is not revealed withing PHP
        shell('echo \''.$mesg.'\' "$(cat '.$CONFFILE.')" >>'.$LOGFILE.' 2>/dev/null', $status);
        if ($status == 0) {
          syslog(LOG_INFO, '"Staff user" configuration backup generated: '.$aesfile.'  Remote Address: '.$_SERVER['REMOTE_ADDR']);
          chmod($LOGFILE, 0600);
          chmod($CONFFILE, 0600);
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$aesfile.'"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.$aessize);
        ob_end_clean();
        flush();
        @readfile($prefix.$aesfile);
        @unlink($prefix.$aesfile);
        exit;
      }
    }
  } elseif (isset($_POST['submit_reboot'])) {
    $result = 99;
    $delay = (int)$_POST['reboot_delay'];
    if (isset($_POST['confirm_reboot'])) {
      $mesg = date('Y-m-d H:i:s');
      $mesg .= '  REBOOT';
      if ($delay > 0) {
        $mesg .= '  Scheduled: '.str_pad(($delay % 24), 2, '0', STR_PAD_LEFT).':00';
      } else {
        $mesg .= '  Scheduled: '.($delay < 0 ? 'Canceled' : 'Now');
      }
      $mesg .= '  Remote Address: '.$_SERVER['REMOTE_ADDR'];
      @file_put_contents($LOGFILE, $mesg."\n", FILE_APPEND);
      chmod($LOGFILE, 0600);
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
  } elseif (isset($_POST['submit_restart_asterisk'])) {
    $result = 99;
    if (isset($_POST['confirm_restart_asterisk'])) {
      $mesg = date('Y-m-d H:i:s').'  RESTART_ASTERISK  Remote Address: '.$_SERVER['REMOTE_ADDR'];
      @file_put_contents($LOGFILE, $mesg."\n", FILE_APPEND);
      chmod($LOGFILE, 0600);
      $result = restartPROCESS('asterisk', 25, $result);
    } else {
      $result = 7;
    }
  } elseif (isset($_POST['submit_restart_fop2'])) {
    $result = 99;
    if (isset($_POST['confirm_restart_fop2'])) {
      $mesg = date('Y-m-d H:i:s').'  RESTART_FOP2  Remote Address: '.$_SERVER['REMOTE_ADDR'];
      @file_put_contents($LOGFILE, $mesg."\n", FILE_APPEND);
      chmod($LOGFILE, 0600);
      $result = restartPROCESS('fop2', 26, $result);
    } else {
      $result = 7;
    }
  } elseif (isset($_POST['submit_shutdown'])) {
    $result = 99;
    if (isset($_POST['confirm_shutdown'])) {
      header('Location: /admin/shutdown.php');
      exit;
    } else {
      $result = 7;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = $global_staff_disable_staff ? 'admin' : 'staff';
require_once '../common/header.php';

  if (isset($_GET['reboot_delay'])) {
    $reboot_delay = $_GET['reboot_delay'];
  } else {
    $reboot_delay = '0';
  }

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 5) {
      putHtml('<p style="color: red;">Backup Failed.</p>');
    } elseif ($result == 7) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">System is Rebooting... back in <span id="count_down"><script language="JavaScript" type="text/javascript">document.write(count_down_secs);</script></span> seconds.</p>');
    } elseif ($result == 15) {
      putHtml('<p style="color: red;">Backup Failed, error archiving unionfs partition.</p>');
    } elseif ($result == 25) {
      putHtml('<p style="color: green;">Asterisk'.statusPROCESS('asterisk').'.</p>');
    } elseif ($result == 26) {
      putHtml('<p style="color: green;">Asterisk Flash Operating Panel2'.statusPROCESS('fop2').'.</p>');
    } elseif ($result == 40) {
      putHtml('<p style="color: green;">Reboot Scheduled within 24 hours.</p>');
    } elseif ($result == 41) {
      putHtml('<p style="color: green;">Scheduled Reboot Canceled.</p>');
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
<?php
if (isDownloadValid($CONFFILE)) {
  putHtml('<tr><td style="text-align: center;">');
  putHtml('<h2>Configuration/File Backup:</h2>');
  putHtml('</td></tr><tr><td class="dialogText" style="text-align: center;">');
  putHtml('<strong>OpenSSL Encryption:</strong> AES-256-CBC');
  putHtml('</td></tr><tr><td style="text-align: center;">');
  putHtml('<select name="backup_type">');
  $sel = (getPREFdef($global_prefs, 'system_backup_asturw') === 'yes') ? '&amp; unionfs ' : '';
  putHtml('<option value="primary">Primary /mnt/kd/ '.$sel.'files</option>');
  putHtml('<option value="full">All /mnt/kd/ '.$sel.'files</option>');
  putHtml('</select>');
  putHtml('</td></tr><tr><td style="text-align: center;">');
  putHtml('<input type="submit" value="Download Backup" name="submit_backup" />');
  putHtml('</td></tr>');
}

  putHtml('<tr><td style="text-align: center;">');
  putHtml('<h2>Restart Asterisk:</h2>');
  putHtml('</td></tr><tr><td class="dialogText" style="text-align: center;">');
  putHtml('<input type="submit" value="Restart Asterisk" name="submit_restart_asterisk" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="restart_asterisk" name="confirm_restart_asterisk" />&nbsp;Confirm');
  putHtml('</td></tr>');
if (is_addon_package('fop2')) {
  putHtml('<tr><td class="dialogText" style="text-align: center;">');
  putHtml('<input type="submit" value="Restart FOP2" name="submit_restart_fop2" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="restart_fop2" name="confirm_restart_fop2" />&nbsp;Confirm');
  putHtml('</td></tr>');
}

  putHtml('<tr><td style="text-align: center;">');
  putHtml('<h2>Reboot/Restart System:</h2>');
  putHtml('</td></tr><tr><td class="dialogText" style="text-align: center;">');
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

  putHtml('<tr><td style="text-align: center;">');
  putHtml('<h2>System Shutdown/Halt:</h2>');
  putHtml('</td></tr><tr><td class="dialogText" style="text-align: center;">');
  putHtml('<input type="submit" value="Shutdown" name="submit_shutdown" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="shutdown" name="confirm_shutdown" />&nbsp;Confirm');
  putHtml('</td></tr>');

  putHtml('</table>');
  putHtml('</form>');

  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
