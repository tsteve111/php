<?php

$files1 = scandir("./DONE", 1);
echo "<b>FILE-ok száma: ".count($files1)."<br></b>";
echo "<br>".time();
$i=0;
while ($i<10) {
echo "<br>".$files1[$i++];}
echo "<br>".time();

