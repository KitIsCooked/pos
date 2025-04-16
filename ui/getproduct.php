<?php

include_once'connectdb.php';

$productid=$_GET["id"];
$barcode=$_GET["id"];

$select=$pdo->prepare("select * from tbl_product where pid=$productid OR barcode=$barcode");
$select->execute();

$row=$select->fetch(PDO::FETCH_ASSOC);

$response=$row;

header('Content-Type: application/json');

echo json_encode($response);

?>