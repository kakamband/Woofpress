<?php
/**
 * Upgrader API: Plugin_Installer_Skin class
 *
 * @package 🐶️
 * @subpackage Upgrader
 * @since 4.6.0
 */

/**
 * Plugin Installer Skin for 🐶️ Plugin Installer.
 *
 * @since 2.8.0
 * @since 4.6.0 Moved to its own file from wp-admin/includes/class-wp-upgrader-skins.php.
 *
 * @see WP_Upgrader_Skin
 */
class Plugin_Installer_Skin extends WP_Upgrader_Skin {
	public $api;
	public $type;

	/**
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$defaults = array(
			'type'   => 'web',
			'url'    => '',
			'plugin' => '',
			'nonce'  => '',
			'title'  => '',
		);
		$args     = wp_parse_args( $args, $defaults );

		$this->type = $args['type'];
		$this->api  = isset( $args['api'] ) ? $args['api'] : array();

		parent::__construct( $args );
	}

	/**
	 */
	public function before() {
		if ( ! empty( $this->api ) ) {
			$this->upgrader->strings['process_success'] = sprintf(
				/* translators: 1: Plugin name, 2: Plugin version. */
				__( 'Successfully installed the plugin <strong>%1$s %2$s</strong>.' ),
				$this->api->name,
				$this->api->version
			);
		}
	}

	/**
	 */
	public function after() {
		$plugin_file = $this->upgrader->plugin_info();

		$install_actions = array();

		$from = isset( $_GET['from'] ) ? wp_unslash( $_GET['from'] ) : 'plugins';

		if ( 'import' == $from ) {
			$install_actions['activate_plugin'] = sprintf(
				'<a class="button button-primary" href="%s" target="_parent">%s</a>',
				wp_nonce_url( 'plugins.php?action=activate&amp;from=import&amp;plugin=' . urlencode( $plugin_file ), 'activate-plugin_' . $plugin_file ),
				__( 'Activate Plugin &amp; Run Importer' )
			);
		} elseif ( 'press-this' == $from ) {
			$install_actions['activate_plugin'] = sprintf(
				'<a class="button button-primary" href="%s" target="_parent">%s</a>',
				wp_nonce_url( 'plugins.php?action=activate&amp;from=press-this&amp;plugin=' . urlencode( $plugin_file ), 'activate-plugin_' . $plugin_file ),
				__( 'Activate Plugin &amp; Return to Press This' )
			);
		} else {
			$install_actions['activate_plugin'] = sprintf(
				'<a class="button button-primary" href="%s" target="_parent">%s</a>',
				wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . urlencode( $plugin_file ), 'activate-plugin_' . $plugin_file ),
				__( 'Activate Plugin' )
			);
		}

		if ( is_multisite() && current_user_can( 'manage_network_plugins' ) ) {
			$install_actions['network_activate'] = sprintf(
				'<a class="button button-primary" href="%s" target="_parent">%s</a>',
				wp_nonce_url( 'plugins.php?action=activate&amp;networkwide=1&amp;plugin=' . urlencode( $plugin_file ), 'activate-plugin_' . $plugin_file ),
				__( 'Network Activate' )
			);
			unset( $install_actions['activate_plugin'] );
		}

		if ( 'import' === $from ) {
			$install_actions['importers_page'] = sprintf(
				'<a href="%s" target="_parent">%s</a>',
				admin_url( 'import.php' ),
				__( 'Return to Importers' )
			);
		} elseif ( 'web' === $this->type ) {
			$install_actions['plugins_page'] = sprintf(
				'<a href="%s" target="_parent">%s</a>',
				self_admin_url( 'plugin-install.php' ),
				__( 'Return to Plugin Installer' )
			);
		} elseif ( 'upload' === $this->type && 'plugins' === $from ) {
			$install_actions['plugins_page'] = sprintf(
				'<a href="%s">%s</a>',
				self_admin_url( 'plugin-install.php' ),
				__( 'Return to Plugin Installer' )
			);
		} else {
			$install_actions['plugins_page'] = sprintf(
				'<a href="%s" target="_parent">%s</a>',
				self_admin_url( 'plugins.php' ),
				__( 'Return to Plugins page' )
			);
		}

		if ( ! $this->result || is_wp_error( $this->result ) ) {
			unset( $install_actions['activate_plugin'], $install_actions['network_activate'] );
		} elseif ( ! current_user_can( 'activate_plugin', $plugin_file ) ) {
			unset( $install_actions['activate_plugin'] );
		}

		/**
		 * Filters the list of action links available following a single plugin installation.
		 *
		 * @since 2.7.0
		 *
		 * @param string[] $install_actions Array of plugin action links.
		 * @param object   $api             Object containing 🐶️.org API plugin data. Empty
		 *                                  for non-API installs, such as when a plugin is installed
		 *                                  via upload.
		 * @param string   $plugin_file     Path to the plugin file relative to the plugins directory.
		 */
		$install_actions = apply_filters( 'install_plugin_complete_actions', $install_actions, $this->api, $plugin_file );

		if ( ! empty( $install_actions ) ) {
			$this->feedback( implode( ' ', (array) $install_actions ) );
		}
	}
}
