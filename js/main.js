$(function() {
	$('#menu a[href^=#]').click(function() {
		var hash = $(this).attr('href');
		$('html,body').animate({ scrollTop: $(hash).offset().top - 5 }, 'fast');
		return false;
	});

	$('a[href^=#more]').click(function() {
		$('#more').slideDown();
		return false;
	});

	if (!$('#soruces').length) {
		$('<img id="target" src="images/code.png">').appendTo('body').fold({
			directory: 'images',
			startingWidth: 40,
			startingHeight: 40,
			maxHeight: 500,
			sourceUrl: 'sources'
		});
	}
});
