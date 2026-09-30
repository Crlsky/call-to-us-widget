<?php
/**
 * Plugin Name: Call To Us Widget
 * Description: Konfiguracja pływającej ikonki kontaktowej.
 * Version: 1.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: KarolŚ
 * Text Domain: call-to-us-widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Call_To_Us_Widget_Settings {
	const OPTION_NAME = 'call_to_us_widget_settings';
	const PAGE_SLUG   = 'call-to-us-widget';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_color_picker' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_widget' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'add_settings_link' ) );
	}

	/** Add a direct Settings link to the Plugins screen. */
	public static function add_settings_link( $links ) {
		$url  = admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
		$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Ustawienia', 'call-to-us-widget' ) . '</a>';

		array_unshift( $links, $link );

		return $links;
	}

	public static function add_settings_page() {
		add_options_page(
			__( 'Call To Us Widget', 'call-to-us-widget' ),
			__( 'Call To Us Widget', 'call-to-us-widget' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			'call_to_us_widget_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::defaults(),
			)
		);
	}

	public static function defaults() {
		return array(
			'enabled' => 0,
			'number' => 666777666,
			'svg'    => '',
			'color'  => '#1d4236',
			'corner' => 'bottom-right',
			'size'   => 64,
		);
	}

	public static function sanitize_settings( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$corners  = array( 'top-left', 'top-right', 'bottom-left', 'bottom-right' );
		$svg      = isset( $input['svg'] ) ? trim( (string) $input['svg'] ) : '';

		$clean = array(
			'enabled' => ! empty( $input['enabled'] ) ? 1 : 0,
			'number' => ! empty($input['number']) ? $input['number'] : $defaults['number'],
			'svg'    => '' === $svg ? '' : wp_kses( $svg, self::allowed_svg() ),
			'color'  => isset( $input['color'] ) ? sanitize_hex_color( $input['color'] ) : $defaults['color'],
			'corner' => isset( $input['corner'] ) && in_array( $input['corner'], $corners, true ) ? $input['corner'] : $defaults['corner'],
			'size'   => isset( $input['size'] ) ? absint( $input['size'] ) : $defaults['size'],
		);

		if ( ! $clean['color'] ) {
			$clean['color'] = $defaults['color'];
		}

		$clean['size'] = min( 160, max( 32, $clean['size'] ) );

		return $clean;
	}


	/** Render the configured icon on every front-end page, including the homepage. */
	public static function render_widget() {
		$settings = wp_parse_args( get_option( self::OPTION_NAME, array() ), self::defaults() );
		if ( empty( $settings['enabled'] ) || empty( $settings['svg'] ) ) {
			return;
		}

		$positions = array(
			'top-left'     => 'top: 20px; left: 20px;',
			'top-right'    => 'top: 20px; right: 20px;',
			'bottom-left'  => 'bottom: 20px; left: 20px;',
			'bottom-right' => 'bottom: 20px; right: 20px;',
		);
		$corner = isset( $positions[ $settings['corner'] ] ) ? $settings['corner'] : 'bottom-right';
		$size   = min( 160, max( 32, absint( $settings['size'] ) ) );
		$color  = sanitize_hex_color( $settings['color'] );
		$color  = $color ? $color : self::defaults()['color'];

		printf(
			'<a href="tel:%1$d" class="call-to-us-widget" role="img" aria-label="%2$s" style="%3$s width:%4$dpx; height:%4$dpx; color:%5$s; fill:%5$s;">%6$s</a>',
			esc_attr( $settings['number'] ),
			esc_attr( 'Kontakt', 'call-to-us-widget' ),
			esc_attr( $positions[ $corner ] ),
			$size,
			esc_attr( $color ),
			$settings['svg']
		);
		echo '<style>.call-to-us-widget{position:fixed;z-index:99999;display:flex;align-items:center;justify-content:center}.call-to-us-widget svg{display:block;width:100%;height:100%;fill:currentColor}.call-to-us-widget svg [fill]{fill:currentColor!important}</style>';
	}

	private static function allowed_svg() {
		return array(
			'svg' => array(
				'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true,
				'class' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true,
				'role' => true, 'aria-hidden' => true, 'focusable' => true,
			),
			'g' => array( 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'transform' => true, 'opacity' => true, 'fill-rule' => true, 'clip-rule' => true ),
			'path' => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'fill-rule' => true, 'clip-rule' => true, 'transform' => true, 'opacity' => true ),
			'circle' => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'opacity' => true ),
			'ellipse' => array( 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'opacity' => true ),
			'rect' => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'opacity' => true ),
			'line' => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true ),
			'polyline' => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ),
			'polygon' => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linejoin' => true ),
			'title' => array(),
		);
	}

	public static function enqueue_color_picker( $hook ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', "jQuery(function($){ $('.call-to-us-color').wpColorPicker(); });" );
	}

	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = wp_parse_args( get_option( self::OPTION_NAME, array() ), self::defaults() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Call To Us Widget', 'call-to-us-widget' ); ?></h1>
			<div class="card" style="max-width: 760px; padding: 24px; margin-top: 20px;">
				<h2 style="margin-top: 0;"><?php esc_html_e( 'Wygląd ikonki', 'call-to-us-widget' ); ?></h2>
				<p><?php esc_html_e( 'Wklej kod SVG, wybierz kolor, położenie i rozmiar widgetu.', 'call-to-us-widget' ); ?></p>
				<form action="options.php" method="post">
					<?php settings_fields( 'call_to_us_widget_group' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Widget', 'call-to-us-widget' ); ?></th>
							<td><label for="ctu-enabled"><input id="ctu-enabled" type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> <?php esc_html_e( 'Włącz wyświetlanie ikonki na stronie', 'call-to-us-widget' ); ?></label></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Numer telefonu', 'call-to-us-widget' ); ?></th>
							<td><label for="ctu-number"><input id="ctu-number" type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[number]" value="<?php echo esc_attr( $settings['number'] ); ?>"> <?php esc_html_e( 'Numer na telefonu', 'call-to-us-widget' ); ?></label></td>
						</tr>
						<tr>
							<th scope="row"><label for="ctu-svg"><?php esc_html_e( 'Kod SVG ikonki', 'call-to-us-widget' ); ?></label></th>
							<td><textarea id="ctu-svg" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[svg]" rows="10" class="large-text code" placeholder="&lt;svg viewBox=&quot;0 0 24 24&quot;...&gt;...&lt;/svg&gt;"><?php echo esc_textarea( $settings['svg'] ); ?></textarea><p class="description"><?php esc_html_e( 'Dozwolone są podstawowe kształty SVG. Skrypty, zdarzenia i osadzone elementy zostaną usunięte.', 'call-to-us-widget' ); ?></p></td>
						</tr>
						<tr>
							<th scope="row"><label for="ctu-color"><?php esc_html_e( 'Kolor ikonki', 'call-to-us-widget' ); ?></label></th>
							<td><input id="ctu-color" class="call-to-us-color" type="text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[color]" value="<?php echo esc_attr( $settings['color'] ); ?>" data-default-color="#1687ff"></td>
						</tr>
						<tr>
							<th scope="row"><label for="ctu-corner"><?php esc_html_e( 'Narożnik ekranu', 'call-to-us-widget' ); ?></label></th>
							<td><select id="ctu-corner" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[corner]">
								<?php
								$corners = array(
									'bottom-right' => __( 'Prawy dolny', 'call-to-us-widget' ),
									'bottom-left'  => __( 'Lewy dolny', 'call-to-us-widget' ),
									'top-right'    => __( 'Prawy górny', 'call-to-us-widget' ),
									'top-left'     => __( 'Lewy górny', 'call-to-us-widget' ),
								);
								foreach ( $corners as $value => $label ) :
									?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['corner'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select></td>
						</tr>
						<tr>
							<th scope="row"><label for="ctu-size"><?php esc_html_e( 'Rozmiar ikonki', 'call-to-us-widget' ); ?></label></th>
							<td><input id="ctu-size" type="number" min="32" max="160" step="1" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[size]" value="<?php echo esc_attr( $settings['size'] ); ?>"> <span><?php esc_html_e( 'px (32–160)', 'call-to-us-widget' ); ?></span></td>
						</tr>
					</table>
					<?php submit_button( __( 'Zapisz ustawienia', 'call-to-us-widget' ) ); ?>
				</form>
			</div>
		</div>
		<?php
	}
}

Call_To_Us_Widget_Settings::init();
