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
if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['next_test_key'])){
  $course_key = $_POST['course_test'];
  $next_test_key = $_POST['next_test_key'];
  if (!empty($_POST['question'])){
    $updates = [];
    $node = 'teachers/"'.$key.'"/courses/"'.$course_key.'"/test/';
    for ($i = 0 ; $i < count($_POST['question']); $i++) {
      $updates[$node."/".$next_test_key."/paper"."/".$i."/question"] = $_POST['question'][$i];
      $updates[$node."/".$next_test_key."/paper"."/".$i."/answer"] = $_POST['answer'][$i];
    }
    $database->getReference() // this is the root reference
    ->update($updates);
    echo "<p class = 'feedback success'>Test added succesfully. </p>";
  }
}

//Remove a Test
if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['remove_test'])){
  $course_key = $_POST['course_test'];
  $remove_key = $_POST['remove_test'];
  $reference = $database->getReference('teachers/"'.$key.'"/courses/"'.$course_key.'"/test/'.$remove_key);
  $reference->set([]);
    echo "<p class = 'feedback success'>Test removed succesfully. </p>";
}

//Get Posted Tests
$posted_test = [];
$course_key = "";
if ($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['course_test'])){
  $course_key = $_POST['course_test'];
  $reference = $database->getReference('teachers/"'.$key.'"/courses/"'.$course_key.'"/test');
  $posted_test = $reference->getSnapshot()->getValue();
}else{
  header("Location: ".Config::get('URL')."course/index");
  exit;
}
$next_test_key = "test_".time();
?>

<div class="container">
    <h1>Create Test</h1>
    <!-- echo out the system feedback (error and success messages) -->
    <?php $this->renderFeedbackMessages(); ?>
    <div class = "box">
      <h3>Posted Tests</h3>
      <?php if (!empty($posted_test)): ?>
      <table class="overview-table">
      <thead>
      <tr>
      <th><?php echo "Posted Test"; ?></th>
      <th><?php echo "Action"; ?></th>
      <th><?php echo "View"; ?></th>
      </tr>
      </thead>
      <tbody>
      <?php foreach (array_keys($posted_test) as $test): ?>
      <tr>
      <td><?php echo $test ?></td>
      <td><form action="<?php echo Config::get('URL'); ?>course/test" method="post">
        <input type="hidden" name = "course_test" value="<?php echo $course_key;?>"></input>
        <button type='submit' name='remove_test' value="<?php echo $test; ?>">Remove</>
      </form></td>
      <td><form action="<?php echo Config::get('URL'); ?>course/view" method="post">
        <input type="hidden" name = "course" value="<?php echo $course_key;?>"></input>
          <input type="hidden" name = "teacher" value="<?php echo $key;?>"></input>
        <button type='submit' name='view_test' value="<?php echo $test; ?>">View</>
      </form></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
      </table>
    <?php
  else:
    echo "<b>No test posted yet.</b>";
  endif;?>
    </div>
    <div class="box">
          <b>Test ID: </b><?php echo $next_test_key; ?><br><br>
          <input type="text" id="member" name="member" value="">Number of questions: <br />
<a href="#" id="filldetails" onclick="addFields()">Generate template</a>
<br>
<form method="post" action="<?php echo Config::get('URL'); ?>course/test">
    <div id="container"/>
    </div>
    <input type="hidden" name = "course_test" value="<?php echo $course_key;?>"></input>
    <input type="hidden" name = "next_test_key" value="<?php echo $next_test_key;?>"></input>
    <input type="submit" value="Submit"></input>
  </form>
</div>

<script>
function addFields(){
            var number = document.getElementById("member").value;
            var container = document.getElementById("container");
            while (container.hasChildNodes()) {
                container.removeChild(container.lastChild);
            }
            for (i=0;i<number;i++){
                container.appendChild(document.createTextNode("Question " + (i+1)));
                var input = document.createElement("input");
                input.type = "text";
                input.name = "question[]";
                input.required = true;
                container.appendChild(input);
                container.appendChild(document.createElement("br"));
                container.appendChild(document.createTextNode("Answer " + (i+1)+"     "));
                var input = document.createElement("input");
                input.type = "text";
                container.appendChild(input);
                input.name = "answer[]"
                input.required = true;
                container.appendChild(document.createElement("br"));
                container.appendChild(document.createElement("br"));
            }
        }
</script>
