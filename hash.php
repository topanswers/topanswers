<?php
function h($f){
  return preg_replace('/(.*).(js|css)$/','$1.'.(json_decode('{
    "/lib/clipboard.js": "fa1eb95558c72c3f",
    "/lib/clipboard.min.js": "fa1eb95558c72c3f",
    "/lib/codemirror-5.49.2/addon/display/placeholder.js": "c2412da7adc816c2",
    "/lib/codemirror-5.49.2/addon/mode/overlay.js": "78008086f33a69da",
    "/lib/codemirror-5.49.2/addon/runmode/colorize.js": "e507e083560a8285",
    "/lib/codemirror-5.49.2/addon/runmode/runmode.js": "5017e8810200ecd7",
    "/lib/codemirror-5.49.2/codemirror.css": "2c9baef3de12ae02",
    "/lib/codemirror-5.49.2/codemirror.js": "c55cac653c01cf72",
    "/lib/codemirror-5.49.2/colorize.js": "e507e083560a8285",
    "/lib/codemirror-5.49.2/lib/codemirror.css": "2c9baef3de12ae02",
    "/lib/codemirror-5.49.2/lib/codemirror.js": "c55cac653c01cf72",
    "/lib/codemirror-5.49.2/mode/apl/apl.js": "71cadc768dcd17aa",
    "/lib/codemirror-5.49.2/mode/apl.js": "71cadc768dcd17aa",
    "/lib/codemirror-5.49.2/mode/clike/clike.js": "215e8d258f779286",
    "/lib/codemirror-5.49.2/mode/clike.js": "215e8d258f779286",
    "/lib/codemirror-5.49.2/mode/clojure/clojure.js": "a6c82719a8589569",
    "/lib/codemirror-5.49.2/mode/clojure.js": "a6c82719a8589569",
    "/lib/codemirror-5.49.2/mode/css/css.js": "4bc27114a047458b",
    "/lib/codemirror-5.49.2/mode/css.js": "4bc27114a047458b",
    "/lib/codemirror-5.49.2/mode/diff/diff.js": "5cf2c64b70d63c41",
    "/lib/codemirror-5.49.2/mode/diff.js": "5cf2c64b70d63c41",
    "/lib/codemirror-5.49.2/mode/erlang/erlang.js": "6c840b08e44b804c",
    "/lib/codemirror-5.49.2/mode/erlang.js": "6c840b08e44b804c",
    "/lib/codemirror-5.49.2/mode/gfm/gfm.js": "f4ad694c46ec6bf6",
    "/lib/codemirror-5.49.2/mode/gfm.js": "f4ad694c46ec6bf6",
    "/lib/codemirror-5.49.2/mode/go/go.js": "2284d5e1ed99bb70",
    "/lib/codemirror-5.49.2/mode/go.js": "2284d5e1ed99bb70",
    "/lib/codemirror-5.49.2/mode/haskell/haskell.js": "89003d648807d629",
    "/lib/codemirror-5.49.2/mode/haskell.js": "89003d648807d629",
    "/lib/codemirror-5.49.2/mode/htmlmixed/htmlmixed.js": "e437c7b25be942af",
    "/lib/codemirror-5.49.2/mode/htmlmixed.js": "e437c7b25be942af",
    "/lib/codemirror-5.49.2/mode/javascript/javascript.js": "dba573ca58fe9f89",
    "/lib/codemirror-5.49.2/mode/javascript.js": "dba573ca58fe9f89",
    "/lib/codemirror-5.49.2/mode/julia.js": "9737225b3d556bc5",
    "/lib/codemirror-5.49.2/mode/julia/julia.js": "9737225b3d556bc5",
    "/lib/codemirror-5.49.2/mode/lua.js": "46bed41a163ab8e9",
    "/lib/codemirror-5.49.2/mode/lua/lua.js": "46bed41a163ab8e9",
    "/lib/codemirror-5.49.2/mode/markdown.js": "f3d5d20a74781395",
    "/lib/codemirror-5.49.2/mode/markdown/markdown.js": "f3d5d20a74781395",
    "/lib/codemirror-5.49.2/mode/markdown/markdown.orig.js": "1549a6e90442648b",
    "/lib/codemirror-5.49.2/mode/meta.js": "b3427edc59ba76e6",
    "/lib/codemirror-5.49.2/mode/mllike.js": "3db74901aefde9b7",
    "/lib/codemirror-5.49.2/mode/mllike/mllike.js": "3db74901aefde9b7",
    "/lib/codemirror-5.49.2/mode/php.js": "8ec12a115955dbe9",
    "/lib/codemirror-5.49.2/mode/php/php.js": "8ec12a115955dbe9",
    "/lib/codemirror-5.49.2/mode/powershell.js": "0ec3665715de1320",
    "/lib/codemirror-5.49.2/mode/powershell/powershell.js": "0ec3665715de1320",
    "/lib/codemirror-5.49.2/mode/python.js": "db2fe31b948160d0",
    "/lib/codemirror-5.49.2/mode/python/python.js": "db2fe31b948160d0",
    "/lib/codemirror-5.49.2/mode/shell.js": "5980b96325aa589a",
    "/lib/codemirror-5.49.2/mode/shell/shell.js": "5980b96325aa589a",
    "/lib/codemirror-5.49.2/mode/sql.js": "c24d05289711c73a",
    "/lib/codemirror-5.49.2/mode/sql/sql.js": "c24d05289711c73a",
    "/lib/codemirror-5.49.2/mode/stex.js": "bb1677e80c8f487f",
    "/lib/codemirror-5.49.2/mode/stex/stex.js": "bb1677e80c8f487f",
    "/lib/codemirror-5.49.2/mode/vb.js": "6a7b7d044ba746e1",
    "/lib/codemirror-5.49.2/mode/vb/vb.js": "6a7b7d044ba746e1",
    "/lib/codemirror-5.49.2/mode/xml.js": "1d35c14cae46a93b",
    "/lib/codemirror-5.49.2/mode/xml/xml.js": "1d35c14cae46a93b",
    "/lib/codemirror-5.49.2/placeholder.js": "c2412da7adc816c2",
    "/lib/codemirror-5.49.2/runmode.js": "5017e8810200ecd7",
    "/lib/codemirror/addon/display/placeholder.js": "c2412da7adc816c2",
    "/lib/codemirror/addon/mode/overlay.js": "78008086f33a69da",
    "/lib/codemirror/addon/runmode/colorize.js": "e507e083560a8285",
    "/lib/codemirror/addon/runmode/runmode.js": "5017e8810200ecd7",
    "/lib/codemirror/codemirror.css": "2c9baef3de12ae02",
    "/lib/codemirror/codemirror.js": "c55cac653c01cf72",
    "/lib/codemirror/colorize.js": "e507e083560a8285",
    "/lib/codemirror/lib/codemirror.css": "2c9baef3de12ae02",
    "/lib/codemirror/lib/codemirror.js": "c55cac653c01cf72",
    "/lib/codemirror/mode/apl/apl.js": "71cadc768dcd17aa",
    "/lib/codemirror/mode/apl.js": "71cadc768dcd17aa",
    "/lib/codemirror/mode/clike/clike.js": "215e8d258f779286",
    "/lib/codemirror/mode/clike.js": "215e8d258f779286",
    "/lib/codemirror/mode/clojure/clojure.js": "a6c82719a8589569",
    "/lib/codemirror/mode/clojure.js": "a6c82719a8589569",
    "/lib/codemirror/mode/css/css.js": "4bc27114a047458b",
    "/lib/codemirror/mode/css.js": "4bc27114a047458b",
    "/lib/codemirror/mode/diff/diff.js": "5cf2c64b70d63c41",
    "/lib/codemirror/mode/diff.js": "5cf2c64b70d63c41",
    "/lib/codemirror/mode/erlang/erlang.js": "6c840b08e44b804c",
    "/lib/codemirror/mode/erlang.js": "6c840b08e44b804c",
    "/lib/codemirror/mode/gfm/gfm.js": "f4ad694c46ec6bf6",
    "/lib/codemirror/mode/gfm.js": "f4ad694c46ec6bf6",
    "/lib/codemirror/mode/go/go.js": "2284d5e1ed99bb70",
    "/lib/codemirror/mode/go.js": "2284d5e1ed99bb70",
    "/lib/codemirror/mode/haskell/haskell.js": "89003d648807d629",
    "/lib/codemirror/mode/haskell.js": "89003d648807d629",
    "/lib/codemirror/mode/htmlmixed/htmlmixed.js": "e437c7b25be942af",
    "/lib/codemirror/mode/htmlmixed.js": "e437c7b25be942af",
    "/lib/codemirror/mode/javascript/javascript.js": "dba573ca58fe9f89",
    "/lib/codemirror/mode/javascript.js": "dba573ca58fe9f89",
    "/lib/codemirror/mode/julia.js": "9737225b3d556bc5",
    "/lib/codemirror/mode/julia/julia.js": "9737225b3d556bc5",
    "/lib/codemirror/mode/lua.js": "46bed41a163ab8e9",
    "/lib/codemirror/mode/lua/lua.js": "46bed41a163ab8e9",
    "/lib/codemirror/mode/markdown.js": "f3d5d20a74781395",
    "/lib/codemirror/mode/markdown/markdown.js": "f3d5d20a74781395",
    "/lib/codemirror/mode/markdown/markdown.orig.js": "1549a6e90442648b",
    "/lib/codemirror/mode/meta.js": "b3427edc59ba76e6",
    "/lib/codemirror/mode/mllike.js": "3db74901aefde9b7",
    "/lib/codemirror/mode/mllike/mllike.js": "3db74901aefde9b7",
    "/lib/codemirror/mode/php.js": "8ec12a115955dbe9",
    "/lib/codemirror/mode/php/php.js": "8ec12a115955dbe9",
    "/lib/codemirror/mode/powershell.js": "0ec3665715de1320",
    "/lib/codemirror/mode/powershell/powershell.js": "0ec3665715de1320",
    "/lib/codemirror/mode/python.js": "db2fe31b948160d0",
    "/lib/codemirror/mode/python/python.js": "db2fe31b948160d0",
    "/lib/codemirror/mode/shell.js": "5980b96325aa589a",
    "/lib/codemirror/mode/shell/shell.js": "5980b96325aa589a",
    "/lib/codemirror/mode/sql.js": "c24d05289711c73a",
    "/lib/codemirror/mode/sql/sql.js": "c24d05289711c73a",
    "/lib/codemirror/mode/stex.js": "bb1677e80c8f487f",
    "/lib/codemirror/mode/stex/stex.js": "bb1677e80c8f487f",
    "/lib/codemirror/mode/vb.js": "6a7b7d044ba746e1",
    "/lib/codemirror/mode/vb/vb.js": "6a7b7d044ba746e1",
    "/lib/codemirror/mode/xml.js": "1d35c14cae46a93b",
    "/lib/codemirror/mode/xml/xml.js": "1d35c14cae46a93b",
    "/lib/codemirror/placeholder.js": "c2412da7adc816c2",
    "/lib/codemirror/runmode.js": "5017e8810200ecd7",
    "/lib/datatables/DataTables-1.10.20/css/dataTables.bootstrap4.css": "10d57f8ee8040b4f",
    "/lib/datatables/DataTables-1.10.20/css/dataTables.bootstrap4.min.css": "2250829c2c1c7010",
    "/lib/datatables/DataTables-1.10.20/css/dataTables.bootstrap.css": "29c67f425a9ca89c",
    "/lib/datatables/DataTables-1.10.20/css/dataTables.bootstrap.min.css": "65da80c6c73cc597",
    "/lib/datatables/DataTables-1.10.20/css/dataTables.foundation.css": "2fe011f4aa030080",
    "/lib/datatables/DataTables-1.10.20/css/dataTables.foundation.min.css": "48765b67790e2813",
    "/lib/datatables/DataTables-1.10.20/css/dataTables.jqueryui.css": "99b6ac75bce81f98",
    "/lib/datatables/DataTables-1.10.20/css/dataTables.jqueryui.min.css": "48f2b20c52559ac9",
    "/lib/datatables/DataTables-1.10.20/css/dataTables.semanticui.css": "8c50864ef1ef3629",
    "/lib/datatables/DataTables-1.10.20/css/dataTables.semanticui.min.css": "8d706df6a8155c90",
    "/lib/datatables/DataTables-1.10.20/css/jquery.dataTables.css": "8c27edd2c5ee9445",
    "/lib/datatables/DataTables-1.10.20/css/jquery.dataTables.min.css": "46446404eb42cd6e",
    "/lib/datatables/DataTables-1.10.20/js/dataTables.bootstrap4.js": "31078cafbe857d33",
    "/lib/datatables/DataTables-1.10.20/js/dataTables.bootstrap4.min.js": "f24d9824cf945961",
    "/lib/datatables/DataTables-1.10.20/js/dataTables.bootstrap.js": "f2d1ac177b48c536",
    "/lib/datatables/DataTables-1.10.20/js/dataTables.bootstrap.min.js": "7817c9e1a39c443b",
    "/lib/datatables/DataTables-1.10.20/js/dataTables.foundation.js": "62d5a6fff37a8c4e",
    "/lib/datatables/DataTables-1.10.20/js/dataTables.foundation.min.js": "fad086a201e11c76",
    "/lib/datatables/DataTables-1.10.20/js/dataTables.jqueryui.js": "a25a8a10c8467663",
    "/lib/datatables/DataTables-1.10.20/js/dataTables.jqueryui.min.js": "b822a69180b26b19",
    "/lib/datatables/DataTables-1.10.20/js/dataTables.semanticui.js": "18d3c2f0281a6db4",
    "/lib/datatables/DataTables-1.10.20/js/dataTables.semanticui.min.js": "710418f23ba588b6",
    "/lib/datatables/DataTables-1.10.20/js/jquery.dataTables.js": "f7fc1f0d440caf02",
    "/lib/datatables/DataTables-1.10.20/js/jquery.dataTables.min.js": "017fe1540aa56fa5",
    "/lib/datatables/datatables.css": "c34d5b274b3d96ec",
    "/lib/datatables/datatables.js": "4958a5a90cc1a1dd",
    "/lib/datatables/datatables.min.css": "37176c0252371642",
    "/lib/datatables/datatables.min.js": "c85b4a1bac2aec5b",
    "/lib/datatables/SearchPanes-1.0.1/css/searchPanes.bootstrap4.css": "909e831b5b800591",
    "/lib/datatables/SearchPanes-1.0.1/css/searchPanes.bootstrap4.min.css": "543df1dd177717bd",
    "/lib/datatables/SearchPanes-1.0.1/css/searchPanes.bootstrap.css": "d427da6058c43e0b",
    "/lib/datatables/SearchPanes-1.0.1/css/searchPanes.bootstrap.min.css": "879f51ae632399d2",
    "/lib/datatables/SearchPanes-1.0.1/css/searchPanes.dataTables.css": "05db21baef337f5a",
    "/lib/datatables/SearchPanes-1.0.1/css/searchPanes.dataTables.min.css": "6070609309220a18",
    "/lib/datatables/SearchPanes-1.0.1/css/searchPanes.foundation.css": "aee89550b028b301",
    "/lib/datatables/SearchPanes-1.0.1/css/searchPanes.foundation.min.css": "4e261f6c23ce8b33",
    "/lib/datatables/SearchPanes-1.0.1/css/searchPanes.jqueryui.css": "82ed1dc053a02f25",
    "/lib/datatables/SearchPanes-1.0.1/css/searchPanes.jqueryui.min.css": "dbca23f96c5874ad",
    "/lib/datatables/SearchPanes-1.0.1/css/searchPanes.semanticui.css": "aff6785a54e34649",
    "/lib/datatables/SearchPanes-1.0.1/css/searchPanes.semanticui.min.css": "11792ccffa7bebaa",
    "/lib/datatables/SearchPanes-1.0.1/js/dataTables.searchPanes.js": "b52e118f7b080e12",
    "/lib/datatables/SearchPanes-1.0.1/js/dataTables.searchPanes.min.js": "d05ae23d2aa1b449",
    "/lib/datatables/SearchPanes-1.0.1/js/searchPanes.bootstrap4.js": "988a8141de5f6ed0",
    "/lib/datatables/SearchPanes-1.0.1/js/searchPanes.bootstrap4.min.js": "9a049a311ad48ddb",
    "/lib/datatables/SearchPanes-1.0.1/js/searchPanes.bootstrap.js": "0f2b2c6fdd244abe",
    "/lib/datatables/SearchPanes-1.0.1/js/searchPanes.bootstrap.min.js": "d49d66ee73aa01c1",
    "/lib/datatables/SearchPanes-1.0.1/js/searchPanes.dataTables.js": "78e63ffaa83c07ee",
    "/lib/datatables/SearchPanes-1.0.1/js/searchPanes.dataTables.min.js": "030be0008cee44f1",
    "/lib/datatables/SearchPanes-1.0.1/js/searchPanes.foundation.js": "b823751595e93151",
    "/lib/datatables/SearchPanes-1.0.1/js/searchPanes.foundation.min.js": "09095d2a1f39d708",
    "/lib/datatables/SearchPanes-1.0.1/js/searchPanes.jqueryui.js": "f9a45092fa048c63",
    "/lib/datatables/SearchPanes-1.0.1/js/searchPanes.jqueryui.min.js": "c9b7b4f0ed5b8373",
    "/lib/datatables/SearchPanes-1.0.1/js/searchPanes.semanicui.js": "ef46db3751d8e999",
    "/lib/datatables/SearchPanes-1.0.1/js/searchPanes.semanticui.js": "dd7629030e5684db",
    "/lib/datatables/SearchPanes-1.0.1/js/searchPanes.semanticui.min.js": "2990e8c907156f63",
    "/lib/datatables/Select-1.3.1/css/select.bootstrap4.css": "1c46f53588c32f02",
    "/lib/datatables/Select-1.3.1/css/select.bootstrap4.min.css": "6427261a8b4cb809",
    "/lib/datatables/Select-1.3.1/css/select.bootstrap.css": "34e7d310d3cb68fb",
    "/lib/datatables/Select-1.3.1/css/select.bootstrap.min.css": "da4844e893294bbc",
    "/lib/datatables/Select-1.3.1/css/select.dataTables.css": "988d3eeecc03e25f",
    "/lib/datatables/Select-1.3.1/css/select.dataTables.min.css": "4bb7eb851f8ec66a",
    "/lib/datatables/Select-1.3.1/css/select.foundation.css": "4a6c10cdb4ce0fc1",
    "/lib/datatables/Select-1.3.1/css/select.foundation.min.css": "50e0675d85aed3c6",
    "/lib/datatables/Select-1.3.1/css/select.jqueryui.css": "988d3eeecc03e25f",
    "/lib/datatables/Select-1.3.1/css/select.jqueryui.min.css": "4bb7eb851f8ec66a",
    "/lib/datatables/Select-1.3.1/css/select.semanticui.css": "4979b3c8fd910261",
    "/lib/datatables/Select-1.3.1/css/select.semanticui.min.css": "0ea1c43c3c72b372",
    "/lib/datatables/Select-1.3.1/js/dataTables.select.js": "f1681c4aaf2400f9",
    "/lib/datatables/Select-1.3.1/js/dataTables.select.min.js": "4205baedaac1823c",
    "/lib/datatables/Select-1.3.1/js/select.bootstrap4.js": "b9d6b6ae7e86604d",
    "/lib/datatables/Select-1.3.1/js/select.bootstrap4.min.js": "ef14a33d6056fa9c",
    "/lib/datatables/Select-1.3.1/js/select.bootstrap.js": "4def8d28d457b7a1",
    "/lib/datatables/Select-1.3.1/js/select.bootstrap.min.js": "67928430c82923db",
    "/lib/datatables/Select-1.3.1/js/select.dataTables.js": "54690b4383ab7f6d",
    "/lib/datatables/Select-1.3.1/js/select.foundation.js": "1698f22b1a42ae01",
    "/lib/datatables/Select-1.3.1/js/select.foundation.min.js": "f319cd09629ffc30",
    "/lib/datatables/Select-1.3.1/js/select.jqueryui.js": "02e36353575f409b",
    "/lib/datatables/Select-1.3.1/js/select.jqueryui.min.js": "d9f4e9d3a181d508",
    "/lib/datatables/Select-1.3.1/js/select.semanticui.js": "2f538c3b02a4fd95",
    "/lib/datatables/Select-1.3.1/js/select.semanticui.min.js": "a21d25ad799e08af",
    "/lib/diff_match_patch.js": "6e3838c0996f1453",
    "/lib/domReady.js": "e2a6afe670185cf8",
    "/lib/Fork-Awesome-1.1.7/css/fork-awesome.css": "bffececf94c71afd",
    "/lib/Fork-Awesome-1.1.7/css/fork-awesome.min.css": "2538819e70d72069",
    "/lib/Fork-Awesome-1.1.7/css/v5-compat.css": "ab3e643faa4974ad",
    "/lib/Fork-Awesome-1.1.7/css/v5-compat.min.css": "29c7cdb2e766546d",
    "/lib/Fork-Awesome-1.1.7/src/doc/assets/css/prettify.css": "6bfca2dbc53ad035",
    "/lib/Fork-Awesome-1.1.7/src/doc/assets/css/pygments.css": "4d123a7524ca6558",
    "/lib/Fork-Awesome-1.1.7/src/doc/assets/css/share.min.css": "70a510fee2810710",
    "/lib/Fork-Awesome-1.1.7/src/doc/assets/js/html5shiv.js": "eec1c277757cc76a",
    "/lib/Fork-Awesome-1.1.7/src/doc/assets/js/monetization.js": "e6d48eb0c8daa557",
    "/lib/Fork-Awesome-1.1.7/src/doc/assets/js/prettify.min.js": "851a5bb489ede731",
    "/lib/Fork-Awesome-1.1.7/src/doc/assets/js/respond.min.js": "38c963d20280d731",
    "/lib/Fork-Awesome-1.1.7/src/doc/assets/js/search.js": "6df077bdc539db68",
    "/lib/Fork-Awesome-1.1.7/src/doc/assets/js/share.min.js": "5772093bd8072e4d",
    "/lib/Fork-Awesome-1.1.7/src/doc/assets/js/site.js": "40875edfad44d67d",
    "/lib/Fork-Awesome-1.1.7/src/doc/assets/js/ZeroClipboard-1.1.7.min.js": "c5a11e4b3a605581",
    "/lib/Fork-Awesome-1.1.7/src/doc/_includes/code/license.css": "96663ab7cb64b66a",
    "/lib/fork-awesome/css/fork-awesome.css": "bffececf94c71afd",
    "/lib/fork-awesome/css/fork-awesome.min.css": "2538819e70d72069",
    "/lib/fork-awesome/css/v5-compat.css": "ab3e643faa4974ad",
    "/lib/fork-awesome/css/v5-compat.min.css": "29c7cdb2e766546d",
    "/lib/fork-awesome/src/doc/assets/css/prettify.css": "6bfca2dbc53ad035",
    "/lib/fork-awesome/src/doc/assets/css/pygments.css": "4d123a7524ca6558",
    "/lib/fork-awesome/src/doc/assets/css/share.min.css": "70a510fee2810710",
    "/lib/fork-awesome/src/doc/assets/js/html5shiv.js": "eec1c277757cc76a",
    "/lib/fork-awesome/src/doc/assets/js/monetization.js": "e6d48eb0c8daa557",
    "/lib/fork-awesome/src/doc/assets/js/prettify.min.js": "851a5bb489ede731",
    "/lib/fork-awesome/src/doc/assets/js/respond.min.js": "38c963d20280d731",
    "/lib/fork-awesome/src/doc/assets/js/search.js": "6df077bdc539db68",
    "/lib/fork-awesome/src/doc/assets/js/share.min.js": "5772093bd8072e4d",
    "/lib/fork-awesome/src/doc/assets/js/site.js": "40875edfad44d67d",
    "/lib/fork-awesome/src/doc/assets/js/ZeroClipboard-1.1.7.min.js": "c5a11e4b3a605581",
    "/lib/fork-awesome/src/doc/_includes/code/license.css": "96663ab7cb64b66a",
    "/lib/jquery-3.4.1.min.js": "6ad657dafdfaa495",
    "/lib/jquery.js": "6ad657dafdfaa495",
    "/lib/jquery.mark.min.js": "9c62caf8b13d1f17",
    "/lib/jquery.simplePagination.js": "b892c57d7f01a4e5",
    "/lib/js.cookie-2.2.1.min.js": "76935a70978b8098",
    "/lib/js.cookie.js": "76935a70978b8098",
    "/lib/katex/contrib/auto-render.js": "1ce34da626dec02d",
    "/lib/katex/contrib/auto-render.min.js": "fd15df9601dcbc09",
    "/lib/katex/contrib/copy-tex.css": "13730bd1876f6160",
    "/lib/katex/contrib/copy-tex.js": "ca16c1a43dc149ee",
    "/lib/katex/contrib/copy-tex.min.css": "e21f0e111148c5eb",
    "/lib/katex/contrib/copy-tex.min.js": "84778a57ce5caa43",
    "/lib/katex/contrib/mathtex-script-type.js": "a8cd01e76bd7e7c5",
    "/lib/katex/contrib/mathtex-script-type.min.js": "621dd38f63242304",
    "/lib/katex/contrib/mhchem.js": "3707af344f159c31",
    "/lib/katex/contrib/mhchem.min.js": "f4b31a8f40f5f252",
    "/lib/katex/contrib/render-a11y-string.js": "dc25a24aba8785d9",
    "/lib/katex/contrib/render-a11y-string.min.js": "069ba3c7e1b26dc3",
    "/lib/katex.js": "9460c91fb69cca2c",
    "/lib/katex/katex.css": "c9fae360b1c0fd1c",
    "/lib/katex/katex.js": "9460c91fb69cca2c",
    "/lib/katex/katex.min.css": "b83dda1be4141b3e",
    "/lib/katex/katex.min.js": "9460c91fb69cca2c",
    "/lib/lightbox2-2.11.1/css/lightbox.css": "9e313f797f5ef92b",
    "/lib/lightbox2-2.11.1/css/lightbox.min.css": "c751699f512b3838",
    "/lib/lightbox2-2.11.1/js/lightbox.js": "9bbfbed2435b9355",
    "/lib/lightbox2-2.11.1/js/lightbox.min.js": "9bbfbed2435b9355",
    "/lib/lightbox2/css/lightbox.css": "9e313f797f5ef92b",
    "/lib/lightbox2/css/lightbox.min.css": "c751699f512b3838",
    "/lib/lightbox2/js/lightbox.js": "9bbfbed2435b9355",
    "/lib/lightbox2/js/lightbox.min.js": "9bbfbed2435b9355",
    "/lib/lodash.js": "0205cae71b99e29f",
    "/lib/lodash.min.js": "0205cae71b99e29f",
    "/lib/markdown-it-abbr.js": "a4c1600fcf93c532",
    "/lib/markdown-it-abbr.min.js": "a4c1600fcf93c532",
    "/lib/markdownItAnchor.js": "ae40bc8d4a7ecab4",
    "/lib/markdown-it-codefence.js": "9f8bfc1e73180d4d",
    "/lib/markdown-it-codeinput.js": "678c3e8af01d3cba",
    "/lib/markdown-it-container.js": "6bfec73d44d83f3e",
    "/lib/markdown-it-container.min.js": "6bfec73d44d83f3e",
    "/lib/markdown-it-deflist.js": "0fe834f8e98e50e6",
    "/lib/markdown-it-deflist.min.js": "0fe834f8e98e50e6",
    "/lib/markdown-it-emoji.js": "2ef729f133487489",
    "/lib/markdown-it-emoji.min.js": "2ef729f133487489",
    "/lib/markdown-it-errorfence.js": "cddf7510c1e5c9c2",
    "/lib/markdown-it-footnote.js": "f603b2eba24d2be8",
    "/lib/markdown-it-footnote.min.js": "f603b2eba24d2be8",
    "/lib/markdown-it-for-inline.js": "616d95e2e481616e",
    "/lib/markdown-it-for-inline.min.js": "616d95e2e481616e",
    "/lib/markdown-it-inject-linenumbers.js": "fe3172e46dc4a28c",
    "/lib/markdown-it-inject-linenumbers.min.js": "fe3172e46dc4a28c",
    "/lib/markdown-it.js": "423da6f97e0b5f53",
    "/lib/markdown-it-katex.js": "689b2993b1958946",
    "/lib/markdown-it.min.js": "423da6f97e0b5f53",
    "/lib/markdown-it-object.js": "91bf7e1de64af714",
    "/lib/markdown-it-onebox.js": "ae97889bc48e1037",
    "/lib/markdown-it-sub.js": "8ed862af30f8d49f",
    "/lib/markdown-it-sub.min.js": "8ed862af30f8d49f",
    "/lib/markdown-it-sup.js": "8954ee74136fd1ee",
    "/lib/markdown-it-sup.min.js": "8954ee74136fd1ee",
    "/lib/markdownItTocDoneRight.js": "022f622633419583",
    "/lib/mark.js": "9c62caf8b13d1f17",
    "/lib/moment.js": "cbe417c063543c63",
    "/lib/moment-with-locales.min.js": "cbe417c063543c63",
    "/lib/pako.js": "3949f219f23162d4",
    "/lib/pako.min.js": "3949f219f23162d4",
    "/lib/paste.js": "6bcdb4817596af13",
    "/lib/promise-all-settled.js": "b9101ad6b2a74f8b",
    "/lib/qp/qp.css": "415d5f5e7613b0d2",
    "/lib/qp/qp.js": "1eebf353313a8d4c",
    "/lib/qp/qp.min.js": "1eebf353313a8d4c",
    "/lib/require.js": "04ef68a65b54ab49",
    "/lib/resizer.js": "acc48d85f03977b4",
    "/lib/sparkmd5.js": "6c489f8ae5ae763c",
    "/lib/starrr.css": "9eab7177811ce175",
    "/lib/starrr.js": "a95baf4870cd4d81",
    "/lib/vex-4.1.0/vex.combined.min.js": "ce8070c89395b6ea",
    "/lib/vex-4.1.0/vex.css": "76d7f028edf114c8",
    "/lib/vex-4.1.0/vex.js": "ce8070c89395b6ea",
    "/lib/vex-4.1.0/vex-theme-topanswers.css": "772496da37c708aa",
    "/lib/vex.js": "ce8070c89395b6ea",
    "/lib/vex/vex.combined.min.js": "ce8070c89395b6ea",
    "/lib/vex/vex.css": "76d7f028edf114c8",
    "/lib/vex/vex.js": "ce8070c89395b6ea",
    "/lib/vex/vex-theme-topanswers.css": "772496da37c708aa",
    "/fonts/apl333.css": "fa82541e788bfa35",
    "/fonts/apl385.css": "1dd5a073e2864671",
    "/fonts/bqn386.css": "998e9ea4f763d4ae",
    "/fonts/charis.css": "fc10cf90160cc4d3",
    "/fonts/dejavu-sans.css": "0a5603a11372e71b",
    "/fonts/dejavu-sans-mono.css": "6e7582654b4f39b3",
    "/fonts/hack.css": "e2a7ba9f952fb00c",
    "/fonts/iosevka.css": "182f249af96ec012",
    "/fonts/quattrocento.css": "56e8a387e8af8fb8",
    "/fonts/sax2.css": "28ed675276a6224b",
    "/fonts/shobhika.css": "7583c3d824a647d8",
    "/fonts/source-code-pro.css": "040b9fc96c140e1a",
    "/fonts/source-sans-pro.css": "7128abb0cd4de792",
    "/fonts/stix2.css": "5cf141611d69835c",
    "/error.js": "60aa3b21598409da",
    "/fouc.css": "cb4be44f955fc826",
    "/global.css": "c6b442f82ce488fa",
    "/header.css": "ec650f1a67184f79",
    "/markdown.css": "bd40766a6ff499a3",
    "/markdown.js": "a648db7d820f06f6",
    "/md.js": "ef46db3751d8e999",
    "/md-oneline.js": "30cbe39e44603c66",
    "/md-shortlinks.js": "089468c314bf7e1c",
    "/navigation.js": "d0d12edff60010b2",
    "/noscript.css": "4f7e2c6342642088",
    "/page/answer/answer.css": "7d8d34681f770e3a",
    "/page/answer/answer.js": "b2dfa53b70c67312",
    "/page/answer-history/answer-history.css": "e261ad744f68003f",
    "/page/answer-history/answer-history.js": "9b236b1349d4e3aa",
    "/page/chat-history/chat-history.css": "1e5da1ea076366cd",
    "/page/chat-history/chat-history.js": "c3068c3d639c1820",
    "/page/community/community.css": "07d091810b33cd96",
    "/page/community/community.js": "ae29ea7e4325788f",
    "/page/community/community_question.css": "07a412ca211cc45d",
    "/page/index/index.css": "e1255dd738e52b37",
    "/page/index/index.js": "d87432c0cae8bec6",
    "/page/profile/profile.css": "a85262ba84ae3880",
    "/page/profile/profile.js": "96ef31b4b07359bc",
    "/page/profile/se.js": "f81862b7e735e616",
    "/page/question-history/question-history.css": "45f6439b41aadb24",
    "/page/question-history/question-history.js": "8745349d8dd90480",
    "/page/question/question.css": "9467cb5a46584b92",
    "/page/question/question.js": "e42da1d897a11cf9",
    "/page/room/room.css": "fe6feab6a06ad6f0",
    "/page/room/room.js": "37e19792513f57c9",
    "/page/sample/sample.css": "94efebee41fedb5f",
    "/page/sample/sample.js": "4d0cf438493c276e",
    "/page/tags/tags.css": "1c23770d61a0ef7f",
    "/page/tags/tags.js": "0f847605e6b7be50",
    "/page/transcript/transcript.css": "03420759b92f7270",
    "/page/transcript/transcript.js": "77c3019c428f6bd4",
    "/page/user/user.css": "11c2caff7b24908b",
    "/page/user/user.js": "7753c25dfe08401b",
    "/post.css": "2d85563808f49017",
    "/require.config.js": "0086167179c4d1e5",
    "/tio.js": "84a530d72166be1e"
}',true)[$f]).'.$2',$f);
}