<?php 
for ($x = 0; $x < 12; $x=$x+1) {
  if ($x == 4) {
    continue;
  }
  echo "The number is: $x";
}
?>