define(()=>{
  return e=>fetch('//post.topanswers.xyz/error', { method: 'POST', body: JSON.stringify(e, Object.getOwnPropertyNames(e)), credentials: 'include' });
});
