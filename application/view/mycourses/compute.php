<?php
require_once __DIR__ . '\..\..\autoload.php';
require_once __DIR__ .'\..\..\core\FirebaseHelper.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

if ($_SERVER['REQUEST_METHOD'] == "POST" and
  isset($_POST['answers']) and
  isset($_POST['test_id']) and
  (Session::get('marks') != null) and
  isset($_POST['course_test'])){
  $answers = $_POST['answers'];
  $test_id = $_POST['test_id'];
  $marks = Session::get('marks');
  $course = $_POST['course_test'];
  $question = unserialize(base64_decode($_POST['questions']));
  $correct = [];
  $actual_marks = [];
  foreach ($question as $k => $unit) {
    $correct[$k] = $unit['answer'];
    $actual_marks = $unit['marks'];
  }
  $c_key = '"'.explode("_",$course)[1].'"';
  $t_key = '"'.explode("_",$course)[0].'"';
}else{
  header("Location: ".Config::get('URL')."mycourses/index");
  exit;
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
$reference = $database->getReference('teachers/'.$t_key."/courses"."/".$c_key."/test"."/".$test_id);
$test = $reference->getSnapshot()->getValue();

$reference = $database->getReference('students/"'.$key.'"/courses/'.$course."/"."tests/".$test_id);
$updates = [];
$updates['students/"'.$key.'"/courses/'.$course."/"."tests/".$test_id."/completed"]= true;
$updates['teachers/'.$t_key."/courses"."/".$c_key."/test"."/".$test_id."/marks".'/"'.$key.'"'] = $key;
foreach($answers as $k => $a){
  $updates['students/"'.$key.'"/courses/'.$course."/"."tests/".$test_id."/answers"."/".$k]= $a;
  $updates['students/"'.$key.'"/courses/'.$course."/"."tests/".$test_id."/marks"."/".$k]= $marks[$k];
}
$updates['teachers/'.$t_key."/courses"."/".$c_key."/test"."/".$test_id."/marks".'/"'.$key.'"'] = array_sum($marks);
$database->getReference()->update($updates);
?>
<div class="container">
    <h1>Result for Test ID: <?php echo $test_id; ?></h1>
    <?php
    foreach ($marks as $key => $mark) {
      echo "<br><p><b>Your Answer:</b><br>".$answers[$key]."</p>";
      echo "<p><b>Correct Answer:</b><br>".$correct[$key]."</p>";
      echo "<b>Score: ".$mark."</b> out of ".$actual_marks[$key]."<br>";
    }?>
</div>
