define(['pako'],(pako)=>{

  const tio = (code,lang)=>{
    var oneTimeToken = "'" + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15) + "'";
    var runRequest = new XMLHttpRequest;
  
    function textToByteString(string) {return unescape(encodeURIComponent(string));}
    function codeToByteString(code) {
      var value = textToByteString(code), runString = ["Vlang","1",lang,"Vargs","0","F.input.tio","0","F.code.tio"];
      runString.push(value.length);runString.push(value);runString.push("R");
      return runString.join("\0");
    }
    function deflate(byteString) {return pako.deflateRaw(byteStringToByteArray(byteString), {"level": 9});}
    function inflate(byteString) {return byteArrayToByteString(pako.inflateRaw(byteString));}
    function byteStringToText(byteString) {return decodeURIComponent(escape(byteString));}
    function byteStringToByteArray(byteString) {
      var byteArray = new Uint8Array(byteString.length);
      for(var index = 0; index < byteString.length; index++)byteArray[index] = byteString.charCodeAt(index);
      byteArray.head = 0;
      return byteArray;
    }
    function byteArrayToByteString(byteArray) {
      var retval = "";
      iterate(byteArray, function(byte) { retval += String.fromCharCode(byte); });
      return retval;
    }
    function iterate(iterable, monad) {if (!iterable)return;for (var i = 0; i < iterable.length; i++)monad(iterable[i]);}
    function byteStringToBase64(byteString) {
    	return btoa(byteString).replace(/\+/g, "@").replace(/=+/, "");
    }
  
    return new Promise(function (resolve, reject) {
  		runRequest.onreadystatechange = function () {
  			if (runRequest.readyState !== 4) return;
  			if (runRequest.status >= 200 && runRequest.status < 300) {
          var response = byteArrayToByteString(new Uint8Array(runRequest.response));
          var rawOutput = inflate(response.slice(10));
          var output;
          try {output = byteStringToText(rawOutput);}catch(error) {output = rawOutput;}
          output = output.replace(new RegExp(output.slice(0,16).replace(/\W/g,t=>"\\"+t),"g"),"").split("\n").slice(0,-5).join("\n").replace(/\n$/g,'');
  				resolve({ req: byteStringToBase64(byteArrayToByteString(deflate(lang+'ÿÿ'+textToByteString(code)+'ÿÿ'))), output: output });
  			} else {
  				reject({
  					status: runRequest.status,
  					statusText: runRequest.statusText
  				});
  			}
  		};
  
  		runRequest.open('POST','https://tio.run/cgi-bin/run',true);
      runRequest.responseType = "arraybuffer";
  		runRequest.send(deflate(codeToByteString(code)));
  	});
  }

  return tio;

});
