<?php
    include_once 'connectdb.php';
    session_start();

    if($_SESSION['useremail']==""  OR $_SESSION['role']=="User") {
        header('location:../index.php');
    }

    if($_SESSION['role']=="Admin") {
        include_once'header.php';
    } else {
        include_once'headeruser.php';
    }

    if(isset($_POST['btnsave'])) {
        $barcode       = $_POST['txtbarcode'];
        $product       = $_POST['txtproductname'];
        $category      = $_POST['txtselect_option'];
        $description   = $_POST['txtdescription'];
        $stock         = $_POST['txtstock'];
        $purchaseprice = $_POST['txtpurchaseprice'];
        $saleprice     = $_POST['txtsaleprice'];

        //Image Code or File Code Start Here..
        $f_name        = $_FILES['myfile']['name'];
        $f_tmp         = $_FILES['myfile']['tmp_name'];
        $f_size        = $_FILES['myfile']['size'];
        $f_extension   = explode('.',$f_name); //explode() function split a string by .
        $f_extension   = strtolower(end($f_extension)); //get last part and converts to lowercase
        $f_newfile     = uniqid().'.'. $f_extension;   
        
        $store = "productimages/".$f_newfile;
            
        if($f_extension=='jpg' || $f_extension=='jpeg' || $f_extension=='png' || $f_extension=='gif') {
            if($f_size>=1000000) {
                $_SESSION['status']="Max file should be 1MB";
                $_SESSION['status_code']="warning";
            } else {
                if(move_uploaded_file($f_tmp,$store)) {

                    $productimage=$f_newfile; //stores new file name in variable
                    
                    //check barcode is empty or not
                    if(empty($barcode)) {

                        $insert=$pdo->prepare("insert into tbl_product (product,category,description,stock,purchaseprice,saleprice,image) 
                            values(:product,:category,:description,:stock,:pprice,:saleprice,:img)");

                        $insert->bindParam(':product',$product);
                        $insert->bindParam(':category',$category);
                        $insert->bindParam(':description',$description);
                        $insert->bindParam(':stock',$stock);
                        $insert->bindParam(':pprice',$purchaseprice);
                        $insert->bindParam(':saleprice',$saleprice);
                        $insert->bindParam(':img',$productimage);
                        
                        $insert->execute();

                        $pid=$pdo->lastInsertId();
                        date_default_timezone_set("Asia/Calcutta");
                        $newbarcode=$pid.date('his'); //his=hour,minute,second

                        $update=$pdo->prepare("update tbl_product SET barcode='$newbarcode' where pid='".$pid."'");

                        if($update->execute()) {
                            $_SESSION['status']="Product Inserted Successfully";
                            $_SESSION['status_code']="success";
                        } else {
                            $_SESSION['status']="Product Inserted Failed";
                            $_SESSION['status_code']="error";
                        }
                    } else {
                        $insert=$pdo->prepare("insert into tbl_product (barcode,product,category,description,stock,purchaseprice,saleprice,image) 
                            values(:barcode,:product,:category,:description,:stock,:pprice,:saleprice,:img)");

                        $insert->bindParam(':barcode',$barcode);
                        $insert->bindParam(':product',$product);
                        $insert->bindParam(':category',$category);
                        $insert->bindParam(':description',$description);
                        $insert->bindParam(':stock',$stock);
                        $insert->bindParam(':pprice',$purchaseprice);
                        $insert->bindParam(':saleprice',$saleprice);
                        $insert->bindParam(':img',$productimage);

                        if($insert->execute()) {
                            $_SESSION['status']="Product Inserted Successfully";
                            $_SESSION['status_code']="success";
                        } else {
                            $_SESSION['status']="Product Inserted Failed";
                            $_SESSION['status_code']="error";
                        }
                    }
                }
            }
        } else {
            $_SESSION['status']="only jpg, jpeg, png and gif can be upload";
            $_SESSION['status_code']="warning";
        }
    }
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <!-- <div class="col-sm-6">
                    <h1 class="m-0">Add Product</h1>
                </div> -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="m-0">Add Product</h3>
                        </div>
                        
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Barcode</label>
                                            <input type="text" class="form-control" placeholder="Enter Barcode" name="txtbarcode" autocomplete="off">
                                        </div>

                                        <div class="form-group">
                                            <label>Product Name</label>
                                            <input type="text" class="form-control" placeholder="Enter Name" name="txtproductname" autocomplete="off" required>
                                        </div>

                                        <div class="form-group">
                                            <label>Category</label>
                                            <select class="form-control" name="txtselect_option" required>
                                                <option value="" disabled selected>Select Category</option>
                                                <?php
                                                    $select=$pdo->prepare("select * from tbl_category order by catid desc");
                                                    $select->execute();

                                                    while($row=$select->fetch(PDO::FETCH_ASSOC)) {
                                                        extract($row);
                                                        echo "<option>{$row['category']}</option>";
                                                    }
                                                ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea class="form-control" placeholder="Enter Description" name="txtdescription" rows="4" required></textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Stock Quantity</label>
                                            <input type="number" min="1" step="any" class="form-control" placeholder="Enter Stock" name="txtstock" autocomplete="off" required>
                                        </div>

                                        <div class="form-group">
                                            <label>Purchase Price</label>
                                            <input type="number" min="1" step="any" class="form-control" placeholder="Enter Purchase Price" name="txtpurchaseprice" autocomplete="off" required>
                                        </div>

                                        <div class="form-group">
                                            <label>Sale Price</label>
                                            <input type="number" min="1" step="any" class="form-control" placeholder="Enter Sale Price" name="txtsaleprice" autocomplete="off" required>
                                        </div>

                                        <div class="form-group">
                                            <label>Product image</label>
                                            <input type="file" class="input-group" name="myfile" required>
                                            <p>Upload image</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="text-center">
                                    <button type="submit" class="btn btn-warning" name="btnsave">Save Product</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
    include_once "footer.php";
?>

<?php
    if(isset($_SESSION['status']) && $_SESSION['status']!='') {
?>
<script>
    Swal.fire({
        icon: '<?php echo $_SESSION['status_code'];?>',
        title: '<?php echo $_SESSION['status'];?>'
    });
</script>
<?php
        unset($_SESSION['status']);
    }
?>