define(['jquery','navigation','datatables/datatables'],function($){
  $('#pin').click(function(){ $(this).prop('disabled',true); $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'pin', pin: $('html').data('pin') }, xhrFields: { withCredentials: true } }).done(function(){
    $('#pin').replaceWith('<code>'+$('html').data('pin')+'</code>'); });
  });
  $('#uuid').click(function(){ var t = $(this); $.get('/profile?uuid').done(function(r){ t.replaceWith('<span class="highlight">'+r+'</span>'); }); });
  $('[name="license"],[name="codelicense"]').on('change',function(){
    if($(this).children('option:selected').data('versioned')===true){
      $(this).next().css('color','rgb(var(--rgb-black))').find('input').prop('disabled',false);
    }else{
      $(this).next().css('color','#ccc').find('input').prop('checked',false).prop('disabled',true);
    }
  }).trigger('change');
  $('[name]').on('change input',function(){
    $(this).parents('fieldset').siblings().find('[name],input').prop('disabled',true);
    $(this).closest('fieldset').find('input[type=submit]').css('visibility','visible');
    if($(this).is('input[type=file]')) $(this).next().click();
  });
  if('highlightRecovery' in $('html').data()){ $('#uuid').click(); }
  $('#community').change(function(){ window.location = '/profile?community='+$(this).find(':selected').attr('data-name'); });
  $('input[value=save]').css('visibility','hidden');
  $('table.data').DataTable({ select: true, dom: 'Pfrtip' });
  $('.select>div:last-child>div>div').each(function(){
    $(this).append('<a href="/profile?community='+$(this).data('community')+'">profile</a>');
  });
});
