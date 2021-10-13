<?php

require '../../wgm/wgm_config.php';


$cur_date = urldecode($_GET['cur_date']);
//error_log("Received Hour: ".$cur_date);
$chart = urldecode($_GET['chart']);
//error_log("Chart: ".$chart);

// open db
$wgm_db = pg_connect( "$db_host $db_port $db_name $db_credentials" );
if(!$wgm_db){
	$returnArray['message']='Encountered an Error';
	error_log("Encountered an Error");
	echo json_encode($returnArray);
	//http_response_code(400);
	exit();
}
$pos_db = pg_connect( "$db_host $db_port dbname = skylaski_pos $db_credentials" );
if(!$pos_db){
	$returnArray['message']='Encountered an Error';
	error_log("Encountered an Error");
	echo json_encode($returnArray);
	//http_response_code(400);
	exit();
}

function formatDate($date_string) {
	// Set an expiration date on the ovpn plan
	$dt = new DateTime($date_string);
	$last_updated_year = $dt->format("Y");
	$last_updated_month = $dt->format("m");
	$last_updated_day = $dt->format("d");
	$last_updated_hour = $dt->format("G:00");
	//echo "<br>";
	//echo $last_updated_month." ".$last_updated_day.", ".$last_updated_year." at ".$last_updated_hour;
	return $last_updated_month."/".$last_updated_day."@".$last_updated_hour;
}

switch($chart){
	case 'newsigninsmom':
		$get_newsigninsmom_sql = "SELECT to_char(date_trunc('month',updated_at), 'YYYY-MM-dd') as month,count(id) as count FROM users GROUP BY month ORDER BY month ASC";
		$get_newsigninsmom_ret = pg_query($wgm_db,$get_newsigninsmom_sql);
		
		$data = array ( 'cols' => array( array('id' => '', 'label' => 'month', 'pattern' => '', 'type' => 'string'), array('id' => '', 'label' => 'count', 'pattern' => '', 'type' => 'number') ), 'rows' => [] );
		
		while($row = pg_fetch_assoc($get_newsigninsmom_ret)){
			
			$month = $row['month'];
			$count = $row['count'];
			//error_log("Month: $month, SUM: $sum");
			
			$rowArray = array( 'c' => [] );
			$monthArray = array( 'v' => $month, 'f' => null);
			$countArray = array( 'v' => $count, 'f' => null);
			
			array_push($rowArray['c'], $monthArray);
			array_push($rowArray['c'], $countArray);
			array_push($data['rows'], $rowArray);
		}
		
		$json = json_encode($data);
		
		header('Content-type: application/json');
		print $json;
		
		pg_close($pos_db);
		pg_close($wgm_db);
	
		//exit();
		break;
		
	case 'totalmom':
		$get_totalmom_sql = "SELECT to_char(date_trunc('month',updated_at), 'YYYY-MM-dd') as month,sum(subtotal) as sum FROM active_plans GROUP BY month ORDER BY month ASC";
		$get_totalmom_ret = pg_query($pos_db,$get_totalmom_sql);
		
		$data = array ( 'cols' => array( array('id' => '', 'label' => 'month', 'pattern' => '', 'type' => 'string'), array('id' => '', 'label' => 'sum', 'pattern' => '', 'type' => 'number') ), 'rows' => [] );
		
		while($row = pg_fetch_assoc($get_totalmom_ret)){
			
			$month = $row['month'];
			$sum = $row['sum'];
			$fSum = "$".$sum;
			//error_log("Month: $month, SUM: $sum");
			
			$rowArray = array( 'c' => [] );
			$monthArray = array( 'v' => $month, 'f' => null);
			$sumArray = array( 'v' => $sum, 'f' => $fSum);
			
			array_push($rowArray['c'], $monthArray);
			array_push($rowArray['c'], $sumArray);
			array_push($data['rows'], $rowArray);
		}
		
		$json = json_encode($data);
		
		header('Content-type: application/json');
		print $json;
		
		pg_close($pos_db);
		pg_close($wgm_db);
	
		//exit();
		break;
		
	case 'totalcountmom':
		$get_totalcountmom_sql = "SELECT to_char(date_trunc('month',updated_at), 'YYYY-MM-dd') as month,count(id) as count FROM active_plans GROUP BY month ORDER BY month ASC";
		$get_totalcountmom_ret = pg_query($pos_db,$get_totalcountmom_sql);
		
		$data = array ( 'cols' => array( array('id' => '', 'label' => 'month', 'pattern' => '', 'type' => 'string'), array('id' => '', 'label' => 'count', 'pattern' => '', 'type' => 'number') ), 'rows' => [] );
		
		while($row = pg_fetch_assoc($get_totalcountmom_ret)){
			
			$month = $row['month'];
			$count = $row['count'];
			//error_log("Month: $month, SUM: $sum");
			
			$rowArray = array( 'c' => [] );
			$monthArray = array( 'v' => $month, 'f' => null);
			$countArray = array( 'v' => $count, 'f' => null);
			
			array_push($rowArray['c'], $monthArray);
			array_push($rowArray['c'], $countArray);
			array_push($data['rows'], $rowArray);
		}
		
		$json = json_encode($data);
		
		header('Content-type: application/json');
		print $json;
		
		pg_close($pos_db);
		pg_close($wgm_db);
	
		//exit();
		break;
		
	case 'curusagemap':
		$get_locations_cur_connected_sql = "SELECT location,SUM(still_connected) as still_connected FROM usage WHERE date_trunc('hour',created_at)='".pg_escape_string($cur_date)."' AND still_connected > 0 GROUP BY location";
		//error_log($get_locations_cur_connected_sql);
		$get_locations_cur_connected_ret = pg_query($wgm_db,$get_locations_cur_connected_sql);


		$locations = array( 'cols' => array( array('id' => '', 'label' => 'location', 'pattern' => '', 'type' => 'string'), array('id' => '', 'label' => 'count', 'pattern' => '', 'type' => 'number') ), 'rows' => [] );
		//echo "location	count<br>";

		while($row = pg_fetch_assoc($get_locations_cur_connected_ret)){
					
					$location_code = $row['location'];
					$count = $row['still_connected'];
					
					$rowArray = array( 'c' => [] );
					$locationArray = array( 'v' => $location_code, 'f' => null);
					$countArray = array( 'v' => $count, 'f' => null);
					
					array_push($rowArray['c'], $locationArray);
					array_push($rowArray['c'], $countArray);
					array_push($locations['rows'], $rowArray);
					
		}


		$json = json_encode($locations);

		header('Content-type: application/json');
		print $json;

		pg_close($pos_db);
		pg_close($wgm_db);
		
		exit();
		break;
		
	case 'usagehistory':
	
		$get_usage_history_sql = "SELECT date_trunc('hour',created_at) as timestamp,SUM(still_connected) as count FROM usage GROUP BY date_trunc('hour',created_at) ORDER BY date_trunc('hour',created_at) ASC";
		$get_usage_history_ret = pg_query($wgm_db,$get_usage_history_sql);
		
		$history = array( 'cols' => array( array('id' => '', 'label' => 'timestamp', 'pattern' => '', 'type' => 'string'), array('id' => '', 'label' => 'total', 'pattern' => '', 'type' => 'number') ), 'rows' => [] );

		while($row = pg_fetch_assoc($get_usage_history_ret)){
					
					$timestamp = formatDate($row['timestamp']);
					//error_log($timestamp);
					$count = $row['count'];
					//error_log("$timestamp: $count");
					
					$rowArray = array( 'c' => [] );
					$timestampArray = array( 'v' => $timestamp, 'f' => null);
					$countArray = array( 'v' => $count, 'f' => null);
					
					array_push($rowArray['c'], $timestampArray);
					array_push($rowArray['c'], $countArray);
					array_push($history['rows'], $rowArray);
					
		}


		$json = json_encode($history);

		header('Content-type: application/json');
		print $json;

		pg_close($pos_db);
		pg_close($wgm_db);
		exit();
		break;
		
		
		
}

?>
