<?php
get_header();

$collection = get_post(); // Get the current collection post
$videos = get_field('videos', $collection->ID); // ACF relationship field

$image = get_field('image', $collection->ID);
$description = get_field('description', $collection->ID);
$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg';
?>

<main class="main">

		<?php get_template_part( 'template-parts/modules/page-header' ); ?>

    <?php if (!empty($videos)) : ?>
        <div class="grid-container full">
            <div class="cards">
                <?php foreach ($videos as $video_post) :
                    setup_postdata($video_post);

                    $vimeoUrl = get_field('video', $video_post->ID);
                    $video_length = get_field('video_length', $video_post->ID);
                    $videoId = getVimeoVideoId($vimeoUrl);
                    $thumbnail_url = has_post_thumbnail($video_post) ? get_the_post_thumbnail_url($video_post, 'fp-small') : $placeholder_url;
                    ?>

                    <div class="cards__item">
                        <a href="<?php echo esc_url(get_permalink($video_post->ID)); ?>">
                            <figure class="cards__figure">
                                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title($video_post)); ?>">

                                <?php if ($video_length) : ?>
                                    <div class="cards__length">
                                        <?php echo esc_html($video_length); ?>
                                    </div>
                                <?php endif; ?>
                            </figure>
                            <div class="cards__header">
                                <h3 class="cards__title"><?php echo esc_html(get_the_title($video_post)); ?></h3>
                            </div>
                        </a>
                    </div>

                <?php endforeach;
                wp_reset_postdata(); ?>
            </div>
        </div>
    <?php endif; ?>

</main>

<?php get_footer(); ?>
