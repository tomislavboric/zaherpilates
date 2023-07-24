<?php
/*
Template Name: category
*/
get_header();

// vars
$term = get_queried_object(); // This gets the current taxonomy term object
$image = get_field('image', $term);
$description = get_field('description', $term);

?>

<main class="main">

	<header class="category__header">
		<div class="grid-container full">
			<h1 class="category__title"><?php single_term_title(); ?></h1>
			<div class="category__description">
				<?php echo $description; ?>
			</div>
		</div>
	</header>

	<?php if ( have_posts() ) : ?>

		<div class="grid-container full">

			<div class="cards">

				<?php while ( have_posts() ) : the_post();

				// vars
				$vimeoUrl = get_field('video', get_the_ID());
				$videoId = getVimeoVideoId($vimeoUrl);

				// Initialize cURL
				$curl = curl_init();

				curl_setopt_array($curl, array(
					CURLOPT_URL => "https://api.vimeo.com/videos/{$videoId}",
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_HTTPHEADER => array(
						'Authorization: Bearer 5709cd8b25a785d7b3b05a549c856df1x'
					),
				));

				// Execute the request
				$response = curl_exec($curl);
				$err = curl_error($curl);

				curl_close($curl);

				$formattedVideoDuration = '';

				if ($err) {
					// cURL encountered an error during the request
					// Handle this error in an appropriate way for your application
					error_log("cURL Error #: {$err}");
				} else {
					// No cURL error, so parse the response
					$obj = json_decode($response);

					// Check if the response is valid and contains a duration
					if ($obj && property_exists($obj, 'duration')) {
						// Format video duration as mm:ss
						$minutes = floor($obj->duration / 60);
						$seconds = $obj->duration % 60;
						$formattedVideoDuration = sprintf("%02d:%02d", $minutes, $seconds);
					} else {
						// The response is not valid or doesn't contain a duration
						// Handle this error in an appropriate way for your application
						error_log("Invalid Vimeo API response for video ID {$videoId}: {$response}");
					}
				}
				?>

					<div class="cards__item">
						<a href="<?php the_permalink(); ?>">
							<figure>
								<img
									srcset="
									https://vumbnail.com/<?php echo $videoId; ?>.jpg 640w,
									https://vumbnail.com/<?php echo $videoId; ?>_large.jpg 640w,
									https://vumbnail.com/<?php echo $videoId; ?>_medium.jpg 200w,
									https://vumbnail.com/<?php echo $videoId; ?>_small.jpg 100w
									"
									src="https://vumbnail.com/<?php echo $videoId; ?>.jpg"
									alt="<?php the_title(); ?>"
									width="640"
									height="360"
								/>
							</figure>
							<div class="cards__header">
								<h3 class="cards__title"><?php the_title(); ?></h3>
								<div class="cards__length"><?php echo $formattedVideoDuration; ?></div>
							</div>
						</a>
					</div>

				<?php endwhile; ?>

			</div>

		</div>

	<?php endif; // End have_posts() check. ?>

</main>

<?php get_footer();
