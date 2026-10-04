<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://webdeclic.com/
 * @since      1.0.0
 *
 * @package    Mentions_Legales_Par_Webdeclic
 * @subpackage Mentions_Legales_Par_Webdeclic/admin
 */
class Mentions_Legales_Par_Webdeclic_Settings {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}
	
	/**
	 * Array contains all pages and their settings
	 *
	 * @return array
	 */
	public function menu_pages_array(){

		return array(
			// PAGE FOR SETTINGS OF PLUGIN (is parent page)
			'mentions-legales-par-webdeclic-settings' => array(
				'title' 		=> __( 'Mentions Légales', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
				'capability'	=> 'manage_options',
				'callback'		=> 'render_menu_page',
				'position'		=> 999,
				'icon'			=> 'dashicons-portfolio',
				'tabs'	=> array(
					// SECOND TAB
					'tab-1' => array(
						'title' 	=> __( 'Mes informations', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
						'sections'	=> array(
							'section-introduction' => array(
								'title'		=> __( 'Utilisation du plugin', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
								'callback'	=> 'render_section_introduction'
							),
							'section-0' => array(
								'title'		=> __( 'Date de création des mentions légales', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
								'fields'	=> array(
									'mlw_mentions_legales_date' => array(
										'label'			=> __( 'Date de mise en vigueur', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										'args' 			=> array(
										'type'			=> 'date'
										),
									)
								)
							),
							'section-1' => array(
								'title'		=> __( 'Particulier ou société', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
								'fields'	=> array(
									'mlw_is_society' => array(
										'label'			=> __( 'Êtes vous une société ?', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										'callback'		=> 'render_toggle'
									)
								)
							),
							'section-2' => array(
								'title'		=> __( 'Société', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
								'fields'	=> array(
									'mlw_society_name' => array(
										'label'			=> __( 'Nom de la société', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										'sanitize'		=> 'sanitize_text_field',
										'args'			=> array(
											'placeholder'	=> __( 'Exemple : Apple', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN )
										)
									),
									'mlw_society_social_form' => array(
										'label'			=> __( 'Forme sociale', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										'sanitize'		=> 'sanitize_text_field',
										'args'			=> array(
											'description'	=>  __( 'SAS, SARL, EURL, etc...', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'placeholder'	=> __( 'Exemple : SAS', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN )
										)
									),
									'mlw_society_social_capital' => array(
										'label'			=> __( 'Montant du capital social', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										'sanitize'		=> 'sanitize_text_field',
										'args'			=> array(
											'type'			=> 'number',
											'description'	=>  __( 'En euros', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'placeholder'	=>  __( 'Exemple : 1000', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN )
											)
										),
									'mlw_society_immatriculation_city' => array(
										'label'			=> __( "Ville d'immatriculation au RCS", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										'sanitize'		=> 'sanitize_text_field',
										'args'			=> array(
											'placeholder'	=> __( 'Exemple : Paris', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN )
										)
									),
									'mlw_society_siren' => array(
										'label'			=> __( "Numéro SIREN (c'est un numéro à 9 chiffres)", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										'sanitize'		=> 'sanitize_text_field',
										'args'			=> array(
											'type'			=> 'number',
											'placeholder'	=> __( 'Exemple : 123456789', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'min'			=> 100000000,
											'max'			=> 999999999,
										)
									),
									'mlw_society_address' => array(
										'label'			=> __( "Adresse complète du siège social", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										'sanitize'		=> 'sanitize_text_field',
										'args'			=> array(
											'placeholder'	=> __( 'Exemple : 1 Av. des Champs-Élysées 75008 Paris', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'description'	=> __( "N° et nom de la voie, code postal et ville", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN )
										)
									),
									'mlw_society_phone_number' => array(
										'label'			=> __( "Numéro de téléphone", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										'sanitize'		=> 'sanitize_text_field',
										'args'			=> array(
											'type'			=> 'tel',
											'placeholder'	=> __( 'Exemple : +33612345678', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										)
									),
									'mlw_society_email' => array(
										'label'			=> __( "Adresse e-mail", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										'sanitize'		=> 'sanitize_email',
										'args'			=> array(
											'type'			=> 'email',
											'placeholder'	=> __( 'Exemple : hello@exemple.com', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										)
									),
									'mlw_society_vat_number' => array(
										'label'			=> __( "Numéro de TVA intracommunautaire", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										'sanitize'		=> 'sanitize_text_field',
										'args'			=> array(
											'placeholder'	=> __( 'Exemple :  FR 32 123456789', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										)
									),
									'mlw_society_first_and_last_name_director_of_publication' => array(
										'label'			=> __( "Nom et prénom du directeur de la publication", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										'sanitize'		=> 'sanitize_text_field',
										'args'			=> array(
											'placeholder'	=> __( 'Exemple :  Dupont Jean', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
										)
									),
								)
								),
								'section-3' => array(
									'title'		=> __( 'Particulier', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
									'fields'	=> array(
										'mlw_personnal_first_and_last_name' => array(
											'label'			=> __( "Nom et prénom de la personne en charge de l'édition et de la publication", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'sanitize'		=> 'sanitize_text_field',
											'args'			=> array(
												'placeholder'	=> __( 'Exemple : Dupont Jean', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN )
											)
										),
										'mlw_personnal_address' => array(
											'label'			=> __( "Adresse complète de domiciliation", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'sanitize'		=> 'sanitize_text_field',
											'args'			=> array(
												'placeholder'	=> __( 'Exemple : 1 Av. des Champs-Élysées 75008 Paris', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
												'description'	=> __( "N° et nom de la voie, code postal et ville", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN )
											)
										),
										'mlw_personnal_phone_number' => array(
											'label'			=> __( "Numéro de téléphone", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'sanitize'		=> 'sanitize_text_field',
											'args'			=> array(
												'type'			=> 'tel',
												'placeholder'	=> __( 'Exemple : +33612345678', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											)
										),
										'mlw_personnal_email' => array(
											'label'			=> __( "Adresse e-mail", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'sanitize'		=> 'sanitize_email',
											'args'			=> array(
												'type'			=> 'email',
												'placeholder'	=> __( 'Exemple : hello@exemple.com', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											)
										),
									)
								),
								'section-4' => array(
									'title'		=> __( 'Votre hébergeur', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
									'fields'	=> array(
										'mlw_host_name' => array(
											'label'			=> __( "Dénomination sociale de l'hébergeur", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'sanitize'		=> 'sanitize_text_field',
											'args'			=> array(
												'placeholder'	=> __( 'Exemple : OVH SAS', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											)
										),
										'mlw_host_address' => array(
											'label'			=> __( "Adresse du siège social de l'hébergeur", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'sanitize'		=> 'sanitize_text_field',
											'args'			=> array(
												'placeholder'	=> __( 'Exemple : 2 rue Kellermann – BP 80157 59053 ROUBAIX CEDEX 1', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											)
										),
										'mlw_host_phone_number' => array(
											'label'			=> __( "Numéro de téléphone de l'hébergeur", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'sanitize'		=> 'sanitize_text_field',
											'args'			=> array(
												'placeholder'	=> __( 'Exemple : 09 72 10 10 07', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											)
										),
									)
								),
								'section-5' => array(
									'title'		=> __( 'Collecte de données', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
									'fields'	=> array(
										'mlw_data_collected' => array(
											'label'			=> __( 'Le site internet collecte-il des données personnelles ?', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'sanitize'		=> 'sanitize_text_field',
											'callback'		=> 'render_toggle'
										),
										'mlw_data_collected_revoque_by_email' => array(
											'label'			=> __( "L'utilisateur peut modifier ou supprimer ces données par email", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'sanitize'		=> 'sanitize_email',
											'args'			=> array(
												'type'			=> 'email',
												'placeholder'	=> __( 'Exemple : hello@exemple.com', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
												'description'	=> __( "Laisser vide pour ne pas utiliser", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											)
										),
										'mlw_data_collected_revoque_by_poste' => array(
											'label'			=> __( "L'utilisateur peut modifier ou supprimer ces données par voie postale", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'sanitize'		=> 'sanitize_text_field',
											'args'			=> array(
												'placeholder'	=> __( 'Exemple : 1 Av. des Champs-Élysées 75008 Paris', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
												'description'	=> __( "Laisser vide pour ne pas utiliser", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											)
										),
										'mlw_data_collected_revoque_by_contact_form' => array(
											'label'			=> __( "L'utilisateur peut modifier ou supprimer ces données par formulaire de contact", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'sanitize'		=> 'sanitize_url',
											'args'			=> array(
												'type'			=> 'url',
												'placeholder'	=> __( 'Exemple : https://exemple.com/contact/', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
												'description'	=> __( "Laisser vide pour ne pas utiliser", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											)
										),
										'mlw_data_collected_revoque_by_personnal_space' => array(
											'label'			=> __( "L'utilisateur peut modifier ou supprimer ces données via l'espace personnel", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											'sanitize'		=> 'sanitize_url',
											'args'			=> array(
												'type'			=> 'url',
												'placeholder'	=> __( 'Exemple : https://exemple.com/my-account/', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
												'description'	=> __( "Laisser vide pour ne pas utiliser", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
											)
										),
									)
								),
								'section-credits' => array(
									'title'		=> __( 'Crédits', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ),
									'callback'	=> 'render_section_credits',
								),
						)
					)
				)
			)
		);
	}
		
	/**
	 * Add settings menus
	 *
	 * @return void
	 */
	public function add_settings_menu() {

		foreach ( $this->menu_pages_array() as $page_slug => $page_data ) {

			$this->create_menu_page( $page_slug, $page_data );

			if ( !isset( $page_data['tabs'] ) ) continue;

			foreach ( $page_data['tabs'] as $tab_slug => $tab_data ) {

				if ( !isset( $tab_data['sections'] ) ) continue;

				foreach ( $tab_data['sections'] as $section_slug => $section_data ) {
					
					$this->create_section( $tab_slug, $section_slug, $section_data );

					if ( !isset( $section_data['fields'] ) ) continue;

					foreach ( $section_data['fields'] as $field_slug => $field_data ) {

						$this->create_field( $tab_slug, $section_slug, $field_slug, $field_data );
					}
				}
			}
		}

	}
	
	/**
	 * Create a menu page
	 *
	 * @return void
	 */
	public function create_menu_page( $page_slug, $page_data ) {

		$page_title			= isset( $page_data['title'] ) ? $page_data['title'] : '';
		$page_parent		= isset( $page_data['parent'] ) ? $page_data['parent'] : '';
		$page_capability	= isset( $page_data['capability'] ) ? $page_data['capability'] : 'manage_options';
		$page_callback		= isset( $page_data['callback'] ) ? $page_data['callback'] : 'render_menu_page';
		$page_icon			= isset( $page_data['icon'] ) ? $page_data['icon'] : '';
		$page_position		= isset( $page_data['position'] ) ? $page_data['position'] : null;

		if ( empty( $page_parent ) ) {

			add_menu_page( $page_title, $page_title, $page_capability, $page_slug, array( $this, $page_callback ), $page_icon, $page_position );

		} else {

			add_submenu_page( $page_parent, $page_title, $page_title, $page_capability, $page_slug, array( $this, $page_callback ), $page_position );
		}
	}
	
	/**
	 * Create a section
	 *
	 * @return void
	 */
	public function create_section( $tab_slug, $section_slug, $section_data ) {

		$section_callback	= isset( $section_data['callback'] ) ? array( $this, $section_data['callback'] ) : '__return_empty_string';
		$section_title		= isset( $section_data['title'] ) ? $section_data['title'] : '';

		add_settings_section( $section_slug, $section_title, $section_callback, $tab_slug );
	}
	
	/**
	 * Create a field
	 *
	 * @return void
	 */
	public function create_field( $tab_slug, $section_slug, $field_slug, $field_data ) {

		$field_label			= isset( $field_data['label'] ) ? $field_data['label'] : '';
		$field_callback			= isset( $field_data['callback'] ) ? $field_data['callback'] : 'render_input';
		$fields_args 			= isset( $field_data['args'] ) ? $field_data['args'] : array();
		$fields_args['slug']	= $field_slug;
		$field_sanitize			= isset( $field_data['sanitize'] ) ? array( $this, $field_data['sanitize'] ) : array();
		
		add_settings_field( $field_slug, $field_label, array( $this, $field_callback ), $tab_slug, $section_slug, $fields_args );
		register_setting( $tab_slug, $field_slug, $field_sanitize );
	}

	/**
	 * Render menu page
	 *
	 * @return void
	 */
	public function render_menu_page() {

		wp_enqueue_style( MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN . '-settings-style', MENTIONS_LEGALES_PAR_WEBDECLIC_PLUGIN_URL . 'admin/css/page-settings.css', array(), MENTIONS_LEGALES_PAR_WEBDECLIC_VERSION, 'all' );
		wp_enqueue_script( MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN . '-settings-script', MENTIONS_LEGALES_PAR_WEBDECLIC_PLUGIN_URL . 'admin/js/page-settings.js', array(), MENTIONS_LEGALES_PAR_WEBDECLIC_VERSION, 'all' );
		
		// passe la tab active dans le script
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'tab-1';
		wp_localize_script( MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN . '-settings-script', 'php_data', array( 
			'active_tab' =>	$active_tab
		) );

		$page_slug		= isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$pages_array	= $this->menu_pages_array();
		$tabs_array 	= isset( $pages_array[$page_slug]['tabs'] ) ? $pages_array[$page_slug]['tabs'] : array();
		$page_title		= isset( $pages_array[$page_slug]['title'] ) ? $pages_array[$page_slug]['title'] : '';
		$active_tab		= isset( $_GET[ 'tab' ] ) ? sanitize_text_field( $_GET[ 'tab' ] ) : key( $tabs_array );

		?>
			<div class="wrap">
				<h2><?php echo esc_html( $page_title ); ?></h2>
				<?php settings_errors(); ?>
				
				<?php if ( count( $tabs_array ) > 1 ): ?>

					<h2 class="nav-tab-wrapper">
						<?php foreach( $tabs_array as $tab_key => $tab_data ): ?>
							<a href="?page=<?php echo esc_attr( $page_slug ); ?>&tab=<?php echo esc_attr( $tab_key ); ?>" class="nav-tab <?php echo esc_attr( $tab_key == $active_tab ? 'nav-tab-active' : '' ); ?>"><?php echo esc_attr( isset( $tab_data['title'] ) ?  $tab_data['title'] : '?' ); ?></a>
						<?php endforeach; ?>
					</h2>

				<?php endif; ?>

				<form method="post" action="options.php">
					<?php
						
						settings_fields( $active_tab );
						do_settings_sections( $active_tab );

						submit_button();
					?>
				</form>
			</div>
		<?php

	}
	
	/**
	 * Render input field
	 *
	 * @return void
	 */
	public function render_input( $args ) {

		$field_slug		= isset( $args['slug'] ) ? $args['slug'] : '';
		$type			= isset( $args['type'] ) ? $args['type'] : 'text';
		$placeholder	= isset( $args['placeholder'] ) ? $args['placeholder'] : '';
		$description	= isset( $args['description'] ) ? $args['description'] : '';
		$min			= isset( $args['min'] ) ? $args['min'] : '';
		$max			= isset( $args['max'] ) ? $args['max'] : '';
		$saved_value	= get_option( $field_slug );

		?>
			<input type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $field_slug ); ?>" id="<?php echo esc_attr( $field_slug ); ?>" value="<?php echo esc_attr( $saved_value ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $placeholder ); ?>" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>">
			<?php if ( !empty( $description ) ): ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		<?php
	}
	
	/**
	 * Render select field
	 *
	 * @return void
	 */
	public function render_select( $args ) {

		$field_slug		= isset( $args['slug'] ) ? $args['slug'] : '';
		$field_default	= isset( $args['default'] ) ? $args['default'] : '';
		$field_options	= isset( $args['options'] ) ? $args['options'] : array();
		$description	= isset( $args['description'] ) ? $args['description'] : '';
		$saved_value	= get_option( $field_slug );

		?>
			<select name="<?php echo esc_attr( $field_slug ); ?>" id="<?php echo esc_attr( $field_slug ); ?>">
				<?php if ( !empty( $field_default ) ): ?>
					<option value=""><?php echo esc_attr( $field_default ); ?></option>
				<?php endif; ?>
				<?php foreach( $field_options as $option_value => $option_label ): ?>
					<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $option_value, $saved_value ); ?> ><?php echo esc_html( $option_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php if ( !empty( $description ) ): ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		<?php
	}
	
	/**
	 * Render textarea field
	 *
	 * @return void
	 */
	public function render_textarea( $args ) {

		$field_slug		= isset( $args['slug'] ) ? $args['slug'] : '';
		$placeholder	= isset( $args['placeholder'] ) ? $args['placeholder'] : '';
		$description	= isset( $args['description'] ) ? $args['description'] : '';
		$saved_value	= get_option( $field_slug );

		?>
			<textarea name="<?php echo esc_attr( $field_slug ); ?>" id="<?php echo esc_attr( $field_slug ); ?>" class="large-text" rows="3" placeholder="<?php echo esc_attr( $placeholder ); ?>"><?php echo esc_textarea( $saved_value ); ?></textarea>
			<?php if ( !empty( $description ) ): ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		<?php
	}
	
	/**
	 * Render checkbox field
	 *
	 * @return void
	 */
	public function render_checkbox( $args ) {

		$field_slug		= isset( $args['slug'] ) ? $args['slug'] : '';
		$description	= isset( $args['description'] ) ? $args['description'] : '';
		$saved_value	= get_option( $field_slug );

		?>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $field_slug ); ?>" id="<?php echo esc_attr( $field_slug ); ?>" <?php checked( 'on', $saved_value ); ?>>
				<?php echo !empty( $description ) ? $description : ''; ?>
			</label>
		<?php
	}
	
	/**
	 * Render radio field
	 *
	 * @return void
	 */
	public function render_radio( $args ) {

		$field_slug		= isset( $args['slug'] ) ? $args['slug'] : '';
		$field_options	= isset( $args['options'] ) ? $args['options'] : array();
		$description	= isset( $args['description'] ) ? $args['description'] : '';
		$saved_value	= get_option( $field_slug );

		?>
			<fieldset>
				<?php foreach( $field_options as $option_value => $option_label ): ?>
					<label>
						<input type="radio" name="<?php echo esc_attr( $field_slug ); ?>" value="<?php echo esc_attr( $option_value ); ?>" <?php checked( $option_value, $saved_value ); ?>>
						<span><?php echo esc_html( $option_label ); ?></span>
					</label><br>
				<?php endforeach; ?>
				<?php if ( !empty( $description ) ): ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</fieldset>
		<?php
	}

	/**
	 * Render toggle field
	 *
	 * @return void
	 */
	public function render_toggle( $args ) {

		$field_slug		= isset( $args['slug'] ) ? $args['slug'] : '';
		$description	= isset( $args['description'] ) ? $args['description'] : '';
		$saved_value	= get_option( $field_slug );

		?>
			<label class="switch">
				<input type="checkbox" name="<?php echo esc_attr( $field_slug ); ?>" id="<?php echo esc_attr( $field_slug ); ?>" <?php checked( 'on', $saved_value ); ?>>
				<span class="slider round"></span>
			</label>
			<?php if ( !empty( $description ) ): ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		<?php
	}
	
	/**
	 * render_section_introduction
	 *
	 * @return void
	 */
	public function render_section_introduction() {
		?>
			<p><?php _e( 'Pour utiliser le plugin, vous devez utiliser le shortcode suivant :', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ); ?></p>
			<p><code>[wbd_mentions_legales]</code></p>
		<?php
	}
		
	/**
	 * render_section_credits
	 *
	 * @return void
	 */
	public function render_section_credits() {
		?>
			<p>
				<?php _e( "Ce plugin est développé par", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ); ?>
				<a href="https://webdeclic.com/" target="_blank">Webdeclic</a>.
				<?php _e( "Vous pouvez soutenir ce projet ici:", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ); ?>
			</p>
			<p>
				<a class="buymeacoffee" href="https://www.buymeacoffee.com/ludwig" target="_blank">
					<img src="<?php echo esc_url( MENTIONS_LEGALES_PAR_WEBDECLIC_PLUGIN_URL . 'public/images/buy-me-a-coffee.webp' ); ?>" alt="Buy Me A Coffee" style="max-height: 60px;">
				</a>
			</p>
			<p>
				<?php _e( "Vous pouvez afficher tous les plugins de WebDeclic sur", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ); ?>
				<a href="https://wordpress.org/plugins/search/webdeclic/" target="_blank"><?php _e( "wordpress.org", MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ); ?></a>.
			</p>
		<?php
	}

		
	/**
	 * add_settings_link
	 *
	 * @param  mixed $links
	 * @return void
	 */
	public function add_settings_link( $links ){
		$url = esc_url( add_query_arg(
			'page',
			'mentions-legales-par-webdeclic-settings',
			get_admin_url() . 'options-general.php'
		) );
		
		$links[] = "<a href='$url'>" . __( 'Réglages', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ) . '</a>';
		$links[] = '<a href="https://buymeacoffee.com/ludwig" target="_blank">' . __( 'Contribuer', MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN ) . '</a>';

		return $links;
	}
}