<?php

class WPephemeris {

	public $debug = true;

	# planets, in the order swiss provides them, with english name and symbol
	public $planets = array(
		0 => array(
			'name' => 'Sun',
			'symbol' => '☉',
			'html' => '&#x2609;',
			'letter' => 'A'
		),
		1 => array(
			'name' => 'Moon',
			'symbol' => '☽',
			'html' => '&#x263D;',
			'letter' => 'B'
		),
		2 => array(
			'name'=> 'Mercury',
			'symbol' => '☿',
			'html' => '&#x263F;',
			'letter' => 'C'
		),
		3 => array(
			'name' => 'Venus',
			'symbol' => '♀',
			'html' => '&#x2640;',
			'letter' => 'D'
		),
		4 => array(
			'name' => 'Mars',
			'symbol' => '♂',
			'html' => '&#x2642;',
			'letter' => 'E'
		),
		5 => array(
			'name' => 'Jupiter',
			'symbol' => '♃',
			'html' => '&#x2643;',
			'letter' => 'F'
		),
		6 => array(
			'name' => 'Saturn',
			'symbol' => '♄',
			'html' => '&#x2644;',
			'letter' => 'G'
		),
	);

	# zodiacal signs, keys as swiss abbreviates them, with name and symbol
	public $zodiac = array(
		'ar' => array(
			'name' => 'Aries',
			'symbol' => '♈',
			'html' => '&# x2648;',
			'letter' => 'a'
		),
		'ta' => array(
			'name' => 'Taurus',
			'symbol' => '♉',
			'html' => '&#x2649;',
			'letter' => 'b'
		),
		'ge' => array(
			'name' => 'Gemini',
			'symbol' => '♊',
			'html' => '&#x264a;',
			'letter' => 'c'
		),
		'cn' => array(
			'name' => 'Cancer',
			'symbol' => '♋',
			'html' => '&#x264b;',
			'letter' => 'd'
		),
		'le' => array(
			'name' => 'Leo',
			'symbol' => '♌',
			'html' => '&#x264c;',
			'letter' => 'e'
		),
		'vi' => array(
			'name' => 'Virgo',
			'symbol' => '♍',
			'html' => '&#x264d;',
			'letter' => 'f'
		),
		'li' => array(
			'name' => 'Libra',
			'symbol' => '♎',
			'html' => '&#x264e;',
			'letter' => 'g'
		),
		'sc' => array(
			'name' => 'Scorpio',
			'symbol' => '♏',
			'html' => '&#x264f;',
			'letter' => 'h'
		),
		'sa' => array(
			'name' => 'Sagittarius',
			'symbol' => '♐',
			'html' => '&#x2650;',
			'letter' => 'i'
		),
		'cp' => array(
			'name' => 'Capricorn',
			'symbol' => '♑',
			'html' => '&#x2651;',
			'letter' => 'j'
		),
		'aq' => array(
			'name' => 'Aquarius',
			'symbol' => '♒',
			'html' => '&#x2652;',
			'letter' => 'k'
		),
		'pi' => array(
			'name' => 'Pisces',
			'symbol' => '♓',
			'html' => '&#x2653;',
			'letter' => 'l'
		),
	);

	##
	# __construct()
	# 
	##
	public function __construct() {
		switch $_REQUEST['action'] {
			case 'thelemic_date' :
				$this->thelemic_date();
				break;
			case 'zodiac_chart' :
				$this->get_zodiac();
				break;
		}
	}

	public function get_chart( $date, $time ) {
		# run swetest with date information and separate out results by newlines
		$swetest = 'src/swetest';
		$result = `$swetest -b$date -t$time -fTZ -roundmin -head 2>&1`;
		$chart = explode( "\n", $result );

		# trim excess information - swetest repeats the date and time
		foreach ( $chart as $index => $swe_output ) :
			$swe_output_cleaned = split( 'ET ', $swe_output );
			$chart[$index] = $swe_output_cleaned[1];
		endforeach;

		$chart = array_slice( $chart , 0, 8 ); # only get planet lines
		return $chart;
	}

	##
	# The shortcode `[wp-ephemeris]` by default prints the result for the 
	# current time/date.
	# 
	# timeutc="" and date="" attributes given as dd.mm.yyyy and hh.mmss
	##
	public function get_zodiac() {

		$date = $_GET['date'] ? $_GET['date'] : date( 'd.m.Y' );
		$timeutc = $_GET['timeutc'] ? $_GET['timeutc'] : date( 'H.i' );

		$wpephem = new WPephemeris();
		$chart = $wpephem->get_chart( $date, $time );

		$output .= "<span class='wp-ephemeris'>"; # for html request
		$json = array(); # for json request

		foreach( $wpephem->planets as $index => $planet ) :
			$deg = substr( $chart[$index], 0, 2 ); # degrees is first two chars
			$sign = substr( $chart[$index], 3, 2 ); # sign is next two chars
			$json[] = array(
				$planet,
				$deg,
				$wpephem->zodiac[$sign]
			);
			$output .= $wpephem->wingding( $planet['letter'] ) . " " . $deg . "° ";
			if ( $svg ) :
				$url = plugin_dir_url( __FILE__  ) . 'svgs/' . $wpephem->zodiac[$sign]['name'] . '.svg';
				$output .= "<img width='15'src='$url' />";
			else :
				$output .= $wpephem->wingding($wpephem->zodiac[$sign]['letter']) .'';
			endif;

		endforeach;

		$output .= "</span>";

		reset( $wpephem->planets );
		
		# json request?
		if ( 'json' == $_REQUEST['format'] ) ) :
			echo json_encode( $json );
			exit();
		endif;
		echo $output;
		exit();
	}

	function wingding( $letter ) {
		return '<span class="zodiac-sign">' . $letter . '</span>';
	}

	function thelemic_date() {

		$date = $_GET['date'] ? $_GET['date'] : date( 'd.m.Y' );
		$time = $_GET['timeutc'] ? $_GET['timeutc'] : date( 'H.i' );

		$wpephem = new WPephemeris();

		# Sol #deg Sign, Luna #deg Sign Anno IV:xxi dies --
		$chart = $wpephem->get_chart( $date, $time );

		foreach( $wpephem->planets as $index => $planet ) :
			if ( 2 == $index )
				break;
			$deg = substr( $chart[$index], 0, 2 ); # degrees is first two chars
			$sign = substr( $chart[$index], 3, 2 ); # sign is next two chars
			$output .= $wpephem->wingding( $planet['letter'] ) . " " . $deg . "° ";
			$output .= $wpephem->wingding($wpephem->zodiac[$sign]['letter']) .' ';
		endforeach;

		$output .= "Anno ";
		$date1 = "1904-04-08";
		$date2 = date( 'Y-m-d', strtotime( $date ) );

		$diff = abs(strtotime($date2) - strtotime($date1));

		$years = floor( $diff / ( 365*60*60*24 ) );
		$first = $years / 22;
		$second = $years % 22;
		$output .= $wpephem->romanic_number( $first ) . ":" . $wpephem->romanic_number( $second );
		$output .= ' Dies ' . $wpephem->planetary_day( date('N', strtotime( $date ) ) );
		echo $output;
		exit();
	}

	function romanic_number($num){ 
		$n = intval($num); 
		$res = ''; 

		/*** roman_numerals array  ***/ 
		$roman_numerals = array( 
		    'M'  => 1000, 
		    'CM' => 900, 
		    'D'  => 500, 
		    'CD' => 400, 
		    'C'  => 100, 
		    'XC' => 90, 
		    'L'  => 50, 
		    'XL' => 40, 
		    'X'  => 10, 
		    'IX' => 9, 
		    'V'  => 5, 
		    'IV' => 4, 
		    'I'  => 1); 

		foreach ($roman_numerals as $roman => $number){ 
		    /*** divide to get  matches ***/ 
		    $matches = intval($n / $number); 

		    /*** assign the roman char * $matches ***/ 
		    $res .= str_repeat($roman, $matches); 

		    /*** substract from the number ***/ 
		    $n = $n % $number; 
		} 

		/*** return the res ***/ 
		return $res; 
	}

	function planetary_day( $num ) {
		switch ( $num ) {
			case '1' :
				return $this->planets[1]['html'];
			break;
			case '2' :
				return $this->planets[4]['html'];
			break;
			case '3' :
				return $this->planets[2]['html'];
			break;
			case '4' :
				return $this->planets[5]['html'];
			break;
			case '5' :
				return $this->planets[3]['html'];
			break;
			case '6' :
				return $this->planets[6]['html'];
			break;
			case '7' :
				return $this->planets[0]['html'];
			break;
		}
	}
}

new WPephemeris();