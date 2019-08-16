<?php

// Copyright (C) 2012 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// openvpnuserpass.php for AstLinux
// 06-05-2012
//
// System location of /mnt/kd/rc.conf.d directory
$OPENVPNUSERPASSCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.openvpnuserpass.conf file
$OPENVPNUSERPASSCONFFILE = '/mnt/kd/rc.conf.d/gui.openvpnuserpass.conf';

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

// Function: openvpnGETclients
//
function openvpnGETclients($vars) {
  $id = 0;

  if (($line = getVARdef($vars, 'OVPN_USER_PASS')) !== '') {
    $linetokens = explode("\n", $line);
    foreach ($linetokens as $data) {
      if ($data !== '') {
        $datatokens = explode(' ', $data);
        $db['data'][$id]['user'] = $datatokens[0];
        $db['data'][$id]['pass'] = $datatokens[1];
        $id++;
      }
    }
  }
  // Sort by Username
  if ($id > 1) {
    foreach ($db['data'] as $key => $row) {
      $user[$key] = $row['user'];
    }
    array_multisort($user, SORT_ASC, SORT_STRING, $db['data']);
  }
  return($db);
}

// Function: saveOPENVPNsettings
//
function saveOPENVPNsettings($conf_dir, $conf_file, $db, $delete = NULL) {
  $result = 11;

  if (! is_dir($conf_dir)) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.openvpnuserpass.conf - start ###\n###\n");

  $value = 'OVPN_USER_PASS="';
  fwrite($fp, "### Authentication\n".$value."\n");
  if (arrayCount($db['data']) > 0) {
    foreach ($db['data'] as $data) {
      if ($data['user'] !== '' && $data['pass'] !== '') {
        $skip = FALSE;
        if (! is_null($delete)) {
          foreach ($delete as $deluser) {
            if ($deluser === $data['user']) {
              $skip = TRUE;
              break;
            }
          }
        }
        if (! $skip) {
          fwrite($fp, $data['user'].' '.$data['pass']."\n");
        }
      }
    }
  }
  fwrite($fp, '"'."\n");

  fwrite($fp, "### gui.openvpnuserpass.conf - end ###\n");
  fclose($fp);

  return($result);
}

// Function: addUserPass
//
function addUserPass(&$db, $id) {

  $user = str_replace(' ', '', stripshellsafe($_POST['user']));
  $pass = str_replace(' ', '', stripshellsafe($_POST['pass']));

  if ($user === '') {
    return(FALSE);
  }
  if ($pass === '') {
    return(1);
  }

  $db['data'][$id]['user'] = $user;
  $db['data'][$id]['pass'] = $pass;

  return(TRUE);
}

if (is_file($OPENVPNUSERPASSCONFFILE)) {
  $vars = parseRCconf($OPENVPNUSERPASSCONFFILE);
} else {
  $vars = NULL;
}
$db = openvpnGETclients($vars);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_admin) {
    $result = 999;
  } elseif (isset($_POST['submit_save']) || isset($_POST['submit_openvpn_config'])) {
    $n = arrayCount($db['data']);
    $id = $n;
    for ($i = 0; $i < $n; $i++) {
      if ($db['data'][$i]['user'] === str_replace(' ', '', stripshellsafe($_POST['user']))) {
        $id = $i;
        break;
      }
    }
    $ok = addUserPass($db, $id);
    $result = saveOPENVPNsettings($OPENVPNUSERPASSCONFDIR, $OPENVPNUSERPASSCONFFILE, $db);
    if ($result == 11 && $ok === 1) {
      $result = 12;
    }
    if (isset($_POST['submit_openvpn_config'])) {
      header('Location: /admin/openvpn.php');
      exit;
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    if (arrayCount($delete) > 0) {
      $result = saveOPENVPNsettings($OPENVPNUSERPASSCONFDIR, $OPENVPNUSERPASSCONFFILE, $db, $delete);
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'admin';
require_once '../common/header.php';

  putHtml('<center>');
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 2) {
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error creating file.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "OpenVPN Configuration" to return to previous screen.</p>');
    } elseif ($result == 12) {
      putHtml('<p style="color: red;">Missing Password, User not added.</p>');
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
  putHtml('</center>');
?>
  <center>
  <table class="layout"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;" colspan="5">
  <h2>OpenVPN Server User/Pass:</h2>
  </td></tr><tr><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Save Settings" name="submit_save" />
  </td><td width="30">&nbsp;
  </td><td style="text-align: center;">
  <input type="submit" value="OpenVPN Configuration" name="submit_openvpn_config" class="button" />
  </td><td width="30">&nbsp;
  </td><td style="text-align: center;">
  <input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />
  </td></tr></table>
<?php

  if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $n = arrayCount($db['data']);
    for ($i = 0; $i < $n; $i++) {
      if ($id === $db['data'][$i]['user']) {
        $ldb = $db['data'][$i];
        break;
      }
    }
  }
  if (is_null($ldb)) {
    $ldb['user'] = '';
    $ldb['pass'] = '';
  }

  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr class="dtrow0"><td width="160">&nbsp;</td><td>&nbsp;</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Client Credentials:</strong>');
  putHtml('</td></tr>');
  putHtml('<tr><td style="text-align: right;">');
  putHtml('Username:');
  putHtml('</td><td style="text-align: left;">');
  putHtml('<input type="text" size="36" maxlength="64" name="user" value="'.$ldb['user'].'" />');
  putHtml('</td></tr>');
  putHtml('<tr><td style="text-align: right;">');
  putHtml('Password:');
  putHtml('</td><td style="text-align: left;">');
  putHtml('<input type="password" size="36" maxlength="128" name="pass" value="'.$ldb['pass'].'" />');
  putHtml('</td></tr>');
  putHtml('</table>');

  putHtml('<table width="66%" class="datatable">');
  putHtml("<tr>");

  if (($n = arrayCount($db['data'])) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Users", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td><a href="'.$myself.'?id='.$db['data'][$i]['user'].'" class="actionText">'.$db['data'][$i]['user'].'</a>', '</td>';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="', $db['data'][$i]['user'], '" />', '</td>';
    }
  } else {
    echo '<td style="color: orange; text-align: center;">No Client Credentials.', '</td>';
  }
  putHtml("</tr>");
  putHtml("</table>");
  putHtml("</form>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
