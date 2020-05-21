define(['jquery'],function($){
  $('.select>div:first-child').click(function(e){ $(this).parent().toggleClass('open'); e.stopPropagation(); });
  $('.select>div:last-child a').click(function(e){ e.stopPropagation(); return true; });
  $('.select>div:last-child').click(function(e){ return false; });
  $('body').click(function(){ $('.select').removeClass('open'); });
});
