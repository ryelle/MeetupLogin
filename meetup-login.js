(function($) {
	$().ready(function() {
		$("a.meetup").click(function() {
			//window.opener.history.go(0); 
			window.open(data.url+'?action='+data.action,'meetup-login','left=20,top=20,width=500,height=500,locationbar=0,toolbar=0,resizable=0');
			return false;
		});
	});
})(jQuery);
