<?php
get_header();
$error = sanitize_key($_GET['error'] ?? '');
$booking = sanitize_text_field($_GET['booking'] ?? '');
$booking_id = preg_match('/^RES-([0-9]+)$/', $booking, $match) ? (int) $match[1] : 0;
$confirmed = $booking_id && get_post_type($booking_id) === 'salo_reservation' && wp_verify_nonce(sanitize_text_field($_GET['proof'] ?? ''), 'salo_booking_' . $booking_id);
$messages = [
    'date' => 'Choose a date and time at least two hours from now, within the next 60 days.',
    'details' => 'Check your name, email, phone number, and party size.',
    'full' => 'That time is fully booked. Please choose another time.',
    'save' => 'We could not save your reservation. Please try again or call us.',
    'invalid' => 'Please check the form and try again.',
];
?>
<main id="main" class="reservation-page">
  <div class="shell reservation-grid">
    <div class="reservation-intro"><span class="eyebrow">THERE'S ROOM FOR YOU</span><h1>Let’s make<br><em>plans.</em></h1><p>Good food is even better when we know you’re coming. Pick a time, bring your people, and leave the rest to us.</p><div class="reservation-photo"><img src="<?php echo esc_url(get_theme_file_uri('assets/hero.webp')); ?>" alt="Grilled chicken and fresh vegetables in a rice bowl" loading="lazy"></div><div class="reservation-facts"><div><strong>Sample hours</strong><span>Every day · 11:00 AM – 10:00 PM</span></div><div><strong>Sample location</strong><span>Makati City, Metro Manila</span></div></div></div>
    <div class="reservation-card">
      <?php if ($confirmed) : ?>
        <div class="reservation-success" role="status"><span class="success-icon" aria-hidden="true">✓</span><span class="eyebrow">SEE YOU SOON</span><h2>Your table is reserved.</h2><p>Your booking reference is <strong><?php echo esc_html($booking); ?></strong>. A confirmation email is on its way if mail delivery is configured.</p><a class="button button-dark" href="<?php echo esc_url(home_url('/menu/')); ?>">Browse the menu <span aria-hidden="true">↗</span></a></div>
      <?php else : ?>
        <div class="form-heading"><span class="eyebrow">BOOK YOUR VISIT</span><h2>Save your seat.</h2><p>For groups of 1–8. Reservations open up to 60 days ahead.</p></div>
        <?php if (isset($messages[$error])) : ?><p class="form-alert" role="alert"><?php echo esc_html($messages[$error]); ?></p><?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="reservation-form">
          <input type="hidden" name="action" value="salo_reserve">
          <?php wp_nonce_field('salo_reserve', 'salo_nonce', false); ?>
          <div class="honeypot" aria-hidden="true"><label for="website">Leave this empty</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
          <div class="field-row"><div class="field"><label for="date">Date <span>*</span></label><input id="date" name="date" type="date" required min="<?php echo esc_attr(current_datetime()->format('Y-m-d')); ?>" max="<?php echo esc_attr(current_datetime()->modify('+60 days')->format('Y-m-d')); ?>"></div><div class="field"><label for="time">Time <span>*</span></label><select id="time" name="time" required><option value="">Choose a time</option><?php foreach (salo_slots() as $slot) : ?><option value="<?php echo esc_attr($slot); ?>"><?php echo esc_html(DateTimeImmutable::createFromFormat('H:i', $slot)->format('g:i A')); ?></option><?php endforeach; ?></select></div></div>
          <div class="field"><label for="party">How many guests? <span>*</span></label><select id="party" name="party" required><option value="">Choose party size</option><?php for ($n = 1; $n <= 8; $n++) : ?><option value="<?php echo esc_attr($n); ?>"><?php echo esc_html($n . ($n === 1 ? ' guest' : ' guests')); ?></option><?php endfor; ?></select></div>
          <div class="field-divider"><span>YOUR DETAILS</span></div>
          <div class="field"><label for="name">Your name <span>*</span></label><input id="name" name="name" type="text" autocomplete="name" maxlength="80" required placeholder="First and last name"></div>
          <div class="field-row"><div class="field"><label for="email">Email address <span>*</span></label><input id="email" name="email" type="email" autocomplete="email" required placeholder="you@example.com"></div><div class="field"><label for="phone">Phone number <span>*</span></label><input id="phone" name="phone" type="tel" autocomplete="tel" required placeholder="+63 912 345 6789"></div></div>
          <div class="field"><label for="note">Anything we should know? <small>Optional</small></label><textarea id="note" name="note" rows="3" maxlength="500" placeholder="A celebration, accessibility needs, or anything else"></textarea></div>
          <button class="button button-dark button-full" type="submit">Confirm reservation <span aria-hidden="true">↗</span></button><p class="form-note">Your reservation is confirmed when you see a booking reference. For changes, contact the restaurant directly.</p>
        </form>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php get_footer(); ?>
