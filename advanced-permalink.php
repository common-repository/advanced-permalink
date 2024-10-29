<?php
/*
Plugin Name:	Advanced Permalink
Description:	Manage Permalinks off all posts
Author: 		Mingocommerce
Author URI:		http://www.mingocommerce.com
Text Domain: 	advanced-permalink
Domain Path: 	/i18n/
Version: 		1.0.2
*/
class MNG_Post_Permalink{
	
	var $wp_rewrite;
	
	function __construct(){
		add_action( 'plugins_loaded', array($this, 'load_text_domain'));
		add_action( 'admin_menu', array($this, 'admin_menu'));
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action('init', array($this, 'init'));
	}
	
	function load_text_domain(){
		load_plugin_textdomain( 'advanced-permalink', false, basename( dirname( __FILE__ ) ) . '/i18n/' ); 
	}
	function init(){
		global $wp_rewrite;
		$this->wp_rewrite	=	$wp_rewrite;
		$this->permalinks['custom_post_type'] = get_option( 'mng_post_type_permalinks' );
		$this->permalinks['page'] = get_option( 'mng_page_permalinks' );
		$this->rewrite_rules();
	}
	
	function rewrite_rules(){
		global $wp_rewrite;
		if(is_array($this->permalinks)){
			foreach($this->permalinks as $field_type => $fields){
				
				switch($field_type){
					
					case 'custom_post_type': default:
						if(is_array($fields)){
							foreach($fields as $field_key => $field_val){
								if(!empty($field_val)){
									$wp_rewrite->extra_permastructs[$field_key]['struct']	=	$wp_rewrite->root.$field_val;
								}
							}
						}
						break;
						
					case 'page':
						$wp_rewrite->page_structure = $wp_rewrite->root.$fields;
						break;
				}
			}
		}
	}
	
	function admin_menu(){
		add_options_page( __('Advanced Permalinks', 'advanced-permalink'), __('Advanced Permalinks', 'advanced-permalink'), 'manage_options', 'post-type-permalink', array($this, 'settings_page') );
	}
	
	function settings_page(){
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' , 'advanced-permalink' ) );
		}
		echo '<div class="wrap">';
		echo '<h1>'.__('Advanced Permalink Settings', 'advanced-permalink').'</h1>';
		?>
		<form method="post" action="options.php">
		<?php
			// This prints out all hidden setting fields
			settings_fields( 'mng_permalink_group' );
			do_settings_sections( 'mng-permalinnk-settings' );
			submit_button();
		?>
		</form>
		<?php
		echo '</div>';
	}
	
	function get_custom_post_types(){
		$args	=	array(
			'public'	=>	true,
			'_builtin'	=>	false,
		);
		return get_post_types($args);
	}
	
	function register_settings(){
		register_setting(
            'mng_permalink_group', // Option group
            'mng_page_permalinks', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );
		register_setting(
            'mng_permalink_group', // Option group
            'mng_post_type_permalinks', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );
		
		add_settings_section(
            'mng_permalink_settings_sec1', // ID
            __('Common Links', 'advanced-permalink'), // Title
            '', //array( $this, 'print_section_info' ), // Callback
            'mng-permalinnk-settings' // Page
        );
		
		add_settings_field(
				'page_link', // ID
				__('Page', 'advanced-permalink'), // Title 
				array( $this, 'get_perma_page_field'), // Callback
				'mng-permalinnk-settings', // Page
				'mng_permalink_settings_sec1' // Section
			);
		
        add_settings_section(
            'mng_permalink_settings_sec2', // ID
            __('Post Type Links', 'advanced-permalink'), // Title
            '', //array( $this, 'print_section_info' ), // Callback
            'mng-permalinnk-settings' // Page
        );  
		foreach($this->get_custom_post_types() as $post_type){
			add_settings_field(
				$post_type, // ID
				$post_type, // Title 
				array( $this, 'get_perma_posttype_field'), // Callback
				'mng-permalinnk-settings', // Page
				'mng_permalink_settings_sec2', // Section
				array('field_name'	=>	$post_type , 'field_type' => 'custom_post_type')
			);
		}
	}
	
	function sanitize($input){
		// Do nothing right now
		return $input;
	}
	
	function get_perma_posttype_field($arg){		//pre($this->wp_rewrite);	
		$field_name	=	$arg['field_name'];
		$field_type	=	$arg['field_type'];
		
		$default_perma	=	$this->get_default_permalink_value($field_type, $field_name);
		$new_perma		=	$this->get_new_permalink_value($field_type, $field_name);
		$current_perma	=	$new_perma ? $new_perma : $default_perma;
		
		printf(
            '<input type="text" name="mng_post_type_permalinks[%s]" value="%s" size="40" />',$field_name, $current_perma
        );
	}
	
	function get_perma_page_field(){
		$old_value	='';
		
		$default_value	=	$this->get_default_permalink_value('page');
		$new_value		=	$this->get_new_permalink_value('page');
		$current_value	=	$new_value ? $new_value : $default_value;
		
		printf(
            '<input type="text" name="mng_page_permalinks" value="%s" size="40" />', $current_value
        );
	}
	
	function get_default_permalink_value($field_type, $field_name=''){
		$perm	=	'';
		switch($field_type){
			case 'custom_post_type': default:
				$perm	=	array_key_exists($field_name, $this->wp_rewrite->extra_permastructs) ? $this->wp_rewrite->extra_permastructs[$field_name]['struct'] : 'xyz';
				break;
				
			case 'page':
				$perm	=	property_exists($this->wp_rewrite, 'page_structure') ? $this->wp_rewrite->page_structure : '';
				break;
		}
		return $perm;
	}
	
	function get_new_permalink_value($field_type, $field_name=''){
		switch($field_type){
			case 'custom_post_type':
				if(isset($this->permalinks[$field_type][$field_name]) && !empty($this->permalinks[$field_type][$field_name])){
					return $this->permalinks[$field_type][$field_name];
				}
				break;
				
			default:
				if(isset($this->permalinks[$field_type]) && !empty($this->permalinks[$field_type])){
					return $this->permalinks[$field_type];
				}
				break;
		}
		return false;
	}
}

new MNG_Post_Permalink();function pre($data){print '<pre>';print_r($data);print '</pre>';}
