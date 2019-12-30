$(document).ready(function() {
  $('[data-toggle="tooltip"]').tooltip({
    container : 'body'
  });
  if (typeof $('').select2 == 'function') {
    $('select:not(#nb,.input-sm,.sonata-filter-option)').select2();
  }
});