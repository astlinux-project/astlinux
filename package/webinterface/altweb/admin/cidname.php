<?php

// Copyright (C) 2008 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// cidname.php for AstLinux
// 03-24-2008
//
// -- extensions.conf snippet --
// exten => s,100,GotoIf($[${DB_EXISTS(cidname/${CALLERID(num)})} = 0]?200)
// exten => s,n,Set(CALLERID(name)=${DB_RESULT:0:15})
//
// exten => s,200,NoOp(${CALLERID(num)})
// -- end snippet --

$family = "cidname";
$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

if (($cidmaxlen = getPREFdef($global_prefs, 'cidname_maxlen_cmdstr')) === '') {
  $cidmaxlen = '15';
}

// Function: getCommentURL
//
function getCommentURL($comment) {

  $str = '';
  if (! is_null($comment)) {
    if ($comment !== '') {
      $str = '&amp;comment='.rawurlencode($comment);
    }
  }
  return($str);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_staff) {
    $result = 999;
  } elseif (isset($_POST['submit_add'])) {
    $cidnum = tuqd($_POST['cidnum']);
    $cidname = tuqd($_POST['cidname']);
    if (strlen($cidname) > 0) {
      if (($cmd = getPREFdef($global_prefs, 'number_format_cmdstr')) === '') {
        $cmd = '^[2-9][0-9][0-9][2-9][0-9][0-9][0-9][0-9][0-9][0-9]$';
      }
      if (preg_match("/$cmd/", $cidnum)) {
        if (putAstDB($family, $cidnum, $cidname) == 0) {
          $result = 0;
        } else {
          $result = 4;
        }
      } else {
        $result = 2;
      }
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    for ($i = 0; $i < count($delete); $i++) {
      if (delAstDB($family, $delete[$i]) == 0) {
        $result = 0;
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

  $ldb['value'] = '';
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
  if (isset($_GET['name'])) {
    if ($ldb['value'] === '') {
      $ldb['value'] = rawurldecode($_GET['name']);
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
  putHtml('<h2>Caller*ID Database Management:</h2>');
  putHtml('</td></tr><tr><td width="200" style="text-align: center;">');
  putHtml('<input type="submit" class="formbtn" value="Save Changes" name="submit_add" />');
  putHtml('</td><td width="200" style="text-align: center;">');
  putHtml('<input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />');
  putHtml('</td></tr>');
  putHtml('</table><table class="stdtable">');

  putHtml('<tr><td class="dialogText" style="text-align: right;">');
  putHtml('CID&nbsp;Number:<input type="text" size="18" maxlength="24" name="cidnum" value="'.$ldb['key'].'" />');
  putHtml('</td><td class="dialogText" style="text-align: right;">');
  if ($ldb['key'] !== '') {
    putHtml('Blacklist:&nbsp;<a href="/admin/blacklist.php?num='.$ldb['key'].getCommentURL($ldb['value']).'" class="actionText">'.$ldb['key'].'</a>');
  } else {
    putHtml('&nbsp;');
  }
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;">');
  putHtml('&nbsp;CID&nbsp;Name:<input type="text" size="18" maxlength="'.$cidmaxlen.'" name="cidname" value="'.htmlspecialchars($ldb['value']).'" />');
  putHtml('</td><td class="dialogText" style="text-align: right;">');
  if ($ldb['key'] !== '') {
    putHtml('Whitelist:&nbsp;<a href="/admin/whitelist.php?num='.$ldb['key'].getCommentURL($ldb['value']).'" class="actionText">'.$ldb['key'].'</a>');
  } else {
    putHtml('&nbsp;');
  }
  putHtml('</td></tr>');
  putHtml('</table>');

  putHtml('<table width="100%" class="datatable">');
  putHtml("<tr>");

  if (($n = count($db['data'])) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "CID Number", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "CID Name", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td><a href="'.$myself.'?num='.$db['data'][$i]['key'].'" class="actionText">'.$db['data'][$i]['key'].'</a>', '</td>';
      echo '<td>', htmlspecialchars($db['data'][$i]['value']), '</td>';
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
