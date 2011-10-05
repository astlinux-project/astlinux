<?php

    function box_header($width, $title)
    {
        $fullsize = $width + 13;
        echo "<TABLE cellSpacing=0 cellPadding=0 width=".$fullsize." border=0>";
        echo "<TR>";
        echo "    <TD colspan=2 bgcolor=#A0A0A0><B><font color=#FFFFFF>&nbsp;&nbsp;".$title."</FONT></B></TD>";
        echo "    <TD background=\"graphics/box-topright.gif\" width=8><img src=\"graphics/blank.gif\" width=1 height=14></TD>";
        echo "</TR>";
        echo "<TR>";
        echo "    <TD background=\"graphics/box-left.gif\" width=5></TD>";
        echo "    <TD width=".$width."><BR>";
    }

    function box_footer()
    {
        echo "    </td>";
        echo "    <TD background=\"graphics/box-right.gif\" width=8></TD>";
        echo "</TR>";
        echo "<TR>";
        echo "    <TD colspan=2 background=\"graphics/box-bottomleft.gif\"><img src=\"graphics/blank.gif\" width=1 height=14></TD>";
        echo "    <TD background=\"graphics/box-bottomright.gif\"></TD>";
        echo "</tr>";
        echo "</TABLE>";
    }

?>

<HTML>
<HEAD>
    <META http-equiv="Content-Style-Type" content="text/css">
    <link href="default.css" rel="stylesheet" type="text/css">
    <title>AstLinux Web Admin</title>
</HEAD>
<body bgcolor="#ffffff" leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" class="astlinux">

<TABLE cellSpacing=0 cellPadding=0 width=760 border=0>
<TR>
    <TD>
    <a href="http://www.astlinux.org">
    <img src="graphics/logo.jpg" width=600 height=120 border=0>
    </a>
    </TD>
</TR>
<TR>
    <TD>

        <TABLE cellSpacing=0 cellPadding=0 width=740 border=0>
        <TR>
            <TD bgcolor="#FFFFFF" width=10></td>
            <TD bgcolor="#A9A9A9" width=128><img src="graphics/blank.gif" width=1 height=20></td>
            <TD bgcolor="#000000" width=7></td>
            <TD bgcolor="#000000" width=445 align=right>
<B><font color="#666666">Filesystem:</font>&nbsp;&nbsp;
<?php
    $UNIONFS=exec( "cat /proc/cmdline | grep -q asturw" );
    if ($UNIONFS == 0)
        echo "<font color=#00DD00>Unionfs</font>";
    else
        if (is_writable("/var/www/admin/header.php"))
            echo "<font color=#DD0000>Read/Write</font>";
        else
            echo "<font color=#00DD00>Read Only</font>";
?>
</B>&nbsp;&nbsp;
            </td>
            <TD bgcolor="#FFFFFF" width=150></td>
        </tr>
        <tr>
            <TD bgcolor="#FFFFFF" width=10></td>
            <TD colspan=2 width=135 align=left valign=top>
                <TABLE cellSpacing=0 cellPadding=0 width=135 border=0>
                <TR>
                    <TD background="graphics/box-left.gif" width=5></TD>
                    <TD width=122><BR>

                    <B>Asterisk:</B><BR>
&nbsp;&nbsp;<a href="dir.php?dir=/etc/asterisk/">Config</a><BR>
&nbsp;&nbsp;<a href="asterisk.php">Shell</a><BR>
&nbsp;&nbsp;<a href="log.php">Log</a><BR>
<BR>

<B>Boot:</B><BR>
&nbsp;&nbsp;<a href="dir.php?dir=/tftpboot/">TFTP</a><BR>
<?
if (file_exists("/home/ftp")) {
echo "&nbsp;&nbsp;<a href=\"dir.php?dir=/home/ftp/\">FTP</a><BR>";}
?>
<BR>

<B>General:</B><BR>
&nbsp;&nbsp;<a href="about.php">About</a><BR>
&nbsp;&nbsp;<a href="credits.php">Credits</a><BR>
&nbsp;&nbsp;<a href="index.php">Status</a><BR>
&nbsp;&nbsp;<a href="network.php">Network</a><BR>
<?
if (file_exists("nistnet.php")) {
echo "&nbsp;&nbsp;<a href=\"nistnet.php\">WanSimulator</a><BR>";}
?>
&nbsp;&nbsp;<a href="exec.php">Shell</a><BR>
&nbsp;&nbsp;<a href="general.php">Setup</a><BR>
<BR>

                    </td>
                    <TD background="graphics/box-right.gif" width=8></TD>
                </TR>
                <TR>
                    <TD colspan=2 background="graphics/box-bottomleft.gif"><img src="graphics/blank.gif" width=1 height=14></TD>
                    <TD background="graphics/box-bottomright.gif"></TD>
                </tr>
                </TABLE>
            </td>
            <TD colspan=2 width=595 bgcolor="#FFFFFF" align=left valign=top><BR>

                    <TABLE cellSpacing=0 cellPadding=0 width="100%" border=0>
                    <TR>
                        <TD width="1%"></TD>
                        <TD align=left valign=top>

