<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "185.224.137.6";
$username = "u832900566_sckit";
$password = "u832900566";
$database_name = "u832900566_sckit";

try {
    $conn = new PDO("mysql:host=$servername;dbname={$database_name}", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully";
    }
catch(PDOException $e)
    {
    echo "Connection failed: " . $e->getMessage();
    }
?>