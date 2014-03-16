<?php

// Copyright (C) 2014 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// phoneprov.php for AstLinux
// 03-14-2014
//
// System location of /mnt/kd/rc.conf.d directory
$PHONEPROVCONFDIR = '/mnt/kd/rc.conf.d';
// System location of gui.phoneprov.conf file
$PHONEPROVCONFFILE = '/mnt/kd/rc.conf.d/gui.phoneprov.conf';

$family = "phoneprov";
$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

$MAXNUM = (int)getPREFdef($global_prefs, 'phoneprov_extensions_displayed');
if ($MAXNUM <= 0) {
  $MAXNUM = 2;
} elseif ($MAXNUM > 6) {
  $MAXNUM = 6;
}

$gw_if_menu = array (
  'INTIF' => '1st LAN Interface',
  'INT2IF' => '2nd LAN Interface',
  'INT3IF' => '3rd LAN Interface',
  'EXTIF' => 'External Interface'
);

// Function: putACTIONresult
//
function putACTIONresult($result_str, $status) {
  global $myself;
  
  if ($status == 0) {
    $result = 100;
  } else {
    $result = 101;
  }
  if ($result == 100) {
    $result_str = 'Phone Provisioning files are generated.';
  } elseif ($result_str === '') {
    $result_str = 'Error';
  }
  header('Location: '.$myself.'?result_str='.rawurlencode($result_str).'&result='.$result);
}

// Function: getACTIONresult
//
function getACTIONresult($result) {
  $str = 'No Action.';
  
  if (isset($_GET['result_str'])) {
    $str = rawurldecode($_GET['result_str']);
  }
  if ($result == 100) {
    $color = 'green';
  } else {
    $color = 'red';
  }
  return('<p style="color: '.$color.';">'.$str.'</p>');
}

// Function: getPHONEPROVtemplates
//
function getPHONEPROVtemplates($dir) {

  if (! is_dir($dir)) {
    return(FALSE);
  }

  $id = 0;
  foreach (glob($dir.'/*.conf') as $globfile) {
    $templates[$id]['name'] = basename($globfile, '.conf');
    $menu = trim(shell_exec('sed -r -n -e "1,/^\[/ s/^menu_name *=(.*)$/\1/p" "'.$globfile.'" 2>/dev/null'));
    if ($menu === '') {
      $menu = $templates[$id]['name'];
    } elseif (strlen($menu) > 40) {
      $menu = htmlspecialchars(substr($menu, 0, 40)).'&hellip;';
    }
    $templates[$id]['menu'] = $menu;
    $id++;
  }

  if ($id == 0) {
    return(FALSE);
  }
  return($templates);
}

// Function: savePHONEPROVsettings
//
function savePHONEPROVsettings($conf_dir, $conf_file) {

  if (is_dir($conf_dir) === FALSE) {
    return(3);
  }
  if (($fp = @fopen($conf_file,"wb")) === FALSE) {
    return(3);
  }
  fwrite($fp, "### gui.phoneprov.conf - start ###\n###\n");

  $value = 'PHONEPROV_GW_IF="'.$_POST['gw_if'].'"';
  fwrite($fp, $value."\n");

  fwrite($fp, "### gui.phoneprov.conf - end ###\n");
  fclose($fp);
  
  return(11);
}

// Function: phoneprovDBtoDATA
//
function phoneprovDBtoDATA($db) {
  global $MAXNUM;
  $id = 0;

  if (($n = count($db['data'])) > 0) {
    for ($i = 0; $i < $n; $i++) {
      $data[$id]['mac'] = $db['data'][$i]['key'];
      $datatokens = explode(' ', $db['data'][$i]['value']);
      $data[$id]['enabled'] = $datatokens[0];
      $data[$id]['orig_enabled'] = $datatokens[0];
      $data[$id]['template'] = $datatokens[1];
      $exttokens = explode(';', $datatokens[2]);
      for ($j = 0; $j < $MAXNUM; $j++) {
        $ext_cid = explode('/', $exttokens[$j], 2);  // Include any /'s in 'cid'
        $data[$id]['ext'][$j] = (isset($ext_cid[0]) ? $ext_cid[0] : '');
        $data[$id]['cid'][$j] = (isset($ext_cid[1]) ? $ext_cid[1] : '');
      }
      $data[$id]['password'] = $datatokens[3];
      $data[$id]['account'] = (isset($datatokens[4]) ? $datatokens[4] : '');
      $id++;
    }
  }

  // Sort by 1st Extension
  if ($id > 1) {
    foreach ($data as $key => $row) {
      $ext[$key] = $row['ext'][0];
    }
    array_multisort($ext, SORT_ASC, SORT_NUMERIC, $data);
  }

  return($data);
}

// Function: delPHONEPROVmac
//
function delPHONEPROVmac($family, $key) {

  $err = delAstDB($family, $key);

  return($err);
}

// Function: expandPHONEPROVexttext
//
function expandPHONEPROVexttext($data) {

  $str = str_replace(';', ' ', expandPHONEPROVext($data));

  return($str);
}

// Function: expandPHONEPROVext
//
function expandPHONEPROVext($data) {
  global $MAXNUM;
  $str = '';

  for ($j = 0; $j < $MAXNUM; $j++) {
    if ($data['ext'][$j] !== '') {
      if ($str !== '') {
        $str .= ';';
      }
      $str .= $data['ext'][$j];
      if ($data['cid'][$j] !== '') {
        $str .= '/'.$data['cid'][$j];
      }
    }
  }
  return($str);
}

// Function: get_importPHONEPROVfile
//
function get_importPHONEPROVfile() {

  $conf_files = array ('/mnt/kd/massdeployment.conf',
                       '/mnt/kd/webgui-massdeployment.conf',
                       '/mnt/kd/phoneprov/massdeployment.conf');

  $conf_file = '';
  foreach ($conf_files as $value) {
    if (is_file($value)) {
      $conf_file = $value;
      break;
    }
  }
  return($conf_file);
}

// Function: importPHONEPROVfiles
//
function importPHONEPROVfiles($family) {
  $result = 99;

  if (($conf_file = get_importPHONEPROVfile()) === '') {
    return(3);
  }
  if (($fp = @fopen($conf_file, "r")) === FALSE) {
    return(3);
  }
  while (! feof($fp)) {
    if ($line = trim(fgets($fp, 1024))) {
      if ($line[0] !== '#') {
        if (preg_match('/^([^ \t]+)[ \t]+([^ \t]+)[ \t]+([^ \t]+)[ \t]+([^ \t]+)(.*)$/', $line, $tokens)) {
          $enabled = '1';
          $template = $tokens[1];
          $mac = $tokens[2];
          $ext_cid = $tokens[3];
          $password = $tokens[4];
          $account = trim($tokens[5]);
          $result = addPHONEPROVmac($family, $mac, $enabled, $template, $ext_cid, $password, $account);
        }
      }
    }
  }
  fclose($fp);

  return($result);
}

// Function: savePHONEPROVenabled
//
function savePHONEPROVenabled($family, $data) {
  $err = 0;

  if (($n = count($data)) > 0) {
    for ($i = 0; $i < $n; $i++) {
      if ($data[$i]['enabled'] !== $data[$i]['orig_enabled']) {
        $mac = $data[$i]['mac'];
        $enabled = $data[$i]['enabled'];
        $template = $data[$i]['template'];
        $ext_cid = expandPHONEPROVext($data[$i]);
        $password = $data[$i]['password'];
        $account = $data[$i]['account'];
        $err = addPHONEPROVmac($family, $mac, $enabled, $template, $ext_cid, $password, $account);
      }
    }
  }
  return($err);
}

// Function: addPHONEPROVmac
//
function addPHONEPROVmac($family, $key, $enabled, $template, $ext_cid, $password, $account) {
  
  $value = $enabled.' '.$template.' '.$ext_cid.' '.$password;
  if ($account !== '') {
    $value .= ' '.$account;
  }

  $err = putAstDB($family, $key, $value);

  return($err);
}

// Function: generatePHONEPROVfiles
//
function generatePHONEPROVfiles($data, $reload, &$result_str, &$status) {
  $result_str = '';
  $status = 0;
  $conf_file = '/mnt/kd/webgui-massdeployment.conf';

  if (($fp = @fopen($conf_file, 'wb')) === FALSE) {
    $status = 1;
    return(3);
  }
  fwrite($fp, "### AstLinux Web Interface - Phone Provisioning - Mass Deployment ###\n###\n");

  if (($n = count($data)) > 0) {
    for ($i = 0; $i < $n; $i++) {
      if ($data[$i]['enabled'] === '1') {
        $template = $data[$i]['template'];
        $mac = $data[$i]['mac'];
        $ext_cid = expandPHONEPROVext($data[$i]);
        $password = $data[$i]['password'];
        $account = $data[$i]['account'];

        $str = $template.' '.$mac.' '.$ext_cid.' '.$password;
        if ($account !== '') {
          $str .= ' '.$account;
        }
        fwrite($fp, $str."\n");
      }
    }
  }
  fclose($fp);

  $cmd = '/usr/sbin/phoneprov-massdeployment';
  if (! is_file($cmd)) {
    $result_str = 'phoneprov-massdeployment: not found';
    $status = 1;
    return(3);
  }
  if ($reload) {
    $cmd .= ' -f -r ';
  } else {
    $cmd .= ' -f ';
  }
  $cmd .= $conf_file.' 2>&1 1>/dev/null';

  @exec('cd /root;/usr/sbin/gen-rc-conf;'.$cmd, $result_array, $status);
  $result_str = '';
  foreach ($result_array as $value) {
    $result_str .= $value.' ';
  }
  $result_str = trim($result_str);

  return(0);
}

$db = parseAstDB($family);

$data = phoneprovDBtoDATA($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_staff) {
    $result = 999;                                 
  } elseif (isset($_POST['submit_save'])) {
    $disabled = $_POST['disabled'];
    if (($n = count($data)) > 0) {
      for ($i = 0; $i < $n; $i++) {
        $data[$i]['enabled'] = '1';
        if (($m = count($disabled)) > 0) {
          for ($j = 0; $j < $m; $j++) {
            if ($disabled[$j] === $data[$i]['mac']) {
              $data[$i]['enabled'] = '0';
              break;
            }
          }
        }
      }
    }
    $result = savePHONEPROVsettings($PHONEPROVCONFDIR, $PHONEPROVCONFFILE);
    savePHONEPROVenabled($family, $data);

    $mac = strtolower(tuq($_POST['mac']));
    if ($mac !== '') {
      if (preg_match('/^([0-9a-f]{2}:){5}([0-9a-f]{2})$/', $mac)) {
        $template = $_POST['template'];
        if ($template !== '') {
          for ($i = 0; $i < $MAXNUM; $i++) {
            $pdata['ext'][$i] = preg_replace('/[^0-9#*]*([0-9#*]*).*/', '$1', tuq($_POST["ext$i"]));
            $pdata['cid'][$i] = str_replace(' ', '_', tuq($_POST["cid$i"]));
          }
          $ext_cid = expandPHONEPROVext($pdata);
          if ($ext_cid !== '') {
            $enabled = '1';
            if (($m = count($disabled)) > 0) {
              for ($j = 0; $j < $m; $j++) {
                if ($disabled[$j] === $mac) {
                  $enabled = '0';
                  break;
                }
              }
            }
            $password = str_replace(' ', '', tuq($_POST['password']));
            $account = str_replace(' ', '', tuq($_POST['account']));
            if ($password === '') {
              $password = base64_encode(openssl_random_pseudo_bytes(12));
            }
            if (($result = addPHONEPROVmac($family, $mac, $enabled, $template, $ext_cid, $password, $account)) == 0) {
              $result = 11;
            }
          } else {
            $result = 7;
          }
        } else {
          $result = 6;
        }
        if ($result != 11) {
          header('Location: '.$myself.'?key='.rawurlencode($mac).'&result='.$result);
          exit;
        }
      } else {
        $result = 5;                                 
      }
    }
  } elseif (isset($_POST['submit_generate'])) {
    $result = 99;
    if (isset($_POST['confirm_generate'])) {
      generatePHONEPROVfiles($data, isset($_POST['reload_dialplan_sip']), $result_str, $status);
      putACTIONresult($result_str, $status);
      exit;
    } else {
      $result = 2;
    }
  } elseif (isset($_POST['submit_delete'])) {
    $delete = $_POST['delete'];
    for ($i = 0; $i < count($delete); $i++) {
      $result = delPHONEPROVmac($family, $delete[$i]);
    }
  } elseif (isset($_POST['submit_import'])) {
    $result = importPHONEPROVfiles($family);
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
      putHtml('<p style="color: red;">No Action, check "Confirm" for this action.</p>');
    } elseif ($result == 3) {
      putHtml('<p style="color: red;">Error.</p>');
    } elseif ($result == 4 || $result == 1101 || $result == 1102) {
      putHtml('<p style="color: red;">'.asteriskERROR($result).'</p>');
    } elseif ($result == 5) {
      putHtml('<p style="color: red;">Not a valid MAC Address, format ff:ff:ff:ff:ff:ff</p>');
    } elseif ($result == 6) {
      putHtml('<p style="color: red;">No Template is selected.</p>');
    } elseif ($result == 7) {
      putHtml('<p style="color: red;">No Extension(s) are defined.</p>');
    } elseif ($result == 11) {
      putHtml('<p style="color: green;">Settings saved, click "Generate Files" to apply any changed settings.</p>');
    } elseif ($result == 99) {
      putHtml('<p style="color: red;">Action Failed.</p>');
    } elseif ($result == 100 || $result == 101) {
      putHtml(getACTIONresult($result));
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
  if (($phoneprov_base_dir = trim(shell_exec('. /etc/rc.conf; echo "$PHONEPROV_BASE_DIR"'))) === '') {
    $phoneprov_base_dir = '/mnt/kd/phoneprov';
  }

  if (is_file($PHONEPROVCONFFILE)) {
    $vars = parseRCconf($PHONEPROVCONFFILE);
  } else {
    $vars = NULL;
  }

  $ldata['template'] = '';
  for ($i = 0; $i < $MAXNUM; $i++) {
    $ldata['ext'][$i] = '';
    $ldata['cid'][$i] = '';
  }
  $ldata['password'] = '';
  $ldata['account'] = '';

  if (isset($_GET['key'])) {
    $mac = rawurldecode($_GET['key']);
    if (($n = count($data)) > 0) {
      for ($i = 0; $i < $n; $i++) {
        if ($mac === $data[$i]['mac']) {
          $ldata = $data[$i];
          break;
        }
      }
    }
  } else {
    $mac = '';
  }

  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr><td style="text-align: center;" colspan="3">');
  putHtml('<h2>Phone Provisioning - Mass Deployment:</h2>');
  putHtml('</td></tr><tr><td style="text-align: center;">');
if (($templates = getPHONEPROVtemplates("$phoneprov_base_dir/templates")) !== FALSE) {
  putHtml('<input type="submit" class="formbtn" value="Save Changes" name="submit_save" />');

  putHtml('</td><td style="text-align: center;">');
  putHtml('<input type="submit" class="formbtn" value="Generate Files" name="submit_generate" />');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="reload_dialplan_sip" name="reload_dialplan_sip" />&nbsp;Reload Dialplan/SIP');
  putHtml('&ndash;');
  putHtml('<input type="checkbox" value="generate" name="confirm_generate" />&nbsp;Confirm');
  putHtml('</td><td style="text-align: center;">');
  putHtml('<input type="submit" class="formbtn" value="Delete Checked" name="submit_delete" />');
  putHtml('</td></tr>');

  putHtml('<tr><td style="text-align: center;" colspan="3">');
  putHtml('Gateway Interface:');
  $gw_if = getVARdef($vars, 'PHONEPROV_GW_IF');
  putHtml('<select name="gw_if">');
  foreach ($gw_if_menu as $key => $value) {
    $sel = ($gw_if == $key) ? ' selected="selected"' : '';
    putHtml('<option value="'.$key.'"'.$sel.'>'.$value.'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');
  putHtml('</table>');
  
  putHtml('<table width="100%" class="stdtable">');
  putHtml('<tr class="dtrow0"><td width="350">&nbsp;</td><td>&nbsp;</td></tr>');
  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Phone Configuration:</strong>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('MAC Address:');
  putHtml('<input type="text" size="20" maxlength="17" name="mac" value="'.$mac.'" />');
  putHtml('</td><td style="text-align: center;">');
  putHtml('Template:');
  $template = $ldata['template'];
  putHtml('<select name="template">');
  putHtml('<option value="">--- select ---</option>');
  foreach ($templates as $value) {
    $sel = ($template === $value['name']) ? ' selected="selected"' : '';
    putHtml('<option value="'.$value['name'].'"'.$sel.'>['.$value['name'].'] '.$value['menu'].'</option>');
  }
  putHtml('</select>');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: center;" colspan="2">');
  putHtml('Extension(s) / CID Name(s) <i>(optional)</i>:');
  putHtml('</td></tr>');
  putHtml('<tr class="dtrow1"><td style="text-align: center;" colspan="2">');
  for ($i = 0; $i < $MAXNUM; $i++) {
    echo '<input type="text" size="10" maxlength="64" name="ext'.$i.'" value="'.$ldata['ext'][$i].'" />/';
    echo '<input type="text" size="24" maxlength="64" name="cid'.$i.'" value="'.$ldata['cid'][$i].'" />';
    if ((($i + 1) % 2) == 0) {
      putHtml('<br />');
    } else {
      putHtml('&nbsp;&nbsp;');
    }
  }
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow1"><td style="text-align: right;">');
  putHtml('Password:');
  putHtml('<input type="text" size="24" maxlength="128" name="password" value="'.$ldata['password'].'" />');
  putHtml('</td><td style="text-align: center;">');
  putHtml('Account <i>(optional)</i>:');
  putHtml('<input type="text" size="24" maxlength="128" name="account" value="'.$ldata['account'].'" />');
  putHtml('</td></tr>');

  putHtml('<tr class="dtrow0"><td class="dialogText" style="text-align: left;" colspan="2">');
  putHtml('<strong>Phone Database:</strong>');
  putHtml('</td></tr>');
  putHtml('</table>');
  
  putHtml('<table width="100%" class="datatable">');
  putHtml("<tr>");
  
  if (($n = count($data)) > 0) {
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "MAC Address", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Template", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Extensions", "</td>";
    echo '<td class="dialogText" style="text-align: left; font-weight: bold;">', "Password", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Account", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Disabled", "</td>";
    echo '<td class="dialogText" style="text-align: center; font-weight: bold;">', "Delete", "</td>";
    for ($i = 0; $i < $n; $i++) {
      putHtml("</tr>");
      $mac = $data[$i]['mac'];
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td><a href="'.$myself.'?key='.rawurlencode($mac).'" class="actionText">'.$mac.'</a>', '</td>';
      echo '<td>', htmlspecialchars($data[$i]['template']), '</td>';
      echo '<td>', wordwrap(htmlspecialchars(expandPHONEPROVexttext($data[$i])), 10, '<br />', FALSE), '</td>';
      echo '<td>', htmlspecialchars(substr($data[$i]['password'], 0, 6)), '&hellip;', '</td>';
      echo '<td>', htmlspecialchars($data[$i]['account']), '</td>';
      $sel = ($data[$i]['enabled'] === '0') ? ' checked="checked"' : '';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="disabled[]" value="', $mac, '"'.$sel.' />', '</td>';
      echo '<td style="text-align: center;">', '<input type="checkbox" name="delete[]" value="', $mac, '" />', '</td>';
    }
  } else {
    if ($db['status'] == 0) {                                                                    
      echo '<td style="text-align: center;">No Database Entries for: ', $db['family'], '</td>';
      if (($fname = get_importPHONEPROVfile()) !== '') {
        putHtml('</tr><tr><td style="text-align: center;">');
        putHtml('<input type="submit" class="formbtn" value="Import '.htmlspecialchars($fname).'" name="submit_import" /></td>');
      }
    } else {                                                                                     
      echo '<td style="text-align: center; color: red;">', asteriskERROR($db['status']), '</td>';
    }                                    
  }
} else {
  echo '<p style="color: red;">No Templates found in: ', "$phoneprov_base_dir/templates", '</p></td>';  
}
  putHtml("</tr>");
  putHtml("</table>");
  putHtml("</form>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
