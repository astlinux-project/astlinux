<?php

// Copyright (C) 2008 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// blacklist.php for AstLinux
// 03-24-2008
//
// -- extensions.conf snippet --
// exten => s,100,GotoIf($[${DB_EXISTS(blacklist/${CALLERID(num)})} = 0]?200) ; blacklist test
// exten => s,n,GotoIf($["${DB_RESULT}" = "0"]?110)
// exten => s,n,GotoIf($["${DB_RESULT}" = "2"]?120)
// exten => s,n,Goto(blacklist,s,1)        ; "1" TN in blacklist database, answer and Zapateller
//
// exten => s,110,Goto(blacklist,no-answer,1) ; "0" TN in blacklist, don't answer
//
// exten => s,120,Goto(voicemail-ivr,s,1)  ; "2" TN in blacklist, direct to voicemail
//
// exten => s,200,NoOp(Valid TN:${CALLERID(num)})
// -- end snippet --

$family = "blacklist";
$familycomment = "blacklistcomment";
$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

if (($value = getPREFdef($global_prefs, 'blacklist_action_menu_cmdstr')) === '') {
  $value = 'No Answer~Zapateller~Voicemail';
}
$BLACKLISTMENU = explode('~', $value);

// Function: blacklistaction
//
function blacklistaction($val) {
  global $BLACKLISTMENU;

  $status = "Undefined";
  $i = 0;
  foreach ($BLACKLISTMENU as $value) {
    if ($value !== '') {
      if ($val == $i) {
        $status = $value;
        break;
      }
      $i++;
    }
  }
  return($status);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_staff) {
    $result = 999;
  } elseif (isset($_POST['submit_add'])) {
    $cidnum = tuqd($_POST['cidnum']);
    $action = $_POST['action'];
    $comment = tuqd($_POST['comment']);
    if (strlen($cidnum) > 0) {
      if (($cmd = getPREFdef($global_prefs, 'number_format_cmdstr')) === '') {
        $cmd = '^[2-9][0-9][0-9][2-9][0-9][0-9][0-9][0-9][0-9][0-9]$';
      }
      if (preg_match("/$cmd/", $cidnum)) {
        if (putAstDB($family, $cidnum, $action) == 0) {
          $result = 0;
          if (strlen($comment) > 0) {
            putAstDB($familycomment, $cidnum, $comment);
          } else {
            delAstDB($familycomment, $cidnum);
          }
        } else {
          $result = 4;
        }
      } else {
        $result = 2;
      }
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    for ($i = 0; $i < count($delete); $i++) {
      if (delAstDB($family, $delete[$i]) == 0) {
        $result = 0;
        delAstDB($familycomment, $delete[$i]);
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
  $dbcomment = parseAstDB($familycomment);

  if (($n = count($db['data'])) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $key = $db['data'][$i]['key'];
      $comment = '';
      if (($m = count($dbcomment['data'])) > 0) {
        for ($j = 0; $j < $m; $j++) {
          if ($dbcomment['data'][$j]['key'] === $key) {
            $comment = $dbcomment['data'][$j]['value'];
            break;
          }
        }
      }
      $db['data'][$i]['comment'] = $comment;
    }
  }

  $ldb['comment'] = '';
  $ldb['key'] = '';
  $RESULT_NUMBER = '';
  if (isset($_GET['num'])) {
    $key = $_GET['num'];
    $ldb['key'] = $key;
    $RESULT_NUMBER = $key;
    if (($n = count($db['data'])) > 0) {
      for ($i = 0; $i < $n; $i++) {
        if ($key === $db['data'][$i]['key']) {
          $ldb = $db['data'][$i];
          $RESULT_NUMBER = '';
          break;
        }
      }
    }
  }
  if (isset($_GET['comment'])) {
    if ($ldb['comment'] === '') {
      $ldb['comment'] = rawurldecode($_GET['comment']);
    }
  }

require_once '../common/result.php';
?>
  <center>
  <table class="layout"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>">
<?php
  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<h2>Blacklist Database Management:</h2>');
  putHtml('</td></tr><tr><td style="text-align: center;">');
  putHtml('<input type="submit" class="formbtn" value="Save Changes" name="submit_add" />');
  putHtml('</td><td style="text-align: center;">');
  putHtml('<input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />');
  putHtml('</td></tr>');
  putHtml('</table><table class="stdtable">');
  putHtml('<tr><td class="dialogText" style="text-align: right;">');
  putHtml('Blacklist&nbsp;Number:<input type="text" size="18" maxlength="24" name="cidnum" value="'.$ldb['key'].'" />');
  putHtml('</td><td class="dialogText" style="text-align: right;">');
  putHtml('&nbsp;Action:');
  putHtml('<select name="action">');

  $i = 0;
  foreach ($BLACKLISTMENU as $value) {
    if ($value !== '') {
      $sel = ((string)$i === $ldb['value']) ? ' selected="selected"' : '';
      putHtml('<option value="'.$i.'"'.$sel.'>'.$value.'</option>');
      $i++;
    }
  }
  putHtml('</select>');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;" colspan="2">');
  putHtml('Comment<i>(optional)</i>:<input type="text" size="40" maxlength="40" name="comment" value="'.htmlspecialchars($ldb['comment']).'" />');
  putHtml('</td></tr>');
  putHtml('</table>');

  putHtml('<table width="100%" class="datatable">');
  putHtml("<tr>");

  if (($n = count($db['data'])) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Blacklist", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Action", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Comment", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td><a href="'.$myself.'?num='.$db['data'][$i]['key'].'" class="actionText">'.$db['data'][$i]['key'].'</a>', '</td>';
      echo '<td>', blacklistaction($db['data'][$i]['value']), '</td>';
      echo '<td>', htmlspecialchars($db['data'][$i]['comment']), '</td>';
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
