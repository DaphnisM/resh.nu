jQuery(document).ready(function($){
	var times = SunCalc.getTimes( new Date(), '45.5379','-122.714' );

	events = [];

	events.push( {
	    title  : 'Sunrise',
	    start  : times.sunrise.toISOString(),
	    end    : times.sunriseEnd.toISOString(),
	} );

	jQuery( '#wpfc-calendar').fullCalendar({
	    events: events
	});

});