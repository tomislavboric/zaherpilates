<?php get_header();

// Get term information
$term = get_queried_object(); // This gets the current taxonomy term object
$image = get_field('image', $term);
$description = get_field('description', $term);

// Placeholder image URL for posts without a thumbnail
$placeholder_url = get_stylesheet_directory_uri() . '/dist/assets/images/placeholder.jpg';

?>

<main class="main">

    <header class="page__header">
        <div class="grid-container full">

            <div class="page__header-grid">

                <div class="page__header-content">
                    <?php /* Breadcrumbs
                    <div class="category__breadcrumbs">
                        <div class="breadcrumbs">
                            <?php if (function_exists('rank_math_the_breadcrumbs')) rank_math_the_breadcrumbs(); ?>
                        </div>
                    </div>
                    */ ?>
                    <h1 class="page__title"><?php single_term_title(); ?></h1>

                    <?php if ($description) : ?>
                        <div class="page__desc">
                            <?php echo esc_html($description); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($image && $description) : ?>
                    <figure class="page__header-figure">
                        <img src="<?php echo esc_url($image['sizes']['fp-small']); ?>" alt="<?php echo esc_attr(single_term_title('', false)); ?>">
                    </figure>
                <?php endif; ?>

            </div>

        </div>
    </header>

    <?php if ( have_posts() ) : ?>

        <div class="grid-container full">

            <div class="cards">

                <?php while ( have_posts() ) : the_post();

                    // Variables for video information
                    $vimeoUrl = get_field('video', get_the_ID());
                    $video_length = get_field('video_length', get_the_ID());
                    $videoId = getVimeoVideoId($vimeoUrl);

                    // Thumbnail or placeholder URL
                    $thumbnail_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'fp-small') : $placeholder_url;
                    ?>

                    <div class="cards__item">
                        <a href="<?php the_permalink(); ?>">
                            <figure class="cards__figure">
                                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php the_title_attribute(); ?>">

                                <?php if ($video_length) : ?>
                                    <div class="cards__length">
                                        <?php echo esc_html($video_length); ?>
                                    </div>
                                <?php endif; ?>
                            </figure>
                            <div class="cards__header">
                                <h3 class="cards__title"><?php the_title(); ?></h3>
                            </div>
                        </a>
                    </div>

                <?php endwhile; ?>

            </div>

        </div>

    <?php endif; // End have_posts() check. ?>

</main>

<?php get_footer(); ?>
