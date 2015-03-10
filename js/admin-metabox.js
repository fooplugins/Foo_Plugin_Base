jQuery(function ($) {
	//colorpicker fields
	if ($.fn.spectrum) {
		$('.foo_metabox_field_colorpicker input.colorpicker').spectrum({
			preferredFormat: "rgb",
			showInput: true,
			clickoutFiresChange: true
		});
	}

	$('[data-show-field]').each(function() {
		var $source_row = $(this),
			field = $source_row.data('showField'),
			requiredValue = $source_row.attr('data-show-value'),
			actualValue = false,
			$field_row = $('.' + field),
			type = $field_row.data('fieldType');

		if ($field_row.length) {
			switch(type) {
				case 'select':
					var $select = $field_row.find('select');
					$select.on('change', function() {
						if ($(this).val() == requiredValue) {
							$source_row.show();
						} else {
							$source_row.hide();
						}
					});
					actualValue = $select.val();
					break;
				case 'radio':
					var $radios = $field_row.find('input[type="radio"]');
					actualValue = $field_row.find('input[type="radio"]:checked').val();
					$radios.on('change', function() {
						actualValue = $field_row.find('input[type="radio"]:checked').val();
						if (actualValue == requiredValue) {
							$source_row.show();
						} else {
							$source_row.hide();
						}
					});
					break;
				case 'checkbox':
					var $checkbox = $field_row.find('input[type="checkbox"]');
					actualValue = $checkbox.is(':checked') ? 'on' : '';
					$checkbox.on('change', function() {
						actualValue = $checkbox.is(':checked') ? 'on' : '';
						if (actualValue == requiredValue) {
							$source_row.show();
						} else {
							$source_row.hide();
						}
					});
					break;
			}

			if (requiredValue == actualValue) {
				$source_row.show();
			} else {
				$source_row.hide();
			}
		}
	});
});