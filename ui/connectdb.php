<?php

try{

    $pdo = new PDO('mysql:host=localhost;port=3307;dbname=pos_db','root','');

}catch(PDOException $e  ){

echo $e->getMessage();

}

//echo'connection success';

?>