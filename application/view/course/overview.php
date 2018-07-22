<?php
require_once __DIR__ . '\..\..\autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['overview'])){

  }else{
  echo "<p class = 'feedback error'>Not a valid course. </p>";
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
$course = $database->getReference('courses/"'.$_POST["course"].'"')->getSnapshot()->getValue();
$temp = $database->getReference('teachers/"'.$_POST["teacher"].'"')->getSnapshot()->getValue();
$teacher = $temp['name'];
if(!empty($temp["courses"]['"'.$_POST["course"].'"']["test"])){
$test_count = count($temp["courses"]['"'.$_POST["course"].'"']["test"]);
$tests = $temp["courses"]['"'.$_POST["course"].'"']["test"];
$student_count = count($temp["courses"]['"'.$_POST["course"].'"']["students"]);
$student_all = $temp["courses"]['"'.$_POST["course"].'"']["students"];
}else{
  $test_count = 0;
  $student_count = 0;
}
?>
<div class="container">
  <h3>Course Overview</h3>
  <p><b>Course Name: </b><?php echo $course;?></p>
  <p><b>Course ID: </b><?php echo $_POST["course"];?></p>
  <p><b>Instructor: </b><?php echo $teacher;?></p>
  <p><b>Test Posted: </b><?php echo $test_count;?></p>
  <p><b>Students Enrolled: </b><?php echo $student_count;?></p>
  <h3>Enrolled Students</h3>
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
            if($test_count > 0){
              $total = 0;
              foreach ($tests as $key => $value) {
                if(!empty($value["marks"]['"'.$v.'"'])){
                  $total = $total +  $value["marks"]['"'.$v.'"'];
                }
              }
              echo $total;
            }else{
              echo '<p>-<p>';
            }
            ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
