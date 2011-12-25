function call_user_func_array (cb, parameters)
{
	// http://kevin.vanzonneveld.net
	// +	original by: Thiago Mata (http://thiagomata.blog.com)
	// +	revised	by: Jon Hohle
	// +	improved by: Brett Zamir (http://brett-zamir.me)
	// +	improved by: Diplom@t (http://difane.com/)
	// +	improved by: Brett Zamir (http://brett-zamir.me)
	var func;

	if (typeof cb === 'string')
		func = (typeof this[cb] === 'function') ? this[cb] : func = (new Function(null, 'return ' + cb))();

	else if (Object.prototype.toString.call(cb) === '[object Array]')
		func = (typeof cb[0] == 'string') ? eval(cb[0] + "['" + cb[1] + "']") : func = cb[0][cb[1]];

	else if (typeof cb === 'function')
		func = cb;

	if (typeof func !== 'function')
		throw new Error(func + ' is not a valid function');

	return (typeof cb[0] === 'string') ? func.apply(eval(cb[0]), parameters) : (typeof cb[0] !== 'object') ? func.apply(null, parameters) : func.apply(cb[0], parameters);
}

function handleFields()
{
	this.type = $("#field_type").val();
	this.isText = this.type == "text" || this.type == "textarea";
	this.regexMask = this.type == "text" || this.type == "textarea";
	this.regexFmt = this.type == "select";
	this.dimension = this.type == "textarea";
	this.size = this.type == "select";
	this.bbc = this.type == "text" || this.type == "textarea" || this.type == "select" || this.type == "radio" || this.type == "check";
	this.opts = this.type == "select" || this.type == "radio";
	this.def = this.type == "check";
}

function updateInputBoxes(b)
{
	var hf = new handleFields();
	$.pfHooks.call("pf_admin_form", [hf, b]);
	if (b)
	{
		$("#max_length_dt, #max_length_dd").toggle(hf.isText);
		$("#dimension_dt, #dimension_dd").toggle(hf.dimension);
		$("#size_dt, #size_dd").toggle(hf.size);
		$("#bbc_dt, #bbc_dd").toggle(hf.bbc);
		$("#options_dt, #options_dd").toggle(hf.opts);
		$("#default_dt, #default_dd").toggle(hf.def);
		$("#mask_dt, #mask_dd").toggle(hf.regexMask);
	}
	else
	{
		$("#max_length_dt, #max_length_dd").slideFadeToggle(hf.isText);
		$("#dimension_dt, #dimension_dd").slideFadeToggle(hf.dimension);
		$("#size_dt, #size_dd").slideFadeToggle(hf.size);
		$("#bbc_dt, #bbc_dd").slideFadeToggle(hf.bbc);
		$("#options_dt, #options_dd").slideFadeToggle(hf.opts);
		$("#default_dt, #default_dd").slideFadeToggle(hf.def);
		$("#mask_dt, #mask_dd").slideFadeToggle(hf.regexMask);
	}
}

function updateInputBoxes2(b)
{
	regexMask = $("#field_mask").val() == 'regex';
	$.pfHooks.call("pf_admin_form_regex", [regexMask, b]);
	if (b)
		$("#regex_dt, #regex_dd").toggle(regexMask);
	else
		$("#regex_dt, #regex_dd").slideFadeToggle(regexMask);
}

function addOption()
{
	$("#addopt").append('<br><input type="radio" name="default_select" value="' + startOptID + '" id="' + startOptID + '"><input type="text" name="select_option[' + startOptID + ']" value="">');
	startOptID++;
}

(function ($) {
	$.pfHooks = {
		funcs: [],
		add: function (sPoint, func) {
			if (typeof this.funcs[sPoint] === "undefined")
				this.funcs[sPoint] = [];

			this.funcs[sPoint].push(func);
		},
		call: function (sPoint, sArguments) {
			aRet = [];

			if (typeof this.funcs[sPoint] === "undefined")
				return aRet;

			this.funcs[sPoint].forEach(function (name) {
				aRet.push(name.apply(this, sArguments));
			});

			return aRet;
		},
		remove: function (sPoint, func) {
			var idx = typeof this.funcs[sPoint] === "undefined" ? -1 : this.funcs[sPoint].indexOf(func);
			if (idx != -1)
				this.funcs[sPoint].splice(idx, 1);
		}
	};

	var _text = $.fn.text;
	$.fn.text = function(text) {
		if ($(this).html().indexOf('|'))
			return $(this).html().replace('|',  '<div class="smalltext" style="color: gray; font-style: italic; margin: 0 0 0.5em 2em;">') + '</div>';
		else
			return _text.call(this, text);
	};
	$("#field_mask").sb('refresh');

	$.hookEvent = function (fns) {
		fns = typeof fns === 'string' ? fns.split(' ') : $.makeArray(fns);

		jQuery.each(fns, function (i, method) {
			var old = $.fn[method];

			if (old && !old.__hookold) {
				$.fn[method] = function () {
					this.triggerHandler('onbefore' + method);
					var ret = old.apply(this, arguments);
					this.triggerHandler('onafter' + method);
					return ret;
				};
				$.fn[method].__hookold = old;
			}
		});
	};

	$.unhookEvent = function (fns) {
		fns = typeof fns === 'string' ? fns.split(' ') : $.makeArray(fns);

		jQuery.each(fns, function (i, method) {
			var cur = $.fn[method];

			if (cur && cur.__hookold)
				$.fn[method] = cur.__hookold;
		});
	};

	$.fn.slideFadeToggle = function(b) {
		sh = b ? "show" : "hide";
		// !!! TODO: Convert this to CSS
		return this.animate({opacity: sh, height: sh});
	};

})(jQuery);