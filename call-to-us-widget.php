<?php
/**
 * Plugin Name: Call To Us Widget
 * Description: Konfiguracja pływającej ikonki kontaktowej.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Cremini
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
