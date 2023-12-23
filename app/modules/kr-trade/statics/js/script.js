$(document).ready(function(){

  $('[kr-side-part="kr-leaderboard"]').off('click').click(function(){
    toggleLeaderBoard();
  });

  $('[kr-side-part="kr-orderbook"]').off('click').click(function(){
    toggleOrderbook();
  });

  if($('.kr-trade-lst').length <= 0) return false;

  $.get($('body').attr('hrefapp') + '/app/modules/kr-trade/src/actions/getTradecoins.php').done(function(data){
    let jsonRes = jQuery.parseJSON(data);
    $.each(jQuery.parseJSON(jsonRes.symbols), function(k, symbol){
      addSubscribtion(symbol, jsonRes.currency, 0);
    });
  }).fail(function(){
    showAlert('Ooops', 'Fail to gat trade coins list', 'error');
  });

  subscribeStreamerCallback(function(dataCoin){
    updateTradeTable(dataCoin);
    updateTradeBalance(dataCoin);
  }, 0);

});

function updateTradeTable(dataCoin){

  if($('.kr-trade').hasClass('kr-trade-hide')) return false;

  $('.kr-trade-lst.kr-trade-lst-global').prepend('<li>' +
    '<div class="kr-trade-lst-symbol">' +
      '<span class="kr-mono">' + dataCoin.FromSymbol + '</span>' +
    '</div>' +
    '<div>' +
      '<span class="kr-mono">' + dataCoin.Market + '</span>' +
    '</div>' +
    '<div>' +
      '<span class="kr-mono kr-trade-lst-' + dataCoin.Type.toLowerCase() + '">' + $.number(dataCoin.Total, 2, ',', ' ') + ' ' + dataCoin.ToCurrency + '</span>' +
    '</div>' +
    '<div>' +
      '<span class="kr-mono">' + dataCoin.Quantity + '</span' +
    '</div>' +
  '</li>');

  $('.kr-trade-lst.kr-trade-lst-global').find('li').slice(50).remove();
}

let balanceHistory = {
  'buy': [],
  'sell': []
};
function updateTradeBalance(dataCoin){

  if($('.kr-trade').hasClass('kr-trade-hide')) return false;

  if(dataCoin.Type.toLowerCase() == 'unknown') return false;
  balanceHistory[dataCoin.Type.toLowerCase()].push('1');
  if(balanceHistory[dataCoin.Type.toLowerCase()].length > 1200){
      balanceHistory['buy'] = []; balanceHistory['sell'] = [];
  }

  $('.kr-trade-balance > div:first-child').css('max-width', ((balanceHistory['buy'].length / (balanceHistory['buy'].length + balanceHistory['sell'].length)) * 100) + '%');
  $('.kr-trade-balance > div:last-child').css('max-width', ((balanceHistory['sell'].length / (balanceHistory['buy'].length + balanceHistory['sell'].length)) * 100) + '%');

}

function toggleLeaderBoard(){

  $('.kr-rankingside').toggleClass('kr-rankingside-show');
  if($('.kr-rankingside').hasClass('kr-rankingside-show')){
    $('[kr-side-part="kr-leaderboard"]').addClass('kr-leftnav-select');
    if($('.kr-rankingside-mine').length == 0){
      $.get($('body').attr('hrefapp') + '/app/modules/kr-trade/views/leaderboard.php').done(function(data){
        $('.kr-rankingside > *').not('header').remove();
        $('.kr-rankingside').append(data);
      });
    }
  } else {
    $('[kr-side-part="kr-leaderboard"]').removeClass('kr-leftnav-select');
  }
  checkGraphResize();
}

let timeOutOrderBook = null;

function orderBookSync(){

  $.get($('body').attr('hrefapp') + '/app/modules/kr-trade/views/orderBook.php').done(function(data){
    $('.kr-orderbookside > *').not('header').remove();
    $('.kr-orderbookside').append(data);
    timeOutOrderBook = setTimeout(function(){
      orderBookSync();
    }, 5000);
  });
}

function toggleOrderbook(){

  $('.kr-orderbookside').toggleClass('kr-orderbookside-show');
  if($('.kr-orderbookside').hasClass('kr-orderbookside-show')){
    $('[kr-side-part="kr-orderbook"]').addClass('kr-leftnav-select');
    $.get($('body').attr('hrefapp') + '/app/modules/kr-trade/views/orderBook.php').done(function(data){
      $('.kr-orderbookside > *').not('header').remove();
      $('.kr-orderbookside').append(data);
      timeOutOrderBook = setTimeout(function(){
        orderBookSync();
      }, 5000);
    });
  } else {
    $('[kr-side-part="kr-orderbook"]').removeClass('kr-leftnav-select');
    clearTimeout(timeOutOrderBook); timeOutOrderBook = null;
  }
  checkGraphResize();
}
