<?php session_start();

// Copyright (C) 2008-2016 Lonnie Abelbeck
// This is free software, licensed under the GNU General Public License
// version 3 as published by the Free Software Foundation; you can
// redistribute it and/or modify it under the terms of the GNU
// General Public License; and comes with ABSOLUTELY NO WARRANTY.

// cdrlog.php for AstLinux
// 03-28-2008
// 04-13-2008, Add user defined logfile location
// 06-04-2008, Add Darrick Hartman's extra columns w/ Prefs option
// 06-04-2008, Change CDR.csv to not be a binary image of Master.csv
// 06-06-2008, Add filter by date for download CDR.csv
// 08-19-2008, Add search text option
// 08-19-2008, Add standard cdr-csv, default cdr-custom and special cdr-custom
// 10-02-2008, Add optional last column CDR value
// 07-20-2009, Add David Kerr's code and ideas for multiple page support
// 02-13-2010, Add multiple *.csv CDR Database suport
// 06-22-2015, Add "Export CDR.csv" button
//
// cdr_custom.conf Master.csv definition for "Special cdr-custom" option
// Master.csv => "${CDR(start)}","${CDR(clid)}","${CDR(dst)}","${CDR(dcontext)}","${CDR(billsec)}"
//

$myself = $_SERVER['PHP_SELF'];

require_once '../common/functions.php';

if (($CDRLOGFILE = getPREFdef($global_prefs, 'cdrlog_log_file_cmdstr')) === '') {
  $CDRLOGFILE = '/var/log/asterisk/cdr-csv/Master.csv';
}

// Function: getCDRdatabases
//
function getCDRdatabases() {
  global $CDRLOGFILE;

  $path = dirname($CDRLOGFILE);

  foreach (glob($path.'/*.csv') as $globfile) {
    if (is_file($globfile)) {
      $files[] = $globfile;
    }
  }
  return($files);
}

// Function: getMAPlast
//
function getMAPlast($default, $last) {
  if ($last === '') {
    return(FALSE);
  }
  $def = ($default === 'yes');
  switch ($last) {
  case 'disposition':
    return($def ? 13 : 14);
  case 'channel':
    return($def ? 4 : 5);
  case 'dstchannel':
    return($def ? 5 : 6);
  case 'lastapp':
    return($def ? 6 : 7);
  case 'lastdata':
    return($def ? 7 : 8);
  case 'amaflags':
    return($def ? 14 : 15);
  case 'accountcode':
    return($def ? 15 : 0);
  case 'uniqueid':
    return($def ? 16 : 16);
  case 'userfield':
    return($def ? 17 : 17);
  }
  return(FALSE);
}

// Function: mapCDRvalues
//
function mapCDRvalues($default, $extra, $last) {
  if ($default === 'special') {
    $map['time'] = 0;
    $map['cid'] = 1;
    $map['ext'] = 2;
    $map['context'] = 3;
    $map['billsec'] = 4;
    $map['commasafe'] = 5;
  } elseif ($default === 'yes') {
    $map['time'] = 8;
    $map['cid'] = 0;
    $map['ext'] = 2;
    $map['context'] = 3;
    $map['billsec'] = 12;
    if ($extra === 'yes') {
      $map['channel'] = 4;
      $map['dstchannel'] = 5;
      $map['disposition'] = 13;
    }
    if (($n = getMAPlast($default, $last)) !== FALSE) {
      $map["$last"] = $n;
    }
    $map['commasafe'] = 8;
  } else {
    $map['time'] = 9;
    $map['cid'] = 4;
    $map['ext'] = 2;
    $map['context'] = 3;
    $map['billsec'] = 13;
    if ($extra === 'yes') {
      $map['channel'] = 5;
      $map['dstchannel'] = 6;
      $map['disposition'] = 14;
    }
    if (($n = getMAPlast($default, $last)) !== FALSE) {
      $map["$last"] = $n;
    }
    $map['commasafe'] = 9;
  }
  return($map);
}

// Function: splitCIDfields
//
function splitCIDfields($cid) {

  if (($pos = strrpos($cid, ' <')) !== FALSE) {
    $cidtokens['name'] = trim(substr($cid, 0, $pos), ' "');
    $cidtokens['num'] = trim(substr($cid, $pos), ' <>');
  } else {
    $cidtokens['name'] = trim($cid, ' ');
    $cidtokens['num'] = $cidtokens['name'];
  }
  return($cidtokens);
}

// Function: exportCDRline
//
function exportCDRline($data) {

  $str  =  '"'.$data['time'].'"';
  $str .= ',"'.$data['cidname'].'"';
  $str .= ',"'.$data['cidnum'].'"';
  $str .= ',"'.$data['ext'].'"';
  $str .= ',"'.$data['context'].'"';
  if (isset($data['channel'])) {
    $str .= ',"'.$data['channel'].'"';
  }
  if (isset($data['dstchannel'])) {
    $str .= ',"'.$data['dstchannel'].'"';
  }
  if (isset($data['disposition'])) {
    $str .= ',"'.$data['disposition'].'"';
  }
  $str .= ',"['.secs2hourminsec($data['billsec']).']"';
  $str .= ',"'.$data['billsec'].'"';

  if (isset($data['lastapp'])) {
    $str .= ',"'.$data['lastapp'].'"';
  }
  if (isset($data['lastdata'])) {
    $str .= ',"'.$data['lastdata'].'"';
  }
  if (isset($data['amaflags'])) {
    $str .= ',"'.$data['amaflags'].'"';
  }
  if (isset($data['accountcode'])) {
    $str .= ',"'.$data['accountcode'].'"';
  }
  if (isset($data['uniqueid'])) {
    $str .= ',"'.$data['uniqueid'].'"';
  }
  if (isset($data['userfield'])) {
    $str .= ',"'.$data['userfield'].'"';
  }

  return($str);
}

// Function: parseCDRline
//
function parseCDRline($line, $format, $match, $map) {
  $i = $map['commasafe'];
  $linetokens = explode('",', $line, ($i + 1));
  if (isset($linetokens[$i])) {
    $rtokens = explode(',', $linetokens[$i]);
    foreach ($rtokens as $v) {
      $linetokens[$i] = $v;
      $i++;
    }
  }

  if (($lmatch = strlen($match)) > 0) {
    $date = trim($linetokens[$map['time']], '"');
    if (strncmp($date, $match, $lmatch)) {
      return(FALSE);
    }
  }

  if ($format === 'raw') {
    return($line);
  }

  $cid = trim($linetokens[$map['cid']], '"');
  $cidtokens = splitCIDfields($cid);
  $str  =  '"'.trim($linetokens[$map['time']], '"').'"';
  $str .= ',"'.$cidtokens['name'].'"';
  $str .= ',"'.$cidtokens['num'].'"';
  $str .= ',"'.trim($linetokens[$map['ext']], '"').'"';
  $str .= ',"'.trim($linetokens[$map['context']], '"').'"';
  if (isset($map['channel'])) {
    $str .= ',"'.trim($linetokens[$map['channel']], '"').'"';
  }
  if (isset($map['dstchannel'])) {
    $str .= ',"'.trim($linetokens[$map['dstchannel']], '"').'"';
  }
  if (isset($map['disposition'])) {
    $str .= ',"'.trim($linetokens[$map['disposition']], '"').'"';
  }
  $str .= ',"['.secs2hourminsec(trim($linetokens[$map['billsec']], '"')).']"';
  $str .= ',"'.trim($linetokens[$map['billsec']], '"').'"';
  if (isset($map['lastapp'])) {
    $str .= ',"'.trim($linetokens[$map['lastapp']], '"').'"';
  }
  if (isset($map['lastdata'])) {
    $str .= ',"'.trim($linetokens[$map['lastdata']], '"').'"';
  }
  if (isset($map['amaflags'])) {
    $str .= ',"'.trim($linetokens[$map['amaflags']], '"').'"';
  }
  if (isset($map['accountcode'])) {
    $str .= ',"'.trim($linetokens[$map['accountcode']], '"').'"';
  }
  if (isset($map['uniqueid'])) {
    $str .= ',"'.trim($linetokens[$map['uniqueid']], '"').'"';
  }
  if (isset($map['userfield'])) {
    $str .= ',"'.trim($linetokens[$map['userfield']], '"').'"';
  }

  return($str);
}

// Function: parseCDRlog
//
function parseCDRlog(&$db, $match, $key, $map, $default, $extra, $last, $databases) {
  global $CDRLOGFILE;

  if ($db['logfileBase'] === $CDRLOGFILE && $databases === 'yes') {
    $logfile = $db['logfileNext'];
  } else {
    $logfile = $CDRLOGFILE;
  }
  $nlines = $db['dbLoadLengthNext'];

  $lastmtime = @filemtime($logfile);
  if (isset($db['lastmtime'])) {
    if ($db['logfile'] === $logfile &&
        $db['lastmtime'] == $lastmtime &&
        $db['dbLoadLength'] == $nlines &&
        $db['match'] === $match &&
        $db['key'] === $key &&
        $db['default'] === $default &&
        $db['extra'] === $extra &&
        $db['last'] === $last) {
      return;
    }
  }
  $db['logfileBase'] = $CDRLOGFILE;
  $db['logfileNext'] = $logfile;
  $db['logfile'] = $logfile;
  $db['lastmtime'] = $lastmtime;
  $db['dbLoadLength'] = $nlines;
  $db['match'] = $match;
  $db['key'] = $key;
  $db['default'] = $default;
  $db['extra'] = $extra;
  $db['last'] = $last;

  $id = 0;
  unset($db['data']);
  $db['totalCDRrecords'] = (int)trim(shell_exec('wc -l '.$logfile));
  $db['displayStart'] = 0;
  $db['sortedby'] = 'none';
  $tmpfile = tempnam("/tmp", "PHP_");
  if ($match !== '') {
    if (strpos($match, '|') !== FALSE) {
      $cmd = '';
      foreach (explode('|', $match) as $token) {
        if ($token !== '') {
          $cmd .= '-e "'.$token.'" ';
        }
      }
      if ($cmd !== '') {
        $cmd = 'grep -i '.$cmd;
      } else {
        $cmd = 'cat ';
      }
      @exec($cmd.$logfile.' | tail -n '.$nlines.' >'.$tmpfile);
    } elseif (strpos($match, '&') !== FALSE) {
      $cmd = 'cat '.$logfile.' | ';
      foreach (explode('&', $match) as $token) {
        if ($token !== '') {
          $cmd .= 'grep -i -e "'.$token.'" | ';
        }
      }
      @exec($cmd.'tail -n '.$nlines.' >'.$tmpfile);
    } else {
      @exec('grep -i -e "'.$match.'" '.$logfile.' | tail -n '.$nlines.' >'.$tmpfile);
    }
  } else {
    @exec('tail -n '.$nlines.' '.$logfile.' >'.$tmpfile);
  }
  $ph = @fopen($tmpfile, "r");
  while (! feof($ph)) {
    if (($line = trim(fgets($ph, 1024))) !== '') {
      $i = $map['commasafe'];
      $linetokens = explode('",', $line, ($i + 1));
      if (isset($linetokens[$i])) {
        $rtokens = explode(',', $linetokens[$i]);
        foreach ($rtokens as $v) {
          $linetokens[$i] = $v;
          $i++;
        }
      }
      $k['time'] = trim($linetokens[$map['time']], '"');
      $cid = trim($linetokens[$map['cid']], '"');
      $cidtokens = splitCIDfields($cid);
      $k['cidname'] = $cidtokens['name'];
      $k['cidnum'] = $cidtokens['num'];
      $k['ext'] = trim($linetokens[$map['ext']], '"');
      if (isset($map['channel'])) {
        $k['channel'] = trim($linetokens[$map['channel']], '"');
      }
      if ($key !== '' && $match !== '') {
        if (stristr($k["$key"], $match) === FALSE) {
          continue;
        }
      }

      $db['data'][$id]['time'] = $k['time'];
      $db['data'][$id]['cidname'] = $k['cidname'];
      $db['data'][$id]['cidnum'] = $k['cidnum'];
      $db['data'][$id]['ext'] = $k['ext'];
      $db['data'][$id]['context'] = trim($linetokens[$map['context']], '"');
      $db['data'][$id]['billsec'] = trim($linetokens[$map['billsec']], '"');
      if (isset($map['channel'])) {
        $db['data'][$id]['channel'] = $k['channel'];
      }
      if (isset($map['dstchannel'])) {
        $db['data'][$id]['dstchannel'] = trim($linetokens[$map['dstchannel']], '"');
      }
      if (isset($map['disposition'])) {
        $db['data'][$id]['disposition'] = trim($linetokens[$map['disposition']], '"');
      }
      if (isset($map['lastapp'])) {
        $db['data'][$id]['lastapp'] = trim($linetokens[$map['lastapp']], '"');
      }
      if (isset($map['lastdata'])) {
        $db['data'][$id]['lastdata'] = trim($linetokens[$map['lastdata']], '"');
      }
      if (isset($map['amaflags'])) {
        $db['data'][$id]['amaflags'] = trim($linetokens[$map['amaflags']], '"');
      }
      if (isset($map['accountcode'])) {
        $db['data'][$id]['accountcode'] = trim($linetokens[$map['accountcode']], '"');
      }
      if (isset($map['uniqueid'])) {
        $db['data'][$id]['uniqueid'] = trim($linetokens[$map['uniqueid']], '"');
      }
      if (isset($map['userfield'])) {
        $db['data'][$id]['userfield'] = trim($linetokens[$map['userfield']], '"');
      }
      $id++;
    }
  }
  fclose($ph);
  @unlink($tmpfile);
}

// Function: sortCDRdb
//
function sortCDRdb(&$db, $sortby) {

  if (count($db['data']) > 1) {
    if ($db['sortedby'] !== $sortby) {
      if ($sortby === 'time' || $sortby === 'billsec') {
        $sortorder = SORT_DESC;
      } else {
        $sortorder = SORT_ASC;
      }
      if ($sortby === 'billsec') {
        $sorttype = SORT_NUMERIC;
      } else {
        $sorttype = SORT_STRING;
      }

      if ($sortby === 'time' ||
          $sortby === 'cidnum' ||
          $sortby === 'ext' ||
          $sortby === 'billsec') {
        foreach ($db['data'] as $key => $row) {
          $sort[$key] = $row[$sortby];
        }
      } else {
        foreach ($db['data'] as $key => $row) {
          $sort[$key] = strtolower($row[$sortby]);
        }
      }
      array_multisort($sort, $sortorder, $sorttype, $db['data']);

      $db['sortedby'] = $sortby;
    }
  }
  $db['displayStart'] = 0;
}

// Function: putCDRheader
//
function putCDRheader($sortby, $title) {
  global $myself;

  return('<a href="'.$myself.'?sortcolumnby='.$sortby.'" class="headerText" title="Column Sort by: '.$sortby.'">'.$title.'</a>');

}

// Function: getCIDnumHtml
//
function getCIDnumHtml($cmd, $disable, $cidnum, $cidname) {

  if ($disable) {
    return(htmlspecialchars($cidnum));
  }
  if (isset($cidnum[0]) && $cidnum[0] === '+') {
    $num = substr($cidnum, 1);
    if (! preg_match("/$cmd/", $num)) {
      if (isset($num[0]) && $num[0] === '1') {
        $num = substr($num, 1);
        if (! preg_match("/$cmd/", $num)) {
          return(htmlspecialchars($cidnum));
        }
      } else {
        return(htmlspecialchars($cidnum));
      }
    }
  } elseif (preg_match("/$cmd/", $cidnum)) {
    $num = $cidnum;
  } else {
    return(htmlspecialchars($cidnum));
  }
  $name = ($cidname !== '') ? '&amp;name='.rawurlencode($cidname) : '';
  return('<a href="/admin/cidname.php?num='.$num.$name.'" class="tnumText">'.$cidnum.'</a>');
}

// Function: getDATEval
//
function getDATEval($when, $offset, &$title) {
  $days = 3600 * 24;
  $now = time();
  if ($when === 'today') {
    if ($offset == 0) {
      $val = date('Y-m-d', $now);
      $title = 'Today';
    } elseif ($offset == 1) {
      $val = date('Y-m-d', $now - $days);
      $title = 'Yesterday';
    } else {
      $val = date('Y-m-d', $now - ($offset * $days));
      $title = date('l', $now - ($offset * $days));
    }
  } elseif ($when === 'month') {
    if ($offset == 0) {
      $val = date('Y-m', $now);
      $title = 'Current Month';
    } else {
      $current = date('Y-m', $now);
      if ($current === ($val = date('Y-m', $now - (28 * $days)))) {
        if ($current === ($val = date('Y-m', $now - (29 * $days)))) {
          if ($current === ($val = date('Y-m', $now - (30 * $days)))) {
            $val = date('Y-m', $now - (31 * $days));
          }
        }
      }
      $title = 'Previous Month';
    }
  } elseif ($when === 'year') {
    if ($offset == 0) {
      $val = date('Y', $now);
      $title = 'Current Year';
    } else {
      $current = date('Y', $now);
      if ($current === ($val = date('Y', $now - (365 * $days)))) {
        $val = date('Y', $now - (366 * $days));
      }
      $title = 'Previous Year';
    }
  } else {
    $val = '';
    $title = 'All Dates';
  }

  return($val);
}

// We use PHP session variables to save our CDR database between page views. This means we don't
// have to keep reloading and sorting from the master csv file.  We use reference variable (&) to
// avoid copying the database, thereby saving memory and improving performance.
if (! isset($_SESSION['db'])) {
  $_SESSION['db']['logfileBase'] = $CDRLOGFILE;
  $_SESSION['db']['logfileNext'] = $CDRLOGFILE;
  $_SESSION['db']['logfile'] = $CDRLOGFILE;
  $_SESSION['db']['dbLoadLengthNext'] = 500;
  $_SESSION['db']['displayLength'] = 100;
}
$db = &$_SESSION['db'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = 1;
  if (! $global_staff) {
    $result = 999;
  } elseif (isset($_POST['submit_cdrlog'])) {
    if (isset($_POST['databases'])) {
      $db['logfileNext'] = $_POST['databases'];
    }
    if (isset($_POST['max_dbload'], $_POST['page_length'])) {
      $db['dbLoadLengthNext'] = (int)$_POST['max_dbload'];
      $db['displayLength'] = (int)$_POST['page_length'];
      $db['displayStart'] = 0;
    }
    if (isset($_POST['list_type_val'])) {
      $search = tuq($_POST['list_type_val']);
      $search = trim($search, ' |&"');
      if ($search === '') {
        $result = 0;
      } elseif ($_POST['list_type'] === 'search') {
        header('Location: '.$myself.'?search='.rawurlencode($search));
        exit;
      } elseif ($_POST['list_type'] === 'all') {
        $search = str_replace(' ', '&', $search);
        $search = str_replace('|', '&', $search);
        header('Location: '.$myself.'?search='.rawurlencode($search));
        exit;
      } elseif ($_POST['list_type'] === 'any') {
        $search = str_replace(' ', '|', $search);
        $search = str_replace('&', '|', $search);
        header('Location: '.$myself.'?search='.rawurlencode($search));
        exit;
      } else {
        $key = $_POST['list_type'];
        header('Location: '.$myself.'?search='.rawurlencode($search).'&key='.$key);
        exit;
      }
    }
  } elseif (isset($_POST['submit_export'])) {
    if (($n = count($db['data'])) > 0) {
      $search = isset($_POST['current_search']) ? $_POST['current_search'] : '';
      $key = isset($_POST['current_key']) ? $_POST['current_key'] : '';
      $name = 'CDR'.(($key === '') ? '' : '-'.$key).(($search === '') ? '' : '-'.rawurlencode($search)).'.csv';
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="'.$name.'"');
      header('Content-Transfer-Encoding: binary');
      ob_clean();
      flush();
      for ($i = 0; $i < $n; $i++) {
        echo exportCDRline($db['data'][$i]), "\n";
      }
      exit;
    } else {
      $result = 0;
    }
  } elseif (isset($_POST['submit_backup'])) {
    if (($fp = @fopen($db['logfile'],"rb")) === FALSE) {
      $result = 5;
    } else {
      $format = $_POST['format_dl'];
      $match = $_POST['match_date'];
      $name = ($match === '') ? 'CDR.csv' : 'CDR-'.$match.'.csv';
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="'.$name.'"');
      header('Content-Transfer-Encoding: binary');
      ob_clean();
      flush();
      $default = getPREFdef($global_prefs, 'cdrlog_default_format');
      $extra = getPREFdef($global_prefs, 'cdrlog_extra_show');
      $last = (getPREFdef($global_prefs, 'cdrlog_last_show') === 'yes') ? getPREFdef($global_prefs, 'cdrlog_last_cmd') : '';
      $map = mapCDRvalues($default, $extra, $last);
      while (! feof($fp)) {
        if (($line = trim(fgets($fp, 1024))) !== '') {
          if (($line = parseCDRline($line, $format, $match, $map)) !== FALSE) {
            echo $line, "\n";
          }
        }
      }
      fclose($fp);
      exit;
    }
  }
  header('Location: '.$myself.'?result='.$result);
  exit;
} else { // Start of HTTP GET
$ACCESS_RIGHTS = 'staff';
require_once '../common/header.php';

  if (($number_format = getPREFdef($global_prefs, 'number_format_cmdstr')) === '') {
    $number_format = '^[2-9][0-9][0-9][2-9][0-9][0-9][0-9][0-9][0-9][0-9]$';
  }
  $staff_flag = ($global_user === 'staff' && getPREFdef($global_prefs, 'tab_cidname_show') === 'no');
  $default = getPREFdef($global_prefs, 'cdrlog_default_format');
  $databases = getPREFdef($global_prefs, 'cdrlog_databases_show');
  $extra = getPREFdef($global_prefs, 'cdrlog_extra_show');
  $last = (getPREFdef($global_prefs, 'cdrlog_last_show') === 'yes') ? getPREFdef($global_prefs, 'cdrlog_last_cmd') : '';
  $map = mapCDRvalues($default, $extra, $last);

  if (isset($_GET['search'])) {
    $search = tuq(rawurldecode($_GET['search']));
    if (isset($_GET['key'])) {
      $fkey = $_GET['key'];
    } else {
      $fkey = '';
    }
  } else {
    $search = '';
    $fkey = '';
  }

  if (isset($_GET['previous_page'], $db['lastmtime'])) {
    $db['displayStart'] = max( $db['displayStart'] - $db['displayLength'], 0);
  } elseif (isset($_GET['next_page'], $db['lastmtime'])) {
    if (($db['displayStart'] + $db['displayLength']) < count($db['data'])) {
      $db['displayStart'] += $db['displayLength'];
    }
  } elseif (isset($_GET['start_page_at'], $db['lastmtime'])) {
    $startat = (int)$_GET['start_page_at'];
    if ($startat < 0) {
      $db['displayStart'] = 0;
    } elseif ($startat >= count($db['data'])) {
      $db['displayStart'] = max( count($db['data']) - $db['displayLength'], 0);
    } else {
      $db['displayStart'] = $startat;
    }
  } elseif (isset($_GET['sortcolumnby'], $db['lastmtime'])) {
    sortCDRdb($db, $_GET['sortcolumnby']);
  } else {
    parseCDRlog($db, $search, $fkey, $map, $default, $extra, $last, $databases);
    sortCDRdb($db, 'time');
  }

  $n = count($db['data']);
  if (($start = min( $n, $db['displayStart'])) < 0) {
    $start = 0;
  }
  $count = $db['displayLength'];
  $end = min( $n, $start + $count);

  $info_str = 'Viewing '.($n > 0 ? ($start+1).'-'.$end.' of ' : '').$n.' selected records; ';
  $info_str .= ($db['match'] !== '') ? 'Matching "'.htmlspecialchars($db['match']).'"' : $db['totalCDRrecords'].' CDR\'s in database.';

  putHtml('<center>');
  if (isset($_GET['result'])) {
    $result = $_GET['result'];
    if ($result == 1) {
      putHtml('<p style="color: orange;">No Action.</p>');
    } elseif ($result == 5) {
      putHtml('<p style="color: red;">Download Failed.</p>');
    } elseif ($result == 999) {
      putHtml('<p style="color: red;">Permission denied for user "'.$global_user.'".</p>');
    } else {
      putHtml('<p style="color: green;">'.$info_str.'</p>');
    }
  } else {
    putHtml('<p style="color: green;">'.$info_str.'</p>');
  }
  putHtml('</center>');
?>
  <center>
  <table class="layout"><tr><td><center>
  <form method="post" action="<?php echo $myself;?>">
  <table width="100%" class="stdtable">
  <tr><td style="text-align: center;">
  <h2>Display Call Detail Records:</h2>
  </td><td style="text-align: center;">
  <h2>Download Call Detail Records:</h2>
  </td></tr><tr><td class="dialogText" style="text-align: center;">
<?php
if ($databases === 'yes') {
  $database_files = getCDRdatabases();
  if (count($database_files) > 1) {
    putHtml('Database:');
    putHtml('<select name="databases">');
    foreach ($database_files as $value) {
      $sel = ($db['logfile'] === $value) ? ' selected="selected"' : '';
      putHtml('<option value="'.$value.'"'.$sel.'>'.basename($value).'</option>');
    }
    putHtml('</select>');
    putHtml('<br />');
  }
}
putHtml('Load');
putHtml('<select name="max_dbload">');
$max_dbload_label = array( '100', '500', '1000', '2000', '3000', '4000', '5000');
foreach ($max_dbload_label as $value) {
  $sel = ($db['dbLoadLengthNext'] == (int)$value) ? ' selected="selected"' : '';
  putHtml('<option value="'.$value.'"'.$sel.'>'.$value.'</option>');
  if (isset($db['totalCDRrecords'])) {
    if ($db['totalCDRrecords'] <= (int)$value) {
      break;
    }
  }
}
putHtml('</select>');

putHtml('CDR\'s from database &ndash;');
putHtml('<select name="page_length">');
$page_length_label = array( '10', '25', '50', '100', '250', '500');
foreach ($page_length_label as $value) {
  $sel = ($db['displayLength'] == (int)$value) ? ' selected="selected"' : '';
  putHtml('<option value="'.$value.'"'.$sel.'>'.$value.'</option>');
}
putHtml('</select>');
putHtml('per page<br />');

putHtml('<select name="list_type">');
$sel = ($search !== '' && $fkey === '') ? ' selected="selected"' : '';
putHtml('<option value="search"'.$sel.'>Search For Text:</option>');
putHtml('<option value="all">Match All Words:</option>');
putHtml('<option value="any">Match Any Words:</option>');
$sel = ($fkey === 'time') ? ' selected="selected"' : '';
putHtml('<option value="time"'.$sel.'>Match Date-Time:</option>');
$sel = ($fkey === 'cidname') ? ' selected="selected"' : '';
putHtml('<option value="cidname"'.$sel.'>Match CID Name:</option>');
$sel = ($fkey === 'cidnum') ? ' selected="selected"' : '';
putHtml('<option value="cidnum"'.$sel.'>Match CID Num:</option>');
$sel = ($fkey === 'ext') ? ' selected="selected"' : '';
putHtml('<option value="ext"'.$sel.'>Match Extension:</option>');
$sel = ($fkey === 'channel') ? ' selected="selected"' : '';
putHtml('<option value="channel"'.$sel.'>Match Src Channel:</option>');
putHtml('</select>');
putHtml('<input type="text" value="'.htmlspecialchars($search).'" size="18" maxlength="64" name="list_type_val" />');
putHtml('<input type="hidden" name="current_search" value="'.htmlspecialchars($search).'" />');
putHtml('<input type="hidden" name="current_key" value="'.$fkey.'" />');

putHtml('</td><td style="text-align: center;">');
putHtml('<select name="format_dl">');
putHtml('<option value="" selected="selected">Formatted</option>');
putHtml('<option value="raw">Raw CDR</option>');
putHtml('</select>');

putHtml('<select name="match_date">');
putHtml('<option value="" selected="selected">All Dates</option>');

echo '<option value="'.getDATEval('today', 0, $sel).'">';
putHtml($sel.'</option>');
echo '<option value="'.getDATEval('today', 1, $sel).'">';
putHtml($sel.'</option>');
echo '<option value="'.getDATEval('today', 2, $sel).'">';
putHtml($sel.'</option>');
echo '<option value="'.getDATEval('today', 3, $sel).'">';
putHtml($sel.'</option>');
echo '<option value="'.getDATEval('today', 4, $sel).'">';
putHtml($sel.'</option>');
echo '<option value="'.getDATEval('today', 5, $sel).'">';
putHtml($sel.'</option>');
echo '<option value="'.getDATEval('today', 6, $sel).'">';
putHtml($sel.'</option>');
echo '<option value="'.getDATEval('month', 0, $sel).'">';
putHtml($sel.'</option>');
echo '<option value="'.getDATEval('month', 1, $sel).'">';
putHtml($sel.'</option>');
echo '<option value="'.getDATEval('year', 0, $sel).'">';
putHtml($sel.'</option>');
echo '<option value="'.getDATEval('year', 1, $sel).'">';
putHtml($sel.'</option>');
?>
  </select>
  </td></tr><tr><td style="text-align: center;">
  <input type="submit" value="Refresh CDR Display" name="submit_cdrlog" />
  <input type="submit" value="Export CDR.csv" name="submit_export" />
  </td><td style="text-align: center;">
  <input type="submit" value="Download CDR.csv" name="submit_backup" />
  </td></tr>
  </table>
  </form>
<?php

  $n = count($db['data']);
  if (($start = min( $n, $db['displayStart'])) < 0) {
    $start = 0;
  }
  $count = $db['displayLength'];
  $end = min( $n, $start + $count);
  $npages = (int)ceil($n / $count);

  if ($npages > 1) {
    echo '<div class="pagination">';
    echo '<ul>';
    echo '<li>';
    if ($start == 0) {
      echo '<a class="prevnext disablelink">&lt;&lt;&nbsp;previous</a></li>';
    } else {
      echo '<a href="',$myself,'?previous_page=true','" class="prevnext" >&lt;&lt;&nbsp;previous</a></li>';
    }
    $curpage = ($start / $count);
    // Front block (first 2 pages)
    $i = 0;
    while ($i < min( 2, $npages)) {
      echo '<li><a ',(($i == $curpage) ? 'class="currentpage"' : 'href="'.$myself.'?start_page_at='.$i*$count.'" '),'>',1+$i++,'</a></li>';
    }
    // middle block (current page +/- three pages
    if ($i < ($curpage - 3)) {
      $block2start = max( $i, min( $curpage - 3, $npages - 9));
      if ($block2start > $i) {
        echo '...';
      }
      $i = $block2start;
    }
    $block2end = min( $i+7, $npages);
    while ($i < $block2end) {
      echo '<li><a ',(($i == $curpage) ? 'class="currentpage"' : 'href="'.$myself.'?start_page_at='.$i*$count.'" '),'>',1+$i++,'</a></li>';
    }
    // back block (last two pages)
    if ($i < ($npages - 2)) {
      echo '...';
      $i = $npages - 2;
    }
    while ($i < $npages) {
      echo '<li><a ',(($i == $curpage) ? 'class="currentpage"' : 'href="'.$myself.'?start_page_at='.$i*$count.'" '),'>',1+$i++,'</a></li>';
    }
    if ($end == $n) {
      echo '<li><a class="prevnext disablelink">next&nbsp;&gt;&gt;</a></li>';
    } else {
      echo '<li><a href="',$myself,'?next_page=true','" class="prevnext" >next&nbsp;&gt;&gt;</a></li>';
    }
    putHtml('</ul></div>');
  }

  putHtml('<table width="100%" class="datatable">');
  putHtml("<tr>");

  if ($start < $end) {
    echo '<td style="text-align: center;">', putCDRheader('time', 'Date - Time'), "</td>";
    echo '<td style="text-align: left;">', putCDRheader('cidname', 'CID Name'), "</td>";
    echo '<td style="text-align: left;">', putCDRheader('cidnum', 'CID Num'), "</td>";
    echo '<td style="text-align: left;">', putCDRheader('ext', 'Extension'), "</td>";
    echo '<td style="text-align: left;">', putCDRheader('context', 'Context'), "</td>";
    if ($default !== 'special' && $extra === 'yes') {
      echo '<td style="text-align: left;">', putCDRheader('channel', 'Source Channel'), "</td>";
      echo '<td style="text-align: left;">', putCDRheader('dstchannel', 'Dest. Channel'), "</td>";
      echo '<td style="text-align: left;">', putCDRheader('disposition', 'Disposition'), "</td>";
    }
    echo '<td style="text-align: left;">', putCDRheader('billsec', 'Duration'), "</td>";
    if ($default !== 'special' && $last !== '') {
      echo '<td style="text-align: left;">', putCDRheader($last, ucfirst($last)), "</td>";
    }
    for ($i = $start; $i < $end; $i++) {
      putHtml("</tr>");
      echo '<tr ', ($i % 2 == 0) ? 'class="dtrow0"' : 'class="dtrow1"', '>';
      echo '<td>', $db['data'][$i]['time'], '</td>';
      echo '<td>', htmlspecialchars($db['data'][$i]['cidname']), '</td>';
      echo '<td>', getCIDnumHtml($number_format, $staff_flag, $db['data'][$i]['cidnum'], $db['data'][$i]['cidname']), '</td>';
      echo '<td>', $db['data'][$i]['ext'], '</td>';
      echo '<td>', $db['data'][$i]['context'], '</td>';
      if ($default !== 'special' && $extra === 'yes') {
        echo '<td>', $db['data'][$i]['channel'], '</td>';
        echo '<td>', $db['data'][$i]['dstchannel'], '</td>';
        echo '<td>', $db['data'][$i]['disposition'], '</td>';
      }
      echo '<td>', secs2hourminsec($db['data'][$i]['billsec']), '</td>';
      if ($default !== 'special' && $last !== '') {
        echo '<td>', htmlspecialchars($db['data'][$i]["$last"]), '</td>';
      }
    }
  } else {
    echo '<td style="text-align: center;">No Log Entries for file: ', $db['logfile'], '</td>';
  }
  putHtml("</tr>");
  putHtml("</table>");
  putHtml("</center></td></tr></table>");
  putHtml("</center>");
} // End of HTTP GET
require_once '../common/footer.php';

?>
