jQuery(function($){
	$('.ogr-connect-disconnect').on('click', function(e){
		if ( ! confirm('Are you sure want to disconnect the website?')) e.preventDefault();
	})
});


