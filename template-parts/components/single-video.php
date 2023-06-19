<?php
$vimeoUrl = get_field('video', get_the_ID());
$videoId = getVimeoVideoId($vimeoUrl);
?>

<?php if ( custom_can_user_view_content('mjesecna-pretplata') ) : ?>
	<div class="responsive-embed widescreen">
		<a data-fancybox href="https://vimeo.com/<?php echo $videoId; ?>">
			<figure>
					<img
							srcset="
							https://vumbnail.com/<?php echo $videoId; ?>.jpg 640w,
							https://vumbnail.com/<?php echo $videoId; ?>_large.jpg 640w,
							https://vumbnail.com/<?php echo $videoId; ?>_medium.jpg 200w,
							https://vumbnail.com/<?php echo $videoId; ?>_small.jpg 100w
							"
							sizes="(max-width: 640px) 100vw, 640px"
							src="https://vumbnail.com/<?php echo $videoId; ?>.jpg"
							alt="Vimeo Thumbnail"
							width="640"
							height="360"
					/>
					<button id="play-video" class="video-play-button">
							<span></span>
					</button>
			</figure>
		</a>
	</div>
	<?php else : ?>
		<figure>
			<img
					srcset="
					https://vumbnail.com/<?php echo $videoId; ?>.jpg 640w,
					https://vumbnail.com/<?php echo $videoId; ?>_large.jpg 640w,
					https://vumbnail.com/<?php echo $videoId; ?>_medium.jpg 200w,
					https://vumbnail.com/<?php echo $videoId; ?>_small.jpg 100w
					"
					sizes="(max-width: 640px) 100vw, 640px"
					src="https://vumbnail.com/<?php echo $videoId; ?>.jpg"
					alt="Vimeo Thumbnail"
					width="640"
					height="360"
			/>
		</figure>
<?php endif; ?>
