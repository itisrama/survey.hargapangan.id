jQuery.noConflict();
(function($) {
	$(document).ready(function (){
		checkSatwil();
		if(typeof id_wil_dir1 !== 'undefined' && typeof id_wil_dir1.enable === 'function') {
			id_wil_dir1.on('change', function(){
				checkSatwil();
			});
		}

		function checkSatwil() {
			if(typeof id_wil_dir2 !== 'undefined' && typeof id_wil_dir2.enable === 'function') id_wil_dir2.enable();
			if(typeof id_wil_polda !== 'undefined' && typeof id_wil_polda.enable === 'function') id_wil_polda.enable();
			if(typeof id_wil_polres !== 'undefined' && typeof id_wil_polres.enable === 'function') id_wil_polres.enable();
			if(typeof id_wil_polsek !== 'undefined' && typeof id_wil_polsek.enable === 'function') id_wil_polsek.enable();
			if(typeof id_wil_dir1 !== 'undefined' && typeof id_wil_dir1.enable === 'function') {
				switch(id_wil_dir1.getValue()) {
					case '1':
						if(typeof id_wil_dir2 !== 'undefined' && typeof id_wil_dir2.disable === 'function') {
							id_wil_dir2.clear();
							id_wil_dir2.clearOptions();
							id_wil_dir2.onSearchChange('');
							id_wil_dir2.disable();
						}
					case '2':
						if(typeof id_wil_polda !== 'undefined' && typeof id_wil_polda.disable === 'function') {
							id_wil_polda.clear();
							id_wil_polda.clearOptions();
							id_wil_polda.onSearchChange('');
							id_wil_polda.disable();
						}
					case '3':
						if(typeof id_wil_polres !== 'undefined' && typeof id_wil_polres.disable === 'function') {
							id_wil_polres.clear();
							id_wil_polres.clearOptions();
							id_wil_polres.onSearchChange('');
							id_wil_polres.disable();
						}
					case '4':
						if(typeof id_wil_polsek !== 'undefined' && typeof id_wil_polsek.disable === 'function') {
							id_wil_polsek.clear();
							id_wil_polsek.clearOptions();
							id_wil_polsek.onSearchChange('');
							id_wil_polsek.disable();
						}
						break;
				}
			}
		}
	});
})(jQuery);