<footer class="site-footer">
  <div class="shell footer-grid">
    <div><a class="brand brand-light" href="<?php echo esc_url(home_url('/')); ?>"><span class="brand-mark" aria-hidden="true">✳</span><span>restaurant<span class="brand-dot">.</span></span></a><p>Fresh food, warm company, and a seat at the table.</p></div>
    <div><strong>Find us</strong><p>Makati City<br>Metro Manila</p></div>
    <div><strong>Opening hours</strong><p>Monday – Sunday<br>11:00 AM – 10:00 PM</p></div>
    <div><strong>Explore</strong><p><a href="<?php echo esc_url(home_url('/menu/')); ?>">Menu</a><br><a href="<?php echo esc_url(home_url('/reservations/')); ?>">Reservations</a><br><a href="<?php echo esc_url(home_url('/about/')); ?>">Our story</a></p></div>
  </div>
  <div class="shell footer-bottom"><span>© <?php echo esc_html(wp_date('Y')); ?> restaurant.</span><a href="<?php echo esc_url(home_url('/reservations/')); ?>">Reserve a table ↗</a></div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
