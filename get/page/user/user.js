define(['jquery','navigation','datatables/datatables','domReady!'],function($){
  $('#community').change(function(){ window.location = '/profile?community='+$(this).find(':selected').attr('data-name'); });
  $('input[value=save]').css('visibility','hidden');
  $('table').DataTable({
    dom: 'Pfrtip',
    language: { searchPanes: { emptyPanes: null } },
    preDrawCallback: function (settings) {
      $(this).closest('.dataTables_wrapper').find('.dataTables_paginate,.dataTables_info,.dataTables_filter').toggle((new $.fn.dataTable.Api(settings)).page.info().pages > 1);
    }
  });
  $('a.panel').click(function(){
    var panels = $('div.panel'), panel = $('#'+$(this).data('panel'));
    $('a.panel:not([href])').attr('href','.');
    $(this).removeAttr('href');
    panels.hide();
    panel.show();
    return false;
  });
});
