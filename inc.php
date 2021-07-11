<?php

if ($kod=="1651") {
if (strpos(file_get_contents($dir."/".$filerovidnev),"LX 21/00061") !== false and strpos(file_get_contents($dir."/".$filerovidnev),"LK 21/00804") !== false) {
unlink($dir."/".$filename);
exit;}}

?>