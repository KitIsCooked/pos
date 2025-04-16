<?php

include_once'connectdb.php';

$id=$_POST['pidd']; //pidd comes from productlist.php under ajax

// Get image name before deleting the product
$select = $pdo->prepare("SELECT image FROM tbl_product WHERE pid = :id");
$select->bindParam(':id', $id);
$select->execute();
$row = $select->fetch();

$sql="delete from tbl_product where pid =$id";

$delete=$pdo->prepare($sql);

if($delete->execute()){

    unlink("productimages/".$row['image']);

}else{

    echo"Error in deleting product";
}

?>