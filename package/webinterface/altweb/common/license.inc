<?php

// Copyright (C) 2008-2010 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// license.inc for AstLinux
// 10-27-2010
//
$LICENSEFILE = $COMMON.'license-astlinux.txt';

$LICENSEPKGSFILE = $COMMON.'license-packages.txt';

require_once $COMMON.'functions.php';

if (getPREFdef($global_prefs, 'status_require_auth') === 'yes') {
  if (! $STATUS_AUTH) {
    header('Location: /admin/license.php');
    exit;
  }
}

// Function: parseLICENSEpackages
//
function parseLICENSEpackages($file) {
  $id = 0;
  if (($fh = @fopen($file, "r")) !== FALSE) {
    while (! feof($fh)) {
      if (($line = trim(fgets($fh, 1024))) !== '') {
        $linetokens = explode('~', $line);
        if (isset($linetokens[0]) && ($linetokens[0][0] !== '#')) {
          $status['data'][$id]['package'] = $linetokens[0];
          $status['data'][$id]['license'] = $linetokens[1];
          $id++;
        }
      }
    }
    fclose($fh);
  }
  // Sort by Package Name
  if ($id > 1) {
    foreach ($status['data'] as $key => $row) {
      $package[$key] = strtolower($row['package']);
    }
    array_multisort($package, SORT_ASC, SORT_STRING, $status['data']);
  }
  return($status);
}

// Start of HTTP GET
$ACCESS_RIGHTS = 'all';
require_once $COMMON.'header.php';

?>
<center><p>&nbsp;</p></center>

<center>
<table class="layout"><tr><td><center>
<table class="stdtable">
<tr><td style="text-align: center;">
<h2>AstLinux License:</h2>
</td></tr>
<?php
  putHtml('<tr><td class="dialogText" style="text-align: left;">');
  if (($fp = @fopen($LICENSEFILE, "rb")) !== FALSE) {
    while (! feof($fp)) {
      if (($line = fgets($fp, 1024)) != '') {
        $line = str_replace('(c)', '<big>&copy;</big>', htmlspecialchars($line));
        echo $line;
        putHtml("<br />");
      }
    }
    fclose($fp);
    putHtml("<br />");
  } else {
    echo 'No License file found: ', $LICENSEFILE;
  }
  putHtml("</td></tr>");
  putHtml("</table>");

  $db = parseLICENSEpackages($LICENSEPKGSFILE);

  putHtml('<h2>AstLinux Packages Licenses:</h2>');
  putHtml('<table width="100%" class="datatable">');
  putHtml('<tr>');

  if (($n = count($db['data'])) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Package", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "License", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      $package = $db['data'][$i]['package'];
      echo '<td>', htmlspecialchars($package), '</td>';
      $license = $db['data'][$i]['license'];
      if (strlen($license) > 64) {
        $license = wordwrap(htmlspecialchars($license), 58, '<br />', TRUE);
      } else {
        $license = htmlspecialchars($license);
      }
      $license = str_replace('(c)', '<big>&copy;</big>', $license);
      echo '<td>', $license, '</td>';
    }
  } else {
    echo '<td style="text-align: center;">No License file found: ', $LICENSEPKGSFILE, '</td>';
  }
  putHtml("</tr>");

  putHtml("</table>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");

// End of HTTP GET
require_once $COMMON.'footer.php';

?>
