<?php session_manual_gc();

// Copyright (C) 2008-2023 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// header.php for AstLinux
// 03-25-2008
// 07-20-2009, Add manual session garbage collection
// 03-04-2011, Add custom tab support
// 07-29-2013, Use charset 'utf-8' instead of 'iso-8859-1'
// 10-29-2019, Add Wiki tab and link

// Function: getCUSTOMtabs
//
function getCUSTOMtabs($g_prefs) {
  if (($cmd = getPREFdef($g_prefs, 'custom_tab_list_cmdstr')) !== '') {
    $id = 0;
    $tabtokens = explode('~', $cmd);
    foreach ($tabtokens as $tabs) {
      if ($tabs !== '') {
        $options = explode(',', $tabs);
        if (isset($options[0], $options[1])) {
          $list[$id]['href'] = trim($options[0]);
          $list[$id]['title'] = htmlspecialchars($options[1]);
          $list[$id]['access'] = isset($options[2]) ? trim($options[2]) : 'all';
          $id++;
        }
      }
    }
  }
  return(isset($list) ? $list : NULL);
}

// Function: getTITLEname
//
function getTITLEname($g_prefs) {
  if (($cmd = getPREFdef($g_prefs, 'title_name_cmdstr')) === '') {
    $cmd = 'AstLinux Management';
  }
  return(htmlspecialchars($cmd));
}

// Function: url_localhost_handler
//
function url_localhost_handler($str) {
  if (strpos($str, '://localhost') !== FALSE) {
    $host = $_SERVER['HTTP_HOST'];
    if (($pos = strpos($host, ':')) !== FALSE && strpos($str, '://localhost:') !== FALSE) {
      $host = substr($host, 0, $pos);
    }
    $str = str_replace('://localhost', '://'.$host, $str);
  }
  return($str);
}

// Function: ssh_localhost_handler
//
function ssh_localhost_handler($str) {
  if (strpos($str, '@localhost') !== FALSE) {
    $host = $_SERVER['HTTP_HOST'];
    if (($pos = strpos($host, ':')) !== FALSE) {
      $host = substr($host, 0, $pos);
    }
    $str = str_replace('@localhost', '@'.$host, $str);
  }
  return($str);
}

// Function: getURLlink
//
function getURLlink($g_prefs) {
  if (($cmd = getPREFdef($g_prefs, 'external_url_link_cmdstr')) !== '') {
    $cmd = url_localhost_handler($cmd);
  }
  return(htmlspecialchars($cmd));
}

// Function: getURLname
//
function getURLname($g_prefs) {
  if (($cmd = getPREFdef($g_prefs, 'external_url_name_cmdstr')) === '') {
    $cmd = 'Help';
  }
  return(htmlspecialchars($cmd));
}

// Function: getWIKIlink
//
function getWIKIlink($g_prefs, $g_staff) {
  if (($cmd = getPREFdef($g_prefs, 'external_wiki_link_cmdstr')) !== '') {
    if ((! $g_staff) && (strpos($cmd, '://localhost') !== FALSE)) {
      $cmd = '';
    } else {
      $cmd = url_localhost_handler($cmd);
    }
  }
  return(htmlspecialchars($cmd));
}

// Function: getCLIlink
//
function getCLIlink($g_prefs) {
  if (($cmd = getPREFdef($g_prefs, 'external_cli_link_cmdstr')) !== '') {
    if (strncmp($cmd, 'ssh://', 6) == 0) {
      $cmd = ssh_localhost_handler($cmd);
    } else {
      $cmd = url_localhost_handler($cmd);
    }
  }
  return(htmlspecialchars($cmd));
}

// Function: getFOP2link
//
function getFOP2link($g_prefs) {
  $cmd = '';
  if (is_addon_package('fop2')) {
    if (getPREFdef($g_prefs, 'external_fop2_https') === 'yes') {
      $cmd = 'https://localhost/fop2/';
    } else {
      $cmd = 'http://localhost/fop2/';
    }
    $cmd = url_localhost_handler($cmd);
  }
  return(htmlspecialchars($cmd));
}

// Function: putUSERerror
//
function putUSERerror($user, $tab) {
  putHtml('<center>');
  putHtml('<p style="color: red;">User "'.$user.'" does not have permission to access the "'.$tab.'" tab.</p>');
  putHtml('<p style="color: red;">The User can be changed by closing/restarting your browser.</p>');
  putHtml('</center>');
  putHtml('</body>');
  putHtml('</html>');
}

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<!-- Copyright (C) 2008-2023 Lonnie Abelbeck -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
<meta http-equiv="Expires" content="0" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<?php
    $status = (getPREFdef($global_prefs, 'status_require_auth') === 'yes') ? '/admin/status.php' : '/status.php';
    $directory = (getPREFdef($global_prefs, 'directory_require_auth') === 'yes') ? '/admin/directory.php' : '/directory.php';
    $tabname = getTABname();

    putHtml('<title>'.getTITLEname($global_prefs).'</title>');
    putHtml('<link rel="stylesheet" href="/common/style.css" type="text/css" />');
    putHtml('<!--[if IE 7]><link rel="stylesheet" href="/common/ie7.css" type="text/css" /><![endif]-->');
    if (isset($_GET['count_down_secs'])) {
      putHtml('<script language="JavaScript" type="text/javascript">');
      putHtml('//<![CDATA[');
      putHtml('var count_down_secs = '.$_GET['count_down_secs'].';');
      putHtml('var timer = setInterval("timerInterval();", 1000);');
      putHtml('function timerInterval() {');
      putHtml('if (count_down_secs > 0) { count_down_secs--; }');
      putHtml('document.getElementById("count_down").innerHTML = count_down_secs;');
      putHtml('if (count_down_secs == 0) {');
      putHtml('clearInterval(timer);');
      if (isset($_GET['setup'])) {
        putHtml('top.location.href="/admin/setup.php";');
      } elseif (isset($_GET['shutdown'])) {
        putHtml('alert(\'Shutdown is complete.\');');
        putHtml('top.location.href="about:blank";');
      } else {
        putHtml('top.location.href="'.$status.'";');
      }
      putHtml('}');
      putHtml('}');
      putHtml('//]]>');
      putHtml('</script>');
    }
    putHtml('</head>');
    putHtml('<body>');

    putHtml('<table class="headerTable"><tr>');
    putHtml('<td width="140"><img src="/common/logo-small.gif" width="113" height="23" alt="AstLinux" /></td>');
    putHtml('<td><h1>'.getTITLEname($global_prefs).'</h1></td>');
    $URLlink = getURLlink($global_prefs);
    $WIKIlink = getWIKIlink($global_prefs, $global_staff);
    $CLIlink = getCLIlink($global_prefs);
    $FOP2link = getFOP2link($global_prefs);
    if ($URLlink !== '' || $WIKIlink !== '' || ($global_admin && $CLIlink !== '') || $FOP2link !== '') {
      putHtml('<td style="text-align: right;">');
      if ($URLlink !== '') {
        putHtml('<a href="'.$URLlink.'" class="headerText" target="_blank">'.getURLname($global_prefs).'</a>');
      }
      if ($WIKIlink !== '') {
        putHtml('<a href="'.$WIKIlink.'" class="headerText" target="_blank">Wiki</a>');
      }
      if ($global_admin && $CLIlink !== '') {
        if (strncmp($CLIlink, 'ssh://', 6) == 0) {
          putHtml('<a href="'.$CLIlink.'" class="headerText">CLI</a>');
        } else {
          putHtml('<a href="'.$CLIlink.'" class="headerText" target="_blank">CLI</a>');
        }
      }
      if ($FOP2link !== '') {
        putHtml('<a href="'.$FOP2link.'" class="headerText" target="_blank">FOP2</a>');
      }
      putHtml('</td>');
    }
    putHtml('</tr></table>');
    putHtml('<div id="tabs">');
    putHtml('<ul>');
    if ($tabname === 'setup') {
      putHtml('<li><a href="/admin/setup.php"><span>Setup</span></a></li>');
    } else {
      putHtml('<li><a href="'.$status.'"><span>Status</span></a></li>');
      if (getPREFdef($global_prefs, 'tab_directory_show') === 'yes') {
        putHtml('<li><a href="'.$directory.'"><span>Directory</span></a></li>');
      }
      if ((! $global_staff_disable_voicemail) && getPREFdef($global_prefs, 'tab_voicemail_show') === 'yes') {
        putHtml('<li><a href="/admin/voicemail.php"><span>Voicemail</span></a></li>');
      }
      if ((! $global_staff_disable_monitor) && getPREFdef($global_prefs, 'tab_monitor_show') === 'yes') {
        putHtml('<li><a href="/admin/monitor.php"><span>Monitor</span></a></li>');
      }
      if ((! $global_staff_disable_followme) && getPREFdef($global_prefs, 'tab_followme_show') === 'yes') {
        putHtml('<li><a href="/admin/followme.php"><span>Follow-Me</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_meetme_show') === 'yes')) {
        putHtml('<li><a href="/admin/meetme.php"><span>MeetMe</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_confbridge_show') === 'yes')) {
        putHtml('<li><a href="/admin/confbridge.php"><span>ConfBridge</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_cdrlog_show') !== 'no')) {
        putHtml('<li><a href="/admin/cdrlog.php"><span>CDR&nbsp;Log</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_sysdial_show') !== 'no')) {
        putHtml('<li><a href="/admin/sysdial.php"><span>Speed&nbsp;Dial</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_cidname_show') !== 'no')) {
        putHtml('<li><a href="/admin/cidname.php"><span>Caller*ID</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_blacklist_show') !== 'no')) {
        putHtml('<li><a href="/admin/blacklist.php"><span>Blacklist</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_whitelist_show') === 'yes')) {
        putHtml('<li><a href="/admin/whitelist.php"><span>Whitelist</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_actionlist_show') === 'yes')) {
        putHtml('<li><a href="/admin/actionlist.php"><span>Actionlist</span></a></li>');
      }
      if (($global_admin || $global_staff_enable_sqldata) && (getPREFdef($global_prefs, 'tab_sqldata_show') === 'yes')) {
        putHtml('<li><a href="/admin/sqldata.php"><span>SQL-Data</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_ldapab_show') === 'yes')) {
        putHtml('<li><a href="/admin/ldapab.php"><span>LDAP-AB</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_phoneprov_show') === 'yes')) {
        putHtml('<li><a href="/admin/phoneprov.php"><span>PhoneProv</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_users_show') === 'yes')) {
        putHtml('<li><a href="/admin/users.php"><span>Users</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_vnstat_show') === 'yes')) {
        putHtml('<li><a href="/admin/vnstat.php"><span>vnStat</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_netstat_show') === 'yes')) {
        putHtml('<li><a href="/admin/netstat.php"><span>NetStat</span></a></li>');
      }
      if ($global_staff_enable_dnshosts) {
        putHtml('<li><a href="/admin/dnshosts.php"><span>DNS Hosts</span></a></li>');
      }
      if ($global_staff_enable_xmpp) {
        putHtml('<li><a href="/admin/xmpp.php"><span>XMPP Users</span></a></li>');
      }
      if (! is_null($custom_tabs = getCUSTOMtabs($global_prefs))) {
        foreach ($custom_tabs as $tab) {
          if ($tab['access'] === 'all' || ($global_staff && $tab['access'] === 'staff')
                                       || ($global_admin && $tab['access'] === 'admin')) {
            putHtml('<li><a href="'.$tab['href'].'"><span>'.$tab['title'].'</span></a></li>');
          }
        }
      }
      if ($global_admin && (getPREFdef($global_prefs, 'tab_monit_show') === 'yes')) {
        putHtml('<li><a href="/admin/monit.php"><span>Monit</span></a></li>');
      }
      if ($global_admin && (getPREFdef($global_prefs, 'tab_network_show') !== 'no')) {
        putHtml('<li><a href="/admin/network.php"><span>Network</span></a></li>');
      }
      if ($global_admin && (getPREFdef($global_prefs, 'tab_edit_show') !== 'no')) {
        putHtml('<li><a href="/admin/edit.php"><span>Edit</span></a></li>');
      }
      if (($global_admin || $global_staff_enable_cli) && (getPREFdef($global_prefs, 'tab_cli_show') === 'yes')) {
        putHtml('<li><a href="/admin/cli.php"><span>CLI</span></a></li>');
      }
      if ($global_admin && (getPREFdef($global_prefs, 'tab_fossil_show') === 'yes')) {
        putHtml('<li><a href="/admin/fossil.php"><span>Fossil</span></a></li>');
      }
      if ($global_admin && (getPREFdef($global_prefs, 'tab_prefs_show') !== 'no')) {
        putHtml('<li><a href="/admin/prefs.php"><span>Prefs</span></a></li>');
      }
      if ($global_admin) {
        putHtml('<li><a href="/admin/system.php"><span>System</span></a></li>');
      } elseif ($global_user === 'staff' && (! $global_staff_disable_staff)) {
        putHtml('<li><a href="/admin/staff.php"><span>Staff</span></a></li>');
      }
      if ($global_staff && (getPREFdef($global_prefs, 'tab_wiki_show') === 'yes')) {
        putHtml('<li><a href="/admin/wiki.php"><span>Wiki</span></a></li>');
      }
    }
    putHtml('</ul>');
    putHtml('</div>');
    putHtml('<br /><br />');

    // non-staff and non-admin
    if (! ($global_staff ||
      $ACCESS_RIGHTS === 'all' ||
      $ACCESS_RIGHTS === 'non-staff')) {
      putUSERerror($global_user, $tabname);
      exit;
    }
    // staff
    if (! ($global_admin ||
      $ACCESS_RIGHTS === 'all' ||
      $ACCESS_RIGHTS === 'staff')) {
      putUSERerror($global_user, $tabname);
      exit;
    }
?>
