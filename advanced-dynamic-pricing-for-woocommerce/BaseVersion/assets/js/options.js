jQuery( document ).ready( function ( $ ) {


	$( '.section_choice' ).click( function () {

		$( '.section_choice' ).removeClass( 'active' );
		$( this ).addClass( 'active' );

		$( '.settings-section' ).removeClass( 'active' );
		$( '#' + $( this ).data( 'section' ) + '_section' ).addClass( 'active' );

		window.location.href = $( this ).attr( 'href' );
	} );

	setTimeout( function () {
		if ( window.location.hash.indexOf( 'section' ) !== - 1 ) {
			$( '.section_choice[href="' + window.location.hash + '"]' ).click()
		} else {
			$( '.section_choice' ).first().click()
		}
	}, 0 );

	setTimeout(function () {
		$('#update_price_with_qty').change(function() {
			if (this.checked) {
        $('#enable_quick_price_change_for_simple_products').closest('tr').show()
        $('#show_spinner_when_update_price').closest('tr').show()
				$('#replace_variable_price').closest('tr').show()
			} else {
				$('#enable_quick_price_change_for_simple_products').closest('tr').hide()
				$('#replace_variable_price').closest('tr').hide()
        $('#show_spinner_when_update_price').closest('tr').hide()
			}
		}).trigger('change');
    $('#update_price_with_qty').change(function() {
      if (this.checked) {
        $('#replace_variable_price').prop('checked', true);
      }
    });
	}, 0);

  setTimeout(function () {
    $('#process_product_strategy').change(function(e) {
      if (e.target.value === "after") {
        $('#process_product_strategy_after_use_price').closest('tr').show()
      } else {
        $('#process_product_strategy_after_use_price').closest('tr').hide()
      }
    }).trigger('change');
  }, 0);

  $('.wdp-settings-search-wrapper').css('max-width', $('.wcp_tabs_container_list').width())
  $('.wdp-settings-button').css('max-width', $('.wdp-settings-wrapper form').width() - ($('.wcp_tabs_container').outerWidth(true) - $('.wcp_tabs_container_list').width()))

  $('.wdp-settings-search input').on('input', function () {
    var search = $(this).val()
    $('.settings-section').removeClass('active')
    $('.section-settings tr').addClass('hide')
    $('.titledesc').each(function () {
        $(this).html($(this).text().trim().replace(new RegExp('<span class="search\-match">(.*?)<\/span>', 'ig'), '$1'))
    })
    $('.wdp-settings-search-results-wrapper .empty-results').addClass('hide')
    $('.wdp-settings-button').removeClass('hide')
    if (search) {
        $('.wdp-settings-search-results-wrapper .title').removeClass('hide')
        $('.titledesc').each(function () {
            if ($(this).text().trim().match(new RegExp(search, 'ig'))) {
                $(this).html($(this).text().trim().replace(new RegExp('(' + search + ')', 'ig'), '<span class="search-match">$1</span>'))
                $(this).closest('.settings-section').addClass('active')
                $(this).closest('tr').removeClass('hide')
            }
        })
        if (!$('.titledesc .search-match').length) {
            $('.wdp-settings-search-results-wrapper .empty-results').removeClass('hide')
            $('.wdp-settings-button').addClass('hide')
        }
        $(this).closest('.wdp-settings-search').find('.dashicons-dismiss').removeClass('hide')
    } else {
        $('#' + $('.section_choice.active').attr('href').replace('#section=', '') + '_section').addClass('active')
        $('.section-settings tr').removeClass('hide')
        $('.wdp-settings-search-results-wrapper .title').addClass('hide')
        $(this).closest('.wdp-settings-search').find('.dashicons-dismiss').addClass('hide')
    }
  })

  $('.wdp-settings-search .dashicons-dismiss').on('click', function () {
    $(this).closest('.wdp-settings-search').find('input').val('').trigger('input').focus()
  })

} );
