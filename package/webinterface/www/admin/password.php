<?php include "header.php"; ?>

<?
    $UNIONFS=exec( "cat /proc/cmdline |grep -q asturw" );                       
    if (isset($_POST['password1'])) $password1 = $_POST['password1'];
    if (isset($_POST['password2'])) $password2 = $_POST['password2'];

    if (isset($password1))
        {
        if ($password1 == $password2)
            {
            $jumble = md5(time() . getmypid()); 
            $salt = substr($jumble, 0, 2); 
            $htpasswd_text .= "admin:".crypt($password1, $salt)."\n";


            if ($UNIONFS == 1)
            @system("mount -o rw,remount /", $result);

            if (($fp = @fopen("/var/www/admin/.htpasswd","wb")) != NULL)
                {
                fwrite($fp, $htpasswd_text);
                fclose($fp);

                echo "<center><font color=green>Web password changed!</font></center><BR>";
                }
            else
                {
                echo "<center><font color=red>Unable to change web password.</font></center><BR>";
                }

            if ($UNIONFS == 1)
            @system("mount -o ro,remount /", $result);
            }
        else
            {
            echo "<center><font color=red>Passwords do not match.</font></center><BR>";
            }
        }

?>

<CENTER>
<FORM METHOD="POST" ACTION="<? echo $PHP_SELF; ?>">
<table>
<tr><td>Password:</td><td><INPUT TYPE="PASSWORD" NAME="password1"></td></tr>
<tr><td>Password again:</td><td><INPUT TYPE="PASSWORD" NAME="password2"></td></tr>
<tr><td><INPUT type=submit name="submit" VALUE="Change">
</FORM>
</td></tr>
</table>
</center>

<?php include "footer.php"; ?>

