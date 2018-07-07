<?php
include('../vendor/rake/rake.php');
use NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;
use \NlpTools\Tokenizers\WhitespaceTokenizer;
use \NlpTools\Similarity\JaccardIndex;
use \NlpTools\Similarity\CosineSimilarity;
use \NlpTools\Similarity\Simhash;
use NlpTools\Stemmers\PorterStemmer;
use NlpTools\Documents\TokensDocument;


class Checker
{

static $DEMO_KEY = "DpY0mRqfDtrtLjZZ";
static $thesaurusRequest = "http://words.bighugelabs.com/api/2/fd31a8ee69ba3f5bc990214c97c70d94/";
static $rake  = NULL;
static $marks = [];

public static function check($test,$answers){
  self::$rake = new Rake('../vendor/rake/stoplist_smart.txt');
  foreach ($test as $k => $unit) {
    // $keywords_actual = self::$rake->extract($unit['answer']);
    // $keywords_test = self::$rake->extract($answers[$k]);
    self::oneShot($unit['answer'],$answers[$k]);
  }
  return self::$marks;
}

static function oneShot($s1,$s2){
  //Rapid KeyWord Extraction
  $rake = new Rake('../vendor/rake/stoplist_smart.txt');
  $keywords_actual = $rake->extract($s1);
  $keywords_test = $rake->extract($s2);
 //Difference
 $result = array_diff_assoc($keywords_test,array_intersect_assoc($keywords_actual,$keywords_test));

 //Porter Stemming
 $stemmer = new PorterStemmer();
 $d = new TokensDocument(array_keys($keywords_actual));
 $d->applyTransformation($stemmer);
 //Sentence Generation from Thesaurus
 $match_sim = [];
 $match_ant = [];

 $sentences_sim = [];
 $sentences_ant = [];
 foreach (array_keys($result) as $string) {
 $stem_sim = [];
 $stem_ant = [];
 $sim = [];
 $ant = [];
 	$r = file_get_contents(self::$thesaurusRequest.$string."/json");
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
//Cosine Similarity Score
$cos = new CosineSimilarity();
$cos_sim_array = [];
$tok = new WhitespaceAndPunctuationTokenizer();
foreach($sentences_sim as $k => $sent){
 $setB = $rake->extract($sent);
 $cos_sim_array[$k] = $cos->similarity($setB,$keywords_actual);
}
arsort($cos_sim_array);
$score = array_values($cos_sim_array)[0] * 8;

//Grammar Check
$score = $score + (self::checkGrammar(array_values($cos_sim_array)[0])*2)/100 ;

//Save score
array_push(self::$marks,number_format((float)$score, 2, '.', ''));
}

static function checkGrammar($text){
  $url = 'https://api.textgears.com/check.php';
  $data = array('text' => $text, 'key' => self::$DEMO_KEY);

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
 return json_decode( $result, true )['score'];
}

}
?>
