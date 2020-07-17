define(function(){

  try{

    const mainnav = document.getElementById('mainnav');

    mainnav.firstElementChild.addEventListener('click',event=>{
      event.stopPropagation();
      mainnav.classList.toggle('open');
    });

    document.documentElement.addEventListener('click',()=>mainnav.classList.remove('open'));

  }catch(e){ console.error(e); }

});
