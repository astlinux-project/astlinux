<?php
/************************************************************ GENERAL INFO ***\
| DirPHP version 1.0                                                          |
| Created by Stuart Montgomery as a simple solution for                       |
| easy directory printing and uploading.                                      |
|*****************************************************************************|
| Copyright 2004 Stuart Montgomery                                            |
| GNU General Public License notice:                                          |
|                                                                             |
|   This program is free software; you can redistribute it and/or modify      |
|   it under the terms of the GNU General Public License as published by      |
|   the Free Software Foundation; either version 2 of the License, or         |
|   (at your option) any later version.                                       |
|                                                                             |
|   This program is distributed in the hope that it will be useful,           |
|   but WITHOUT ANY WARRANTY; without even the implied warranty of            |
|   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
|   GNU General Public License for more details.                              |
|                                                                             |
|   You should have received a copy of the GNU General Public License         |
|   along with this program; if not, write to the Free Software               |
|   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
|                                                                             |
| Place this file on any PHP capable server to view its containing            |
| directory's contents and upload to it (if allowed by server).  There        |
| are several configurable options to control all functions of the script     |
| to customize it to your needs.  The script is written as a PHP class        |
| and should be called from another PHP file.  Refer to the documentation     |
| (dirphp_readme.htm) for installation and configuration instructions.        |
|                                                                             |
| http://scripts.ensitehosting.com/                                           |
| stuart@ensitehosting.com                                                    |
\*****************************************************************************/

class DirPHP {

	var $version;
	var $dir;
	var $use_dir;
	var $parent_dir;
	var $date_format;
	var $msg;

    function DirPHP($date_format, $time_limit = 300)
    {
		$this->version = "1.0";
		$this->date_format = $date_format;
        $use_dir = $this->find_use_dir();
		error_reporting(E_ALL ^ E_NOTICE);  // Turn of annoying error notices  (comment-out line for debugging)
        set_time_limit($time_limit);
        if ($use_dir == FALSE) {
			$this->dir = opendir(".");
			$this->use_dir = "";
		} else {
			$this->dir = opendir($use_dir);
			$this->use_dir = $use_dir;
		}
		array_pop($dirtree = explode("/", $this->use_dir));
		$num_levels = count($dirtree) - 1;
		$this->parent_dir = "?dir=";
		for ($i = 0; $i < $num_levels; $i++) {
			$this->parent_dir .= $dirtree[$i]."/";
		}
		if ($this->parent_dir == "?dir=") $this->parent_dir = "";
	} // End of DirPHP function (class constructor)

    function display_header()
    {
        include "header.php";
        $header  = "Current Path: <B>" . $this->find_use_dir() . "</B><br><BR>";

        // For editing file page
        if (isset($_GET['edit']) && is_file($_GET['edit'])) {
            $header .= "     <span style=\"font-style: italic; color: green\">Editing:</span> " . $_GET['edit'] . "</a>      <a href=\"" . $_SERVER['PHP_SELF'];
			if (isset($_GET['dir']))
				$header .= "?dir=" . $_GET['dir'];
            $header .= "\">&lt; Return to directory</a>";
		}
		$header .= "\n";
		echo $header;
    }

    function display_footer()
    {
        include "footer.php";
	}

    function find_use_dir()
    {
		if (isset($_GET['dir'])) $use_dir = $_GET['dir'];
		if (isset($_POST['dir'])) $use_dir = $_POST['dir'];
		if (!isset($use_dir)) {
			$use_dir = FALSE;
		}
        return $use_dir;
    }

    function display_dir()
    {
		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
        echo "<tr class=\"header\"><td class=\"file\" style=\"padding: 2px\">File</td><td width=\"90\" class=\"file-property\">Size</td><td width=\"78\" class=\"file-property\">Modified</td></tr>\n";
		//  Show parent directory link, if applicable
        if ($this->use_dir != "")
            echo "<tr height=\"24\"><td colspan=\"3\" class=\"file\" align=\"left\"><a href=\"" .$_SERVER['PHP_SELF'] . $this->parent_dir . "\"><span class=\"directory\">&gt;&nbsp;.. (parent)</span></a></td></tr>";
		//  Show error message
		$this->show_msg();
		//  Begin directory read loop
		while (($file = readdir($this->dir)) != FALSE) {
			$filename = $file;  // $filename will be unedited, while $file will have $use_dir tacked onto the front of it
			$file = $this->use_dir . $file;
			if ($filename == "." || $filename == "..") continue;  // Ignore self and parent directory links (first two entries in each directory)
			$fsize = $this->file_property("size", $file);
			$fmtime = $this->file_property("modified", $file);
			// Check if it's a directory or not
			if (is_dir($file)) {
                $dir_files[$filename] = "<tr><td class=\"file\"><a href=\"" . $_SERVER['PHP_SELF'] . "?dir=" . $file . "/\"><span class=\"directory\">&gt;&nbsp;" . $filename . "/</span></a></td><td class=\"file\"> </td><td class=\"file\"> </td></tr>\n";
			// Check if it's a php file
            }
            else
                {
                $non_dir_files[$filename] = "<tr style=\"border-top: 1px solid #000000\"><td class=\"file\"><a href=\"" . $_SERVER['PHP_SELF'] . "?edit=" . $file;
                if ($this->use_dir != "")
                    $non_dir_files[$filename] .= "&dir=" . $this->use_dir;
                $non_dir_files[$filename] .= "\" title=\"Edit " . $file . "\">" . $filename . "</a></td><td class=\"file-property\">" . $fsize . "</td><td class=\"file-property\">" . $fmtime . "</td></tr>\n";
			}
		}
		// Sort the arrays alphabetically and output them
		if (isset($dir_files)) {
			ksort($dir_files);
			foreach ($dir_files as $value) echo $value;
		}
		if (isset($non_dir_files)) {
			ksort($non_dir_files);
			foreach ($non_dir_files as $value) echo $value;
		}
		echo "</table>\n";

        closedir($this->dir);
	} // End of display_dir function

    function cleanup_text_file($file)
    {
        // chr(13)  = CR (carridge return) = Macintosh
        // chr(10)  = LF (line feed)       = Unix
        // Win line break = CRLF
        $new_file  = '';
        $old_file  = '';
        $fcontents = file($file);
        while (list ($line_num, $line) = each($fcontents))
            {
            $old_file .= $line;
            $new_file .= str_replace(chr(13), chr(10), $line);
            }
        if ($old_file != $new_file)
            {
           // Open the uploaded file, and re-write it with the new changes
            $fp = fopen($file, "w");
            fwrite($fp, $new_file);
            fclose($fp);
            }
    }


    function edit_file($file, $change_file = 0, $newfile = 0)
    {
		if ($change_file == 1) {
            $fp = fopen($file, "w");
            $newfile = stripslashes($newfile);
            $newfile = str_replace(chr(13), '', $newfile);

            if (fwrite($fp, $newfile))
                $this->msg = "File <u>" . $filename . "</u> successfully edited.";
			fclose($fp);
		} else {
            if (is_writable($file)) {
				$ret  = "<form action=\"" . $_SERVER['PHP_SELF'] . "\" method=\"POST\">\n";
                $ret .= "<p><text" . "area cols=\"80\" rows=\"30\" wrap=\"off\" name=\"newfile\">\n";

				$contents = file_get_contents($file);
                $contents = str_replace("&nbsp;", "&amp;nbsp;", $contents);  // <--- Check for non-breaking space characters and maintain them
                $contents = str_replace("&gt;", "&amp;gt;", $contents);
                $contents = str_replace("&lt;", "&amp;lt;", $contents);

				$ret .= $contents;
                $ret .= "</text" . "area>\n";
				$ret .= "<p>Save As: <br><input type=\"text\" name=\"filename\" value=\"" . $file . "\">";
				$ret .= "<input type=\"hidden\" name=\"edit\" value=\"true\">\n";
				$ret .= "<input type=\"hidden\" name=\"dir\" value=\"" . $this->use_dir . "\">\n";
				$ret .= "<p><input type=\"submit\" name=\"submit\" value=\"Submit Changes\">\n";
				$ret .= "</form></p>\n";
			} else {
				$ret .= "<p>Cannot edit file: system permission denied.</p>\n";
			}
			return $ret;
		}
	} // End of edit_file function

    function file_property($property, $file)
    {
        $prev_display_errors = ini_get("display_errors");
        ini_set("display_errors", "0");
            if ($property == "size") {   // File size property
				$size = filesize($file);
			if ($size > 1024 && $size < 1048576) {
				$size = round($size / 1024, 2) . " KB";
			} elseif ($size > 1048576) {
				$size = round($size / 1048576, 1) . " MB";
			} elseif ($size > 1073741824) {
				$size = round($size / 1073741824, 1) . " GB";
			} else {
				$size = $size . " B";
			}
        ini_set("display_errors", $prev_display_errors);

            return $size;
		} elseif ($property == "modified") {   // Last modified date of file
			$modtime = date($this->date_format, filemtime($file));
			$modtime .= "&nbsp;";
        ini_set("display_errors", $prev_display_errors);

            return $modtime;
		}
        ini_set("display_errors", $prev_display_errors);
	} // End of file_property function

    function show_msg()
    {
		if (isset($this->msg))
			echo "<tr height=\"24\"><td colspan=\"3\" class=\"file\" align=\"center\"><span class=\"error_msg\">" . $this->msg . "</span></td></td><td class=\"file-options\"> </td></tr>";
	}

    function handle_events()
    {
        // Handle normal events

        $this->display_header();
        if (isset($_GET['edit'])) {
            echo $this->edit_file($_GET['edit']);
        } elseif (isset($_POST['edit'], $_POST['dir'])) {
            $this->edit_file($_POST['filename'], 1, $_POST['newfile']);
            $this->use_dir = $_POST['dir'];
            $this->display_dir();
        } else {
            $this->display_dir();
        }

        $this->display_footer();
    } // End of handle_events function


} // End of DirPHP class


?>



