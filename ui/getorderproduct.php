<?php
include_once'connectdb.php';

$id=$_GET['id'];

$select=$pdo->prepare("select * from tbl_invoice_details a INNER JOIN tbl_product b ON a.product_id=b.pid where a.invoice_id=$id");
$select->execute();

$row_invoice_details=$select->fetchAll(PDO::FETCH_ASSOC);

$response=$row_invoice_details;

header('Content-Type: application/json');

echo json_encode($response);

?>