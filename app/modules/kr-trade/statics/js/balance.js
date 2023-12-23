$(document).ready(function(){
  $('.kr-wallet-top > div').off('click').click(function(){
    $('.kr-wallet-top > section').css('display', 'flex');
  });
  $('[kr-wallet-change]').off('click').click(function(){
    _changeWalletBalance($(this).attr('kr-wallet-change'));
  });

  $('[kr-credit-balance]').off('click').click(function(){
    _loadCreditForm('depositChooseBalance');
  });

  $(document).mouseup(function(e)
  {
      var container = $('.kr-wallet-top');
      if (!container.is(e.target) && container.has(e.target).length === 0) $('.kr-wallet-top > section').css('display', 'none');
  });

  $('[kr-credit-widthdraw]').off('click').click(function(){
    _askWidthdraw();
  });

  $('[kr-balance-transaction-history]').off('click').click(function(){
    _loadCreditForm('transactionsHistory', {}, 'Transactions history');
  });

  $('.kr-wallet-balance-show-list').off('click').click(function(){
    _loadCreditForm('balanceList', {exchange:$(this).attr('kr-balance-exchange')}, 'Balance list');
  });

  $('.kr-wallet-top-change').find('[kr-wallet-exch-name]').off('click').click(function(){
    $('.kr-wallet-top > section').css('display', 'none');
    $.post($('body').attr('hrefapp') + '/app/modules/kr-trade/src/actions/changeMainThirdparty.php', {exchange:$(this).attr('kr-wallet-exch-name')}).done(function(data){
      let jsonRes = jQuery.parseJSON(data);
      if(jsonRes.error == 1){
        showAlert('Oops', jsonRes.msg, 'error');
      } else {
        $('.kr-wallet-top-resum > ul').html('');
        _updateBalanceData();
      }
    }).fail(function(){
      showAlert('Oops', 'Fail to change exchange account', 'error');
    });
  });

});

function _askWidthdraw(){
  $.get($('body').attr('hrefapp') + '/app/modules/kr-trade/src/actions/askWidthdraw.php').done(function(data){
    $.when($('body').prepend(data)).then(function(){
      _initWidthdrawPopup();
    });
  }).fail(function(){
    showAlert('Oops', 'Fail to open widthdraw form (404, 505)', 'error');
  });
}

function _loadCreditForm(form, args = {}, title = 'Make a deposit'){
  if($('.kr-balance-credit').length == 0){
    $('body').prepend('<section class="kr-balance-credit kr-ov-nblr">' +
      '<section>' +
        '<header>' +
          '<span>' + title + '</span>' +
          '<div onclick="_closeCreditForm();"> <svg class="lnr lnr-cross"><use xlink:href="#lnr-cross"></use></svg> </div>' +
        '</header><div class="spinner"></div>' +
      '</section>' +
    '</section>');
    $('body').addClass('kr-nblr');
  } else {
    $('.kr-balance-credit > section > header > span').html(title);
  }

  $('.kr-balance-credit').attr('kr-balance-credit-view', form);

  $('.kr-balance-credit > section > section').remove();
  $('.kr-balance-credit > section > div').remove();
  $.post($('body').attr('hrefapp') + '/app/modules/kr-trade/src/actions/' + form + '.php', args).done(function(data){
    $.when($('.kr-balance-credit > section').append(data)).then(function(){
      _initCreditPopup();
    });
  }).fail(function(){
    showAlert('Oops', 'Fail to load credit form', 'error');
  });
}

function _initWidthdrawPopup(){
  $("#kr-credit-chosamount").ionRangeSlider({
    step: 0.01,
    min:$("#kr-credit-chosamount").attr('kr-chosamount-min'),
    max:$("#kr-credit-chosamount").attr('kr-chosamount-max'),
    grid: true,
    postfix: " $",
    onChange: function (data) {
      $('[name="kr_widthdraw_amount"]').val(data.from);
    }
  });

  $('.kr-createwidthdraw').off('submit').submit(function(e){
    $('.kr-balance-widthdraw').hide();
    $('.kr-balance-widthdraw').parent().find('.spinner').show();
    $.post($(this).attr('action'), $(this).serialize()).done(function(data){
      let jsonRes = jQuery.parseJSON(data);
      _closeCreditForm();
      if(jsonRes.error == 1){
        showAlert('Oops', jsonRes.msg, 'error');
      } else {
        showAlert('Success', jsonRes.msg, 'success');
        _updateBalanceData();
      }
    }).fail(function(){
      showAlert('Oops', 'Fail to create widthdraw (404, 505)', 'error');
    });
    e.preventDefault();
    return false;
  });


}

function _initCreditPopup(){
  $('[kr-balance-credit]').off('click').click(function(){
    if($(this).hasClass('kr-balance-credit-dibl')) return false;
    let type = $(this).attr('kr-balance-type');
    if(type == "practice"){
      $.post($('body').attr('hrefapp') + '/app/modules/kr-trade/src/actions/depositBalance.php', {bid:$(this).attr('kr-balance-idc')}).done(function(data){
        let jsonRes = jQuery.parseJSON(data);
        if(jsonRes.error == 1){
          showAlert('Oops', jsonRes.msg, 'error');
        } else {
          _closeCreditForm();
          _updateBalanceData();
        }
      }).fail(function(){
        showAlert('Oops', 'Fail to access to the deposit script (404, 505)', 'error');
      });
    } else {
      _loadCreditForm('depositRealBalance', {});
    }
  });

  $('[kr-charges-payment]').off('click').click(function(){
    let ptype = $(this).attr('kr-charges-payment');
    let paymentAmount = $('[kr-charges-payment-vamdepo]').val();
    $('.kr-balance-credit > section > section').hide();
    $('.kr-balance-credit > section > div.spinner').show();
    if(ptype == "creditcard"){
      _loadCreditForm('depositCreditCard', {amount:paymentAmount});
    } else if(ptype == "coingate"){
      window.open($('body').attr('hrefapp') + "/app/modules/kr-payment/views/coingate.php?t=deposit&m=" + paymentAmount, "popupWindow", "width=1041, height=669, scrollbars=yes");
      let timeCreated = $(this).attr('kr-cng-lt');
      setTimeout(function(){
        _checkCoinGatePayment(timeCreated);
      }, 5000);
    } else {
      $.post($('body').attr('hrefapp') + '/app/modules/kr-payment/src/actions/deposit/processOther.php', {type:ptype, amount:paymentAmount}).done(function(data){
        let jsonRes = jQuery.parseJSON(data);
        if(jsonRes.error == 1){
          showAlert('Oops', jsonRes.msg, 'error');
        } else {
          window.location.replace(jsonRes.link);
        }
      });
    }
  });

  $("#kr-credit-chosamount").ionRangeSlider({
    step: 10,
    grid: true,
    min:$("#kr-credit-chosamount").attr('kr-chosamount-min'),
    max:$("#kr-credit-chosamount").attr('kr-chosamount-max'),
    postfix: " $",
    onChange: function (data) {
      _recalCreditAmount(data.from);

    }
  });

  $('.kr-deposit-creditcard').off('submit').submit(function(e){
    $('.kr-balance-credit > section > section').hide();
    $('.kr-balance-credit > section > div.spinner').show();
    $.post($(this).attr('action'), $(this).serialize()).done(function(data){
      let jsonRes = jQuery.parseJSON(data);
      if(jsonRes.error == 1){
        showAlert('Oops', jsonRes.msg, 'error');
      } else {
        _closeCreditForm();
        _updateBalanceData();
      }
    }).fail(function(){
      showAlert('Oops', 'Fail to access to payment (404, 505)', 'error');
    });
    e.preventDefault();
    return false;
  });




}

function _recalCreditAmount(amount){
  let fees = parseFloat($('[kr-credit-calcfees="fees"]').attr('kr-credit-calcfees-am')) / 100;
  $('[kr-charges-payment-vamdepo]').val(amount);
  $('[kr-credit-calcfees="amount"]').find('i').html(amount);
  let feesTotal = amount * fees;
  $('[kr-credit-calcfees="fees"]').find('i').html(KRformatNumber(feesTotal, 2));

  $('[kr-credit-calcfees="total"]').find('i').html(KRformatNumber(feesTotal + amount, 2));
}

function _closeCreditForm(){
  $('.kr-balance-credit').remove();
  $('body').removeClass('kr-nblr');
}

function _changeWalletBalance(bid){
  $.post($('body').attr('hrefapp') + '/app/modules/kr-trade/src/actions/changeBalance.php', {bid:bid}).done(function(data){
    let jsonRes = jQuery.parseJSON(data);
    if(jsonRes.error == 1){
      showAlert('Oops', jsonRes.msg, 'error');
    } else {
      $('.kr-wallet-top > div').attr('class', 'kr-wallet-top-' + jsonRes.balance.type_balance);
      $('.kr-wallet-top > div > div > span:first-child').html(jsonRes.balance.title);
      $('.kr-wallet-top > div > div').find('[kr-balance-id]').attr('kr-balance-id', jsonRes.balance.enc_id_balance);
      _updateBalanceData();
      $('.kr-wallet-top > section').css('display', 'none');
    }
  }).fail(function(){
    showAlert('Oops', 'Fail to change balance (404, 505)', 'error');
  });
}

function walletNumberAnimation(now, tween) {
    var floored_number = now / Math.pow(10, 2);
    var  target = $(tween.elem);

    //floored_number = floored_number.toFixed();
    //floored_number = floored_number.toString().replace('.', ',');

  target.text(KRformatNumber(floored_number, (floored_number > 1 || floored_number < -1 ? 2 : 5)));
}

function _updateBalanceData(){
  $.get($('body').attr('hrefapp') + '/app/modules/kr-trade/src/actions/dataBalance.php').done(function(data){
    let jsonRes = jQuery.parseJSON(data);
    if(jsonRes.error == 1){
      showAlert('Oops', jsonRes.msg, 'error');
    } else {

      if(jsonRes.type == "native"){
        $('.kr-wallet-top-resum > h3').html(jsonRes.current_balance.title);
        $('.kr-wallet-top-resum').find('[kr-wallet-resum-profit]').attr('class', jsonRes.current_balance.profit_class);
        $.each(jsonRes.current_balance, function(k, v){
          if($('[kr-wallet-resum="' + k + '"]').length == 0) return true;
          let actualValue = parseFloat(KRunformatNumber($('[kr-wallet-resum="' + k + '"]').html())) * 100;
          $('[kr-wallet-resum="' + k + '"]').prop('number', actualValue)
              .animateNumber(
                {
                  number: v,
                  numberStep: walletNumberAnimation
                },
                1000
              );
        });

        $.each(jsonRes.balance, function(k, v){
          if($('[kr-balance-id="' + v.enc_id + '"]').length == 0) return true;
          let actualBalance = parseFloat(KRunformatNumber($('[kr-balance-id="' + v.enc_id + '"]').find('i').html())) * 100;
          $('[kr-balance-id="' + v.enc_id + '"]').find('i')
              .prop('number', actualBalance)
              .animateNumber(
                {
                  number: v.balance,
                  numberStep: walletNumberAnimation
                },
                1000
              );
        });
      } else if(jsonRes.type == "external"){

        $('.kr-wallet-top-thirdparty > div > span:first-child').html(jsonRes.exchange_title);

        let actualValue = parseFloat(KRunformatNumber($('.kr-wallet-top-thirdparty > div > span:last-child > i:first-child').html())) * 100;
        $('.kr-wallet-top-thirdparty > div > span:last-child > i:first-child').prop('number', actualValue)
          .animateNumber(
            {
              number: jsonRes.first_balance * 100,
              numberStep: walletNumberAnimation
            },
            1000
          );

        $('.kr-wallet-top-thirdparty > div > span:last-child > i:last-child').html(jsonRes.first_balance_symbol);

        $.each(jsonRes.balances, function(k, v){
          if($('.kr-wallet-top-resum > ul > li[kr-wallet-exchange="' + jsonRes.exchange_name + '"][kr-wallet-symbol="' + v.symbol + '"]').length > 0){
            let balanceWalletItem = $('.kr-wallet-top-resum > ul > li[kr-wallet-exchange="' + jsonRes.exchange_name + '"][kr-wallet-symbol="' + v.symbol + '"]');
            let currentBalance = balanceWalletItem.find('span:last-child > i:first-child');
            currentBalanceValue = parseFloat(KRunformatNumber(currentBalance.html())) * 100;
            currentBalance.prop('number', currentBalanceValue)
              .animateNumber(
                {
                  number: v.amount * 100,
                  numberStep: walletNumberAnimation
                },
                1000
              );
            balanceWalletItem.find('span:last-child > i:last-child').html(v.symbol);
            balanceWalletItem.find('span:first-child').html(v.symbol);
          } else {
            $('.kr-wallet-top-resum > ul').append('<li kr-wallet-exchange="' + jsonRes.exchange_name + '" kr-wallet-symbol="' + v.symbol + '">' +
              '<span>' + v.symbol + '</span>' +
              '<div></div>' +
              '<span><i>' + KRformatNumber(v.amount, (v.amount > 10 ? 2 : 5))  + '</i> <i>' + v.symbol + '</i></span>' +
            '</li>')
          }
        });

        if(jsonRes.show_more){
          $('.kr-wallet-balance-show-list').show();
        } else {
          $('.kr-wallet-balance-show-list').hide();
        }

      }

    }
  }).fail(function(){
    showAlert('Oops', 'Fail to reload balance', 'error');
  })
}

let checkCoinGateTimeout = null;
function _checkCoinGatePayment(t){
  clearTimeout(checkCoinGateTimeout); checkCoinGateTimeout = null;
  $.get($('body').attr('hrefapp') + '/app/modules/kr-payment/src/actions/deposit/checkCoingate.php', {t:t}).done(function(data){
    let response = jQuery.parseJSON(data);
    if(response.error == 0){
      if(response.status != 0){
        _closeCreditForm();
        if(response.status == 1){
          _updateBalanceData();
        }
      } else {
        checkCoinGateTimeout = setTimeout(function(){
          _checkCoinGatePayment(t);
        }, 500);
      }
    } else {
      showAlert('Oops', response.msg, 'error');
    }

  }).fail(function(){
    showAlert('Oops', 'Fail to check coingate payment', 'error');
  });
}
