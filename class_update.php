<html>
<body>  
<?php
$con = mysqli_connect("localhost","root","","xyz polytechnic"); //connect to database
if (!$con){
	die('Could not connect: ' . mysqli_connect_errno()); //return error is connect fail
}

$query= $con->prepare("UPDATE class SET class_code=?, course_code=?,class_type=? WHERE class_code=?");

$upd_classcode = htmlspecialchars($_POST["upd_classcode"]);
$upd_coursecode = htmlspecialchars($_POST["upd_coursecode"]);
$upd_classtype = htmlspecialchars($_POST["upd_classtype"]);
$original_classcode = htmlspecialchars($_POST["original_classcode"]); // The original class code to identify the record




//bind the parameters
$query->bind_param('ssss', $upd_classcode,$upd_coursecode,$upd_classtype, $original_classcode); 

if ($query->execute()){
  echo "Update Query executed.";
  header("location:class_record.php");
}else{
  echo "Error executing UPDATE query.";
}
?>
</body>
</html>