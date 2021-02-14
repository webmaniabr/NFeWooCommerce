jQuery(document).ready(function($){
  $('.single').click(function(e){
    var target = $(e.target);
    var block_classes = ['wrt', 'update-nfe', 'danfe-icon'];
    var toggle = true;

    block_classes.forEach(function(value, index){

      if(target.hasClass(value)){
        toggle = false;
      }
    });

    if(toggle){
      var rotate = $(this).find('.extra').css('display');


      if(rotate == 'none'){
        $(this).find('.expand-nfe').css('transform', 'rotate(180deg)').css('-webkit-transform', 'rotate(180deg)');
      }else{
        $(this).find('.expand-nfe').css('transform', 'rotate(0deg)').css('-webkit-transform', 'rotate(0deg)');
      }


      $(this).find('.extra').slideToggle('fast');
    }



  });
});
