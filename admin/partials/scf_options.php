<?php

class SCFOptions {


	private $options;
	private $fields;
	private $defaultFields;
	private $table_name;
	private $wpdb;
	

	function __construct() {

		$this->options = array();

		$this->fields = array(
			'form' 					=> '1' ,
			'send_to' 				=> '' ,
			'form_title' 			=> 'Enquire now!' ,
			'email_subject' 		=> 'Website Enquiry' ,
			'email_recipients'		=> get_bloginfo('admin_email'),
			'form_styling' 			=> 'bootstrap' ,
			'include_bootstrap' 	=> '0' ,
			'include_fontawesome' 	=> '0' ,
			'submit_class' 			=> 'btn-primary' ,
			'success_msg' 			=> '<h2 style="text-align: center;">Thanks for completing the form!</h2><p>We will be in touch shortly.</p>' ,
			'validation'			=> 'recaptcha' ,
			'include_recaptcha'		=> '0' ,
			'display_button' 		=> '0' ,
			'default_collapse'		=> '0' ,
			'button_text'			=> 'Get in touch!' ,
			'button_class'			=> 'btn-primary' ,
			'button_icon_side'		=> 'left' ,
			'button_icon'			=> 'fa-comments' ,
			'recaptcha_public'		=> '' ,
			'recaptcha_private'		=> '' ,
		);

		$this->defaultFields = array(
    		'1' => array(
	            'label' => 'Email Address',
	            'type' => 'email',
	            'options' => array(),
	            'required' => 1,
	            'exclude' => false
        	),
		    '2' => array(
	            'label' => 'Enquiry',
	            'type' => 'textarea',
	            'options' => array(),
	            'required' => 1,
	            'exclude' => false
	        )
		);
		
	    global $wpdb;
	    $this->wpdb = &$wpdb;

	}



	private function getif($slug = '', $default = '') {

		if( get_option( 'scf_' . $slug, false ) === false && get_option( 'scf_' . $slug, false ) !== '0') {

			// Set the value for the very first time
			update_option('scf_' . $slug, $default);

		};

		// Get the option from the database
		$option = get_option( 'scf_' . $slug , $default );

		// Check if it's the table and it's not a string
		if( $slug === 'table_fields' && !is_array($option) ) $option = maybe_unserialize($option);

		// Return
		return $option;

	}



	private function updateif($slug = '', $default = '') {

		// Check if it's been submitted and pass the value or the default (if the field has been removed for some reason)
		$newval = (isset($_POST[$slug]) ? $_POST[$slug] : $default);

		// Make sure it's a valid input and do the checking and setting
		if( array_key_exists($slug, $this->fields) && $newval != $this->getif($slug) && isset($_POST[$slug])) {
			
			update_option('scf_' . $slug, stripslashes(wp_filter_post_kses(addslashes($newval))) );

		}

	}



	public function delete() {

		// For each field above
		foreach($this->fields as $field => $default) {

			delete_option('scf_' . $field);

		};

		// Delete the fields
		delete_option('scf_table_fields');

		// Delete the database version
		delete_option('scf_db_version');

	}



	public function set() {

		// Set each field that comes back
		foreach($this->fields as $field => $default) {

			$this->updateif($field, $default);

		};

		// Check if the fields were submitted in the form
		if(isset($_POST['fields'])) {

			$fields = array();
			$r = 1;

			// Set the table array
			foreach($_POST['fields'] as $field) {

				$fields[$r]['label'] = $field['label'];

				$fields[$r]['type'] = $field['type'];
				
				$fields[$r]['options'] = (isset($field['options']) ? $field['options'] : array() );
				
				$fields[$r]['required'] = (isset($field['required']) ? '1' : '0');
				
				$fields[$r]['exclude'] = (isset($field['exclude']) ? '1' : '0');

				$r++;

			}

			// Add the table fields to the database
			if( isset($fields) ) {

				update_option('scf_table_fields', $fields );

			}

		}

		if(isset($_POST['delete_completion'])) {

			$id = $_POST['delete_completion'];

			if(!is_numeric($id)) return false;

		   	$completions_cl = new SCF_Data_Management;
		   	$this->table_name = $completions_cl->table;

			$this->wpdb->delete(
				$this->table_name,
				array(
					'id' => $id
				)
			);

		}

	}



	public function get() {

		// Get each option
		foreach($this->fields as $field => $default) {

			$this->options[$field] = $this->getif( $field, $default );

		}
			
		// Get or set the fields
		$this->options['fields'] = $this->getif('table_fields', $this->defaultFields );

		return $this->options;

	}

	

}