<?php

require '../wgm_config.php';


?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">

  <title>Skylaski VPN Usage</title>



</head>

<body>

  <!-- Page Content -->
  <div class="container">
    <div class="row">
      <div class="col-lg-12 text-center">
		  
        <h1 class="mt-5">Skylaski VPN Usage</h1>
        <?php        
			
			$wgm_db = pg_connect( "$db_host $db_port $db_name $db_credentials"  );
			if(!$wgm_db) {
				echo "Error : Unable to open database\n";
			} else {
				echo "<p>Opened database successfully</p>";
			}
			
			$get_max_hour_sql = "SELECT MAX(date_trunc('hour',created_at)) FROM usage";
			$get_max_hour_ret = pg_query($wgm_db,$get_max_hour_sql);
			$max_hour = pg_fetch_assoc($get_max_hour_ret);
			
			$get_cur_total_peers_sql = "SELECT SUM(peers) as total_peers FROM usage WHERE date_trunc('hour',created_at)='".pg_escape_string($max_hour['max'])."'";
			$get_cur_total_peers_ret = pg_query($wgm_db,$get_cur_total_peers_sql);
			$cur_total_peers = pg_fetch_assoc($get_cur_total_peers_ret);
			
			$get_cur_total_connected_sql = "SELECT SUM(still_connected) as total_connected FROM usage WHERE date_trunc('hour',created_at)='".pg_escape_string($max_hour['max'])."'";
			$get_cur_total_connected_ret = pg_query($wgm_db,$get_cur_total_connected_sql);
			$cur_total_connected = pg_fetch_assoc($get_cur_total_connected_ret);
			
			$get_cur_total_has_connected_sql = "SELECT SUM(has_connected) as total_has_connected FROM usage WHERE date_trunc('hour',created_at)='".pg_escape_string($max_hour['max'])."'";
			$get_cur_total_has_connected_ret = pg_query($wgm_db,$get_cur_total_has_connected_sql);
			$cur_total_has_connected = pg_fetch_assoc($get_cur_total_has_connected_ret);
			
			
		?>
        <hr>
        <p class="lead"><u>Last Updated: <?php echo $max_hour['max']; ?></u></p>
        
        <div align="left" style="float:left; z-index:0;">
        <ul class="list-unstyled">
			<li>Current Total Peers: <h2><?php echo $cur_total_peers['total_peers']; ?></h2></li>
          <li>Current Total Connected: <h2><?php echo $cur_total_connected['total_connected']; ?></h2> (<?php echo round(($cur_total_connected['total_connected'] / $cur_total_peers['total_peers']) * 100,2); ?>%)</li>
          <li>Has Connected: <h2><?php echo $cur_total_has_connected['total_has_connected'] ?></h2> (<?php echo round(($cur_total_has_connected['total_has_connected'] / $cur_total_peers['total_peers'])*100,2) ?>%)</li>
          <li>Avg Sustained per Hour: <h2>
          AVG
          </h2></li>
          
          
        </ul>
        
        </div>
        <div style="float:right; width:800px; height=800px;">
			<h3>Current Usage Map</h3>
			<div id="regions_div"></div>
        </div>
		<div style="float:right; width:800px; height=800px;">
			<h3>Usage by Hour</h3>
			<div id="usage_chart"></div>
        </div>
     
      </div>


    </div>



  </div>

<div class="container">


</div>

  <!-- GOOGLE MAP SCRIPT -->
	<!-- <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script> -->
	<script type="text/javascript" src="/js/jquery.min.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {
        'packages':['geochart','line'],
        // Note: you will need to get a mapsApiKey for your project.
        // See: https://developers.google.com/chart/interactive/docs/basic_load_libs#load-settings
        'mapsApiKey': 'AIzaSyAREAR4F1w6sqedGksqUT3ePpYzimEtbDI'
      });
      google.charts.setOnLoadCallback(drawCharts);
 
      function drawRegionsMap() {
        
		var jsonData = $.ajax({
          url: "get_data.php?chart=curusagemap&cur_date=<?php echo urlencode($max_hour['max']);?>",
          dataType: "json",
          async: false
          }).responseText;
        
        var data = new google.visualization.DataTable(jsonData);
        
        var options = {
			sizeAxis: { minValue: 1, maxValue: 20 },			
			displayMode: 'markers',
			colorAxis: {colors: ['#aa0000', '#aa0000']},
			legend: 'none'
        };

        var chart = new google.visualization.GeoChart(document.getElementById('regions_div'));
        
        chart.draw(data, options);
      }
      
      function drawUsageChart() {

			var jsonData = $.ajax({
			  url: "get_data.php?chart=usagehistory&cur_date=<?php echo urlencode($max_hour['max']);?>",
			  dataType: "json",
			  async: false
			  }).responseText;
			var usage_data = new google.visualization.DataTable(jsonData);
			  
			var options = {
			  title: 'Total Usage History',
			  legend: { position: 'none' },
			  colors:['#aa0000'],

			};

			var chart = new google.charts.Line(document.getElementById('usage_chart'));

			chart.draw(usage_data, options);
		}

	function drawCharts(){
		drawRegionsMap();
		drawUsageChart();
	}
	
	$(window).resize(function(){
		drawCharts();
	});    
</script>
<!-- END MAP SCRIPT -->


</body>

</html>
