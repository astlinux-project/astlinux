<?php include "header.php"; ?>

Welcome to the AstLinux <B><I>VERY</I></B> Basic Web Interface.<br>
<br>

<CENTER>
<TABLE border=0>
<TR><TD>
<?php
    function linuxUptime()
        {
        $ut = strtok( exec( "cat /proc/uptime" ), "." );
        $days = sprintf( "%2d", ($ut/(3600*24)) );
        $hours = sprintf( "%2d", ( ($ut % (3600*24)) / 3600) );
        $min = sprintf( "%2d", ($ut % (3600*24) % 3600)/60 );
        $sec = sprintf( "%2d", ($ut % (3600*24) % 3600)%60 );
        return array( $days, $hours, $min, $sec );
        }

    box_header(250, "Server uptime");

    $uptime = linuxUptime();
    echo "<center>";
    if ($uptime[0] >= 1)
        {
        echo $uptime[0]." day";
        if ($uptime[0] != 1)
            echo "s";
        echo " ";
        }

    if ($uptime[1] >= 1)
        {
        echo $uptime[1]." hour";
        if ($uptime[1] != 1)
            echo "s";
        echo " ";
        }

    echo $uptime[2]." min";
    if ($uptime[2] != 1)
        echo "s";
    echo " ";

    echo $uptime[3]." sec";
    if ($uptime[3] != 1)
        echo "s";
    echo " ";

    echo "</center><BR>";
    box_footer();

    echo "</td><td>";


    box_header(250, "Server load");
    echo "<center>";

    $data = shell_exec('cat /proc/loadavg');
    $dataArray = explode(' ', $data);

    $procArray = explode('/', $dataArray[3]);
    echo "<table border=0 width=100%><tr><td align=center>1 min avg</td><td align=center>5 min avg</td><td align=center>15 min avg</td></tr>";
    echo "<tr><td align=center>".$dataArray[0]."</td><td align=center>".$dataArray[1]."</td><td align=center>".$dataArray[2]."</td></tr>";

    echo "</table></center><BR>";
    box_footer();

?>
</TD></TR>
</TABLE>

<?php
    function checkd($daemon,$name)
    {
        $ps ="ps | grep ".$daemon." | grep -v grep | wc -l";
        $origps = exec($ps);
        echo "<TR><TD><B>" . $name . "</B></TD>";

        if ($origps < 1)
            {
            echo "<TD><font color=red>DOWN</font></TD>";
            echo "<TD>0</TD>";
            }
        else
            {
            echo "<TD><font color=green>UP</font></TD>";
            echo "<TD>" . $origps . "</TD>";
            }
    }

    box_header(500, "Service status");

    echo "<TABLE width=\"90%\">";
    echo "<TR><TD>Service</TD><TD>Status</TD><TD>Process Count</TD></TR>";

    echo checkd("asterisk","Asterisk");
    echo checkd("mini_httpd","HTTP Daemon");
    echo checkd("sshd","SSH Daemon");
    echo checkd("ntpd","NTP Daemon");
    echo checkd("dnsmasq","DNSMasq Daemon");
    echo checkd("inetd","Inet Daemon");
    echo checkd("syslogd","Syslog Daemon");

    echo "</table>";

    box_footer();

?>


<BR>


<?php

    box_header(550, "Drive usage");

echo "<TABLE border=0 width=100%><TR><TD>Drive</TD><TD align=right>Size</TD><TD align=right>Used</TD><TD align=right>Avail</TD><TD align=right>Percent</TD><TD>Mount</TD></TR>";

exec ("df -h", $x);
$count = 1;
while ($count < sizeof($x))
     {
     list($drive, $size, $used, $avail, $percent, $mount) = split(" +", $x[$count]);

     echo "<TR><TD>".$drive."</TD><TD align=right>".$size."</TD><TD align=right>".$used."</TD><TD align=right>".$avail."</TD><TD align=right>".$percent."</TD><TD>".$mount."</TD><TR>";

     $count++;
     }

    echo "</table>";

    box_footer();
?>

</CENTER>


<?php include "footer.php"; ?>

















