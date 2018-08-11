<?php
require_once __DIR__ . '\..\..\autoload.php';
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

//Get all courses
$reference = $database->getReference('courses')->orderByKey();
$all_courses = $reference->getSnapshot()->getValue();

//Add course if previously requested
if($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['course']))
{
  echo "<p class = 'feedback success'>".$_POST['course']." added to your courses</p>";
  foreach(array_keys($all_courses) as $course_key){
    if ($all_courses[$course_key] == $_POST['course']){
      $reference = $database->getReference('teachers/"'.$key.'"/courses/'.$course_key);
      $reference->set([
        "active"=>true]);
      }
    }
  }

  //Remove course if previously requested
  if($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['remove']))
  {
    echo "<p class = 'feedback success'>".$_POST['remove']." removed from your courses</p>";
    foreach(array_keys($all_courses) as $course_key){
      if ($all_courses[$course_key] == $_POST['remove']){
        $reference = $database->getReference('teachers/"'.$key.'"/courses/'.$course_key.'/students');
        $snapshot = $reference->getSnapshot()->getValue();
        if(!empty($snapshot)){
          $c_key = str_replace("\"", "", $course_key);
          foreach(array_keys($snapshot) as $k){
            $reference = $database->getReference('students/"'.$k.'"/courses/'.$key.'_'.$c_key);
            $reference->set([]);
          }
        }
        $reference = $database->getReference('teachers/"'.$key.'"/courses/'.$course_key);
        $reference->set([]);
      }
    }
  }

  //Add student to course
  if($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['student_id']))
  {
    foreach (explode(",",$_POST['student_id']) as $student_enroll) {
    $reference = $database->getReference('students/"'.$student_enroll.'"');
    $temp = $reference->getSnapshot()->getValue();
    if(!empty($temp)){
      $reference = $database->getReference('students/"'.$student_enroll.'"/courses');
      $temp = $reference->getSnapshot()->getValue();
      if(!empty($temp) && in_array($key."_".$_POST['course_add'], array_keys($temp))){
        echo "<p class = 'feedback error'> Student ".$student_enroll." already added in ".$all_courses['"'.$_POST['course_add'].'"'].". </p>";
      }else{
        $updates = [
          'students/"'.$student_enroll.'"/courses/'.$key."_".$_POST['course_add'] => ["enrolled"=>true],
          'teachers/"'.$key.'"/courses/"'.$_POST['course_add'].'"/students/'.$student_enroll => $student_enroll
        ];
        $database->getReference() // this is the root reference
        ->update($updates);
        echo "<p class = 'feedback success'>Student ".$student_enroll." added in ".$all_courses['"'.$_POST['course_add'].'"']." succesfully. </p>";
      }
    }else{
      echo "<p class = 'feedback error'> No such student.".$student_enroll." </p>";
    }
  }
  }


  //Get teachers courses
  $reference = $database->getReference('teachers/"'.$key.'"/courses')->orderByKey();
  //Refine list of all courses
  $all_new = [];
  $added_courses = $reference->getSnapshot()->getValue();
  if (!empty($added_courses)){
    foreach(array_keys($all_courses) as $key_1){
      if (!in_array($key_1, array_keys($added_courses))){
        $all_new[$key_1] = $all_courses[$key_1];
      }
    }
  }else{
    $all_new = $all_courses;
  }
  ?>
  <div class="container">
    <h1>Add Courses</h1>
    <div>
      <p>Add new courses to teach<br></p>
      <?php if (!empty($all_new)): ?>
        <table class="overview-table">
          <thead>
            <tr>
              <th><?php echo "Available Courses"; ?></th>
              <th><?php echo "Action"; ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($all_new as $course): ?>
              <tr>
                <td><?php echo $course ?></td>
                <td><form action="<?= config::get("URL"); ?>course/index" method="post">
                  <button type='submit' name='course' value="<?php echo $course; ?>">Add</>
                  </form></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php
        else:
          echo "<b>All courses have been added.</b>";
        endif; ?>
      </div>
      <h1>Your Courses</h1>
      <div>
        <p>Select your courses to add student and make tests<br></p>
        <?php if(!empty($added_courses)): ?>
          <table class="overview-table">
            <thead>
              <tr>
                <th><?php echo "Courses"; ?></th>
                <th><?php echo "Post Test"; ?></th>
                <th><?php echo "Add Student"; ?></th>
                <th><?php echo "Action"; ?></th>
                <th><?php echo "Action"; ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($added_courses as $key_1=>$value_1): ?>
                <tr>
                  <td><?php echo $all_courses[$key_1];?></td>
                  <td>
                    <form action="<?php echo Config::get('URL'); ?>course/test" method="post">
                      <input type="hidden" name="course_test" value= <?php echo $key_1; ?> />
                      <input type="submit" value="Add Test" />
                    </form>
                  </td>
                  <td>
                    <form action="<?php echo Config::get('URL'); ?>course/index" method="post">
                      <input type="text" name="student_id" placeholder="Enter student id" required />
                      <input type="hidden" name="course_add" value= <?php echo $key_1; ?> />
                      <input type="submit" value="Add" />
                    </form>
                  </td>
                  <td><form action="<?= config::get("URL"); ?>course/index" method="post">
                    <button type='submit' name='remove' value="<?php echo $all_courses[$key_1]; ?>">Remove</>
                    </form></td>
                    <td><form action="<?= config::get("URL"); ?>course/overview" method="post">
                      <button type='submit' name='overview' value="<?php echo 0; ?>">Overview</>
                      <input type="hidden" name = "course" value="<?php echo str_replace('"',"",$key_1);?>"></input>
                      <input type="hidden" name = "teacher" value="<?php echo $key;?>"></input>
                      </form></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else:
              echo "<b>No course selected</b>";
            endif; ?>
          </div>
          <!-- echo out the system feedback (error and success messages) -->
          <?php $this->renderFeedbackMessages(); ?>
        </div>
