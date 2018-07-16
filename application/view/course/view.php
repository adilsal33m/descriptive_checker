<?php
require_once __DIR__ . '\..\..\autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['view_test'])){
  $test_id = $_POST['view_test'];
  $course = $_POST['teacher']."_".$_POST['course'];
  $c_key = '"'.explode("_",$course)[1].'"';
  $t_key = '"'.explode("_",$course)[0].'"';
}else{
  echo "<p class = 'feedback error'>No test found. </p>";
}

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
$reference = $database->getReference('teachers/'.$t_key."/courses"."/".$c_key."/test"."/".$test_id."/marks");
$student_present = $reference->getSnapshot()->getValue();
$reference = $database->getReference('teachers/'.$t_key."/courses"."/".$c_key."/students");
$student_all = $reference->getSnapshot()->getValue();
?>
<div class="container">
  <h1>View Test</h1>
  <?php $this->renderFeedbackMessages(); ?>
  <div class = "box">
    <h3>Question and Answers</h3>
    <?php foreach($test as $unit):?>
      <p>Q. <?php echo $unit['question'];?></p>
      <p>A. <?php echo $unit['answer'];?></p>
      <br>
    <?php endforeach;?>
  </div>
  <h3>Student Attendance</h3>
  <table>
    <thead>
      <tr>
        <th><?php echo "Student ID"; ?></th>
        <th><?php echo "Name"; ?></th>
        <th><?php echo "Marks"; ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($student_all)): ?>
      <?php foreach ($student_all as $k => $v): ?>
        <tr>
          <td><?php echo $v ?></td>
          <td>
            <?php echo $database->getReference('students/"'.$v.'"/name')->getSnapshot()->getValue(); ?>
          </td>
          <td>
            <?php
            if(!empty($student_present) && array_key_exists('"'.$v.'"', $student_present)){
              echo $student_present['"'.$v.'"'];
            }else{
              echo '<p>ABSENT<p>';
            }
            ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
