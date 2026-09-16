<?php
namespace AIKB;

defined( 'ABSPATH' ) || exit;

/**
 * GitHub Releases auto-updater for AI Knowledge & Visibility.
 *
 * Hooks into the WordPress update system so the plugin appears in
 * Dashboard › Updates and can be upgraded with one click, pulling
 * the latest release ZIP from the public GitHub repo.
 */
final class Github_Updater {

	private const REPO       = '22MW/ai-knowledge';
	private const ASSET_NAME = 'ai-knowledge.zip';
	private const SLUG       = 'ai-knowledge';
	private const CACHE_KEY  = 'aikb_github_release_latest';

	/**
	 * Get the actual plugin basename for the current installation folder.
	 *
	 * @return string
	 */
	private function get_plugin_basename(): string {
		return plugin_basename( AIKB_FILE );
	}

	/** @return void */
	public function register_hooks(): void {
		add_filter( 'site_transient_update_plugins', array( $this, 'filter_plugin_updates' ) );
		add_filter( 'plugins_api',                   array( $this, 'filter_plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection',      array( $this, 'fix_source_dir' ),     10, 4 );
	}

	/**
	 * Fetch the latest release from GitHub API (cached 1 hour).
	 *
	 * @return array<string,mixed>|null
	 */
	private function get_latest_release(): ?array {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'AI-Knowledge-Visibility',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return null;
		}

		set_transient( self::CACHE_KEY, $data, HOUR_IN_SECONDS );
		return $data;
	}

	/**
	 * Extract version string from a release tag (strips leading 'v').
	 *
	 * @param array<string,mixed> $release
	 * @return string
	 */
	private function get_remote_version( array $release ): string {
		return ltrim( (string) ( $release['tag_name'] ?? '' ), 'v' );
	}

	/**
	 * Return the download URL for the release ZIP.
	 * Prefers the named asset; falls back to the tag ZIP.
	 *
	 * @param array<string,mixed> $release
	 * @return string
	 */
	private function get_package_url( array $release ): string {
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( is_array( $asset ) && isset( $asset['name'] ) && $asset['name'] === self::ASSET_NAME ) {
					return (string) ( $asset['browser_download_url'] ?? '' );
				}
			}
		}

		$tag = (string) ( $release['tag_name'] ?? '' );
		if ( '' === $tag ) {
			return '';
		}

		return 'https://github.com/' . self::REPO . '/archive/refs/tags/' . $tag . '.zip';
	}

	/**
	 * Inject update info into the WP update transient when a newer release exists.
	 *
	 * @param object|false $transient
	 * @return object|false
	 */
	public function filter_plugin_updates( $transient ) {
		if ( ! is_object( $transient ) || ! isset( $transient->checked ) || ! is_array( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $transient;
		}

		$remote_version = $this->get_remote_version( $release );
		$plugin_slug    = $this->get_plugin_basename();

		if ( empty( $remote_version ) || empty( $transient->checked[ $plugin_slug ] ) ) {
			return $transient;
		}

		if ( version_compare( $remote_version, $transient->checked[ $plugin_slug ], '<=' ) ) {
			return $transient;
		}

		$transient->response[ $plugin_slug ] = (object) array(
			'slug'        => self::SLUG,
			'plugin'      => $plugin_slug,
			'new_version' => $remote_version,
			'url'         => 'https://github.com/' . self::REPO,
			'package'     => $this->get_package_url( $release ),
		);

		return $transient;
	}

	/**
	 * Provide plugin details for the WP update info modal.
	 *
	 * @param false|object|array $result
	 * @param string             $action
	 * @param object             $args
	 * @return false|object
	 */
	public function filter_plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || $args->slug !== self::SLUG ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		$info                = new \stdClass();
		$info->name          = 'AI Knowledge & Visibility';
		$info->slug          = self::SLUG;
		$info->version       = $this->get_remote_version( $release );
		$info->author        = '<a href="https://github.com/22MW">22MW</a>';
		$info->homepage      = 'https://github.com/' . self::REPO;
		$info->requires      = '5.8';
		$info->requires_php  = '7.4';
		$info->download_link = $this->get_package_url( $release );
		$info->sections      = array(
			'description' => 'Genera documentos de base de conocimiento a partir de tu contenido y los publica para que buscadores y agentes de IA los entiendan.',
			'changelog'   => $this->format_release_changelog( $release ),
		);

		return $info;
	}

	/**
	 * Convert GitHub release body to basic HTML for the WP changelog section.
	 *
	 * @param array<string,mixed> $release
	 * @return string
	 */
	private function format_release_changelog( array $release ): string {
		$body    = trim( (string) ( $release['body'] ?? '' ) );
		$version = $this->get_remote_version( $release );
		$date    = substr( (string) ( $release['published_at'] ?? '' ), 0, 10 );

		if ( '' === $body ) {
			return '<p><strong>' . esc_html( $version ) . '</strong>' . ( $date ? ' — ' . esc_html( $date ) : '' ) . '</p>';
		}

		$lines  = explode( "\n", $body );
		$output = '<p><strong>' . esc_html( $version ) . '</strong>' . ( $date ? ' — ' . esc_html( $date ) : '' ) . '</p><ul>';
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$line    = preg_replace( '/^[-*]\s+/', '', $line ) ?? $line;
			$output .= '<li>' . esc_html( $line ) . '</li>';
		}
		$output .= '</ul>';
		return $output;
	}

	/**
	 * Rename the extracted ZIP folder to the expected plugin slug if needed.
	 * GitHub names the folder "{repo}-{tag}" by default.
	 *
	 * @param string              $source
	 * @param string              $remote_source
	 * @param object              $upgrader
	 * @param array<string,mixed> $hook_extra
	 * @return string
	 */
	public function fix_source_dir( string $source, string $remote_source, object $upgrader, array $hook_extra ): string {
		$plugin_slug = $this->get_plugin_basename();

		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $plugin_slug ) {
			return $source;
		}

		$plugin_dir = dirname( $plugin_slug );

		if ( basename( $source ) === $plugin_dir ) {
			return $source;
		}

		$corrected = trailingslashit( dirname( $source ) ) . $plugin_dir;
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( @rename( $source, $corrected ) ) {
			return $corrected;
		}

		return $source;
	}
}
