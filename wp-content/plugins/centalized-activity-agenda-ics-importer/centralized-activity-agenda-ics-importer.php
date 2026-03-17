<?php
/**
 * Plugin Name: Centralized Agenda ICS Importer
 * Description: PoC plugin to fetch and parse ICS calendar feeds for the Centralized Activity Agenda project.
 */

if (!defined('ABSPATH')) exit;

final class CAA_ICS_Importer
{
    private const OPTION_URL  = 'caa_ics_url';
    private const DEFAULT_URL = 'https://it-hub.nl/evenementen/?ical=1';

    public static function init(): void
    {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
    }

    public static function add_menu(): void
    {
        add_menu_page(
            'ICS Importer',
            'ICS Importer',
            'manage_options',
            'ics-importer',
            [__CLASS__, 'render_page'],
            'dashicons-calendar-alt'
        );
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) return;

        // Save URL
        if (isset($_POST['save_url'])) {
            check_admin_referer('caa_save_url_action');
            $url = isset($_POST['ics_url']) ? esc_url_raw(trim((string)$_POST['ics_url'])) : '';
            update_option(self::OPTION_URL, $url);
            echo '<div class="notice notice-success"><p>Saved ICS URL.</p></div>';
        }

        $icsUrl = (string) get_option(self::OPTION_URL, self::DEFAULT_URL);

        echo '<div class="wrap">';
        echo '<h1>Centralized Activity Agenda – ICS Integration PoC</h1>';

        echo '<form method="post">';
        wp_nonce_field('caa_save_url_action');
        echo '<label><strong>ICS Feed URL</strong></label><br>';
        echo '<input type="url" name="ics_url" value="' . esc_attr($icsUrl) . '" style="width:520px;margin-top:6px;" required>';
        echo '<br><br>';
        echo '<button class="button button-primary" name="save_url" value="1">Save URL</button>';
        echo '</form>';

        echo '<br>';

        echo '<form method="post">';
        wp_nonce_field('caa_fetch_action');
        echo '<button class="button" name="fetch_event" value="1">Fetch + Parse First Event</button>';
        echo '</form>';

        if (isset($_POST['fetch_event'])) {
            check_admin_referer('caa_fetch_action');
            self::fetch_and_parse($icsUrl);
        }

        echo '</div>';
    }

    private static function fetch_and_parse(string $url): void
    {
        echo '<h3>Fetching ICS from:</h3>';
        echo '<code>' . esc_html($url) . '</code>';

        $response = wp_remote_get($url, [
            'timeout'     => 25,
            'redirection' => 5,
            'headers'     => [
                'Accept'     => 'text/calendar, text/plain, */*',
                'User-Agent' => 'CentralizedAgendaPoC/0.5 (+WordPress)',
            ],
        ]);

        if (is_wp_error($response)) {
            echo '<div class="notice notice-error"><p>Request error: ' . esc_html($response->get_error_message()) . '</p></div>';
            return;
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $headers = wp_remote_retrieve_headers($response);
        $body   = (string) wp_remote_retrieve_body($response);

        if ($status < 200 || $status >= 300) {
            echo '<div class="notice notice-error"><p>Request failed. HTTP ' . esc_html((string)$status) . '</p></div>';
            return;
        }

        // If it’s HTML, it’s not an ICS feed
        $bodyStart = ltrim(substr($body, 0, 200));
        $looksLikeHtml = (stripos($bodyStart, '<!doctype') !== false) || (stripos($bodyStart, '<html') !== false);

        if ($looksLikeHtml || stripos($body, 'BEGIN:VCALENDAR') === false) {
            $contentType = isset($headers['content-type']) ? (string)$headers['content-type'] : '(unknown)';
            echo '<div class="notice notice-warning"><p><strong>This URL does not return an ICS calendar.</strong> It looks like a normal webpage response.</p></div>';
            echo '<p><strong>Content-Type:</strong> <code>' . esc_html($contentType) . '</code></p>';
            echo '<p><strong>Response preview:</strong></p>';
            echo '<pre style="max-width:900px;white-space:pre-wrap;">' . esc_html($bodyStart) . '</pre>';
            echo '<p><em>Tip:</em> Try a real .ics link (Google Calendar public ICS works), or the hub must provide an actual ICS export endpoint.</p>';
            return;
        }

        $event = self::parse_first_event($body);

        if (!$event) {
            echo '<div class="notice notice-warning"><p>ICS retrieved, but no VEVENT blocks were found.</p></div>';
            return;
        }

        echo '<div class="notice notice-success"><p>First event parsed successfully ✅</p></div>';
        echo '<h3>First Event Parsed:</h3>';
        echo '<pre>' . esc_html(print_r($event, true)) . '</pre>';
    }

    private static function parse_first_event(string $ics): ?array
    {
        $ics = str_replace("\r\n", "\n", $ics);
        $ics = preg_replace("/\n[ \t]/", "", $ics); // unfold lines

        $parts = preg_split('/BEGIN:VEVENT/i', $ics);
        if (!$parts || count($parts) < 2) return null;

        $eventBlock = $parts[1];
        $endPos = stripos($eventBlock, 'END:VEVENT');
        if ($endPos !== false) {
            $eventBlock = substr($eventBlock, 0, $endPos);
        }

        $get = function (string $field) use ($eventBlock): string {
            if (preg_match('/^' . preg_quote($field, '/') . '[^:]*:(.*)$/mi', $eventBlock, $m)) {
                return trim((string)$m[1]);
            }
            return '';
        };

        return [
            'uid'      => $get('UID'),
            'title'    => $get('SUMMARY'),
            'start'    => $get('DTSTART'),
            'end'      => $get('DTEND'),
            'location' => $get('LOCATION'),
        ];
    }
}

CAA_ICS_Importer::init();