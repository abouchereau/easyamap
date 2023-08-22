$(document).ready(function() {
  $('input[type=text], input[type=email],input[type=url], textarea, select').addClass('form-control');
  $('form button[type=submit]').addClass('btn').addClass('btn-success').addClass('btn-lg').css({'display':'block','width':'100%'}).html('<span class="glyphicon glyphicon-save"></span> Enregistrer');
  //$('.alert-success').delay(2000).fadeOut(800);
  $('select[multiple=multiple]').height('250px');
  $('<div class="text-muted"><small>Maintenir CTRL appuyé pour sélectionner plusieurs éléments</small></div>').insertBefore('select[multiple=multiple]');
}); 