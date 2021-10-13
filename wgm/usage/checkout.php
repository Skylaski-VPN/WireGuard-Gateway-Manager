<?php

require '../wgm_config.php';

// Configuration for connecting to PostgreSQL Database
$db2_name = "dbname = skylaski_pos";

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
		  
        <h1 class="mt-5">Skylaski Checkout(POS) Summary</h1>
        <?php        
			
			$wgm_db = pg_connect( "$db_host $db_port $db_name $db_credentials"  );
			if(!$wgm_db) {
				echo "Error : Unable to open database\n";
			} else {
				echo "<p>Opened database successfully</p>";
			}
			
			$pos_db = pg_connect ("$db_host $db_port $db2_name $db_credentials" );
			if(!$pos_db) {
				echo "Error : Unable to open database\n";
			} else {
				echo "<p>Opened database successfully</p>";
			}
			
			$get_max_hour_sql = "SELECT MAX(date_trunc('hour',created_at)) FROM usage";
			$get_max_hour_ret = pg_query($wgm_db,$get_max_hour_sql);
			$max_hour = pg_fetch_assoc($get_max_hour_ret);
			
			$get_cur_total_sql = "SELECT COUNT(id) as count, SUM(subtotal) as sum FROM active_plans WHERE updated_at >= date_trunc('month', CURRENT_DATE);";
			$get_cur_total_ret = pg_query($pos_db,$get_cur_total_sql);
			$cur_total = pg_fetch_assoc($get_cur_total_ret);
			
			$get_cur_total_last_sql = "SELECT COUNT(id) as count, SUM(subtotal) as sum FROM active_plans WHERE updated_at >= date_trunc('month', CURRENT_DATE - interval '1' month);";
			$get_cur_total_last_ret = pg_query($pos_db,$get_cur_total_last_sql);
			$cur_total_last = pg_fetch_assoc($get_cur_total_last_ret);
			
			$get_new_signins_sql = "SELECT COUNT(id) as count FROM users WHERE updated_at >= date_trunc('month',CURRENT_DATE)";
			$get_new_signins_ret = pg_query($wgm_db,$get_new_signins_sql);
			$new_signins = pg_fetch_assoc($get_new_signins_ret);
			
			$get_new_signins_last_sql = "SELECT COUNT(id) as count FROM users WHERE updated_at >= date_trunc('month',CURRENT_DATE - interval '1' month)";
			$get_new_signins_last_ret = pg_query($wgm_db,$get_new_signins_last_sql);
			$new_signins_last = pg_fetch_assoc($get_new_signins_last_ret);
			
			$get_unfinished_checkouts_sql = "SELECT COUNT(id) as count FROM checkouts WHERE status='new' AND updated_at >= date_trunc('month',CURRENT_DATE)";
			$get_unfinished_checkouts_ret = pg_query($pos_db,$get_unfinished_checkouts_sql);
			$unfinished_checkouts = pg_fetch_assoc($get_unfinished_checkouts_ret);
			
			$get_unfinished_checkouts_last_sql = "SELECT COUNT(id) as count FROM checkouts WHERE status='new' AND updated_at >= date_trunc('month',CURRENT_DATE - interval '1' month)";
			$get_unfinished_checkouts_last_ret = pg_query($pos_db,$get_unfinished_checkouts_last_sql);
			$unfinished_checkouts_last = pg_fetch_assoc($get_unfinished_checkouts_last_ret);
			
			
		?>
        <hr>
        <p class="lead"><u>Last Updated: <?php echo $max_hour['max']; ?></u></p>
        
        <div align="left" style="float:left; z-index:0;">
        <ul class="list-unstyled">
			<li>Current Month Total: <h2><i>(<?php echo $cur_total['count']; ?>)</i> $<?php echo $cur_total['sum']; ?></h2></li>
          <li>Last Month's Total: <h2>(<?php echo $cur_total_last['count']; ?>)</i> $<?php echo $cur_total_last['sum']; ?></h2></li>
          <li>New Sign-In's This Month: <h2><?php echo $new_signins['count']; ?></h2></li>
          <li>New Sign-In's Last Month: <h2><?php echo $new_signins_last['count']; ?></h2></li>
          <li>Unfinished Checkouts This Month: <h2><?php echo $unfinished_checkouts['count']; ?></h2></li>
          <li>Unfinished Checkouts Last Month Month: <h2><?php echo $unfinished_checkouts_last['count']; ?></h2></li>
          
          
        </ul>
        
        </div>
        
		<div style="float:right; width:800px; height=800px;">
			<h3>Total MoM</h3>
			<div id="totalmom_chart"></div>
        </div>
        <div style="float:right; width:800px; height=800px;">
			<h3>Total Count MoM</h3>
			<div id="totalcountmom_chart"></div>
        </div>
        <div style="float:right; width:800px; height=800px;">
			<h3>New Sign-Ins MoM</h3>
			<div id="newsigninsmom_chart"></div>
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
            
      function drawTotalMoMChart() {

			var jsonData = $.ajax({
			  url: "get_data.php?chart=totalmom",
			  dataType: "json",
			  async: false
			  }).responseText;
			var totalmom_data = new google.visualization.DataTable(jsonData);
			  
			var options = {
			  title: 'Total MoM',
			  legend: { position: 'none' },
			  colors:['#aa0000'],

			};

			var chart = new google.charts.Line(document.getElementById('totalmom_chart'));

			chart.draw(totalmom_data, options);
		}
		
		function drawTotalCountMoMChart() {

			var jsonData = $.ajax({
			  url: "get_data.php?chart=totalcountmom",
			  dataType: "json",
			  async: false
			  }).responseText;
			var totalcountmom_data = new google.visualization.DataTable(jsonData);
			  
			var options = {
			  title: 'Total Count MoM',
			  legend: { position: 'none' },
			  colors:['#aa0000'],

			};

			var chart = new google.charts.Line(document.getElementById('totalcountmom_chart'));

			chart.draw(totalcountmom_data, options);
		}
		
		function drawNewSignInsMoMChart() {

			var jsonData = $.ajax({
			  url: "get_data.php?chart=newsigninsmom",
			  dataType: "json",
			  async: false
			  }).responseText;
			var newsigninsmom_data = new google.visualization.DataTable(jsonData);
			  
			var options = {
			  title: 'New Sign-Ins MoM',
			  legend: { position: 'none' },
			  colors:['#aa0000'],
			  pointSize: 10,

			};

			var chart = new google.charts.Line(document.getElementById('newsigninsmom_chart'));

			chart.draw(newsigninsmom_data, options);
		}

	function drawCharts(){
		drawTotalMoMChart();
		drawTotalCountMoMChart();
		drawNewSignInsMoMChart();
	}
	
	$(window).resize(function(){
		drawCharts();
	});    
</script>
<!-- END MAP SCRIPT -->


</body>

</html>
