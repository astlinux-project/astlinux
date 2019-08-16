<?php

// Copyright (C) 2008-2017 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// followme.php for AstLinux
// 12-05-2008
// 04-01-2017, Add enable_callee_prompt support
//
// -- extensions.conf snippet --
// [macro-local-followme]
// exten => s,1,GotoIf($[${DB_EXISTS(followme/${ARG1})}=0]?nofollow)
// exten => s,n,GotoIf($[${DB_RESULT:0:1}=0]?nofollow:follow)
// exten => s,n(follow),Dial(SIP/${ARG1},20)
// exten => s,n,Followme(${ARG1},san)
// exten => s,n,Goto(s-${DIALSTATUS},1)
// exten => s,n(nofollow),Dial(SIP/${ARG1},20)
// exten => s,n,Goto(s-${DIALSTATUS},1)
// exten => s-NOANSWER,1,Voicemail(${ARG1},u)  ; If unavailable, send to voicemail
// exten => s-BUSY,1,Voicemail(${ARG1},b)  ; If busy, send to voicemail w/ busy ann
// exten => _s-.,1,Goto(s-NOANSWER,1)
// -- end of snippet --
//
// System location of the asterisk followme.conf
$FOLLOWMECONF = '/etc/asterisk/followme.conf';
// Asterisk 1.4 command to reload Follow-Me Application
$RELOAD_FOLLOWME_CMD = 'module reload app_followme.so';

$family = "followme";
$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$MAXNUM = (int)getPREFdef($global_prefs, 'followme_numbers_displayed');
if ($MAXNUM <= 0 || $MAXNUM > 6) {
  $MAXNUM = 4;
}

$USE_RULES = (getPREFdef($global_prefs, 'followme_use_number_format') !== 'no');

if (($NUMBER_FORMAT = getPREFdef($global_prefs, 'number_format_cmdstr')) === '') {
  $NUMBER_FORMAT = '^[2-9][0-9][0-9][2-9][0-9][0-9][0-9][0-9][0-9][0-9]$';
}

// Function: fmGetDataValue
//
function fmGetDataValue($dv) {
  global $MAXNUM;

  $str = '';
  for ($i = 0; $i < $MAXNUM; $i++) {
    if ($dv['enabled'][$i] === '1') {
      $str .= $dv['number'][$i].', ';
    }
  }
  $str = rtrim($str, ', ');
  if ($str === '') {
    $str = '** Not Active **';
  }
  return($str);
}

// Function: fmDBtoDATA
//
function fmDBtoDATA($db, $key) {
  global $MAXNUM;

  $data = NULL;

  if (($n = arrayCount($db['data'])) > 0) {
    for ($i = 0; $i < $n; $i++) {
      if ($key !== FALSE) {
        if ($key === $db['data'][$i]['key']) {
          $d = 0;
        } else {
          continue;
        }
      } else {
        $d = $i;
      }

      $data[$d]['key'] = $db['data'][$i]['key'];
      $tokens = explode('~', $db['data'][$i]['value']);
      if (isset($tokens[0]) && $tokens[0] !== '') {
        $field = explode(':', $tokens[0]);
        $data[$d]['method'] = $field[1];
        $data[$d]['time_class'] = $field[2];
      } else {
        $data[$d]['method'] = '0';
        $data[$d]['time_class'] = '0';
      }
      for ($j = 0; $j < $MAXNUM; $j++) {
        if (isset($tokens[$j+1]) && $tokens[$j+1] !== '') {
          $field = explode(':', $tokens[$j+1]);
          $data[$d]['enabled'][$j] = $field[0];
          $data[$d]['number'][$j] = $field[1];
          $data[$d]['timeout'][$j] = $field[2];
        } else {
          $data[$d]['enabled'][$j] = '0';
          $data[$d]['number'][$j] = '';
          $data[$d]['timeout'][$j] = '45';
        }
      }
    }
  }
  return($data);
}

// Function: isFMextension
//
function isFMextension($key, $fname) {
  $result = FALSE;

  if (($ph = popen('sed -n "/^\['.$key.'\]/ s/^\['.$key.'.*/'.$key.'/p" '.$fname, "r")) !== FALSE) {
    if (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) === $key) {
        $result = TRUE;
      }
    }
    pclose($ph);
  }
  return($result);
}

// Function: delFMextension
//
function delFMextension($family, $key, $fname) {

  if (($err = delAstDB($family, $key)) != 0) {
    return($err);
  }
  if (! is_file($fname)) {
    return(0);
  }
  if (isFMextension($key, $fname)) {
    shell('sed -i "/^\['.$key.'\]/,/^\[/ s/^[a-zA-Z].*//" '.$fname.' >/dev/null', $status);
    shell('sed -i "/^\['.$key.'\]/ d" '.$fname.' >/dev/null', $status);
    shell('sed -i "/^$/ d" '.$fname.' >/dev/null', $status);
  }
  return($err);
}

// Function: addFMextension
//
function addFMextension($family, $key, $method, $time_class, $enabled, $number, $timeout, $fname) {
  global $global_prefs;
  global $MAXNUM;

  for ($i = 0; $i < $MAXNUM; $i++) {
    $my_enabled[$i] = '0';
  }

  $count = 0;
  for ($i = 0; $i < $MAXNUM; $i++) {
    foreach ($enabled as $val) {
      if ($val == $i) {
        if ($number[$i] !== '') {
          $my_enabled[$i] = '1';
          $count++;
          if ($method == 4) { // Only one number allowed with "enable_callee_prompt=>false"
            break 2;
          }
        }
        break;
      }
    }
  }
  $value = $count.':'.$method.':'.$time_class;
  for ($i = 0; $i < $MAXNUM; $i++) {
    $value .= '~'.$my_enabled[$i].':'.$number[$i].':'.$timeout[$i];
  }
  if (($err = putAstDB($family, $key, $value)) != 0) {
    return($err);
  }
  if (! is_file($fname)) {
    return(0);
  }

  if (isFMextension($key, $fname)) {
    shell('sed -i "/^\['.$key.'\]/,/^\[/ s/^number.*/;deleted;&/" '.$fname.' >/dev/null', $status);
    shell('sed -i "/^;deleted;number/ d" '.$fname.' >/dev/null', $status);
    shell('sed -i "/^\['.$key.'\]/,/^\[/ s/^enable_callee_prompt.*/;deleted;&/" '.$fname.' >/dev/null', $status);
    shell('sed -i "/^;deleted;enable_callee_prompt/ d" '.$fname.' >/dev/null', $status);
    if ($count > 0) {
      if ($method == 1) {
        $cmd = 'a'.chr(92).chr(10).'number=>';
        for ($i = 0; $i < $MAXNUM; $i++) {
          if ($my_enabled[$i] == 1) {
            $cmd .= $number[$i].'&';
            $time = $timeout[$i];
          }
        }
        $cmd = rtrim($cmd, '&');
        $cmd .= ','.$time.chr(10);
      } else {
        $cmd = '';
        for ($i = 0; $i < $MAXNUM; $i++) {
          if ($my_enabled[$i] == 1) {
            $cmd .= 'a'.chr(92).chr(10).'number=>'.$number[$i].','.$timeout[$i].chr(10);
          }
        }
      }
      $value = ($method == 4) ? 'false' : 'true';
      $cmd .= 'a'.chr(92).chr(10).'enable_callee_prompt=>'.$value.chr(10);
      shell('sed -i "/^\['.$key.'\]/ {'.chr(10).$cmd.'}" '.$fname.' >/dev/null', $status);
    }
  } elseif ($count > 0) {
    if ($method == 1) {
      $cmd = '['.$key.']'.chr(10).'number=>';
      for ($i = 0; $i < $MAXNUM; $i++) {
        if ($my_enabled[$i] == 1) {
          $cmd .= $number[$i].'&';
          $time = $timeout[$i];
        }
      }
      $cmd = rtrim($cmd, '&');
      $cmd .= ','.$time.chr(10);
    } else {
      $cmd = '['.$key.']'.chr(10);
      for ($i = 0; $i < $MAXNUM; $i++) {
        if ($my_enabled[$i] == 1) {
          $cmd .= 'number=>'.$number[$i].','.$timeout[$i].chr(10);
        }
      }
    }
    $value = ($method == 4) ? 'false' : 'true';
    $cmd .= 'enable_callee_prompt=>'.$value.chr(10);
    if (($value = tuq(getPREFdef($global_prefs, 'followme_number_context_cmdstr'))) !== '') {
      $cmd .= 'context=>'.$value.chr(10);
    }
    if (($value = tuq(getPREFdef($global_prefs, 'followme_music_class_cmdstr'))) !== '') {
      $cmd .= 'musicclass=>'.$value.chr(10);
    }
    shell('echo -n "'.chr(10).$cmd.'" >>'.$fname, $status);
  }
  return($status);
}

// Function: reloadFMmodule
//
function reloadFMmodule($cmd, $fname) {
  $result = 11;

  if (is_file($fname)) {
    $status = asteriskCMD($cmd, '');
    if ($status == 0) {
      $result = 10;
    } elseif ($status == 1101) {
      $result = 1101;
    } elseif ($status == 1102) {
      $result = 1102;
    } else {
      $result = 4;
    }
  }
  return($result);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if ($global_staff_disable_followme) {
    $result = 999;
  } elseif (isset($_POST['submit_add'])) {
    if (isset($_POST['key'])) {
      $key = tuqd($_POST['key']);
    } else {
      $key = $global_user;
    }
    if (preg_match('/^[0-9][0-9]*$/', $key)) {
      if (isset($_POST['method'])) {
        $method = $_POST['method'];
      } else {
        $method = '0';
      }
      if (isset($_POST['time_class'])) {
        $time_class = $_POST['time_class'];
      } else {
        $time_class = '0';
      }
      $enabled = isset($_POST['enabled']) ? $_POST['enabled'] : array();
      for ($i = 0; $i < $MAXNUM; $i++) {
        $number[$i] = tuq($_POST["number$i"]);
        $timeout[$i] = tuq($_POST["timeout$i"]);
        if ($USE_RULES && $number[$i] !== '') {
          if (! preg_match("/$NUMBER_FORMAT/", $number[$i])) {
            $result = 12;
            header('Location: '.$myself.'?result='.$result);
            exit;
          }
        }
      }
      if (addFMextension($family, $key, $method, $time_class, $enabled, $number, $timeout, $FOLLOWMECONF) == 0) {
        $result = reloadFMmodule($RELOAD_FOLLOWME_CMD, $FOLLOWMECONF);
      } else {
        $result = 99;
      }
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    for ($i = 0; $i < arrayCount($delete); $i++) {
      if (delFMextension($family, $delete[$i], $FOLLOWMECONF) == 0) {
        $result = reloadFMmodule($RELOAD_FOLLOWME_CMD, $FOLLOWMECONF);
      } else {
        $result = 99;
      }
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = $global_staff_disable_followme ? 'non-staff' : 'all';
require_once '../common/header.php';

  putHtml('<center>');
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 0) {
      putHtml('<p style="color: green;">Action Successful.</p>');
    } elseif ($result == 1) {
      putHtml('<p style="color: orange;">No Action.</p>');
    } elseif ($result == 2) {
      putHtml('<p style="color: red;">Extension must contain digits [0-9].</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error.</p>');
    } elseif ($result == 4 || $result == 1101 || $result == 1102) {
      putHtml('<p style="color: red;">'.asteriskERROR($result).'</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">Changes saved, Asterisk Follow-Me reloaded.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: orange;">Changes saved, Asterisk Follow-Me not reloaded.</p>');
    } elseif ($result == 12) {
      if (($cmd = htmlspecialchars(getPREFdef($global_prefs, 'number_error_cmdstr'))) === '') {
        $cmd = 'Number must be 10 digits in the format NXXNXXXXXX';
      }
      putHtml('<p style="color: red;">'.$cmd.'</p>');
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
  $db = parseAstDB($family);

  $def_data['key'] = '';
  $def_data['method'] = '0';
  $def_data['time_class'] = '0';
  for ($i = 0; $i < $MAXNUM; $i++) {
    $def_data['enabled'][$i] = '0';
    $def_data['number'][$i] = '';
    $def_data['timeout'][$i] = '45';
  }
  $ldata = $def_data;

  $MANAGE = $global_staff;
  if (($data = fmDBtoDATA($db, ($MANAGE ? FALSE : $global_user))) !== NULL) {
    if (arrayCount($data) == 1) {
      $ldata = $data[0];
    }
  }

  if (isset($_GET['key']) && $MANAGE) {
    $key = $_GET['key'];
    if (($n = arrayCount($data)) > 0) {
      for ($i = 0; $i < $n; $i++) {
        if ($key === $data[$i]['key']) {
          $ldata = $data[$i];
          break;
        }
      }
    }
  }

  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr><td style="text-align: center;" colspan="3">');
  putHtml('<h2>Follow-Me Number Management:</h2>');
  putHtml('</td></tr><tr><td style="text-align: center;">');
  putHtml('<input type="submit" class="formbtn" value="Save Changes" name="submit_add" />');
  if ($MANAGE) {
    putHtml('</td><td class="dialogText" style="text-align: center;">');
    putHtml('Ext:<input type="text" size="8" maxlength="24" name="key" value="'.$ldata['key'].'" />');
    putHtml('</td><td style="text-align: center;">');
    putHtml('<input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />');
  } else {
    putHtml('</td><td>');
    putHtml('&nbsp;');
    putHtml('</td><td style="text-align: center;">');
    putHtml('Extension: '.$global_user);
  }
  putHtml('</td></tr>');
  putHtml('</table>');

  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr class="dtrow0"><td width="50">&nbsp;</td><td>&nbsp;</td></tr>');
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Numbers and Duration:</strong>');
  putHtml('</td></tr>');
  for ($i = 0; $i < $MAXNUM; $i++) {
    putHtml('<tr class="dtrow1"><td style="text-align: right;">');
    $sel = ($ldata['enabled'][$i] === '1') ? ' checked="checked"' : '';
    putHtml('<input type="checkbox" value="'.$i.'" name="enabled[]"'.$sel.' /></td><td>');
    putHtml('Dial');
    putHtml('<input type="text" size="18" maxlength="24" name="number'.$i.'" value="'.$ldata['number'][$i].'" />');
    putHtml('try for');
    putHtml('<input type="text" size="5" maxlength="3" name="timeout'.$i.'" value="'.$ldata['timeout'][$i].'" />');
    putHtml('secs</td></tr>');
  }

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Dial Method:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td>&nbsp;</td><td>');
  putHtml('Dial Numbers:');
  putHtml('<select name="method">');
  if ($MAXNUM == 1) {
    putHtml('<option value="0">With callee prompt</option>');
    $sel = ($ldata['method'] === '4') ? ' selected="selected"' : '';
    putHtml('<option value="4"'.$sel.'>Without callee prompt</option>');
  } else {
    putHtml('<option value="0">One at a Time with callee prompt</option>');
    $sel = ($ldata['method'] === '1') ? ' selected="selected"' : '';
    putHtml('<option value="1"'.$sel.'>All at Once with callee prompt</option>');
    $sel = ($ldata['method'] === '4') ? ' selected="selected"' : '';
    putHtml('<option value="4"'.$sel.'>Single number without callee prompt</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  if (($value = getPREFdef($global_prefs, 'followme_schedule_menu_cmdstr')) !== '') {
    $menu = explode('~', $value);
    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
    putHtml('<strong>Schedule:</strong>');
    putHtml('</td></tr>');
    putHtml('<tr class="dtrow1"><td>&nbsp;</td><td>');
    putHtml('Follow-Me Applies:');
    putHtml('<select name="time_class">');
    putHtml('<option value="0">Always</option>');
    $i = 1;
    foreach ($menu as $value) {
      if ($value !== '') {
        $sel = ($ldata['time_class'] == $i) ? ' selected="selected"' : '';
        putHtml('<option value="'.$i.'"'.$sel.'>'.$value.'</option>');
        $i++;
      }
    }
    putHtml('</select>');
    putHtml('</td></tr>');
  }
  if ($MANAGE) {
    putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
    putHtml('<strong>Follow-Me Database:</strong>');
    putHtml('</td></tr>');
  }
  putHtml('</table>');

  if ($MANAGE) {
    putHtml('<table width="100%" class="datatable">');
    putHtml("<tr>");

    if (($n = arrayCount($data)) > 0) {
      echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Extension", "</td>";
      echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Follow-Me Numbers", "</td>";
      echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
      for ($i = 0; $i < $n; $i++) {
        putHtml("</tr>");
        echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
        echo '<td><a href="'.$myself.'?key='.$data[$i]['key'].'" class="actionText">'.$data[$i]['key'].'</a>', '</td>';
        echo '<td>', wordwrap(htmlspecialchars(fmGetDataValue($data[$i])), 40, '<br />', TRUE), '</td>';
        echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="', $data[$i]['key'], '" />', '</td>';
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
  }
  putHtml("</form>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
