<?php

// Copyright (C) 2008-2011 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// view.php for AstLinux
// 04-09-2008
// 02-26-2009, Added staff-user support to log files
//

require_once '../common/functions.php';

// Start of HTTP GET
$ACCESS_RIGHTS = 'staff';
require_once '../common/header.php';

  $file = isset($_GET['file']) ? $_GET['file'] : '';
  $pos = strrpos($file, '/');
  $dir = substr($file, 0, $pos);

  putHtml("<center>");
  if ($file === '') {
    putHtml('<p style="color: red;">Invalid Entry.</p>');
  } elseif (! $global_admin) {  // staff-user
    if ($dir === '/var/log' ||
        $dir === '/var/log/asterisk') {
      if (is_file($file)) {
        putHtml('<p style="color: green;">Filename: '.$file.'</p>');
      } else {
        putHtml('<p style="color: red;">Unable to open file: '.$file.'</p>');
        $file = '';
      }
    } else {
      putHtml('<p style="color: red;">Permission Denied!</p>');
      $file = '';
    }
  } else {
    if ($dir === '/var/log' ||
        $dir === '/var/log/asterisk' ||
        $dir === '/mnt/kd' ||
        $dir === '/mnt/kd/openvpn' ||
        $dir === '/mnt/kd/rc.conf.d' ||
        $dir === '/mnt/kd/arno-iptables-firewall' ||
        $dir === '/mnt/kd/crontabs' ||
        $dir === '/mnt/kd/docs' ||
        $dir === '/etc/asterisk' ||
        $dir === '/etc/dahdi' ||
        $file === '/etc/ssh/sshd_config' ||
        $file === '/stat/etc/rc.conf' ||
        $file === '/var/lib/ntp/chrony.drift' ||
        $file === '/etc/udev/rules.d/70-persistent-net.rules' ||
        ($dir === '/etc' && (substr($file, -5) === '.conf'))) {
      if (is_file($file)) {
        putHtml('<p style="color: green;">Filename: '.$file.'</p>');
      } else {
        putHtml('<p style="color: red;">Unable to open file: '.$file.'</p>');
        $file = '';
      }
    } else {
      putHtml('<p style="color: red;">Permission Denied!</p>');
      $file = '';
    }
  }
  putHtml("</center>");

  if ($file !== '') {
    putHtml("<pre>");
    if (($fp = @fopen($file, "rb")) !== FALSE) {
      $max = 250000;
      $stat = fstat($fp);
      if ($stat['size'] > $max) {
        @fseek($fp, -$max, SEEK_END);
        fgets($fp, 1024);
        echo "<strong>----- File too large to display, showing the end of the file -----</strong>\n";
      }
      while (! feof($fp)) {
        if (($line = fgets($fp, 1024)) != '') {
          echo htmlspecialchars($line);
        }
      }
      fclose($fp);
    }
    putHtml("</pre>");
  }
// End of HTTP GET
require_once '../common/footer.php';

?>
