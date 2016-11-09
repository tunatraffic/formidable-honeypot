<?php
/*
Plugin Name: Formidable Honeypot
Plugin URI: http://wpbiz.co/
Description: Add invisible SPAM protection to your Formidable forms.
Author: Ryan Pilling
Version: 0.2
Author URI: http://wpbiz.co/
*/
	
// Define our plugin's wrapper class
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

		function add_honeypot($form, $action='', $errors=''){
		    //insert honeypot
		    global $frm_next_page, $frm_vars;

		    // skip captcha if user is logged in
			if ( (is_admin() and !defined('DOING_AJAX')) or ( is_user_logged_in() ))
			    return;
			   
			//skip if there are more pages for this form  
			if( ( is_array($errors) and !isset( $errors['hnypt'] ) ) or (is_array($frm_vars) and isset($frm_vars['next_page']) and isset($frm_vars['next_page'][$form->id])) or (is_array($frm_next_page) and isset($frm_next_page[$form->id])))
				return;

			// captcha html
			echo '<div id="frm_field_hnypt" class="form-field  frm_top_container" style="display: none;">';
				
				echo '<label class="frm_primary_label">'. _e( 'Please leave this blank', 'wpbiz-frm-honeypot' ) .'</label>';

		    	echo '<input type="text" name="firstname_hnypt" id="firstname_hnypt" value="" />';
		

			echo '</div>';

			if( is_array($errors) and isset( $errors['hnypt'] ) )
				echo '<div class="frm_error">'. $errors['hnypt'] .'</div>';


		}

		function validate_honeypot($errors, $values=''){

			// skip hpneypot if user is logged in and the settings allow
			if ( (is_admin() and !defined('DOING_AJAX')) or is_user_logged_in() )
				return $errors;
		        
		    //don't require if editing
		    $action_var = isset($_REQUEST['frm_action']) ? 'frm_action' : 'action';
		  	if(isset($values[$action_var]) and $values[$action_var] == 'update')
		  		return $errors;
		  	unset($action_var);
		  	
		  	//don't require if not on the last page
			global $frm_next_page, $frm_vars;
			if((is_array($frm_vars) and isset($frm_vars['next_page']) and isset($frm_vars['next_page'][$values['form_id']])) or (is_array($frm_next_page) and isset($frm_next_page[$values['form_id']])))
				return $errors;
		  		
		  	//if the honeypot wasn't incuded on the page
			if(!isset($_POST['firstname_hnypt'])){
		        return $errors;
		    }
		  	

		  	// If captcha not complete, return error
		  	if ( $_POST['firstname_hnypt'] != '' ) 	
		  		$errors['hnypt'] = __( 'Due to suspected SPAM, this form was not submitted. Please do not use auto-fill.', 'wpbiz-frm-honeypot' );
		  			  

		    return $errors;
		}
	}
} // End Class

// Instantiating the Class
if (class_exists("WPBizFrmHoneypot")) {
	$WPBizFrmHoneypot = new WPBizFrmHoneypot();
}

if(!function_exists('_log')){
  function _log( $message ) {
    if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}