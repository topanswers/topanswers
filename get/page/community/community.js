define(['markdown','moment','js.cookie']
       .concat(document.documentElement.style.getPropertyValue('--question')?['starrr']:['jquery.simplePagination']),function([$,_,CodeMirror,tioRequest],moment,Cookies){

  moment.locale($('html').css('--jslang'));

  const DEV = 'dev' in document.documentElement.dataset
      , AUTH = 'auth' in document.documentElement.dataset
      , MINIMAP = 'minimap' in document.documentElement.dataset
      , ROOM = document.documentElement.dataset.room
      , ROOM_CHAT_COUNT = document.documentElement.dataset.roomChatCount
      , ROOM_CHAT_AGE = document.documentElement.dataset.roomChatAge;


  try{ // chat

    let saved;

    try{ // 'reply to' links

      const panels = document.getElementById('chat-panels');

      panels.addEventListener('click',event=>{
        if( (event.target.nodeName==='A') && (event.target.getAttribute('href').substring(0,2)==='#c') ){
          event.preventDefault();
          const id = event.target.getAttribute('href').substring(2);
          let promise = Promise.resolve(), target = document.getElementById('c'+id);

          if(!target){
            const buffer = document.getElementById('jumpchat'), messages = document.getElementById('messages');
            if(!saved) saved = [...messages.children];
            messages.innerHTML = '<i class="fa fa-fw fa-spinner fa-pulse" style="visibility: visible;"></i>';
            promise = fetch('/chat?room='+ROOM+'&around='+id, { credentials: 'include' })
            .then(response => { if(response.ok) return response.text() })
            .then(data => { buffer.innerHTML = data; return processNewChat(buffer); })
            .then(()=>{
              document.getElementById('minimap-wrapper').style.display = 'flex';
              messages.innerHTML = '';
              messages.append(...buffer.children);
              target = document.getElementById('c'+id);
              target.scrollIntoView({ block: 'center' });
            });
          }

          promise.then(()=>{
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.classList.remove('target');
            target.classList.add('target');
          });
        }
      },true);

    }catch(e){ console.error(e); }


    try{ // infinity scroll up

      const scroller = document.querySelector('#chat>.firefoxwrapper'), messages = document.querySelector('#messages'), buffer = document.getElementById('scrollup');
      let getting = false;

      scroller.addEventListener('scroll', e => {
        if(getting) return;
        if(scroller.scrollTop>window.innerHeight) return;
        if(!messages.children.length) return;
        const spinner = document.querySelector('#messages>i:last-child');
        if(!spinner) return;
        spinner.classList.add('fa-pulse');
        spinner.style.visibility = 'visible';
        const last = (()=>{ const m = document.querySelectorAll('#messages>.message'); return m[m.length-1]; })();
        if(!last) return;
        getting = true;
        if(DEV) console.log('getting older chat to id '+last.dataset.id);
        fetch('/chat?room='+ROOM+'&to='+last.dataset.id, { credentials: 'include' })
          .then(response => { if(response.ok) return response.text(); })
          .then(data => { buffer.innerHTML = data; return processNewChat(buffer); })
          .then(()=>{
            const lastTop = last.offsetTop, scrollerTop = scroller.scrollTop, newBottomSpinner = document.querySelector('#scrollup>i:first-child');
            spinner.remove();
            if(newBottomSpinner) newBottomSpinner.remove();
            messages.append(...buffer.children);
            scroller.scrollTop = scrollerTop + last.offsetTop - lastTop;
          }).finally(()=>{ getting = false; });
      });

    }catch(e){ console.error(e); }

    try{ // infinity scroll down

      const scroller = document.querySelector('#chat>.firefoxwrapper'), messages = document.querySelector('#messages'), buffer = document.getElementById('scrolldown');
      let getting = false;

      scroller.addEventListener('scroll', e => {
        if(getting) return;
        if(scroller.classList.contains('follow')) return;
        if(scroller.scrollHeight-scroller.scrollTop-scroller.offsetHeight>window.innerHeight) return;
        if(!messages.children.length) return;
        const spinner = document.querySelector('#messages>i:first-child');
        if(!spinner) return;
        spinner.classList.add('fa-pulse');
        spinner.style.visibility = 'visible';
        const first = document.querySelector('#messages>.message');
        if(!first) return;
        getting = true;
        if(DEV) console.log('getting newer chat from id '+first.dataset.id);
        fetch('/chat?room='+ROOM+'&from='+first.dataset.id, { credentials: 'include' })
          .then(response => { if(response.ok) return response.text(); })
          .then(data => { buffer.innerHTML = data; return processNewChat(buffer); })
          .then(()=>{
            const firstTop = first.offsetTop, scrollerTop = scroller.scrollTop, newTopSpinner = document.querySelector('#scrolldown>i:last-child');
            spinner.remove();
            if(newTopSpinner) newTopSpinner.remove();
            messages.prepend(...buffer.children);
            scroller.scrollTop = scrollerTop + first.offsetTop - firstTop;
          }).finally(()=>{ getting = false; });
      });

    }catch(e){ console.error(e); }

    try{ // follow

      const scroller = document.querySelector('#chat>.firefoxwrapper');

      let intersectionObserver = new IntersectionObserver((entries,observer)=>{ entries.forEach( entry => {
        scroller.classList.remove('newscroll');
        scroller.classList.toggle('follow',entry.isIntersecting);
      } ); }, { root: scroller });

      let mutationObserver = new MutationObserver((mutationsList, observer)=> mutationsList.forEach( mutation => mutation.addedNodes.forEach( child => {
        if(child instanceof Element) if(child.classList.contains('last')) intersectionObserver.observe(child);
      } ) ) );
      mutationObserver.observe(document.getElementById('messages'), { childList: true });

    }catch(e){ console.error(e); }

    try{ // minimap

      if(MINIMAP){

        const map = document.querySelector('#minimap>img'), bar = document.querySelector('#minimap>div');

        let intersectionObserver = new IntersectionObserver((entries,observer)=>{

          entries.forEach( entry => entry.target.classList.toggle('viewport',entry.isIntersecting) );
          const messages = document.querySelectorAll('#messages>.viewport');

          if(messages.length>0){

            let start = map.naturalHeight-1, end = 0;

            document.querySelectorAll('#messages>.viewport').forEach(message=>{
              end = Math.max(end,message.dataset.daysAgo);
              start = Math.min(start,message.dataset.daysAgo);
            });

            bar.style.bottom = (start*100/(map.naturalHeight))+'%';
            bar.style.height = ((end-start+1)*100/(map.naturalHeight))+'%';
            bar.style.display = 'block';

          }

        }, { root: document.querySelector('#chat>.firefoxwrapper') });

        let mutationObserver = new MutationObserver((mutationsList, observer)=> mutationsList.forEach( mutation => mutation.addedNodes.forEach( child => {
          if(child instanceof Element) if('daysAgo' in child.dataset) intersectionObserver.observe(child);
        } ) ) );
        mutationObserver.observe(document.getElementById('messages'), { childList: true });

        function jumpAgo(ago){
          const buffer = document.getElementById('jumpchat'), messages = document.getElementById('messages');
          let scroll;
          if(DEV) console.log(ago);
          if(!saved) saved = [...messages.children];
          messages.innerHTML = '<i class="fa fa-fw fa-spinner fa-pulse" style="visibility: visible;"></i>';
          bar.style.display = 'none';

          fetch('/chat?room='+ROOM+'&daysago='+ago, { credentials: 'include' })
          .then(response => { if(response.ok) return response.text(); })
          .then(data => {
            buffer.innerHTML = data;
            for(let child of buffer.children){
              if(+child.dataset.daysAgo>ago) break;
              scroll = child;
            }
            return processNewChat(buffer);
          }).then(()=>{
            messages.innerHTML = '';
            messages.append(...buffer.children);
            scroll.scrollIntoView();
          });
        }

        function jump(offsetY){ jumpAgo( (map.naturalHeight-offsetY*map.naturalHeight/map.height).toFixed(3) ); }

        map.addEventListener('click',event=>{
          event.preventDefault();
          jump(event.offsetY);
        });

        map.addEventListener('mousemove',event=>{
          const ago = (map.naturalHeight-event.offsetY*map.naturalHeight/map.height)
          map.setAttribute('title',(new Date(new Date().setDate(new Date().getDate()-ago))).toISOString().split('T')[0]);
        });

        document.getElementById('showmap').addEventListener('click',event=>{
          document.getElementById('minimap-wrapper').style.display = 'flex';
          document.getElementById('hidemap').style.display = 'block';
          event.target.style.display = 'none';
        });

        document.getElementById('hidemap').addEventListener('click',event=>{
          document.getElementById('minimap-wrapper').style.display = 'none';
          document.getElementById('showmap').style.display = 'block';
          event.target.style.display = 'none';
        });

        document.getElementById('startmap').addEventListener('click',event=>{
          jump(0);
        });

        document.getElementById('endmap').addEventListener('click',event=>{
          if(saved){
            messages.innerHTML = '';
            messages.append(...saved);
            saved = null;
            messages.children[0].scrollIntoView(false);
          }
        });

        document.getElementById('datemap').children[0].addEventListener('change',event=>{
          const val = event.target.value;
          event.target.value = '';
          jumpAgo( Math.floor( (Date.now() - (Date.now()%(1000*60*60*24)) - (new Date(val)))/(1000*60*60*24) ) );
        });

      }

    }catch(e){ console.error(e); }

    try{ // image pasting
      require(['paste'], function(paste){
        $('#chattext').pastableTextarea().on('pasteImage', function(e,v){
          var d = new FormData();
          d.append('image',v.blob);
          $('#chattext').prop('disabled',true);
          $.post({ url: "//post.topanswers.xyz/upload", data: d, processData: false, cache: false, contentType: false, xhrFields: { withCredentials: true } }).done(function(r){
            $('#chattext').prop('disabled',false).focus();
            textareaInsertTextAtCursor($('#chattext'),'!['+d.get('image').name+'](/image?hash='+r+')');
            $('#chatuploadfile').closest('form').trigger('reset');
          }).fail(function(r){
            alert(r.status+' '+r.statusText+'\n'+r.responseText);
            $('#chattext').prop('disabled',false).focus();
          });
          return false;
        });
      });
    }catch(e){ console.error(e); }

  }catch(e){ console.error(e); }


  try{ // common header navigation
    require(['navigation']);
  }catch(e){ console.error(e); }


  try{ // import from SE
    require(['vex'], function(vex){

      vex.defaultOptions.className = 'vex-theme-topanswers';

      $('#import').click(function(){
        var t = $(this), f = t.closest('form');
        vex.dialog.open({
          input: $('dialog').html()
         ,callback: function(v){
            if(v){
              t.hide().after('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
              f.find('[name=sesiteid]').attr('value',v.site);
              f.find('[name=seids]').attr('value',v.ids);
              f.submit();
            }
          }
        });
        return false;
      });

    });
  }catch(e){ console.error(e); }


  try{ // resizer
    require(['resizer'], function(Resizer){
      $('#dummyresizer').remove();
      new Resizer('body', { width: 6, colour: 'rgb(var(--rgb-black))', full_length: true, callback: function(w) {
        if(AUTH) $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'resizer', position: Math.round(w) }, xhrFields: { withCredentials: true } });
      } });
    });
  }catch(e){ console.error(e); }


  try{ // prod/test environment switching
    if('dev' in $('html').data()) require(['js.cookie'], function(Cookies){
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
    });
  }catch(e){ console.error(e); }


  try{ // tags
    if($('html').css('--question')){
      $('.newtag').click(function(){
        $(this).addClass('hide');
        $('#taginput').removeClass('hide').focus();
      });
      $('#taginput').blur(function(){
        $(this).val('').addClass('hide');
        $('.newtag').removeClass('hide');
      });
      $('#taginput').keydown(function(e){
        if(e.which===27){
          $(this).val('').blur();
          return false;
        }
      });
      $('#taginput').on('input',function(e){
        const t = $(this), o = $('datalist option[value="'+t.val()+'"]:not(:disabled)');
        if(o.length){
          t.val('');
          $.post({ url: '//post.topanswers.xyz/question', data: { id: $('html').css('--question'), tagid: o.data('id'), action: 'new-tag' }, xhrFields: { withCredentials: true } }).then(function(){ window.location.reload(); });
        }
      });
      $('#tagbar').on('mouseenter','.tag[data-id]',function(){ $('.tag.t'+$(this).data('id')).addClass('thread'); }).on('mouseleave','.tag[data-id]',function(){ $('.thread').removeClass('thread'); });
      $('#tagbar').on('click','.tag[data-id]',function(){
        $.post({ url: '//post.topanswers.xyz/question', data: { id: $('html').css('--question'), tagid: $(this).data('id'), action: 'remove-tag' }, xhrFields: { withCredentials: true } }).then(function(){ window.location.reload(); });
      });
      $('.tag[data-id]').each(function(){
        var id = $(this).data('id'), rid = id;
        function foo(b){
          $(this).addClass('t'+id);
          if(arguments.length===0 || b===false) $('.tag[data-implies='+rid+']').each(function(){ rid = $(this).data('id'); foo.call(this,false); });
        }
        foo.call(this);
      });
    }
  }catch(e){ console.error(e); }


  var title = document.title, latestChatId;
  var chatTimer, maxChatChangeID = 0, maxActiveRoomChatID = 0, maxNotificationID = $('html').css('--notification'), numNewChats = 0;
  var maxQuestionPollMajorID = 0, maxQuestionPollMinorID = 0;
  var dismissed = 0;


  setTimeout(function(){ window.scrollTo(0,0); }, 100);
  $('#chattext').blur(function(){ window.scrollTo(0,0); });
  $(window).resize(_.debounce(function(){ $('body').height(window.innerHeight); setTimeout(function(){ window.scrollTo(0,0); },100); })).trigger('resize');

  if(Cookies.get('clearlocal')){
    localStorage.removeItem(Cookies.get('clearlocal'));
    localStorage.removeItem(Cookies.get('clearlocal')+'.title');
    localStorage.removeItem(Cookies.get('clearlocal')+'.type');
    Cookies.remove('clearlocal', { path: '/', domain: 'topanswers.xyz' })
  }

  function setFinalSpacer(){
    var scroller = $('#messages').parent(), frst = Math.round((Date.now() - (new Date($('#messages>.message').first().data('at'))))/1000) || 300, finalspacer = $('#messages .spacer:first-child');
    if(frst>600) finalspacer.css('min-height','1em').css('line-height',(Math.round(100*Math.log(1+frst)/4)/100).toString()+'em').addClass('bigspacer').text(moment.duration(frst,'seconds').humanize());
    if(scroller.hasClass('follow')) scroller.animate({ scrollTop: (scroller.prop('scrollHeight')-scroller.innerHeight())+'px' },'fast');
  }
  function setChatPollTimeout(){
    if('auth' in $('html').data()){
      var chatPollInterval, chatLastChange = Math.round((Date.now() - (new Date($('#messages>.message').first().data('at'))))/1000) || 300;
      clearTimeout(chatTimer);
      if(chatLastChange<10) chatPollInterval = 1000;
      else if(chatLastChange<30) chatPollInterval = 3000;
      else if(chatLastChange<120) chatPollInterval = 5000;
      else if(chatLastChange<600) chatPollInterval = 10000;
      else if(chatLastChange<3600) chatPollInterval = 30000;
      else chatPollInterval = 60000;
      if('dev' in $('html').data()) console.log('set poll interval to '+chatPollInterval);
      chatTimer = setTimeout(checkChat,chatPollInterval);
    }
  }
  function renderQuestion(){
    $(this).find('.summary span[data-markdown]').renderMarkdownSummary();
    $(this).find('.answers>.bar:first-child+.bar+.bar+.bar').each(function(){
      var t = $(this), h = t.nextAll('.bar').addBack();
      if(h.length>1){
        t.prev().addClass('premore');
        $('<div class="bar more"><span></span><a href=".">show '+h.length+' more</a><span></span></div>').appendTo(t.parent()).click(function(){
          t.prev().removeClass('premore');
          $(this).prevAll('.bar:hidden').slideDown().end().slideUp();
          return false;
        });
        h.hide();
      }
    });
    $(this).find('.when').each(function(){
      var t = $(this);
      $(this).text((t.attr('data-prefix')||'')+moment.duration(t.data('seconds'),'seconds').humanize()+' ago'+(t.attr('data-postfix')||''));
      $(this).attr('title',moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'Do MMM YYYY HH:mm' }));
    });
  }
  function processNewQuestions(scroll){
    var newquestions = $('#qa .question:not(.processed)');
    if('dev' in $('html').data()) console.log('processing '+newquestions.length+' questions');
    if($('#qa').scrollTop()<100) scroll = true;
    newquestions.each(renderQuestion);
    newquestions.each(function(){
      if($(this).data('poll-major-id')>maxQuestionPollMajorID) maxQuestionPollMajorID = $(this).data('poll-major-id');
      if($(this).data('poll-minor-id')>maxQuestionPollMinorID) maxQuestionPollMinorID = $(this).data('poll-minor-id');
    });
    if(scroll) setTimeout(function(){ $('#qa').scrollTop(0); },0);
    newquestions.addClass('processed');
  }
  function paginateQuestions(){
    var u = new URLSearchParams(window.location.search)
      , n = u.has('page')?+u.get('page'):1
      , s = u.has('search')?+u.get('search'):''
      , m = $('#questions').children('.question').data('of')
      , i = Cookies.get('pagesize')||10
      , p = Math.ceil(m/i)
      , d = (n<7)?[8,8,8,8,7,6][n-1]:((n>(p-6))?[8,8,8,8,7,5][p-n]:5)
      , o = { items: m
            , itemsOnPage: i
            , currentPage: n
            , prevText: '«'
            , nextText: '»'
            , ellipsePageSet: false
            , displayedPages: d
            , onPageClick: function(n){
                var u = new URLSearchParams(window.location.search);
                u.set('page',n);
                if(n===1) u.delete('page');
                window.history.pushState({},'',(window.location.href.split('?')[0]+'?'+u.toString()).replace(/\?$/,''));
                loadQuestions();
                return false;
            } };
    if(!$('html').css('--question')){
      if((m>i)||(i>10)){
        $('.pages').html('<div></div>'+('auth' in $('html').data()?'<select><option value="10">10/page</option><option value="25">25/page</option><option value="100">100/page</option></select>':'')).children('div').pagination(o);
        $('.pages select').val(i).change(function(){
          var u = new URLSearchParams(window.location.search);
          u.delete('page');
          window.history.pushState({},'',(window.location.href.split('?')[0]+'?'+u.toString()).replace(/\?$/,''));
          Cookies.set('pagesize',$(this).val(),{ secure: true, domain: '.topanswers.xyz', expires: 3650 });
          loadQuestions();
          return false;
        });
      }
    }
    $('#qa>div.banner').show();
  }
  function loadQuestions(){
    $('#questions').children('.question').remove();
    $('.pages').empty();
    $.get('/questions?community='+$('html').css('--community')+window.location.search.replace('?','&'),function(data) {
      var newquestions = $(data).filter('.question').prependTo($('#questions'));
      processNewQuestions();
      paginateQuestions();
      $('#qa').scrollTop(0);
      $('#search+div').hide();
      if($('#search').val()) $('#search').focus();
    },'html');
  }
  function updateQuestions(){
    var maxQuestion = $('#questions>:first-child').data('poll-major-id');
    if('dev' in $('html').data()) console.log('updating questions because polled id > max ('+maxQuestionPollMajorID+')');
    return $.get('/questions?community='+$('html').css('--community')+window.location.search.replace('?','&')).then(function(data) {
      if($('#questions>:first-child').data('poll-major-id')===maxQuestion){
        var newquestions = $(data).filter('.question').filter(function(){ return $(this).data('poll-major-id')>maxQuestion; });
        newquestions.each(function(){ $('#'+$(this).attr('id')).removeAttr('id').slideUp({ complete: function(){ $(this).remove(); } }); });
        newquestions.prependTo($('#questions')).hide().slideDown();
        $('#questions .question').slice(11).slideUp({ complete: function(){ $(this).remove(); } });
        processNewQuestions();
        paginateQuestions();
      }
    },'html');
  }
  function searchQuestions(){
    var u = new URLSearchParams(window.location.search);
    u.set('search',$('#search').val());
    if($('#search').val()==='') u.delete('search');
    u.delete('page');
    window.history.pushState({},'',(window.location.href.split('?')[0]+'?'+u.toString()).replace(/\?$/,''));
    $('#search+div').show();
    loadQuestions()
  }
  function processStarboard(scroll){
    var t = $(this), promises = [] , scroller = $('#starboard').parent()
    $('#starboard .markdown').renderMarkdown(promises);
    $('#starboard .when').each(function(){
      $(this).text('— '+moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' }));
    });
    Promise.allSettled(promises).then(() => {
      $('#starboard>.message').addClass('processed').find('.question:not(.processed)').each(renderQuestion).addClass('processed');
      if(scroll===true) setTimeout(function(){ scroller.scrollTop(1000000); },0);
    });
  }
  function renderChat(){
    var t = $(this), promises = [];
    t.find('.markdown').renderMarkdown(promises);
    return Promise.allSettled(promises).then( () => t.find('.question:not(.processed)').each(renderQuestion).addClass('processed') );
  }
  function processNewChat(buffer){
    const newchat = $(buffer).children('.message');
    let promises = []
      , read = localStorage.getItem('read4')?JSON.parse(localStorage.getItem('read4')):{};

    if((newchat.first().data('id')||0) > (read[$('html').attr('data-room')]||0)){
      if('dev' in $('html').data()) console.log('setting read counter for room '+$('html').attr('data-room')+' to '+newchat.first().data('id'));
      read[$('html').attr('data-room')] = newchat.first().data('id');
      localStorage.setItem('read4',JSON.stringify(read));
    }

    newchat.each(function(){ promises.push(renderChat.call(this)); }).find('.when').each(function(){
      $(this).text('— '+moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' }));
    });

    newchat.find('img').each(function(){ promises.push(new Promise(r => { const i = new Image(); i.onload = () => r(); i.onerror = () => r(); i.src = $(this).attr('src'); })); });

    if(typeof document.fonts !== 'undefined') promises.push(document.fonts.ready);

    $(buffer).children('.bigspacer').each(function(){ $(this).text(moment.duration($(this).data('gap'),'seconds').humanize()); });
    newchat.each(function(){ if($(this).data('change-id')>maxChatChangeID) maxChatChangeID = $(this).data('change-id'); });

    return Promise.allSettled(promises);
  }
  function updateRoomLatest(){
    $('#community-rooms a').each(function(){
      var t = $(this);
      $('#active-rooms a[data-room="'+t.data('id')+'"]').each(function(){
        var u = $(this);
        if(!t.hasClass('this')){
          t.attr('data-unread',u.attr('data-unread'));
          t.attr('data-unread-lang',u.attr('data-unread-lang'));
          t.attr('title',u.attr('title-lang'));
        }
        if(u.siblings().length===0) u.parent().hide();
        u.remove();
      });
    });
    m = $('#active-rooms a[data-unread]').length;
    if(m) $('#more-rooms').attr('data-unread',m).attr('data-unread-lang',m.toLocaleString($('html').css('--jslang')));
    $('#more-rooms').toggleClass('none',$('#active-rooms a').length===0);
  }
  function updateActiveRooms(){
    if('dev' in $('html').data()) console.log('updating active room list');
    return $.get({ url: '/activerooms', data: { community: $('html').css('--community'), read: _.values(localStorage.getItem('read4')?JSON.parse(localStorage.getItem('read4')):{}) } }).then(function(r){
      $('#active-rooms').html(r);
      updateRoomLatest();
    });
  }
  function updateChat(){
    var maxChat = $('#messages>.message').first().data('id');

    if(!('auth' in $('html').data())) return Promise.resolve();
    if('dev' in $('html').data()) console.log('updating chat');

    return $.get('/chat?room='+$('html').attr('data-room')+(($('#messages>.message').length===0)?'':'&from='+maxChat)).then(function(data) {
      var newchat;
      newchat = $(data).prependTo($('#newchat'));
      if(maxChatChangeID) numNewChats += newchat.filter('.message:not(.mine)').length;
      if(maxChatChangeID && (document.visibilityState==='hidden') && numNewChats !== 0){ document.title = '('+numNewChats+') '+title; }
      newchat.filter('.message[data-reply-id]').each(function(){ $('#c'+$(this).attr('data-reply-id')).removeAttr('data-notification-id').removeClass('notify'); });
      return processNewChat($('#newchat'))
    },'html').then(()=>{
      let s = $('#messages').parent();
      $('#messages>.spacer:first-child').remove();
      $('#newchat>.message[data-reply-id!=""]').each(function(){
        let id = $(this).data('id'), rid = $(this).data('reply-id'), m;
        while(true){
          m = $('#messages>#c'+rid);
          if(m.length!==1) break;
          m.addClass('t'+id).addClass('t'+rid);
          rid = m.data('reply-id');
        }
      });
      $('#newchat>i').remove();
      $('#newchat>*').prependTo($('#messages'));
      if(s.hasClass('follow')) s.animate({ scrollTop: (s.prop('scrollHeight')-s.innerHeight())+'px' },'fast');
      return $.get('/activeusers?room='+$('html').attr('data-room')).then(function(r){
        var savepings = $('#active-users .ping').map(function(){ return $(this).data('id'); }).get();
        $('#active-users').html(r);
        $.each(savepings,function(){ $('#active-users .icon[data-id='+this+']').addClass('ping'); });
        return updateActiveRooms();
      });
    });
  }
  function updateChatChangeIDs(){
    if('dev' in $('html').data()) console.log('updating chat change flag statuses');
    return $.get('/chat?changes&room='+$('html').attr('data-room')+'&from='+maxChatChangeID).then(function(r){
      _(JSON.parse(r)).forEach(function(e){ $('#c'+e[0]).each(function(){ if(e[1]>$(this).data('change-id')) $(this).addClass('changed'); }); });
    });
  }
  function updateQuestionPollIDs(){
    if('dev' in $('html').data()) console.log('updating guestion change flag statuses');
    return $.get('/questions?changes&community='+$('html').css('--community')+'&from='+maxQuestionPollMinorID).then(function(r){
      _(JSON.parse(r)).forEach(function(e){ $('#q'+e[0]).each(function(){ if(e[1]>$(this).data('poll-minor-id')) $(this).addClass('changed'); }); });
    });
  }
  function actionChatChange(id){
    if('dev' in $('html').data()) console.log('updating chat '+$('.message.changed').first().data('id'));
    $('#c'+id).css('opacity',0.5);
    return $.get('/chat?room='+$('html').attr('data-room')+'&from='+id+'&to='+id).then(function(r){
      let oldchat = $('#c'+id), merged = oldchat.hasClass('merged'), newchat = $(r).appendTo($('#newchat'));
      if(merged) newchat.addClass('merged');
      return processNewChat($('#newchat')).then(()=>{ $('#c'+id).replaceWith(newchat); });
    });
  }
  function actionQuestionChange(id){
    if('dev' in $('html').data()) console.log('updating question '+$('.question.changed').first().data('id'));
    $('#q'+id).css('opacity',0.5);
    return $.get('/questions?one&community='+$('html').css('--community')+'&id='+id).then(function(r){
      $('#q'+id).replaceWith(r);
      processNewQuestions()
      $('#q'+id).css('opacity',1);
    });
  }
  function processNotifications(){
    var t = $(this), promises = [];
    $('#notifications .markdown').renderMarkdown(promises);
    $('#notifications .when').each(function(){ $(this).text(moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' })); });
    Promise.allSettled(promises).then(() => {
      $('#notifications .markdown').find('.question:not(.processed)').each(renderQuestion).addClass('processed');
      $('#notifications>.notification').addClass('processed');
      $('#chat-bar .panel[data-panel="notifications"]').attr('data-unread',$('#notifications>.notification:not(.dismissed)').length).attr('data-unread-lang',$('#notifications>.notification:not(.dismissed)').length.toLocaleString($('html').css('--jslang')));
    });
  }
  function updateNotifications(){
    if('dev' in $('html').data()) console.log('updating notifications');
    return $.get('/notification?room='+$('html').attr('data-room')+(dismissed?'&dismissed='+dismissed:'')).then(function(r){
      $('#notifications').children().remove();
      $('#notifications').append(r);
      $('#messages>.notify').removeAttr('data-notification-id').removeClass('notify');
      $('#notifications>.message').each(function(){ $('#c'+$(this).attr('data-chat-id')).attr('data-notification-id',$(this).attr('data-id')).addClass('notify'); });
      processNotifications();
    });
  }
  function checkChat(){
    var query = new URLSearchParams(window.location.search)
      , page = query.has('page')?+query.get('page'):1
      , srch = query.has('search')?query.get('search'):''
      , promise;
    clearTimeout(chatTimer);
    setFinalSpacer();
    if('dev' in $('html').data()) console.log('polling chat');
    $.get('/poll?room='+$('html').attr('data-room')).then(function(r){
      var j = JSON.parse(r);
      if( ($('#messages>.last').length>0) && (j.c>+($('#messages>.message').first().data('id')||0)) ) return updateChat().then(()=>{
        if(!$('#messages').parent().hasClass('follow')) $('#messages').parent().addClass('newscroll');
      });
      if(j.n>maxNotificationID){ return updateNotifications().then(() => maxNotificationID = j.n); }
      if((!$('html').css('--question'))&&(j.Q>maxQuestionPollMajorID)&&(page===1)&&(srch.replace(/!|{[^}]*}|\[[^\]]+\]/g,'').trim()==='')){ return updateQuestions().then(() => maxQuestionPollMajorID = j.Q); }
      if(j.cc>maxChatChangeID){ return updateChatChangeIDs().then(() => maxChatChangeID = j.cc); }
      if($('.message.changed').length) return actionChatChange($('.message.changed').first().data('id'));
      if((!$('html').css('--question'))&&(j.q>maxQuestionPollMinorID)&&($('#search').val()==='')){ return updateQuestionPollIDs().then(() => maxQuestionPollMinorID = j.q); }
      if($('.question.changed').length&&($('#search').val()==='')) return actionQuestionChange($('.question.changed').first().data('id'));
      if(j.a>maxActiveRoomChatID){ maxActiveRoomChatID = j.a; return updateActiveRooms(); }
    }).always(setChatPollTimeout);
  }
  function textareaInsertTextAtCursor(e,t) {
    var v = e.val(), s = e.prop('selectionStart')+t.length;
    e.val(v.substring(0,e.prop('selectionStart'))+t+v.substring(e.prop('selectionEnd'),v.length));
    e.prop('selectionStart',s).prop('selectionEnd',s);
    e.trigger('input');
  }

  if(localStorage.getItem('chat')) $('.pane').toggleClass('hidepane');
  $('#join').click(function(){
    $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'new' }, async: false, xhrFields: { withCredentials: true } }).done(function(r){
      location.reload(true);
    }).fail(function(r){
      alert((r.status)===429?'Rate limit hit, please try again later':responseText);
      location.reload(true);
    });
  });
  $('#link').click(function(){ var pin = prompt('Enter PIN (or login key) from account profile'); if(pin!==null) { $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'link', link: pin }, async: false, xhrFields: { withCredentials: true } }).fail(function(r){ alert(r.responseText); }).done(function(){ location.reload(true); }); } });
  $('#poll').click(function(){ checkChat(); });
  $('#chat-wrapper').on('mouseenter', '.message', function(){ $('.message.t'+$(this).data('id')).addClass('thread'); }).on('mouseleave', '.message', function(){ $('.thread').removeClass('thread'); });
  $('#chat-wrapper').on('click','.fa-reply', function(){
    var m = $(this).closest('.message');
    $('#status').attr('data-replyid',m.data('chat-id')).attr('data-replyname',m.data('name')).data('update')();
    $('#chat-bar a.panel[href][data-panel="messages-wrapper"]').click();
    $('.replying').removeClass('replying');
    $('#c'+m.data('chat-id')).addClass('replying');
    $('#chattext').focus();
    return false;
  });
  $('#chat-wrapper').on('click','.fa-ellipsis-h', function(){
    if($(this).closest('.button-group').is(':last-child')) $(this).closest('.button-group').removeClass('show').parent().children('.button-group:nth-child(2)').addClass('show');
    else $(this).closest('.button-group').removeClass('show').next().addClass('show');
    return false;
  });
  $('#chat-wrapper').on('click','.fa-edit', function(){
    var m = $(this).closest('.message');
    $('.ping').removeClass('ping locked');
    $.each(m.data('pings'),function(k,v){ $('.icon.pingable[data-id="'+v+'"]').addClass('ping locked'); });
    $('#status').attr('data-editid',m.data('chat-id')).attr('data-replyid',m.attr('data-reply-id')).attr('data-replyname',m.attr('data-reply-name')).data('update')();
    $('#chattext').val(m.find('.markdown').attr('data-markdown')).focus().trigger('input');
    return false;
  });
  function starflag(t,action,direction){
    var id = t.closest('.message').data('id'), m = $('#c'+id+',#n'+id+',#s'+id).find('.button-group:not(:first-child) .fa-'+action+((direction===-1)?'':'-o'));
    m.css({'opacity':'0.3','pointer-events':'none'});
    $.post({ url: '//post.topanswers.xyz/chat', data: { action: ((direction===-1)?'un':'')+action, room: $('html').attr('data-room'), id: id }, xhrFields: { withCredentials: true } }).done(function(r){
      t.closest('.buttons').find('.fa.fa-'+action+((direction===-1)?'':'-o')).toggleClass('me fa-'+action+' fa-'+action+'-o');
      m.css({ 'opacity':'1','pointer-events':'auto' }).closest('.buttons').find('.button-group .'+action+'s[data-count]').each(function(){ $(this).attr('data-count',+$(this).attr('data-count')+direction); });
    });
  };
  $('#chat-wrapper').on('click','.fa-star-o', function(){ starflag($(this),'star',1); return false; });
  $('#chat-wrapper').on('click','.fa-star', function(){ starflag($(this),'star',-1); return false; });
  $('#chat-wrapper').on('click','.fa-flag-o', function(){ starflag($(this),'flag',1); return false; });
  $('#chat-wrapper').on('click','.fa-flag', function(){ starflag($(this),'flag',-1); return false; });
  if('crew' in $('html').data()){
    $('#chat-wrapper').on('click','.fa-flag-o', function(){ const m = $(this).closest('.message'); m.attr('data-crew-flags',m.data('crew-flags')+1); return false; });
    $('#chat-wrapper').on('click','.fa-flag', function(){ const m = $(this).closest('.message'); m.attr('data-crew-flags',m.data('crew-flags')-1); return false; });
  }
  $('#chat-wrapper').on('click','.notify', function(){
    var t = $(this);
    $.post({ url: '//post.topanswers.xyz/notification', data: { action: 'dismiss', id: t.attr('data-notification-id') }, xhrFields: { withCredentials: true } }).done(function(){
      t.removeAttr('data-notification-id').removeClass('notify');
      updateNotifications();
    });
    return false;
  });
  function subscribe(state){
    var b = $('#question .fa-bell, #question .fa-bell-o');
    b.css({'opacity':'0.3','pointer-events':'none'});
    $.post({ url: '//post.topanswers.xyz/question', data: { action: (state?'':'un')+'subscribe', id: $('html').css('--question') }, xhrFields: { withCredentials: true } }).done(function(r){
      b.css({ 'opacity':'1','pointer-events':'auto' });
      $('#question').toggleClass('subscribed');
    });
  }
  $('.fa-bell').click(function(){ subscribe(false); });
  $('.fa-bell-o').click(function(){ subscribe(true); });
  function flag(direction){
    var t = $(this), b = t.parent().find('.fa-flag, .fa-flag-o, .fa-flag-checkered'), p = t.closest('.post');
    b.css({'opacity':'0.3','pointer-events':'none'});
    $.post({ url: '//post.topanswers.xyz/'+(p.is('#question')?'question':'answer'), data: { action: 'flag', id: p.data('id'), direction: direction }, xhrFields: { withCredentials: true } }).done(function(r){
      b.css({ 'opacity':'1','pointer-events':'auto' });
      p.removeClass('flagged counterflagged');
      if(direction===1) p.addClass('flagged');
      if(direction===-1) p.addClass('counterflagged');
    });
  }
  $('.post .fa-flag').click(function(){ flag.call(this,0); });
  $('.post .fa-flag-o').click(function(){ flag.call(this,1); });
  $('.post .fa-flag-checkered').click(function(){ flag.call(this,$('#question').is('.counterflagged')?0:-1); });
  $('body').on('click','.icon.pingable:not(.locked)', function(){
    var t = $(this);
    if(t.hasClass('ping')){
      $('.icon.pingable[data-id="'+t.data('id')+'"]').removeClass('ping');
    }else{
      $('.icon.pingable[data-id="'+t.data('id')+'"]').addClass('ping');
      textareaInsertTextAtCursor($('#chattext'),'@'+t.data('name')+' ');
    }
    $('#chattext').focus();
    $('#status').data('update')();
  });
  $('a.comment').click(function(){
    var t = $(this), p = t.closest('.post');
    $('a.comment').removeClass('ping');
    t.addClass('ping');
    $('.icon.pingable[data-id="'+t.data('id')+'"]').addClass('ping');
    textareaInsertTextAtCursor($('#chattext'),'@'+t.data('name')+', re: [your '+(p.hasClass('answer')?'answer':'question')+'](#'+p.attr('id')+'), ');
    $('#chattext').focus();
    $('#status').data('update')();
    return false;
  });
  $('#status').data('update',function(){
    var strings = [];
    if($('#status').attr('data-editid')) strings.push('editing');
    if($('#status').attr('data-replyid')) strings.push('replying to: '+$('#status').attr('data-replyname'));
    //console.debug(_.uniqBy($('.ping').map(function(){ return [$(this).data('id'),$(this).data('fullname')]; }).get(),function(e){ return e[0]; }));
    //console.debug(_.map(_.uniqBy($('.ping').map(function(){ return [$(this).data('id'),$(this).data('fullname')]; }).get(),function(e){ return e[0]; }),function(e){ return e[1]; }));
    if($('.ping').length) strings.push('pinging: '+_.map(_.uniqBy($('.ping').map(function(){ return { 'id': $(this).data('id'), 'name': $(this).data('fullname') }; }).get(),function(e){ return e.id; }),function(e){ return e.name; }).join(', '));
    if(strings.length){
      $('#canchat-wrapper').addClass('pinging');
      $('#status').children('span').text(strings.join(', '));
      $('#cancel').show();
    }else{
      $('#canchat-wrapper').removeClass('pinging');
      $('#status').children('span').text($('html').css('--l_preview')+':');
      $('#cancel').hide();
    }
  });
  $('#cancel').click(function(){
    var url = location.href;
    $('.replying').removeClass('replying');
    $('.ping').removeClass('ping locked');
    $('#status').attr('data-editid','').attr('data-replyid','').attr('data-replyname','').data('update')();
    window.location.hash='';
    history.replaceState(null,null,url);
  });
  $('#chatshowpreview').on('mousedown',function(){ return false; }).click(function(){
    $('#canchat-wrapper').addClass('previewing');
    $('#preview .CodeMirror').each(function(){ $(this).get(0).CodeMirror.refresh(); });
    Cookies.set('hidepreview','false',{ secure: true, domain: '.topanswers.xyz', expires: 3650 });
    if($('#messages').parent().hasClass('follow')) $('#messages').parent().scrollTop(1000000);
    return false;
  });
  $('#chathidepreview').on('mousedown',function(){ return false; }).click(function(){
    $('#canchat-wrapper').removeClass('previewing');
    Cookies.set('hidepreview','true',{ secure: true, domain: '.topanswers.xyz', expires: 3650 });
    return false;
  });
  $('#chatshowkeyboard').on('mousedown',function(){ return false; }).click(function(){
    $('#canchat-wrapper').addClass('keyboard');
    Cookies.set('hidekeyboard','false',{ secure: true, domain: '.topanswers.xyz', expires: 3650 });
    if($('#messages').parent().hasClass('follow')) $('#messages').parent().scrollTop(1000000);
    return false;
  });
  $('#chathidekeyboard').on('mousedown',function(){ return false; }).click(function(){
    $('#canchat-wrapper').removeClass('keyboard');
    Cookies.set('hidekeyboard','true',{ secure: true, domain: '.topanswers.xyz', expires: 3650 });
    return false;
  });
  $('#community').change(function(){
    window.location = '/'+$(this).find(':selected').attr('data-name');
  });
  $('#room').change(function(){
    window.location = '/'+$('html').css('--community')+'?room='+$(this).val();
  });
  function renderPreview(sync){
    var m = $('#chattext').val(), s
      , scroller = $('#messages').parent()
      , scroll = (scroller.scrollTop()+scroller.innerHeight()+40) > scroller.prop("scrollHeight")
      , promises = []
      , onebox = false;
    sync = typeof sync !== 'undefined' ? sync : false;
    $('#canchat-wrapper').toggleClass('chatting',m?true:false);
    $('#preview .markdown').html('&nbsp;');
    if(!onebox){
      s = m.match(/^https:\/\/topanswers.xyz\/transcript\?room=([1-9][0-9]*)&id=(-?[1-9][0-9]*)?[^#]*(#c(-?[1-9][0-9]*))?$/);
      if(s&&(s[2]===s[4])){
        $.get({ url: '/chat?quote&room='+$('html').attr('data-room')+'&id='+s[2], async: !sync }).done(function(r){
          if($('#chattext').val()===m){
            $('#preview .markdown').css('visibility','visible').attr('data-markdown',r.replace(/[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z/m,function(match){ return ' *— '+(moment(match).fromNow())+'*'; })).renderMarkdown(promises);
          }
        }).fail(function(){
          if($('#chattext').val()===m){
            $('#preview .markdown').css('visibility',(m?'visible':'hidden')).attr('data-markdown',(m.trim()?m:'&nbsp;')).renderMarkdown(promises);
          }
        });
        return;
      }
    }
    if(!onebox){
      s = m.match(/^https:\/\/(?:www.youtube.com\/watch\?v=|youtu.be\/)([-_0-9a-zA-Z]*)$/);
      if(s){
        $.post({ url: '//post.topanswers.xyz/onebox/youtube', data: { id: s[1] }, xhrFields: { withCredentials: true }, async: !sync }).done(function(r){
          if($('#chattext').val()===m){
            $('#preview .markdown').css('visibility','visible').attr('data-markdown',r).renderMarkdown(promises);
          }
        });
        onebox = true;
      }
    }
    if(!onebox){
      s = m.match(/^https:\/\/xkcd.com\/([0-9]*)\/?$/);
      if(s){
        $.post({ url: '//post.topanswers.xyz/onebox/xkcd', data: { id: s[1] }, xhrFields: { withCredentials: true }, async: !sync }).done(function(r){
          if($('#chattext').val()===m){
            $('#preview .markdown').css('visibility','visible').attr('data-markdown',r).renderMarkdown(promises);
          }
        });
        onebox = true;
      }
    }
    if(!onebox){
      s = m.match(/^https:\/\/[a-z]+.wikipedia.org\/wiki\/.*$/);
      if(s){
        $.post({ url: '//post.topanswers.xyz/onebox/wikipedia', data: { url: s[0] }, xhrFields: { withCredentials: true }, async: !sync }).done(function(r){
          if($('#chattext').val()===m){
            $('#preview .markdown').css('visibility','visible').attr('data-markdown',r).renderMarkdown(promises);
          }
        });
        onebox = true;
      }
    }
    if(!onebox){
      s = m.match(/^(?:https?:\/\/)?(?:www\.)?topanswers.xyz\/[^\?\/]+\?q=([1-9][0-9]*)$/);
      if(s){
        $('#preview .markdown').css('visibility','visible').attr('data-markdown','@@@ question '+s[1]).renderMarkdown(promises);
        onebox = true;
      }
    }
    if(!onebox){
      s = m.match(/^(?:https?:\/\/)?(?:www\.)?topanswers.xyz\/[^\?\/]+\?q=[1-9][0-9]*#a([1-9][0-9]*)$/);
      if(s){
        $('#preview .markdown').css('visibility','visible').attr('data-markdown','@@@ answer '+s[1]).renderMarkdown(promises);
        onebox = true;
      }
    }
    if(!onebox){
      s = m.match(/^(``?)(?: ([^\n]+)| ?([-a-z0-9]+)? *\n([\s\S]+))$/);
      if(s){
        const live = s[1].length===1, codelang = s[3]?s[3].split('-')[0]:$('html').css('--lang-code')||'none', tiolang = s[3]||$('html').css('--lang-tio'), code = s[2]||s[4];
        if(tiolang){
          let f = ':::', c = '§§§', o = '```';
          while((new RegExp('^'+f,'m')).test(code)) f+=':';
          while((new RegExp('^'+c,'m')).test(code)) c+='§';
          while((new RegExp('^'+o,'m')).test(code)) o+='`';
          if(live){
            tioRequest(code,tiolang).then(function(r){
              while((new RegExp('^'+f,'m')).test(r.output)) f+=':';
              while((new RegExp('^'+c,'m')).test(r.output)) c+='§';
              while((new RegExp('^'+o,'m')).test(r.output)) o+='`';
              if($('#chattext').val()===m){
                $('#preview .markdown').css('visibility','visible').attr('data-markdown',f+' tio '+r.req+'\n'+c+' '+codelang+' '+tiolang+'\n'+code+'\n'+c+'\n'+o+' none\n'+r.output+'\n'+o+'\n'+f).renderMarkdown(promises);
              }
            });
          }else{
            $('#preview .markdown').css('visibility','visible').attr('data-markdown',o+' '+codelang+'\n'+code+'\n'+o).renderMarkdown(promises);
          }
          onebox = true;
        }
      }
    }
    if(!onebox) $('#preview .markdown').css('visibility',(m?'visible':'hidden')).attr('data-markdown',(m.trim()?m:'&nbsp;')).renderMarkdown(promises);
    Promise.allSettled(promises).then(() => {
      $('#preview .question:not(.processed)').each(renderQuestion).addClass('processed');
      if(scroll) scroller.scrollTop(1000000);
    });
    if(scroll) scroller.scrollTop(1000000);
  }
  var renderPreviewThrottle;
  renderPreviewThrottle = _.throttle(renderPreview,100);
  $('#chattext').each(function(){ $(this).css('height',this.scrollHeight).data('initialheight',this.scrollHeight); }).on('input', function(){
    if(this.scrollHeight>$(this).outerHeight()) $(this).css('height',this.scrollHeight);
    renderPreviewThrottle();
  }).trigger('input');
  $('#chattext').keydown(function(e){
    var t = $(this), msg = t.val(),  replyid = $('#status').attr('data-replyid'), c = $('#c'+replyid), edit = $('#status').attr('data-editid')!=='', editid = $('#status').attr('data-editid'), post, arr = [];
    if(!t.prop('disabled')) { //Safari workaround for double-posting
      if(e.which===13) {
        if(!e.shiftKey) {
          if(msg.trim()){
            $(this).prop('disabled',true);
            clearTimeout(chatTimer);
            renderPreview(true);
            $('.ping').each(function(){ arr.push($(this).data('id')); });
            if(edit){
              post = { msg: $('#preview>.markdown').attr('data-markdown'), room: $('html').attr('data-room'), editid: editid, replyid: replyid, pings: arr, action: 'edit' };
              $('#c'+editid).css('opacity',0.5);
            }else{
              post = { room: $('html').attr('data-room')
                     , msg: $('#preview>.markdown').attr('data-markdown')
                     , replyid: replyid
                     , pings: arr
                     , action: 'new'
                     , read: _.values(localStorage.getItem('read4')?JSON.parse(localStorage.getItem('read4')):{}) };
            }
            $.post({ url: '//post.topanswers.xyz/chat', data: post, xhrFields: { withCredentials: true } }).then(function(){
              localStorage.removeItem('read4');
              if(edit){
                $('#c'+editid).css('opacity',1).find('.markdown').attr('data-markdown',msg).attr('data-reply-id',replyid).end().each(renderChat);
                checkChat();
              }else{
                if(replyid) $('#notifications .message[data-id='+replyid+']').remove();
                $('#messages').parent().scrollTop(1000000);
                updateChat().always(checkChat);
              }
              $('#cancel').click();
              t.val('').prop('disabled',false).css('height',t.data('initialheight')).focus().trigger('input');
              $('#listen').html($('html').css('--l_mute')).attr('id','mute');
            }).fail(function(r){
              alert(r.status+' '+r.statusText+'\n'+r.responseText);
              t.prop('disabled',false).focus();
            });
            $('.ping').removeClass('ping locked');
          }
          return false;
        }else{
          textareaInsertTextAtCursor($(this),'  ');
        }
      }else if(e.which===38){
        if(msg===''){
          $('#messages .message.mine').first().find('.fa-edit').click()
          return false;
        }
      }else if(e.which===27){
        $('#cancel').click();
        t.val('').css('height',$(this).data('initialheight')).css('min-height',0).focus().trigger('input');
        return false;
      }
    }
  });
  document.addEventListener('visibilitychange', function(){ numNewChats = 0; if(document.visibilityState==='visible') document.title = title; else latestChatId = $('#messages .message:first').data('id'); }, false);
  $('#chatupload').click(function(){ $('#chatuploadfile').click(); });
  $('#chatuploadfile').change(function() {
    if(this.files[0].size > 2097152){
      alert("File is too big — maximum 2MB");
      $(this).val('');
    }else{
      $(this).closest('form').submit();
    };
  });
  $('#chatuploadfile').closest('form').submit(function(){
    var d = new FormData($(this)[0]);
    $('#chattext').prop('disabled',true);
    $.post({ url: "//post.topanswers.xyz/upload", data: d, processData: false, cache: false, contentType: false, xhrFields: { withCredentials: true } }).done(function(r){
      $('#chattext').prop('disabled',false).focus();
      textareaInsertTextAtCursor($('#chattext'),'!['+d.get('image').name+'](/image?hash='+r+')');
      $('#chatuploadfile').closest('form').trigger('reset');
    }).fail(function(r){
      alert(r.status+' '+r.statusText+'\n'+r.responseText);
      $('#chattext').prop('disabled',false).focus();
    });
    return false;
  });
  $('#notifications .when').each(function(){ $(this).text(moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'dddd, Do MMM YYYY HH:mm' })); });
  if($('html').css('--question')){
    if('auth' in $('html').data()){
      $('#question .starrr, #qa .answer .starrr').each(function(){
        var t = $(this), v = t.data('votes'), vv = t.prev().data('total');
        t.starrr({
          rating: v,
          max: $('html').css('--power'),
          change: function(e,n){
            n = n||0;
            if(n!==v){
              t.css({'opacity':'0.3','pointer-events':'none'});
              $.post({ url: '//post.topanswers.xyz/'+t.data('type'), data: { action: 'vote', id: t.data('id'), votes: n }, xhrFields: { withCredentials: true } }).done(function(r){
                var req;
                vv = vv-v+n;
                v = n;
                t.css({'opacity':'1','pointer-events':'auto'}).prev().attr('data-total',vv);
                if(t.data('type')==='question'){
                  req = $('html').css('--required')-vv;
                  t.prev().attr('data-required',req);
                  $('#provide').prop('disabled',req>0)
                }
              }).fail(function(r){ alert((r.status)===429?'Rate limit hit, please try again later':r.responseText); });
            }
          }
        });
        t.find('a').removeAttr('href');
      });
    }
  }
  processNewQuestions(true);
  paginateQuestions();
  (function(){
    var promises = [];
    $('#qa .post:not(.processed)').find('.markdown[data-markdown]').renderMarkdown(promises);
    Promise.allSettled(promises).then(() => {
      $('#qa .post:not(.processed) .question').each(renderQuestion);
      $('#qa .post:not(.processed) .answers .summary span[data-markdown]').renderMarkdownSummary();
      $('#qa .post:not(.processed) .when').each(function(){
        var t = $(this);
        t.text((t.attr('data-prefix')||'')+moment.duration(t.data('seconds'),'seconds').humanize()+' ago'+(t.attr('data-postfix')||''));
        t.attr('title',moment(t.data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'Do MMM YYYY HH:mm' }));
      });
      $('#qa .post').addClass('processed');
    });
  })();
  processNewChat($('#newchat')).then(()=>{
    $('#newchat>*').appendTo($('#messages'));
    $('#messages').parent().scrollTop(1000000);
  });
  updateActiveRooms();
  processNotifications();
  setChatPollTimeout();
  (function(){
    var promises = [];
    $('#info').find('.markdown[data-markdown]').renderMarkdown(promises);
    Promise.allSettled(promises).then(() => {
      $('#info').css('color','rgb(var(--rgb-dark)');
    });
  })();
  if($('html').css('--question')) setTimeout(function(){ $('.answer:target').each(function(){ $(this)[0].scrollIntoView(); }); }, 500);
  $(window).on('hashchange',function(){ if($(':target').length) $(':target')[0].scrollIntoView(); });
  $('#chat-wrapper').on('click','#mute', function(){
    var t = $(this);
    $.post({ url: '//post.topanswers.xyz/room', data: { action: 'mute', id: $('html').attr('data-room') }, xhrFields: { withCredentials: true } }).done(function(){
      t.html($('html').css('--l_listen')).attr('id','listen');
      $('#listen').show();
      updateActiveRooms();
    });
    t.html('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
    return false;
  });
  $('#chat-wrapper').on('click','#listen', function(){
    var t = $(this);
    $.post({ url: '//post.topanswers.xyz/room', data: { action: 'listen', id: $('html').attr('data-room') }, xhrFields: { withCredentials: true } }).done(function(){
      t.html($('html').css('--l_mute')).attr('id','mute');
      updateActiveRooms();
    });
    t.html('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
    return false;
  });
  $('#chat-wrapper').on('click','#pin', function(){
    var t = $(this);
    $.post({ url: '//post.topanswers.xyz/room', data: { action: 'pin', id: $('html').attr('data-room') }, xhrFields: { withCredentials: true } }).done(function(){
      t.html($('html').css('--l_unpin')).attr('id','unpin');
      $('#unpin').show();
      updateActiveRooms();
    });
    t.html('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
    return false;
  });
  $('#chat-wrapper').on('click','#unpin', function(){
    var t = $(this);
    $.post({ url: '//post.topanswers.xyz/room', data: { action: 'unpin', id: $('html').attr('data-room') }, xhrFields: { withCredentials: true } }).done(function(){
      t.html($('html').css('--l_pin')).attr('id','pin');
      updateActiveRooms();
    });
    t.html('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
    return false;
  });
  $('#chat-wrapper').on('click','.notification .fa.fa-times-circle', function(){
    var n = $(this).closest('.notification').attr('data-id');
    $.post({ url: '//post.topanswers.xyz/notification', data: { action: 'dismiss', id: n }, xhrFields: { withCredentials: true } }).done(function(){
      $('#messages>.message.notify[data-notification-id='+n+']').removeAttr('data-notification-id').removeClass('notify');
      updateNotifications().then(() => {
        if(!$('#notifications').children('div').length) $('#chat-bar a.panel[href][data-panel="messages-wrapper"]').click();
        if('dev' in $('html').data()) console.log($('#notifications').children().length);
      });
    });
    $(this).replaceWith('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
    return false;
  });
  $('#chat-wrapper').on('click','#more-notifications', function(){
    dismissed = $(this).data('dismissed');
    $(this).html('<i class="fa fa-fw fa-spinner fa-pulse"></i>');
    updateNotifications();
    return false;
  });
  $('#search').on('input',()=>{ $('#questions>.question').remove(); $('.pages').empty(); });
  $('#search').on('input',_.debounce(searchQuestions,1000));
  $('#search').keydown(function(e){
    if(e.which===27){
      $(this).val('').trigger('input');
      return false;
    }
  });
  $('#chat-bar a.panel').click(function(){
    var panels = $('#chat-panels>div'), panel = $('#'+$(this).data('panel'));
    if(!panel.hasClass('panel')) panel = panel.parent();
    $('#chat-bar a.panel:not([href])').attr('href','.');
    $(this).removeAttr('href');
    panels.css({ 'visibility': 'hidden', 'z-index': '-1' });
    panel.css({ 'visibility': 'visible', 'z-index': 'unset' });
    return false;
  });
  processStarboard(true);
  $('#more-rooms').click(function(){
    $('#active-rooms').slideToggle(200);
    return false;
  });
  $('.panecontrol.fa-angle-double-right').click(function(){ localStorage.setItem('chat','chat'); $('.pane').toggleClass('hidepane'); $('#chattext').trigger('input').blur(); });
  $('.panecontrol.fa-angle-double-left').click(function(){ localStorage.removeItem('chat'); $('.pane').toggleClass('hidepane'); });
  $('a.license').click(function(){ $(this).hide().next('.element').show(); return false; });

  $('#keyboard>span>span').click(function(){
    textareaInsertTextAtCursor($('#chattext'),$(this).text());
    $('#chattext').focus();
    return false;
  });
},function(e){ console.error('boo'); });
