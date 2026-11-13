<?php
/**
 * Template Name: PC Builder India
 * Template Post Type: page
 */

get_header();
?>
<section class="pc-builder-page-banner" aria-labelledby="pc-builder-page-title">
	<div class="pc-builder-page-banner__content">
		<p class="pc-builder-page-banner__eyebrow"><?php esc_html_e( 'Build with confidence', 'hello-elementor' ); ?></p>
		<h1 id="pc-builder-page-title"><?php esc_html_e( 'PC Builder India', 'hello-elementor' ); ?></h1>
		<p class="pc-builder-page-banner__description"><?php esc_html_e( 'Pick Eight Parts Plus Case Fans, See What They Cost, And Catch Compatibility Problems Before You Buy.', 'hello-elementor' ); ?></p>
		<nav aria-label="<?php esc_attr_e( 'Breadcrumb', 'hello-elementor' ); ?>">
			<ol class="pc-builder-page-banner__breadcrumb">
				<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'hello-elementor' ); ?></a></li>
				<li aria-current="page"><?php esc_html_e( 'PC Builder India', 'hello-elementor' ); ?></li>
			</ol>
		</nav>
	</div>
	<img
		class="pc-builder-page-banner__image"
		src="<?php echo esc_url( content_url( 'uploads/2025/02/pc-builder-india-banner.webp' ) ); ?>"
		alt=""
		fetchpriority="high"
		aria-hidden="true"
	/>
</section>
<main id="content" class="site-main pc-builder-india-content">
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	?>
</main>
<style>
	.pc-builder-india-content {
		width: 100%;
		max-width: none;
		margin: 0;
		padding: 0;
	}

	.pc-builder-india-frame {
		display: block;
		width: 100%;
		min-height: 100vh;
		border: 0;
	}

	.pc-builder-page header.elementor-location-header .elementor-widget-image img[alt="PC Pulses"] {
		width: 100%;
		height: 76px;
		object-fit: contain;
		object-position: center;
	}

	.pc-builder-page-banner {
		display: grid;
		grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
		align-items: center;
		box-sizing: border-box;
		width: 100%;
		min-height: 438px;
		gap: clamp(24px, 5vw, 64px);
		margin-top: -96px;
		padding: 116px max(24px, calc((100vw - 1120px) / 2)) 40px;
		overflow: hidden;
		background: linear-gradient(118deg, #172238, #24364e);
		color: #fff;
		text-align: left;
	}

	.pc-builder-page-banner__image {
		grid-column: 2;
		grid-row: 1;
		width: 100%;
		height: auto;
		aspect-ratio: 16 / 9;
		border: 1px solid rgb(255 255 255 / 0.12);
		border-radius: 20px;
		object-fit: cover;
		object-position: center;
		box-shadow: 0 20px 48px rgb(5 12 26 / 0.28);
	}

	.pc-builder-page-banner__content {
		position: relative;
		max-width: 34rem;
	}

	.pc-builder-page-banner__eyebrow {
		margin: 0 0 14px;
		color: #ff9aa5;
		font-family: Inter, Arial, sans-serif;
		font-size: 12px;
		font-weight: 700;
		letter-spacing: 0.1em;
		line-height: 1.4;
		text-transform: uppercase;
	}

	.pc-builder-page-banner h1 {
		margin: 0;
		color: #fff;
		font-family: Inter, Arial, sans-serif;
		font-size: clamp(30px, 3.6vw, 44px);
		font-weight: 700;
		letter-spacing: -0.035em;
		line-height: 1.15;
	}

	.pc-builder-page-banner__description {
		max-width: 38ch;
		margin: 16px 0 0;
		color: rgb(255 255 255 / 0.82);
		font-family: Heebo, Arial, sans-serif;
		font-size: 16px;
		font-weight: 400;
		line-height: 1.6;
	}

	.pc-builder-page-banner__breadcrumb {
		display: flex;
		flex-wrap: wrap;
		justify-content: flex-start;
		gap: 12px;
		margin: 22px 0 0;
		padding: 0;
		list-style: none;
		color: rgb(255 255 255 / 0.66);
		font-family: Heebo, Arial, sans-serif;
		font-size: 14px;
		line-height: 1.5;
	}

	.pc-builder-page-banner__breadcrumb li + li::before {
		margin-right: 12px;
		color: #ff4255;
		content: "›";
		font-size: 18px;
		font-weight: 700;
		vertical-align: -1px;
	}

	.pc-builder-page-banner__breadcrumb a {
		color: inherit;
		text-decoration: none;
	}

	.pc-builder-page-banner__breadcrumb a:hover {
		color: #fff;
	}

	@media (max-width: 767px) {
		.pc-builder-page-banner {
			grid-template-columns: minmax(0, 1fr);
			gap: 24px;
			min-height: 0;
			padding: 116px 24px 32px;
		}

		.pc-builder-page-banner__image {
			grid-column: 1;
			grid-row: 2;
		}

		.pc-builder-page-banner__description {
			font-size: 15px;
		}
	}
</style>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		var siteLogo = document.querySelector('.pc-builder-page header.elementor-location-header .elementor-widget-image img[alt="PC Pulses"]');

		if (siteLogo) {
			siteLogo.src = '<?php echo esc_url( content_url( 'uploads/elementor/thumbs/Logo-1-rirbmt7fqwi490t64uz00zq7nt4jcrhidb3mtvhcq0.png' ) ); ?>';
			siteLogo.removeAttribute('srcset');
			siteLogo.removeAttribute('sizes');
		}

		var builderFrame = document.querySelector('.pc-builder-india-frame');

		if (!builderFrame) {
			return;
		}

		function resizeBuilderFrame() {
			var builderDocument = builderFrame.contentDocument;

			if (!builderDocument) {
				return;
			}

			var height = Math.max(
				builderDocument.documentElement.scrollHeight,
				builderDocument.documentElement.offsetHeight,
				builderDocument.body ? builderDocument.body.scrollHeight : 0
			);

			if (height > 0 && builderFrame.clientHeight !== height) {
				builderFrame.style.height = Math.ceil(height) + 'px';
			}
		}

		builderFrame.addEventListener('load', function () {
			resizeBuilderFrame();
			window.requestAnimationFrame(resizeBuilderFrame);
		});
		resizeBuilderFrame();

		window.addEventListener('message', function (event) {
			if (event.origin !== window.location.origin || event.source !== builderFrame.contentWindow) {
				return;
			}

			if (
				event.data
				&& event.data.type === 'pc-builder-height'
				&& Number.isFinite(event.data.height)
				&& event.data.height > 0
			) {
				var height = Math.ceil(event.data.height);
				if (builderFrame.clientHeight !== height) {
					builderFrame.style.height = height + 'px';
				}
			}
		});
	});
</script>
<?php
get_footer();
