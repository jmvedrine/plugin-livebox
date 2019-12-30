<?php
$num="";
$name="";
if (isset( $_GET['num'])) $num=$_GET['num'];
else if (isset( $_POST['num'])) $num=$_POST['num'];
if (isset( $_GET['name'])) $name=$_GET['name'];
else if (isset( $_POST['name'])) $name=$_POST['name'];

if($num == "" || $name == "") {
  echo "Empty args";
  return;
}
include "../../core/class/livebox.class.php";

$lb= new livebox();
$lb->addFavorite($num,$name);

