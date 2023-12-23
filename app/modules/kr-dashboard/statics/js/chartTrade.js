function initTrading(container){
  if($('#' + container).find('.kr-dash-pan-action').length > 0){
    initTradingChartControllers();
    //startTicker($('#' + container).find('.kr-dash-pan-action').attr('symbol') + '-' + $('#' + container).find('.kr-dash-pan-action').attr('currency'));
  }

}


function initTradingChartControllers(){

  $('.kr-dash-pan-action-btn-buy').off('click').click(function(){
    showConfirmationPlaceOrder(this, $(this).parent().attr('container'), 'buy', $(this).parent().find('.kr-dash-pan-action-slcthird').find('div').attr('kr-chart-trade-tp'));
  });

  $('.kr-dash-pan-action-btn-sell').off('click').click(function(){
    showConfirmationPlaceOrder(this, $(this).parent().attr('container'), 'sell', $(this).parent().find('.kr-dash-pan-action-slcthird').find('div').attr('kr-chart-trade-tp'));
  });

  $('.kr-dash-pan-action-confirm').off('click').click(function(e){
    e.preventDefault();
    return false;
  });

  $('.kr-dash-pan-action-slcthird > div').off('click').click(function(){
    $(this).parent().find('ul').toggle();
  });

  $('.kr-dash-pan-action-amount li').off('click').click(function(){
    let currentAmount = $(this).parent().parent().find('input[type="number"]');
    let newAmount = parseFloat(currentAmount.val());
    if($(this).attr('trade-act') == "minus"){
      newAmount -= 1;
    } else {
      newAmount += 1;
    }
    if(newAmount <= 1) newAmount = 1;
    currentAmount.val(newAmount);
    updatePriceAmountTrading();
  });

  $('.kr-dash-pan-chg-exg').off('click').click(function(){
    let n = $(this).attr('kr-chart-trade-tp');
    let img = $(this).find('img').attr('src');
    let p = $(this).parent().parent().find('div');
    let oldN = p.attr('kr-chart-trade-tp');

    let nPrice = $(this).attr('kr-trading-price');
    let oPrice = p.attr('kr-trading-price');

    $(this).parent().prepend('<li class="kr-dash-pan-chg-exg" kr-trading-price="' + oPrice + '" kr-chart-trade-tp="' + oldN + '"><img src="' + p.find('img').attr('src') + '"/></li>')
    p.attr('kr-chart-trade-tp', n);
    p.find('img').attr('src', img);
    p.attr('kr-trading-price', nPrice);
    $(this).parent().parent().parent().parent().find('.kr-dash-pan-action-qtd').attr('kr-market-multticker', nPrice);
    $(this).parent().hide();

    $(this).parent().parent().parent().parent().find('[kr-confirm-v="unit_price"]').attr('kr-confirm-v-up', nPrice);
    $(this).parent().parent().parent().parent().find('[kr-confirm-v="unit_price"]').find('i').html(KRformatNumber(nPrice, 2));

    updatePriceAmountTrading();

    $(this).remove();

    initTradingChartControllers();
  });

  $('.kr-dash-pan-action-confirm > header > div').click(function(e){
    closeTradeConfirmation();
    e.preventDefault();
    return false;
  });

  $('.kr-dash-pan-action').each(function(){
    $(this).find('input[type="number"]').off('keyup').keyup(function(){
      updatePriceAmountTrading();
    });

    $(this).find('input[type="number"]').off('change').change(function(){
      updatePriceAmountTrading();
    });
  });

  $(document).mouseup(function(e)
  {
    var container = $(".kr-dash-pan-action-confirm");
    if (!container.is(e.target) && container.has(e.target).length === 0) closeTradeConfirmation();
  });



}

function showConfirmationPlaceOrder(elementAction, container, type, sparent){
  let confirmationBox = $(elementAction).parent().parent().find('.kr-dash-pan-action-confirm');
  confirmationBox.show();
  confirmationBox.find('.btn-kr-action-placetrade').html('Confirm ' + type + 'ing');
  if(type == "sell"){
    confirmationBox.find('.btn-kr-action-placetrade').removeClass('btn-green');
    confirmationBox.find('.btn-kr-action-placetrade').addClass('btn-lightred');
    confirmationBox.find('[kr-confirm-qntd]').hide();
  } else {
    confirmationBox.find('.btn-kr-action-placetrade').removeClass('btn-lightred');
    confirmationBox.find('.btn-kr-action-placetrade').addClass('btn-green');
    confirmationBox.find('[kr-confirm-qntd]').show();
  }
  confirmationBox.find('a').off('click').click(function(e){
    placerOrder(container, type, sparent);
    closeTradeConfirmation();
    e.preventDefault();
    return false;
  });
}

function closeTradeConfirmation(){
  $('.kr-dash-pan-action-confirm').hide();
  return false;
}

function updatePriceAmountTrading(){
  $('.kr-dash-pan-action').each(function(){
    let priceValue = $(this).find('.kr-dash-pan-action-qtd').attr('kr-market-multticker');
    //console.log(priceValue);
    let amount = parseFloat($(this).find('input[type="number"]').val());
    if(amount.length == 0 || amount < 1) amount = 1;

    let calculatedAmmount = amount / priceValue;
    let nDeci = 6;
    if(calculatedAmmount > 10) nDeci = 5;
    if(calculatedAmmount > 100) nDeci = 4;
    if(calculatedAmmount > 1000) nDeci = 3;
    if(calculatedAmmount > 10000) nDeci = 2;
    if(calculatedAmmount > 100000) nDeci = 1;
    if(calculatedAmmount > 1000000) nDeci = 0;
    $(this).find('.kr-dash-pan-action-qtd').find('span').html(calculatedAmmount.toFixed(nDeci));

    $(this).find('[kr-confirm-v="investment"]').find('i').html(calculatedAmmount.toFixed(nDeci));
    let uprice = 1 / parseFloat($(this).find('[kr-confirm-v="unit_price"]').attr('kr-confirm-v-up'));

    $(this).find('[kr-confirm-v="amount"]').find('i').html(KRformatNumber(amount, 2));


    if($(this).find('[kr-confirm-v="fees"]').length > 0){
      let fees = parseFloat($(this).find('[kr-confirm-v="fees"]').attr('kr-confirm-v-up')) / 100;
      $(this).find('[kr-confirm-v="fees"]').find('i:last-child').html(KRformatNumber(amount * fees, 2));
      $(this).find('[kr-confirm-v="total"]').find('i').html(KRformatNumber(amount + (amount * fees), 2));
    } else {
      $(this).find('[kr-confirm-v="total"]').find('i').html(KRformatNumber(amount, 2));
    }


  });
}

function placerOrder(container, side, market){

  let tradingActionContainer = $('#' + container).find('.kr-dash-pan-action');
  let symbol = tradingActionContainer.attr('symbol');
  let currency = tradingActionContainer.attr('currency');
  let amount = tradingActionContainer.find('input[type="number"]').val();
  if(side == "sell"){
    amount = tradingActionContainer.find('.kr-dash-pan-action-qtd').find('span').html();
  }

  let opt = chartList[container]['option'];
  let dateList = opt['xAxis'][0]['data'];


  let tradeData = {
    'from': symbol,
    'to': currency,
    'side': side,
    'type': 'market',
    'amount': amount,
    'thirdparty': market
  };

  tradeData['date'] = dateList[dateList.length - 1];



  $('.kr-dash-pan-action[symbol="' + symbol + '"]').find('.kr-dash-pan-action-btn-' + side +' > span').html('~ ~ ~');

  $.post($('body').attr('hrefapp') + '/app/modules/kr-trade/src/actions/placeTrade.php', tradeData).done(function(data){

    let response = jQuery.parseJSON(data);

    _updateBalanceData();

    if(response.error == 0){
      $('.kr-dash-pan-action[symbol="' + symbol + '"]').find('.kr-dash-pan-action-btn-' + side +' > span').html('Success');

      $.each($('.kr-dash-pan-cry[symbol="' + symbol + '"][currency="' + currency + '"]'), function(){
        loadChartOrder($(this).attr('id'), symbol, amount, tradeData['date'], side.toUpperCase());
      });

      tradeGraph = setTimeout(function(){
        $('.kr-dash-pan-action[symbol="' + symbol + '"]').find('.kr-dash-pan-action-btn-' + side +' > span').html(side);
      }, 1500);
    } else {
      if(response.error == 3){
        _showThirdpartySetup(response.thirdparty);
      } else {
        showAlert('Oops', response.msg, 'error');
        $('.kr-dash-pan-action[symbol="' + symbol + '"]').find('.kr-dash-pan-action-btn-' + side +' > span').html(side);
      }
    }


  }).fail(function(){
    showAlert('Oops', 'Fail to make the place', 'error');
  });
}

function _showThirdpartySetup(thirdparty){
  $('body').addClass('kr-nblr');
  $.get($('body').attr('hrefapp') + '/app/modules/kr-trade/views/connectThirdparty.php', {thirdparty:thirdparty}).done(function(data){
    $.when($('body').prepend(data)).then(function(){
      _initThirdpartySetupController();
    });
  }).fail(function(){
    showAlert('Oops', 'Fail to load thirdparty setup form', 'error');
  });
}

function _initThirdpartySetupController(){
  $('.kr-thirdparty-setup-form').off('submit').submit(function(e){

    $('.kr-thirdparty-setup').find('form').hide();
    $('.kr-thirdparty-setup').find('.spinner').show();
    $('.thirdparty_connect_field').removeClass('error_field');
    $.post($(this).attr('action'), $(this).serialize()).done(function(data){
      let jsonRes = jQuery.parseJSON(data);
      if(jsonRes.error == 1){
        showAlert('Oops', jsonRes.msg, 'error');
        $('.kr-thirdparty-setup').find('.spinner').hide();
        $('.kr-thirdparty-setup').find('form').show();
      } else {
        if(jsonRes.error == 2){
          $('.kr-thirdparty-setup').find('.spinner').hide();
          $('.kr-thirdparty-setup').find('form').show();
          $('.thirdparty_connect_field').addClass('error_field');
        } else {
          location.reload();
        }
      }
    }).fail(function(){
      showAlert('Oops', 'Fail to save thirdparty (contact admin)', 'error');
    });
    e.preventDefault();
    return false;
  });
}

function closeThirdpartySetup(){
  $('.kr-thirdparty-setup').remove();
  $('body').removeClass('kr-nblr');
}

function removeThirdpartySetup(token){
  $('.kr-thirdparty-setup').find('form').hide();
  $('.kr-thirdparty-setup').find('.spinner').show();
  $.post($('body').attr('hrefapp') + '/app/modules/kr-trade/src/actions/removeThirdparty.php', {token:token}).done(function(data){
    let jsonRes = jQuery.parseJSON(data);
    if(jsonRes.error == 1){
      showAlert('Oops', jsonRes.msg, 'error');
      $('.kr-thirdparty-setup').find('.spinner').hide();
      $('.kr-thirdparty-setup').find('form').show();
    } else {
      location.reload();
    }
  }).fail(function(){
    showAlert('Oops', 'Fail to remove thirdparty', 'error');
  });
}
