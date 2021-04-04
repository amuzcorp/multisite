jQuery(document).ready(function($){
  $('.cart_all').click(function(){
    var target_class = $(this).attr('data-target');
    $('input:checkbox.' + target_class).not(this).prop('checked', this.checked);
  });

  $('.sort-list--custom-item li a').click(function(){
    $('.sort-list--custom-item li.active').removeClass('active');
    $(this).parents('li').addClass('active');
  });
});


function updateSiteSetting(frm){
  var params = $(frm).serialize();

  XE.ajax({
    type: 'post',
    dataType: 'json',
    data: params,
    url: $(frm).attr('action'),
    success: function(response) {
      XE.toast(response.alert_type, response.message);
    },
    error: function(response) {
      var type = 'xe-danger';
      var errorMessage = response.message;
      XE.toast(type, errorMessage);
    }
  });

  return false;
}
