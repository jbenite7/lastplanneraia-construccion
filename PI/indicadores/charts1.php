<html>
  <head>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script type="text/javascript">
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawVisualization);	
      
    var jsonData = $.ajax({
                    url: "listar_indicadores.php",
                    dataType: "json",
                    async: false
                }).responseText;
       console.log(jsonData); 
      function drawVisualization() {
        // Some raw data (not necessarily accurate)
    
          
        var data = new google.visualization.DataTable(jsonData);

        var options = {
          title : 'Seguimiento Porcentaje de Actividades Completadas',
          vAxis: {title: 'Calificación Semanal' ,format:'#%'},
          hAxis: {title: 'Semanas'},
          seriesType: 'bars',
          series: {3: {type: 'line', color: "red", lineDashStyle: [10, 2]} , 1: {type: 'line', color: "blue", lineDashStyle: [10, 2]}, 0: {color: "rgb(55,86,54)", labelInLegend: true}, 2: {color: "rgb(191,215,48)", labelInLegend: true}}      
        
        };
          

        // Create a formatter.
        // This example uses object literal notation to define the options.
        var formatter = new google.visualization.DateFormat({formatType: 'long'});

        // Reformat our data.
        formatter.format(data, 1);
          
        var chart = new google.visualization.ComboChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
  </head>
  <body>
    <div id="chart_div" style="width: 1300px; height: 500px;"></div>
  </body>
</html>