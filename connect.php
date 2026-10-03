<?php

$host="sql303.infinityfree.com";
$user="if0_43005046";
$pass="pM20m4nyF1UH";
$db="if0_43005046_project";
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error){
    echo "Failed to connect DB".$conn->connect_error;
}
?>