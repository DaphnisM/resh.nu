var app = angular.module('main', [])
.controller('astrodate', function($scope, $http, $sce, $timeout, $log) {

	var planets = [
		{
			'name' : 'Sun',
			'symbol' : '☉',
			'html' : '&#x2609;',
			'letter' : '<span class="zodiac-sign yellow">A</span>'
		},
		{
			'name' : 'Moon',
			'symbol' : '☽',
			'html' : '&#x263D;',
			'letter' : '<span class="zodiac-sign violet">B</span>'
		},
		{
			'name' : 'Mars',
			'symbol' : '♂',
			'html' : '&#x2642;',
			'letter' : '<span class="zodiac-sign red">E</span>'
		},
		{
			'name': 'Mercury',
			'symbol' : '☿',
			'html' : '&#x263F;',
			'letter' : '<span class="zodiac-sign orange">C</span>'
		},
		{
			'name' : 'Jupiter',
			'symbol' : '♃',
			'html' : '&#x2643;',
			'letter' : '<span class="zodiac-sign green">F</span>'
		},
		{
			'name' : 'Venus',
			'symbol' : '♀',
			'html' : '&#x2640;',
			'letter' : '<span class="zodiac-sign cyan">D</span>'
		},
		{
			'name' : 'Saturn',
			'symbol' : '♄',
			'html' : '&#x2644;',
			'letter' : '<span class="zodiac-sign dark blue">G</span>'
		}
	];

	var planetary_hours = [
		6,
		4,
		2,
		0,
		5,
		3,
		1
	];


	function format_ephem( date, time ) {
		if ( date == undefined || date == null ) { // if we have no date
			date = new Date();
			dow = date.getDay();
			date = date.format('UTC:dd.mm.yyyy');
		}
		if ( time == undefined || time == null ) { // if we have no time
			time = new Date();
			time = time.format('UTC:H.MM');
		}
		if ( date instanceof Date ) { // if date is a Date
			dow = date.getDay();
			time = date.format('UTC:H.MM');
			date = date.format('UTC:dd.mm.yyyy');
		}
		return { date: date, time: time };
	}

	function get_thelemic_date( date, time ) {
		format = format_ephem( date, time );
		date = format.date;
		time = format.time;
		tdate = $.ajax({
			url : '/time.php?action=thelemic_date',
			data : '&date=' + date + "&time=" + time,
			async : false,
			success : function( data ) { return data; }
		});
		thelemic_date = tdate.responseText + " dies " + planets[dow]['letter'];
		return thelemic_date;
	}

	function get_planets( date, time ) {
		format = format_ephem( date, time );
		date = format.date;
		time = format.time;
		curr_planets = $.ajax({
			url : '/time.php?action=zodiac_chart&format=json',
			data : 'date=' + date + "&time=" + time,
			async : false,
			success : function( data ) { return data; }
		});
		curr_planets = JSON.parse(curr_planets.responseText);
		return curr_planets;
	}

	function get_planetary_hours( date, times ) {

		var daylength =  times.sunset.getTime() - times.sunrise.getTime();
		var dayhourlength = daylength / 12;
		var tomorrow = new Date( date );
		tomorrow = tomorrow.setDate( tomorrow.getDate() + 1 );
		tomorrow = SunCalc.getTimes( tomorrow, '45.5379','-122.714' );
		var nighthourlength = ( tomorrow.sunrise.getTime() - times.sunset.getTime() ) / 12;
		// day of the week
		var daynow = date.getDay();
		daynow = planetary_hours.indexOf(daynow);
		var hrend = new Date ( times.sunrise.getTime() );
		var night = [];
		var day = [];

		for ( var i = 1; i <= 12; i++ ) {
			if ( 7 == daynow )
				daynow = 0;
			hstart = new Date( hrend.getTime() );
			hrend = new Date( hrend.getTime() + dayhourlength );
			var time = planets[planetary_hours[daynow]]['letter']
			 + " " + hstart.format('shortTime') + " - " + hrend.format('shortTime');
			day.push($sce.trustAsHtml(time));
			daynow++;
		}

		for ( var i = 13; i <= 24; i++ ) {
			if ( 7 == daynow )
				daynow = 0;
			hstart = new Date( hrend.getTime() );
			hrend = new Date( hrend.getTime() + nighthourlength );
			var time = planets[planetary_hours[daynow]]['letter']
			 + " " + hstart.format('shortTime') + " - " + hrend.format('shortTime');
			night.push($sce.trustAsHtml(time));
			daynow++;
		}

		return {
			'day' : day,
			'night' : night
		};
	}

	function set_date( date ) {
		var times = SunCalc.getTimes( date, '45.5379','-122.714' );
		times.nadir = SunCalc.getTimes(new Date( date.getTime() + 24 * 60 * 60 * 1000), '45.5379','-122.714').nadir;
		var hours = get_planetary_hours( date, times );

		// Now set everything in scope.
		$scope.tdate = $sce.trustAsHtml( get_thelemic_date( date ) );
		$scope.resh.rise.hd = times.sunrise.format( 'shortTime' );
		$scope.resh.noon.hd = times.solarNoon.format( 'shortTime' );
		$scope.resh.set.hd = times.sunset.format( 'shortTime' );
		$scope.resh.nadir.hd = times.nadir.format( 'shortTime' );
		$scope.resh.rise.td = $sce.trustAsHtml(removeAnno(get_thelemic_date(times.sunrise)));
		$scope.resh.noon.td = $sce.trustAsHtml(removeAnno(get_thelemic_date(times.solarNoon)));
		$scope.resh.set.td = $sce.trustAsHtml(removeAnno(get_thelemic_date(times.sunset)));
		$scope.resh.nadir.td = $sce.trustAsHtml(removeAnno(get_thelemic_date(times.nadir)));
		$scope.day = hours.day;
		$scope.night = hours.night;
		$scope.curr_planets = get_planets( date );
	}

	function removeAnno(str) {
		str = str.substring( 0, str.indexOf("Anno"));
		return str;
	}

	// JQuery-ui datepicker
	$('.datepicker input').datepicker({
		dateFormat : "mm/dd/yy",
		onSelect : function (date, inst) {
			var dateObj = new Date(inst.selectedYear, inst.selectedMonth, inst.selectedDay);
			$log.log(date, inst, dateObj);
			set_date(dateObj);
			$scope.$apply();
		}
	});

	var today = new Date();

	$scope.resh = [];
	$scope.resh.rise = [];
	$scope.resh.noon = [];
	$scope.resh.set = [];
	$scope.resh.nadir = [];

	// Initialize to today.
	set_date(today);

	$scope.hours = Array.apply(null, {length: 24}).map(Number.call, Number);
	$scope.minutes = Array.apply(null, {length: 60}).map(Number.call, Number);
	$scope.date = today.format('mm/dd/yy');
	$scope.hour = today.format('H');
	$scope.minute = today.format('MM');
});