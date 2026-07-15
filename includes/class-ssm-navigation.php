<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Navigation {
	const MENU_LOCATION = 'ssm-global-sections';
	const OPTION_THEME_MENU_LOCATION = 'ssm_theme_menu_location';
	const OPTION_HOME_MENU_ID = 'ssm_home_menu_id';
	const SECTION_MENU_META_KEY = '_ssm_nav_menu_id';

	/**
	 * @var SSM_Content
	 */
	private $content;

	public function __construct( SSM_Content $content ) {
		$this->content = $content;
	}

	public function register_menu_location() {
		register_nav_menu( self::MENU_LOCATION, __( 'Site Section Global Menu', 'site-section-manager' ) );
	}

	public function get_available_theme_locations() {
		$locations = get_registered_nav_menus();
		unset( $locations[ self::MENU_LOCATION ] );

		return $locations;
	}

	public function get_selected_theme_location() {
		$location = (string) get_option( self::OPTION_THEME_MENU_LOCATION, '' );
		if ( self::MENU_LOCATION === $location ) {
			return '';
		}

		return $location;
	}

	public function update_selected_theme_location( $location ) {
		$location = sanitize_key( (string) $location );
		$choices  = $this->get_available_theme_locations();

		if ( '' !== $location && ! isset( $choices[ $location ] ) ) {
			$location = '';
		}

		update_option( self::OPTION_THEME_MENU_LOCATION, $location, false );
	}

	public function get_home_menu_id() {
		$menu_id = (int) get_option( self::OPTION_HOME_MENU_ID, 0 );
		$menu    = $menu_id ? wp_get_nav_menu_object( $menu_id ) : false;

		if ( $menu && ! is_wp_error( $menu ) ) {
			$this->sync_menu_name( $menu->term_id, $this->get_home_menu_name() );
			return (int) $menu->term_id;
		}

		$menu_id = wp_create_nav_menu( $this->get_home_menu_name() );
		if ( is_wp_error( $menu_id ) ) {
			return 0;
		}

		update_option( self::OPTION_HOME_MENU_ID, (int) $menu_id, false );
		return (int) $menu_id;
	}

	public function get_section_menu_id( $section_id, $section_title = '' ) {
		$menu_id = (int) get_post_meta( $section_id, self::SECTION_MENU_META_KEY, true );
		$menu    = $menu_id ? wp_get_nav_menu_object( $menu_id ) : false;
		$name    = $this->get_section_menu_name( $section_id, $section_title ? $section_title : get_the_title( $section_id ) );

		if ( $menu && ! is_wp_error( $menu ) ) {
			$this->sync_menu_name( $menu->term_id, $name );
			return (int) $menu->term_id;
		}

		$menu_id = wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			return 0;
		}

		update_post_meta( $section_id, self::SECTION_MENU_META_KEY, (int) $menu_id );
		return (int) $menu_id;
	}

	public function get_menu_edit_url( $menu_id ) {
		if ( ! $menu_id ) {
			return admin_url( 'nav-menus.php' );
		}

		return add_query_arg(
			array(
				'action' => 'edit',
				'menu'   => (int) $menu_id,
			),
			admin_url( 'nav-menus.php' )
		);
	}

	public function get_menu_items( $menu_id ) {
		$items = wp_get_nav_menu_items(
			$menu_id,
			array(
				'orderby' => 'menu_order',
				'order'   => 'ASC',
			)
		);

		return is_array( $items ) ? $items : array();
	}

	public function should_render_fallback_header() {
		return '' === $this->get_selected_theme_location();
	}

	public function filter_nav_menu_args( $args ) {
		if ( is_admin() || ! isset( $args['theme_location'] ) ) {
			return $args;
		}

		$selected_location = $this->get_selected_theme_location();
		if ( '' === $selected_location || $selected_location !== $args['theme_location'] ) {
			return $args;
		}

		$menu_id = $this->get_current_section_menu_id();
		if ( ! $menu_id ) {
			return $args;
		}

		$args['menu'] = $menu_id;

		return $args;
	}

	public function filter_nav_menu_items( $items, $args ) {
		if ( is_admin() || ! isset( $args->theme_location ) ) {
			return $items;
		}

		if ( self::MENU_LOCATION !== $args->theme_location ) {
			return $items;
		}

		return $this->build_menu_items_markup();
	}

	private function build_menu_items_markup() {
		$current_section_id = $this->get_current_section_id();
		$items              = $this->content->get_section_navigation_items();
		$markup             = '';

		foreach ( $items as $item ) {
			$classes = array(
				'menu-item',
				'menu-item-type-custom',
				'menu-item-object-custom',
				'ssm-menu-item',
			);

			if ( (int) $current_section_id === (int) $item['section_id'] ) {
				$classes[] = 'current-menu-item';
				$classes[] = 'current_page_item';
			}

			$markup .= sprintf(
				'<li class="%1$s"><a href="%2$s">%3$s</a></li>',
				esc_attr( implode( ' ', $classes ) ),
				esc_url( $item['url'] ),
				esc_html( $item['section_title'] )
			);
		}

		return $markup;
	}

	private function sync_menu_name( $menu_id, $name ) {
		$menu = wp_get_nav_menu_object( $menu_id );
		if ( ! $menu || is_wp_error( $menu ) || $menu->name === $name ) {
			return;
		}

		wp_update_nav_menu_object(
			$menu_id,
			array(
				'menu-name' => $name,
			)
		);
	}

	private function get_home_menu_name() {
		return __( 'Home Section Menu', 'site-section-manager' );
	}

	private function get_section_menu_name( $section_id, $section_title ) {
		return sprintf(
			/* translators: 1: Section title, 2: Section ID. */
			__( '%1$s Section Menu (%2$d)', 'site-section-manager' ),
			$section_title,
			(int) $section_id
		);
	}

	private function get_current_section_id() {
		if ( is_singular( 'site_section' ) ) {
			return (int) get_the_ID();
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return 0;
		}

		return (int) get_post_meta( $post_id, '_ssm_section_id', true );
	}

	private function get_current_section_menu_id() {
		$section_id = $this->get_current_section_id();
		if ( $section_id > 0 ) {
			return $this->get_section_menu_id( $section_id );
		}

		return $this->get_home_menu_id();
	}
}
