<?    
include '../cors.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_POST['action']) || fail(400,'must have an "action" parameter');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
db("set search_path to import,pg_temp");
ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_POST['community']??'') || fail(403,'access denied');
extract(cdb("select sesite_url,selink_user_id from sesite where sesite_id=$1",$_POST['sesiteid']));

function file_get_contents_retry($url,$attempts=3) {
  $content = file_get_contents($url);
  $attempts--;
  if( empty($content) && ($attempts>0) ){
    usleep(10);
    return file_get_contents_retry($url,$attempts);
  }
  return $content;
}

switch($_POST['action']) {
  case 'new':
    db("select new_import($1::integer,$2)",$_POST['sesiteid'],$_POST['seids']);
    libxml_use_internal_errors(true);
    // turn posted string into an array
    $seids = explode(' ',$_POST['seids']);
    // pop last id if '*'
    $last = array_pop($seids);
    $all = ($last==='*');
    if(!$all) array_push($seids,$last);
    // at this point there should be at least one id
    if(count($seids)===0) fail(400,'no id or url given');
    // map urls/ids to integer ids
    $seids = array_map(function($id){
      $id = preg_replace('/.*\/([0-9]+)$/','$1',preg_replace('/.*\/([0-9]+)\/.*/','$1',preg_replace('/.*\/[0-9]+\/[^\/]+\/([0-9]+)$/','$1',preg_replace('/.*#([0-9]+)$/','$1',$id))));
      if(!ctype_digit($id)) fail(400,'"'.$id.'" is not an integer or a recognized SE url');
      $id=intval($id);
      return $id;
    },$seids);
    // check if first id is a question or an answer (in the latter case find the question id)
    $doc = new DOMDocument();
    $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents_retry($sesite_url.'/posts/'.$seids[0].'/edit'));
    $xpath = new DOMXpath($doc);
    $elements = $xpath->query("//a[contains(concat(' ', @class, ' '), ' question-hyperlink ')]");
    if(count($elements)){
      $seqid = explode('/',$elements[0]->getAttribute('href'))[2];
      $seaids = array_unique($seids);
    }else{
      $seqid = $seids[0];
      $seaids = array_unique(array_slice($seids,1));
    }
    // get the SE user-id and user-name for the question asker
    $doc = new DOMDocument();
    $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents_retry($sesite_url.'/questions/'.$seqid));
    $xpath = new DOMXpath($doc);
    $elements = $xpath->query("//div[@id='question-header']/h1/a");
    $title = $elements[0]->childNodes[0]->nodeValue;
    $elements = $xpath->query("//div[@id='question']//div[contains(concat(' ', @class, ' '), ' owner ')]//div[contains(concat(' ', @class, ' '), ' user-action-time ')]/span");
    $at = $elements[0]->getAttribute('title');
    $elements = $xpath->query("//div[@id='question']//div[contains(concat(' ', @class, ' '), ' owner ')]//div[contains(concat(' ', @class, ' '), ' user-details ')]/a[not(@id)]");
    $qanon = (count($elements)===0);
    if(!$qanon){
      $seuid = explode('/',$elements[0]->getAttribute('href'))[2];
      $seuname = $elements[0]->textContent;
    }
    // get every answer id with matching SE user-id, user-name and timestamp
    $answers = [];
    $elements = $xpath->query("//div[contains(concat(' ', @class, ' '), ' answer ')]/@id");
    foreach($elements as $element){
      $a = $xpath->query("//div[@id='".$element->textContent."']"
                        ."//div[contains(concat(' ', @class, ' '), ' post-signature ') and not(following-sibling::div[contains(concat(' ', @class, ' '), ' post-signature ')])]"
                        ."//div[contains(concat(' ', @class, ' '), ' user-details ')]/a[not(@id)]");
      $answers[explode('-',$element->textContent)[1]] = (count($a)===0)?["anon"=>true]:["anon"=>false,"uid"=>explode('/',$a[0]->getAttribute('href'))[2],"uname"=>$a[0]->textContent];
      $answers[explode('-',$element->textContent)[1]]['at'] = ($xpath->query("//div[@id='".$element->textContent."']//time[@itemprop='dateCreated']"))[0]->getAttribute('datetime');
    }
   //error_log($seqid);
    $id = ccdb("select get_question($1)",$seqid);
    if(!$id){
      // get the markdown and tags for the question
      $doc = new DOMDocument();
      $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents_retry($sesite_url.'/posts/'.$seqid.'/edit'));
      $xpath = new DOMXpath($doc);
      $elements = $xpath->query("//input[@id='tagnames']/@value");
      $tags = $elements[0]->textContent;
      $xpath = new DOMXpath($doc);
      $elements = $xpath->query("//textarea[@id='wmd-input-".$seqid."']");
      $markdown = $elements[0]->textContent;
      $markdown = preg_replace('/<!--[^\n]*-->/m','',$markdown);
      $markdown = preg_replace('/^(#+)([^\n# ][^\n]*[^\n# ])(#+)$/m','$1 $2 $3',$markdown);
      $markdown = preg_replace('/^(#+)([^\n# ])/m','$1 $2',$markdown);
      $markdown = preg_replace('/http:\/\/i.stack.imgur.com\//','https://i.stack.imgur.com/',$markdown);
     //error_log('length: '.strlen($markdown));
      // add the question
      if($qanon){
        $id=ccdb("select new_questionanon($1,$2,$3,$4,$5,$6::timestamptz)",$title,$markdown,$_POST['sesiteid'],$tags,$seqid,$at);
      }else{
        $id=ccdb("select new_question($1,$2,$3,$4,$5,$6,$7,$8::timestamptz)",$title,$markdown,$tags,$_POST['sesiteid'],$seqid,$seuid,$seuname,$at);
      }
    }
   //echo '<pre>'; var_dump($id); echo '</pre>'; exit;
    ccdb("select login_question($1::uuid,$2)",$_COOKIE['uuid'],$id) || fail(500,'failed to import answers');
    // generate an array of answers to import
    $aids = [];
    if($all){
      $doc = new DOMDocument();
      $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents_retry($sesite_url.'/questions/'.$seqid));
      $xpath = new DOMXpath($doc);
      $elements = $xpath->query("//div[contains(concat(' ', @class, ' '), ' answer ')]");
      foreach($elements as $element) array_push($aids,explode('-',$element->getAttribute('id'))[1]);
    }else{
      if($seaids) $aids = $seaids;
      if($selink_user_id){
        $doc = new DOMDocument();
        $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents_retry($sesite_url.'/questions/'.$seqid));
        $xpath = new DOMXpath($doc);
        $elements = $xpath->query("//div[contains(concat(' ', @class, ' '), ' answer ') and "
                                 ."boolean(.//div[contains(concat(' ', @class, ' '), ' post-signature ') and not(following-sibling::div[contains(concat(' ', @class, ' '), ' post-signature ')])]"
                                 ."//div[contains(concat(' ', @class, ' '), ' user-details ')]/a[contains(@href,'/".$selink_user_id."/')])]");
        foreach($elements as $element){
          $aid = explode('-',$element->getAttribute('id'))[1];
          if(!in_array($aid,$aids,true)) array_push($aids,$aid);
        }
      }
    }
   //error_log('aids: '.print_r($aids,true));
    // import each selected answer
    foreach($aids as $aid){
      if(!ccdb("select get_answer($1)",$aid)){
        $doc = new DOMDocument();
        $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents_retry($sesite_url.'/posts/'.$aid.'/edit'));
        $xpath = new DOMXpath($doc);
        $elements = $xpath->query("//textarea[@id='wmd-input-".$aid."']");
        $markdown = $elements[0]->textContent;
        $markdown = preg_replace('/<!--[^\n]*-->/m','',$markdown);
        $markdown = preg_replace('/^(#+)([^\n# ][^\n]*[^\n# ])(#+)$/m','$1 $2 $3',$markdown);
        $markdown = preg_replace('/^(#+)([^\n# ])/m','$1 $2',$markdown);
        $markdown = preg_replace('/http:\/\/i.stack.imgur.com\//','https://i.stack.imgur.com/',$markdown);
        if($answers[$aid]['anon']){
          db("select new_answeranon($1,$2,$3,$4)",$markdown,$_POST['sesiteid'],$aid,$answers[$aid]['at']);
        }else{
          db("select new_answer($1,$2,$3,$4,$5,$6)",$markdown,$_POST['sesiteid'],$aid,$answers[$aid]['uid'],$answers[$aid]['uname'],$answers[$aid]['at']);
        }
      }
    }
    header('Location: //topanswers.xyz/'.$_POST['community'].'?q='.$id);
    exit;
  default: fail(400,'unrecognized action');
}
