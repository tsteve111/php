<?php

$dir = "";
$newline  11   = "";

$kod = $_GET['kod'];
if ($kod != "") echo "Kód: " . $kod . "<br><br>";

if (mb_strlen($kod) != 9) {
  echo "Rövid filenév!";
  exit;
}


if (file_exists($kod)) {
  echo "The file $kod exists";
} else {
  exit;
}

$kod0 = substr($kod, 0, -4);


$filenev = fopen($kod, "a+");
fwrite($filenev, "\nIN_PROGRESS");
fclose($filenev);


$kod1 = $kod . "VAL";
if (file_exists($kod1)) unlink($kod1);

$link = mysqli_connect("127.0.0.1:3307", "root", "Balaton1", "NAV");
$link1 = mysqli_connect("hometours.synology.me:3307", "admin", "Balaton600425?", "NAV");

if (!$link) {
  exit;
}

if (!$link1) {
  exit;
}


if (file_exists(substr($kod, 0, 5) . ".old")) unlink(substr($kod, 0, 5) . ".old");
copy($kod, substr($kod, 0, 5) . ".old");


$filenev = fopen($kod1, "a");
$handle = fopen($kod, "r");
if ($handle) {
  while (($line = fgets($handle)) !== false) {

    if (substr($line, 0, 1) == "\x0D") $line = substr($line, 1, 31);
    if (substr($line, 0, 1) == "\x0D") $line = substr($line, 1, 31);
    if (substr($line, 0, 1) == "\x0D") $line = substr($line, 1, 31);

    $ldone = false;
    $line = inprocess($line, $filenev);
    $line = fcserszeg($line, $filenev, $ldone, $link);    //a DONE szóra keres a cserszegi mysql adatok közt a filenév karakter alapján
    $line = ftapolca($line, $filenev, $ldone, $link1);    //a DONE szóra keres a tapolcai mysql adatok közt a filenév karakter alapján
    $line = fdone($line, $filenev, $ldone, $link);     //done szóra keres a cserszegi mysql adatok közt az első 5 karakter alapján
    $line = fdone1($line, $filenev, $ldone, $link1);   //done szóra keres a tapolcai mysql adatok közt az első 5 karakter alapján
    $line = fsfile1($line, $filenev);  // ekkor nézi át az S jelű fájlokat:STATUS STATUS1 STATUS2....STATUS20 könyvtárig ... a "DONE" szóra keres
    $line = fsfile3($line, $filenev);  // a konkrét (a sorban megadott) filenevet nézi meg, van-e benne "UNIQ" vagy "KORABB"    ... STATUS_U könyvtárban is
    $line = uniq($line, $filenev, $ldone, $link);   // cserszegi mysql-t nézi át UNIQ és KORABB utan kutatva   a teljes filenév alapján
    $line = uniq1($line, $filenev, $ldone, $link1);   // tapolcai mysql-t nézi át UNIQ és KORABB utan kutatva  a teljes filenév alapján
    $line = fsfile2($line, $filenev);  // ekkor nézi át az összes S jelű file-t "UNIQ és KORABB" utan kutatva  __  STATUS_U könyvtarat is
    fvegso($line, $filenev);  //maradék DUMY-k helyére mindenhova berakja a !!!! jelet (végős fázis)
  }
} else {
  echo "HIBA a beolvasásnál!";
}

fwrite($filenev, "KESZ");

fclose($filenev);
fclose($handle);
mysqli_close($link);
mysqli_close($link1);

echo "<br><br>KÉSZ...";

if (file_exists($kod)) unlink($kod);
if (file_exists($kod . "VAL")) rename($kod . "VAL", $kod);

exit;

function fcserszeg($line, $filenev, $ldone, $link)
{

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $kod2 = substr($line, 17, 12);
  if ($result = mysqli_query($link, "SELECT * FROM `2018` WHERE valasz like '%DONE%' and XMLneve like '" . $kod2 . "'  and szamlaszam like '" . substr($line, 0, 11) . "'")) {
    $v1 = mysqli_num_rows($result);
    if ($v1 == 1) {
      $line = substr($line, 0, 11) . " DONE " . substr($line, 17, 12) . "\n";
      fwrite($filenev, $line);
      $ldone = TRUE;
    }
    mysqli_free_result($result);
  }

  return $line;
}



function uniq($line, $filenev, $ldone, $link)
{

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $kod2 = substr($line, 17, 12);
  if ($result = mysqli_query($link, "SELECT * FROM `2018` WHERE (valasz like '%KORABB%' or valasz like '%UNIQ%' ) and XMLneve like '" . $kod2 . "'  and szamlaszam like '" . substr($line, 0, 11) . "'")) {
    $v1 = mysqli_num_rows($result);
    if ($v1 > 0) {
      $line = substr($line, 0, 11) . " UNIQ " . substr($line, 17, 12) . "\n";
      fwrite($filenev, $line);
      $ldone = TRUE;
    }
    mysqli_free_result($result);
  }

  return $line;
}


function uniq2($line, $filenev, $ldone, $link)
{

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $kod2 = substr($line, 17, 5);
  if ($result = mysqli_query($link, "SELECT * FROM `2018` WHERE (valasz like '%KORABB%' or valasz like '%UNIQ%' ) and XMLneve like '" . $kod2 . "'  and szamlaszam like '" . substr($line, 0, 11) . "'")) {
    $v1 = mysqli_num_rows($result);
    if ($v1 > 0) {
      $line = substr($line, 0, 11) . " UNIQ " . substr($line, 17, 12) . "\n";
      fwrite($filenev, $line);
      $ldone = TRUE;
    }
    mysqli_free_result($result);
  }

  return $line;
}


function uniq1($line, $filenev, $ldone, $link1)
{

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $kod2 = substr($line, 17, 12);
  if ($result = mysqli_query($link1, "SELECT * FROM `2018` WHERE (valasz like '%KORABB%' or valasz like '%UNIQ%' ) and XMLneve like '" . $kod2 . "'  and szamlaszam like '" . substr($line, 0, 11) . "'")) {
    $v1 = mysqli_num_rows($result);
    if ($v1 > 0) {
      $line = substr($line, 0, 11) . " UNIQ " . substr($line, 17, 12) . "\n";
      fwrite($filenev, $line);
      $ldone = TRUE;
    }
    mysqli_free_result($result);
  }

  return $line;
}

function uniq3($line, $filenev, $ldone, $link1)
{

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $kod2 = substr($line, 17, 5);
  if ($result = mysqli_query($link1, "SELECT * FROM `2018` WHERE (valasz like '%KORABB%' or valasz like '%UNIQ%' ) and XMLneve like '" . $kod2 . "'  and szamlaszam like '" . substr($line, 0, 11) . "'")) {
    $v1 = mysqli_num_rows($result);
    if ($v1 > 0) {
      $line = substr($line, 0, 11) . " UNIQ " . substr($line, 17, 12) . "\n";
      fwrite($filenev, $line);
      $ldone = TRUE;
    }
    mysqli_free_result($result);
  }

  return $line;
}



function fdone($line, $filenev, $ldone, $link)
{

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $kod2 = substr($line, 17, 5);
  if ($result = mysqli_query($link, "SELECT * FROM `2018` WHERE valasz like '%DONE%' and XMLneve like '" . $kod2 . "%'  and szamlaszam like '" . substr($line, 0, 11) . "'")) {
    $v1 = mysqli_num_rows($result);

    if ($v1 == 1) {
      $row = $result->fetch_assoc();
      $line = substr($line, 0, 11) . " ???? " . $row["XMLneve"] . "\n";
      fwrite($filenev, $line);
      $ldone = TRUE;
    }
    mysqli_free_result($result);
  }

  return $line;
}




function fdone1($line, $filenev, $ldone, $link1)
{

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $kod2 = substr($line, 17, 5);
  if ($result = mysqli_query($link1, "SELECT * FROM `2018` WHERE valasz like '%DONE%' and XMLneve like '" . $kod2 . "%'  and szamlaszam like '" . substr($line, 0, 11) . "'")) {
    $v1 = mysqli_num_rows($result);

    if ($v1 == 1) {
      $row = $result->fetch_assoc();
      $line = substr($line, 0, 11) . " ???? " . $row["XMLneve"] . "\n";
      fwrite($filenev, $line);
      $ldone = TRUE;
    }
    mysqli_free_result($result);
  }

  return $line;
}







function fsfile1($line, $filenev)
{

  global $dir;

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $dir = "STATUS";
  $line = checkdir($line, $filenev);

  $dir = "STATUS2";
  $line = checkdir($line, $filenev);

  $dir = "STATUS3";
  $line = checkdir($line, $filenev);

  $dir = "STATUS4";
  $line = checkdir($line, $filenev);

  $dir = "STATUS5";
  $line = checkdir($line, $filenev);

  $dir = "STATUS6";
  $line = checkdir($line, $filenev);

  $dir = "STATUS7";
  $line = checkdir($line, $filenev);

  $dir = "STATUS8";
  $line = checkdir($line, $filenev);

  $dir = "STATUS9";
  $line = checkdir($line, $filenev);

  $dir = "STATUS10";
  $line = checkdir($line, $filenev);

  $dir = "STATUS11";
  $line = checkdir($line, $filenev);

  $dir = "STATUS12";
  $line = checkdir($line, $filenev);

  $dir = "STATUS13";
  $line = checkdir($line, $filenev);

  $dir = "STATUS14";
  $line = checkdir($line, $filenev);

  $dir = "STATUS15";
  $line = checkdir($line, $filenev);

  $dir = "STATUS16";
  $line = checkdir($line, $filenev);

  $dir = "STATUS17";
  $line = checkdir($line, $filenev);

  $dir = "STATUS18";
  $line = checkdir($line, $filenev);

  $dir = "STATUS19";
  $line = checkdir($line, $filenev);

  $dir = "STATUS20";
  $line = checkdir($line, $filenev);

  return $line;
}



function fsfile2($line, $filenev)
{

  global $dir;

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $dir = "STATUS";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS2";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS3";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS4";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS5";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS6";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS7";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS8";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS9";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS10";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS11";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS12";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS13";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS14";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS15";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS16";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS17";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS18";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS19";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS20";
  $line = checkdir2($line, $filenev);

  $dir = "STATUS_U";
  $line = checkdir2($line, $filenev);

  return $line;
}





function fsfile3($line, $filenev)
{

  global $dir;

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $dir = "STATUS";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS2";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS3";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS4";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS5";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS6";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS7";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS8";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS9";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS10";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS11";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS12";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS13";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS14";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS15";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS16";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS17";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS18";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS19";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS20";
  $line = checkdir3($line, $filenev);

  $dir = "STATUS_U";
  $line = checkdir3($line, $filenev);

  return $line;
}






function inprocess($line, $filenev)
{

  global $dir;

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $dir = "TEMP11";
  $line = checkdir1($line, $filenev);

  $dir = "TEMP12";
  $line = checkdir1($line, $filenev);

  $dir = "TEMP13";
  $line = checkdir1($line, $filenev);

  $dir = "TEMP14";
  $line = checkdir1($line, $filenev);

  $dir = "TEMP15";
  $line = checkdir1($line, $filenev);

  $dir = "TEMP16";
  $line = checkdir1($line, $filenev);

  $dir = "TEMP17";
  $line = checkdir1($line, $filenev);

  $dir = "TEMP18";
  $line = checkdir1($line, $filenev);

  $dir = "TEMP19";
  $line = checkdir1($line, $filenev);

  $dir = "TEMP20";
  $line = checkdir1($line, $filenev);

  return $line;
}




function checkdir($line, $filenev)
{

  global $dir;
  global $newline;
  $newline = $line;
  $szsz = substr($line, 0, 11);

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $kod5 = substr($line, 17, 5);

  foreach (glob($dir . "/" . $kod5 . "*.*S") as $filename) {
    if (filesize($filename) < 1000)  unlink($filename);
  }


  foreach (glob($dir . "/" . $kod5 . "*.*S") as $filename) {

    if ((strpos(file_get_contents($filename), "DONE") !== false) and  (strpos(file_get_contents($filename), $szsz) !== false)) {
      $newline = $szsz . " ???F " . substr($filename, -12) . "\n";
    }
    $line1 = substr($line, 17, 11) . "S";
    if (substr($filename, -12) == $line1) $newline = $szsz . " DONE " . substr($line, 17, 12) . "\n";
  }


  if ($newline != $line) {
    $line = $newline;
    fwrite($filenev, $line);
  }

  return $line;
}




function checkdir2($line, $filenev)
{

  global $dir;
  global $newline;
  $newline = $line;
  $szsz = substr($line, 0, 11);

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $kod5 = substr($line, 17, 5);

  foreach (glob($dir . "/" . $kod5 . "*.*S") as $filename) {
    if (filesize($filename) < 1000)  unlink($filename);
  }


  foreach (glob($dir . "/" . $kod5 . "*.*S") as $filename) {

    if (((strpos(file_get_contents($filename), "KORABB") !== false)  or (strpos(file_get_contents($filename), "UNIQ") !== false)) and (strpos(file_get_contents($filename), $szsz) !== false)) {
      $newline = $szsz . " ???F " . substr($filename, -12) . "\n";
      $line1 = substr($line, 17, 11) . "S";
      if (substr($filename, -12) == $line1) $newline = $szsz . " UNIQ " . substr($line, 17, 12) . "\n";
    }


    if (((strpos(file_get_contents($filename), "INVALID_INVOICE") !== false)) and (strpos(file_get_contents($filename), $szsz) !== false)) {
      $newline = $szsz . " ???F " . substr($filename, -12) . "\n";
      $line1 = substr($line, 17, 11) . "S";
      if (substr($filename, -12) == $line1) $newline = $szsz . " INVA " . substr($line, 17, 12) . "\n";
    }
  }

  if ($newline != $line) {
    $line = $newline;
    fwrite($filenev, $line);
  }


  return $line;
}




function checkdir1($line, $filenev)
{

  global $dir;
  global $newline;
  $newline = $line;
  $szsz = substr($line, 0, 11);
  $fn12 = substr($line, -12);

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;


  if (file_exists($dir . "/" . $fn12)) {
    $newline = $szsz . " DONE " . substr($line, -12) . "\n";
  }

  if ($newline != $line) {
    $line = $newline;
    fwrite($filenev, $line);
  }


  return $line;
}


function checkdir3($line, $filenev)
{

  global $dir;
  global $newline;
  $newline = $line;
  $szsz = substr($line, 0, 11);
  $fn12 = substr($line, 17, 12);

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $filename = $dir . "/" . $fn12;
  if (file_exists($filename)) {

    if (((strpos(file_get_contents($filename), "KORABB") !== false)  or (strpos(file_get_contents($filename), "UNIQ") !== false)) and (strpos(file_get_contents($filename), $szsz) !== false)) {
      $newline = $szsz . " UNIQ " . substr($line, 17, 12) . "\n";
    }

    if (((strpos(file_get_contents($filename), "INVALID_INVOICE") !== false)) and (strpos(file_get_contents($filename), $szsz) !== false)) {
      $newline = $szsz . " INVA " . substr($line, 17, 12) . "\n";
    }
  }


  if ($newline != $line) {
    $line = $newline;
    fwrite($filenev, $line);
  }


  return $line;
}





function ftapolca($line, $filenev, $ldone, $link1)
{

  $pos = strpos($line, "DUMY");
  if ($pos == FALSE) return $line;

  $kod2 = substr($line, 17, 12);
  if ($result = mysqli_query($link1, "SELECT * FROM `2018` WHERE valasz like '%DONE%' and XMLneve like '" . $kod2 . "'  and szamlaszam like '" . substr($line, 0, 11) . "'")) {
    $v1 = mysqli_num_rows($result);
    if ($v1 == 1) {
      $line = substr($line, 0, 11) . " DONE " . substr($line, 17, 12) . "\n";
      fwrite($filenev, $line);
      $ldone = TRUE;
    }
    mysqli_free_result($result);
  }
  return $line;
}



function fvegso($line, $filenev)
{
  $pos = strpos($line, "DUMY");
  if ($pos) fwrite($filenev, substr($line, 0, 11) . " !!!! " . "            \n");
  return $line;
}
