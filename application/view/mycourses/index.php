<?php
require_once __DIR__ . '\..\..\autoload.php';
require_once __DIR__ .'\..\..\core\FirebaseHelper.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

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
$reference = NULL;
?>
<div class="container">
  <div class="box">
    <h1>Your Courses</h1>
    <div>
      <?php
      $reference = $database->getReference('students/"'.$key.'"/courses')->orderByKey();
      $snapshot = $reference->getSnapshot()->getValue();
      if(empty($snapshot)):
        echo "<b>You are not currently enrolled in any course</b>";
      else:?>
      <table class="overview-table">
        <thead>
          <tr>
            <th><?php echo "Course ID"; ?></th>
            <th><?php echo "Course Name"; ?></th>
            <th><?php echo "Instructor"; ?></th>
            <th><?php echo "Action"; ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach(array_keys($snapshot) as $course):?>
            <?php
            $c_key = '"'.explode("_",$course)[1].'"';
            $t_key = '"'.explode("_",$course)[0].'"';
            $reference = $database->getReference('teachers/'.$t_key."/name");
            $teacher_name = $reference->getSnapshot()->getValue();
            $reference = $database->getReference('courses/'.$c_key);
            $course_name = $reference->getSnapshot()->getValue();

            //get first test id for a course`
            $reference = $database->getReference('teachers/'.$t_key."/courses/".$c_key."/test");
            $tests = $reference->getSnapshot()->getValue();
            ?>
            <tr>
              <td><?php echo $course;?></td>
              <td><?php echo $course_name;?></td>
              <td><?php echo $teacher_name;?></td>
              <td>
                <?php
                $test_id = "No test posted";
                if (!empty($tests)):
                  while(true){
                    $temp = key($tests);
                    $reference = $database->getReference('students/"'.$key.'"/courses/'.$course."/"."tests/".$temp);
                    $snapshot = $reference->getSnapshot()->getValue();
                    if (!empty($snapshot)){
                      array_shift($tests);
                    }else{
                      $test_id = $temp;
                      break;
                    }
                    if(empty($tests)){
                      break;
                    }
                  }
                endif;
                echo $test_id;
                ?>
                <?php if(strcmp($test_id,"No test posted")):?>
                  <form action="<?php echo Config::get('URL'); ?>mycourses/test" method="post">
                    <input type="hidden" name="course_test" value= <?php echo $course; ?> />
                    <input type="hidden" name="test_id" value= <?php echo $test_id; ?> />
                    <input type="hidden" name="student_id" value= <?php echo $key; ?> />
                    <input type="submit" value="Take Test" />
                  </form>
                <?php endif;?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif;?>
  </div>
  <!-- echo out the system feedback (error and success messages) -->
  <?php $this->renderFeedbackMessages(); ?>
</div>
</div>
