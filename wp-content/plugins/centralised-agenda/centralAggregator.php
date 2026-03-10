<?php

/**
 * Plugin Name: Centralised Agenda PoC
 * Description: Collects events from hubs' and clubs' websites through their REST API endpoints.
 * Version: 3.1
 */

define('CA_SOURCES', [
    [
        'name'   => 'Ik Ben Drents Ondernemer',
        'url'    => 'https://ikbendrentsondernemer.nl',
        'api'    => 'https://ikbendrentsondernemer.nl/wp-json/wp/v2/event',
        'region' => 'Drenthe, NL',
        'color'  => 'ibdo',
    ],
    [
        'name'   => 'Media Innovatie Campus',
        'url'    => 'https://mediainnovatiecampus.nl',
        'api'    => 'https://mediainnovatiecampus.nl/en/wp-json/wp/v2/events',
        'region' => 'Leeuwarden, NL',
        'color'  => 'mica',
    ],
    [
        'name'   => 'YN Business',
        'url'    => 'https://ynbusiness.nl',
        'api'    => 'https://ynbusiness.nl/wp-json/wp/v2/evenement',
        'region' => 'Groningen, NL',
        'color'  => 'ynb',
    ],
]);

function caToday(): DateTime
{
    return new DateTime('today midnight');
}

function caFormatDate(DateTime $dt): string
{
    return $dt->format('d-m-Y');
}

/**
 * Resolves a day and month (no year) into a concrete DateTime.
 * Uses current year; if that date is before today, returns null.
 */
function caResolveDayMonth(int $day, int $month): ?DateTime
{
    if ($day < 1 || $day > 31 || $month < 1 || $month > 12) return null;

    $today = caToday();
    $year  = (int) $today->format('Y');

    $dt = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day));
    if (!$dt) return null;
    $dt->setTime(0, 0, 0);

    if ($dt < $today) {
        return null;
    }

    return $dt;
}

/**
 * Core date extractor used for slug, title and description.
 * Tries explicit dates with year (NL/EN, numeric, ISO),
 * then month+year (assume day 1),
 * then day+month without year (current year only).
 */
function caExtractDateFromText(string $text): ?DateTime
{
    $text = mb_strtolower($text);

    $nl = [
        'januari'  => 1,
        'februari' => 2,
        'maart'    => 3,
        'april'    => 4,
        'mei'      => 5,
        'juni'     => 6,
        'juli'     => 7,
        'augustus' => 8,
        'september'=> 9,
        'oktober'  => 10,
        'november' => 11,
        'december' => 12,
    ];
    $en = [
        'january'   => 1,
        'february'  => 2,
        'march'     => 3,
        'april'     => 4,
        'may'       => 5,
        'june'      => 6,
        'july'      => 7,
        'august'    => 8,
        'september' => 9,
        'october'   => 10,
        'november'  => 11,
        'december'  => 12,
    ];

    $nlNames = implode('|', array_keys($nl));
    $enNames = implode('|', array_keys($en));

    $today = caToday();
    $candidates = [];

    // helper to add a candidate if it is parsable
    $addCandidate = function (?DateTime $dt) use (&$candidates) {
        if ($dt) {
            $dt->setTime(0, 0, 0);
            $candidates[] = $dt;
        }
    };

    // 1) Explicit Dutch "4 februari 2026" / "4-februari-2026"
    if (preg_match_all('/\b(\d{1,2})[\s\-](' . $nlNames . ')[\s\-](\d{4})\b/u', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $day   = (int) $m[1];
            $month = $nl[$m[2]] ?? null;
            $year  = (int) $m[3];
            if ($month) {
                $addCandidate(DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day)));
            }
        }
    }

    // 2) Explicit English "March 4 2026" / "March 4, 2026"
    if (preg_match_all('/\b(' . $enNames . ')[\s\-](\d{1,2})[,\s]+(\d{4})\b/u', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $month = $en[$m[1]] ?? null;
            $day   = (int) $m[2];
            $year  = (int) $m[3];
            if ($month) {
                $addCandidate(DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day)));
            }
        }
    }

    // 3) Numeric "04-02-2026" / "04/02/2026"
    if (preg_match_all('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/u', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $day   = (int) $m[1];
            $month = (int) $m[2];
            $year  = (int) $m[3];
            $addCandidate(DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $day)));
        }
    }

    // 4) ISO "2026-02-04"
    if (preg_match_all('/\b(\d{4})-(\d{2})-(\d{2})\b/u', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $addCandidate(DateTime::createFromFormat('Y-m-d', $m[0]));
        }
    }

    // 5) Dutch "maart 2026" (no day, assume 1st)
    if (preg_match_all('/\b(' . $nlNames . ')\s+(\d{4})\b/u', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $month = $nl[$m[1]] ?? null;
            $year  = (int) $m[2];
            if ($month) {
                $addCandidate(DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, 1)));
            }
        }
    }

    // 6) English "March 2026" (no day, assume 1st)
    if (preg_match_all('/\b(' . $enNames . ')\s+(\d{4})\b/u', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $month = $en[$m[1]] ?? null;
            $year  = (int) $m[2];
            if ($month) {
                $addCandidate(DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, 1)));
            }
        }
    }

    // 7) Dutch "4 februari" (no year, use current year only)
    if (preg_match_all('/\b(\d{1,2})[\s\-](' . $nlNames . ')\b/u', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $day   = (int) $m[1];
            $month = $nl[$m[2]] ?? null;
            if ($month) {
                $addCandidate(caResolveDayMonth($day, $month));
            }
        }
    }

    // 8) English "February 4" (no year, use current year only)
    if (preg_match_all('/\b(' . $enNames . ')[\s\-](\d{1,2})\b/u', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $month = $en[$m[1]] ?? null;
            $day   = (int) $m[2];
            if ($month) {
                $addCandidate(caResolveDayMonth($day, $month));
            }
        }
    }

    if (empty($candidates)) {
        return null;
    }

    // keep only today or future
    $future = array_filter($candidates, fn(DateTime $d) => $d >= $today);
    if (empty($future)) {
        return null;
    }

    // pick the latest future date (most likely the actual event date)
    usort($future, fn(DateTime $a, DateTime $b) => $a <=> $b);
    return end($future);
}

/**
 * Convenience wrapper: try slug + title, then description HTML.
 */
function caExtractEventDate(array $post): ?DateTime
{
    $slug  = $post['slug'] ?? '';
    $title = strip_tags($post['title']['rendered'] ?? '');
    $html  = $post['content']['rendered'] ?? '';
    $plain = preg_replace('/\s+/', ' ', trim(wp_strip_all_tags($html)));

    // 1) slug + title
    $combined = $slug . ' ' . $title;
    $dt = caExtractDateFromText($combined);
    if ($dt) return $dt;

    // 2) description/plain content
    return caExtractDateFromText($plain);
}

/**
 * Include only posts that are fairly recent when falling back to publish date.
 * This helps pick up events that do not encode the date in the slug/title/description.
 */
function caIsRecentlyPublished(string $publishDate): bool
{
    $published = DateTime::createFromFormat('Y-m-d\TH:i:s', $publishDate);
    if (!$published) return false;

    $today  = caToday();
    $cutoff = (clone $today)->modify('-120 days');

    return $published >= $cutoff;
}

function caFetchAllPages(string $apiURL): array
{
    $all  = [];
    $page = 1;

    do {
        $url      = add_query_arg(['per_page' => 100, 'page' => $page], $apiURL);
        $response = wp_remote_get($url, ['timeout' => 15]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) break;

        $posts      = json_decode(wp_remote_retrieve_body($response), true);
        $totalPages = (int) wp_remote_retrieve_header($response, 'x-wp-totalpages');

        if (!is_array($posts) || empty($posts)) break;

        $all = array_merge($all, $posts);
        $page++;
    } while ($page <= max(1, $totalPages));

    return $all;
}

/**
 * Placeholder for structured date per source (IBDO, MICA, YNB).
 * Currently unused; future-proof hook for ACF/meta fields.
 */
function caExtractStructuredDate(array $post, array $source): ?DateTime
{
    return null;
}

function caFetchFromSource(array $source): array
{
    $posts  = caFetchAllPages($source['api']);
    $today  = caToday();
    $events = [];

    foreach ($posts as $post) {
        $title       = strip_tags($post['title']['rendered'] ?? '');
        $publishDate = $post['date'] ?? '';

        // 1) structured field hook (unused for now)
        $eventDate = caExtractStructuredDate($post, $source);

        // 2) slug, title, description
        if (!$eventDate) {
            $eventDate = caExtractEventDate($post);
        }

        // 3) fallback: use publish date for recent posts
        if (!$eventDate && $publishDate && caIsRecentlyPublished($publishDate)) {
            $published = DateTime::createFromFormat('Y-m-d\TH:i:s', $publishDate);
            if ($published) {
                $published->setTime(0, 0, 0);
                $eventDate = $published;
            }
        }

        if (!$eventDate) {
            continue;
        }

        if ($eventDate < $today) {
            // final guard: past events never show up
            continue;
        }

        $displayDate = caFormatDate($eventDate);
        $sortDate    = $eventDate->getTimestamp();

        $html  = $post['content']['rendered'] ?? '';
        $plain = preg_replace('/\s+/', ' ', trim(wp_strip_all_tags($html)));
        $desc  = mb_substr($plain, 0, 280) . (mb_strlen($plain) > 280 ? '...' : '');

        $events[] = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Event',
            'name'        => html_entity_decode($title),
            'startDate'   => $displayDate,
            '_sort_date'  => $sortDate,
            'description' => $desc,
            'url'         => $post['link'] ?? '',
            'location'    => [
                '@type'   => 'Place',
                'name'    => $source['region'],
                'address' => ['@type' => 'PostalAddress', 'addressCountry' => 'NL'],
            ],
            'organizer'   => [
                '@type' => 'Organization',
                'name'  => $source['name'],
                'url'   => $source['url'],
            ],
            '_source'     => $source['name'],
            '_color'      => $source['color'],
        ];
    }

    return $events;
}

function caFetchAllEvents(): array
{
    $all = [];
    foreach (CA_SOURCES as $source) {
        $all = array_merge($all, caFetchFromSource($source));
    }

    usort($all, fn($a, $b) => $a['_sort_date'] <=> $b['_sort_date']);

    return $all;
}

function caGetCachedEvents(): array
{
    // bump cache key when logic changes
    $cached = get_transient('ca_events_v13');
    if ($cached !== false) return $cached;
    $events = caFetchAllEvents();
    set_transient('ca_events_v13', $events, HOUR_IN_SECONDS);

    return $events;
}

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('ca-style', plugin_dir_url(__FILE__) . 'style.css', [], '4.0');
});

add_action('wp_head', function () {
    global $post;
    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'centralised_agenda')) return;
    $events = caGetCachedEvents();
    if (empty($events)) return;
    $clean = array_map(function ($e) {
        unset($e['_source'], $e['_sort_date'], $e['_color']);
        return $e;
    }, $events);
    echo '<script type="application/ld+json">' .
        wp_json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) .
        '</script>' . "\n";
});

add_shortcode('centralised_agenda', 'caRenderEvents');

function caRenderEvents(): string
{
    $events  = caGetCachedEvents();
    $sources = array_unique(array_column($events, '_source'));

    ob_start(); ?>
    <div class="ca-events">
        <?php if (empty($events)): ?>
            <p class="ca-subtitle">No upcoming events found.</p>
        <?php else: ?>
            <p class="ca-subtitle">
                <?= count($events) ?> upcoming event(s) from:
                <strong><?= implode(', ', array_map('esc_html', $sources)) ?></strong>
                — structured as <a href="https://schema.org/Event" target="_blank">schema.org/Event</a>
            </p>

            <?php
            $grouped = [];
            foreach ($events as $e) {
                $dt    = DateTime::createFromFormat('d-m-Y', $e['startDate']);
                $label = $dt ? $dt->format('F Y') : 'Unknown';
                $grouped[$label][] = $e;
            }
            ?>

            <?php foreach ($grouped as $monthLabel => $monthEvents): ?>
                <div class="ca-month-group">
                    <div class="ca-month-header"><?= esc_html($monthLabel) ?></div>

                    <?php foreach ($monthEvents as $e):
                        $dt   = DateTime::createFromFormat('d-m-Y', $e['startDate']);
                        $day  = $dt ? $dt->format('j') : '–';
                        $mon  = $dt ? $dt->format('M') : '';
                        $color = esc_attr($e['_color'] ?? 'default');
                    ?>
                        <div class="ca-event ca-color-<?= $color ?>">
                            <div class="ca-date-block">
                                <span class="ca-day"><?= esc_html($day) ?></span>
                                <span class="ca-month-short"><?= esc_html($mon) ?></span>
                            </div>
                            <div class="ca-event-body">
                                <h4>
                                    <a href="<?= esc_url($e['url']) ?>" target="_blank"><?= esc_html($e['name']) ?></a>
                                    <span class="ca-source-badge ca-badge-<?= $color ?>"><?= esc_html($e['_source']) ?></span>
                                </h4>
                                <div class="ca-event-meta">
                                    <span>Location: <?= esc_html($e['location']['name']) ?></span>
                                </div>
                                <?php if ($e['description']): ?>
                                    <p class="ca-event-desc"><?= esc_html($e['description']) ?></p>
                                <?php endif; ?>
                                <a class="ca-event-url" href="<?= esc_url($e['url']) ?>" target="_blank">More info →</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}
?>
