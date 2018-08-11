<?php
require_once __DIR__ . '\..\..\autoload.php';
require_once __DIR__ .'\..\..\core\FirebaseHelper.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['test_id'])){
  $test_id = $_POST['test_id'];
}else{
  header("Location: ".Config::get('URL')."mycourses/index");
  exit;
}
if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['course_test'])){
  $course = $_POST['course_test'];
  $c_key = '"'.explode("_",$course)[1].'"';
  $t_key = '"'.explode("_",$course)[0].'"';
}
//Firebase
FirebaseHelper::init();
$firebase = FirebaseHelper::$firebase;
$user = Session::get("user_id");
$db = DatabaseFactory::getFactory()->getConnection();
$sql = "SELECT user_creation_timestamp,user_name,user_account_type FROM users WHERE user_id = :user_id";
$query = $db->prepare($sql);
$query->execute(array(':user_id' => $user));
$result = ($query->fetchAll())[0];
$key = $result->user_creation_timestamp;
$type = $result->user_account_type;
$database = $firebase->getDatabase();
$reference = $database->getReference('teachers/'.$t_key."/courses"."/".$c_key."/test"."/".$test_id."/paper");
$test = $reference->getSnapshot()->getValue();

?>
<div class="container">
  <div class="box">
    <h1>Test</h1>
    <p><b>Test ID: </b><?php echo $test_id;?></p>
    <form action="<?php echo Config::get('URL'); ?>mycourses/compute" method="post">
      <?php foreach($test as $unit):?>
        <p>Q. <?php echo $unit['question'];?></p>
        <input type="text" style="width:300px;" name="answers[]" required autocomplete="off" />
      <?php endforeach;?>
      <br><br>
      <input type="hidden" name="course_test" value= <?php echo $course; ?> />
      <input type="hidden" name="test_id" value= <?php echo $test_id; ?> />
      <input type="hidden" name="student_id" value= <?php echo $key; ?> />
      <input type="hidden" name="questions" value= <?php echo base64_encode(serialize($test)); ?> />
      <input type="submit" value="Submit" />
      <input type="hidden" name="csrf_token" value="<?= Csrf::makeToken(); ?>" />
    </form>
    <div>
    </div>
