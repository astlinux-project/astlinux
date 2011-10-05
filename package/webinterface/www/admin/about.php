<?php include "header.php"; ?>

AstLinux was originally created by Kristian Kielhofner. Over the years
contributors from all over the world have worked together to make AstLinux
what it is today. Please check the credits page for more information and 
thank you for using AstLinux!<BR>
<BR>
For support, please join the AstLinux-Users mailing list at 
<a href="http://sourceforge.net/mail/?group_id=170462">SourceForge.net.</a>
<BR><BR>
<?php
    echo "<table border=0 width=100%><tr><td align=center>";

    box_header(100, "AstLinux");
    echo "<center>";

    system("cat /etc/astlinux-release");

    echo "</center><BR>";
    box_footer();

    echo "</td><td align=center>";

    box_header(200, "Linux Kernel");
    echo "<center>";

    system("uname -a");

    echo "</center><BR>";
    box_footer();

    echo "</td><td align=center>";

    box_header(150, "Asterisk");
    echo "<center>";

    system("/usr/sbin/asterisk -V");

    echo "</center><BR>";
    box_footer();

    echo "</td></tr></table>";

?>

<br>

If you find AstLinux useful, please consider making a donation. <br>
I work very hard on making AstLinux all that it can be, and compensation never hurts :) <br>
<br>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
</form>

<?php include "footer.php"; ?>



