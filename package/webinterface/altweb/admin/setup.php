<?php

// Copyright (C) 2008-2016 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// setup.php for AstLinux
// 01-01-2009
//
// Script name to call
$INITIAL_SETUP = '/usr/sbin/initial-setup';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

// Function: putACTIONresult
//
function putACTIONresult($result_str, $status) {
  global $myself;

  if ($status == 0) {
    $result = 100;
  } elseif ($status == 2) {
    $result = 102;
  } else {
    $result = 101;
  }
  if ($result_str === '') {
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
  } elseif ($result == 102) {
    $color = 'orange';
  } else {
    $color = 'red';
  }
  return('<p style="color: '.$color.';">'.$str.'</p>');
}

// Function: cancelSETUP
//
function cancelSETUP() {
  global $global_prefs;

  $status = (getPREFdef($global_prefs, 'status_require_auth') === 'yes') ? '/admin/status.php' : '/status.php';

  header('Location: '.$status);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_cancel'])) {
    cancelSETUP();
  } elseif (isset($_POST['submit_format'])) {
    if (isset($_POST['format_type']) && isset($_POST['unionfs_size']) && isset($_POST['target_drive'])) {
      $format_type = $_POST['format_type'];
      $target_drive = tuq($_POST['target_drive']);
      if ($format_type === 'combined') {
        $result_str = shell($INITIAL_SETUP.' format combined '.$target_drive.' 2>/dev/null', $status);
        if ($status != 0) {
          putACTIONresult($result_str, $status);
          exit;
        } else {
          systemREBOOT($myself, 10, TRUE);
        }
      } else {
        $unionfs_size = tuq($_POST['unionfs_size']);
        if ($unionfs_size > 9) {
          $result_str = shell($INITIAL_SETUP.' format separate '.$target_drive.' '.$unionfs_size.' 2>/dev/null', $status);
          if ($status != 0) {
            putACTIONresult($result_str, $status);
            exit;
          } else {
            systemREBOOT($myself, 10, TRUE);
          }
        } else {
          $result = 9;
        }
      }
    }
  } elseif (isset($_POST['submit_configure'])) {
    if (isset($_POST['target_drive'])) {
      $target_drive = tuq($_POST['target_drive']);
      $result_str = shell($INITIAL_SETUP.' configure '.$target_drive.' 2>/dev/null', $status);
      if ($status != 0) {
        putACTIONresult($result_str, $status);
        exit;
      } else {
        systemREBOOT($myself, 10);
      }
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 9) {
      putHtml('<p style="color: red;">Unionfs Partition Size must be 10 MBytes or larger.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">System is Rebooting... back in <span id="count_down"><script language="JavaScript" type="text/javascript">document.write(count_down_secs);</script></span> seconds.</p>');
    } elseif ($result == 99) {
      putHtml('<p style="color: red;">Action Failed.</p>');
    } elseif ($result == 100 || $result == 101 || $result == 102) {
      putHtml(getACTIONresult($result));
    } elseif ($result == 999) {
      putHtml('<p style="color: red;">Permission denied for user "'.$global_user.'".</p>');
    } else {
      putHtml('<p style="color: orange;">No Action.</p>');
    }
  } else {
    putHtml("<p>&nbsp;</p>");
  }
  putHtml("</center>");
?>
  <center>
  <table class="layout"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;">
  <h2>AstLinux Installation Setup:</h2>
  </td></tr></table>
  <table class="stdtable">
  <tr class="dtrow0"><td width="50">&nbsp;</td><td width="90">&nbsp;</td><td width="90">&nbsp;</td><td>&nbsp;</td><td width="90">&nbsp;</td><td width="90">&nbsp;</td></tr>
<?php

  if (is_file($INITIAL_SETUP)) {
    $action = trim(shell_exec($INITIAL_SETUP.' status 2>/dev/null'));
  } else {
    $action = 'Your AstLinux version does not support this Setup Tab.';
  }
  $end_with_continue = FALSE;

  if (strncmp($action, 'ok-format', 9) == 0) {
    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
    putHtml('<strong>Step 1 - Format Data Partitions on Drive:</strong>');
    $drives = explode(' ', trim(substr($action, 9)));
    putHtml('<select name="target_drive">');
    foreach ($drives as $value) {
      putHtml('<option value="'.$value.'">'.$value.'</option>');
    }
    putHtml('</select>');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('&nbsp;<input type="radio" value="combined" name="format_type" checked="checked" />');
    putHtml('Combined Unionfs and /mnt/kd/ partition');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">&nbsp;</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">');
    putHtml('&nbsp;<input type="radio" value="separate" name="format_type" />');
    putHtml('Separate Unionfs and /mnt/kd/ partitions');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: right;">&nbsp;');
    putHtml('</td><td style="text-align: left;" colspan="5">');
    putHtml('Unionfs Partition Size:');
    putHtml('<input type="text" size="5" maxlength="4" value="256" name="unionfs_size" />');
    putHtml('MBytes');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">&nbsp;</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: center;" colspan="6">');
    putHtml('<input type="submit" class="formbtn" value="Continue" name="submit_format" /></td></tr>');
  } elseif (strncmp($action, 'ok-configure', 12) == 0) {
    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
    putHtml('<strong>Step 2 - Configure Data Partitions on Drive:</strong>');
    $drives = explode(' ', trim(substr($action, 12)));
    putHtml('<select name="target_drive">');
    foreach ($drives as $value) {
      putHtml('<option value="'.$value.'">'.$value.'</option>');
    }
    putHtml('</select>');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: left; color: green;" colspan="6">');
    putHtml('&nbsp;Format was successful.<br /><br />');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: left; color: orange;" colspan="6">');
    putHtml('&nbsp;Please wait until the System Reboots, then<br />');
    putHtml('&nbsp;click "Finish" to configure the partitions<br />');
    putHtml('&nbsp;and then wait for the System Reboot once again.');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: center;" colspan="6">');
    putHtml('<input type="submit" class="formbtn" value="Finish" name="submit_configure" /></td></tr>');
  } elseif (strncmp($action, 'ok-unionfs', 10) == 0) {
    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
    putHtml('<strong>Existing Runnix Partition on Drive:</strong>');
    $drives = explode(' ', trim(substr($action, 10)));
    putHtml('<select name="target_drive">');
    foreach ($drives as $value) {
      putHtml('<option value="'.$value.'">'.$value.'</option>');
    }
    putHtml('</select>');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: left; color: green;" colspan="6">');
    putHtml('&nbsp;This AstLinux System has been successfully setup.<br />&nbsp;</td></tr>');
    $end_with_continue = TRUE;
  } else {
    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
    putHtml('<strong>Setup Error:</strong>');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: left; color: red;" colspan="6">');
    putHtml('&nbsp;'.$action);
    putHtml('</td></tr>');
  }

  if ($end_with_continue) {
    putHtml('<tr class="dtrow1"><td style="text-align: left;" colspan="6">&nbsp;</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: center;" colspan="6">');
    putHtml('<input type="submit" class="formbtn" value="Continue" name="submit_cancel" /></td></tr>');
  } else {
    putHtml('<tr class="dtrow0"><td style="text-align: left;" colspan="6">&nbsp;</td></tr>');
    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="6">');
    putHtml('<strong>Cancel Installation Setup:</strong>');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow1"><td style="text-align: center;" colspan="6">');
    putHtml('<input type="submit" class="formbtn" value="Cancel" name="submit_cancel" /></td></tr>');
  }

  putHtml("</table>");
  putHtml('</form>');
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
