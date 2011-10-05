<?php include "header.php"; ?>

<?php

    $submit = $_POST['submit'];
    $host   = $_POST['host'];
    $ip     = $_SERVER['REMOTE_ADDR'];
    $self   = $_SERVER['PHP_SELF'];
    $count  = 4;

    echo "This server's current IP address is ".$ip.".<BR>";
    echo "<form method=\"post\" action=\"".$self."\">";
    echo "Enter IP address or domain name <input type=\"text\" name=\"host\" value=\"".$host."\"></input>";
    echo "<input type=\"submit\" name=\"submit\" value=\"Ping\"></input>";
    echo "<input type=\"submit\" name=\"submit\" value=\"Trace\"></input>";
    echo "</form>";


    if ($submit == "Ping")
        {
        $host = preg_replace ("/[^A-Za-z0-9.-]/","",$host);
        echo("Ping:");
        echo "<pre>";

        $ps = "ping -c ".$count." ".$host;

        system($ps);
        echo "</pre>";
        }
    if ($submit == "Trace")
        {
        $host = preg_replace ("/[^A-Za-z0-9.-]/","",$host);
        echo("Traceroute:");
        echo "<pre>";

        $ps = "traceroute -m 30 -w 2 ".$host;

        system($ps);
        echo "</pre>";
        }

?>

<?php include "footer.php"; ?>


