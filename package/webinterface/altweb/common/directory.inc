<?php

// Copyright (C) 2008-2010 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// directory.php for AstLinux
// 08-06-2008
// 04-23-2010, Allow directory.php to be authenticated
//

$familyname = "sysdialname";
$myself = $_SERVER['PHP_SELF'];

require_once $COMMON.'functions.php';

if (getPREFdef($global_prefs, 'directory_require_auth') === 'yes') {
  if (! $DIRECTORY_AUTH) {
    header('Location: /admin/directory.php');
    exit;
  }
}

if (($ext_prefix = getPREFdef($global_prefs, 'sysdial_ext_prefix_cmdstr')) === '') {
  $ext_prefix = '11';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'all';
require_once $COMMON.'header.php';

putHtml("<center>");
putHtml("<p>&nbsp;</p>");
putHtml("</center>");
?>
  <center>
  <table class="layout"><tr><td><center>
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;">
  <h2>Speed Dial Directory:</h2>
  </td></tr>
  </table>
<?php
  $db = parseAstDB($familyname);

  // Sort by Name
  if (($n = count($db['data'])) > 0) {
    foreach ($db['data'] as $key => $row) {
      $name[$key] = $row['value'];
    }
    array_multisort($name, SORT_ASC, SORT_STRING, $db['data']);
  }

  putHtml('<table class="datatable">');
  putHtml("<tr>");

  if (($n = count($db['data'])) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Name", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Extension", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td>', htmlspecialchars($db['data'][$i]['value']), '</td>';
      echo '<td>'.$ext_prefix.$db['data'][$i]['key'], '</td>';
    }
  } else {
    if ($db['status'] == 0) {
      echo '<td style="text-align: center;">No Database Entries for: ', $db['family'], "</td>";
      putHtml("</tr><tr>");
      echo '<td>Only entries in the Speed Dial Tab with a "Name" specified will be listed here.', '</td>';
    } else {
      echo '<td style="color: red;">', asteriskERROR($db['status']), '</td>';
    }
  }
  putHtml("</tr>");
  putHtml("</table>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once $COMMON.'footer.php';

?>
