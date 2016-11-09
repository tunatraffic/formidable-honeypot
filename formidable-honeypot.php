<?php
/*
Plugin Name: Formidable Honeypot
Plugin URI: http://wpbiz.co/
Description: Add invisible SPAM protection to your Formidable forms.
Author: Ryan Pilling
Version: 0.3
Author URI: http://wpbiz.co/
*/

if ( !class_exists( "WPBizFrmHoneypot" ) )
{
	class WPBizFrmHoneypot
	{

		static $styles_required;

		function WPBizFrmHoneypot() // Constructor
		{
			register_activation_hook( __FILE__, array($this, 'run_on_activate') );
			
			add_action('frm_entry_form', array( $this, 'add_honeypot'));
			add_filter('frm_validate_entry', array( $this, 'validate_honeypot') );
		}

		function run_on_activate(){
			// confirm that Formidable is running
			if( is_plugin_active( 'formidable/formidable.php' ) ) {
				// Formidable is active
			} else {
				add_action( 'admin_notices', array( $this, 'notice_missing_formidable') );
			}
		}
		
		function notice_missing_formidable(){
			?>
			<div class="error">
				<p><?php _e( 'Formidable Not Installed! The plugin "Formidable Honeypot by WPbiz.co" does not work without the Formidable plugin.', 'wpbiz-frm-honeypot' ); ?></p>
			</div>
			<?php
		}

		function add_honeypot($form, $action='', $errors = array()) {
			global $frm_next_page, $frm_vars;

			// Skip captcha if user is logged in
			if ((is_admin() && !defined('DOING_AJAX')) || is_user_logged_in()) {
				return;
			}

			// Skip if there are more pages for this form
			if (! isset($errors['hnypt']) || isset($frm_vars['next_page'][$form->id]) || isset($frm_next_page[$form->id])) {
				return;
			}

			// captcha html
			$captcha_label = __('Please leave this blank', 'wpbiz-frm-honeypot');

			echo <<<HTML
<div id="frm_field_hnypt" class="form-field frm_top_container" style="display: none;">
	<label class="frm_primary_label">$captcha_label</label>
	<input type="text" name="firstname_hnypt" id="firstname_hnypt" value="" />
</div>
HTML;

			if (is_array($errors) && isset($errors['hnypt'])) {
				echo '<div class="frm_error">'. $errors['hnypt'] .'</div>';
			}
		}

		function validate_honeypot($errors, $values = array()) {

			global $frm_next_page, $frm_vars;

			$form_id = isset($values['form_id']) ? $values['form_id'] : false;

			// Skip honeypot if user is logged in and the settings allow
			if ((is_admin() && ! defined('DOING_AJAX')) || is_user_logged_in()) {
				return $errors;
			}

			// Don't require if editing
			$action_var = isset($_REQUEST['frm_action']) ? 'frm_action' : 'action';

			if (isset($values[$action_var]) && $values[$action_var] == 'update') {
				return $errors;
			}

			// Don't require if not on the last page
			if (isset($frm_vars['next_page'][$form_id]) || isset($frm_next_page[$form_id])) {
				return $errors;
			}

			// If the honeypot wasn't included on the page
			if (! isset($_POST['firstname_hnypt'])) {
				return $errors;
			}

			// If captcha not complete, return error
			if (! empty($_POST['firstname_hnypt'])) {
				$errors['hnypt'] = __('Due to suspected SPAM, this form was not submitted. Please do not use auto-fill.', 'wpbiz-frm-honeypot');
			}

			return $errors;
		}
	}
} // End Class

// Instantiating the Class
if (class_exists("WPBizFrmHoneypot")) {
	$WPBizFrmHoneypot = new WPBizFrmHoneypot();
}
