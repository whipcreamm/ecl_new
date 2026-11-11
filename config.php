<?php
    $conn=mysqli_connect('localhost','root','','ecl2');
    if(!$conn){
        die ("เชื่อมต่อฐานช้อมูลล้มเหลว" .mysqli_error());
    }
?>