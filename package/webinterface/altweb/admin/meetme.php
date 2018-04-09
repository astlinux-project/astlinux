<?php

// Copyright (C) 2012 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// Inspired by:
// AstLinux Conference Manager v1.0
// -James Babiak

// meetme.php for AstLinux
// 05-16-2012
//

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$isASTERISKv1_4 = isASTERISKv1_4();

// Function: getASTERISKversion
//
function getASTERISKversion() {

  $list = explode(' ', trim(shell_exec('/usr/sbin/asterisk -V')));
  return($list[1]);
}

// Function: isASTERISKv1_4
//
function isASTERISKv1_4() {

  $list = explode('.', getASTERISKversion());
  if ($list[0] === '1' && $list[1] === '4') {
    return(TRUE);
  }
  return(FALSE);
}

// Function: isTALKING
//
function isTALKING($option) {

  $str = 'No';
  if ($option > 0) {
    $str = 'Yes';
  } elseif ($option < 0) {
    $str = '';
  }
  return($str);
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

// Function: userRedirect
//
function userRedirect($chan, $path) {

  $list = explode(',', $path);

  $cont = isset($list[0]) ? $list[0] : 'default';
  $ext  = isset($list[1]) ? $list[1] : 's';
  $prio = isset($list[2]) ? $list[2] : '1';

  if (($socket = @fsockopen('127.0.0.1', '5038', $errno, $errstr, 5)) === FALSE) {
    return(FALSE);
  }
  fputs($socket, "Action: login\r\n");
  fputs($socket, "Username: webinterface\r\n");
  fputs($socket, "Secret: webinterface\r\n");
  fputs($socket, "Events: off\r\n\r\n");

  fputs($socket, "Action: redirect\r\n");
  fputs($socket, "Context: $cont\r\n");
  fputs($socket, "Channel: $chan\r\n");
  fputs($socket, "Exten: $ext\r\n");
  fputs($socket, "Priority: $prio\r\n\r\n");

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
    if (strncasecmp($line, 'Message: Redirect successful', 28) == 0) {
      break;
    }
  }
  while (! feof($socket) && ! $info['timed_out']) {
    fgets($socket, 256);
    $info = stream_get_meta_data($socket);
  }
  fclose($socket);

  sleep(1);
  return(0);
}

// Function: userMute
//
function userMute($user, $mute) {

  if (strpos($user, ',') === FALSE) {
    return(FALSE);
  }
  $ips = explode(',', $user);
  $status = asteriskCMD('meetme '.$mute.' '.$ips[0].' '.$ips[1], '');
  if ($status != 0) {
    return(FALSE);
  }
  return(0);
}

// Function: userKick
//
function userKick($user) {

  if (strpos($user, ',') === FALSE) {
    return(FALSE);
  }
  $ips = explode(',', $user);
  $status = asteriskCMD('meetme kick '.$ips[0].' '.$ips[1], '');
  if ($status != 0) {
    return(FALSE);
  }

  sleep(5);
  return(0);
}

// Function: confLock
//
function confLock($conf, $lock) {

  $status = asteriskCMD('meetme '.$lock.' '.$conf, '');
  if ($status != 0) {
    return(FALSE);
  }
  return(0);
}

// Function: getMEETMErooms
//
function getMEETMErooms() {
  global $isASTERISKv1_4;
  $id = 0;
  $cmd = $isASTERISKv1_4 ? 'meetme' : 'meetme list concise';

  $tmpfile = tempnam("/tmp", "PHP_");
  $status = asteriskCMD($cmd, $tmpfile);
  if ($status == 0) {
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) !== '') {
        if ($isASTERISKv1_4) {
          if (preg_match('/^([0-9][0-9]*) .*$/', $line, $ips)) {
            $rooms[$id]['room'] = $ips[1];
            $rooms[$id]['locked'] = '-1';
            $id++;
          }
        } else {
          if (strpos($line, '!') !== FALSE) {
            $ips = explode('!', $line);
            $rooms[$id]['room'] = $ips[0];
            $rooms[$id]['locked'] = $ips[5];
            $id++;
          }
        }
      }
    }
    fclose($ph);
  }
  @unlink($tmpfile);

  return($rooms);
}

// Function: parseMEETMEdata
//
function parseMEETMEdata($room_list) {
  global $isASTERISKv1_4;
  $id = 0;

  for ($i = 0; $i < count($room_list); $i++) {
    $tmpfile = tempnam("/tmp", "PHP_");
    $status = asteriskCMD('meetme list '.$room_list[$i]['room'].' concise', $tmpfile);
    if ($status == 0) {
      $ph = @fopen($tmpfile, "r");
      while (! feof($ph)) {
        if (($line = trim(fgets($ph, 1024))) !== '') {
          if (strpos($line, '!') !== FALSE) {
            $ips = explode('!', $line);
            $db['data'][$id]['room'] = $room_list[$i]['room'];
            $db['data'][$id]['user'] = $ips[0];
            $db['data'][$id]['cidnum'] = $ips[1];
            $db['data'][$id]['cidname'] = $ips[2];
            $db['data'][$id]['channel'] = $ips[3];
            $db['data'][$id]['mute'] = $ips[6];
            $db['data'][$id]['talking'] = $isASTERISKv1_4 ? $ips[7] : $ips[8];
            $db['data'][$id]['duration'] = $isASTERISKv1_4 ? $ips[8] : $ips[9];
            $id++;
          }
        }
      }
      fclose($ph);
    }
    @unlink($tmpfile);
  }

  return($db);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_staff) {
    $result = 999;
  } elseif (isset($_POST['submit_reload'])) {
    header('Location: '.$myself);
    exit;
  } elseif (isset($_POST['submit_autorefresh'])) {
    header('Location: '.$myself.'?autorefresh');
    exit;
  } elseif (isset($_POST['submit_stoprefresh'])) {
    header('Location: '.$myself);
    exit;
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'staff';
require_once '../common/header.php';

  $autorefresh = isset($_GET['autorefresh']) ? '&amp;autorefresh' : '';

  $ok_redirect = TRUE;
  if (isset($_GET['redirect'])) {
    $redirect_path = getPREFdef($global_prefs, 'meetme_redirect_path_cmdstr');
    $ok_redirect = userRedirect(rawurldecode($_GET['redirect']), $redirect_path);
  }
  $ok = TRUE;
  if (isset($_GET['lock'])) {
    $ok = confLock($_GET['lock'], 'lock');
  }
  if (isset($_GET['unlock'])) {
    $ok = confLock($_GET['unlock'], 'unlock');
  }
  if (isset($_GET['mute'])) {
    $ok = userMute($_GET['mute'], 'mute');
  }
  if (isset($_GET['unmute'])) {
    $ok = userMute($_GET['unmute'], 'unmute');
  }
  if (isset($_GET['kick'])) {
    $ok = userKick($_GET['kick']);
  }

  putHtml('<center>');
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 1) {
      putHtml('<p style="color: orange;">No Action.</p>');
    } elseif ($result == 99) {
      putHtml('<p style="color: red;">Action Failed.</p>');
    } elseif ($result == 999) {
      putHtml('<p style="color: red;">Permission denied for user "'.$global_user.'".</p>');
    } else {
      putHtml('<p>&nbsp;</p>');
    }
  } elseif ($ok_redirect === FALSE) {
    putHtml('<p style="color: red;">Asterisk Action Failed. Redirect requires "call" AMI privileges for [webinterface].</p>');
  } elseif ($ok === FALSE) {
    putHtml('<p style="color: red;">Asterisk Action Failed.</p>');
  } else {
    putHtml('<p>&nbsp;</p>');
  }
  putHtml('</center>');
?>
  <script language="JavaScript" type="text/javascript">
  //<![CDATA[
  var refresh_timeout;
  function auto_refresh() {
    refresh_timeout = setTimeout("refresh()", 10000); // 10 seconds
  }
  function stop_refresh() {
    if (typeof(refresh_timeout) != 'undefined') {
      clearTimeout(refresh_timeout);
      refresh_timeout = undefined;
    }
  }
  function refresh() {
    window.location.replace("/admin/meetme.php?autorefresh");
  }
  //]]>
  </script>
  <center>
  <table class="layout"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;" colspan="5">
  <h2>MeetMe Conference Management:</h2>
  </td></tr><tr><td width="50">&nbsp;
  </td><td style="text-align: center;">
<?php
  if ($autorefresh === '') {
    putHtml('<input type="submit" class="formbtn" value="Refresh List" name="submit_reload" />');
  } else {
    putHtml('<input type="submit" class="formbtn" value="Refresh List" name="submit_autorefresh" />');
  }
?>
  </td><td width="50">&nbsp;
  </td><td style="text-align: center;">
<?php
  if ($autorefresh === '') {
    putHtml('<input type="submit" class="formbtn" value="Auto Refresh List" name="submit_autorefresh" />');
  } else {
    putHtml('<input type="submit" class="formbtn" value="Stop Auto Refresh" onclick="stop_refresh()" name="submit_stoprefresh" />');
  }
?>
  </td><td width="50">&nbsp;
  </td></tr>
  </table>
<?php
  $room_list = getMEETMErooms();
  $db = parseMEETMEdata($room_list);

  $channel = (getPREFdef($global_prefs, 'meetme_channel_show') === 'yes');

  for ($rnum = 0; $rnum < count($room_list); $rnum++) {
    $room = $room_list[$rnum]['room'];
    putHtml('<p class="dialogText" style="text-align: left;">');
    putHtml('&nbsp;<strong>Conference: </strong>'.$room.'&nbsp;&nbsp;');
    if ($room_list[$rnum]['locked'] === '0') {
      echo '<a href="'.$myself.'?lock='.$room.$autorefresh.'" onclick="stop_refresh()" class="actionText">Lock</a>';
    } elseif ($room_list[$rnum]['locked'] > 0) {
      echo '<a href="'.$myself.'?unlock='.$room.$autorefresh.'" onclick="stop_refresh()" class="actionText">Unlock</a>';
    } else {
      echo '<a href="'.$myself.'?lock='.$room.$autorefresh.'" onclick="stop_refresh()" class="actionText">Lock</a>';
      echo '&nbsp;&nbsp;';
      echo '<a href="'.$myself.'?unlock='.$room.$autorefresh.'" onclick="stop_refresh()" class="actionText">Unlock</a>';
    }
    putHtml('</p>');

    putHtml('<table width="100%" class="datatable">');
    putHtml("<tr>");

    if (($n = count($db['data'])) > 0) {
      echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "User#", "</td>";
      echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "CID Num", "</td>";
      echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "CID Name", "</td>";
      if ($channel) {
        echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Channel", "</td>";
      }
      echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Talking", "</td>";
      echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', "Duration", "</td>";
      echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "-- Actions --", "</td>";
      for ($i = 0; $i < $n; $i++) {
        $data = $db['data'][$i];
        if ($data['room'] !== $room) { // skip
          continue;
        }
        $room_user = $data['room'].','.$data['user'];
        putHtml("</tr>");
        echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
        echo '<td style="text-align: left;">', $data['user'], '</td>';
        echo '<td style="text-align: left;">', $data['cidnum'], '</td>';
        echo '<td style="text-align: left;">', htmlspecialchars($data['cidname']), '</td>';
        if ($channel) {
          echo '<td style="text-align: left;">', $data['channel'], '</td>';
        }
        echo '<td style="text-align: center;">', isTALKING($data['talking']), '</td>';
        echo '<td style="text-align: right;">', $data['duration'], '</td>';

        echo '<td style="text-align: right; padding-top: 8px; padding-bottom: 8px;">';
        if ($data['mute'] === '') {
          echo '<a href="'.$myself.'?mute='.$room_user.$autorefresh.'" onclick="stop_refresh()" class="actionText">Mute</a>';
        } else {
          echo '<a href="'.$myself.'?unmute='.$room_user.$autorefresh.'" onclick="stop_refresh()" class="actionText">Unmute</a>';
        }
        echo '&nbsp;<a href="'.$myself.'?redirect='.rawurlencode($data['channel']).$autorefresh.'" onclick="stop_refresh()" class="actionText">Redirect</a>';
        echo '&nbsp;<a href="/admin/blacklist.php?num='.$data['cidnum'].getCommentURL($data['cidname']).'" onclick="stop_refresh()" class="actionText">Blacklist</a>';
        echo '&nbsp;<a href="'.$myself.'?kick='.$room_user.$autorefresh.'" onclick="stop_refresh()" class="actionText">Kick</a>';
        echo '</td>';
      }
    }
    putHtml("</tr>");
    putHtml("</table>");
  }
  if (count($room_list) == 0) {
    putHtml('<p>No active MeetMe conferences.</p>');
  }
  putHtml("</form>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
  if ($autorefresh !== '') {
    putHtml('<script language="JavaScript" type="text/javascript">');
    putHtml('//<![CDATA[');
    putHtml('auto_refresh();');
    putHtml('//]]>');
    putHtml('</script>');
  }
} // End of HTTP GET
require_once '../common/footer.php';

?>
