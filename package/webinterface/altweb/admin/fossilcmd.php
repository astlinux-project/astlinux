<?php

// Copyright (C) 2008-2016 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// fossilcmd.php for AstLinux
// 08-23-2015
//

require_once '../common/functions.php';

$action_menu = array (
  'status' => 'fossil-status',
  'diff' => 'fossil-diff',
  'commit' => 'fossil-commit',
  'revert' => 'fossil-revert'
);

// Function: multi_args
//
function multi_args($args) {
  $str = '';

  if ($args == '') {
    return($str);
  }

  $strtokens = explode(' ', $args);
  foreach ($strtokens as $value) {
    if ($value !== '') {
      $str .= ' "'.$value.'"';
    }
  }
  return($str);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_action'])) {
    $result = 10;
    $action = $_POST['fossil_action'];
    $arg = isset($_POST['fossil_arg']) ? tuq($_POST['fossil_arg']) : '';
    header('Location: '.$myself.'?action='.$action.'&arg='.rawurlencode($arg).'&result='.$result);
    exit;
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  $action = isset($_GET['action']) ? $_GET['action'] : '';
  $arg = isset($_GET['arg']) ? tuq(rawurldecode($_GET['arg'])) : '';

  if ($action === 'status') {
    $arg_str = '';
  } elseif ($action === 'diff') {
    $arg_str = multi_args($arg);
  } elseif ($action === 'commit') {
    $arg_str = ($arg !== '') ? '"'.$arg.'"' : '"commit via web interface: Fossil Commands"';
  } elseif ($action === 'revert') {
    $arg_str = multi_args($arg);
  } else {
    $action = '';
    $arg_str = '';
  }

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 10 && $action !== '') {
      putHtml('<p style="color: green;">Fossil Command: fossil-'.$action.' '.htmlspecialchars($arg_str).'</p>');
    } elseif ($result == 99) {
      putHtml('<p style="color: red;">Action Failed.</p>');
    } elseif ($result == 999) {
      putHtml('<p style="color: red;">Permission denied for user "'.$global_user.'".</p>');
    } else {
      putHtml('<p style="color: orange;">No Action.</p>');
    }
  } else {
    putHtml('<p>&nbsp;</p>');
  }
  putHtml("</center>");
?>
  <center>
  <table width="100%" class="layout"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>" enctype="multipart/form-data">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;" colspan="2">
<?php
  putHtml('<h2>Fossil Commands:'.includeTOPICinfo('Fossil-Commands').'</h2>');
  putHtml('</td></tr>');

if (is_file('/var/run/fossil.pid')) {
  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<select name="fossil_action">');
  foreach ($action_menu as $key => $value) {
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('<input type="text" size="48" maxlength="256" name="fossil_arg" value="" />');
  putHtml('&ndash;');
  putHtml('<input type="submit" value="Fossil Command" name="submit_action" />');
  putHtml('</td></tr>');
} else {
  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<p style="color: red;">The Fossil Server is not running, enable via the Network Tab.</p>');
  putHtml('</td></tr>');
}
  putHtml('</table>');
  putHtml('</form>');

  putHtml("</center></td></tr></table>");
  putHtml("</center>");

if (is_file('/var/run/fossil.pid')) {
  if ($action !== '') {
    putHtml("<pre>");
    $tmpfile = tempnam("/tmp", "PHP_");
    @exec('cd /root;fossil-'.$action.' '.$arg_str.' >'.$tmpfile.' 2>&1');
    if (($fp = @fopen($tmpfile, "rb")) !== FALSE) {
      $max = 250000;
      $stat = fstat($fp);
      if ($stat['size'] > $max) {
        @fseek($fp, -$max, SEEK_END);
        fgets($fp, 1024);
        echo "<strong>----- Result too large to display, showing the end of the output -----</strong>\n";
      }
      while (! feof($fp)) {
        if (($line = fgets($fp, 1024)) != '') {
          echo htmlspecialchars($line);
        }
      }
      fclose($fp);
    }
    @unlink($tmpfile);
    putHtml("</pre>");
  }
}

} // End of HTTP GET
require_once '../common/footer.php';

?>
