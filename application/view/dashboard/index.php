<?php
require_once __DIR__ . '\..\..\autoload.php';
require_once __DIR__ .'\..\..\core\FirebaseHelper.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
?>
<div class="container">
    <!-- echo out the system feedback (error and success messages) -->
    <?php $this->renderFeedbackMessages(); ?>
      <?php
      $user = Session::get("user_id");
      $db = DatabaseFactory::getFactory()->getConnection();
      $sql = "SELECT user_creation_timestamp,user_name,user_account_type FROM users WHERE user_id = :user_id";
      $query = $db->prepare($sql);
      $query->execute(array(':user_id' => $user));
      $result = ($query->fetchAll())[0];
      $key = $result->user_creation_timestamp;
      $type = $result->user_account_type;

      //Get student or teacher
      FirebaseHelper::init();
      $firebase = FirebaseHelper::$firebase;
      $database = $firebase->getDatabase();
      $reference = NULL;
      if($type != 7){
        $reference = $database->getReference('students')->orderByKey()->equalTo("\"".$key."\"");
      }else{
        $reference = $database->getReference('teachers')->orderByKey()->equalTo("\"".$key."\"");
        }
      $snapshot = $reference->getSnapshot()->getValue();
      $snapshot = $snapshot['"'.$key.'"'];

      $reference = $database->getReference('courses')->orderByKey();
      $courses = $reference->getSnapshot()->getValue();
      // $updates = [
      //   'courses/'."\"3\"" => "Geography"
      // ];
      //
      // $database->getReference() // this is the root reference
      // ->update($updates);
      ?>

      <p><b>Username: </b><?php echo $snapshot["name"];?></p>
    <h2>Your Courses</h2>
      <?php
        if (!empty(array_keys($snapshot["courses"]))){
          foreach($snapshot["courses"] as $k=>$v){
            if ($type == 7 ){
              echo "<h3>".$courses[$k]."</h3>";
            }else{
              $ks = explode("_", $k);
              echo "<h3>".$courses['"'.$ks[count($ks)-1].'"']."</h3>";
              //Get tests
              if(!empty($v["tests"])){
              foreach ($v["tests"] as $k1 => $v1) {
                echo "<p><b>Test ID: </b>".$k1." <b>Score: </b>".array_sum($v1["marks"])."</p>";
              }
            }
            }

          }
        }else{
          echo "<br><p>No courses</p>";
        }
      ?>
    </div>
