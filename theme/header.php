<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
  <div class="shell header-inner">
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Restaurant Demo home"><span class="brand-mark">✳</span><span>restaurant<span class="brand-dot">.</span></span></a>
    <nav class="main-nav" aria-label="Main navigation">
      <a href="<?php echo esc_url(home_url('/menu/')); ?>">Menu</a>
      <a href="<?php echo esc_url(home_url('/about/')); ?>">Our story</a>
      <a class="nav-cta" href="<?php echo esc_url(home_url('/reservations/')); ?>">Reserve a table <span aria-hidden="true">↗</span></a>
    </nav>
  </div>
</header>
