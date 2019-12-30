var chart = Morris.Bar({
  element: 'graph',
  data: dataPoints,
  xkey: 'label',
  ykeys: ['y'],
  labels: ['Y'],
  gridTextSize: 11,
  hoverCallback: function (index, options, content, row) {
	return row.label+": "+ row.indexLabel;
  }
});
/*
var chart = new CanvasJS.Chart("chartContainer",{
      title: {
        text: "Paiements "+dateStr,
        fontSize: 20
        },
      interactivityEnabled: false,
      exportEnabled: true,
      animationEnabled: false,
      theme: "theme1",
      axisY: {
        labelFormatter: function (e) {
            return number_format(e.value, 0, ","," ")+" €";
        }
      },
      legend: {
        verticalAlign: "bottom",
        horizontalAlign: "center"
      },
      data: [
        {        
          legendText: " ",
          type: "column",  
          showInLegend: true, 
          legendMarkerColor: "grey",          
          indexLabel: "{y}",
          indexLabelFontSize: 13,
          dataPoints: dataPoints
        }   
      ]
});
    */
$(document).ready(function () {
    //chart.render();
    initGraph(dataPoints, "Paiements "+dateStr, total);   
    $('select').change(function() {
        ajaxStats();
    });
});

function ajaxStats() {
    var year = $("#sel-year").val();
    var id_user = $("#sel-user").val();
    var user = $("#sel-user option:selected").html();
    var id_farm = $("#sel-farm").val();
    var farm = $("#sel-farm option:selected").html();
    var url = root+'ajaxStats/'+year+'/'+id_user+'/'+id_farm;
    $.ajax({
      url: url,
      dataType: 'json',
      beforeSend: function () 
      {

      },
      success: function(data) 
      {
        var title = "Paiements "+year+" ";
        if (id_user + id_farm > 0)
            title += "(";
        if (id_user > 0)
            title += user.substr(0,user.indexOf(" "));
        if (id_farm > 0)
            title += " > "+farm;
        if (id_user + id_farm > 0)
            title += ")";
        chart.setData(data.graph);
        initGraph(data.graph, title, data.total);
      }
    });
}

function initGraph(data, title, total) {
    $("#title").html(title);
    $("#graphtab td").each(function (i) {
            $(this).html(data[i].indexLabel);
    });
    $("#total").html(number_format(total,2,',',' ')+" €");
}

function number_format(number, decimals, dec_point, thousands_sep) {
  number = (number + '')
    .replace(/[^0-9+\-Ee.]/g, '');
  var n = !isFinite(+number) ? 0 : +number,
    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    s = '',
    toFixedFix = function(n, prec) {
      var k = Math.pow(10, prec);
      return '' + (Math.round(n * k) / k)
        .toFixed(prec);
    };
  // Fix for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec) : '' + Math.round(n))
    .split('.');
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
  }
  if ((s[1] || '')
    .length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1)
      .join('0');
  }
  return s.join(dec);
}