<? 
include '../config.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
function transpose($arr) {
  $out = array();
  foreach ($arr as $key => $subarr) {
    foreach ($subarr as $subkey => $subvalue) {
      $out[$subkey][$key] = $subvalue;
    }
  }
  return $out;
}
$json = json_decode(file_get_contents('https://dbfiddle.uk/json?rdbms='.$_GET['rdbms'].'&fiddle='.$_GET['fiddle']),true);
$hide = isset($_GET['hide'])?array_reverse(array_map(function($c){ return $c==='1'; },str_split(decbin($_GET['hide'])))):[];
header('X-Powered-By: ');
header('Cache-Control: max-age=60');
?>
<div class="dbfiddle" data-rdbms="<?=$_GET['rdbms']?>">
  <?foreach($json as $i=>$batchplusoutput){?>
    <?if(!array_key_exists($i,$hide)) $hide[$i] = false;?>
    <div class="batch<?=$hide[$i]?' hidden':''?>">
      <textarea rows="1"><?=$batchplusoutput['batch']?></textarea>
      <?if($batchplusoutput['output']['error']){?>
        <pre class="error"><?=$batchplusoutput['output']['error']?></pre>
      <?}else{?>
        <?foreach($batchplusoutput['output']['result'] as $result){?>
          <?if($result['head']){?>
            <div class="tablewrapper">
              <table>
                <thead>
                  <tr>
                    <?foreach($result['head'] as $head){?>
                      <th><?=$head?></th>
                    <?}?>
                  </tr>
                </thead>
                <tbody>
                  <?$rows = transpose($result['data']);?>
                  <?$initial = 0;?>
                  <?$more = false;?>
                  <?foreach($rows as $rownum=>$row){?>
                    <?if( in_array($rownum,[10,100,1000,10000,100000]) && (count($rows)>=$rownum*1.3) ){ $more = true;?></tbody><tbody class="hide" data-showing="<?=$rownum?>"><?}?>
                    <?if(!$more) $initial += 1;?>
                    <tr>
                      <?foreach($row as $k=>$data){?>
                        <td class="<?=trim((($data===null)?'null ':'').(($result['align'][$k]===0)?'right ':''))?>"><?=htmlspecialchars($data)?></td>
                      <?}?>
                    </tr>
                  <?}?>
                </tbody>
                <?$count = $rownum+1;?>
                <?if($count>5){?>
                  <tfoot data-showing="<?=$rownum+1?>">
                    <tr><td colspan="<?=count($result['head'])?>"><span><?=$initial?></span> rows<?if($count>$initial){?><span> of <?=$count?><br><a href='.'>show more</a></span><?}?></td></tr>
                  </tfoot>
                <?}?>
              </table>
            </div>
          <?}?>
          <?if(isset($head)) if($head==='Microsoft SQL Server 2005 XML Showplan'){?><div class="qp" data-xml="<?=htmlspecialchars($data)?>"></div><?}?>
        <?}?>
      <?}?>
    </div>
  <?}?>
  <div>
    <input type="button" value="run">
    <a href="https://dbfiddle.uk?rdbms=<?=$_GET['rdbms']?>&fiddle=<?=$_GET['fiddle']?><?=isset($_GET['hide'])?'&hide='.$_GET['hide']:''?>" target="_blank">fiddle</a>
  </div>
</div>
