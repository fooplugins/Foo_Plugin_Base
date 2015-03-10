jQuery(function ($) {

	$( document ).on( 'click', '.foo_metabox_field_visibility .add-condition', function(e) {
		e.preventDefault();

		var $container = $( this).parents('.foo_metabox_field_visibility:first'),
			$rule = $container.find( 'div.foo_metabox_field_visibility_rule:first' ),
			$ruleClone = $rule.clone().insertBefore( $container.find('.foo_metabox_field_visibility_control') );
		$container.find('.condition-seperator').show();
		$ruleClone.find('.condition-seperator').hide();
		$ruleClone.find( 'select.conditions-rule-major' ).val( '' );
		$ruleClone.find( 'select.conditions-rule-minor' ).html( '' ).attr( 'disabled' );
	});

	$( document ).on( 'click', '.foo_metabox_field_visibility .delete-condition', function(e) {
		e.preventDefault();
		$( this).parents('.foo_metabox_field_visibility_rule:first').remove();
	});

		//change visibility type
	$( document ).on( 'change', '.foo_metabox_field_visibility select.conditions-rule-major', function() {
		var $conditionsRuleMajor = $ ( this );
		var $conditionsRuleMinor = $conditionsRuleMajor.siblings( 'select.conditions-rule-minor:first' );

		if ( $conditionsRuleMajor.val() ) {
			$conditionsRuleMinor.html( '' ).append( $( '<option/>' ).text( $conditionsRuleMinor.data( 'loading-text' ) ) );

			var data = {
				action: 'metabox_visibility_conditions_options',
				major: $conditionsRuleMajor.val()
			};

			jQuery.post( ajaxurl, data, function( html ) {
				$conditionsRuleMinor.html( html ).removeAttr( 'disabled' );
			} );
		} else {
			$conditionsRuleMajor.siblings( 'select.conditions-rule-minor' ).attr( 'disabled', 'disabled' ).html( '' );
		}
	} );

});