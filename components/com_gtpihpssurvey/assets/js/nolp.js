jQuery.noConflict();
(function($) {
	inputmask.extendDefinitions({
		'~': {
			validator: "[-_/ a-zA-Z0-9]",
			cardinality: 1,
			casing: "upper"
		}
	});
	$(function() {
		function setMask() {
			$('#jform_no_lp_2').inputmask({ mask: '9{1,8}'});
			$('#jform_no_lp_5').inputmask({ mask: '~{8,50}'});
		}

		setMask();

		$('#jform_tgl_lp').change(function(){
			var date = $(this).val().split('-');
			var month = decimalToRoman(parseInt(date[1]));

			$('#jform_no_lp_3').val(month);
			$('#jform_no_lp_4').val(date[2]);
		});

		$('#jform_no_lp_2, #jform_no_lp_5').keyup(function(){
			var val1 = $('#jform_no_lp_2').val();
			var val2 = $('#jform_no_lp_5').val();

			if(val1.length > 0 && val2.length > 0) {
				$('#jform_no_lp_format').val(val1+':'+val2);
			} else {
				$('#jform_no_lp_format').val('');
			}
		});
	});

	
})(jQuery);