<?php

// Copyright (C) 2008-2012 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// sysdial.php for AstLinux
// 03-24-2008
// 04-12-2008, Added Extension Prefix
// 02-22-2012, Added 00-999 format
//
// -- extensions.conf snippet --
// exten => _11[01234]X,1,Macro(dial-sysdial,${EXTEN:2:2}) ; DB: sysdial/00-49
//
// [macro-dial-sysdial]
// exten => s,1,Answer
// exten => s,n,GotoIf($[${DB_EXISTS(sysdial/${ARG1})} = 0]?100)
// exten => s,n,Set(CALLERID(num)=${HOME_CIDNUM})
// exten => s,n,Set(CALLERID(name)=${HOME_CIDNAME})
// exten => s,n,DIAL(${DB_RESULT},${DIALOUTTIME})
// exten => s,n,Hangup
//
// exten => s,100,Playback(extras/num-not-in-db)
// exten => s,n,Hangup
// -- end of snippet --

$family = "sysdial";
$familyname = "sysdialname";
$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

if (($ext_prefix = getPREFdef($global_prefs, 'sysdial_ext_prefix_cmdstr')) === '') {
  $ext_prefix = '11';
}
if (($ext_digits = getPREFdef($global_prefs, 'sysdial_ext_digits_cmdstr')) === '') {
  $ext_digits = '50';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_staff) {
    $result = 999;
  } elseif (isset($_POST['submit_add'])) {
    $speeddial = tuqd($_POST['speeddial']);
    $speeddialname = tuqd($_POST['speeddialname']);
    $ext_1x00 = (isset($_POST['ext_1x00'])) ? $_POST['ext_1x00'] : '';
    $ext_11x0 = $_POST['ext_11x0'];
    $ext_110x = $_POST['ext_110x'];
    $value = $ext_1x00.$ext_11x0.$ext_110x;
    if (strlen($speeddial) > 0) {
      if (putAstDB($family, $value, $speeddial) == 0) {
        $result = 0;
        if (strlen($speeddialname) > 0) {
          putAstDB($familyname, $value, $speeddialname);
        } else {
          delAstDB($familyname, $value);
        }
      } else {
        $result = 4;
      }
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    for ($i = 0; $i < count($delete); $i++) {
      if (delAstDB($family, $delete[$i]) == 0) {
        $result = 0;
        delAstDB($familyname, $delete[$i]);
      } else {
        $result = 4;
      }
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'staff';
require_once '../common/header.php';

  $db = parseAstDB($family);
  $dbname = parseAstDB($familyname);

  // Sort by Number
  if (($n = count($db['data'])) > 0) {
    foreach ($db['data'] as $key => $row) {
      $number[$key] = '1'.$row['key'];  // Use leading '1' to not ignore leading 0's
    }
    array_multisort($number, SORT_ASC, SORT_NUMERIC, $db['data']);
  }

  if (($n = count($db['data'])) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $key = $db['data'][$i]['key'];
      $name = '';
      if (($m = count($dbname['data'])) > 0) {
        for ($j = 0; $j < $m; $j++) {
          if ($dbname['data'][$j]['key'] === $key) {
            $name = $dbname['data'][$j]['value'];
            break;
          }
        }
      }
      $db['data'][$i]['name'] = $name;
    }
  }

  $ldb['key'] = 0;
  $RESULT_NUMBER = '';
  if (isset($_GET['key'])) {
    $key = $_GET['key'];
    if (($n = count($db['data'])) > 0) {
      for ($i = 0; $i < $n; $i++) {
        if ($key === $db['data'][$i]['key']) {
          $ldb = $db['data'][$i];
          break;
        }
      }
    }
  }
  if ($ldb['key'] >= $ext_digits) {
    $ldb['key'] = 0;
  }

require_once '../common/result.php';
?>
  <center>
  <table class="layout"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>">
<?php
  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr><td style="text-align: center;" colspan="3">');
  putHtml('<h2>Speed Dial Database Management:</h2>');
  putHtml('</td></tr><tr><td style="text-align: center;">');
  putHtml('<input type="submit" class="formbtn" value="Save Changes" name="submit_add" />');
  putHtml('</td><td class="dialogText" style="text-align: center;">');
  echo('Ext:&nbsp;'.$ext_prefix.'&nbsp;');
  if ($ext_digits > 100) {
    putHtml('<select name="ext_1x00">');
    putHtml('<option value="">&nbsp;</option>');
    $digits = ($ext_digits > 1000) ? 10 : ($ext_digits / 100);
    $key = (strlen($ldb['key']) >= 3) ? ($ldb['key'] / 100) % 10 : -1;
    for ($i = 0; $i < $digits; $i++) {
      $sel = ($i == $key) ? ' selected="selected"' : '';
      putHtml('<option value="'.$i.'"'.$sel.'>'.$i.'</option>');
    }
    putHtml('</select>');
  }
  putHtml('<select name="ext_11x0">');
  $digits = ($ext_digits > 100) ? 10 : ($ext_digits / 10);
  $key = ($ldb['key'] / 10) % 10;
  for ($i = 0; $i < $digits; $i++) {
    $sel = ($i == $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$i.'"'.$sel.'>'.$i.'</option>');
  }
  putHtml('</select>');
  putHtml('<select name="ext_110x">');
  $digits = 10;
  $key = $ldb['key'] % 10;
  for ($i = 0; $i < $digits; $i++) {
    $sel = ($i == $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$i.'"'.$sel.'>'.$i.'</option>');
  }
  putHtml('</select>');
  putHtml('</td><td style="text-align: center;">');
  putHtml('<input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />');
  putHtml('</td></tr>');
  putHtml('</table>');
  putHtml('<table class="stdtable">');
  putHtml('<tr><td class="dialogText" style="text-align: right;">');
  putHtml('&nbsp;Speed&nbsp;Dial:<input type="text" size="32" maxlength="127" name="speeddial" value="'.htmlspecialchars($ldb['value']).'" />');
  putHtml('</td><td class="dialogText" style="text-align: right;">');
  putHtml('&nbsp;Name:<input type="text" size="24" maxlength="64" name="speeddialname" value="'.htmlspecialchars($ldb['name']).'" />');
  putHtml('</td></tr>');
  putHtml('</table>');

  putHtml('<table width="100%" class="datatable">');
  putHtml("<tr>");

  if (($n = count($db['data'])) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Extension", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Speed Dial", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Name", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td><a href="'.$myself.'?key='.$db['data'][$i]['key'].'" class="actionText">'.$ext_prefix.$db['data'][$i]['key'].'</a>', '</td>';
      echo '<td>', htmlspecialchars($db['data'][$i]['value']), '</td>';
      echo '<td>', htmlspecialchars($db['data'][$i]['name']), '</td>';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="', $db['data'][$i]['key'], '" />', '</td>';
    }
  } else {
    if ($db['status'] == 0) {
      echo '<td style="text-align: center;">No Database Entries for: ', $db['family'], '</td>';
    } else {
      echo '<td style="text-align: center; color: red;">', asteriskERROR($db['status']), '</td>';
    }
  }
  putHtml("</tr>");
  putHtml("</table>");
  putHtml("</form>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
