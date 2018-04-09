<?php

// Copyright (C) 2008-2010 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// shutdown.php for AstLinux
// 12-12-2009
//
$STAFF_LOGFILE = '/mnt/kd/webgui-staff-activity.log';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_staff) {
    $result = 999;
  } elseif (isset($_POST['submit_shutdown'])) {
    $result = 99;
    if (isset($_POST['confirm_shutdown'])) {
      if ($global_user === 'staff') {
        $mesg = date('Y-m-d H:i:s');
        $mesg .= '  SHUTDOWN';
        $mesg .= '  Remote Address: '.$_SERVER['REMOTE_ADDR'];
        @file_put_contents($STAFF_LOGFILE, $mesg."\n", FILE_APPEND);
        chmod($STAFF_LOGFILE, 0600);
      }
      systemSHUTDOWN($myself, 10);
    } else {
      $result = 7;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'staff';
require_once '../common/header.php';

  putHtml('<center>');
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 0) {
      putHtml('<p style="color: green;">Action Successful.</p>');
    } elseif ($result == 7) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">System is Shutting Down, safe to unplug in <span id="count_down"><script language="JavaScript" type="text/javascript">document.write(count_down_secs);</script></span> seconds.</p>');
    } elseif ($result == 99) {
      putHtml('<p style="color: red;">Action Failed.</p>');
    } elseif ($result == 999) {
      putHtml('<p style="color: red;">Permission denied for user "'.$global_user.'".</p>');
    } else {
      putHtml('<p>&nbsp;</p>');
    }
  } else {
    putHtml('<p>&nbsp;</p>');
  }
  putHtml('</center>');
?>
  <center>
  <table class="layout"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;" colspan="3">
  <h2>System Shutdown/Halt:</h2>
  </td></tr>
  </table>
<?php
  putHtml('<table class="stdtable">');
  putHtml('<tr><td class="dialogText" style="color: red; text-align: center;">');
  putHtml('WARNING: "Shutdown" will stop this computer.<br />');
  putHtml('Disconnecting and reconnecting the power may be required to restart.<br />');
  putHtml('Make sure you have physical access to this computer before continuing.<br />');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText" style="color: red; text-align: center;">');
  putHtml('WARNUNG: "Shutdown" wird diesen Computer herunterfahren.<br />');
  putHtml('Um neu zu starten, ist es m&ouml;glicherweise erforderlich,<br />');
  putHtml('die Stromversorgung zu Trennen und Wiederherzustellen.<br />');
  putHtml('Stellen Sie sicher, da&szlig; Sie physikalischen Zugang zu diesem Computer haben,<br />');
  putHtml('bevor Sie fortfahren.<br />');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: center;">');
  putHtml('<input type="submit" value="Shutdown" name="submit_shutdown" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="shutdown" name="confirm_shutdown" />&nbsp;Confirm');
  putHtml('</td></tr>');
  putHtml('</table>');

  putHtml("</form>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
