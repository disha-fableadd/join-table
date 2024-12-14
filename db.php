<?php 

    $server="localhost";
    $username="root";
    $password="";
    $db="api";

    $conn=mysqli_connect($server,$username,$password,$db);

    if(!$conn){
        die("connection fail..".mysqli_connect_error());
    }
    // else{
    //     echo "connection sccessfull..";
    // }
?>