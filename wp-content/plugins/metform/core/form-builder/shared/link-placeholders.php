<?php
namespace MetForm\Core\FormBuilder\Shared;

defined( 'ABSPATH' ) || exit;

/**
 * Renders plain text with `{placeholder}` tokens swapped for links, shared by
 * the GDPR Consent text and the Opt-in privacy note.
 *
 * Each token becomes a real <a> when its URL is set, or a plain non-link span
 * (with the caller's placeholder class) otherwise. Everything else is escaped.
 */
final class Link_Placeholders {

	/**
	 * @param string $text              Raw text containing the tokens.
	 * @param array  $links             Map of token (e.g. '{privacy_policy}') => array( 'url' => string, 'text' => string ).
	 * @param bool   $new_tab           Whether links open in a new tab.
	 * @param string $placeholder_class Class for the span used when a token's URL is empty.
	 * @return string Escaped HTML.
	 */
	public static function render( string $text, array $links, bool $new_tab, string $placeholder_class ): string {
		$target  = $new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
		$pattern = '/(' . implode( '|', array_map( 'preg_quote', array_keys( $links ) ) ) . ')/';

		$parts = preg_split( $pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		$out   = '';
		foreach ( $parts as $part ) {
			if ( isset( $links[ $part ] ) ) {
				$url       = (string) ( $links[ $part ]['url'] ?? '' );
				$link_text = (string) ( $links[ $part ]['text'] ?? '' );
				$out      .= ! empty( $url )
					? sprintf( '<a href="%1$s"%2$s>%3$s</a>', esc_url( $url ), $target, esc_html( $link_text ) )
					: sprintf( '<span class="%1$s">%2$s</span>', esc_attr( $placeholder_class ), esc_html( $link_text ) );
			} else {
				$out .= esc_html( $part );
			}
		}
		return $out;
	}
}
