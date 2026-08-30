<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Navigation {
	const MENU_LOCATION = 'ssm-global-sections';
	const OPTION_THEME_MENU_LOCATION = 'ssm_theme_menu_location';
	const OPTION_HOME_MENU_ID = 'ssm_home_menu_id';
	const OPTION_HOME_MENU_AUTO = 'ssm_home_menu_auto';
	const SECTION_MENU_META_KEY = '_ssm_nav_menu_id';
	const SECTION_MENU_AUTO_META_KEY = '_ssm_nav_menu_auto';

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

		if ( '' === $location ) {
			$locations = $this->get_available_theme_locations();
			if ( isset( $locations['primary'] ) ) {
				return 'primary';
			}
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

		if ( $menu && ! is_wp_error( $menu ) && ! $this->is_menu_claimed_by_another_section( $menu->term_id, $section_id ) ) {
			$this->sync_menu_name( $menu->term_id, $name );
			$this->ensure_section_menu_has_home_item( $menu->term_id, $section_id );
			return (int) $menu->term_id;
		}

		$menu_id = wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			return 0;
		}

		update_post_meta( $section_id, self::SECTION_MENU_META_KEY, (int) $menu_id );
		$this->ensure_section_menu_has_home_item( $menu_id, $section_id );

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

	public function is_section_menu_auto( $section_id, $is_home = false ) {
		if ( $is_home ) {
			return '0' !== (string) get_option( self::OPTION_HOME_MENU_AUTO, '1' );
		}

		$value = get_post_meta( $section_id, self::SECTION_MENU_AUTO_META_KEY, true );
		return '' === $value || '0' !== (string) $value;
	}

	public function update_section_menu_auto( $section_id, $is_home, $enabled ) {
		if ( $is_home ) {
			update_option( self::OPTION_HOME_MENU_AUTO, $enabled ? '1' : '0', false );
			return;
		}

		update_post_meta( $section_id, self::SECTION_MENU_AUTO_META_KEY, $enabled ? '1' : '0' );
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

		$args['fallback_cb'] = false;

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
			return $this->filter_section_theme_menu_items( $items, $args );
		}

		return $this->build_menu_items_markup();
	}

	private function filter_section_theme_menu_items( $items, $args ) {
		$selected_location = $this->get_selected_theme_location();
		if ( '' === $selected_location || $selected_location !== $args->theme_location ) {
			return $items;
		}

		$section_id = $this->get_current_section_id();
		$is_home    = 0 === (int) $section_id;
		if ( ! $this->is_section_menu_auto( $section_id, $is_home ) ) {
			return $items;
		}

		return $this->build_section_menu_items_markup( $section_id, $is_home );
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

	private function build_section_menu_items_markup( $section_id, $is_home ) {
		$pages  = $this->content->get_section_content_items( 'page', $section_id, $is_home );
		$markup = '';

		foreach ( $pages as $page ) {
			$classes = array(
				'menu-item',
				'menu-item-type-post_type',
				'menu-item-object-page',
				'menu-item-' . (int) $page->ID,
			);

			if ( is_page( $page->ID ) ) {
				$classes[] = 'current-menu-item';
				$classes[] = 'current_page_item';
			}

			$markup .= sprintf(
				'<li class="%1$s"><a href="%2$s">%3$s</a></li>',
				esc_attr( implode( ' ', $classes ) ),
				esc_url( get_permalink( $page ) ),
				esc_html( get_the_title( $page ) ? get_the_title( $page ) : __( '(no title)', 'site-section-manager' ) )
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

	private function is_menu_claimed_by_another_section( $menu_id, $section_id ) {
		foreach ( $this->content->get_sections() as $section ) {
			if ( (int) $section->ID === (int) $section_id ) {
				continue;
			}

			if ( (int) get_post_meta( $section->ID, self::SECTION_MENU_META_KEY, true ) === (int) $menu_id ) {
				return true;
			}
		}

		return false;
	}

	private function ensure_section_menu_has_home_item( $menu_id, $section_id ) {
		if ( ! empty( $this->get_menu_items( $menu_id ) ) ) {
			return;
		}

		$home_page_id = $this->content->get_section_home_page_id( $section_id );
		if ( ! $home_page_id ) {
			return;
		}

		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-object-id' => $home_page_id,
				'menu-item-object'    => 'page',
				'menu-item-title'     => __( 'Home', 'site-section-manager' ),
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'post_type',
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
