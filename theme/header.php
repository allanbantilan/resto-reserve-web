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
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Restaurant home"><span class="brand-mark" aria-hidden="true">✳</span><span>restaurant<span class="brand-dot">.</span></span></a>
    <details class="mobile-menu"><summary><span class="mobile-menu-icon" aria-hidden="true"></span><span class="sr-only">Navigation menu</span></summary><nav class="mobile-menu-panel" aria-label="Mobile navigation"><a href="<?php echo esc_url(home_url('/menu/')); ?>">Explore the menu</a><a href="<?php echo esc_url(home_url('/about/')); ?>">Our story</a><a href="<?php echo esc_url(home_url('/reservations/')); ?>">Reserve a table</a></nav></details>
    <nav class="main-nav" aria-label="Main navigation">
      <a href="<?php echo esc_url(home_url('/menu/')); ?>">Menu</a>
      <a href="<?php echo esc_url(home_url('/about/')); ?>">Our story</a>
      <a class="nav-cta" href="<?php echo esc_url(home_url('/reservations/')); ?>"><span class="nav-label-long">Reserve a table</span><span class="nav-label-short">Reserve</span><span aria-hidden="true">↗</span></a>
    </nav>
  </div>
</header>
