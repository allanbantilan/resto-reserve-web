<?php get_header(); ?>
<main id="main" class="shell page-hero"><h1><?php single_post_title(); ?></h1><?php if (have_posts()) : while (have_posts()) : the_post(); the_content(); endwhile; endif; ?></main>
<?php get_footer(); ?>
