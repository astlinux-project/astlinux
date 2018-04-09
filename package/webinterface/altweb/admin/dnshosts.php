<?php

// Copyright (C) 2008-2016 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// dnshosts.php for AstLinux
// 01-05-2009
// 12-10-2010, Added IPv6 support
// 07-22-2013, Reorganize to force unique IP's
// 02-10-2016, Added Staff support
//
// System location of /mnt/kd/rc.conf.d directory
$DNSHOSTSCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.dnshosts.conf file
$DNSHOSTSCONFFILE = '/mnt/kd/rc.conf.d/gui.dnshosts.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

// Function: saveDNSHOSTSsettings
//
function saveDNSHOSTSsettings($conf_dir, $conf_file, $db, $delete = NULL) {

  if (is_dir($conf_dir) === FALSE) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.dnshosts.conf - start ###\n###\n");

  $value = 'STATICHOSTS="';
  fwrite($fp, "### STATICHOSTS\n".$value."\n");
  if (($n = count($db['data'])) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $skip = FALSE;
      if (! is_null($delete)) {
        for ($j = 0; $j < count($delete); $j++) {
          if ($delete[$j] == $i) {
            $skip = TRUE;
            break;
          }
        }
      }
      if (! $skip) {
        $value = str_replace('~', '-', $db['data'][$i]['name']);
        $value .= '~'.$db['data'][$i]['ip'];
        $value .= '~'.$db['data'][$i]['mac'];
        $value .= '~';
        if ($db['data'][$i]['comment'] !== '') {
          $value .= str_replace('~', '-', $db['data'][$i]['comment']);
        }
        fwrite($fp, $value."\n");
      }
    }
  }
  fwrite($fp, '"'."\n");

  fwrite($fp, "### gui.dnshosts.conf - end ###\n");
  fclose($fp);

  return(11);
}

// Function: parseDNSHOSTSconf
//
function parseDNSHOSTSconf($vars) {
  $id = 0;

  if (($line = getVARdef($vars, 'STATICHOSTS')) !== '') {
    $linetokens = explode("\n", $line);
    foreach ($linetokens as $data) {
      if ($data !== '') {
        $datatokens = explode('~', $data);
        $db['data'][$id]['name'] = $datatokens[0];
        $db['data'][$id]['ip'] = $datatokens[1];
        $db['data'][$id]['mac'] = (isset($datatokens[2]) ? $datatokens[2] : '');
        $db['data'][$id]['comment'] = (isset($datatokens[3]) ? $datatokens[3] : '');
        $id++;
      }
    }
  }
  // Sort by IP Address
  if ($id > 1) {
    foreach ($db['data'] as $key => $row) {
      $ip[$key] = pad_ipv4_str($row['ip']);
    }
    array_multisort($ip, SORT_ASC, SORT_STRING, $db['data']);
  }
  return($db);
}

// Function: addDNSHOST
//
function addDNSHOST(&$db, $id) {

  $name = tuq($_POST['name']);
  $ip = tuq($_POST['ip']);
  $mac = tuq($_POST['mac']);
  $comment = tuq($_POST['comment']);
  if ($name === '' ||
      $ip === '') {
    return(FALSE);
  }
  $db['data'][$id]['name'] = $name;
  $db['data'][$id]['ip'] = $ip;
  $db['data'][$id]['mac'] = $mac;
  $db['data'][$id]['comment'] = $comment;

  return(TRUE);
}

if (is_file($DNSHOSTSCONFFILE)) {
  $vars = parseRCconf($DNSHOSTSCONFFILE);
} else {
  $vars = NULL;
}
$db = parseDNSHOSTSconf($vars);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! ($global_admin || $global_staff_enable_dnshosts)) {
    $result = 999;
  } elseif (isset($_POST['submit_save'])) {
    $n = count($db['data']);
    $id = $n;
    for ($i = 0; $i < $n; $i++) {
      if ($db['data'][$i]['ip'] === tuq($_POST['ip'])) {
        $id = $i;
        break;
      }
    }
    if (filter_var(tuq($_POST['ip']), FILTER_VALIDATE_IP) !== FALSE) {
      $mac = tuq($_POST['mac']);
      if ($mac === '' || preg_match('/^([0-9a-fA-F]{2}:){5}([0-9a-fA-F]{2})$/', $mac)) {
        if (addDNSHOST($db, $id)) {
          $result = saveDNSHOSTSsettings($DNSHOSTSCONFDIR, $DNSHOSTSCONFFILE, $db);
        }
      } else {
        $result = 5;
      }
    } else {
      $result = 4;
    }
  } elseif (isset($_POST['submit_restart'])) {
    $result = 99;
    if (isset($_POST['confirm_restart'])) {
      $result = restartPROCESS('dnsmasq', 10, $result, 'init');
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    if (count($delete) > 0) {
      $result = saveDNSHOSTSsettings($DNSHOSTSCONFDIR, $DNSHOSTSCONFFILE, $db, $delete);
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = $global_staff_enable_dnshosts ? 'staff' : 'admin';
require_once '../common/header.php';

  putHtml("<center>");
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 2) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error creating file.</p>');
    } elseif ($result == 4) {
      putHtml('<p style="color: red;">Not a valid IPv4 or IPv6 Address</p>');
    } elseif ($result == 5) {
      putHtml('<p style="color: red;">Not a valid MAC Address, format ff:ff:ff:ff:ff:ff</p>');
    } elseif ($result == 10) {
      putHtml('<p style="color: green;">DNS &amp; DHCP Server'.statusPROCESS('dnsmasq').'.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Restart DNS" to apply any changed settings.</p>');
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
  <table class="layout"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;" colspan="3">
  <h2>DNS Forwarder Hosts:</h2>
  </td></tr><tr><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  &nbsp;
  </td><td class="dialogText" style="text-align: center;">
  <input type="submit" class="formbtn" value="Restart DNS" name="submit_restart" />
  &ndash;
  <input type="checkbox" value="restart" name="confirm_restart" />&nbsp;Confirm
  </td><td style="text-align: center;">
  &nbsp;
  <input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />
  </td></tr>
  </table>

<?php
  if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $n = count($db['data']);
    if ($id < $n && $id >= 0) {
      $ldb = $db['data'][$id];
    }
  }
  if (is_null($ldb)) {
    $ldb['name'] = '';
    $ldb['ip'] = '';
    $ldb['mac'] = '';
    $ldb['comment'] = '';
  }
  putHtml('<table class="stdtable">');
  putHtml('<tr><td class="dialogText" style="text-align: right;">');
  putHtml('IP Address:<input type="text" size="42" maxlength="39" name="ip" value="'.$ldb['ip'].'" />');
  putHtml('</td><td class="dialogText" style="text-align: right;">');
  putHtml('Host Name(s):<input type="text" size="28" maxlength="128" name="name" value="'.$ldb['name'].'" />');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;" colspan="2">');
  putHtml('MAC Address matched via DHCP for IPv4 address <i>(optional)</i>:<input type="text" size="20" maxlength="17" name="mac" value="'.$ldb['mac'].'" />');
  putHtml('</td></tr>');
  putHtml('<tr><td class="dialogText" style="text-align: right;" colspan="2">');
  putHtml('Comment <i>(optional)</i>:<input type="text" size="42" maxlength="42" name="comment" value="'.htmlspecialchars($ldb['comment']).'" />');
  putHtml('</td></tr>');
  putHtml('</table>');

  putHtml('<table width="100%" class="datatable">');
  putHtml('<tr>');

  if (($n = count($db['data'])) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "IP Address", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Host Name(s)", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "MAC Address", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td>', '<a href="'.$myself.'?id='.$i.'" class="actionText">'.$db['data'][$i]['ip'].'</a>', '</td>';
      echo '<td>', $db['data'][$i]['name'], '</td>';
      echo '<td>', $db['data'][$i]['mac'], '</td>';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="', $i, '" />', '</td>';
      if ($db['data'][$i]['comment'] !== '') {
        putHtml("</tr>");
        echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
        echo '<td class="dialogText" colspan="4">', '&nbsp;&nbsp;<strong>Comment:</strong>&nbsp;',
             htmlspecialchars($db['data'][$i]['comment']), '</td>';
      }
    }
  } else {
    echo '<td style="text-align: center;">No DNS Forwarder Hosts are defined.', '</td>';
  }

  putHtml('</tr>');
  putHtml('</table>');
  putHtml('</form>');
  putHtml('</center></td></tr></table>');
  putHtml('</center>');
} // End of HTTP GET
require_once '../common/footer.php';

?>
