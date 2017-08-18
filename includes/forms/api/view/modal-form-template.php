<?php
/**
 * Modal Form Template
 */
ob_start(); ?>

	<div class="give-form-wrapper give-display-style-modal">
		<form action="{{form_action}}" method="{{form_method}}" {{form_attributes}}>
			{{form_fields}}
		</form>
		{{continue_button}}
	</div>

<?php return ob_get_clean(); ?>