/**
 * Init admin interface
 */
function initAdmin(){

  // Enable admin navigation menu
  $('.kr-admin-nav').find('li').off('click').click(function(){
    changeView($(this).attr('kr-module'), $(this).attr('kr-view'));
  });

  // Enable coin list pagination
  $('.kr-admin-pagination-coins').find('li').off('click').click(function(){
    changeView('admin', 'coins', {page:$(this).attr('kr-page')});
  });

  // Enable post with JS
  $('.kr-adm-post-evs').off('submit').submit(function(e){

    // Post form
    $.post($(this).attr('action'), $(this).serialize()).done(function(data){

      // Decode result in JSON
      let resp = jQuery.parseJSON(data);

      // Check if result was an error
      if(resp.error == 1) showAlert('Oops', resp.msg, 'error');
      else showAlert(resp.title, resp.msg);

      // Reload view
      changeView('admin', $('.kr-admin-nav-selected').attr('kr-view'));

    }).fail(function(){ // If fail to post (505, 404), show error message
      showAlert('Oops', 'Error : Fail to save (check php error log)', 'error');
    });

    e.preventDefault();
    return false;
  });

  $('.kr-admin-tggle-coin-status').off('submit').submit(function(e){

    let cs = $(this).parent().parent().find('.kr-admin-lst-c-status').html();
    $(this).parent().parent().find('.kr-admin-lst-c-status').html($(this).find('input[type="submit"]').attr('alt-st'));
    $(this).parent().parent().find('.kr-admin-lst-c-status').toggleClass('kr-admin-lst-tag-red');
    $(this).find('input[type="submit"]').attr('alt-st', cs);

    let css = $(this).find('input[type="submit"]').val();
    $(this).find('input[type="submit"]').val($(this).find('input[type="submit"]').attr('alt'));
    $(this).find('input[type="submit"]').attr('alt', css);

    $.post($(this).attr('action'), $(this).serialize()).done(function(data){
      let resp = jQuery.parseJSON(data);
      if(resp.error == 1){
        showAlert('Oops', resp.msg, 'error');
      }
    });
    e.preventDefault();
    return false;
  });



  $('.btn-adm-user-c').off('click').click(function(e){
    showAccountView({adm_acc_user:$(this).attr('idu')});
    e.preventDefault();
    return false;
  });

}
