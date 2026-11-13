<?php
namespace MetForm\Core\FormBuilder\Blocks\Hiddenfield;

use MetForm\Core\FormBuilder\Advanced\Controls as Advanced_Controls;
use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;

defined( 'ABSPATH' ) || exit;

/**
 * Unlike its React counterpart, this block resolves its dynamic value
 * SERVER-SIDE at render time (URL param, cookie, referrer, page URL,
 * current user id/email) — no client JS needed at all, since the page is
 * already freshly rendered by PHP on every load. This is a case where the
 * PHP-canonical approach is strictly simpler than the CSR alternative,
 * which would need a browser-side script to read the same sources.
 */
final class Hiddenfield extends Block_Base {
	private const VALUE_SOURCES = array( 'static', 'url_param', 'cookie', 'page_url', 'referrer', 'user_id', 'user_email' );

	public function get_name(): string {
		return 'hiddenfield'; }
	public function get_title(): string {
		return __( 'Hidden Field', 'metform' ); }
	public function get_icon(): string {
		return 'EyeOff'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'other'; }

	public function get_default_settings(): array {
		return array(
			'name'        => 'hidden_field',
			'valueSource' => 'static',
			'value'       => '',
			'paramName'   => '',
			'cookieName'  => '',
			'fallback'    => '',
			'helpText'    => '',
			'styles'      => array(),
		);
	}

	public function get_property_schema(): array {
		return array(
			'name'        => array( 'type' => 'field_name' ),
			'valueSource' => array(
				'type' => 'string',
				'enum' => self::VALUE_SOURCES,
			),
			'value'       => array( 'type' => 'string' ),
			'paramName'   => array( 'type' => 'string' ),
			'cookieName'  => array( 'type' => 'string' ),
			'fallback'    => array( 'type' => 'string' ),
			'helpText'    => array( 'type' => 'string' ),
		);
	}

	/**
	 * A hidden input has no box to style, animate or hide.
	 *
	 * @since 4.2.0
	 *
	 * @return string[] Advanced\Controls groups to leave out.
	 */
	protected function get_advanced_exclude(): array {
		return Advanced_Controls::GROUPS;
	}

	public function register_controls(): array {
		return array(
			'content' => array(
				array(
					'type'        => 'section',
					'key'         => 'content',
					'label'       => __( 'Content', 'metform' ),
					'defaultOpen' => true,
				),
				array(
					'type'    => 'text',
					'key'     => 'name',
					'label'   => __( 'Name', 'metform' ),
					'default' => 'hidden_field',
					'help'    => __( 'Submitted as this key in the form data.', 'metform' ),
				),
				array(
					'type'    => 'select',
					'key'     => 'valueSource',
					'label'   => __( 'Value Source', 'metform' ),
					'default' => 'static',
					'help'    => __( 'Where the field value is pulled from at submission time.', 'metform' ),
					'options' => array(
						array(
							'label' => __( 'Static Value', 'metform' ),
							'value' => 'static',
						),
						array(
							'label' => __( 'URL Parameter', 'metform' ),
							'value' => 'url_param',
						),
						array(
							'label' => __( 'Cookie', 'metform' ),
							'value' => 'cookie',
						),
						array(
							'label' => __( 'Current Page URL', 'metform' ),
							'value' => 'page_url',
						),
						array(
							'label' => __( 'Referrer URL', 'metform' ),
							'value' => 'referrer',
						),
						array(
							'label' => __( 'User ID', 'metform' ),
							'value' => 'user_id',
						),
						array(
							'label' => __( 'User Email', 'metform' ),
							'value' => 'user_email',
						),
					),
				),
				array(
					'type'      => 'text',
					'key'       => 'value',
					'label'     => __( 'Static Value', 'metform' ),
					'default'   => '',
					'help'      => __( 'The fixed value sent with the form.', 'metform' ),
					'condition' => array( 'valueSource' => 'static' ),
				),
				array(
					'type'      => 'text',
					'key'       => 'paramName',
					'label'     => __( 'URL Parameter Name', 'metform' ),
					'default'   => '',
					'help'      => __( 'Read from ?param=value in the page URL. E.g. utm_source', 'metform' ),
					'condition' => array( 'valueSource' => 'url_param' ),
				),
				array(
					'type'      => 'text',
					'key'       => 'cookieName',
					'label'     => __( 'Cookie Name', 'metform' ),
					'default'   => '',
					'help'      => __( 'Read the value of this browser cookie at form load.', 'metform' ),
					'condition' => array( 'valueSource' => 'cookie' ),
				),
				array(
					'type'    => 'text',
					'key'     => 'fallback',
					'label'   => __( 'Fallback Value', 'metform' ),
					'default' => '',
					'help'    => __( 'Used when the dynamic source cannot be resolved (e.g. param missing, cookie unset).', 'metform' ),
				),
				array(
					'type'  => 'text',
					'key'   => 'helpText',
					'label' => __( 'Internal Note', 'metform' ),
					'help'  => __( 'Reminder for you — never shown to users.', 'metform' ),
				),
			),
			'style'   => array(),
		);
	}

	/** Resolve the value this instance submits, entirely server-side. */
	private function resolve_value( array $settings ): string {
		$fallback = (string) ( $settings['fallback'] ?? '' );
		$source   = in_array( $settings['valueSource'] ?? 'static', self::VALUE_SOURCES, true ) ? $settings['valueSource'] : 'static';

		switch ( $source ) {
			case 'url_param':
				$param = (string) ( $settings['paramName'] ?? '' );
				if ( '' === $param || ! isset( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					return $fallback;
				}
				return sanitize_text_field( wp_unslash( $_GET[ $param ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			case 'cookie':
				$cookie = (string) ( $settings['cookieName'] ?? '' );
				if ( '' === $cookie || ! isset( $_COOKIE[ $cookie ] ) ) {
					return $fallback;
				}
				return sanitize_text_field( wp_unslash( $_COOKIE[ $cookie ] ) );

			case 'page_url':
				return home_url( add_query_arg( null, null ) );

			case 'referrer':
				$referrer = wp_get_referer();
				return $referrer ? esc_url_raw( $referrer ) : $fallback;

			case 'user_id':
				return is_user_logged_in() ? (string) get_current_user_id() : $fallback;

			case 'user_email':
				if ( ! is_user_logged_in() ) {
					return $fallback;
				}
				$user = wp_get_current_user();
				return ( $user && $user->user_email ) ? $user->user_email : $fallback;

			case 'static':
			default:
				return (string) ( $settings['value'] ?? '' );
		}
	}

	private const SOURCE_META = array(
		'static'     => array(
			'label' => 'static',
			'color' => 'blue',
		),
		'url_param'  => array(
			'label' => 'url param',
			'color' => 'violet',
		),
		'cookie'     => array(
			'label' => 'cookie',
			'color' => 'orange',
		),
		'page_url'   => array(
			'label' => 'page url',
			'color' => 'teal',
		),
		'referrer'   => array(
			'label' => 'referrer',
			'color' => 'indigo',
		),
		'user_id'    => array(
			'label' => 'user id',
			'color' => 'green',
		),
		'user_email' => array(
			'label' => 'user email',
			'color' => 'cyan',
		),
	);

	/** What the editor badge shows in place of the actual resolved value (matches hiddenfield/index.jsx's editorValueHint()). */
	private function editor_value_hint( array $settings ): string {
		switch ( $settings['valueSource'] ?? 'static' ) {
			case 'static':
				return (string) ( $settings['value'] ?? '' ) ?: __( '(empty)', 'metform' );
			case 'url_param':
				return ! empty( $settings['paramName'] ) ? '?' . $settings['paramName'] : __( '(no param set)', 'metform' );
			case 'cookie':
				return ! empty( $settings['cookieName'] ) ? $settings['cookieName'] : __( '(no cookie set)', 'metform' );
			case 'page_url':
				return __( 'current page URL', 'metform' );
			case 'referrer':
				return __( 'referrer URL', 'metform' );
			case 'user_id':
				return __( 'logged-in user ID', 'metform' );
			case 'user_email':
				return __( 'logged-in user email', 'metform' );
			default:
				return '—';
		}
	}

	public function render( array $settings, Render_Context $context ): string {
		// A genuinely invisible <input type="hidden"> can't be seen or
		// clicked in the editor canvas — same problem the React block
		// solves by only rendering the real hidden input in preview/
		// frontend mode, and a visible "Hidden" badge otherwise (see
		// hiddenfield/index.jsx). Render_Context::is_editor() is exactly
		// the flag that lets PHP branch the same way.
		if ( $context->is_editor() ) {
			$source     = in_array( $settings['valueSource'] ?? 'static', array_keys( self::SOURCE_META ), true ) ? $settings['valueSource'] : 'static';
			$meta       = self::SOURCE_META[ $source ];
			$field_name = (string) ( $settings['name'] ?? 'hidden_field' );
			$hint       = $this->editor_value_hint( $settings );
			$note       = (string) ( $settings['helpText'] ?? '' );

			ob_start();
			?>
			<div class="mfb-hidden-field__badge mfb-hidden-field__badge--<?php echo esc_attr( $meta['color'] ); ?>" data-mf-block-id="<?php echo esc_attr( $context->get_block_id() ); ?>">
				<span class="mfb-hidden-field__eyeoff" aria-hidden="true">👁</span>
				<span class="mfb-hidden-field__title"><?php esc_html_e( 'Hidden', 'metform' ); ?></span>
				<span class="mfb-hidden-field__source mfb-hidden-field__source--<?php echo esc_attr( $meta['color'] ); ?>"><?php echo esc_html( $meta['label'] ); ?></span>
				<span class="mfb-hidden-field__row">
					<span class="mfb-hidden-field__name"><?php echo esc_html( $field_name ); ?></span>
					<span class="mfb-hidden-field__arrow">→</span>
					<span class="mfb-hidden-field__hint"><?php echo esc_html( $hint ); ?></span>
				</span>
				<?php if ( '' !== $note ) : ?>
				<span class="mfb-hidden-field__note" title="<?php echo esc_attr( $note ); ?>"><?php echo esc_html( $note ); ?></span>
				<?php endif; ?>
			</div>
			<?php
			return (string) ob_get_clean();
		}

		$value = $this->resolve_value( $settings );

		return sprintf(
			'<input type="hidden" name="%1$s" value="%2$s" data-mf-block-id="%3$s">',
			esc_attr( $settings['name'] ),
			esc_attr( $value ),
			esc_attr( $context->get_block_id() )
		);
	}
}
