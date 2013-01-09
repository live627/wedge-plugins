var progressTest = new function () {
	var me = this;
	
	var counter = 1;
	
	me.init = function () {
		$('progress').each( function (index, element) {
			var $el = $(element);
			if ($el.next().hasClass('arrow') || $el.next().hasClass('after')) {
				$el = $el.next();
			}
			
			if (!element.id) {
				element.id = 'id' + counter;
				counter++;
			}
			
			
			$el.after('<input type="submit" class="progressTest" data-for="'+ element.id + '" value="Test Progress Bar" />')
		});
		
		$('.progressTest').click(function (e) {
			e.preventDefault();
			var id = $(e.target).attr('data-for');
			
			var el = $('#' + id ).get(0);
			var $arrow = $('[data-arrow-for="' + id + '"]');
			
			
			
			$arrow.addClass('noTransition');
			el.value=0;
			setTimeout(function () {
				startHelper($arrow, el);
			}, 200);
		});

	}
	
	function startHelper($arrow, el) {
		
		var step = $(el).attr('data-step');
			
		if (step) {
			step = parseInt(step);
		} else {
			step = 10;
		}
		
		$arrow.removeClass('noTransition');
		
		startTimeout(0, el, step, 100);
	
		
	}
	
	function startTimeout(n, el, step, ms) {
		 
		var val = parseInt(el.value);
		el.value = n;
		
		if (parseInt(el.value) < 100) {
			setTimeout(function() {
				startTimeout(n+step, el, step, ms)
			}, ms);
		}	
	}
}


$(document).ready(progressTest.init)
