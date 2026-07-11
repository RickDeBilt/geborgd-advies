<?php
/**
 * Plugin Name:       Geborgd Advies — Actueel CMS
 * Description:        Voegt de velden toe die de website voor "Actueel"-artikelen gebruikt (categorie, In het kort, bronnen, SEO) en stelt ze beschikbaar via de REST API voor de headless Astro-frontend.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Geborgd Advies
 * Text Domain:       geborgd-advies-cms
 *
 * Uitleg en installatie: zie docs/WORDPRESS.md in de website-repository.
 */

if (!defined('ABSPATH')) {
    exit; // Directe toegang blokkeren.
}

const GA_CMS_META_PREFIX = 'ga_';

/**
 * De categorieën die de website ondersteunt. Moeten exact overeenkomen met
 * src/data/categories.ts in de website-repository.
 */
const GA_CMS_CATEGORIES = [
    'Financieel',
    'Personeel',
    'Wet- en regelgeving',
    'Ondernemen',
    'WIA & IVA',
];

/* -------------------------------------------------------------------------
 * 1. Meta-velden registreren en beschikbaar maken via de REST API
 * ---------------------------------------------------------------------- */

add_action('init', 'ga_cms_register_meta');

function ga_cms_register_meta(): void
{
    $can_edit = static function (): bool {
        return current_user_can('edit_posts');
    };

    register_post_meta('post', 'ga_seo_title', [
        'type'          => 'string',
        'single'        => true,
        'show_in_rest'  => true,
        'auth_callback' => $can_edit,
        'default'       => '',
    ]);

    register_post_meta('post', 'ga_intro', [
        'type'          => 'string',
        'single'        => true,
        'show_in_rest'  => true,
        'auth_callback' => $can_edit,
        'default'       => '',
    ]);

    register_post_meta('post', 'ga_reviewed_date', [
        'type'          => 'string', // ISO-datum YYYY-MM-DD
        'single'        => true,
        'show_in_rest'  => true,
        'auth_callback' => $can_edit,
        'default'       => '',
    ]);

    register_post_meta('post', 'ga_author_role', [
        'type'          => 'string',
        'single'        => true,
        'show_in_rest'  => true,
        'auth_callback' => $can_edit,
        'default'       => '',
    ]);

    register_post_meta('post', 'ga_image_alt', [
        'type'          => 'string',
        'single'        => true,
        'show_in_rest'  => true,
        'auth_callback' => $can_edit,
        'default'       => '',
    ]);

    register_post_meta('post', 'ga_featured', [
        'type'          => 'boolean',
        'single'        => true,
        'show_in_rest'  => true,
        'auth_callback' => $can_edit,
        'default'       => false,
    ]);

    register_post_meta('post', 'ga_key_takeaways', [
        'type'          => 'array',
        'single'        => true,
        'auth_callback' => $can_edit,
        'default'       => [],
        'show_in_rest'  => [
            'schema' => [
                'type'  => 'array',
                'items' => ['type' => 'string'],
            ],
        ],
    ]);

    register_post_meta('post', 'ga_sources', [
        'type'          => 'array',
        'single'        => true,
        'auth_callback' => $can_edit,
        'default'       => [],
        'show_in_rest'  => [
            'schema' => [
                'type'  => 'array',
                'items' => [
                    'type'       => 'object',
                    'properties' => [
                        'name'  => ['type' => 'string'],
                        'title' => ['type' => 'string'],
                        'url'   => ['type' => 'string', 'format' => 'uri'],
                    ],
                ],
            ],
        ],
    ]);
}

/* -------------------------------------------------------------------------
 * 2. Bewerkscherm (meta box) voor de redacteur
 * ---------------------------------------------------------------------- */

add_action('add_meta_boxes', 'ga_cms_add_meta_box');

function ga_cms_add_meta_box(): void
{
    add_meta_box(
        'ga_cms_article_fields',
        'Actueel — artikelgegevens',
        'ga_cms_render_meta_box',
        'post',
        'normal',
        'high'
    );
}

function ga_cms_render_meta_box(WP_Post $post): void
{
    wp_nonce_field('ga_cms_save', 'ga_cms_nonce');

    $seo_title    = (string) get_post_meta($post->ID, 'ga_seo_title', true);
    $intro        = (string) get_post_meta($post->ID, 'ga_intro', true);
    $reviewed     = (string) get_post_meta($post->ID, 'ga_reviewed_date', true);
    $author_role  = (string) get_post_meta($post->ID, 'ga_author_role', true);
    $image_alt    = (string) get_post_meta($post->ID, 'ga_image_alt', true);
    $featured     = (bool) get_post_meta($post->ID, 'ga_featured', true);

    $takeaways = get_post_meta($post->ID, 'ga_key_takeaways', true);
    $takeaways = is_array($takeaways) ? implode("\n", $takeaways) : '';

    $sources = get_post_meta($post->ID, 'ga_sources', true);
    $sources_text = '';
    if (is_array($sources)) {
        foreach ($sources as $s) {
            if (!is_array($s)) {
                continue;
            }
            $sources_text .= sprintf(
                "%s | %s | %s\n",
                $s['name'] ?? '',
                $s['title'] ?? '',
                $s['url'] ?? ''
            );
        }
    }
    ?>
    <style>
        .ga-field { margin: 0 0 18px; }
        .ga-field > label { display:block; font-weight:600; margin-bottom:4px; }
        .ga-field .description { color:#646970; font-size:12px; margin-top:4px; }
        .ga-field input[type="text"],
        .ga-field input[type="date"],
        .ga-field textarea { width:100%; max-width:680px; }
        .ga-field textarea { min-height:90px; font-family:inherit; }
    </style>

    <p style="color:#646970;">
        Deze gegevens bepalen hoe het artikel op de website getoond wordt.
        De hoofdtekst schrijft u hierboven in de gewone editor.
        De <strong>categorie</strong> en de <strong>uitgelichte afbeelding</strong>
        stelt u rechts in het zijpaneel in. De <strong>samenvatting</strong>
        (het korte introzinnetje in de lijst) komt uit het veld
        <em>Samenvatting</em> — zet dit zichtbaar via "Schermopties" rechtsboven.
    </p>

    <div class="ga-field">
        <label for="ga_intro">Intro (korte tekst bovenaan het artikel)</label>
        <textarea id="ga_intro" name="ga_intro"><?php echo esc_textarea($intro); ?></textarea>
        <p class="description">Optioneel. Valt terug op de samenvatting als u dit leeg laat.</p>
    </div>

    <div class="ga-field">
        <label for="ga_key_takeaways">In het kort (één punt per regel)</label>
        <textarea id="ga_key_takeaways" name="ga_key_takeaways" placeholder="Bijv.
Sinds 1 juli gelden nieuwe minimumuurlonen.
Controleer uw loonadministratie op de nieuwe bedragen."><?php echo esc_textarea($takeaways); ?></textarea>
        <p class="description">Verschijnt als opvallend blok bovenaan. Laat leeg om het blok te verbergen.</p>
    </div>

    <div class="ga-field">
        <label for="ga_sources">Bronnen (één per regel: <code>Naam | Titel | https://url</code>)</label>
        <textarea id="ga_sources" name="ga_sources" placeholder="Rijksoverheid | Bedragen minimumloon 2026 | https://www.rijksoverheid.nl/..."><?php echo esc_textarea($sources_text); ?></textarea>
        <p class="description">Gebruik het teken <code>|</code> om naam, titel en URL te scheiden. Laat leeg als er geen bronnen zijn.</p>
    </div>

    <div class="ga-field">
        <label>
            <input type="checkbox" name="ga_featured" value="1" <?php checked($featured); ?> />
            Uitlichten op de Actueel-pagina
        </label>
        <p class="description">Er wordt steeds één artikel groot bovenaan getoond. Vinkt u er meerdere aan, dan wint het nieuwste.</p>
    </div>

    <div class="ga-field">
        <label for="ga_reviewed_date">Inhoud gecontroleerd op</label>
        <input type="date" id="ga_reviewed_date" name="ga_reviewed_date" value="<?php echo esc_attr($reviewed); ?>" />
        <p class="description">Optioneel. Toont onderaan "Informatie voor het laatst gecontroleerd op …".</p>
    </div>

    <div class="ga-field">
        <label for="ga_author_role">Auteursrol / organisatie</label>
        <input type="text" id="ga_author_role" name="ga_author_role" value="<?php echo esc_attr($author_role); ?>" placeholder="Geborgd Advies" />
        <p class="description">Optioneel. Standaard "Geborgd Advies".</p>
    </div>

    <div class="ga-field">
        <label for="ga_image_alt">Alt-tekst afbeelding (toegankelijkheid)</label>
        <input type="text" id="ga_image_alt" name="ga_image_alt" value="<?php echo esc_attr($image_alt); ?>" />
        <p class="description">Beschrijf de uitgelichte afbeelding. Valt terug op de alt-tekst uit de mediabibliotheek.</p>
    </div>

    <div class="ga-field">
        <label for="ga_seo_title">SEO-titel (optioneel)</label>
        <input type="text" id="ga_seo_title" name="ga_seo_title" value="<?php echo esc_attr($seo_title); ?>" />
        <p class="description">Alternatieve titel voor Google en het browsertabblad. Leeg = gewone titel.</p>
    </div>
    <?php
}

/* -------------------------------------------------------------------------
 * 3. Opslaan
 * ---------------------------------------------------------------------- */

add_action('save_post_post', 'ga_cms_save_meta', 10, 2);

function ga_cms_save_meta(int $post_id, WP_Post $post): void
{
    if (!isset($_POST['ga_cms_nonce']) || !wp_verify_nonce($_POST['ga_cms_nonce'], 'ga_cms_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (wp_is_post_revision($post_id)) {
        return;
    }

    update_post_meta($post_id, 'ga_seo_title', sanitize_text_field($_POST['ga_seo_title'] ?? ''));
    update_post_meta($post_id, 'ga_intro', sanitize_textarea_field($_POST['ga_intro'] ?? ''));
    update_post_meta($post_id, 'ga_reviewed_date', sanitize_text_field($_POST['ga_reviewed_date'] ?? ''));
    update_post_meta($post_id, 'ga_author_role', sanitize_text_field($_POST['ga_author_role'] ?? ''));
    update_post_meta($post_id, 'ga_image_alt', sanitize_text_field($_POST['ga_image_alt'] ?? ''));
    update_post_meta($post_id, 'ga_featured', isset($_POST['ga_featured']) ? true : false);

    // In het kort: één punt per regel.
    $takeaways_raw = (string) wp_unslash($_POST['ga_key_takeaways'] ?? '');
    $takeaways = [];
    foreach (preg_split('/\r\n|\r|\n/', $takeaways_raw) as $line) {
        $line = trim(sanitize_text_field($line));
        if ($line !== '') {
            $takeaways[] = $line;
        }
    }
    update_post_meta($post_id, 'ga_key_takeaways', $takeaways);

    // Bronnen: "Naam | Titel | URL" per regel.
    $sources_raw = (string) wp_unslash($_POST['ga_sources'] ?? '');
    $sources = [];
    foreach (preg_split('/\r\n|\r|\n/', $sources_raw) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        $name  = sanitize_text_field($parts[0] ?? '');
        $title = sanitize_text_field($parts[1] ?? '');
        $url   = esc_url_raw($parts[2] ?? '');
        if ($url === '') {
            continue; // Zonder geldige URL slaan we de bron over.
        }
        $sources[] = [
            'name'  => $name !== '' ? $name : $title,
            'title' => $title !== '' ? $title : $name,
            'url'   => $url,
        ];
    }
    update_post_meta($post_id, 'ga_sources', $sources);
}

/* -------------------------------------------------------------------------
 * 4. Categorieën klaarzetten bij activeren
 * ---------------------------------------------------------------------- */

register_activation_hook(__FILE__, 'ga_cms_seed_categories');

function ga_cms_seed_categories(): void
{
    foreach (GA_CMS_CATEGORIES as $name) {
        if (!term_exists($name, 'category')) {
            wp_insert_term($name, 'category');
        }
    }
}

/* -------------------------------------------------------------------------
 * 5. Automatisch herbouwen na publiceren (deploy-webhook)
 *    Roept na publiceren/bijwerken een URL aan zodat de statische site opnieuw
 *    wordt gebouwd. Standaard afgestemd op GitHub Actions (repository_dispatch):
 *    vul de URL en een token in onder Instellingen → Schrijven, of definieer
 *    GA_DEPLOY_HOOK_URL en GA_DEPLOY_HOOK_TOKEN in wp-config.php.
 * ---------------------------------------------------------------------- */

add_action('transition_post_status', 'ga_cms_trigger_deploy', 10, 3);

function ga_cms_trigger_deploy(string $new_status, string $old_status, WP_Post $post): void
{
    if ($post->post_type !== 'post') {
        return;
    }
    // Alleen reageren als een bericht live gaat, wordt bijgewerkt of offline gaat.
    $relevant = ($new_status === 'publish') || ($old_status === 'publish' && $new_status !== 'publish');
    if (!$relevant) {
        return;
    }

    $hook = defined('GA_DEPLOY_HOOK_URL') ? GA_DEPLOY_HOOK_URL : get_option('ga_deploy_hook_url', '');
    $hook = trim((string) $hook);
    if ($hook === '') {
        return;
    }

    $token = defined('GA_DEPLOY_HOOK_TOKEN') ? GA_DEPLOY_HOOK_TOKEN : get_option('ga_deploy_hook_token', '');
    $token = trim((string) $token);
    $is_github = (bool) preg_match('#^https://api\.github\.com/repos/#', $hook);

    $headers = ['Content-Type' => 'application/json'];
    if ($token !== '') {
        $headers['Authorization'] = 'Bearer ' . $token;
    }

    if ($is_github) {
        // GitHub repository_dispatch: activeert een workflow met type "publish".
        $headers['Accept'] = 'application/vnd.github+json';
        $headers['User-Agent'] = 'geborgd-advies-cms';
        $body = wp_json_encode([
            'event_type'     => 'publish',
            'client_payload' => ['slug' => $post->post_name, 'status' => $new_status],
        ]);
    } else {
        $body = wp_json_encode([
            'event' => 'post_' . $new_status,
            'slug'  => $post->post_name,
        ]);
    }

    // Blokkerend met korte time-out: een gemiste trigger = artikel niet online.
    $response = wp_remote_post($hook, [
        'timeout' => 15,
        'headers' => $headers,
        'body'    => $body,
    ]);

    if (is_wp_error($response)) {
        error_log('[geborgd-advies-cms] deploy-trigger mislukt: ' . $response->get_error_message());
    }
}

add_action('admin_init', 'ga_cms_register_settings');

function ga_cms_register_settings(): void
{
    register_setting('writing', 'ga_deploy_hook_url', [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default'           => '',
    ]);
    register_setting('writing', 'ga_deploy_hook_token', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ]);

    add_settings_field(
        'ga_deploy_hook_url',
        'Deploy-webhook (Geborgd Advies)',
        'ga_cms_deploy_hook_field',
        'writing'
    );
    add_settings_field(
        'ga_deploy_hook_token',
        'Deploy-token (Geborgd Advies)',
        'ga_cms_deploy_token_field',
        'writing'
    );
}

function ga_cms_deploy_hook_field(): void
{
    echo '<p class="description" style="max-width:640px">Voor GitHub Actions: '
        . '<code>https://api.github.com/repos/RickDeBilt/geborgd-advies/dispatches</code>. '
        . 'Wordt na publiceren aangeroepen om de website opnieuw te bouwen. '
        . 'Leeg laten als u handmatig publiceert.</p>';
    if (defined('GA_DEPLOY_HOOK_URL')) {
        printf(
            '<p><code>%s</code> <span class="description">(ingesteld via wp-config.php)</span></p>',
            esc_html(GA_DEPLOY_HOOK_URL)
        );
        return;
    }
    $value = get_option('ga_deploy_hook_url', '');
    printf(
        '<input type="url" name="ga_deploy_hook_url" value="%s" class="regular-text" placeholder="https://api.github.com/repos/…/dispatches" />',
        esc_attr($value)
    );
}

function ga_cms_deploy_token_field(): void
{
    echo '<p class="description" style="max-width:640px">GitHub Personal Access Token '
        . '(classic, scope <code>repo</code>) om de build te mogen starten. '
        . 'Alleen nodig bij een GitHub-webhook.</p>';
    if (defined('GA_DEPLOY_HOOK_TOKEN')) {
        echo '<p><span class="description">Ingesteld via wp-config.php (GA_DEPLOY_HOOK_TOKEN).</span></p>';
        return;
    }
    $value = get_option('ga_deploy_hook_token', '');
    printf(
        '<input type="password" name="ga_deploy_hook_token" value="%s" class="regular-text" autocomplete="new-password" placeholder="ghp_…" />',
        esc_attr($value)
    );
}
