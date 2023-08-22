$(document).ready(function() {
  $('[data-toggle="tooltip"]').tooltip({
    container : 'body'
  });
  if (typeof $('').select2 == 'function') {
    $('select:not(#nb,.input-sm,.sonata-filter-option,.not-select2)').select2();
  }
});