<?php

@system("/usr/sbin/astback /tmp/backup.tar.gz", $result);
if ($result != 0)
    {
    include "header.php";
    echo "<center><font color=red>Unable to create backup file.</font></center><BR>";
    include "footer.php";
    }
else
    {
    header('Content-type: application/binary');
    header('Content-Disposition: attachment; filename="backup.tar.gz"');
    $fp = @fopen("/tmp/backup.tar.gz","rb");
    fpassthru($fp);
    fclose($fp);
    }
@system("rm /tmp/backup.tar.gz", $result);
?> 
