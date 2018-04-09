<?php

// Copyright (C) 2008-2009 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// users-password.php for AstLinux
// 07-29-2008
// 08-21-2009, Add support to also set system root password if not changed from default.

// Function genHTpasswd()
//
function genHTpasswd($user, $pass1, $pass2, $minlen) {
  $result = 1;
  $id = 0;
  $oldpass['admin'] = '';
  $oldpass['staff'] = '';

  if (($HTPASSWD = getPASSWDlocation()) !== '') {
    if (strlen($pass1) > $minlen) {
      if ($pass1 === $pass2) {
        $jumble = md5(time() . getmypid());
        $salt = substr($jumble, 0, 2);
        $htpasswd_text = $user.':'.crypt($pass1, $salt);

        if (($fp = @fopen($HTPASSWD,"rb")) !== FALSE) {
          while (! feof($fp)) {
            if (($line = trim(fgets($fp, 1024))) !== '') {
              if (strncmp($line, 'admin:', 6) == 0) {
                $oldpass['admin'] = $line;
              } elseif (strncmp($line, 'staff:', 6) == 0) {
                $oldpass['staff'] = $line;
              } else {
                $oldpass['users'][$id] = $line;
                $id++;
              }
            }
          }
          fclose($fp);
        }
        if (($fp = @fopen($HTPASSWD,"wb")) !== FALSE) {
          if ($user === 'admin') {
            $value = $htpasswd_text;
            fwrite($fp, $value."\n");
            if (($value = $oldpass['staff']) !== '') {
              fwrite($fp, $value."\n");
            }
          } elseif ($user === 'staff') {
            if (($value = $oldpass['admin']) !== '') {
              fwrite($fp, $value."\n");
            }
            $value = $htpasswd_text;
            fwrite($fp, $value."\n");
          } else {
            if (($value = $oldpass['admin']) !== '') {
              fwrite($fp, $value."\n");
            }
            if (($value = $oldpass['staff']) !== '') {
              fwrite($fp, $value."\n");
            }
            $value = $htpasswd_text;
            fwrite($fp, $value."\n");
            // remove any existing user
            $len = strlen($user) + 1;
            for ($i = 0; $i < $id; $i++) {
              if (strncmp($oldpass['users'][$i], $user.':', $len) == 0) {
                $oldpass['users'][$i] = '';
              }
            }
          }
          for ($i = 0; $i < $id; $i++) {
            if (($value = $oldpass['users'][$i]) !== '') {
              fwrite($fp, $value."\n");
            }
          }
          fclose($fp);
          $result = 0;
          //
          // If the system root password has not been reset from the default, set it to the admin password
          //
          if ($user === 'admin') {
            syslog(LOG_WARNING, 'Web Interface "admin" password changed.  Remote Address: '.$_SERVER['REMOTE_ADDR']);
            $result = 12;
            shell('/usr/sbin/check-default-passwd root >/dev/null 2>/dev/null', $status);
            if ($status == 0) {
              shell('echo \'root:'.$pass1.'\' | /usr/sbin/chpasswd -m >/dev/null 2>/dev/null', $status);
              if ($status == 0) {
                syslog(LOG_WARNING, 'System "root" password changed.  Remote Address: '.$_SERVER['REMOTE_ADDR']);
                $result = 13;
              }
            }
          }
        } else {
          $result = 99;
        }
      } else {
        $result = 2;
      }
    } else {
      $result = 3;
    }
  } else {
    $result = 6;
  }
  return($result);
}

// Function delHTpasswd()
//
function delHTpasswd($user) {
  $result = 1;
  $id = 0;

  if (($HTPASSWD = getPASSWDlocation()) !== '') {
    if (($fp = @fopen($HTPASSWD,"rb")) !== FALSE) {
      $len = strlen($user) + 1;
      while (! feof($fp)) {
        if (($line = trim(fgets($fp, 1024))) !== '') {
          if (strncmp($line, $user.':', $len) != 0) {
            $oldpass['users'][$id] = $line;
            $id++;
          }
        }
      }
      fclose($fp);
      if (($fp = @fopen($HTPASSWD,"wb")) !== FALSE) {
        for ($i = 0; $i < $id; $i++) {
          $value = $oldpass['users'][$i];
          fwrite($fp, $value."\n");
        }
        fclose($fp);
        $result = 0;
      } else {
        $result = 99;
      }
    } else {
      $result = 99;
    }
  } else {
    $result = 6;
  }
  return($result);
}

?>
