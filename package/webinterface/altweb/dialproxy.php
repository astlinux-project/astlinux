<?php

// Copyright (C) 2008-2023 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// dialproxy.php for AstLinux
// 09-03-2009
// 10-31-2013, Add POST method
// 12-11-2015, Add GET/POST variables 'phone' and 'exten' synonymous for 'num' and 'ext'
// 03-22-2019, Add Asterisk 16 support to AMIcommand()
//
// GET Method:
// Usage: http://pbx/dialproxy.php?num=2223334444&ext=default
// Usage: https://pbx/dialproxy.php?num=2223334444&ext=default
//
// POST Method:
// Usage: curl --data-urlencode 'num=2223334444' --data-urlencode 'ext=default' http://pbx/dialproxy.php
// Usage: curl --data-urlencode 'num=2223334444' --data-urlencode 'ext=default' --insecure https://pbx/dialproxy.php
//
// [webinterface] manager.conf context must contain
// read = command,call,originate
// write = command,call,originate
//
// Asterisk "astdb" database actionlist DIALPROXY-default
// Required value channel, ie. SIP/1234
// with ~ seprated options context=, timeout=, callerid=, localcallerid=, dialprefix= and allow=
//
// Debug Mode: (set debug=1)
//             Useful for testing DIALING_PREFIX_NUMBERS prefix settings without dialing.
// Usage: curl "http://pbx/dialproxy.php?num=2223334444&ext=default&debug=1"
// Usage: curl --data-urlencode 'num=2223334444' --data-urlencode 'ext=default' -d 'debug=1' http://pbx/dialproxy.php
//

$remote_addr = $_SERVER['REMOTE_ADDR'];

// The context to make the outgoing call from
$opts['context'] = 'default';
// The amount to time to ring extension
$opts['timeout'] = '30';
// The outgoing caller id
$opts['callerid'] = '';
// The caller id for local (2-4 char) extension destinations
$opts['localcallerid'] = '';
// Dial Prefix
$opts['dialprefix'] = '';
// Allowed IP addresses which only have access, space-or-comma separated
// defaults to everyone.
$opts['allow'] = '0.0.0.0';

// Function: myexit
//
function myexit($status, $statusStr) {
  global $remote_addr;

  if ($status != 0) {
    header('HTTP/1.0 401 Forbidden');
    syslog(LOG_WARNING, "Failed dialproxy.php access  Remote Address: $remote_addr  Status: $statusStr");
    sleep(4);
  }
  header('Content-Type: text/plain');
  header('Content-Length: '.(strlen($statusStr)+1));
  ob_clean();
  flush();
  echo $statusStr, "\n";
  exit($status);
}

// Function: AMIcommand
//
function AMIcommand($cmd, &$result) {

  $result = '';
  if (($socket = @fsockopen('127.0.0.1', '5038', $errno, $errstr, 5)) === FALSE) {
    return(1);
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
        return(2);
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
  while (! feof($socket) && ! $info['timed_out']) {
    $line = fgets($socket, 1024);
    $info = stream_get_meta_data($socket);
    if (strncasecmp($line, '--END COMMAND--', 15) == 0) {
      break;
    }
    if ($output_header && strncasecmp($line, 'Output: ', 8) != 0) {
      break;
    }
    $result .= $output_header ? substr($line, 8) : $line;
  }
  // end command data
  while (! feof($socket) && ! $info['timed_out']) {
    fgets($socket, 256);
    $info = stream_get_meta_data($socket);
  }
  fclose($socket);

  return($info['timed_out'] ? 3 : 0);
}

// Function: AMIoriginate
//
function AMIoriginate($num, $channel, $opts) {

  if (($socket = @fsockopen('127.0.0.1', '5038', $errno, $errstr, 5)) === FALSE) {
    return(1);
  }
  if ($opts['localcallerid'] !== '' && strlen($num) >= 2 && strlen($num) <= 4) {
    $callerid = $opts['localcallerid'];
  } elseif ($opts['callerid'] !== '') {
    $callerid = $opts['callerid'];
  } else {
    $callerid = "DialProxy <".$opts['dialprefix'].$num.">";
  }
  fputs($socket, "Action: login\r\n");
  fputs($socket, "Username: webinterface\r\n");
  fputs($socket, "Secret: webinterface\r\n");
  fputs($socket, "Events: off\r\n\r\n");

  fputs($socket, "Action: originate\r\n");
  fputs($socket, "Channel: $channel\r\n");
  fputs($socket, "Exten: ".$opts['dialprefix']."$num\r\n");
  fputs($socket, "Context: ".$opts['context']."\r\n");
  fputs($socket, "Timeout: ".$opts['timeout']."000\r\n");
  fputs($socket, "Callerid: $callerid\r\n");
  fputs($socket, "Priority: 1\r\n\r\n");

  fputs($socket, "Action: logoff\r\n\r\n");

  stream_set_timeout($socket, (int)$opts['timeout'] + 10);
  $info = stream_get_meta_data($socket);
  while (! feof($socket) && ! $info['timed_out']) {
    $line = fgets($socket, 256);
    $info = stream_get_meta_data($socket);
    if (strncasecmp($line, 'Response: Error', 15) == 0) {
      $noanswer = FALSE;
      while (! feof($socket) && ! $info['timed_out']) {
        $line = fgets($socket, 256);
        $info = stream_get_meta_data($socket);
        if (strncasecmp($line, 'Message: Originate failed', 25) == 0) {
          $noanswer = TRUE;
        }
      }
      fclose($socket);
      return($noanswer ? 10 : 2);
    }
    if (strncasecmp($line, 'Response: Goodbye', 17) == 0) {
      break;
    }
  }
  while (! feof($socket) && ! $info['timed_out']) {
    fgets($socket, 256);
    $info = stream_get_meta_data($socket);
  }
  fclose($socket);

  return($info['timed_out'] ? 3 : 0);
}

// Function: normalize_phone_number
//
function normalize_phone_number($num, &$opts) {

  if ($num !== '') {
    if (($df = trim(shell_exec('. /etc/rc.conf; echo "$DIALING_PREFIX_NUMBERS"'))) !== '') {
      $df_opts = explode('~', $df);
      $internationalprefix = isset($df_opts[0]) ? $df_opts[0] : '';
      $nationalprefix = isset($df_opts[1]) ? $df_opts[1] : '';
      $countryprefix = isset($df_opts[2]) ? $df_opts[2] : '';
      $outgoinglineprefix = isset($df_opts[3]) ? $df_opts[3] : '';

      if ($outgoinglineprefix !== '') {
        $opts['dialprefix'] = $outgoinglineprefix;
      }
      if ($nationalprefix !== '') {
        $num = preg_replace('/\('.$nationalprefix.'\)/', '', $num);
      }
      $num = preg_replace('/[^0-9*#+]/', '', $num);
      if ($countryprefix !== '') {
        $match = '+'.$countryprefix;
        if (strncmp($num, $match, strlen($match)) == 0) {
          $num = $nationalprefix.substr($num, strlen($match));
        }
      }
      if ($internationalprefix !== '') {
        if (strncmp($num, '+', 1) == 0) {
          $num = $internationalprefix.substr($num, 1);
        }
      }
    } else {
      $num = preg_replace('/[^0-9*#]/', '', $num);
    }
  }
  return($num);
}

if (isset($_POST['num'])) {
  $num = $_POST['num'];
} elseif (isset($_POST['phone'])) {
  $num = $_POST['phone'];
} elseif (isset($_GET['num'])) {
  $num = $_GET['num'];
} elseif (isset($_GET['phone'])) {
  $num = $_GET['phone'];
} else {
  $num = '';
}
if ($num !== '') {
  $num = normalize_phone_number($num, $opts);
}

if (isset($_POST['ext'])) {
  $ext = $_POST['ext'];
} elseif (isset($_POST['exten'])) {
  $ext = $_POST['exten'];
} elseif (isset($_GET['ext'])) {
  $ext = $_GET['ext'];
} elseif (isset($_GET['exten'])) {
  $ext = $_GET['exten'];
} else {
  $ext = '';
}

if (isset($_POST['debug']) || isset($_GET['debug'])) {
  myexit(0, "Debug: num=$num ext=$ext dialprefix=".$opts['dialprefix']);
}

if ($num === '' || $ext === '') {
  myexit(1, 'Error');
}

if ($err = AMIcommand("database get actionlist DIALPROXY-$ext", $result)) {
  if ($err == 1) {
    myexit(1, 'The "manager.conf" file is not enabled for 127.0.0.1 on port 5038.');
  } elseif ($err == 2) {
    myexit(1, 'The "manager.conf" file is not defined properly.');
  } else {
    myexit(1, 'Asterisk not responding.');
  }
}
$lines = explode("\n", ltrim($result));
$line = trim($lines[0]);
if (strncasecmp($line, 'VALUE:', 6) == 0) {
  $line = trim(substr($line, 6));
} else {
  myexit(1, 'Error');
}
$tokens = explode('~', $line);
$channel = $tokens[0];
if (isset($tokens[1])) {
  $pairs = array_slice($tokens, 1);
  foreach ($pairs as $pair) {
    if (strncmp($pair, 'context=', 8) == 0) {
      $opts['context'] = substr($pair, 8);
    } elseif (strncmp($pair, 'timeout=', 8) == 0) {
      $opts['timeout'] = substr($pair, 8);
    } elseif (strncmp($pair, 'callerid=', 9) == 0) {
      $opts['callerid'] = substr($pair, 9);
    } elseif (strncmp($pair, 'localcallerid=', 14) == 0) {
      $opts['localcallerid'] = substr($pair, 14);
    } elseif (strncmp($pair, 'dialprefix=', 11) == 0) {
      $opts['dialprefix'] = substr($pair, 11);
    } elseif (strncmp($pair, 'allow=', 6) == 0) {
      $opts['allow'] = substr($pair, 6);
    }
  }
}

if ($opts['allow'] !== '0.0.0.0') {  // Apply allow access list
  $disallow = TRUE;
  $allowips = preg_split('/[ ,]+/', trim($opts['allow'], ' ,'));
  foreach ($allowips as $allowip) {
    if ($remote_addr === $allowip) {
      $disallow = FALSE;
      break;
    }
  }
  if ($disallow) {
    myexit(1, 'Disallowed IP Address');
  }
}

if ($err = AMIoriginate($num, $channel, $opts)) {
  if ($err == 10) {
    myexit(0, 'No Answer');
  } else {
    myexit(1, 'Error');
  }
}

myexit(0, 'Success');
?>
