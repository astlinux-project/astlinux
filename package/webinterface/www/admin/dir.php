<?php

require("dirphp_class.php");

// CREATE AN INSTANCE OF DirPHP TO WORK WITH.
$dirphp = new DirPHP("m/d/y", $header, $footer);

// THIS FUNCTION DOES ALL THE WORK
$dirphp->handle_events();

?>

