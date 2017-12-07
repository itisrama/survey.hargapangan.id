jQuery.noConflict();
(function($) {
	$(function() {
		function getWilayah(el, task) {
			el.attr('loadJson', 1);
			$.post(window.location.pathname, {
				task: "wilayah."+task,
				id_parent: el.attr('itemId')
			}, function(data){
				data.reverse();
				$.each(data, function(id, item) {
					var row = el.clone();
					var icon = $('.nama i', row);

					if(item.childClass) {
						changeIcon(icon, 1);
					} else {
						changeIcon(icon, 2);
					}
					
					row.attr('class', item.class);
					row.attr('childClass', item.childClass);
					row.attr('itemId', item.id);
					row.attr('level', item.level);
					row.attr('loadJson', 0);

					$('.nama span', row).text(item.nama);
					$('.nama div', row).css('padding-left', item.margin);
					$('.alias', row).text(item.alias);
					row.insertAfter(el);
				});
				changeIcon($('.nama i', el), 0);
			}, 'json');
		}

		function getPolres(el) {
			getWilayah(el, 'getPolres');
		}

		function getPolsek(el) {
			getWilayah(el, 'getPolsek');
		}

		function changeIcon(el, status) {
			var icon0 = 'fa-chevron-circle-down';
			var icon1 = 'fa-chevron-circle-up';
			var icon2 = 'fa-circle-o';
			switch(status) {
				case 0:
					el.css('color', '#2980b9');
					el.addClass(icon0);
					el.removeClass(icon1);
					el.removeClass(icon2);
					break;
				case 1:
					el.css('color', '#95a5a6');
					el.addClass(icon1);
					el.removeClass(icon0);
					el.removeClass(icon2);
					
					break;
				case 2:
					el.css('color', '#95a5a6');
					el.addClass(icon2);
					el.removeClass(icon0);
					el.removeClass(icon1);
					break;

			}
		}

		$('#tableWilayah tbody').on('click', 'tr td:first-child', function(){
			var el = $(this).parent('tr');
			var level = parseInt(el.attr('level'));
			var childClass = el.attr('childClass');
			if(!childClass || level == 1) return false;

			var children	= $('#tableWilayah tbody tr.'+childClass+'.level'+(level+1));
			var childrenAll	= $('#tableWilayah tbody tr.'+childClass);
			var icon		= $('.nama i', el);
			var iconAll		= $('.nama i', $('#tableWilayah tbody tr.children.'+childClass));
			var isOpen		= icon.hasClass('fa-chevron-circle-down');
			var loadJson	= el.attr('loadJson');

			if(isOpen) {
				childrenAll.hide();
				changeIcon(icon, 1);
				changeIcon(iconAll, 1);
			} else {
				if(loadJson == 0 && !el.hasClass('bareskrim')) {
					if(el.hasClass('polda')) {
						getPolres(el);
					} else if(el.hasClass('polres')) {
						getPolsek(el);
					}
				} else {
					children.show();
					changeIcon(icon, 0);
				}
			}
		});
	});

	
})(jQuery);