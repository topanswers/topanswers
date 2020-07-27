define(['domReady!'],()=>{

  const AUTH = 'auth' in document.documentElement.dataset; 

  function error(e){
    console.error(e);
    if(AUTH) fetch('//post.topanswers.xyz/error', { method: 'POST', body: JSON.stringify(e, Object.getOwnPropertyNames(e)), credentials: 'include' });
  }

  window.addEventListener('error', e=>error(e));

  return error;

});
