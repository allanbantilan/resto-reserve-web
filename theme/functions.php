<?php
defined('ABSPATH') || exit;

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('salo-fonts', 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap', [], null);
    wp_enqueue_style('salo', get_theme_file_uri('assets/site.css'), [], '2.0.0');
    wp_enqueue_script('salo', get_theme_file_uri('assets/site.js'), [], '2.0.0', true);
});

// Local Docker mail capture; production mail should use the host's SMTP setup.
if (getenv('RESTAURANT_DEMO_MAILPIT_HOST')) add_action('phpmailer_init', function ($mail) {
    $mail->isSMTP();
    $mail->Host = getenv('RESTAURANT_DEMO_MAILPIT_HOST');
    $mail->Port = 1025;
    $mail->SMTPAuth = false;
});

add_action('init', function () {
    register_taxonomy('salo_category', 'salo_dish', ['label' => 'Menu categories', 'hierarchical' => true, 'show_ui' => true, 'show_in_rest' => true]);
    register_post_type('salo_dish', [
        'labels' => ['name' => 'Menu dishes', 'singular_name' => 'Menu dish'],
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-food',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
        'taxonomies' => ['salo_category'],
    ]);
    register_post_type('salo_reservation', [
        'labels' => ['name' => 'Reservations', 'singular_name' => 'Reservation'],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => ['title'],
        'capability_type' => 'post',
    ]);
});

add_action('after_switch_theme', function () {
    foreach (['Home' => 'home', 'Menu' => 'menu', 'Reservations' => 'reservations', 'About' => 'about'] as $title => $slug) {
        $page = get_page_by_path($slug);
        if (!$page) {
            $id = wp_insert_post(['post_title' => $title, 'post_name' => $slug, 'post_type' => 'page', 'post_status' => 'publish'], true);
            if (!is_wp_error($id) && $slug === 'home') $page = get_post($id);
        }
        if ($slug === 'home' && $page && !get_option('page_on_front')) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $page->ID);
        }
    }
});

add_action('init', function () {
    if (!add_option('salo_dishes_seeded', 1)) return;
    foreach (['Bowls', 'Small plates', 'Drinks'] as $group) if (!term_exists($group, 'salo_category')) wp_insert_term($group, 'salo_category');
    $dishes = [
        ['Signature Rice Bowl', 'Bowls', 249, 'Garlic rice, grilled chicken, crisp vegetables and house sauce.', 'Bestseller', ''],
        ['Garden Bowl', 'Bowls', 229, 'Fresh greens, grains, avocado and a bright sesame dressing.', 'Plant based', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=900&q=85'],
        ['Calamansi Chicken', 'Bowls', 269, 'Smoky chicken, citrus glaze and colorful market vegetables.', 'Bestseller', 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?w=900&q=85'],
        ['Garlic Fries', 'Small plates', 119, 'Golden fries tossed with garlic and herbs.', 'Vegetarian', 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=900&q=85'],
        ['Grilled Fish Plate', 'Small plates', 149, 'Grilled fish with fresh herbs and citrus.', 'Favorite', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=900&q=85'],
        ['Iced Calamansi Tea', 'Drinks', 99, 'Fresh citrus tea, served cold.', 'Refreshing', 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=900&q=85'],
        ['Mango Cream Soda', 'Drinks', 119, 'A tropical, creamy sparkle.', 'Refreshing', 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=900&q=85'],
    ];
    foreach ($dishes as [$name, $group, $price, $description, $badge, $image]) {
        $id = wp_insert_post(['post_type' => 'salo_dish', 'post_status' => 'publish', 'post_title' => $name, 'post_excerpt' => $description], true);
        if (is_wp_error($id)) continue;
        $term = get_term_by('name', $group, 'salo_category');
        if ($term) wp_set_post_terms($id, [$term->term_id], 'salo_category');
        update_post_meta($id, '_salo_price', $price);
        update_post_meta($id, '_salo_badge', $badge);
        update_post_meta($id, '_salo_image', $image);
    }
}, 20);

function salo_dish_image($id) {
    return get_the_post_thumbnail_url($id, 'large') ?: (get_post_meta($id, '_salo_image', true) ?: get_theme_file_uri('assets/hero.webp'));
}

add_action('add_meta_boxes_salo_dish', function () {
    add_meta_box('salo_dish_details', 'Menu details', function ($post) {
        wp_nonce_field('salo_dish_details', 'salo_dish_nonce');
        foreach (['_salo_price' => 'Price (PHP)', '_salo_badge' => 'Badge', '_salo_image' => 'Image URL (used when there is no featured image)'] as $key => $label) {
            echo '<p><label for="' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label><br><input class="widefat" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr(get_post_meta($post->ID, $key, true)) . '"></p>';
        }
    }, 'salo_dish');
});

add_action('save_post_salo_dish', function ($id) {
    if (!isset($_POST['salo_dish_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['salo_dish_nonce'])), 'salo_dish_details') || !current_user_can('edit_post', $id) || wp_is_post_autosave($id)) return;
    update_post_meta($id, '_salo_price', max(0, (float) ($_POST['_salo_price'] ?? 0)));
    update_post_meta($id, '_salo_badge', sanitize_text_field(wp_unslash($_POST['_salo_badge'] ?? '')));
    update_post_meta($id, '_salo_image', esc_url_raw(wp_unslash($_POST['_salo_image'] ?? '')));
});

function salo_slots() {
    return ['11:00', '12:30', '14:00', '15:30', '17:00', '18:30', '20:00'];
}

add_action('admin_post_nopriv_salo_reserve', 'salo_reserve');
add_action('admin_post_salo_reserve', 'salo_reserve');
function salo_reserve() {
    $back = home_url('/reservations/');
    if (!isset($_POST['salo_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['salo_nonce'])), 'salo_reserve')) wp_die('Please reload the booking page and try again.', 'Invalid request', ['response' => 403]);
    $error = function ($code) use ($back) { wp_safe_redirect(add_query_arg('error', $code, $back)); exit; };
    if (!empty($_POST['website'])) $error('invalid');
    foreach (['date', 'time', 'name', 'email', 'phone', 'party', 'note'] as $key) if (isset($_POST[$key]) && !is_string($_POST[$key])) $error('invalid');
    $date = sanitize_text_field(wp_unslash($_POST['date'] ?? ''));
    $time = sanitize_text_field(wp_unslash($_POST['time'] ?? ''));
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $party = filter_var($_POST['party'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 8]]);
    $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
    $today = new DateTimeImmutable('today', wp_timezone());
    $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date, wp_timezone());
    $visit = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, wp_timezone());
    if (!$day || $day->format('Y-m-d') !== $date || !$visit || $day < $today || $day > $today->modify('+60 days') || $visit < new DateTimeImmutable('+2 hours', wp_timezone()) || !in_array($time, salo_slots(), true)) $error('date');
    if (!$party || !$name || strlen($name) > 80 || !is_email($email) || strlen($phone) > 25 || !preg_match('/^\+?[0-9 ()-]+$/', $phone) || preg_match_all('/\d/', $phone) < 7 || strlen($note) > 500) $error('details');
    // shortcut: ten parties per 90-minute slot, replace with table-level capacity when the real floor plan is known.
    $existing = get_posts(['post_type' => 'salo_reservation', 'post_status' => 'publish', 'numberposts' => 10, 'fields' => 'ids', 'meta_query' => [['key' => '_salo_date', 'value' => $date], ['key' => '_salo_time', 'value' => $time]]]);
    if (count($existing) >= 10) $error('full');
    $id = wp_insert_post(['post_type' => 'salo_reservation', 'post_status' => 'publish', 'post_title' => $name . ' — ' . $date . ' ' . $time], true);
    if (is_wp_error($id) || !$id) $error('save');
    foreach (['_salo_date' => $date, '_salo_time' => $time, '_salo_party' => $party, '_salo_name' => $name, '_salo_email' => $email, '_salo_phone' => $phone, '_salo_note' => $note] as $key => $value) update_post_meta($id, $key, $value);
    $reference = 'RES-' . str_pad($id, 5, '0', STR_PAD_LEFT);
    $details = "Reservation $reference\n$name · $party guests\n$date at $time\nPhone: $phone\nEmail: $email\nNotes: $note";
    wp_mail($email, 'Your Restaurant Demo reservation ' . $reference, "Your table is reserved. See you soon!\n\n" . $details);
    wp_mail(get_option('admin_email'), 'New Restaurant Demo reservation ' . $reference, $details);
    wp_safe_redirect(add_query_arg(['booking' => $reference, 'proof' => wp_create_nonce('salo_booking_' . $id)], $back));
    exit;
}

add_filter('manage_salo_reservation_posts_columns', function ($columns) {
    return ['cb' => $columns['cb'], 'title' => 'Guest', 'visit' => 'Date & time', 'party' => 'Party', 'contact' => 'Contact', 'date' => 'Submitted'];
});
add_action('manage_salo_reservation_posts_custom_column', function ($column, $id) {
    if ($column === 'visit') echo esc_html(get_post_meta($id, '_salo_date', true) . ' · ' . get_post_meta($id, '_salo_time', true));
    if ($column === 'party') echo esc_html(get_post_meta($id, '_salo_party', true));
    if ($column === 'contact') echo esc_html(get_post_meta($id, '_salo_email', true) . ' · ' . get_post_meta($id, '_salo_phone', true));
}, 10, 2);
add_action('add_meta_boxes_salo_reservation', function () {
    add_meta_box('salo_booking', 'Booking details', function ($post) {
        foreach (['_salo_date' => 'Date', '_salo_time' => 'Time', '_salo_party' => 'Guests', '_salo_name' => 'Name', '_salo_email' => 'Email', '_salo_phone' => 'Phone', '_salo_note' => 'Notes'] as $key => $label) {
            echo '<p><strong>' . esc_html($label) . ':</strong> ' . nl2br(esc_html(get_post_meta($post->ID, $key, true))) . '</p>';
        }
        echo '<p>Move this reservation to Trash to release its slot.</p>';
    }, 'salo_reservation');
});
