jQuery(document).ready(function($){
  $('.cart_all').click(function(){
    var target_class = $(this).attr('data-target');
    $('input:checkbox.' + target_class).not(this).prop('checked', this.checked);
  });
});
