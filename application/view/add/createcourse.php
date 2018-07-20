<?php
require_once __DIR__ . '\..\..\autoload.php';
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

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

//Post a new test
if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['course_name'])){
  $course_name = $_POST['course_name'];
  $course_key = '"'.time().'"';
  $snapshot = $database->getReference("courses")->orderByValue()
  ->equalTo($course_name)->getSnapshot()->getValue();
  if(!empty($snapshot)){
    echo "<p class = 'feedback error'>Course creation failed. Course already exists.</p>";
  }else{
  $updates["courses/".$course_key] = $course_name;
  $database->getReference()->update($updates);
    echo "<p class = 'feedback success'>Course created succesfully. Course ID:".$course_key." </p>";
  }
}
?>

<div class="container">
<h1>Create Course</h1>
        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>
        <div>
            <p>Create a new course<br></p>
        </div>
        <div style="width: 50%; display: block;">
            <form method="post" action="<?php echo Config::get('URL'); ?>add/createcourse">
                <!-- the user name input field uses a HTML5 pattern check -->
                <input type="text" pattern="[a-zA-Z0-9]{2,64}" name="course_name" placeholder="Course name" required />
                <input type="submit" value="Create Course" />
            </form>
        </div>
</div>
