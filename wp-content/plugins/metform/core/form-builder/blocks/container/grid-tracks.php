<?php
namespace MetForm\Core\FormBuilder\Blocks\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Grid track sizes for the Container block's grid layout.
 *
 * The server-side twin of src/form-builder/utils/form/gridTracks.js: the
 * template strings built here must stay byte-identical to that file's
 * toColumnTemplate() / toRowTemplate(), so the editor's inline values and
 * this CSS always agree. Change one, change both.
 *
 * @since 4.1.8
 */
final class Grid_Tracks {

	/**
	 * `--mfb-grid-cols` / `--mfb-grid-rows` for every breakpoint that has
	 * stored track sizes. Media queries are max-width, so a breakpoint
	 * without its own sizes inherits the next larger one — the same
	 * mobile → tablet → desktop chain the editor resolves.
	 *
	 * @param string                $root_sel    The grid element's selector.
	 * @param array<string, mixed>  $settings    The container settings.
	 * @param array<string, int[]>  $counts      Slot => [ columns, rows ], each
	 *                                           resolved at that slot.
	 * @param array<string, string> $breakpoints Slot => media query, smallest last.
	 */
	public static function build_css( string $root_sel, array $settings, array $counts, array $breakpoints ): string {
		$col_sizes = $settings['gridColumnSizes'] ?? null;
		$row_sizes = $settings['gridRowSizes'] ?? null;
		$css       = '';
		$parent    = array(
			'tablet' => 'desktop',
			'mobile' => 'tablet',
		);

		foreach ( array_merge( array( 'desktop' => '' ), $breakpoints ) as $slot => $media ) {
			list( $columns, $rows ) = $counts[ $slot ] ?? $counts['desktop'];
			if ( 'desktop' === $slot ) {
				// The desktop rule always declares both properties: custom
				// properties inherit, so a nested grid without its own sizes
				// must not pick up its parent grid's tracks.
				$cols = self::template( self::slot( $col_sizes, 'desktop' ), 'columns', $columns );
				$rws  = self::template( self::slot( $row_sizes, 'desktop' ), 'rows', $rows );
				$cols = '' !== $cols ? $cols : "repeat({$columns}, 1fr)";
				$rws  = '' !== $rws ? $rws : "repeat({$rows}, minmax(80px, auto))";
			} else {
				$up   = $counts[ $parent[ $slot ] ?? 'desktop' ] ?? $counts['desktop'];
				$cols = self::slot_template( $col_sizes, $slot, 'columns', $columns, $columns !== $up[0] );
				$rws  = self::slot_template( $row_sizes, $slot, 'rows', $rows, $rows !== $up[1] );
			}
			$decls = '';
			if ( '' !== $cols ) {
				$decls .= "--mfb-grid-cols: {$cols};";
			}
			if ( '' !== $rws ) {
				$decls .= "--mfb-grid-rows: {$rws};";
			}
			if ( '' === $decls ) {
				continue;
			}
			$rule = "{$root_sel} { {$decls} }";
			$css .= '' === $media ? $rule : "{$media} { {$rule} }";
		}

		return $css;
	}

	/**
	 * A tablet/mobile track template: the slot's own sizes, or — when only
	 * the track COUNT changed at this slot — the inherited sizes (mobile →
	 * tablet → desktop, as the canvas resolves them) re-fit to the new
	 * count, or an even split. '' when nothing changes at this slot.
	 *
	 * @since 4.4.1
	 */
	private static function slot_template( $sizes, string $slot, string $axis, int $count, bool $count_changed ): string {
		$own = self::slot( $sizes, $slot );
		if ( null !== $own ) {
			return self::template( $own, $axis, $count );
		}
		if ( ! $count_changed ) {
			return '';
		}
		$chain     = 'mobile' === $slot ? array( 'tablet', 'desktop' ) : array( 'desktop' );
		$inherited = null;
		foreach ( $chain as $key ) {
			$inherited = self::slot( $sizes, $key );
			if ( null !== $inherited ) {
				break;
			}
		}
		if ( null !== $inherited ) {
			return self::template( $inherited, $axis, $count );
		}
		return 'columns' === $axis ? "repeat({$count}, 1fr)" : "repeat({$count}, minmax(80px, auto))";
	}

	/** The list stored for exactly `$slot`; a bare list counts as desktop. */
	public static function slot( $sizes, string $slot ): ?array {
		if ( ! is_array( $sizes ) || empty( $sizes ) ) {
			return null;
		}
		if ( array_keys( $sizes ) === range( 0, count( $sizes ) - 1 ) ) {
			return 'desktop' === $slot ? $sizes : null;
		}
		return isset( $sizes[ $slot ] ) && is_array( $sizes[ $slot ] ) && ! empty( $sizes[ $slot ] ) ? array_values( $sizes[ $slot ] ) : null;
	}

	/**
	 * A track template, byte-identical to gridTracks.js's
	 * toColumnTemplate() / toRowTemplate(). Columns are fr shares with a 0
	 * minimum (purely proportional); rows are px minimums that still grow
	 * with content. Missing or invalid entries fall back to 1fr / 80px.
	 */
	public static function template( ?array $list, string $axis, int $count ): string {
		if ( null === $list ) {
			return '';
		}
		$parts = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$value   = $list[ $i ] ?? null;
			$value   = is_numeric( $value ) && (float) $value > 0 ? (float) $value : ( 'columns' === $axis ? 1.0 : 80.0 );
			$num     = rtrim( rtrim( sprintf( '%.3F', round( $value, 3 ) ), '0' ), '.' );
			$parts[] = 'columns' === $axis ? "minmax(0, {$num}fr)" : "minmax({$num}px, auto)";
		}
		return implode( ' ', $parts );
	}
}
