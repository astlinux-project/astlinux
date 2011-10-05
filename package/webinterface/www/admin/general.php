<?php
$message = "";

if      ($_POST['submit'] == "Reload")
        {
        @system("/usr/sbin/asterisk -rx reload > /dev/null", $result);
        if ($result == 0)
            $message = "<center><font color=green>Asterisk settings successfully reloaded.</font></center><BR>";
        else
            $message = "<center><font color=red>Asterisk settings failed to reload.</font></center><BR>";
        }
else if ($_POST['submit'] == "Reboot")
        {
        @system("/sbin/reboot", $result);
        if ($result == 0)
            $message = "<center><font color=green>The system is rebooting...</font></center><BR>";
        else
            $message = "<center><font color=red>The system was unable to reboot.</font></center><BR>";
        }
else if ($_POST['submit'] == "Mount Read/Write")
        {
        @system("mount -o rw,remount /", $result);
        if ($result == 0)
            $message = "<center><font color=green>Filesystem is now set to read/write.</font></center><BR>";
        else
            $message = "<center><font color=red>Unable to set filesystem to read/write.</font></center><BR>";
        }
else if ($_POST['submit'] == "Mount Read Only")
        {
        @system("mount -o ro,remount /", $result);
        if ($result == 0)
            $message = "<center><font color=green>Filesystem is now set to read only.</font></center><BR>";
        else
            $message = "<center><font color=red>Unable to set filesystem to read only.</font></center><BR>";
        }
?>


<?php include "header.php"; ?>

Keep in mind that any changes will more than likely not take effect until the next reboot<br>
<br>



<?php
    echo $message;
?>

<center>


<TABLE width="90%" border=0 cellpadding=4>
<TR><TD width="70%" bgcolor="#EEEEEE"><B>Description</B></TD><TD width="30%" bgcolor="#EEEEEE"><B>Execute</B></TD></TR>

<TR><TD valign=top>
<I><U>Reload asterisk settings:</U></I> Before any changes to the Asterisk config files are processed, the
settings must be reloaded.
</TD><TD valign=top bgcolor="#E0E0E0">
<form action="<?=$ScriptName ?>" method="POST"><input name="submit" type="submit" class="button" value="Reload"></form></TD>
</TR>

<TR><TD valign=top>
<I><U>Edit rc.conf:</U></I> The file rc.conf contains descriptive information about the local host
name, configuration details for any potential network interfaces and which services should be started
up at system initial boot time.
</TD><TD valign=top bgcolor="#E0E0E0">
<a href="dir.php?edit=/etc/rc.conf&dir=/etc/">Edit rc.conf</a>
</TD></TR>

<TR><TD valign=top>
<I><U>View phpinfo():</U></I> Outputs a large amount of information about the current state of PHP.
This includes information about PHP compilation options and extensions, the PHP version, server
information and environment (if compiled as a module), the PHP environment, OS version information,
paths, master and local values of configuration options, HTTP headers, and the PHP License.
</TD><TD valign=top bgcolor="#E0E0E0">
<a href="phpinfo.php">phpinfo()</a>
</TD></TR>

<TR><TD valign=top>
<I><U>Backup:</U></I> Backup key files used in AstLinux, including all the files contained in the
Asterisk configuration, rc.conf, and others.  Output is in the form of a tar'd gzipped archive, for
storage on your local machine.
</TD><TD valign=top bgcolor="#E0E0E0">
<a href="backup.php">Backup</a>
</TD></TR>

<TR><TD valign=top>
<I><U>Change Password:</U></I> Change the password used to access the web administration interface.
Keep in mind that this does <B>NOT</B> change the console password, only the web interface.
</TD><TD valign=top bgcolor="#E0E0E0">
<a href="password.php">Change Password</a>
</TD></TR>

<TR><TD valign=top>
<I><U>Reboot:</U></I> Reboot the entire AstLinux system.  While not necessary, it never hurts to reboot
the system to make sure everything is dialed in after a hard day of making changes.
</TD><TD valign=top bgcolor="#E0E0E0">
<form action="<?=$ScriptName ?>" method="POST"><input name="submit" type="submit" class="button" value="Reboot"></form></TD>
</TR>

<?php
$UNIONFS=exec( "cat /proc/cmdline |grep -q asturw" );
if ($UNIONFS == 0)
 ;

else

  echo "
<TR><TD valign=top>
<I><U>Mount Read/Write:</U></I> By default the boot filesystem (hda1) is mounted as read only, to ensure
a long life for the compact flash based boot drive.  If any changes need to be made to the boot filesystem,
the drive must first be mounted as read/write.
</TD><TD valign=top bgcolor=\"#E0E0E0\">
<form action=\"<?=$ScriptName ?>\" method=\"POST\"><input name=\"submit\" type=\"submit\" class=\"button\" value=\"Mount Read/Write\"></form>
<form action=\"<\?=$ScriptName \?>\" method=\"POST\"><input name=\"submit\" type=\"submit\" class=\"button\" value=\"Mount Read Only\"></form></TD>
</TR>
";
?>

</table>

</center>

<?php include "footer.php"; ?>


