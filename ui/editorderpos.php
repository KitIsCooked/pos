<?php
ob_start();

include_once 'connectdb.php';
session_start();

if($_SESSION['useremail']==""  OR $_SESSION['role']=="") {
    header('location:../index.php');
}

if($_SESSION['role']=="Admin") {
    include_once'header.php';
} else {
    include_once'headeruser.php';
}

function fill_product($pdo) {
    $output='';
    $select=$pdo->prepare("select * from tbl_product order by product asc");
    $select->execute();
    $result=$select->fetchAll();

    foreach($result as $row) {
        $output.='<option value="'.$row["pid"].'">'.$row["product"].'</option>';
    }
    return $output; 
}

$id=$_GET["id"];
$select=$pdo->prepare("select * from tbl_invoice where invoice_id =$id");
$select->execute();
$row=$select->fetch(PDO::FETCH_ASSOC);

$order_date=date('Y-m-d',strtotime($row['order_date']));
$subtotal     =$row['subtotal'];
$sgst         =$row['sgst'];
$cgst         =$row['cgst'];
$discount     =$row['discount'];
$total        =$row['total'];
$paid         =$row['paid'];
$due          =$row['due'];
$payment_type =$row['payment_type'];

$select=$pdo->prepare("select * from tbl_invoice_details where invoice_id=$id");
$select->execute();
$row_invoice_details=$select->fetchAll(PDO::FETCH_ASSOC);

if(isset($_POST['btnupdateorder'])){

//Steps for btnupdateorder button.
   
// 1) Get values from text fields and from array in variables.

$txt_orderdate     = date('Y-m-d');
$txt_subtotal      = $_POST['txtsubtotal'];
$txt_discount      = $_POST['txtdiscount'];
$txt_sgst          = $_POST['txtsgst'];
$txt_cgst          = $_POST['txtcgst'];
$txt_total         = $_POST['txttotal'];
$txt_payment_type  = $_POST['rb'];
$txt_due           = $_POST['txtdue'];
$txt_paid          = $_POST['txtpaid'];

/////

$arr_pid     = $_POST['pid_arr'];
$arr_barcode = $_POST['barcode_arr'];
$arr_name    = $_POST['product_arr'];
$arr_stock   = $_POST['stock_c_arr'];
$arr_qty     = $_POST['quantity_arr'];
$arr_price   = $_POST['price_c_arr'];
$arr_total   = $_POST['saleprice_arr'];

// 2) Write update query for tbl_product and update stock values to its previous values when clicked on update order

foreach($row_invoice_details as $product_invoice_details) {

$updateproduct_stock=$pdo->prepare("update tbl_product set stock=stock+".$product_invoice_details['qty']." where pid='".$product_invoice_details['product_id']."'");
$updateproduct_stock->execute();

}

// 3) Write delete query for tbl_invoice_details table data where invoice_id =$id .

$delete_invoice_details=$pdo->prepare("delete from tbl_invoice_details where invoice_id =$id");
$delete_invoice_details->execute();

// 4) Write update query for tbl_invoice table data.

$update_tbl_invoice=$pdo->prepare("update tbl_invoice SET order_date=:orderdate,subtotal=:subtotal,discount=:discount,sgst=:sgst,cgst=:cgst,total=:total,payment_type=:payment_type,due=:due,paid=:paid where invoice_id=$id");

$update_tbl_invoice->bindParam(':orderdate',$txt_orderdate);
$update_tbl_invoice->bindParam(':subtotal',$txt_subtotal);
$update_tbl_invoice->bindParam(':discount',$txt_discount);
$update_tbl_invoice->bindParam(':sgst',$txt_sgst);
$update_tbl_invoice->bindParam(':cgst',$txt_cgst);
$update_tbl_invoice->bindParam(':total',$txt_total);
$update_tbl_invoice->bindParam(':payment_type',$txt_payment_type);
$update_tbl_invoice->bindParam(':due',$txt_due);
$update_tbl_invoice->bindParam(':paid',$txt_paid);

$update_tbl_invoice->execute(); 

 $invoice_id=$pdo->lastInsertId();
 
 if($invoice_id!=null){
    
// 5) Write select query for tbl_product table to get out stock value.
  for($i=0;$i<count($arr_pid);$i++){

$selectpdt=$pdo->prepare("select * from tbl_product where pid='".$arr_pid[$i]."'");
$selectpdt->execute();

while($rowpdt=$selectpdt->fetch(PDO::FETCH_OBJ)){


$db_stock[$i]=$rowpdt->stock; //get stock values from tbl_product table

    $rem_qty=$db_stock[$i]-$arr_qty[$i];

    if($rem_qty<0){

      return"Order is not completed";
    
    }else{
        
// 6) Write update query for tbl_product table to update stock values.
    $update=$pdo->prepare("update tbl_product SET stock='$rem_qty' where pid='".$arr_pid[$i]."'");
    $update->execute();
    
    }//else end here

}

// 7) Write insert query for tbl_invoice_details for insert new records.

$insert=$pdo->prepare("insert into tbl_invoice_details (invoice_id,barcode,product_id,product_name,qty,rate,saleprice,order_date) values (:invid,:barcode,:pid,:name,:qty,:rate,:saleprice,:order_date)");
$insert->bindParam(':invid',$id);
$insert->bindParam(':barcode',$arr_barcode[$i]);
$insert->bindParam(':pid',$arr_pid[$i]);
$insert->bindParam(':name',$arr_name[$i]);
$insert->bindParam(':qty',$arr_qty[$i]);
$insert->bindParam(':rate',$arr_price[$i]);
$insert->bindParam(':saleprice',$arr_total[$i]);
$insert->bindParam(':order_date',$txt_orderdate);

if(!$insert->execute()){

  print_r($insert->errorInfo());
}

 }//end for loop

 header('location:orderlist.php');
 }//1st if end

//var_dump($arr_total);

}

ob_end_flush();

$select=$pdo->prepare("select * from tbl_taxdis where taxdis_id =1");
$select->execute();
$row=$select->fetch(PDO::FETCH_OBJ);

?>

<style type="text/css">

.tableFixHead{
overflow: scroll;
height: 520px;
}
.tableFixHead thead th {
    position: sticky;
    top:0 ;
    z-index: 1;
}

table {border-collapse:collapse; width: 100px;}
th,td {padding:8px 16px;}
th{background: #eee;}

</style>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <!-- <h1 class="m-0">Point Of Sale</h1> -->
        </div><!-- /.col -->
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <!-- <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Starter Page</li> -->
          </ol>
        </div><!-- /.col -->
      </div><!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->

  <!-- Main content -->
  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
              <h3 class="m-0">Edit Order POS</h3>
            </div>
            <div class="card-body">
            <div class="row">
  <div class="col-md-8">

  <div class="input-group mb-3">
    <div class="input-group-prepend">
        <span class="input-group-text"><i class="fa fa-barcode"></i></span>
          </div>
          <input type="text" class="form-control" placeholder="Scan Barcode" autocomplete="off" name="txtbarcode" id="txtbarcode_id">
          </div>

          <form action="" method="post" name="">

          <select class="form-control select2" data-dropdown-css-class="select2-primary" style="width: 100%;">
            <option>Select/Search</option><?php echo fill_product($pdo);?>        
          </select>

          </br>

        <div class="tableFixHead">

        <table id="producttable" class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>Product</th>
              <th>Stock  </th>
              <th>price  </th>
              <th>QTY    </th>
              <th>Total  </th> 
              <th>Del    </th>   
            </tr>

          </thead>

          <tbody class="details" id="itemtable">
            <tr data-widget="expandable-table" aria-expanded="false">
                              
            </tr>           
          </tbody>
        </table>

      </div>

    </div>

  <div class="col-md-4">
  <div class="input-group">
    <div class="input-group-prepend">
      <span class="input-group-text">SUBTOTAL(Rs) </span>
    </div>
  <input type="text" class="form-control" name="txtsubtotal" value="<?php echo $subtotal;?>"  id="txtsubtotal_id" readonly >
    <div class="input-group-append">
      <span class="input-group-text">Rs</span>
    </div>
  </div>

  <div class="input-group">
    <div class="input-group-prepend">
        <span class="input-group-text">DISCOUNT(%)</span>
    </div>
    <input type="text" class="form-control"  name="txtdiscount" id="txtdiscount_p"  value="<?php echo $row->discount;?>" >
      <div class="input-group-append">
    <span class="input-group-text">%</span>
    </div>
  </div>

  <div class="input-group">
    <div class="input-group-prepend">
      <span class="input-group-text">DISCOUNT(Rs)</span>
    </div>
    <input type="text" class="form-control" id="txtdiscount_n" readonly >
    <div class="input-group-append">
      <span class="input-group-text">Rs</span>
    </div>
  </div>

  <div class="input-group">
    <div class="input-group-prepend">
      <span class="input-group-text">SGST(%)</span>
    </div>
    <input type="text" class="form-control"  name="txtsgst" id="txtsgst_id_p" value="<?php echo $row->sgst;?>" readonly >
    <div class="input-group-append">
      <span class="input-group-text">%</span>
    </div>
  </div>

  <div class="input-group">
    <div class="input-group-prepend">
        <span class="input-group-text">CGST(%)</span>
    </div>
    <input type="text" class="form-control" name="txtcgst" id="txtcgst_id_p" value="<?php echo $row->cgst;?>" readonly  >
    <div class="input-group-append">
      <span class="input-group-text">%</span>
    </div>
  </div>

  <div class="input-group">
    <div class="input-group-prepend">
      <span class="input-group-text">SGST(Rs)</span>
    </div>
    <input type="text" class="form-control" id="txtsgst_id_n" readonly >
    <div class="input-group-append">
      <span class="input-group-text">Rs</span>
    </div>
  </div>

  <div class="input-group">
    <div class="input-group-prepend">
      <span class="input-group-text">CGST(Rs)</span>
    </div>
    <input type="text" class="form-control" id="txtcgst_id_n" readonly >
    <div class="input-group-append">
      <span class="input-group-text">Rs</span>
    </div>
  </div>

  <hr style="height:2px; border-width:0; color:black; background-color:black;">

  <div class="input-group">
    <div class="input-group-prepend">
      <span class="input-group-text">TOTAL(Rs)</span>
    </div>
    <input type="text" class="form-control form-control-lg total" name="txttotal" value="<?php echo $total;?>" id="txttotal" readonly >
    <div class="input-group-append">
      <span class="input-group-text">Rs</span>
    </div>
  </div> 

  <hr style="height:2px; border-width:0; color:black; background-color:black;">

  <div class="icheck-success d-inline">
    <input type="radio" name="rb" value="Cash" id="radioSuccess1" value="Cash"<?php echo ($payment_type=='Cash')?'checked':''?>>
    <label for="radioSuccess1">CASH</label>
  </div>
  <div class="icheck-primary d-inline">
    <input type="radio" name="rb" id="radioSuccess2"   value="Card"<?php echo ($payment_type=='Card')?'checked':''?>>
    <label for="radioSuccess2">CARD</label>
  </div>
  <div class="icheck-danger d-inline">
    <input type="radio" name="rb" id="radioSuccess3" value="Check"<?php echo ($payment_type=='Check')?'checked':''?>>
    <label for="radioSuccess3">CHECK</label>
  </div>
  
  <hr style="height:2px; border-width:0; color:black; background-color:black;">

  <div class="input-group">
    <div class="input-group-prepend">
      <span class="input-group-text">DUE(Rs)</span>
    </div>
    <input type="text" class="form-control" name="txtdue" value="<?php echo $due;?>" id="txtdue" readonly >
    <div class="input-group-append">
      <span class="input-group-text">Rs</span>
    </div>
  </div>

  <div class="input-group">
    <div class="input-group-prepend">
      <span class="input-group-text">PAID(Rs)</span>
    </div>
    <input type="text" class="form-control"  name="txtpaid" value="<?php echo $paid;?>" id="txtpaid" required>
    <div class="input-group-append">
    <span class="input-group-text">Rs</span>
    </div>
  </div>
                
  <hr style="height:2px; border-width:0; color:black; background-color:black;">

  <div class="card-footer">
    <div class="text-center">
      <button type="submit" class="btn btn-warning" name="btnupdateorder">Update order</button>
    </div>
  </div>
</div>
</div>
</div>
</div>
</div>
</form>
</div>
<!-- /.col-md-6 -->
</div>
<!-- /.row -->
</div><!-- /.container-fluid -->
</div>
<!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php
include_once "footer.php";
?>

<script>
    //Initialize Select2 Elements
    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })

var productarr=[];

$.ajax({
url:"getorderproduct.php",
method:"get",
dataType: "json",
data:{id:<?php echo $_GET['id']?>},
success:function(data){
//alert("pid");

//console.log(data);

$.each(data, function(key,data){
if(jQuery.inArray(data["product_id"],productarr)!== -1){

  var actualqty = parseInt($('#qty_id'+data["product_id"]).val())+1;
  $('#qty_id'+data["product_id"]).val(actualqty);

  var saleprice=parseInt(actualqty)*data["saleprice"];

  $('#saleprice_id'+data["product_id"]).html(saleprice);
  $('#saleprice_idd'+data["product_id"]).val(saleprice);

 // $("#txtbarcode_id").val("");
  calculate(0,0);

}else{

addrow(data["product_id"],data["product_name"],data["qty"],data["rate"],data["saleprice"],data["stock"],data["barcode"]);

productarr.push(data["product_id"]);

//$("#txtbarcode_id").val("");

function addrow(product_id,product_name,qty,rate,saleprice,stock,barcode){

var tr='<tr>'+

'<input type="hidden" class="form-control barcode" name="barcode_arr[]" id="barcode_id'+barcode+'" value="'+barcode+'" >'+

'<td style="text-align:left; vertical-align:middle; font-size:17px;"><class="form-control product_c" name="product_arr[]" <span class="badge badge-dark">'+product_name+'</span><input type="hidden" class="form-control pid" name="pid_arr[]" value="'+product_id+'" ><input type="hidden" class="form-control product" name="product_arr[]" value="'+product_name+'" >  </td>'+

'<td style="text-align:left; vertical-align:middle; font-size:17px;"><span class="badge badge-primary stocklbl" name="stock_arr[]" id="stock_id'+product_id+'">'+stock+'</span><input type="hidden" class="form-control stock_c" name="stock_c_arr[]" id="stock_idd'+product_id+'" value="'+stock+'"></td>'+

'<td style="text-align:left; vertical-align:middle; font-size:17px;"><span class="badge badge-warning price" name="price_arr[]" id="price_id'+product_id+'">'+saleprice+'</span><input type="hidden" class="form-control price_c" name="price_c_arr[]" id="price_idd'+product_id+'" value="'+saleprice+'"></td>'+

'<td><input type="text" class="form-control qty" name="quantity_arr[]" id="qty_id'+product_id+'" value="'+qty+'" size="1"></td>'+

'<td style="text-align:left; vertical-align:middle; font-size:17px;"><span class="badge badge-success totalamt" name="netamt_arr[]" id="saleprice_id'+product_id+'">'+rate*qty+'</span><input type="hidden" class="form-control saleprice" name="saleprice_arr[]" id="saleprice_idd'+product_id+'" value="'+rate*qty+'"></td>'+

//remove button code start here

// '<td style="text-align:left; vertical-align:middle;"><center><name="remove" class"btnremove" data-id="'+pid+'"><span class="fas fa-trash" style="color:red"></span></center></td>'+
// '</tr>';

'<td><center><button type="button" name="remove" class="btn btn-danger btn-sm btnremove" data-id="'+product_id+'"><span class="fas fa-trash"></span></center></td>'+


'</tr>';

$('.details').append(tr);
calculate(0,0);
}//end function addrow

}});//end function each
$("#txtbarcode_id").val("");
}   // end of success function
})  // end of ajax request



$(function() {

$('#txtbarcode_id').on('change', function() {


var barcode = $("#txtbarcode_id").val();

$.ajax({
url:"getproduct.php",
method:"get",
dataType: "json",
data:{id:barcode},
success:function(data){
//alert("pid");

//console.log(data);


if(jQuery.inArray(data["pid"],productarr)!== -1){

  var actualqty = parseInt($('#qty_id'+data["pid"]).val())+1;
  $('#qty_id'+data["pid"]).val(actualqty);

  var saleprice=parseInt(actualqty)*data["saleprice"];

  $('#saleprice_id'+data["pid"]).html(saleprice);
  $('#saleprice_idd'+data["pid"]).val(saleprice);

 // $("#txtbarcode_id").val("");
  calculate(0,0);
  $("#txtpaid").val("");
$("#txtdue").val("");

}else{

addrow(data["pid"],data["product"],data["saleprice"],data["stock"],data["barcode"]);

productarr.push(data["pid"]);

//$("#txtbarcode_id").val("");

function addrow(pid,product,saleprice,stock,barcode){

var tr='<tr>'+

'<input type="hidden" class="form-control barcode" name="barcode_arr[]" id="barcode_id'+barcode+'" value="'+barcode+'" >'+

'<td style="text-align:left; vertical-align:middle; font-size:17px;"><class="form-control product_c" name="product_arr[]" <span class="badge badge-dark">'+product+'</span><input type="hidden" class="form-control pid" name="pid_arr[]" value="'+pid+'" ><input type="hidden" class="form-control product" name="product_arr[]" value="'+product+'" >  </td>'+

'<td style="text-align:left; vertical-align:middle; font-size:17px;"><span class="badge badge-primary stocklbl" name="stock_arr[]" id="stock_id'+pid+'">'+stock+'</span><input type="hidden" class="form-control stock_c" name="stock_c_arr[]" id="stock_idd'+pid+'" value="'+stock+'"></td>'+

'<td style="text-align:left; vertical-align:middle; font-size:17px;"><span class="badge badge-warning price" name="price_arr[]" id="price_id'+pid+'">'+saleprice+'</span><input type="hidden" class="form-control price_c" name="price_c_arr[]" id="price_idd'+pid+'" value="'+saleprice+'"></td>'+

'<td><input type="text" class="form-control qty" name="quantity_arr[]" id="qty_id'+pid+'" value="'+1+'" size="1"></td>'+

'<td style="text-align:left; vertical-align:middle; font-size:17px;"><span class="badge badge-success totalamt" name="netamt_arr[]" id="saleprice_id'+pid+'">'+saleprice+'</span><input type="hidden" class="form-control saleprice" name="saleprice_arr[]" id="saleprice_idd'+pid+'" value="'+saleprice+'"></td>'+

//remove button code start here

// '<td style="text-align:left; vertical-align:middle;"><center><name="remove" class"btnremove" data-id="'+pid+'"><span class="fas fa-trash" style="color:red"></span></center></td>'+
// '</tr>';

'<td><center><button type="button" name="remove" class="btn btn-danger btn-sm btnremove" data-id="'+pid+'"><span class="fas fa-trash"></span></center></td>'+


'</tr>';

$('.details').append(tr);
calculate(0,0);
$("#").val("");
$("#txtdue").val("");
}//end function addrow

}
$("#txtbarcode_id").val("");
}   // end of success function
})  // end of ajax request
})  // end of onchange function
}); // end of main function


$("#itemtable").delegate(".qty" ,"keyup change", function(){

var quantity=$(this);
var tr = $(this).parent().parent();

if((quantity.val()-0)>(tr.find(".stock_c").val()-0)){

Swal.fire("WARNING!", "SORRY! This Much Of Quantity Is Not Available", "warning");
quantity.val(1);

tr.find(".totalamt").text(quantity.val() * tr.find(".price").text());

tr.find(".saleprice").val(quantity.val() * tr.find(".price").text());
calculate(0,0);
$("#txtpaid").val("");
$("#txtdue").val("");
}else{
tr.find(".totalamt").text(quantity.val() * tr.find(".price").text());

tr.find(".saleprice").val(quantity.val() * tr.find(".price").text());
calculate(0,0);
$("#txtpaid").val("");
$("#txtdue").val("");
}
});



function calculate(dis,paid){

var subtotal=0;
var discount=dis;
var sgst=0;
var cgst=0;
var total=0;
var paid_amt=paid;
var due=0;

$(".saleprice").each(function(){

  subtotal=subtotal+($(this).val()*1);
});

$("#txtsubtotal_id").val(subtotal.toFixed(2));

sgst=parseFloat($("#txtsgst_id_p").val());

cgst=parseFloat($("#txtcgst_id_p").val());

discount=parseFloat($("#txtdiscount_p").val());

sgst=sgst/100;
sgst=sgst*subtotal;

cgst=cgst/100;
cgst=cgst*subtotal;

discount=discount/100;
discount=discount*subtotal;

$("#txtsgst_id_n").val(sgst.toFixed(2));

$("#txtcgst_id_n").val(cgst.toFixed(2));

$("#txtdiscount_n").val(discount.toFixed(2));


total=sgst+cgst+subtotal-discount;
due=total-paid_amt;


$("#txttotal").val(total.toFixed(2));

paid_db=parseFloat($("#txtpaid").val());
due_db=paid_db-total;

$("#txtdue").val(due_db.toFixed(2));

}  //end calculate function


$("#txtdiscount_p").keyup(function(){

var discount=$(this).val();

calculate(discount,0);

});

$("#txtpaid").keyup(function(){

var paid=$(this).val();
var discount=$("#txtdiscount_p").val();
calculate(discount,paid);

});


$(document).on('click','.btnremove',function(){

var removed=$(this).attr("data-id");
productarr=jQuery.grep(productarr,function(value){

  return value!=removed;
  calculate(0,0);
  $("#txtpaid").val("");
$("#txtdue").val("");
})

$(this).closest('tr').remove();
calculate(0,0);
$("#txtpaid").val("");
$("#txtdue").val("");
});
</script>