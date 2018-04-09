<?php

// Copyright (C) 2008-2011 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// phone-dir.php for AstLinux
// 08-06-2008
// 12-08-2008, Added XML support for Aastra Phones
// 21-12-2010, Added XML support for Yealink Phones - Rob Hillis
// 28-12-2010, Make Sort by Name case insensitive
// 25-01-2011, Add search option (particularly for Yealink phones, but
//             option works for all phones) - Rob Hillis
// 25-01-2011, Added XML support for Snom Phones
//
// Usage: https://pbx/phone-dir.php?type=generic&search=
// type= generic, polycom, aastra, yealink, snom (defaults to "generic")
// search= text to search for anywhere in name (defaults to none)

$familyname = "sysdialname";
$myself = $_SERVER['PHP_SELF'];

require_once './common/functions.php';

$opts['type'] = isset($_GET['type']) ? $_GET['type'] : 'generic';
$opts['search'] = isset($_GET['search']) ? $_GET['search'] : '';

if (($ext_prefix = getPREFdef($global_prefs, 'sysdial_ext_prefix_cmdstr')) === '') {
  $ext_prefix = '11';
}

$data = '';

// Function: buildData
//
function buildData($str) {
  global $data;

  $data .= $str."\n";
}

// Function: getTITLEname
//
function getTITLEname($g_prefs) {
  if (($cmd = htmlspecialchars(getPREFdef($g_prefs, 'title_name_cmdstr'))) === '') {
    $cmd = 'Directory';
  }
  return($cmd);
}

if ($opts['type'] === 'aastra') {
  buildData('<?xml version="1.0" encoding="ISO-8859-1"?>');
  buildData('<AastraIPPhoneDirectory type="string">');
  buildData('<Title>'.getTITLEname($global_prefs).'</Title>');
} elseif ($opts['type'] === 'yealink') {
  buildData('<?xml version="1.0" encoding="UTF-8"?>');
  buildData('<YealinkIPPhoneDirectory clearlight="true">');
  buildData('<Title>'.getTITLEname($global_prefs).'</Title>');
} elseif ($opts['type'] === 'snom') {
  buildData('<?xml version="1.0" encoding="UTF-8"?>');
  buildData('<SnomIPPhoneDirectory>');
  buildData('<Title>'.getTITLEname($global_prefs).'</Title>');
  buildData('<Prompt>Prompt</Prompt>');
} else {
  buildData('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">');
  buildData('<html>');
  buildData('<head>');
  buildData('<title>'.getTITLEname($global_prefs).'</title>');
  buildData('</head>');
  buildData('<body>');
}

  $db = parseAstDB($familyname);

  // Sort by Name
  if (($n = count($db['data'])) > 0) {
    foreach ($db['data'] as $key => $row) {
      $name[$key] = strtolower($row['value']);
    }
    array_multisort($name, SORT_ASC, SORT_STRING, $db['data']);
  }

  if (($n = count($db['data'])) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $number = $ext_prefix.$db['data'][$i]['key'];
      $value = htmlspecialchars($db['data'][$i]['value']);

      // Check to see if the entry matches the search criteria (if any)
      if ($opts['search'] === '' || stripos($db['data'][$i]['value'], $opts['search']) !== FALSE) {
        if ($opts['type'] === 'aastra') {
          buildData('<MenuItem>');
          buildData('<Prompt>'.$value.'</Prompt>');
          buildData('<URI>'.$number.'</URI>');
          buildData('</MenuItem>');
        } elseif ($opts['type'] === 'yealink' || $opts['type'] === 'snom') {
          buildData('<DirectoryEntry>');
          buildData('<Name>'.$value.'</Name>');
          buildData('<Telephone>'.$number.'</Telephone>');
          buildData('</DirectoryEntry>');
        } elseif ($opts['type'] === 'polycom') {
          buildData('<p><a href="tel://'.$number.'">'.$number.'</a> '.$value.'</p>');
        } else {
          buildData('<li><a href="tel://'.$number.'">'.$number.'</a> '.$value.'</li><br />');
        }
      }
    }
  } else {
    if ($db['status'] == 0) {
      buildData('<p>No Database Entries for: '.$db['family'].'</p>');
    } else {
      buildData('<p>'.asteriskERROR($db['status']).'</p>');
    }
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
