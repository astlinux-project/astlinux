<?php

include "header.php";

// Function: is Blank
// Returns true or false depending on blankness of argument.

function isBlank( $arg ) { return ereg( "^\s*$", $arg ); }

?>

<form action="<?=$HTTP_SERVER_VARS['SCRIPT_NAME']; ?>" method="POST" enctype="multipart/form-data" name="frmExecPlus">
  <table>
    <tr>
      <td class="label" align="right">Command:</td>
      <td class="type"><input name="txtCommand" type="text" size="70" value="<?=htmlspecialchars($_POST['txtCommand']);?>"></td>
    </tr>
    <tr>
      <td valign="top">   </td>
      <td valign="top" class="label">
         <input type="submit" class="button" value="Execute">
      </td>
    </tr>
    <tr>
      <td height="8"></td>
      <td></td>
    </tr>
  </table>
</form>

<p>
<?php if (isBlank($_POST['txtCommand'])): ?>
</p>
<?php endif; ?>
<?php if ($ulmsg) echo "<p><strong>" . $ulmsg . "</strong></p>\n"; ?>
<?php

if (!isBlank($_POST['txtCommand']))
   {
   echo "<pre>";
   putenv("TERM=vt100");
   putenv("PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin");
   putenv("SCRIPT_FILENAME=" . strtok(stripslashes($_POST['txtCommand']), " "));  /* PHP scripts */
   $ph = popen(stripslashes("asterisk -rx \"" . $_POST['txtCommand'] . "\""), "r" );
   while ($line = fgets($ph))
      echo htmlspecialchars($line);
   pclose($ph);
   echo "</pre>";
   }

?>

<?php
include "footer.php";
?>










