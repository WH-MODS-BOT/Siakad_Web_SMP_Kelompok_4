<?php

$host     = "localhost";

$username = "root";

$password = "";

$database = "siakad_web";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {

    die("Gagal: " . mysqli_connect_error());

}

?>