<?php

// Copyright (C) 2008-2013 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// voicemail.php for AstLinux
// 04-20-2008
// 06-04-2008, Added multi-user support
// 07-20-2008, Added special user "staff" permissions
// 07-21-2008, Added externnotify support
// 09-05-2012, Automatically create "Old" folder if it doesn't exist
// 07-25-2013, Add support for FOP2 UserEvent: FOP2RELOADVOICEMAIL
//
// System location of the asterisk voicemail directory
$VOICEMAILDIR = '/var/spool/asterisk/voicemail/';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

// Function: asteriskAMI_UserEvent
//
function asteriskAMI_UserEvent($user, $pass, $event) {

  $event_list = explode(',', $event);

  if (($socket = @fsockopen('127.0.0.1', '5038', $errno, $errstr, 5)) === FALSE) {
    return(FALSE);
  }
  fputs($socket, "Action: login\r\n");
  fputs($socket, "Username: $user\r\n");
  fputs($socket, "Secret: $pass\r\n");
  fputs($socket, "Events: off\r\n\r\n");

  fputs($socket, "Action: UserEvent\r\n");
  foreach ($event_list as $value) {
    fputs($socket, "$value\r\n");
  }
  fputs($socket, "\r\n");

  fputs($socket, "Action: logoff\r\n\r\n");

  stream_set_timeout($socket, 5);
  $info = stream_get_meta_data($socket);
  while (! feof($socket) && ! $info['timed_out']) {
    $line = fgets($socket, 256);
    $info = stream_get_meta_data($socket);
    if (strncasecmp($line, 'Response: Error', 15) == 0) {
      while (! feof($socket) && ! $info['timed_out']) {
        fgets($socket, 256);
        $info = stream_get_meta_data($socket);
      }
      fclose($socket);
      return(FALSE);
    }
    if (strncasecmp($line, 'Message: Event Sent', 19) == 0) {
      break;
    }
  }
  while (! feof($socket) && ! $info['timed_out']) {
    fgets($socket, 256);
    $info = stream_get_meta_data($socket);
  }
  fclose($socket);

  return(0);
}

// Function: getVMdataTXT
//
function getVMdataTXT($path) {

  if (($ph = @fopen($path, "r")) === FALSE) {
    return(FALSE);
  }
  $vm['cidname'] = '';
  $vm['cidnum'] = '';
  $vm['duration'] = 0;
  while (! feof($ph)) {
    if (($line = trim(fgets($ph, 1024))) !== '') {
      if ($line[0] !== ';' && $line[0] !== '[') {
        if (($pos = strpos($line, '=')) !== FALSE) {
          $var = trim(substr($line, 0, $pos), ' ');
          $value = trim(substr($line, ($pos + 1)), '" ');
          if ($var !== '' && $value !== '') {
            if ($var === 'callerid') {
              $cidtokens = explode('"', $value);
              $vm['cidname'] = trim($cidtokens[0]);
              $vm['cidnum'] = trim($cidtokens[1], ' <>');
            } elseif ($var === 'origtime') {
              $vm['origtime'] = $value;
            } elseif ($var === 'duration') {
              $vm['duration'] = $value;
            }
          }
        }
      }
    }
  }
  fclose($ph);

  return(isset($vm['origtime']) ? $vm : FALSE);
}

// Function: parseVOICEMAILfiles
//
function parseVOICEMAILfiles($dir, $username) {
  $db['dir'] = $dir;
  $ldir = strlen($dir);
  $id = 0;

  $tmpfile = tempnam("/tmp", "PHP_");
  shell('find '.$dir.' | grep "\.txt$" >'.$tmpfile, $status);
  if (($db['status'] = $status) == 0) {
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) !== '') {
        if (substr($line, 0, $ldir) === $dir) {
          if (($value = substr($line, $ldir, -4)) !== '') {
            $path = $dir.$value.'.txt';
            if (is_file($path)) {
              $tokens = explode('/', $value);
              if (isset($tokens[3]) && $tokens[3] !== '' &&
                ($username === 'admin' || $username === 'staff' || $username === '' || $username === $tokens[1])) {
                if (($vm_data = getVMdataTXT($path)) !== FALSE) {
                  $db['data'][$id]['context'] = $tokens[0];
                  $db['data'][$id]['mbox'] = $tokens[1];
                  $db['data'][$id]['folder'] = $tokens[2];
                  $db['data'][$id]['basename'] = $tokens[3];

                  $db['data'][$id]['cidname'] = $vm_data['cidname'];
                  $db['data'][$id]['cidnum'] = $vm_data['cidnum'];
                  $db['data'][$id]['duration'] = $vm_data['duration'];
                  $db['data'][$id]['origtime'] = $vm_data['origtime'];

                  // Give priority to .wav (PCM) over .WAV (GSM)
                  if (is_file($dir.$value.'.wav')) {
                    $db['data'][$id]['suffix'] = '.wav';
                  } elseif (is_file($dir.$value.'.WAV')) {
                    $db['data'][$id]['suffix'] = '.WAV';
                  } else {
                    $db['data'][$id]['suffix'] = '';
                  }
                  if ($id++ > 998) {  // Sanity limit
                    break;
                  }
                }
              }
            }
          }
        }
      }
    }
    fclose($ph);
  }
  @unlink($tmpfile);

  // Sort by mbox first, then by date, newest on top
  if ($id > 1) {
    foreach ($db['data'] as $key => $row) {
      $mbox[$key] = $row['mbox'];
      $origtime[$key] = $row['origtime'];
    }
    array_multisort($mbox, SORT_ASC, SORT_NUMERIC, $origtime, SORT_DESC, SORT_NUMERIC, $db['data']);
  }

  return($db);
}

// Function: notifyVMdir
//
function notifyVMdir($dir, $path, $count, $fop2) {
  global $global_prefs;

  $value = substr($path, strlen($dir));
  $tokens = explode('/', $value);
  $context = $tokens[0];
  $mbox = $tokens[1];
  $folder = $tokens[2];

  if ($fop2 && is_addon_package('fop2')) {
    $user_event= 'UserEvent: FOP2RELOADVOICEMAIL,Mailbox: '.$mbox.'@'.$context;
    asteriskAMI_UserEvent('fop2', 'astlinux', $user_event);
  }

  if ($folder !== 'INBOX') {
    return(FALSE);
  }

  if (getPREFdef($global_prefs, 'voicemail_extern_notify') === 'yes') {
    if (($ph = popen("grep -m 1 '^externnotify' /etc/asterisk/voicemail.conf", "r")) !== FALSE) {
      if (! feof($ph)) {
        if (($line = trim(fgets($ph, 1024))) !== '') {
          if (($pos = strpos($line, '=')) !== FALSE) {
            $value = trim(substr($line, ($pos + 1)), '" ');
            if (($pos = strpos($value, ' ')) !== FALSE) {
              $value = substr($value, 0, $pos);
            }
            if (($pos = strpos($value, ';')) !== FALSE) {
              $value = substr($value, 0, $pos);
            }
            if (is_executable($value)) {
              shell($value.' '.$context.' '.$mbox.' '.$count, $status);
            }
          }
        }
      }
      pclose($ph);
    }
  }
  return(TRUE);
}

// Function: sequenceVMdir
//
function sequenceVMdir($path) {
  $id = 0;

  $prefix = basename($path);
  $prefix = substr($prefix, 0, (strlen($prefix) - 4));
  $pos = strrpos($path, '/');
  $dir = substr($path, 0, ($pos + 1));
  if(! is_dir($dir)) {
    return($id);
  }
  if (($dh = opendir($dir)) !== FALSE) {
    while (($file = readdir($dh)) !== FALSE) {
      if($file[0] !== '.') {
        if (substr($file, -4) === '.txt') {
          $file = basename($file, '.txt');
          if ($prefix === substr($file, 0, (strlen($file) - 4))) {
            $msg[$id] = $file;
            $id++;
          }
        }
      }
    }
    closedir($dh);
  }
  if ($id > 0) {
    sort($msg);
    foreach ($msg as $key => $msgbase) {
      if (($base = str_pad($key, 4, '0', STR_PAD_LEFT)) !== substr($msgbase, -4)) {
        foreach (glob($dir.$msgbase.'*') as $msgfile) {
          if (is_file($msgfile)) {
            if (($pos = strrpos(basename($msgfile), '.')) !== FALSE) {
              $suffix = substr(basename($msgfile), $pos);
              @rename($msgfile, $dir.$prefix.$base.$suffix);
            }
          }
        }
      }
    }
  }
  return($id);
}

// Function: delVMmessage
//
function delVMmessage($path) {

  foreach (glob($path.'*') as $msgfile) {
    if (is_file($msgfile)) {
      @unlink($msgfile);
    }
  }
  return(TRUE);
}

// Function: moveVMmessage
//
function moveVMmessage($msg, $folder) {

  if (! is_dir($msg['dir'].$msg['context'].'/'.$msg['mbox'].'/'.$folder)) {
    return(FALSE);
  }

  $prefix = substr($msg['basename'], 0, (strlen($msg['basename']) - 4));
  $fpath = $msg['dir'].$msg['context'].'/'.$msg['mbox'].'/'.$msg['folder'].'/'.$msg['basename'];
  $tpath = $msg['dir'].$msg['context'].'/'.$msg['mbox'].'/'.$folder.'/'.$prefix.'9999';

  foreach (glob($fpath.'*') as $msgfile) {
    if (is_file($msgfile)) {
      if (($pos = strrpos(basename($msgfile), '.')) !== FALSE) {
        $suffix = substr(basename($msgfile), $pos);
        @rename($msgfile, $tpath.$suffix);
      }
    }
  }
  $cnt = sequenceVMdir($fpath);
  notifyVMdir($msg['dir'], $fpath, $cnt, FALSE);

  $cnt = sequenceVMdir($tpath);
  notifyVMdir($msg['dir'], $tpath, $cnt, TRUE);

  return($cnt == 0 ? FALSE : $cnt);
}

// Function: xferVMmsg
//
function xferVMmsg($dir, $path, $folder) {
  $msg['dir'] = $dir;

  $tokens = explode('/', $path);
  if (isset($tokens[3]) && $tokens[3] !== '') {
    $msg['context'] = $tokens[0];
    $msg['mbox'] = $tokens[1];
    $msg['folder'] = $tokens[2];
    $msg['basename'] = $tokens[3];
    if ($msg['folder'] === 'INBOX' && $folder === 'Old') {
      # Later versions of Asterisk no longer automatically create the "Old" folder
      if (! is_dir($Old = $msg['dir'].$msg['context'].'/'.$msg['mbox'].'/'.$folder)) {
        @mkdir($Old, 0755);
      }
      return(moveVMmessage($msg, $folder));
    } elseif ($msg['folder'] === 'Old' && $folder === 'INBOX') {
      return(moveVMmessage($msg, $folder));
    }
  }
  return(FALSE);
}

// Function: getXFERcmd
//
function getXFERcmd($data) {
  $path = $data['context'].'/'.$data['mbox'].'/'.$data['folder'].'/'.$data['basename'];

  if ($data['folder'] === 'INBOX') {
    $cmd['href'] = 'xfer_old='.$path;
    $cmd['action'] = '&raquo;Old';
  } elseif ($data['folder'] === 'Old') {
    $cmd['href'] = 'xfer_in='.$path;
    $cmd['action'] = '&raquo;IN';
  } else {
    $cmd['href'] = '';
    $cmd['action'] = '&raquo;';
  }
  return($cmd);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if ($global_staff_disable_voicemail) {
    $result = 999;
  } elseif (isset($_POST['submit_reload'])) {
    header('Location: '.$myself);
    exit;
  } elseif (isset($_POST['submit_delete'])) {
    $resequence = 0;
    $delete = $_POST['delete'];
    for ($i = 0; $i < count($delete); $i++) {
      if (strstr($delete[$i], '../') !== FALSE) {
        $result = 4;
      } elseif (is_file($VOICEMAILDIR.$delete[$i].'.txt')) {
        delVMmessage($VOICEMAILDIR.$delete[$i]);
        $resequence++;
      } else {
        $result = 5;
      }
    }
    if ($resequence > 0) {
      for ($i = 0; $i < count($delete); $i++) {
        $cnt = sequenceVMdir($VOICEMAILDIR.$delete[$i]);
        notifyVMdir('', $delete[$i], $cnt, TRUE);
      }
      $result = 0;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} elseif (isset($_GET['xfer_in'])) {
  $file = $_GET['xfer_in'];
  $result = 5;
  if (strstr($file, '../') !== FALSE) {
    $result = 4;
  } elseif (is_file($VOICEMAILDIR.$file.'.txt')) {
    if (xferVMmsg($VOICEMAILDIR, $file, 'INBOX') !== FALSE) {
      header('Location: '.$myself);
      exit;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} elseif (isset($_GET['xfer_old'])) {
  $file = $_GET['xfer_old'];
  $result = 5;
  if (strstr($file, '../') !== FALSE) {
    $result = 4;
  } elseif (is_file($VOICEMAILDIR.$file.'.txt')) {
    if (xferVMmsg($VOICEMAILDIR, $file, 'Old') !== FALSE) {
      header('Location: '.$myself);
      exit;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} elseif (isset($_GET['file']) && (getPREFdef($global_prefs, 'monitor_play_inline') === '')) {
  $file = $_GET['file'];
  $result = 5;
  if (strstr($file, '../') !== FALSE) {
    $result = 4;
  } elseif (is_file($VOICEMAILDIR.$file)) {
    $file = $VOICEMAILDIR.$file;
    header('Content-Type: audio/x-wav');
    header('Content-Disposition: attachment; filename="'.basename($file).'"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: '.filesize($file));
    ob_end_clean();
    flush();
    @readfile($file);
    exit;
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = $global_staff_disable_voicemail ? 'non-staff' : 'all';
require_once '../common/header.php';

require_once '../common/insert-wav-inline.php';

  if (isset($_GET['file'])) {
    $file = $_GET['file'];
    if (strstr($file, '../') !== FALSE) {
      $file = '';
    } elseif (! is_file($VOICEMAILDIR.$file)) {
      $file = '';
    } else {
      if (($cacheLink = createCACHElink($VOICEMAILDIR.$file, getSYSlocation(), $VOICEMAILCACHEPREFIX.$global_user.'_')) === FALSE) {
        $file = '';
      }
    }
  }

  putHtml('<center>');
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 1) {
      putHtml('<p style="color: orange;">No Action.</p>');
    } elseif ($result == 4) {
      putHtml('<p style="color: red;">Permission Denied.</p>');
    } elseif ($result == 5) {
      putHtml('<p style="color: red;">File Not Found.</p>');
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
  <tr><td style="text-align: center;" colspan="2">
  <h2>Voicemail Message Management:</h2>
  </td></tr><tr><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Refresh List" name="submit_reload" />
  </td><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />
  </td></tr>
  </table>
<?php
  $db = parseVOICEMAILfiles($VOICEMAILDIR, $global_user);

  $inlineType = getPREFdef($global_prefs, 'monitor_play_inline');
  $action = ($inlineType !== '') ? 'Play' : 'Get';
  $datef = (getPREFdef($global_prefs, 'voicemail_24_hour_format') === 'yes') ? 'Y-m-d H:i' : 'Y-m-d h:ia';
  $context = (getPREFdef($global_prefs, 'voicemail_show_context') === 'yes');

  putHtml('<table width="100%" class="datatable">');
  putHtml("<tr>");

  if (($n = count($db['data'])) > 0) {
    if ($context) {
      echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Context", "</td>";
    }
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Mbox", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Folder", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Duration", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Date - Time", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Xfer", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Action", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "CID Name", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "CID Num", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      $data = $db['data'][$i];
      $path = $data['context'].'/'.$data['mbox'].'/'.$data['folder'].'/'.$data['basename'];
      $cmd = getXFERcmd($data);
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      if ($context) {
        echo '<td style="text-align: left;">', $data['context'], '</td>';
      }
      echo '<td style="text-align: left;">', $data['mbox'], '</td>';
      echo '<td style="text-align: center;">', $data['folder'], '</td>';
      echo '<td style="text-align: center;">', secs2minsec($data['duration']), '</td>';
      echo '<td style="text-align: left;">', date($datef, $data['origtime']), '</td>';
      echo '<td style="text-align: center;">', ($data['folder'] !== 'INBOX' && $data['folder'] !== 'Old') ? '&nbsp;' : '<a href="'.$myself.'?'.$cmd['href'].'" class="actionText">'.$cmd['action'].'</a>', '</td>';

      echo '<td style="text-align: center;">';
      if (isset($file) && $file === $path.$data['suffix']) {
        insertWAVinline($cacheLink, $inlineType);
      } else {
        echo ($data['suffix'] === '') ? '&nbsp;' : '<a href="'.$myself.'?file='.$path.$data['suffix'].'" class="actionText">'.$action.'</a>';
      }
      echo '</td>';

      echo '<td style="text-align: left;">', $data['cidname'], '</td>';
      echo '<td style="text-align: left;">', $data['cidnum'], '</td>';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="', $path, '" />', '</td>';
    }
  } else {
    echo '<td style="text-align: center;">No Voicemail Files in Directory: ', $db['dir'], ' for user ', '"'.$global_user.'"', '</td>';
  }
  putHtml("</tr>");
  putHtml("</table>");
  putHtml("</form>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
