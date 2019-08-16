<?php

// Copyright (C) 2008-2014 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// actionlist.php for AstLinux
// 08-03-2009
// 06-26-2014, Added larger User Data field
//
// -- extensions.conf snippet --
//
//; Actionlist Menu: No Monitoring~Monitor
//[default]
//exten => 100,1,Macro(monitor-gui,${CALLERID(num)})
//exten => 100,n,Dial(SIP/100,25,t)
//exten => 100,n,Hangup
//
//[macro-monitor-gui]
//exten => s,1,GotoIf($[${DB_EXISTS(actionlist/${ARG1})} = 0]?end)
//exten => s,n,GotoIf($["${DB_RESULT}" = "1"]?mon)
//exten => s,n,Goto(end)
//
//exten => s,n(mon),Set(CALLFILENAME=Mon-${STRFTIME(${EPOCH},Europe/Berlin,%Y%m%d-%H%M%S)})
//exten => s,n,Monitor(wav49,${CALLFILENAME},mb) ; record only when bridged + mix it
//
//exten => s,n(end),MacroExit()
//
// -- end snippet --

$family = "actionlist";
$familycomment = "actionlistcomment";
$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$value = getPREFdef($global_prefs, 'actionlist_action_menu_cmdstr');
$ACTIONLISTMENU = explode('~', $value);

// Function: is_actiondata
//
function is_actiondata($val) {

  if (strlen($val) == 1) {
    for ($i = 0; $i <= 9; $i++) {
      if ($val === (string)$i) {
        return(FALSE);
      }
    }
  }
  return(TRUE);
}

// Function: actionlistaction
//
function actionlistaction($val) {
  global $ACTIONLISTMENU;

  if (is_actiondata($val)) {
    return($val);
  }

  $status = "Undefined";
  $i = 0;
  foreach ($ACTIONLISTMENU as $value) {
    if ($value !== '') {
      if ($value[0] !== '-') {
        if ($val == $i) {
          $status = $value;
          break;
        }
        $i++;
      }
    }
  }
  return($status);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_staff) {
    $result = 999;
  } elseif (isset($_POST['submit_add'])) {
    $actionkey = tuqd($_POST['actionkey']);
    if (($action = $_POST['action']) === '') {
      $action = tuqd($_POST['actiondata']);
    }
    $comment = tuqd($_POST['comment']);
    if (strlen($actionkey) > 0) {
      if (($cmd = getPREFdef($global_prefs, 'actionlist_format_cmdstr')) === '') {
        $cmd = '^[A-Za-z0-9-]{2,20}$';
      }
      if (preg_match("/$cmd/", $actionkey)) {
        if ($action !== '') {
          if (putAstDB($family, $actionkey, $action) == 0) {
            $result = 0;
            if (strlen($comment) > 0) {
              putAstDB($familycomment, $actionkey, $comment);
            } else {
              delAstDB($familycomment, $actionkey);
            }
          } else {
            $result = 4;
          }
        } else {
          $result = 99;
        }
      } else {
        $result = 2;
      }
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    for ($i = 0; $i < arrayCount($delete); $i++) {
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

  if (($n = arrayCount($db['data'])) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $key = $db['data'][$i]['key'];
      $comment = '';
      if (($m = arrayCount($dbcomment['data'])) > 0) {
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

  $ldb['key'] = '';
  if (isset($_GET['key'])) {
    $key = rawurldecode($_GET['key']);
    if (($n = arrayCount($db['data'])) > 0) {
      for ($i = 0; $i < $n; $i++) {
        if ($key === $db['data'][$i]['key']) {
          $ldb = $db['data'][$i];
          break;
        }
      }
    }
  }

  putHtml('<center>');
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 0) {
      putHtml('<p style="color: green;">Action Successful.</p>');
    } elseif ($result == 1) {
      putHtml('<p style="color: orange;">No Action.</p>');
    } elseif ($result == 2) {
      if (($cmd = htmlspecialchars(getPREFdef($global_prefs, 'actionlist_error_cmdstr'))) === '') {
        $cmd = 'Key must be alphanumeric, 2-20 characters';
      }
      putHtml('<p style="color: red;">'.$cmd.'</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error.</p>');
    } elseif ($result == 4 || $result == 1101 || $result == 1102) {
      putHtml('<p style="color: red;">'.asteriskERROR($result).'</p>');
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
<?php
  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr><td style="text-align: center;" colspan="2">');
  putHtml('<h2>Actionlist Database Management:</h2>');
  putHtml('</td></tr><tr><td style="text-align: center;">');
  putHtml('<input type="submit" class="formbtn" value="Save Changes" name="submit_add" />');
  putHtml('</td><td style="text-align: center;">');
  putHtml('<input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />');
  putHtml('</td></tr>');
  putHtml('</table><table class="stdtable">');
  putHtml('<tr><td class="dialogText" style="text-align: left;">');
  putHtml('Action&nbsp;Key:<input type="text" size="22" maxlength="64" name="actionkey" value="'.$ldb['key'].'" />');
  putHtml('</td><td class="dialogText" style="text-align: right;">');
  putHtml('&nbsp;Action:');
  putHtml('<select name="action">');
  putHtml('<option value="">User Data&nbsp;&nbsp;&nbsp;&gt;&gt;&gt;</option>');

  $i = 0;
  foreach ($ACTIONLISTMENU as $value) {
    if ($value !== '') {
      if ($value[0] !== '-') {
        $sel = (! is_actiondata($ldb['value']) && (string)$i === $ldb['value']) ? ' selected="selected"' : '';
        putHtml('<option value="'.$i.'"'.$sel.'>'.$value.'</option>');
        $i++;
        if ($i > 9) break;
      } else {
        $value = str_replace('-', '&mdash;', $value);
        putHtml('<option value="" disabled="disabled">'.$value.'</option>');
      }
    }
  }
  putHtml('</select>');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;" colspan="2">');
  $sel = is_actiondata($ldb['value']) ? htmlspecialchars($ldb['value']) : '';
  putHtml('User&nbsp;Data:<input type="text" size="70" maxlength="128" name="actiondata" value="'.$sel.'" />');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;" colspan="2">');
  putHtml('Comment<i>(optional)</i>:<input type="text" size="42" maxlength="42" name="comment" value="'.htmlspecialchars($ldb['comment']).'" />');
  putHtml('</td></tr>');
  putHtml('</table>');

  putHtml('<table width="100%" class="datatable">');
  putHtml("<tr>");

  if (($n = arrayCount($db['data'])) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Action Key", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Action", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Comment", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td><a href="'.$myself.'?key='.rawurlencode($db['data'][$i]['key']).'" class="actionText">'.$db['data'][$i]['key'].'</a>', '</td>';

      $value = actionlistaction($db['data'][$i]['value']);
      if (strlen($value) > 46) {
        $value = wordwrap(htmlspecialchars($value), 40, '<br />', TRUE);
      } else {
        $value = htmlspecialchars($value);
      }
      echo '<td>', $value, '</td>';

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
