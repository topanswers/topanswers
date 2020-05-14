<?php
function h($f){
  return preg_replace('/(.*).(js|css)$/','$1.'.(json_decode('{"\/markdown.js":"cafc7706cee4572b","\/require.config.js":""}',true)[$f]).'.$2',$f);
}