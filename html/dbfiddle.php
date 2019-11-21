<? 
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
header('X-Powered-By: ');
header('Cache-Control: max-age=60');
?>
<div class="dbfiddle" data-rdbms="<?=$_GET['rdbms']?>">
  <?foreach($json as $i=>$batchplusoutput){?>
    <fieldset>
      <textarea rows="1"><?=$batchplusoutput['batch']?></textarea>
      <?foreach($batchplusoutput['output']['result'] as $result){?>
        <div class="tablewrapper">
          <table>
            <tbody>
              <tr>
                <?foreach($result['head'] as $head){?>
                  <th><?=$head?></th>
                <?}?>
              </tr>
              <?foreach(transpose($result['data']) as $row){?>
                <tr>
                  <?foreach($row as $data){?>
                    <td><?=$data?></td>
                  <?}?>
                <tr>
              <?}?>
            </tbody>
          </table>
        </div>
      <?}?>
    </fieldset>
  <?}?>
  <div>
    <input type="button" value="run">
    <span>or</span>
    <a href="https://dbfiddle.uk?rdbms=<?=$_GET['rdbms']?>&fiddle=<?=$_GET['fiddle']?>" target="_blank">fiddle</a>
  </div>
</div>
