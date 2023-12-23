let graphListView = [];

window.onload = function() {

  checkWatchingListSymbol();

  // Subscribe to data coin update
  subscribeStreamerCallback(function(dataCoin) {
    updateWatchCoinItem(dataCoin, dataCoin.FROMSYMBOL);
  });

};

/**
 * Add coin value
 * @param {Float} value   Coin value
 * @param {String} symbol Coin symbol
 */
function addCoinValue(value, symbol) {

  let chart = graphListView[symbol];

  // Remove first graph element
  chart.data.labels.shift();
  chart.data.datasets.forEach((dataset) => {
    dataset.data.shift();
  });

  // Add new value
  chart.data.labels.push(value);
  chart.data.datasets.forEach((dataset) => {
    dataset.data.push(value);
  });

  // Update graph
  chart.update();
}

/**
 * Load wathcing list item
 */
function checkWatchingListSymbol() {
  $.get($('body').attr('hrefapp') + '/app/modules/kr-watchinglist/src/actions/getWatchingListSymbol.php').done(function(data) {

    // Parse respond from JSON
    let respond = jQuery.parseJSON(data);

    // Check error
    if (respond.error == 0) {
      $.each(jQuery.parseJSON(respond.symbols), function(k, v) {
        setTimeout(function() {
          // Add wathcing list item
          addWatchingListItem(v, respond.currency);
        }, 1);
      });
    } else {
      showAlert('Oops', respond.msg, 'error');
    }
  }).fail(function(){
    showAlert('Ooops', 'Fail to get watching list symbol', 'error');
  });
}

let chartUpdateCoin = null;

/**
 * Update watching list item
 * @param  {Array} data    Coin data
 * @param  {String} symbol Coin symbol
 */
function updateWatchCoinItem(data, symbol) {

  // Get symbolContent
  if(symbol.length > 15) return false;
  let symbolContent = $('.kr-wtchl-item[symbol="' + symbol + '"]');

  // Check currency
  if (symbolContent.attr('currency') != data.TOSYMBOL) return false;

  // Update chart value
  if (chartUpdateCoin == null) {
    chartUpdateCoin = setTimeout(function() {
      addCoinValue(data.PRICE, symbol);
      chartUpdateCoin = null;
    }, 60000);
  }

  // Update change 24h percentage
  if (parseFloat(data.CHANGE24HOURPCT) < 0) { // Change color negativ / positiv
    symbolContent.addClass('kr-wtchl-neg');
  } else {
    symbolContent.removeClass('kr-wtchl-neg');
  }

  // Update data coin
  $.each(data, function(k, v) {
    if (v == "NaN") return false;
    if (symbolContent.find('[kr-data="' + k + '"]').length > 0) {
      if (k == "PRICE") v = $.number(v, 2, ',', ' ');
      if (k == "CHANGE24HOURPCT") v = v + '%';
      symbolContent.find('[kr-data="' + k + '"]').html(v);
    }
  });
}

/**
 * Toggle watching list item
 * @param  {String} symbol   Item symbol (ex : BTC)
 * @param  {String} currency Item currency (ex : USD)
 */
function toggleWatchingList(symbol, currency) {
  // If watching list item found = remvoe
  if ($('.kr-wtchl-item[symbol="' + symbol + '"]').length > 0) removeWatchingListItem(symbol);
  else addWatchingListItem(symbol, currency, "add"); // Else add watching list item
}

/**
 * Add watching list item
 * @param {String} symbol        Item symbol (ex : BTC)
 * @param {String} currency      Item currency (ex : USD)
 * @param {String} [type="load"] Type add
 */
function addWatchingListItem(symbol, currency, type = "load") {
  $('.kr-dash-pan-cry-select-lst-tdn[symbol="' + symbol + '"]').addClass('watching-list-present');

  // Get watching list item data
  $.post($('body').attr('hrefapp') + '/app/modules/kr-watchinglist/src/actions/getWatchingItem.php', {
    symb: symbol,
    t: type
  }).done(function(data) {

    // Try to parse respond to json = success = error
    try {
      let respond = jQuery.parseJSON(data);
      if (respond.error == 1) showAlert('Oops', respond.msg, 'error');
    } catch (e) {
      let elemWatching = $(data);
      elemWatching.click(function() {

        // Change graph
        $('.kr-leftside').removeClass('kr-leftside-resp-on');

        // Data coin
        let coin = {
          'symbol': $(this).attr('symbol'),
          'name': $(this).find('.kr-wtchl-inf-nm').find('label').html(),
          'icon': $(this).find('.kr-wtchl-inf-pic').html(),
          'currency': $(this).attr('currency')
        }

        if($('.kr-dash-chart-n').length > 0){
          // Change graph & att top list item
          if ($('.kr-top-graphlist-item[symbol="' + coin.symbol + '"][currency="' + $(this).attr('currency') + '"]').length > 0) {
            $('.kr-top-graphlist-item[symbol="' + coin.symbol + '"][currency="' + $(this).attr('currency') + '"]').trigger('click');
          } else {
            addGraphDashboard(coin, $(this).attr('currency'));
          }
        } else {
          changeView('coin', 'coin', {symbol:coin.symbol});
        }



      });
      $.when($('.kr-wtchl').find('ul.kr-wtchl-lst').append(elemWatching)).then(function() {
        // Load graph item
        loadGraphWatchingItem(symbol);
      });
    }
  }).fail(function(){
    showAlert('Ooops', 'Fail to load watching item', 'error');
  });

  // Add subscribtion to item symbol
  addSubscribtion(symbol, currency);
}

/**
 * Remove wathcing item
 * @param  {String} symbol Item symbol (ex : BTC)
 */
function removeWatchingListItem(symbol) {
  $('.kr-wtchl-item[symbol="' + symbol + '"]').remove();
  $('.kr-dash-pan-cry-select-lst-tdn[symbol="' + symbol + '"]').removeClass('watching-list-present');

  // Remove item in DB
  $.post($('body').attr('hrefapp') + '/app/modules/kr-watchinglist/src/actions/removeWatchingListItem.php', {
    symb: symbol
  }).done(function(data) {

    // Parse result
    let respond = jQuery.parseJSON(data);
    if (respond.error == 1) showAlert('Oops', respond.msg, 'error');
  });
}

/**
 * Load small graph watching item
 * @param  {String} symbol Item symbol (ex : BTC)
 */
function loadGraphWatchingItem(symbol) {

  // Get item object
  $('.kr-wtchl-data-grph[symbol="' + symbol + '"]').each(function() {

    // Get graph container (canvas)
    let graphPicture = $(this).find('canvas')[0].getContext('2d');
    let yVal = $(this).attr('yv').split(',');
    let xVal = $(this).attr('xv').split(',');

    // Init Chart library to canvas element
    graphListView[$(this).attr('symbol')] = new Chart(graphPicture, {
      type: 'line',
      data: {
        labels: xVal,
        datasets: [{
          data: yVal,
          fill: false,
          borderColor: 'rgba(255, 255, 255, 0.5)',
          borderWidth: 1
        }]
      },
      scaleShowLabels: false,
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
            radius: 0
          }
        },
        scales: {
          yAxes: [{
            gridLines: {
              display: false
            },
            display: false
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

  });
}
