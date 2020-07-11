<?php
exec("find -L get/lib -maxdepth 19 -type f \( -name '*.js' -o -name '*.css' \) | sort -fdt '\n'",$files);
exec("find -L get/fonts -maxdepth 19 -type f \( -name '*.js' -o -name '*.css' \) | sort -fdt '\n'",$files);
exec("find -L get -type d \( -path get/lib -o -path get/fonts \) -prune -o \( -name '*.js' -o -name '*.css' \) -type f -print | sort -fdt '\n'",$files);
$files = array_map(function($f){ return '/'.substr($f,4); },$files);

foreach($files as $file) {
  $hashes[$file] = exec('xxhsum get'.$file.' | cut -f 1 -d " " | tr -d "\n"');
}

$hashes['/markdown.js'] = exec('echo '.$hashes['/markdown.js'].implode(array_filter($hashes,function($f){ return preg_match('/^\/lib\/.*\.js$/',$f); },ARRAY_FILTER_USE_KEY),'').' | xxhsum | cut -f 1 -d " " | tr -d "\n"');
$hashes['/md-oneline.js'] = exec('echo '.$hashes['/markdown.js'].implode(array_filter($hashes,function($f){ return preg_match('/^\/lib\/.*\.js$/',$f); },ARRAY_FILTER_USE_KEY),'').' | xxhsum | cut -f 1 -d " " | tr -d "\n"');
array_walk($hashes,function(&$h,$f){ global $hashes; if(preg_match('/^\/page\/.*\.js$/',$f)) $h = exec('echo '.$hashes[$f].$hashes['/markdown.js'].' | xxhsum | cut -f 1 -d " " | tr -d "\n"'); });
$filtered = array_filter($hashes,function($f){ return preg_match('/^\/lib\/(codemirror|katex|qp)\/.*\.js$/',$f)
                                                 || ( preg_match('/^\/lib\/[^\/]*\.js$/',$f) && $f!=='/lib/jquery.js' ); },ARRAY_FILTER_USE_KEY);
array_walk($filtered,function($h,$f){ global $modules,$hashes; $modules .= "    '".substr($f,5,-3)."':'".substr($f,5,-3).'.'.$hashes['/lib/'.substr($f,5,-3).'.js']."',".PHP_EOL; });
$filtered = array_filter($hashes,function($f){ return preg_match('/^\/[^\/]*\.js$/',$f); },ARRAY_FILTER_USE_KEY);
array_walk($filtered,function($h,$f){ global $omodules,$hashes; $omodules .= "    '".substr($f,1,-3)."':'../".substr($f,1,-3).'.'.$hashes['/'.substr($f,1,-3).'.js']."',".PHP_EOL; });

$j = <<<EOT
var require = {
  baseUrl: '/lib',
  enforceDefine: true,
  paths: { 'jquery': 'jquery.{$hashes['/lib/jquery.js']}' },
  shim: {
    'markdown-it-katex.{$hashes['/lib/markdown-it-katex.js']}': ['katex.{$hashes['/lib/katex.js']}'],
    'starrr.{$hashes['/lib/starrr.js']}': { deps: ['jquery'], exports: 'jQuery.fn.starrr' },
    'jquery.simplePagination.{$hashes['/lib/jquery.simplePagination.js']}': { deps: ['jquery'], exports: 'jQuery.fn.pagination' },
    'paste.{$hashes['/lib/paste.js']}': { deps: ['jquery'], exports: 'jQuery.fn.pastableTextarea' },
    'resizer.{$hashes['/lib/resizer.js']}': { exports: 'Resizer' },
    'promise-all-settled.{$hashes['/lib/promise-all-settled.js']}': { exports: 'allSettled' },
    'diff_match_patch.{$hashes['/lib/diff_match_patch.js']}': { exports: 'diff_match_patch' },
    'jquery.{$hashes['/lib/jquery.js']}': { exports: 'jQuery' },
  },
  map: { '*': {
{$modules}{$omodules} } },
};
EOT;

$hashes['/require.config.js'] = exec('xxhsum get/require.config.js | cut -f 1 -d " " | tr -d "\n"');

$json = json_encode($hashes,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
$p = <<<EOT
<?php
function h(\$f){
  return preg_replace('/(.*).(js|css)\$/','\$1.'.(json_decode('$json',true)[\$f]).'.\$2',\$f);
}
EOT;
file_put_contents('hash.php',$p);
file_put_contents('get/require.config.js',$j);
