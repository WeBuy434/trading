function initCoinView(){

  initDashboard(true);


  loadDepthGraph();

  if($('.kr-wallet-top').length > 0) initTradingAction();


  addSubscribtion($('.kr-dash-pan-cry').attr('symbol'), $('.kr-dash-pan-cry').attr('currency'));

  subscribeStreamerCallback(function(dataCoin){
    if(dataCoin.FromSymbol == $('.kr-dash-pan-cry').attr('symbol') && dataCoin.ToCurrency == $('.kr-dash-pan-cry').attr('currency')){
      $('.kr-trade-lst.kr-trade-lst-uniq').prepend('<li>' +
        '<div>' +
          '<span class="kr-mono kr-trade-lst-' + dataCoin.Type.toLowerCase() + '">' + $.number(dataCoin.Total, 2, ',', ' ') + ' ' + dataCoin.ToCurrency + '</span>' +
        '</div>' +
        '<div>' +
          '<span class="kr-mono">' + dataCoin.Quantity + '</span' +
        '</div>' +
      '</li>');

      $('.kr-trade-lst.kr-trade-lst-uniq').find('li').slice(50).remove();
    }
  }, 0);

  subscribeStreamerCallback(function(dataCoin) {
    if(dataCoin.FROMSYMBOL == $('.kr-dash-pan-cry').attr('symbol') && dataCoin.TOSYMBOL == $('.kr-dash-pan-cry').attr('currency')){
      $.each(dataCoin, function(k, v){
        if($('.kr-cinf-item').find('i[kr-cinf-v="' + k + '"]').length > 0){

          if(k == "PRICE" && v > 10) v = $.number(v, 2, ',', ' ');


          if(k == "CHANGE24HOURPCT"){
            if(v < 0) $('.kr-cinf-item').find('i[kr-cinf-v="' + k + '"]').parent().parent().removeClass('kr-cinf-item-positiv').addClass('kr-cinf-item-negativ');
            else $('.kr-cinf-item').find('i[kr-cinf-v="' + k + '"]').parent().parent().removeClass('kr-cinf-item-negativ').addClass('kr-cinf-item-positiv');
          }

          v = v.replace('.', ',');

          $('.kr-cinf-item').find('i[kr-cinf-v="' + k + '"]').html(v);

          if(k == "PRICE"){
            $('[kr-coin-v-data="PRICE"]').find('i').html(v);
          }


        }

      });
    }
  });

  $('.kr-cinf-changeexchange-toggle').click(function(){
    $(this).parent().toggleClass('kr-cinf-changeexchange-tggled');
    if($(this).parent().hasClass('kr-cinf-changeexchange-tggled')){
      $('.kr-cinf-buysell').hide();
    } else {
      $('.kr-cinf-buysell').show();
    }

  });

}


function loadDepthGraph(){

  if($('#canvas_depth').length == 0) return false;

  var graphPicture = document.getElementById('canvas_depth').getContext('2d');

  let labels = $('#canvas_depth').attr('xv').split(',');
  let graphValueAsk = $('#canvas_depth').attr('yaskv').split(',');
  let graphValueBid = $('#canvas_depth').attr('ybidv').split(',');

  let oldAskLength = graphValueBid.length;
  for (var i = 0; i < graphValueBid.length; i++) {
    graphValueAsk.push(null);
  }

  for (var i = 0; i < oldAskLength; i++) {
    graphValueBid.unshift(null);
  }

  var myLineChart = new Chart(graphPicture, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        borderWidth:1,
        borderColor:'rgb(41, 195, 89, 1)',
        backgroundColor: 'rgba(41, 195, 89, 0.6)',
        data: graphValueAsk
      },
      {
        borderWidth:1,
        borderColor:'rgb(227, 15, 15, 1)',
        data: graphValueBid,
        backgroundColor: 'rgba(227, 15, 15, 0.6)'
      }],
    },
    options: {
        animation: {
          duration: 0
        },
        legend: {
          display: false
        },
        tooltips: {
          enabled: false
        },
        elements: {
          point: {
            radius: 0,
            hoverRadius: 0,
            hitRadius: 0
          }
        },
        scales: {
          yAxes: [{
            gridLines: {
              display: false
            },
            display: false,
            ticks: {
              min: 0
            }
          }],
          xAxes: [{
            gridLines: {
              display: false
            },
            display: false
          }]
        }
    }
  });

  // setTimeout(function(){
  //   loadDepthGraph();
  // }, 1000);

}

function reloadTradesListSimple(){
  reloadTradesList($('input[type="hidden"][name="from"]').val(), $('input[type="hidden"][name="to"]').val(), $('input[type="hidden"][name="thirdparty"]').val());
}

function initTradingAction(){

  //alert('dd');

  //startTicker($('.kr-cinf-buysell-action').attr('pair'));

  if($('input[type="hidden"][name="thirdparty"]').length > 0) reloadTradesListSimple();



  $('[kr-trade-type]').click(function(){
    $("[kr-trade-type]").removeClass('selected-act-bs-n');
    $(this).addClass('selected-act-bs-n');
    $('[kr-trade-inpt-type]').hide().attr('kr-trade-inpt-enabled', '0');
    $('[kr-trade-inpt-type="' + $(this).attr('kr-trade-type') + '"]').attr('kr-trade-inpt-enabled', '1').show();
    if($(this).attr('kr-trade-force') == "1"){
      changeTradeSymbol($(this).attr('kr-trade-force-currency'), $(this).attr('kr-trade-force-currency'));
    } else {
      changeTradeSymbol($('.kr-cinf-buysell-type-selected').attr('kr-trade-symbol'), $('.kr-cinf-buysell-type-selected').attr('kr-conv-symbol'));
    }
    $(this).addClass('selected-act-bs-n');
    $('.kr-cinf-trade-total').attr('kr-cinf-trade-total-field', $(this).attr('kr-trade-totalfield'));
    reloadTotalAmount();
  });

  $('[kr-trade-side]').click(function(){
    if($(this).hasClass('kr-cinf-buysell-type-selected')) return false;
    $("[kr-trade-side]").removeClass('kr-cinf-buysell-type-selected');
    $(this).addClass('kr-cinf-buysell-type-selected');
    if(getTypeTradingSelected() != "limit") changeTradeSymbol($(this).attr('kr-trade-symbol'), $(this).attr('kr-conv-symbol'));
    if($(this).attr('kr-trade-side') == "sell"){
      $('[kr-trade-btn-type-flow]').val($('[kr-trade-btn-type-flow]').attr('alt-sell'));
      $('[kr-trade-btn-type-flow]').addClass('kr-trade-btn-type-sell');
    } else {
      $('[kr-trade-btn-type-flow]').val($('[kr-trade-btn-type-flow]').attr('alt-buy'));
      $('[kr-trade-btn-type-flow]').removeClass('kr-trade-btn-type-sell');
    }
    reloadTotalAmount();
  });

  $('[kr-trade-amount-field], [kr-trade-amount-number]').focusout(function(){
    if($(this).val().length > 0) $(this).val(getFormatedAmount($(this).val()));
  }).keyup(function(){
    reloadTotalAmount();
  });

  $('.kr-cinf-buysell-action').find('input[type="submit"]').removeAttr('disabled');


  $('.kr-cinf-buysell-action').submit(function(e){

    let tradeData = {};
    let side = $('.kr-cinf-buysell-type').find('.kr-cinf-buysell-type-selected').attr('kr-trade-side');
    if(side != 'sell' && side != 'buy'){
      showAlert('Oops', 'Wrong side', 'error');
      return false;
    }
    tradeData['side'] = side;

    $('[kr-trade-inpt-enabled="1"]').each(function(){
      let nameInpt = $(this).find('input[type="text"]').attr('name');
      let valueInpt = $(this).find('input[type="text"]').val();
      let currency = $(this).parent().find('[kr-trade-dynamic-symbol]').html();
      tradeData[nameInpt] = valueInpt;
    });

    tradeData['from'] = $(this).attr('from');
    tradeData['to'] = $(this).attr('to');
    tradeData['pair'] = $(this).attr('from') + '-' + $(this).attr('to');

    tradeData['amount'] = $('input[type="text"][name="amount"]').val();
    tradeData['thirdparty'] = $('input[type="hidden"][name="thirdparty"]').val();

    tradeData['type'] = $('.kr-cinf-buysell-trade-type').find('.selected-act-bs-n').attr('kr-trade-type');

    let pairInfos = tradeData['pair'].split('-');

    // if(tradeData['type'] == "market"){
    //   let dataGraph = $('.kr-dash-pan-cry[symbol="' + pairInfos[0] + '"][currency="' + pairInfos[1] + '"]').first();
    //
    //   let opt = chartList[dataGraph.attr('id')]['option'];
    //   let dateList = opt['xAxis'][0]['data'];
    //
    //   tradeData['date'] = dateList[dateList.length - 1];
    // }


    $(this)[0].reset();
    reloadTotalAmount();
    let oldSubmitValue = $(this).find('input[type="submit"]').val();
    $(this).find('input[type="submit"]').val('Loading ...');
    $(this).find('input[type="submit"]').attr('disabled', 'true');
    $('.kr-cinf-trade-err').removeClass('kr-cinf-trade-success').hide();

    $.post($(this).attr('action'), tradeData).done(function(data){
      $('.kr-cinf-buysell-action').find('input[type="submit"]').val(oldSubmitValue);
      $('.kr-cinf-buysell-action').find('input[type="submit"]').removeAttr('disabled');
      let response = jQuery.parseJSON(data);
      if(response.error == 1){
        showAlert('Oops', response.msg, 'error');
      } else if(response.error == 2){
        $('.kr-cinf-trade-err').find('div').html(response.msg);
        $('.kr-cinf-trade-err').fadeIn();
      } else {
        $('.kr-cinf-buysell-action').find('input[type="submit"]').val(response.msg);

        if(tradeData['type'] == "market"){
          $.each($('.kr-dash-pan-cry[symbol="' + pairInfos[0] + '"][currency="' + pairInfos[1] + '"]'), function(){
            let opt = chartList[$(this).attr('id')]['option'];
            let dateList = opt['xAxis'][0]['data'];
            loadChartOrder($(this).attr('id'), pairInfos[0], tradeData['amount'], dateList[dateList.length - 1], tradeData['side'].toUpperCase());
          });
          _updateBalanceData();
          reloadTradesListSimple();
        }

        setTimeout(function(){
          $('.kr-cinf-buysell-action').find('input[type="submit"]').val(oldSubmitValue);
        }, 1500);
      }
    }).fail(function(){
      showAlert('Oops', 'Fail to make the place', 'error');
    });

    e.preventDefault();
    return false;
  });


}

function getFormatedAmount(amount){
  amount = amount.replace(/\,/g, '.');
  let nV = amount.split('.');
  if(nV.length > 2) amount = '0.00';
  if(isNaN(parseFloat(amount))) amount = '0.00';
  if(amount < 0) amount = '0.00';
  return amount;
}

function changeTradeSymbol(symbol, convert_symbol){
  $('.kr-cinf-trade-total').find('span').find('i').html('(' + convert_symbol + ')');
  $('.kr-cinf-trade-total').attr('convsymbol', convert_symbol);
  $('[kr-trade-dynamic-symbol="1"]').html(symbol);
  $('[kr-trade-dynamic-symbol="1"]').parent().find('input[type="text"]').val('');

  reloadTotalAmount();
}

function getTypeTradingSelected(){ return $('.selected-act-bs-n').attr('kr-trade-type'); }

function reloadTotalAmount(){

  let total = '0.00000000';
  if($('.kr-cinf-buysell-type-selected').attr('kr-trade-side') == 'sell' || getTypeTradingSelected() == "limit") total = '0.00';

  let pair = $('.kr-cinf-buysell-action').attr('pair');

  let amountTotal = getFormatedAmount($('#' + $('.kr-cinf-trade-total').attr('kr-cinf-trade-total-field')).val());

  let unitPrice = parseFloat($('input[type="hidden"][name="unit_price"]').val());

  let comissionValue = parseFloat($('.kr-cinf-trade-commission-value').attr('kr-trade-commission-v'));

  let comissionAmount = 0;
  let totalWCommission = 0;
  if(getTypeTradingSelected() == "limit"){
    total = (parseFloat(amountTotal)).toFixed(2);

    comissionAmount = (total * (comissionValue / 100)).toFixed(2);
    if($('.kr-cinf-buysell-type-selected').attr('kr-trade-side') == 'sell'){
      totalWCommission = parseFloat(total) - parseFloat(comissionAmount);
    } else {
      totalWCommission = parseFloat(total) + parseFloat(comissionAmount);
    }
  } else {
    if($('.kr-cinf-buysell-type-selected').attr('kr-trade-side') == 'sell'){
      total = (amountTotal * unitPrice).toFixed(2);

      comissionAmount = (total * (comissionValue / 100)).toFixed(2);
      totalWCommission = parseFloat(total) - parseFloat(comissionAmount);

    } else {
      total = (parseFloat(amountTotal) / parseFloat(unitPrice)).toFixed(9);

      comissionAmount = (parseFloat(amountTotal) * (comissionValue / 100)).toFixed(2);
      totalWCommission = parseFloat(amountTotal) + parseFloat(comissionAmount);
    }
  }

  $('.kr-cinf-trade-commission-total').find('.kr-cinf-trade-commission-value').find('b').html(comissionAmount);
  $('.kr-cinf-trade-amount-total').find('.kr-cinf-trade-total-value-wc').find('b').html(totalWCommission.toFixed(2));


  $('.kr-cinf-trade-total-value').html(total);
}

function reloadTradesList(symbol, to, market, type = 'load'){
  if($('.kr-cinf-order-filledorder').length > 0){
    $.post($('body').attr('hrefapp') + '/app/modules/kr-trade/src/actions/getOrderList.php', {from:symbol, to:to, type:type, thirdparty:market}).done(function(data){
      //console.log(data);
      let jsonRes = jQuery.parseJSON(data);
      if(jsonRes.error == 1){
        showAlert('Oops', jsonRes.msg, 'error');
      } else {
        $.each(jsonRes.list_filled, function(k, v){
          if($('.kr-cinf-order-filledorder').find('[kr-order-id="' + v.id + '"]').length == 0){
            $('.kr-cinf-order-filledorder').prepend('<li kr-order-id="' + v.id + '">' +
              '<div class="kr-cinf-order-t-' + v.side + '">' + v.amount + '</div>' +
              '<div>' + v.size + '</div>' +
              '<div>' + v.date + '</div>' +
            '</li>');
          }
        });
      }
    }).fail(function(){
      showAlert('Oops', 'Fail to reload trades list');
    });
  }
}
