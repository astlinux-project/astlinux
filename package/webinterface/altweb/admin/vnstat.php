<?php

// Copyright (C) 2021 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// vnstat.php for AstLinux
// 02-05-2021
//

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

// Function: display_section
//
function display_section($output, $label) {

  putHtml('<h2>'.$label.':</h2>');
  putHtml('<pre style="background: #F7F7F7; border: 1px solid #54545C;">');

  while (! feof($output)) {
    if (($line = fgets($output, 1024)) !== FALSE) {
      if (($line = rtrim($line)) === '#next#') {
        break;
      }
      putText(rtrim($line));
    }
  }
  putHtml("</pre>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_staff) {
    $result = 999;
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'staff';
require_once '../common/header.php';

if (is_executable('/usr/bin/vnstat')) {
  $cmd = '/usr/bin/vnstat -hg';
  $cmd .= '; echo "#next#"';
  $cmd .= '; /usr/bin/vnstat';
  $cmd .= '; echo "#next#"';
  $cmd .= '; /usr/bin/vnstat -m';
  $cmd .= '; echo "#next#"';
  $cmd .= '; /usr/bin/vnstat -t';
  $cmd .= '; echo "#next#"';
  $cmd .= '; /usr/bin/vnstat -d';
  $cmd .= '; echo "#next#"';
  $cmd .= '; /usr/bin/vnstat -y';
  $cmd .= '; echo "#next#"';
  $vnstat_output = @popen($cmd, 'r');
}

  putHtml('<center>');
  if (! is_file('/var/run/vnstat/vnstat.pid')) {
    putHtml('<p style="color: red;">The vnStat Daemon is not running.</p>');
  } else {
    putHtml('<p>&nbsp;</p>');
  }
  putHtml('</center>');
  putHtml('<center>');

  putHtml('<table class="status"><tr><td style="text-align: center;">');
  putHtml('<h2>View Network Statistics:</h2>');
  putHtml('</td></tr><tr><td>');

  if (isset($vnstat_output)) {
    if ($vnstat_output !== FALSE) {

      display_section($vnstat_output, "Hours Graph");

      display_section($vnstat_output, "All Monitored Summary");

      display_section($vnstat_output, "Months");

      display_section($vnstat_output, "Top Days");

      display_section($vnstat_output, "Days");

      display_section($vnstat_output, "Years");

      while (! feof($vnstat_output)) {
        fgets($vnstat_output, 1024);
      }
      pclose($vnstat_output);
    }
  } else {
    putHtml('<p style="color: red;">The vnStat package is not installed.</p>');
  }

  putHtml('</td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
