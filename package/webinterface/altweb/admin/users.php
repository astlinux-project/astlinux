<?php

// Copyright (C) 2008-2016 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// users.php for AstLinux
// 07-29-2008
// 09-05-2012, Support Prefs option to remove user VM data when mailbox is deleted
// 08-09-2014, Support |'s in email, longer email and sanitize input more strictly
//
// System location of the asterisk voicemail.conf
$VOICEMAILCONF = '/etc/asterisk/voicemail.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

require_once '../common/users-password.php';

if (($context = tuq(getPREFdef($global_prefs, 'users_voicemail_context_cmdstr'))) === '') {
  $context = 'default';
}

// Function: parseVMconf
//
function parseVMconf($context, $fname) {
  $id = 0;
  $db['context'] = $context;
  $tmpfile = tempnam("/tmp", "PHP_");
  @exec('sed -n "/^\['.$context.'\]/,/^\[/ s/^[0-9][0-9]*[ ]*[=][> ]*[-*0-9]*,/&/p" '.$fname.' >'.$tmpfile);
  $ph = @fopen($tmpfile, "r");
  while (! feof($ph)) {
    if (($line = trim(fgets($ph, 1024))) !== '') {
      $linetokens = explode(',', $line);
      $boxtokens = explode('=', $linetokens[0]);
      $db['data'][$id]['mbox'] = trim($boxtokens[0], ' ');
      $db['data'][$id]['pass'] = trim($boxtokens[1], '> ');
      $db['data'][$id]['name'] = $linetokens[1];
      $db['data'][$id]['email'] = $linetokens[2];
      $db['data'][$id]['pager'] = $linetokens[3];
      $db['data'][$id]['opts'] = $linetokens[4];
      $id++;
    }
  }
  fclose($ph);
  @unlink($tmpfile);

  // Sort by mailbox
  if ($id > 1) {
    foreach ($db['data'] as $key => $row) {
      $mbox[$key] = $row['mbox'];
    }
    array_multisort($mbox, SORT_ASC, SORT_NUMERIC, $db['data']);
  }

  return($db);
}

// Function: is_deletedVMmailbox
//
function is_deletedVMmailbox($context, $mbox, $fname) {
  $result = FALSE;

  if (($ph = popen('sed -n "/^\['.$context.'\]/,/^\[/ s/^;deleted;'.$mbox.'[ ]*[=][> ]*[-*0-9]*,.*/'.$mbox.'/p" '.$fname, "r")) !== FALSE) {
    if (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) === $mbox) {
        $result = TRUE;
      }
    }
    pclose($ph);
  }
  return($result);
}

// Function: isVMmailbox
//
function isVMmailbox($context, $mbox, $fname) {
  $result = FALSE;

  if (($ph = popen('sed -n "/^\['.$context.'\]/,/^\[/ s/^'.$mbox.'[ ]*[=][> ]*[-*0-9]*,.*/'.$mbox.'/p" '.$fname, "r")) !== FALSE) {
    if (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) === $mbox) {
        $result = TRUE;
      }
    }
    pclose($ph);
  }
  return($result);
}

// Function: addVMmailbox
//
function addVMmailbox($context, $mbox, $pass, $name, $email, $pager, $opts, $fname) {

  $cmd = $mbox.' => '.$pass.','.$name.','.$email.','.$pager.','.$opts;

  if (isVMmailbox($context, $mbox, $fname)) {
    shell('sed -i "/^\['.$context.'\]/,/^\[/ s/^'.$mbox.'[ ]*[=][> ]*[-*0-9]*,.*/'.$cmd.'/" '.$fname.' >/dev/null', $status);
  } elseif (is_deletedVMmailbox($context, $mbox, $fname)) {
    shell('sed -i "/^\['.$context.'\]/,/^\[/ s/^;deleted;'.$mbox.'[ ]*[=][> ]*[-*0-9]*,.*/'.$cmd.'/" '.$fname.' >/dev/null', $status);
  } else {
    shell('sed -i "/^\['.$context.'\]/ a'.chr(92).chr(10).$cmd.chr(10).'" '.$fname.' >/dev/null', $status);
  }
  if (isVMmailbox($context, $mbox, $fname)) {
    $password = str_replace('-', '', $pass);
    genHTpasswd($mbox, $password, $password, 0);
  } else {
    $status = 1;
  }
  return($status);
}

// Function: delVMmailbox
//
function delVMmailbox($context, $mbox, $fname) {
  global $global_prefs;

  shell('sed -i "/^\['.$context.'\]/,/^\[/ s/^'.$mbox.'[ ]*[=][> ]*[-*0-9]*,.*/;deleted;&/" '.$fname.' >/dev/null', $status);

  if (isVMmailbox($context, $mbox, $fname)) {
    $status = 1;
  } else {
    delHTpasswd($mbox);
    # Optionally remove the local VM data for mbox
    if (getPREFdef($global_prefs, 'users_voicemail_delete_vmdata') === 'yes') {
      if ($context !== '' && $mbox !== '') {  // Sanity check
        if (is_dir($VMdata = '/var/spool/asterisk/voicemail/'.$context.'/'.$mbox)) {
          shell("rm -rf '".$VMdata."/'", $ret_val);
        }
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
    $mailbox = tuq($_POST['mailbox']);
    $password = tuq($_POST['password']);
    if (preg_match('/^[0-9][0-9]*$/', $mailbox)) {
      if (preg_match('/^[-*0-9][*0-9]*$/', $password)) {
        $name = tuq($_POST['name']);
        $name = preg_replace('/[,]+/', '', $name);
        $email = trim(tuq($_POST['email']), '|, ');
        $email = preg_replace('/[|, ]+/', '|', $email);
        $pager = tuq($_POST['pager']);
        $pager = preg_replace('/[|, ]+/', '', $pager);
        $options = trim(tuq($_POST['options']), '|, ');
        $options = preg_replace('/[|,]+/', '|', $options);
        if (addVMmailbox($context, $mailbox, $password, $name, $email, $pager, $options, $VOICEMAILCONF) == 0) {
          $result = 10;
        } else {
          $result = 99;
        }
      } else {
        $result = 3;
      }
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_reload'])) {
    $result = 99;
    if (isset($_POST['confirm_reload'])) {
      if (($cmd = getPREFdef($global_prefs, 'users_voicemail_reload_cmdstr')) === '') {
        $cmd = 'module reload app_voicemail.so';
      }
      $status = asteriskCMD($cmd, '');
      if ($status == 0) {
        $result = 11;
      } elseif ($status == 1101) {
        $result = 1101;
      } elseif ($status == 1102) {
        $result = 1102;
      } else {
        $result = 4;
      }
    } else {
      $result = 7;
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    for ($i = 0; $i < count($delete); $i++) {
      if (delVMmailbox($context, $delete[$i], $VOICEMAILCONF) == 0) {
        $result = 10;
      } else {
        $result = 99;
      }
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
    } elseif ($result == 1) {
      putHtml('<p style="color: orange;">No Action.</p>');
    } elseif ($result == 2) {
      putHtml('<p style="color: red;">Mailbox must contain digits [0-9].</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Password must contain digits [0-9] or star [*].</p>');
    } elseif ($result == 4 || $result == 1101 || $result == 1102) {
      putHtml('<p style="color: red;">'.asteriskERROR($result).'</p>');
    } elseif ($result == 6) {
      putHtml('<p style="color: red;">Unable to calculate web root directory.</p>');
    } elseif ($result == 7) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">Changes saved, click "Reload Voicemail" to apply any changes.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Voicemail Module Reloaded.</p>');
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
  <h2>Voicemail Users Management:</h2>
  </td></tr><tr><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Changes" name="submit_add" />
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Reload Voicemail" name="submit_reload" />
  &ndash;
  <input type="checkbox" value="reload" name="confirm_reload" />&nbsp;Confirm
  </td><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />
  </td></tr>
  </table>
<?php
  $db = parseVMconf($context, $VOICEMAILCONF);

  if (isset($_GET['mbox'])) {
    $mbox = $_GET['mbox'];
    if (($n = count($db['data'])) > 0) {
      for ($i = 0; $i < $n; $i++) {
        if ($mbox === $db['data'][$i]['mbox']) {
          $ldb = $db['data'][$i];
          break;
        }
      }
    }
  }
  putHtml('<table class="stdtable">');
  putHtml('<tr><td class="dialogText" style="text-align: right;">');
  putHtml('Mailbox:<input type="text" size="8" maxlength="24" name="mailbox" value="'.$ldb['mbox'].'" />');
  putHtml('</td><td class="dialogText" style="text-align: right;">');
  putHtml('Password:<input type="text" size="8" maxlength="24" name="password" value="'.$ldb['pass'].'" />');
  putHtml('</td><td class="dialogText" style="text-align: right;">');
  putHtml('Name:<input type="text" size="32" maxlength="64" name="name" value="'.$ldb['name'].'" />');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;" colspan="3">');
  putHtml('Email(s):<input type="text" size="80" maxlength="256" name="email" value="'.$ldb['email'].'" />');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;" colspan="3">');
  putHtml('Pager:<input type="text" size="80" maxlength="128" name="pager" value="'.$ldb['pager'].'" />');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;" colspan="3">');
  putHtml('Options:<input type="text" size="80" maxlength="128" name="options" value="'.$ldb['opts'].'" />');
  putHtml('</td></tr>');
  putHtml('</table>');

  $hidePASS = (getPREFdef($global_prefs, 'users_voicemail_hide_pass') === 'yes');

  putHtml('<table width="100%" class="datatable">');
  putHtml("<tr>");

  if (($n = count($db['data'])) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Mailbox", "</td>";
    if (! $hidePASS) {
      echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Password", "</td>";
    }
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Name", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Email(s)", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Pager", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td>', '<a href="'.$myself.'?mbox='.$db['data'][$i]['mbox'].'" class="actionText">'.$db['data'][$i]['mbox'].'</a>', '</td>';
      if (! $hidePASS) {
        echo '<td>', $db['data'][$i]['pass'], '</td>';
      }
      echo '<td>', $db['data'][$i]['name'], '</td>';
      echo '<td>', str_replace('|', '<br />', $db['data'][$i]['email']), '</td>';
      echo '<td>', $db['data'][$i]['pager'], '</td>';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="', $db['data'][$i]['mbox'], '" />', '</td>';
      if (isset($db['data'][$i]['opts']) && $db['data'][$i]['opts'] !== '') {
        putHtml("</tr>");
        echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
        echo '<td class="dialogText" style="font-weight: bold;">Options:</td>';
        echo '<td colspan="5">', $db['data'][$i]['opts'], '</td>';
      }
    }
  } else {
    echo '<td style="text-align: center;">No Voicemail Mailboxes for Context: ', $db['context'], '</td>';
  }
  putHtml("</tr>");
  putHtml("</table>");
  putHtml("</form>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
