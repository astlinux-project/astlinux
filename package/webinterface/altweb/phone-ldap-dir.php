<?php

// Copyright (C) 2008-2013 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// phone-ldap-dir.php for AstLinux
// 25-05-2013, Convert phone-dir.php for use with LDAP
// 16-09-2013, Add Microsoft Active Directory custom variable
//
// Usage: https://pbx/phone-ldap-dir.php?tls&type=generic&search=
// If 'tls' appears, regardless of value,  start_tls is enabled (defaults to disabled)
// type= generic, polycom, aastra, yealink, snom (defaults to "generic")
// search= text to search for anywhere in name (defaults to none)

$myself = $_SERVER['PHP_SELF'];

$opts['tls'] = isset($_GET['tls']) ? TRUE : FALSE;
$opts['type'] = isset($_GET['type']) ? $_GET['type'] : 'generic';
$opts['search'] = isset($_GET['search']) ? $_GET['search'] : '';

$data = '';

// Function: buildData
//
function buildData($str) {
  global $data;

  $data .= $str."\n";
}

// Function: extract_dialing_digits
//
function extract_dialing_digits($number, $type) {
  // Convert human formated number to what the phone 'type' expects

  $pattern = array('/[^0-9]+/');
  $replace = array('');

  $digits = preg_replace($pattern, $replace, $number);

  return($digits);
}

// Function: ldap_utf8_decode
//
function ldap_utf8_decode($utf8, $type) {
  // Convert LDAP UTF-8 encoding to what the phone 'type' expects

  if ($type === 'aastra') {
    $str = utf8_decode($utf8);
  } elseif ($type === 'yealink') {
    $str = $utf8;
  } elseif ($type === 'snom') {
    $str = $utf8;
  } else {
    $str = utf8_decode($utf8);
  }
  return($str);
}

// Function: LDAP_Client
//
function LDAP_Client($start_tls, &$uri, &$base) {

  if (! function_exists('ldap_connect')) {
    return(FALSE);
  }

  // begin - Custom variables, don't edit origional phone-ldap-dir.php script.
  // Copy this script to /mnt/kd/phoneprov/phone-ldap-dir.php to make changes.
  $user = '';
  $pass = '';
  $proto_version = 3;
  $ms_ad = FALSE;       // Set to TRUE for Active Directory server
  // end

  $uri = '';
  $base = '';
  if (is_file($ldap_conf = '/etc/openldap/ldap.conf')) {
    if (($lines = @file($ldap_conf, FILE_IGNORE_NEW_LINES)) !== FALSE) {
      if (($grep = current(preg_grep('/^URI\s/', $lines))) !== FALSE) {
        $uri = trim(substr($grep, 4));
      }
      if (($grep = current(preg_grep('/^BASE\s/', $lines))) !== FALSE) {
        $base = trim(substr($grep, 5));
      }
    }
  }
  if ($uri === '') {
    return(FALSE);
  }

  if (($client = ldap_connect($uri)) !== FALSE) {
    if ($proto_version > 0) {
      ldap_set_option($client, LDAP_OPT_PROTOCOL_VERSION, $proto_version);
    }
    if ($ms_ad) {
      ldap_set_option($client, LDAP_OPT_REFERRALS, 0);
    }
    if ($start_tls && strncmp($uri, 'ldaps://', 8)) {  // Don't use together with ldaps://
      if (! ldap_start_tls($client)) {
        ldap_close($client);
        return(FALSE);
      }
    }
    if ($user !== '' && $pass !== '') {
      $ok_bind = ldap_bind($client, $user, $pass);
    } else {
      $ok_bind = ldap_bind($client);
    }
    if (! $ok_bind) {
      ldap_close($client);
      return(FALSE);
    }
  }
  return($client);
}

// Function: getTITLEname
//
function getTITLEname($opts) {

  $cmd = 'Directory Search: '.$opts['search'];

  return($cmd);
}

if ($opts['type'] === 'aastra') {
  buildData('<?xml version="1.0" encoding="ISO-8859-1"?>');
  buildData('<AastraIPPhoneDirectory type="string">');
  buildData('<Title>'.getTITLEname($global_prefs).'</Title>');
} elseif ($opts['type'] === 'yealink') {
  buildData('<?xml version="1.0" encoding="UTF-8"?>');
  buildData('<YealinkIPPhoneDirectory clearlight="true">');
  buildData('<Title>'.getTITLEname($opts).'</Title>');
} elseif ($opts['type'] === 'snom') {
  buildData('<?xml version="1.0" encoding="UTF-8"?>');
  buildData('<SnomIPPhoneDirectory>');
  buildData('<Title>'.getTITLEname($opts).'</Title>');
  buildData('<Prompt>Prompt</Prompt>');
} else {
  buildData('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">');
  buildData('<html>');
  buildData('<head>');
  buildData('<title>'.getTITLEname($opts).'</title>');
  buildData('</head>');
  buildData('<body>');
}

if (($ldapconn = LDAP_Client($opts['tls'], $uri, $dn)) !== FALSE) {

  $name = $opts['search'];
  $filter = "(|(sn=$name*)(givenname=$name*))";
  $justthese = array('cn', 'sn', 'givenname', 'displayname', 'telephonenumber', 'mobile', 'cellphone', 'homephone');

  if (($sr = ldap_search($ldapconn, $dn, $filter, $justthese)) !== FALSE) {
    ldap_sort($ldapconn, $sr, 'givenname');
    ldap_sort($ldapconn, $sr, 'sn');
    $info = ldap_get_entries($ldapconn, $sr);

    if (($n = $info['count']) > 0) {
      for ($i = 0; $i < $n; $i++) {
        $numbers = array();
        if (($number = $info[$i]['telephonenumber'][0]) != '') {
          $numbers[] = extract_dialing_digits($number, $opts['type']);
        }
        if (($number = $info[$i]['mobile'][0]) != '') {
          $numbers[] = extract_dialing_digits($number, $opts['type']);
        }
        if (($number = $info[$i]['cellphone'][0]) != '') {
          $numbers[] = extract_dialing_digits($number, $opts['type']);
        }
        if (($number = $info[$i]['homephone'][0]) != '') {
          $numbers[] = extract_dialing_digits($number, $opts['type']);
        }

        if (($value = $info[$i]['displayname'][0]) != '') {
          ;
        } elseif (($value = $info[$i]['cn'][0]) != '') {
          ;
        } elseif (($value = $info[$i]['sn'][0]) != '') {
          ;
        } elseif (($value = $info[$i]['givenname'][0]) != '') {
          ;
        }
        if ($value != '') {
          $value = htmlspecialchars(ldap_utf8_decode($value, $opts['type']));
        }

        if (isset($numbers[0]) && $numbers[0] != '' && $value != '') {
          if ($opts['type'] === 'aastra') {
            buildData('<MenuItem>');
            buildData('<Prompt>'.$value.'</Prompt>');
            buildData('<URI>'.$numbers[0].'</URI>');
            buildData('</MenuItem>');
          } elseif ($opts['type'] === 'yealink') {
            buildData('<DirectoryEntry>');
            buildData('<Name>'.$value.'</Name>');
            foreach ($numbers as $number) {
              buildData('<Telephone>'.$number.'</Telephone>');
            }
            buildData('</DirectoryEntry>');
          } elseif ($opts['type'] === 'snom') {
            buildData('<DirectoryEntry>');
            buildData('<Name>'.$value.'</Name>');
            buildData('<Telephone>'.$numbers[0].'</Telephone>');
            buildData('</DirectoryEntry>');
          } elseif ($opts['type'] === 'polycom') {
            buildData('<p><a href="tel://'.$numbers[0].'">'.$numbers[0].'</a> '.$value.'</p>');
          } else {
            buildData('<li><a href="tel://'.$numbers[0].'">'.$numbers[0].'</a> '.$value.'</li><br />');
          }
        }
        unset($numbers);
      }
    } else {
      buildData('<p>No Matches</p>');
    }
  } else {
    buildData('<p>LDAP Search Failed</p>');
  }
  ldap_close($ldapconn);
} else {
  buildData('<p>LDAP Connection Failed</p>');
}

if ($opts['type'] === 'aastra') {
  buildData('</AastraIPPhoneDirectory>');
  header('Content-Type: text/xml');
} elseif ($opts['type'] === 'yealink') {
  buildData('</YealinkIPPhoneDirectory>');
  header('Content-Type: text/xml');
} elseif ($opts['type'] === 'snom') {
  buildData('</SnomIPPhoneDirectory>');
  header('Content-Type: text/xml');
} else {
  buildData('</body>');
  buildData('</html>');
  header('Content-Type: text/html');
}
header('Content-Length: '.strlen($data));
ob_clean();
flush();
echo $data;
exit;
?>
