<?php

// Copyright (C) 2008-2012 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// testmail.php for AstLinux
// 01-30-2012
//
// System location of gui.network.conf file
$NETCONFFILE = '/mnt/kd/rc.conf.d/gui.network.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

// Function: putACTIONresult
//
function putACTIONresult($result_str, $status) {
  global $myself;

  if ($status == 0) {
    $result = 100;
  } else {
    $result = 101;
  }
  if ($result == 100) {
    $result_str = 'Test Email has been successfully sent.';
  } elseif ($result_str === '') {
    $result_str = 'Error';
  }
  header('Location: '.$myself.'?result_str='.rawurlencode($result_str).'&result='.$result);
}

// Function: getACTIONresult
//
function getACTIONresult($result) {
  $str = 'No Action.';

  if (isset($_GET['result_str'])) {
    $str = rawurldecode($_GET['result_str']);
  }
  if ($result == 100) {
    $color = 'green';
  } else {
    $color = 'red';
  }
  return('<p style="color: '.$color.';">'.$str.'</p>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_send_email'])) {
    $to = tuqd($_POST['to_email']);
    $from = tuqd($_POST['from_email']);
    if ($to !== '') {
      // Sanitize to and from
      if (preg_match('/^[a-zA-Z0-9._@-]*$/', $to) && preg_match('/^[a-zA-Z0-9._@-]*$/', $from)) {
        $result = restartPROCESS('msmtp', 10, 99, 'init');
        if ($result == 10) {
          @exec('cd /root;/usr/sbin/testmail "'.$to.'" "'.$from.'" 2>&1', $result_array, $status);
          $result_str = '';
          foreach ($result_array as $value) {
            $result_str .= $value.' ';
          }
          putACTIONresult(trim($result_str), $status);
          exit;
        }
      } else {
        $result = 106;
      }
    } else {
      $result = 105;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  if (is_file($NETCONFFILE)) {
    $db = parseRCconf($NETCONFFILE);
    if (($to_email = getVARdef($db, 'SAFE_ASTERISK_NOTIFY')) === '') {
      $to_email = getVARdef($db, 'UPS_NOTIFY');
    }
    if (($from_email = getVARdef($db, 'SAFE_ASTERISK_NOTIFY_FROM')) === '') {
      $from_email = getVARdef($db, 'UPS_NOTIFY_FROM');
    }
    if ($from_email !== '') {
      if (($i = strpos($from_email, '<')) !== FALSE && ($j = strrpos($from_email, '>')) !== FALSE) {
        if (($len = $j - $i - 1) > 0) {
          $from_email = substr($from_email, $i + 1, $len);
        }
      }
    }
  } else {
    $to_email = '';
    $from_email = '';
  }

  putHtml('<center>');
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 0) {
      putHtml('<p style="color: green;">Action Successful.</p>');
    } elseif ($result == 99) {
      putHtml('<p style="color: red;">Action Failed.</p>');
    } elseif ($result == 100 || $result == 101) {
      putHtml(getACTIONresult($result));
    } elseif ($result == 105) {
      putHtml('<p style="color: red;">To Email Address is missing.</p>');
    } elseif ($result == 106) {
      putHtml('<p style="color: red;">Invalid Email Address.</p>');
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
  <h2>Test SMTP Mail Relay:</h2>
  </td></tr>
  </table>
<?php
  putHtml('<table class="stdtable">');
  putHtml('<tr><td class="dialogText" style="text-align: right;">');
  putHtml('To Email <i>(Required)</i>:<input type="text" size="42" maxlength="128" name="to_email" value="'.$to_email.'" />');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;">');
  putHtml('From Email <i>(Optional)</i>:<input type="text" size="42" maxlength="128" name="from_email" value="'.$from_email.'" />');
  putHtml('</td></tr>');

  putHtml('<tr><td class="dialogText" style="text-align: center;">');
  putHtml('<br />');
  putHtml('<input type="submit" value="Send Test Email" name="submit_send_email" />');
  putHtml('</td></tr>');
  putHtml('</table>');

  putHtml("</form>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
