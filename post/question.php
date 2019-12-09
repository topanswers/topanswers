<?    
include '../cors.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_POST['action']) || fail(400,'must have an "action" parameter');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
include '../db.php';
ccdb("select login($1)",$_COOKIE['uuid']) || fail(403,'invalid uuid');

switch($_POST['action']) {
  case 'new-tag': exit(ccdb("select new_question_tag($1,$2)",$_POST['id'],$_POST['tagid']));
  case 'remove-tag': exit(ccdb("select remove_question_tag($1,$2)",$_POST['id'],$_POST['tagid']));
  case 'new':
    $id=ccdb("select new_question((select community_id from get.community where community_name=$1),(select question_type from get.question_type_enums where question_type=$2),$3,$4,$5,$6)",$_POST['community'],$_POST['type'],$_POST['title'],$_POST['markdown'],$_POST['license'],$_POST['codelicense']);
    if($id){?>
      <!doctype html>
      <html>
      <head>
        <script>
          localStorage.removeItem('<?=$_POST['community']?>.ask');
          localStorage.removeItem('<?=$_POST['community']?>.ask.title');
          localStorage.removeItem('<?=$_POST['community']?>.ask.type');
          window.location.href = '//topanswers.xyz/<?=$_POST['community']?>?q=<?=$id?>';
        </script>
      </head>
      </html><?}
    exit;
  case 'new-se':
    db("select new_import(community_id,$2,$3) from get.community where community_name=$1",$_POST['community'],$_POST['seqid'],$_POST['seaids']);
    extract(cdb("select sesite_url,my_community_se_user_id from get.community join get.sesite on community_sesite_id=sesite_id natural join get.my_community where community_name=$1",$_POST['community']));
    libxml_use_internal_errors(true);
    // get the SE user-id and user-name for the question asker
    $doc = new DOMDocument();
    $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents($sesite_url.'/questions/'.$_POST['seqid']));
    $xpath = new DOMXpath($doc);
    $elements = $xpath->query("//div[@id='question-header']/h1/a");
    $title = $elements[0]->childNodes[0]->nodeValue;
    $elements = $xpath->query("//div[@id='question']//div[contains(concat(' ', @class, ' '), ' owner ')]//div[contains(concat(' ', @class, ' '), ' user-details ')]/a");
    $qanon = (count($elements)===0);
    if(!$qanon){
      $seuid = explode('/',$elements[0]->getAttribute('href'))[2];
      $seuname = $elements[0]->textContent;
    }
    // get every answer id with matching SE user-id and user-name
    $answers = [];
    $elements = $xpath->query("//div[contains(concat(' ', @class, ' '), ' answer ')]/@id");
    foreach($elements as $element){
      $a = $xpath->query("//div[@id='".$element->textContent."']"
                        ."//div[contains(concat(' ', @class, ' '), ' post-signature ') and not(following-sibling::div[contains(concat(' ', @class, ' '), ' post-signature ')])]"
                        ."//div[contains(concat(' ', @class, ' '), ' user-details ')]/a");
      $answers[explode('-',$element->textContent)[1]] = (count($a)===0)?["anon"=>true]:["anon"=>false,"uid"=>explode('/',$a[0]->getAttribute('href'))[2],"uname"=>$a[0]->textContent];
    }
    // get the markdown and tags for the question
    $doc = new DOMDocument();
    $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents($sesite_url.'/posts/'.$_POST['seqid'].'/edit'));
    $xpath = new DOMXpath($doc);
    $elements = $xpath->query("//input[@id='tagnames']/@value");
    $tags = $elements[0]->textContent;
    $xpath = new DOMXpath($doc);
    $elements = $xpath->query("//textarea[@id='wmd-input-".$_POST['seqid']."']");
    $markdown = $elements[0]->textContent;
    $markdown = preg_replace('/<!--[^\n]*-->/m','',$markdown);
    $markdown = preg_replace('/^(#+)([^\n# ][^\n]*[^\n# ])(#+)$/m','$1 $2 $3',$markdown);
    $markdown = preg_replace('/^(#+)([^\n# ])/m','$1 $2',$markdown);
    $markdown = preg_replace('/http:\/\/i.stack.imgur.com\//','https://i.stack.imgur.com/',$markdown);
   //error_log('length: '.strlen($markdown));
    // add the question
    if($qanon){
      $id=ccdb("select new_sequestionanon((select community_id from get.community where community_name=$1),$2,$3,$4,$5)",$_POST['community'],$title,$markdown,$tags,$_POST['seqid']);
    }else{
      $id=ccdb("select new_sequestion((select community_id from get.community where community_name=$1),$2,$3,$4,$5,$6,$7)",$_POST['community'],$title,$markdown,$tags,$_POST['seqid'],$seuid,$seuname);
    }
    // generate an array of answers to import
    $aids = [];
    if($_POST['seaids']==='*'){
      $doc = new DOMDocument();
      $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents($sesite_url.'/questions/'.$_POST['seqid']));
      $xpath = new DOMXpath($doc);
      $elements = $xpath->query("//div[contains(concat(' ', @class, ' '), ' answer ')]");
      foreach($elements as $element) array_push($aids,explode('-',$element->getAttribute('id'))[1]);
    }else{
      if($_POST['seaids']) $aids = explode(' ',$_POST['seaids']);
      if($my_community_se_user_id){
        $doc = new DOMDocument();
        $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents($sesite_url.'/questions/'.$_POST['seqid']));
        $xpath = new DOMXpath($doc);
        $elements = $xpath->query("//div[contains(concat(' ', @class, ' '), ' answer ') and "
                                 ."boolean(.//div[contains(concat(' ', @class, ' '), ' post-signature ') and not(following-sibling::div[contains(concat(' ', @class, ' '), ' post-signature ')])]"
                                 ."//div[contains(concat(' ', @class, ' '), ' user-details ')]/a[contains(@href,'/".$my_community_se_user_id."/')])]");
        foreach($elements as $element){
          $aid = explode('-',$element->getAttribute('id'))[1];
          if(!in_array($aid,$aids,true)) array_push($aids,$aid);
        }
      }
    }
   //error_log('aids: '.print_r($aids,true));
    // import each selected answer
    foreach($aids as $aid){
      $doc = new DOMDocument();
      $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents($sesite_url.'/posts/'.$aid.'/edit'));
      $xpath = new DOMXpath($doc);
      $elements = $xpath->query("//textarea[@id='wmd-input-".$aid."']");
      $markdown = $elements[0]->textContent;
      $markdown = preg_replace('/<!--[^\n]*-->/m','',$markdown);
      $markdown = preg_replace('/^(#+)([^\n# ][^\n]*[^\n# ])(#+)$/m','$1 $2 $3',$markdown);
      $markdown = preg_replace('/^(#+)([^\n# ])/m','$1 $2',$markdown);
      $markdown = preg_replace('/http:\/\/i.stack.imgur.com\//','https://i.stack.imgur.com/',$markdown);
      if($answers[$aid]['anon']){
        db("select new_seansweranon($1,$2,$3)",$id,$markdown,$aid);
      }else{
        db("select new_seanswer($1,$2,$3,$4,$5)",$id,$markdown,$aid,$answers[$aid]['uid'],$answers[$aid]['uname']);
      }
    }
    header('Location: //topanswers.xyz/'.$_POST['community'].'?q='.$id);
    exit;
  case 'change':
    db("select change_question($1,$2,$3)",$_POST['id'],$_POST['title'],$_POST['markdown']);
    header('Location: //topanswers.xyz/'.ccdb("select community_name from get.question natural join get.community where question_id=$1",$_POST['id']).'?q='.$_POST['id']);
    exit;
  case 'vote': exit(ccdb("select vote_question($1,$2)",$_POST['id'],$_POST['votes']));
  case 'dismiss': exit(ccdb("select dismiss_question_notification($1)",$_POST['id']));
  case 'subscribe': exit(ccdb("select subscribe_question($1)",$_POST['id']));
  case 'unsubscribe': exit(ccdb("select unsubscribe_question($1)",$_POST['id']));
  default: fail(400,'unrecognized action');
}
