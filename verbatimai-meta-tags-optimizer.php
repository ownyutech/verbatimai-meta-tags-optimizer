<?php
/**
 * Plugin Name:       VerbatimAI Meta Tags Optimizer
 * Plugin URI:        https://www.ownyu.com/products/ai-meta-tags-optimizer
 * Description:       Optimize your site content for Google AI Overviews and LLMs by injecting targeted summaries and JSON-LD schema graphs.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            VerbatimAI
 * Author URI:        https://www.ownyu.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       verbatimai-meta-tags-optimizer
 *
 * @package VerbatimAI_Meta_Tags_Optimizer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 *
 * Self-contained in a single file by design.
 */
final class VerbatimAI_Meta_Tags_Optimizer {

	/**
	 * Post meta keys.
	 */
	const META_SUMMARY  = '_vamto_summary';
	const META_QA       = '_vamto_qa';
	const META_ENTITIES = '_vamto_entities';

	/**
	 * Nonce constants.
	 */
	const NONCE_ACTION = 'vamto_save_meta_action';
	const NONCE_NAME   = 'vamto_save_meta_nonce';

	/**
	 * Bootstrap hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta_box' ) );
		add_action( 'wp_head', array( __CLASS__, 'output_head_tags' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register the meta box on Post and Page edit screens.
	 */
	public static function register_meta_box() {
		$screens = array( 'post', 'page' );

		foreach ( $screens as $screen ) {
			add_meta_box(
				'vamto_meta_box',
				esc_html__( 'VerbatimAI Overview Optimizer', 'verbatimai-meta-tags-optimizer' ),
				array( __CLASS__, 'render_meta_box' ),
				$screen,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Enqueue admin CSS/JS only on the Post/Page edit screens, and pass safe i18n strings via wp_localize_script.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		// Register a handle-less stylesheet via inline CSS (no external file needed).
		wp_register_style( 'vamto-admin-style', false, array(), '1.0.0' );
		wp_enqueue_style( 'vamto-admin-style' );
		wp_add_inline_style( 'vamto-admin-style', self::get_admin_css() );

		// Register a handle-less script via inline JS (no external file needed).
		wp_register_script( 'vamto-admin-script', false, array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( 'vamto-admin-script' );
		wp_add_inline_script( 'vamto-admin-script', self::get_admin_js() );

		wp_localize_script(
			'vamto-admin-script',
			'vamtoL10n',
			array(
				'copySuccess'    => esc_html__( '✔ Copied to clipboard!', 'verbatimai-meta-tags-optimizer' ),
				'copyError'      => esc_html__( '✘ Copy failed. Please select and copy manually.', 'verbatimai-meta-tags-optimizer' ),
				'questionLabel'  => esc_attr__( 'Question', 'verbatimai-meta-tags-optimizer' ),
				'answerLabel'    => esc_attr__( 'Direct answer', 'verbatimai-meta-tags-optimizer' ),
				'removeLabel'    => esc_attr__( 'Remove this Q&A pair', 'verbatimai-meta-tags-optimizer' ),
			)
		);
	}

	/**
	 * Inline admin CSS for the meta box.
	 *
	 * @return string CSS rules.
	 */
	private static function get_admin_css() {
		return '
			.vamto-wrap .vamto-field { margin-bottom: 18px; }
			.vamto-wrap .description { color: #646970; display: inline-block; margin-bottom: 6px; }
			.vamto-wrap textarea, .vamto-wrap input[type="text"] { margin-top: 6px; }
			.vamto-qa-row {
				display: flex;
				gap: 8px;
				align-items: flex-start;
				margin-bottom: 10px;
				padding: 10px;
				background: #f6f7f7;
				border: 1px solid #dcdcde;
				border-radius: 4px;
			}
			.vamto-qa-row .vamto-qa-question { flex: 0 0 30%; }
			.vamto-qa-row .vamto-qa-answer { flex: 1 1 auto; resize: vertical; }
			.vamto-qa-row .vamto-remove-qa { flex: 0 0 auto; color: #b32d2e; font-weight: bold; line-height: 1; }
			.vamto-copy-row { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
			.vamto-copy-status { font-weight: 600; }
			.vamto-copy-status.success { color: #008a20; }
			.vamto-copy-status.error { color: #b32d2e; }
			.vamto-footer { margin-top: 14px; padding-top: 10px; border-top: 1px solid #dcdcde; font-size: 12px; color: #646970; text-align: right; }
			.vamto-footer a { text-decoration: none; font-weight: 600; }
		';
	}

	/**
	 * Inline admin JS for the repeater fields and the copy-to-clipboard button.
	 *
	 * @return string JavaScript code.
	 */
	private static function get_admin_js() {
		return <<<'JS'
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var repeater = document.getElementById( 'vamto-qa-repeater' );
		var addBtn   = document.getElementById( 'vamto-add-qa' );
		var copyBtn  = document.getElementById( 'vamto-copy-btn' );
		var status   = document.getElementById( 'vamto-copy-status' );
		var l10n     = ( typeof vamtoL10n !== 'undefined' ) ? vamtoL10n : {};

		if ( ! repeater ) {
			return;
		}

		function buildRow() {
			var row = document.createElement( 'div' );
			row.className = 'vamto-qa-row';

			var qInput = document.createElement( 'input' );
			qInput.type = 'text';
			qInput.className = 'vamto-qa-question regular-text';
			qInput.name = 'vamto_qa_question[]';
			qInput.placeholder = l10n.questionLabel || 'Question';

			var aInput = document.createElement( 'textarea' );
			aInput.className = 'vamto-qa-answer large-text';
			aInput.name = 'vamto_qa_answer[]';
			aInput.rows = 2;
			aInput.placeholder = l10n.answerLabel || 'Answer';

			var removeBtn = document.createElement( 'button' );
			removeBtn.type = 'button';
			removeBtn.className = 'button vamto-remove-qa';
			removeBtn.setAttribute( 'aria-label', l10n.removeLabel || 'Remove' );
			removeBtn.innerHTML = '&times;';

			row.appendChild( qInput );
			row.appendChild( aInput );
			row.appendChild( removeBtn );

			return row;
		}

		if ( addBtn ) {
			addBtn.addEventListener( 'click', function () {
				repeater.appendChild( buildRow() );
			} );
		}

		repeater.addEventListener( 'click', function ( e ) {
			if ( e.target && e.target.classList.contains( 'vamto-remove-qa' ) ) {
				var rows = repeater.querySelectorAll( '.vamto-qa-row' );
				if ( rows.length > 1 ) {
					e.target.closest( '.vamto-qa-row' ).remove();
				} else {
					var row = e.target.closest( '.vamto-qa-row' );
					row.querySelector( '.vamto-qa-question' ).value = '';
					row.querySelector( '.vamto-qa-answer' ).value = '';
				}
			}
		} );

		if ( copyBtn ) {
			copyBtn.addEventListener( 'click', function () {
				var summaryEl  = document.getElementById( 'vamto_summary' );
				var entitiesEl = document.getElementById( 'vamto_entities' );
				var summary    = summaryEl ? summaryEl.value.trim() : '';
				var entities   = entitiesEl ? entitiesEl.value.trim() : '';

				var qaList = [];
				repeater.querySelectorAll( '.vamto-qa-row' ).forEach( function ( row ) {
					var q = row.querySelector( '.vamto-qa-question' ).value.trim();
					var a = row.querySelector( '.vamto-qa-answer' ).value.trim();
					if ( q && a ) {
						qaList.push( { question: q, answer: a } );
					}
				} );

				var entityArray = entities
					? entities.split( ',' ).map( function ( s ) { return s.trim(); } ).filter( Boolean )
					: [];

				var metaTags = '';
				if ( summary ) {
					metaTags += '<meta name="ai-summary" content="' + summary.replace( /"/g, '&quot;' ) + '">\n';
				}
				if ( entityArray.length ) {
					metaTags += '<meta name="ai-entities" content="' + entityArray.join( ', ' ).replace( /"/g, '&quot;' ) + '">\n';
				}

				var jsonLd = { '@context': 'https://schema.org', '@graph': [] };

				if ( summary ) {
					jsonLd[ '@graph' ].push( {
						'@type': 'Article',
						'abstract': summary,
						'keywords': entityArray.join( ', ' )
					} );
				}

				if ( qaList.length ) {
					jsonLd[ '@graph' ].push( {
						'@type': 'FAQPage',
						'mainEntity': qaList.map( function ( pair ) {
							return {
								'@type': 'Question',
								'name': pair.question,
								'acceptedAnswer': { '@type': 'Answer', 'text': pair.answer }
							};
						} )
					} );
				}

				var jsonLdScript = '<script type="application/ld+json">\n' + JSON.stringify( jsonLd, null, 2 ) + '\n<\/script>';
				var output = metaTags + '\n' + jsonLdScript;

				function showStatus( success ) {
					if ( ! status ) {
						return;
					}
					status.textContent = success ? ( l10n.copySuccess || 'Copied!' ) : ( l10n.copyError || 'Copy failed.' );
					status.className = 'vamto-copy-status ' + ( success ? 'success' : 'error' );
					setTimeout( function () {
						status.textContent = '';
						status.className = 'vamto-copy-status';
					}, 4000 );
				}

				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( output ).then(
						function () { showStatus( true ); },
						function () { showStatus( false ); }
					);
				} else {
					var temp = document.createElement( 'textarea' );
					temp.value = output;
					document.body.appendChild( temp );
					temp.select();
					try {
						document.execCommand( 'copy' );
						showStatus( true );
					} catch ( err ) {
						showStatus( false );
					}
					document.body.removeChild( temp );
				}
			} );
		}
	} );
} )();
JS;
	}

	/**
	 * Render the meta box markup.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$summary  = get_post_meta( $post->ID, self::META_SUMMARY, true );
		$entities = get_post_meta( $post->ID, self::META_ENTITIES, true );
		$qa_raw   = get_post_meta( $post->ID, self::META_QA, true );

		$qa_pairs = array();
		if ( ! empty( $qa_raw ) ) {
			$decoded = json_decode( $qa_raw, true );
			if ( is_array( $decoded ) ) {
				$qa_pairs = $decoded;
			}
		}
		if ( empty( $qa_pairs ) ) {
			$qa_pairs = array( array( 'question' => '', 'answer' => '' ) );
		}
		?>
		<div class="vamto-wrap">

			<p class="vamto-field">
				<label for="vamto_summary">
					<strong><?php esc_html_e( 'AI-Targeted Summary / TL;DR', 'verbatimai-meta-tags-optimizer' ); ?></strong>
				</label>
				<br />
				<span class="description">
					<?php esc_html_e( 'A concise, factual summary written for LLM scraping and AI Overview snippets.', 'verbatimai-meta-tags-optimizer' ); ?>
				</span>
				<textarea
					id="vamto_summary"
					name="vamto_summary"
					rows="3"
					class="large-text"
					maxlength="600"
				><?php echo esc_textarea( $summary ); ?></textarea>
			</p>

			<p class="vamto-field">
				<label><strong><?php esc_html_e( 'Key Q&A for AI Search', 'verbatimai-meta-tags-optimizer' ); ?></strong></label>
				<br />
				<span class="description">
					<?php esc_html_e( 'Add clear Questions with direct, authoritative Answers used in the FAQ schema.', 'verbatimai-meta-tags-optimizer' ); ?>
				</span>

				<div id="vamto-qa-repeater">
					<?php foreach ( $qa_pairs as $pair ) : ?>
						<div class="vamto-qa-row">
							<input
								type="text"
								class="vamto-qa-question regular-text"
								name="vamto_qa_question[]"
								placeholder="<?php esc_attr_e( 'Question', 'verbatimai-meta-tags-optimizer' ); ?>"
								value="<?php echo esc_attr( isset( $pair['question'] ) ? $pair['question'] : '' ); ?>"
							/>
							<textarea
								class="vamto-qa-answer large-text"
								name="vamto_qa_answer[]"
								rows="2"
								placeholder="<?php esc_attr_e( 'Direct answer', 'verbatimai-meta-tags-optimizer' ); ?>"
							><?php echo esc_textarea( isset( $pair['answer'] ) ? $pair['answer'] : '' ); ?></textarea>
							<button type="button" class="button vamto-remove-qa" aria-label="<?php esc_attr_e( 'Remove this Q&A pair', 'verbatimai-meta-tags-optimizer' ); ?>">&times;</button>
						</div>
					<?php endforeach; ?>
				</div>

				<button type="button" id="vamto-add-qa" class="button button-secondary">
					+ <?php esc_html_e( 'Add another Q&A pair', 'verbatimai-meta-tags-optimizer' ); ?>
				</button>
			</p>

			<p class="vamto-field">
				<label for="vamto_entities">
					<strong><?php esc_html_e( 'Custom Meta Keywords / Entities', 'verbatimai-meta-tags-optimizer' ); ?></strong>
				</label>
				<br />
				<span class="description">
					<?php esc_html_e( 'Comma-separated core entities and topics (people, brands, products, concepts).', 'verbatimai-meta-tags-optimizer' ); ?>
				</span>
				<input
					type="text"
					id="vamto_entities"
					name="vamto_entities"
					class="large-text"
					value="<?php echo esc_attr( $entities ); ?>"
				/>
			</p>

			<p class="vamto-field vamto-copy-row">
				<button type="button" id="vamto-copy-btn" class="button button-primary">
					<?php esc_html_e( 'Copy AI Meta Data', 'verbatimai-meta-tags-optimizer' ); ?>
				</button>
				<span id="vamto-copy-status" class="vamto-copy-status" aria-live="polite"></span>
				<br />
				<span class="description">
					<?php esc_html_e( 'Copies the formatted meta tags and JSON-LD schema for this post to your clipboard.', 'verbatimai-meta-tags-optimizer' ); ?>
				</span>
			</p>

			<div class="vamto-footer">
				<?php
				printf(
					'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
					esc_url( 'https://www.ownyu.com/products/ai-meta-tags-optimizer' ),
					esc_html__( 'Plugin homepage', 'verbatimai-meta-tags-optimizer' )
				);
				?>
			</div>

		</div><!-- .vamto-wrap -->
		<?php
	}

	/**
	 * Save and sanitize meta box data.
	 *
	 * @param int $post_id Post ID being saved.
	 */
	public static function save_meta_box( $post_id ) {
		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		// Skip autosaves.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Capability check.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save the summary.
		if ( isset( $_POST['vamto_summary'] ) ) {
			$summary = sanitize_textarea_field( wp_unslash( $_POST['vamto_summary'] ) );
			update_post_meta( $post_id, self::META_SUMMARY, $summary );
		}

		// Save the entities field.
		if ( isset( $_POST['vamto_entities'] ) ) {
			$entities = sanitize_text_field( wp_unslash( $_POST['vamto_entities'] ) );
			update_post_meta( $post_id, self::META_ENTITIES, $entities );
		}

		// Save the Q&A repeater as a JSON-encoded array of sanitized pairs.
		if ( isset( $_POST['vamto_qa_question'] ) && isset( $_POST['vamto_qa_answer'] ) ) {
			$raw_questions = (array) wp_unslash( $_POST['vamto_qa_question'] );
			$raw_answers   = (array) wp_unslash( $_POST['vamto_qa_answer'] );

			$questions = array_map( 'sanitize_text_field', $raw_questions );
			$answers   = array_map( 'sanitize_textarea_field', $raw_answers );

			$pairs = array();
			$count = max( count( $questions ), count( $answers ) );

			for ( $i = 0; $i < $count; $i++ ) {
				$question = isset( $questions[ $i ] ) ? trim( $questions[ $i ] ) : '';
				$answer   = isset( $answers[ $i ] ) ? trim( $answers[ $i ] ) : '';

				if ( '' !== $question && '' !== $answer ) {
					$pairs[] = array(
						'question' => $question,
						'answer'   => $answer,
					);
				}
			}

			update_post_meta( $post_id, self::META_QA, wp_json_encode( $pairs ) );
		}
	}

	/**
	 * Output AI meta tags and JSON-LD schema in wp_head on singular posts/pages.
	 */
	public static function output_head_tags() {
		if ( ! is_singular( array( 'post', 'page' ) ) ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		$summary  = get_post_meta( $post_id, self::META_SUMMARY, true );
		$entities = get_post_meta( $post_id, self::META_ENTITIES, true );
		$qa_raw   = get_post_meta( $post_id, self::META_QA, true );

		$qa_pairs = array();
		if ( ! empty( $qa_raw ) ) {
			$decoded = json_decode( $qa_raw, true );
			if ( is_array( $decoded ) ) {
				$qa_pairs = $decoded;
			}
		}

		// Bail early if there is nothing to output.
		if ( empty( $summary ) && empty( $entities ) && empty( $qa_pairs ) ) {
			return;
		}

		echo "\n<!-- VerbatimAI Meta Tags Optimizer -->\n";

		if ( ! empty( $summary ) ) {
			printf( '<meta name="ai-summary" content="%s">' . "\n", esc_attr( $summary ) );
		}

		if ( ! empty( $entities ) ) {
			printf( '<meta name="ai-entities" content="%s">' . "\n", esc_attr( $entities ) );
		}

		$graph = array();

		if ( ! empty( $summary ) ) {
			$entity_list = array();
			if ( ! empty( $entities ) ) {
				$entity_list = array_filter( array_map( 'trim', explode( ',', $entities ) ) );
			}

			$graph[] = array(
				'@type'    => 'Article',
				'headline' => get_the_title( $post_id ),
				'url'      => get_permalink( $post_id ),
				'abstract' => $summary,
				'keywords' => implode( ', ', $entity_list ),
			);
		}

		if ( ! empty( $qa_pairs ) ) {
			$main_entity = array();

			foreach ( $qa_pairs as $pair ) {
				if ( empty( $pair['question'] ) || empty( $pair['answer'] ) ) {
					continue;
				}

				$main_entity[] = array(
					'@type'          => 'Question',
					'name'           => $pair['question'],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $pair['answer'],
					),
				);
			}

			if ( ! empty( $main_entity ) ) {
				$graph[] = array(
					'@type'      => 'FAQPage',
					'mainEntity' => $main_entity,
				);
			}
		}

		if ( ! empty( $graph ) ) {
			$json_ld = array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			);

			/*
			 * Encode with HEX flags so that <, >, &, ' and " are converted to \uXXXX
			 * escape sequences. This neutralizes any literal "</script>" sequence that
			 * could otherwise terminate the script element early and enable script
			 * injection, while still producing fully valid JSON-LD.
			 */
			$json_ld_encoded = wp_json_encode(
				$json_ld,
				JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_PRETTY_PRINT
			);

			if ( false !== $json_ld_encoded ) {
				echo '<script type="application/ld+json">' . "\n";
				// JSON_HEX_* flags above make this output safe to print as-is inside the script tag.
				echo $json_ld_encoded; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode() with JSON_HEX_* flags neutralizes HTML-significant characters.
				echo "\n" . '</script>' . "\n";
			}
		}

		echo "<!-- / VerbatimAI Meta Tags Optimizer -->\n";
	}
}

// Boot the plugin.
VerbatimAI_Meta_Tags_Optimizer::init();
