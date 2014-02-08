<?php

error_reporting( 'E_NONE' );

class WPephemeris {

	public $debug = false;

	# planets, in the order swiss provides them, with english name and symbol
	public $planets = array(
		0 => array(
			'name' => 'Sun',
			'symbol' => '☉',
			'html' => '&#x2609;',
			'letter' => '<span class="zodiac-sign yellow">A</span>'
		),
		1 => array(
			'name' => 'Moon',
			'symbol' => '☽',
			'html' => '&#x263D;',
			'letter' => '<span class="zodiac-sign violet">B</span>'
		),
		2 => array(
			'name'=> 'Mercury',
			'symbol' => '☿',
			'html' => '&#x263F;',
			'letter' => '<span class="zodiac-sign orange">C</span>'
		),
		3 => array(
			'name' => 'Venus',
			'symbol' => '♀',
			'html' => '&#x2640;',
			'letter' => '<span class="zodiac-sign cyan">D</span>'
		),
		4 => array(
			'name' => 'Mars',
			'symbol' => '♂',
			'html' => '&#x2642;',
			'letter' => '<span class="zodiac-sign red">E</span>'
		),
		5 => array(
			'name' => 'Jupiter',
			'symbol' => '♃',
			'html' => '&#x2643;',
			'letter' => '<span class="zodiac-sign cyan">F</span>'
		),
		6 => array(
			'name' => 'Saturn',
			'symbol' => '♄',
			'html' => '&#x2644;',
			'letter' => '<span class="zodiac-sign dark blue">G</span>'
		),
	);

	# zodiacal signs, keys as swiss abbreviates them, with name and symbol
	public $zodiac = array(
		'ar' => array(
			'name' => 'Aries',
			'symbol' => '♈',
			'html' => '&# x2648;',
			'letter' => '<span class="zodiac-sign red">a</span>'
		),
		'ta' => array(
			'name' => 'Taurus',
			'symbol' => '♉',
			'html' => '&#x2649;',
			'letter' => '<span class="zodiac-sign green">b</span>'
		),
		'ge' => array(
			'name' => 'Gemini',
			'symbol' => '♊',
			'html' => '&#x264a;',
			'letter' => '<span class="zodiac-sign orange">c</span>'
		),
		'cn' => array(
			'name' => 'Cancer',
			'symbol' => '♋',
			'html' => '&#x264b;',
			'letter' => '<span class="zodiac-sign blue">d</span>'
		),
		'le' => array(
			'name' => 'Leo',
			'symbol' => '♌',
			'html' => '&#x264c;',
			'letter' => '<span class="zodiac-sign red">e</span>'
		),
		'vi' => array(
			'name' => 'Virgo',
			'symbol' => '♍',
			'html' => '&#x264d;',
			'letter' => '<span class="zodiac-sign green">f</span>'
		),
		'li' => array(
			'name' => 'Libra',
			'symbol' => '♎',
			'html' => '&#x264e;',
			'letter' => '<span class="zodiac-sign blue">g</span>'
		),
		'sc' => array(
			'name' => 'Scorpio',
			'symbol' => '♏',
			'html' => '&#x264f;',
			'letter' => '<span class="zodiac-sign orange">h</span>'
		),
		'sa' => array(
			'name' => 'Sagittarius',
			'symbol' => '♐',
			'html' => '&#x2650;',
			'letter' => '<span class="zodiac-sign red">i</span>'
		),
		'cp' => array(
			'name' => 'Capricorn',
			'symbol' => '♑',
			'html' => '&#x2651;',
			'letter' => '<span class="zodiac-sign green">j</span>'
		),
		'aq' => array(
			'name' => 'Aquarius',
			'symbol' => '♒',
			'html' => '&#x2652;',
			'letter' => '<span class="zodiac-sign orange">k</span>'
		),
		'pi' => array(
			'name' => 'Pisces',
			'symbol' => '♓',
			'html' => '&#x2653;',
			'letter' => '<span class="zodiac-sign blue">l</span>'
		),
	);

	##
	# __construct()
	# 
	##
	public function __construct() {
		switch ( $_REQUEST['action'] ) {
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
		echo "\<!-- " . var_export( $result, true ) . "--\>";
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
		$timeutc = $_GET['time'] ? $_GET['time'] : date( 'H.i' );

		$chart = $this->get_chart( $date, $time );

		$output .= "<span class='wp-ephemeris'>"; # for html request
		$json = array(); # for json request

		foreach( $this->planets as $index => $planet ) :
			$deg = substr( $chart[$index], 0, 2 ); # degrees is first two chars
			$sign = substr( $chart[$index], 3, 2 ); # sign is next two chars
			$json[] = array(
				'planet' => $planet,
				'deg' => $deg,
				'sign' => $this->zodiac[$sign]
			);
			$output .= $planet['letter'] . " " . $deg . "° ";
			if ( $svg ) :
				$url = plugin_dir_url( __FILE__  ) . 'svgs/' . $this->zodiac[$sign]['name'] . '.svg';
				$output .= "<img width='15'src='$url' />";
			else :
				$output .= $this->zodiac[$sign]['letter'] .'';
			endif;

		endforeach;

		$output .= "</span>";

		reset( $this->planets );
		
		# json request?
		if ( 'json' == $_REQUEST['format'] ) :
			echo json_encode( $json );
			exit();
		endif;
		echo $output;
		exit();
	}

	public function thelemic_date() {

		$date = $_GET['date'] ? $_GET['date'] : date( 'd.m.Y' );
		$time = $_GET['time'] ? $_GET['time'] : date( 'H.i' );

		$output = "";

		# Sol #deg Sign, Luna #deg Sign Anno IV:xxi dies --
		$chart = $this->get_chart( $date, $time );

		foreach( $this->planets as $index => $planet ) :
			if ( 2 == $index )
				break;
			$deg = substr( $chart[$index], 0, 2 ); # degrees is first two chars
			$sign = substr( $chart[$index], 3, 2 ); # sign is next two chars
			$output .= $planet['letter'] . " " . $deg . "° ";
			$output .= $this->zodiac[$sign]['letter'] .' ';
		endforeach;

		$output .= "Anno ";
		$date1 = "1904-04-08";
		$date2 = date( 'Y-m-d', strtotime( $date ) );

		$diff = abs(strtotime($date2) - strtotime($date1));

		$years = floor( $diff / ( 365*60*60*24 ) );
		$first = $years / 22;
		$second = $years % 22;
		$output .= $this->roman_number( $first ) . ":" . strtolower( $this->roman_number( $second ) );
		echo $output;
		exit();
	}

	private function roman_number( $num ) { 
		$n = intval( $num ); 
		$res = '';

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
			'I'  => 1
		); 

		foreach ( $roman_numerals as $roman => $number ) { 
			$matches = intval( $n / $number ); 
			$res .= str_repeat( $roman, $matches ); 
			$n = $n % $number; 
		} 

		return $res; 
	}
}

new WPephemeris();