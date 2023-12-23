$(document).ready(function(){

  $('.kr-leftnav').find('li').click(function(){

    changeView($(this).attr('kr-module'), $(this).attr('kr-view'));
  });

  changeView('dashboard', 'dashboard');


  $('.kr-watching-wdsf').off('click').click(function(){
    $('.kr-leftside').addClass('kr-leftside-resp-on');
  });

  $('.kr-toggle-live-dash-trade').off('click').click(function(){
    toggleMarketLive();
  });

  $(document).mouseup(function(e)
  {
    var container = $(".kr-watching-wdsf");
    if (!container.is(e.target) && container.has(e.target).length === 0) $('.kr-leftside').removeClass('kr-leftside-resp-on');
  });

  enableTimeheader();

  $('.kr-toggle-theme-white').click(function(){
    $(this).toggleClass('kr-white-theme');
    if($(this).hasClass('kr-white-theme')) {
      $('body').attr('kr-theme', 'light');
      updateUserSettings('white_mode', 'true');
    }
    else {
      $('body').attr('kr-theme', '');
      updateUserSettings('white_mode', 'false');
    }
    _reloadLogoType();
    _reloadContainerColor();

  });

});

let moduleConstruct = {
  'dashboard': {
    'dashboard': initDashboard
  },
  'marketanalysis': {
    'dashboard': initHeatmap,
    'coinlist': initCoinlist,
    'marketlist': initMarketList
  },
  'admin': {
    'dashboard': initAdmin,
    'users': initAdmin,
    'generalsettings': initAdmin,
    'coins': initAdmin,
    'currencies': initAdmin,
    'news-social': initAdmin,
    'mailsettings': initAdmin,
    'subscriptions': initAdmin,
    'payment': initAdmin,
    'intro': initAdmin,
    'trading': initAdmin,
    'withdraw': initAdmin
  },
  'blockfolio': {
    'blockfolio': initBlockFolio
  },
  'coin': {
    'coin': initCoinView
  }
};

function enableTimeheader(){
  if($('.kr-current-time').length == 0) return false;
  let date = new Date();
  let listMonth = $('.kr-current-time').attr('mlist').split(',');
  let listDay = $('.kr-current-time').attr('dlist').split(',');
  let dayNumber = date.getDay();
  if(dayNumber == 0) dayNumber = 7;
  $('.kr-current-time').find('span').html(listDay[dayNumber - 1] + ' ' + date.getDate() + ', ' + listMonth[date.getMonth()] + '  ' + (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ' : ' +
                                              (date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes()) + ' : ' +
                                              (date.getSeconds() < 10 ? '0' + date.getSeconds() : date.getSeconds()));
  setTimeout(function(){
    enableTimeheader();
  }, 500);
}

let viewPost = null;
function changeView(mod, view, args = {}, callback = null, forcehidewatching = false){
  if(mod == undefined || view == undefined) return false;

  $('.kr-leftnav').find('li[kr-view].kr-leftnav-select').removeClass('kr-leftnav-select');


  if($('body').attr('mbill') == "false"){
    if($('.kr-leftnav').find('li[kr-module="' + mod + '"]').attr('kr-view-allowed') == '*'){
      $('.kr-leftnav').find('li[kr-module="' + mod + '"]').addClass('kr-leftnav-select');
      if($('.kr-leftnav').find('li[kr-module="' + mod + '"]').attr('kr-modules-hleft') == "true" || forcehidewatching){
        $('.kr-leftside').hide();
      } else {
        $('.kr-leftside').show();
      }
    } else {
      $('.kr-leftnav').find('li[kr-module="' + mod + '"][kr-view="' + view + '"]').addClass('kr-leftnav-select');
      if($('.kr-leftnav').find('li[kr-module="' + mod + '"][kr-view="' + view + '"]').attr('kr-modules-hleft') == "true" || forcehidewatching){
        $('.kr-leftside').hide();
      } else {
        $('.kr-leftside').show();
      }
    }

    if(forcehidewatching){
      $('.kr-leftside').hide();
    }
  }


  if(viewPost != null) viewPost.abort();

  $('.kr-dashboard').html('');

  showDashboardLoading();

  viewPost = $.post($('body').attr('hrefapp') + '/app/modules/kr-' + mod + '/views/' + view + '.php', args).done(function(data){
    $.when($('.kr-dashboard').append(data)).then(function(){
      hideDashboardLoading();
      moduleConstruct[mod][view]();
      if(callback != null) callback.call();
    });
  }).fail(function(){
    //showAlert('Ooops', 'Fail to change view : ' + view, 'error');
  });;
}

function showDashboardLoading(){
  $('.kr-dashboard').prepend('<div class="kr-dashboard-loading"><div><div class="sk-folding-cube sk-folding-cube-orange"> <div class="sk-cube1 sk-cube"></div> <div class="sk-cube2 sk-cube"></div> <div class="sk-cube4 sk-cube"></div> <div class="sk-cube3 sk-cube"></div> </div></div></div>');
}

function hideDashboardLoading(){
  $('.kr-dashboard-loading').fadeOut();
}

function appendPageTitle(subtitle){

  $(document).find('title').html(subtitle + ' — ' + $(document).find('title').attr('static-title'));

}

function KRformatNumber(value, decimal = 2){
  let infosFormat = $('body').attr('kr-numformat').split(':');
  return $.number(value, decimal, infosFormat[0], infosFormat[1]);
}

function KRunformatNumber(value){
  let infosFormat = $('body').attr('kr-numformat').split(':');
  value = value.replace(infosFormat[0], '.');
  value = value.replace(infosFormat[1], '');
  return value;
}

function updateUserSettings(k, v){
  $.post($('body').attr('hrefapp') + '/app/modules/kr-user/src/actions/changeUserSettings.php', {k:k, v:v}).done(function(data){
    let jsonRes = jQuery.parseJSON(data);
    if(jsonRes.error == 1){
      showAlert('Oops', jsonRes.msg, 'error');
    }
  }).fail(function(){
    showAlert('Oops', 'Fail to access to change settings script (404, 505)', 'error');
  })
}

function toggleMarketLive(){
  if(!$('.kr-live-dash-trade').hasClass('kr-trade-hide')){
    $('.kr-live-dash-trade').addClass('kr-trade-hide');
    $('.kr-live-dash-trade').find('.lnr-chevron-down').removeClass('lnr-chevron-down').addClass('lnr-chevron-up');
    $('.kr-live-dash-trade').find('.lnr-chevron-up').html('<use xlink:href="#lnr-chevron-up"></use>');
    updateUserSettings('hide_market', 'true');
  } else {
    $('.kr-live-dash-trade').removeClass('kr-trade-hide');
    $('.kr-live-dash-trade').find('.lnr-chevron-up').removeClass('lnr-chevron-up').addClass('lnr-chevron-down');
    $('.kr-live-dash-trade').find('.lnr-chevron-down').html('<use xlink:href="#lnr-chevron-down"></use>');
    updateUserSettings('hide_market', 'false');
  }
}

function _reloadLogoType(){
  if($('body').attr('kr-theme') == "light"){
    $('img').each(function(){
      let path = $(this).attr('src');
      if (path.indexOf("logo.svg") >= 0){
        path = path.replace('logo.svg', 'logo_black.svg');
        $(this).attr('src', path);
      }
    });
  } else {
    $('img').each(function(){
      let path = $(this).attr('src');
      if (path.indexOf("logo_black.svg") >= 0){
        path = path.replace('logo_black.svg', 'logo.svg');
        $(this).attr('src', path);
      }
    });
  }
}

function closeUpdateNewFeature(){
  $('.kr-adm-notif-popup').remove();
}
