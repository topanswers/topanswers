define(['md-oneline','navigation'],function(md){

  const table = document.querySelector('table');

  let target = document.querySelector('tr:target');
  if(target){
    target.classList.add('target');
    target.scrollIntoView({ block: 'center' });
  }

  history.replaceState(null,'',' ');

  for(const e of document.querySelectorAll('td[data-markdown]:not([data-markdown=""])')) e.innerHTML = md.renderInline(e.dataset.markdown.split('\n')[0]);
  document.documentElement.style.display = 'block';

  table.addEventListener('click',event=>{
    if( (event.target.nodeName==='A') && (event.target.getAttribute('href').substring(0,1)==='#') ){
      event.preventDefault();
      if(target) target.classList.remove('target');
      target = document.getElementById(event.target.getAttribute('href').substring(1));
      target.classList.add('target');
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  },true);
});
