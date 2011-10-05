<?php include "header.php"; ?>

<?php

    function isBlank( $arg ) { return ereg( "^\s*$", $arg ); }

    echo "<form method=\"post\" action=\"".$self."\">";

    $search = $_POST['search'];

    if (!isBlank($_POST['returns']))
        $returns = $_POST['returns'];
    else
        $returns = 20;
    if ($returns > 500)
        $returns = 500;

    $submit = $_POST['submit'];

    echo "Number of entries to display: <input type=\"text\" name=\"returns\" value=\"".$returns."\"></input>";
    echo "<input type=\"submit\" name=\"submit\" value=\"List\"></input><br>";
    echo "Search string: <input type=\"text\" name=\"search\" value=\"".$search."\"></input>";
    echo "<input type=\"submit\" name=\"submit\" value=\"Search\"></input><br>";
?>

    </form>

<?php

    if (($submit == "Search") && (!isBlank($search)))
        {
        $command = "grep ".$search." /var/log/asterisk/cdr-csv/Master.csv | tail -n ".$returns."";
        box_header(550, "Search results (max ".$returns." returns)");
        }
    else
        {
        $command = "tail -n ".$returns." /var/log/asterisk/cdr-csv/Master.csv";
        box_header(550, "Call history (last ".$returns." calls)");
        }

    putenv("PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin");
    putenv("SCRIPT_FILENAME=".$command);  /* PHP scripts */
    $handle = popen($command, "r" );

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE)
        {
        $num = count($data);
        $row++;

/*
    0  Account
    1  Source
    2  Destination
    3  Context
    4  CallerID
    5  Source Channel
    6  Destination Channel
    7  Last App
    8  Last Data
    9  Call Date
    10 Call Pickup
    11 Call Hangup
    12 Duration
    13 Billable
    14 Status
    15 User

*/

        echo "<table border=0 cellPadding=0 cellSpacing=2 width=100%>";
        echo "<tr><td width=20%><b>Time:</b></td><td width=40%>".$data[9]." start</td><td width=40%>".$data[11]." end</td></tr>";
        echo "<tr><td><b>Duration:</b></td><td>".$data[12]." total</td><td>".$data[13]." billable</td></tr>";
        echo "<tr><td><b>Status:</b></td><td>".$data[14]."</td><td><b>".$data[3]."</b></td></tr>";
        echo "<tr><td><b>Source:</b></td><td>".$data[1]."</td><td>".$data[5]."</td></tr>";
        echo "<tr><td><b>Destination:</b></td><td>".$data[2]."</td><td>".$data[6]."</td></tr>";
        echo "</table><br>";
        }
    pclose($handle);

    box_footer();

?>

<?php include "footer.php"; ?>















