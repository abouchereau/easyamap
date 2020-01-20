$(document).ready(function() {
  app.init.checkAllHorizontal();
  app.init.checkAllVertical();
  app.init.checkAll();
  app.init.checkChanges();  
  app.init.checkSave();
  app.init.checkShift();
  $('.btn-prodis').click(function() {
      app.editForm($(this).data('id'), $(this).data('prodis'));
  });
});

window.onbeforeunload = function () {
  if (app.has_unsaved_changes)
    return "Des modifications n'ont pas été enregistrées.";
};

var app = {
  has_unsaved_changes: false,
  init: {
    checkAll: function() 
    {
      $('.check-all').click(function (e) {
        var all_checked = true;
        $(this).parent().parent().find('input[type=checkbox]').each(function() {
          if(!$(this).attr('disabled') && !$(this).is(':checked'))
          {
            all_checked = false;
            return false;
          }
        });
        $(this).parent().parent().find('input[type=checkbox]').each(function() {
          if(!$(this).attr('disabled'))
          {
            $(this).prop('checked',!all_checked);
          }
        });
        $(this).parent().parent().find('.save-button').removeAttr("disabled");
        app.has_unsaved_changes = true;
      });
    },
    checkAllHorizontal: function ()
    {
      //Cocher / décocher toutes la ligne
      $('.check-horizontal').click(function (e) {
        var all_checked = true;
        $(this).parent().find('input[type=checkbox]').each(function() {
          if(!$(this).attr('disabled') && !$(this).is(':checked'))
          {
            all_checked = false;
            return false;
          }
        });
        $(this).parent().find('input[type=checkbox]').each(function() {
          if(!$(this).attr('disabled'))
          {
            $(this).prop('checked',!all_checked);
          }
        });
        $(this).parent().parent().find('.save-button').removeAttr("disabled");
        app.has_unsaved_changes = true;
      });
    },
    checkAllVertical: function ()
    {
      //Cocher / décocher toutes la colonne
      $('.check-vertical').click(function (e) {
        var col_num = $(this).index();
        var all_checked = true;
        $(this).parent().parent().find('td').each(function (){
          if ($(this).index() == col_num )
          {
            var $checkbox = $(this).find('input[type=checkbox]');
            
            if ($checkbox.length > 0 && !$checkbox.attr('disabled') && !$checkbox.is(':checked'))
            {
              all_checked = false;
              return false;
            }
          }
        });
        $(this).parent().parent().find('td').each(function (){
          if ($(this).index() == col_num )
          {
            $(this).find('input[type=checkbox]').each(function() {
                if(!$(this).attr('disabled'))
                {
                  $(this).prop('checked',!all_checked);
                }
              });
          }
        });
        $(this).parent().parent().find('.save-button').removeAttr("disabled");
        app.has_unsaved_changes = true;
      });
    },
    checkChanges: function ()
    {
        $('input[type=checkbox]').change(function () {
          app.has_unsaved_changes = true;
          $(this).parent().parent().parent().find('.save-button').removeAttr("disabled");
          $(this).parent().parent().parent().find('.shift-button').removeAttr("disabled");
        });
    },
    checkSave: function () {
      $('.save-button').click(function () {
        var existing = {};
        var new_ones = {};
        $(this).parent().parent().parent().find('input[type=checkbox]').each(function() {
          if (typeof $(this).data('id_product_distribution') != 'undefined')
            existing[$(this).data('id_product_distribution')] = $(this).prop('checked')?1:0;
          else
            new_ones[$(this).attr('id')] = $(this).prop('checked')?1:0;
        });        
        app.save(existing, new_ones);
      });
    },
    checkShift: function() {
      var selected = [];
      $('.shift-button').click(function () {
        $("#form-report").modal('show'); 
        $(this).parent().parent().parent().find('input[type=checkbox]:checked').each(function() {
            selected.push($(this).data('id_product_distribution'));
        });
      });      
      $('#valid-report').click(function () {
        $("#selected").val(selected.join(","));
        $("#new_id_distribution").val($("#distribution_new").val());
        $("#report_type").val($('input[name="report_type"]:checked').val());
        app.has_unsaved_changes = false;
        $("#hidden-form").submit();
      });
    }
  },
  save: function (existing, new_ones)
  {      
    $('#loading').modal('show');
    $("#existing").val(JSON.stringify(existing));
    $("#new_ones").val(JSON.stringify(new_ones));
    $("#page").val(window.page);
    app.has_unsaved_changes = false;
    $("#hidden-form").submit();
  },
  editForm: function(id, prodis) {
      var temp = id.split('-');
      var product = products[temp[0]];
      var distribution = distributions[temp[1]];
      $("#form-prodis .modal-title").html(distribution + " - "+ product);
      var url = root+'save-prodis';
      var html = '<form action="'+url+'" method="POST">';
      html += '<input type="hidden" name="id_product_distribution" value="'+prodis.id_product_distribution+'" />';
      html += '<div class="form-group"><label>Prix</label><input class="form-control" type="text" name="price" value="'+prodis.price.toFixed(2).replace('.',',')+'" /></div>';
      html += '<div class="form-group"><label>Quantité maximum (laisser 0 si pas de maximum)</label><input class="form-control" type="number" name="max_quantity" value="'+(prodis.max_quantity == null?0:prodis.max_quantity)+'" min="0" step="1" /></div>';
      html += '<div class="form-group"><label>Quantité max/pers. (laisser 0 si pas de maximum)</label><input class="form-control" type="number" name="max_per_user" value="'+(prodis.max_per_user == null?0:prodis.max_per_user)+'" min="0" step="1" /></div>';
      html += '<div class="form-group"><button class="btn btn-primary">Enregistrer</button></div>';
      html += '</form>';
      $("#form-prodis .modal-body").html(html);
      $("#form-prodis").modal('show');      
  }
};