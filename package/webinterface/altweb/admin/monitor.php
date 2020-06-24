<?php

// Copyright (C) 2008-2020 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// monitor.php for AstLinux
// 04-15-2008
// 04-25-2008, Added inline wav
// 06-05-2008, Added multi-user support
// 06-23-2020, Added filename label support
//
// System location of the asterisk monitor directory
$MONITORDIR = '/var/spool/asterisk/monitor/';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

// Function: parseMONITORfiles
//
function parseMONITORfiles($dir, $username) {
  $db['dir'] = $dir;
  $ldir = strlen($dir);
  $id = 0;

  $tmpfile = tempnam("/tmp", "PHP_");
  shell('find '.$dir.' | grep -i "\.wav$" >'.$tmpfile, $status);
  if (($db['status'] = $status) == 0) {
    $ph = @fopen($tmpfile, "r");
    while (! feof($ph)) {
      if (($line = trim(fgets($ph, 1024))) !== '') {
        if (substr($line, 0, $ldir) === $dir) {
          if (($value = substr($line, $ldir)) !== '') {
            if (($stat = @stat($dir.$value)) !== FALSE) {
              $tokens = explode('/', $value);
              if ($username === 'admin' || $username === 'staff' || $username === '' || $username === $tokens[0]) {
                $db['data'][$id]['name'] = $value;
                $db['data'][$id]['size'] = $stat['size'];
                $db['data'][$id]['mtime'] = $stat['mtime'];

                if ($id++ > 998) {  // Sanity limit
                  break;
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

  // Sort by date, newest on top
  if ($id > 1) {
    foreach ($db['data'] as $key => $row) {
      $mtime[$key] = $row['mtime'];
    }
    array_multisort($mtime, SORT_DESC, SORT_NUMERIC, $db['data']);
  }

  return($db);
}

// Function: displayMONITORpath
//
function displayMONITORpath($path, &$label) {
  $label = '';
  $str = '';

  if (($suffix_pos = strrpos($path, '.')) === FALSE) {
    return($str);
  }
  $str = substr($path, 0, $suffix_pos);

  if (($label_pos = strrpos($str, '_LBL_')) !== FALSE) {
    $label = substr($str, $label_pos + 5);
    $str = substr($str, 0, $label_pos);
    if ($label !== '') {
      $str .= '&nbsp;<strong>'.$label.'</strong>';
    }
  }
  return($str);
}

// Function: save_edit_label
//
function save_edit_label($dir, $path, $label) {
  $label = preg_replace('/[^a-zA-Z0-9._ -]+/', '', $label);
  $label = str_replace(' ', '_', $label);

  if (! is_file($dir.$path)) {
    return(5);
  }
  if (($suffix_pos = strrpos($path, '.')) === FALSE) {
    return(1);
  }
  $suffix = substr($path, $suffix_pos);
  $rtn = 10;

  if (($label_pos = strrpos($path, '_LBL_')) !== FALSE) {
    if ($label === '') {
      // remove label
      $new = substr($path, 0, $label_pos).$suffix;
      $rtn = 11;
    } else {
      // update label
      $new = substr($path, 0, $label_pos).'_LBL_'.$label.$suffix;
    }
  } else {
    if ($label === '') {
      // no label
      $new = $path;
    } else {
      // add label
      $new = substr($path, 0, $suffix_pos).'_LBL_'.$label.$suffix;
    }
  }
  if ($path === $new) {
    return(0);
  }
  if (@rename($dir.$path, $dir.$new) === FALSE) {
    return(6);
  }
  return($rtn);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if ($global_staff_disable_monitor) {
    $result = 999;
  } elseif (isset($_POST['submit_reload'])) {
    $result = 0;
    if (isset($_POST['label_path'], $_POST['edit_label'])) {
      $label_path = $_POST['label_path'];
      $edit_label = tuq($_POST['edit_label']);
      $result = save_edit_label($MONITORDIR, $label_path, $edit_label);
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    for ($i = 0; $i < arrayCount($delete); $i++) {
      if (strstr($delete[$i], '../') !== FALSE) {
        $result = 4;
      } elseif (is_file($MONITORDIR.$delete[$i])) {
        @unlink($MONITORDIR.$delete[$i]);
        $result = 0;
      } else {
        $result = 5;
      }
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} elseif (isset($_GET['file']) && (getPREFdef($global_prefs, 'monitor_play_inline') === '')) {
  $file = rawurldecode($_GET['file']);
  $result = 5;
  if (strstr($file, '../') !== FALSE) {
    $result = 4;
  } elseif (is_file($MONITORDIR.$file)) {
    $file = $MONITORDIR.$file;
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
$ACCESS_RIGHTS = $global_staff_disable_monitor ? 'non-staff' : 'all';
require_once '../common/header.php';

require_once '../common/insert-wav-inline.php';

  if (isset($_GET['file'])) {
    $file = rawurldecode($_GET['file']);
    if (strstr($file, '../') !== FALSE) {
      $file = '';
    } elseif (! is_file($MONITORDIR.$file)) {
      $file = '';
    } else {
      if (($cacheLink = createCACHElink($MONITORDIR.$file, getSYSlocation(), $MONITORCACHEPREFIX.$global_user.'_')) === FALSE) {
        $file = '';
      }
    }
  }
  if (isset($_GET['label'])) {
    $label = rawurldecode($_GET['label']);
    if (strstr($label, '../') !== FALSE) {
      $label = '';
    } elseif (! is_file($MONITORDIR.$label)) {
      $label = '';
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
    } elseif ($result == 6) {
      putHtml('<p style="color: red;">File rename failed.</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">Recording filename label was updated.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Recording filename label was removed.</p>');
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
  <h2>Monitor Recording Management:</h2>
  </td></tr><tr><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Refresh List" name="submit_reload" />
  </td><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />
  </td></tr>
  </table>
<?php
  $db = parseMONITORfiles($MONITORDIR, $global_user);

  $inlineType = getPREFdef($global_prefs, 'monitor_play_inline');
  $action = ($inlineType !== '') ? 'Play' : 'Get';
  $datef = (getPREFdef($global_prefs, 'voicemail_24_hour_format') === 'yes') ? 'Y-m-d H:i' : 'Y-m-d h:ia';

  putHtml('<table width="100%" class="datatable">');
  putHtml("<tr>");

  if (($n = arrayCount($db['data'])) > 0) {
    echo '<td class="dialogText" style="text-align: right; font-weight: bold;">', "Size", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Date - Time", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Action", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Monitor Recording", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Label", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td style="text-align: right;">', $db['data'][$i]['size'], '</td>';
      echo '<td style="text-align: left;">', date($datef, $db['data'][$i]['mtime']), '</td>';

      echo '<td style="text-align: center;">';
      if (isset($file) && $file === $db['data'][$i]['name']) {
        insertWAVinline($cacheLink, $inlineType);
      } else {
        echo '<a href="'.$myself.'?file='.rawurlencode($db['data'][$i]['name']).'" class="actionText">'.$action.'</a>';
      }
      echo '</td>';

      echo '<td style="text-align: left;">', displayMONITORpath($db['data'][$i]['name'], $cur_label), '</td>';

      echo '<td style="text-align: center;">';
      if (isset($label) && $label === $db['data'][$i]['name']) {
        echo '<input type="hidden" name="label_path" value="'.$label.'" />';
        echo '<input type="text" size="20" maxlength="64" name="edit_label" value="'.$cur_label.'" />';
      } else {
        echo '<a href="'.$myself.'?label='.rawurlencode($db['data'][$i]['name']).'" class="actionText">&nbsp;+&nbsp;</a>';
      }
      echo '</td>';

      echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="', $db['data'][$i]['name'], '" />', '</td>';
    }
  } else {
    echo '<td style="text-align: center;">No Monitor Recordings in Directory: ', $db['dir'], ' for user ', '"'.$global_user.'"', '</td>';
  }
  putHtml("</tr>");
  putHtml("</table>");
  putHtml("</form>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
