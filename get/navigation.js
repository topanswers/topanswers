define(['jquery','js.cookie'],function($,Cookies){
  $('#environment').change(function(){
    var v = $(this).val();
    if(v==='prod'){
      Cookies.remove('environment',{ secure: true, domain: '.topanswers.xyz' });
    }else{
      Cookies.set('environment',v,{ secure: true, domain: '.topanswers.xyz' });
    }
    $(this).attr('disabled',true);
    window.location.reload(true);
  });
  $('.select>div:first-child').click(function(e){ $(this).parent().toggleClass('open'); e.stopPropagation(); });
  $('.select>div:last-child a').click(function(e){ e.stopPropagation(); return true; });
  $('.select>div:last-child').click(function(e){ return false; });
  $('body').click(function(){ $('.select').removeClass('open'); });
});
