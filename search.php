<?php

$lat = $_GET['lat'];
$long = $_GET['long'];
$small_distance = .75;
$large_distance = 1.5;
$days=5;

if($_GET['local'] ) {
	$xml_small = get_xml( $lat, $long, -1, $days );
	$xml_big = get_xml( $lat, $long, -2, $days );
} else {
	$xml_small = get_xml( $lat, $long, $small_distance, $days );
	$xml_big = get_xml( $lat, $long, $large_distance, $days );
}


while (count($xml_small->crimes->crime) <25) {
	$days += 10;
	if($days>20) break;
	$xml_small = get_xml( $lat, $long, $small_distance, $days );
	$xml_big = get_xml( $lat, $long, $large_distance, $days );
}

$i;
while (count($xml_big->crimes->crime) > 500 ) {
	$days -= 2;
	if($i++ > 2) break;
	$xml_small = get_xml( $lat, $long, $small_distance, $days );
	$xml_big = get_xml( $lat, $long, $large_distance, $days );
}

if($_GET['debug']) {
echo "<br><br>area: ";
print_r($crimes_small);echo "<br>surroundings: ";
print_r($crimes_big);
}

$buckets_small = bucket_crimes( $xml_small );
$buckets_big = bucket_crimes( $xml_big );
if($_GET['debug']==1) {
	foreach($buckets_small as $a => $b)
		echo $a.":".count($b);
	echo "<br>";
	foreach($buckets_big as $a => $b)
		echo $a.":".count($b);
	echo "<br>";
}
$risks = bucket_risks($buckets_small, $buckets_big);
if($_GET['type'] == web ) 
	get_web($risks);
else get_twitter($risks);
return;



function get_web( $risks ) {
	$result=array();
	#print_r($risks);
	foreach ($risks as $a => $b) {
		if($a=='neighborhood')
			$a='Get a security alarm?';
		if($a=='sex')
			$a='Meet a friend?';
		if($a=='car')
			$a='Park here  overnight?';
		if($a=='house')
			$a='Settle down here?';
		array_push($result, array(category => $a, risk =>$b['ratio'], crimes =>array_slice($b['crimes'], 0, 3)));
	}
	$json = json_encode($result);
	echo $json;
	return;
}

function get_msg( $category ) {
	$msgs['neighborhood'] = array('better carry your gat.', 'grab the shotgun, bessie.', 'try not to wear red or blue.', 'kevlar can be comfortable.');
	$msgs['sex'] = array("don't wear that short skirt.", "best not to accept the stranger's drink.", "pepper spray is a girl's best friend.");
	$msgs['car'] = array("don't park the ferrari here.", 'did you remember to lock your car doors?');
	$msgs['house'] = array('use the deadbolt.', 'bars on windows look stylish,');

	$messages = $msgs[$category];
	shuffle( $messages );
	return array_pop($messages);
}

function get_twitter( $risks ) {
	$max;
	$msg;
	if($risks)
	foreach ( $risks as $a => $b ) {
		if($b['ratio'] > $max && $b['ratio'] > 20) {
			$msg = get_msg($a)." risk: ".(int)$b['ratio']."%";	
			$max = $b['ratio'];		
		}

	}
	if(!$msg)
		$msg = "all is quiet on the western front";
	echo $msg;
	return;
}

function bucket_risks( $a, $b ) {
	foreach($b as $category => $crimes ) 	
		if(count($b[$category]))
			$risks[$category] = array( ratio => count($a[$category]) / count($b[$category]) * 100 , crimes => $crimes );
	return $risks;
}

function bucket_crimes($crimes) {
	$neighborhood_crimes = array('HOMICIDE', 'ROBBERY', 'THEFT', 'ASSAULT','MISSING PERSON', 'WEAPONS OFFENSE', 'DEATH', 'ASSAULT W/ DEADLY WEAPON');
	$female_crimes = array('SEXUAL OFFENSE', 'OTHER SEXUAL OFFENSE');
	$car_crimes = array('THEFT FROM VEHICLE', 'THEFT OF VEHICLE');
	$home_crimes = array('ARSON', 'BREAKING & ENTERING', 'PROPERTY CRIME', 'BREAKING & ENTERING');

	$categories['neighborhood'] = array();
	$categories['sex'] = array();
	$categories['car'] = array();
	$categories['house'] = array();
	
	foreach($crimes->crimes->crime as $crime) {
		$type = $crime->attributes()->type;
		$crime->attributes()->offense_date=rand(15,19).' Mar 2009';
		if(in_array($type, $neighborhood_crimes))  {
			array_push($categories['neighborhood'], $crime);
		}
		elseif(in_array($type, $female_crimes)) {
			array_push($categories['sex'], $crime);
		}
		elseif(in_array($type, $car_crimes)) {
			array_push($categories['car'], $crime);
		}
		elseif(in_array($type, $home_crimes)) {
			array_push($categories['house'], $crime);
		}
	}

	return $categories;
}


function get_xml( $lat, $long, $distance, $days) {
	$MILES_PER_DEG_LAT  = '69.1';
	$MILES_PER_DEG_LONG = '53.0';
	
	#$distance = 2;
	
	$min_lat = $lat - $distance / $MILES_PER_DEG_LAT;
	$max_lat = $lat + $distance / $MILES_PER_DEG_LAT;
	
	$min_long = $long - $distance / $MILES_PER_DEG_LONG;
	$max_long = $long + $distance / $MILES_PER_DEG_LONG;
	
	#$url = 'http://crimereports.com/map/xmldata/isAjax/true/cb/96.7266698420428?=&address_lat='.$lat.'&address_lng='.$long.'&api_key=5h9a38ecriafiupriawRo6dluBiepro1ziakiazluspieq9uyia2iutouwrIesWi&center_lat='.$lat.'&center_lng='.$long.'&countryCode=%3F&custom_date_end=&custom_date_end=2009-01-08&custom_date_start=&custom_date_start=2009-01-05&dayrange=14&dayrange=14&enddate=2009-01-08&fdate=Dec%2031%2C%201969&filter_categories[]=104&filter_categories[]=100&filter_categories[]=98&filter_categories[]=103&filter_categories[]=99&filter_categories[]=97&filter_categories[]=148&filter_categories[]=149&filter_categories[]=150&filter_categories[]=101&largeMap=&lat='.$lat.'&lng='.$long.'&map_maxlat='.$max_lat.'&map_maxlng='.$max_long.'&map_minlat='.$min_lat.'&map_minlng='.$min_long.'&mapradius=1&one_month=Feb%2019%2C%202009&seven_day=Mar%2012%2C%202009&showSO=0&startdate=2009-01-05&thematic_shapefile_id=&three_day=Mar%2016%2C%202009&two_week=Mar%205%2C%202009';

	$end = 10+$days;
	
	$url = 'http://crimereports.com/map/xmldata/isAjax/true/cb/96.7266698420428?=&address_lat='.$lat.'&address_lng='.$long.'&api_key=5h9a38ecriafiupriawRo6dluBiepro1ziakiazluspieq9uyia2iutouwrIesWi&center_lat='.$lat.'&center_lng='.$long.'&countryCode=%3F&custom_date_end=&custom_date_end=2009-01-'.$end.'&custom_date_start=&custom_date_start=2009-01-10&dayrange=14&dayrange=14&enddate=2009-01-'.$end.'&fdate=Dec%2031%2C%201969&filter_categories%5B%5D=104&filter_categories%5B%5D=100&filter_categories%5B%5D=98&filter_categories%5B%5D=103&filter_categories%5B%5D=99&filter_categories%5B%5D=121&filter_categories%5B%5D=162&filter_categories%5B%5D=164&filter_categories%5B%5D=165&filter_categories%5B%5D=167&filter_categories%5B%5D=171&filter_categories%5B%5D=97&filter_categories%5B%5D=148&filter_categories%5B%5D=149&filter_categories%5B%5D=150&filter_categories%5B%5D=101&filter_categories%5B%5D=180&filter_categories%5B%5D=179&filter_categories%5B%5D=178&largeMap=&lat='.$lat.'&lng='.$long.'&map_maxlat='.$max_lat.'&map_maxlng='.$max_long.'&map_minlat='.$min_lat.'&map_minlng='.$min_long.'&mapradius=1&one_month=Feb%2019%2C%202009&seven_day=Mar%2012%2C%202009&showSO=1&startdate=2009-01-10&thematic_shapefile_id=&three_day=Mar%2016%2C%202009&two_week=Mar%205%2C%202009';

	if($_GET['debug'])
		echo "url: $url<br>";
	
	if($distance==-1) {
		$xml = file_get_contents('./test1.xml');
	} elseif( $distance==-2 ) {
		$xml = file_get_contents('./test2.xml');
	} else {
		$ch = curl_init( $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		$xml = curl_exec($ch);
	}

	$dom = simplexml_load_string( $xml );
	return $dom;
}


?>
