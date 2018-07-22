<?php
include('../vendor/autoload.php');
include('../vendor/rake/rake.php');

use NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;
use \NlpTools\Tokenizers\WhitespaceTokenizer;
use \NlpTools\Similarity\JaccardIndex;
use \NlpTools\Similarity\CosineSimilarity;
use \NlpTools\Similarity\Simhash;
use NlpTools\Stemmers\PorterStemmer;
use NlpTools\Documents\TokensDocument;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;



if (PHP_SAPI != 'cli') {
	echo "<pre>";
}

$text = "This is a simple example of a Tokenizer.";
$s1 = "Sun is giant gas filled star that is very bright.";
$s2 = "Sun is giant gas filled star that is luminous.";
$tok = new WhitespaceTokenizer();
$J = new JaccardIndex();
$cos = new CosineSimilarity();
$simhash = new Simhash(16); // 16 bits hash

$setA = $tok->tokenize($s1);
$setB = $tok->tokenize($s2);

$thesaurusRequest = "http://words.bighugelabs.com/api/2/fd31a8ee69ba3f5bc990214c97c70d94/";
$DEMO_KEY = "DpY0mRqfDtrtLjZZ";

$strings = array(
	1 => 'Weather today is rubbish',
	2 => 'This cake looks amazing',
	3 => 'His skills are mediocre',
	4 => 'He is very talented',
	5 => 'She is seemingly very agressive',
	6 => 'Marie was enthusiastic about the upcoming trip. Her brother was also passionate about her leaving - he would finally have the house for himself.',
	7 => 'To be or not to be?',
	8 => "I don't have money!",
	9 => 'The wall does not require color.',
	10 => 'Paint is not required on this wall'
);

require_once __DIR__ . '\..\..\autoload.php';
//$firebase = new \Geckob\Firebase\Firebase('../application/descriptive-checker-firebase-adminsdk-5df5h-c83dc2c9fc.json');
?>

<div class="container">
    <h1>Descriptive Checker using RAKE</h1>
  <!--  <div>
        <?php $this->renderFeedbackMessages(); ?>
        <p>
		<?php
		/*
		$tok = new WhitespaceAndPunctuationTokenizer();
		//print_r($tok->tokenize($text));
		printf("</br></br>Sentence 1: %s",$s1);
		printf("</br>Sentence 2: %s",$s2);
		printf(
    "</br></br><b>Similarity Comparison:</b></br>Jaccard:  %.3f
    </br>Cosine:   %.3f
    </br>Simhash:  %.3f
    </br>SimhashA: %s
    </br>nSimhashB: %s
    ",
    $J->similarity(
        $setA,
        $setB
    ),
    $cos->similarity(
        $setA,
        $setB
    ),
    $simhash->similarity(
        $setA,
        $setB
    ),
    $simhash->simhash($setA),
    $simhash->simhash($setB)
);

$sentiment = new \PHPInsight\Sentiment();
foreach ($strings as $string) {

	// calculations:
	$scores = $sentiment->score($string);
	$class = $sentiment->categorise($string);

	// output:
	echo "\n";
	echo "String: $string\n";
	echo "Dominant: $class, scores: ";
	print_r($scores);
}
echo "\n<b>Thesaurus Results</b>\n";
		$result = file_get_contents($thesaurusRequest."fire/json");
		print($result);
		*/
		?>
        </p>
   </div> -->
   <h3>Sentences</h3>
<?php
		echo $s1;
		echo "\n";
		echo $s2;
   ?>
   <h3>Keyword Extraction using RAKE</h3>
<?php
$rake = new Rake('../vendor/rake/stoplist_smart.txt');
$keywords = $rake->extract($s1);
$keywords_actual = [];
foreach($keywords as $k => $v){
	foreach(explode(" ",$k) as $k1){
		$keywords_actual[$k1] = $v;
	}
}
$keywords = $rake->extract($s2);
$keywords_test = [];
foreach($keywords as $k => $v){
	foreach(explode(" ",$k) as $k1){
		$keywords_test[$k1] = $v;
	}
}
print_r($keywords_actual);
echo "\n";
print_r($keywords_test);
   ?>
      <!-- <h3>Preprocessing - Stemming</h3> -->
<!-- <?php
$stemmer = new PorterStemmer();
$d = new TokensDocument(array_keys($keywords_actual));
$d->applyTransformation($stemmer);
print_r($d->getDocumentData());
   ?> -->

   <h3>Difference of keywords</h3>
<?php
$result = array_diff(array_keys($keywords_test),array_intersect(array_keys($keywords_actual),array_keys($keywords_test)));
print_r($result);
   ?>

   <h3>Create New Sentences from Thesaurus</h3>

<?php
$match_sim = [];
$match_ant = [];

$sentences_sim = [];
$sentences_ant = [];
foreach ($result as $string) {
$stem_sim = [];
$stem_ant = [];
$sim = [];
$ant = [];
	$r = file_get_contents($thesaurusRequest.$string."/json");
	$thes_array = json_decode($r, true);
	foreach ($thes_array as $a){
			if (in_array('sim',array_keys($a))){
				$sim = array_merge($sim,$a['sim']);
			}
			if (in_array('ant',array_keys($a))){
				$ant = array_merge($ant,$a['ant']);
			}
	}

	$d = new TokensDocument($sim);
	$d->applyTransformation($stemmer);
	$stem_sim = $d->getDocumentData();

	$d = new TokensDocument($ant);
	$d->applyTransformation($stemmer);
	$stem_ant = $d->getDocumentData();

	foreach (array_keys($keywords_actual) as $findme){
		if (in_array($stemmer->stemAll(explode(" ", $findme))[0],$stem_sim)) {
			array_push($match_sim,$findme."$".$string);
		}
		if (in_array($stemmer->stemAll(explode(" ", $findme))[0],$stem_ant)) {
			array_push($match_ant,$findme."$".$string);
		}
	}
	// echo "\nMatched Synonyms\n";
	// print_r($match_sim);
	//
	// echo "\nMatched Antonyms\n";
	// print_r($match_ant);

	foreach($match_sim as $token){
		$sentence = $s2;
		$tokens = explode("$",$token);
		$sentence = str_replace($tokens[1],$tokens[0],$sentence);
		foreach($match_sim as $tok){
			$toks = explode("$",$tok);
			array_push($sentences_sim,str_replace($toks[1],$toks[0],$sentence));
		}
	}

	foreach($match_ant as $token){
		$sentence = $s2;
		$tokens = explode("$",$token);
		//$sentence = str_replace($tokens[1],$tokens[0],$sentence);
		foreach($match_ant as $tok){
			$toks = explode("$",$tok);
			array_push($sentences_ant,str_replace($toks[1],$toks[0],$sentence));
		}
	}
}
	array_push($sentences_sim,$s2);
	print_r($sentences_sim);
	print_r($sentences_ant);
   ?>
   <h3>Best Sentence using Rake Score</h3>
   <?php
   $cos_sim_array = [];
   $tok = new WhitespaceAndPunctuationTokenizer();
	//print_r($tok->tokenize($text));
   foreach($sentences_sim as $k => $sent){
		$setB = $rake->extract($sent);
		//$cos_sim_array[$k] = $cos->similarity($setB,$keywords_actual);
		$temp = [];
		foreach($setB as $k1 => $v1){
			foreach(explode(" ",$k1) as $k2){
				$temp[$k2] = $v1;
			}
		}
		$setB = $temp;
		$intersect = array_intersect(array_keys($setB),array_keys($keywords_actual));
		$temp = [];
 	 	foreach($intersect as $key){
		  $temp[$key]= $setB[$key];
		}
		$cos_sim_array[$k] = array_sum($temp)*10/array_sum($keywords_actual);
	 }
   arsort($cos_sim_array);
	 echo "<p><b>Selected Sentence</b>:<br>".$sentences_sim[key($cos_sim_array)]."<br>";
   echo "<p><b>Score: <b>".array_values($cos_sim_array)[0]."</p>";
   ?>

   <h3>Grammar Check</h3>
   <?php
   $url = 'https://api.textgears.com/check.php';
   $data = array('text' => array_values($cos_sim_array)[0], 'key' => $DEMO_KEY);

// use key 'http' even if you send the request to https://...
	$options = array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    )
	);
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	print_r($result);
   ?>
</div>
