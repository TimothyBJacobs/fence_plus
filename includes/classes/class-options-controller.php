<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */
class Fence_Plus_Options_Controller {
	/**
	 * @var array
	 */
	private $options_fields = array();

	/**
	 * @var Fence_Plus_Options_Controller|null
	 */
	private static $instance = null;

	/**
	 * Private constructor
	 */
	private function __construct() {
		$this->add_fields();
		$this->options_fields = apply_filters( 'fence_plus_options_controller', $this->options_fields );
	}

	/**
	 * @return Fence_Plus_Options_Controller
	 */
	public static function get_instance() {
		if ( self::$instance == null )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Sanitize new options data
	 *
	 * @param $values
	 */
	private function sanitize( $values ) {
		foreach ( $values as $key => $value ) {
			switch ( $key ) {
				case 'club_initials':
					$value = strtoupper( $value );
					break;
				case 'update_interval':
					$value = absint( $value );
					break;
				case 'tournament_distance':
					$value = filter_var( $value, FILTER_VALIDATE_INT, array(
							'options' => array(
								'default'   => $this->options_fields[$key]['default'],
								'min_range' => 1,
								'max_range' => 999
							)
						)
					);
					break;
				case 'public_registration';
				case 'email_fencers_suggested':
					if ( $value == "on" )
						$value = true;
					else
						$value = false;
					break;
			}

			$values[$key] = apply_filters( 'fence_plus_options_sanitize', sanitize_text_field( $value ), $key );
		}

		return $values;
	}

	/**
	 * Save data to options
	 *
	 * @param $values
	 */
	public function save( $values ) {
		$sanitized = $this->sanitize( $values );

		$options = Fence_Plus_Options::get_instance();
		$options->update( $sanitized );
		$options->save();
	}

	/**
	 * Return an array of the default values
	 *
	 * slug => default
	 */
	public function get_defaults() {
		$defaults = array();

		foreach ( $this->get_fields() as $field ) {
			if ( isset( $field['default'] ) )
				$defaults[$field['slug']] = $field['default'];
		}

		return $defaults;
	}

	/**
	 * Get options field
	 *
	 * @return array
	 */
	public function get_fields() {
		return $this->options_fields;
	}

	/**
	 * Add fields to controller
	 */
	private function add_fields() {
		$this->options_fields[] = array(
			'field_type' => 'section_title',
			'title'      => __( 'The Basics', Fence_Plus::SLUG )
		);

		$this->options_fields['api_key'] = array(
			'slug'        => 'api_key',
			'default'     => '',
			'label'       => __( 'askFRED API Key', Fence_Plus::SLUG ),
			'description' => sprintf( __( "In order to use Fence Plus you must have an API key. You can get one from <a href='%s'>askFRED.net</a>.", Fence_Plus::SLUG ),
				'https://sites.google.com/a/countersix.com/fred-rest-api/documentation/developer-access'
			),
			'field_type'  => 'text',
			'field_args'  => array()
		);

		$this->options_fields['public_registration'] = array(
			'slug'        => 'public_registration',
			'default'     => true,
			'label'       => __( 'Public Fencer Registration', Fence_Plus::SLUG ),
			'description' => __( "Allow users to add a USFA ID when they register on your site, automatically converting the user to a fencer if so.", Fence_Plus::SLUG ) . "<br>" .
				__("Requires WordPress \"Anyone Can Register\" option to be on.", Fence_Plus::SLUG ),
			'field_type'  => 'checkbox',
			'field_args'  => array()
		);

		$this->options_fields[] = array(
			'field_type' => 'hr'
		);


		$this->options_fields[] = array(
			'field_type' => 'section_title',
			'title'      => __( 'Advanced Configuration', Fence_Plus::SLUG )
		);

		$this->options_fields['update_interval'] = array(
			'slug'        => 'update_interval',
			'default'     => 24,
			'label'       => __( 'Update interval (hours)', Fence_Plus::SLUG ),
			'description' => __( "How often fencers and tournaments are updated.
				Warning, this can be resource intensive. Recommended setting 24 hours.", Fence_Plus::SLUG
			),
			'field_type'  => 'number',
			'field_args'  => array(
				'min' => 1
			)
		);
	}
}