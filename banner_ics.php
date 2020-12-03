<?php
    // Require composer autoload for direct installs
    @include __DIR__ . '/vendor/autoload.php';

    /**
     * Banner ICS
     *
     * Displays event information from ICS attachments
     *
     * @license MIT License: <http://opensource.org/licenses/MIT>
     * @author Varun Patil
     * @category  Plugin for RoundCube WebMail
     */
    class banner_ics extends rcube_plugin
    {
        public $task = 'mail';

        function init()
        {
            $rcmail = rcmail::get_instance();

            $this->load_config('config.inc.php.dist');
            $this->load_config('config.inc.php');

            $this->include_stylesheet('banner_ics.css');

            if ($rcmail->config->get('banner_ics_description')) {
                $this->include_script('banner_ics_description.js');
            }

            $this->add_hook('message_objects', array($this, 'ics_banner'));
        }

        public function ics_banner($args)
        {
            // Get arguments
            $content = $args['content'];
            $message = $args['message'];

            foreach ($message->attachments as &$a) {
                if (strtolower($a->mimetype) === 'text/calendar') {
                    try {
                        $this->process_attachment($content, $message, $a);
                    } catch (\Exception $e) {}
                }
            }

            return array('content' => $content);
        }

        public function process_attachment(&$content, &$message, &$a)
        {
            $rcmail = rcmail::get_instance();
            $date_format = $rcmail->config->get('date_format', 'D M d, Y');
            $time_format = $rcmail->config->get('time_format', 'h:ia');
            $combined_format = $date_format . ' ' . $time_format;
            $ymd = 'Y-m-d';

            // Parse event
            $ics = $message->get_part_body($a->mime_id);
            $ical = new \ICal\ICal();
            $ical->initString($ics);

            // Make sure we have events
            if (!$ical->hasEvents()) return;

            // Crete timezone objects for calendar timezone and RoundCube UI timezone
            try {
                $ui_tz = new DateTimeZone($rcmail->config->get('timezone'));
            } catch (Exception $e) {
                $ui_tz = new DateTimeZone(DateTimeZone::UTC);
            }
            try {
                $ical_tz = new DateTimeZone($ical->calendarTimeZone(true));
            } catch (Exception $e) {
                // Use UI timezone if the calendar have no timezone specified
                $ical_tz = $ui_tz;
            }

            // Get first event
            foreach ($ical->events() as &$event) {
                $is_all_day = isset($event->dtstart_array[0]['VALUE']) && $event->dtstart_array[0]['VALUE'] === 'DATE';

                if ($is_all_day) {
                    // All day events should use UI timezone to avoid turning dates
                    $dtstart = new DateTime($event->dtstart, $ui_tz);
                    $dtend = new DateTime($event->dtend, $ui_tz);
                    // All day events ends at next day midnight, we should fix the day
                    $dtend->modify('-1 day');
                } else {
                    // Events with proper time should use calendar timezone.
                    // Note that if DTSTART/DTEND ends with "Z", UTC is used instead of the given timezone automatically
                    $dtstart = new DateTime($event->dtstart, $ical_tz);
                    $dtend = new DateTime($event->dtend, $ical_tz);
                }

                $is_oneday = $dtstart->format($ymd) === $dtend->format($ymd);

                // Concatenate event date string
                if ($is_all_day) {
                    // All day events shouldn't display time
                    $dtstr = $rcmail->format_date($dtstart, $date_format)
                            . (!$is_oneday ? ' – ' . $rcmail->format_date($dtend, $date_format) : '');
                } else {
                    $dtstr = $rcmail->format_date($dtstart, $combined_format) . ' – '
                            . ($is_oneday ? $rcmail->format_date($dtend, $time_format) : $rcmail->format_date($dtend, $combined_format));
                }
                // Put timezone in date string
                $dtstr .= ' (' . $rcmail->format_date($dtstart, 'T') . ')';

                // Get attendees
                $who = array();
                foreach (array_merge($event->organizer_array, $event->attendee_array) as &$o) {
                    if (is_array($o) && array_key_exists('CN', $o)) {
                        array_push($who, $o['CN']);
                    }
                }

                // Get attendees string
                if (count($who) > 0) {
                    $max_show = 10;
                    $others = count($who) - $max_show;
                    $who = array_slice($who, 0, $max_show);
                    $who = implode(', ', $who) . ($others > 0 ? " and $others others" : '');
                } else {
                    $who = null;
                }

                // Output
                $html = '<div class="info ics-event-container">';
                $html .= '<div class="ics-icon">';
                $html .= '<div class="m">' . $rcmail->format_date($dtstart, 'M') . '</div>';
                $html .= '<div class="d">' . $rcmail->format_date($dtstart, 'd') . '</div>';
                $html .= '<div class="day">' . $rcmail->format_date($dtstart, 'D') . '</div>';
                $html .= '</div>';
                $html .= '<div class="ics-event">';
                $html .= '<span class="title">' . htmlspecialchars($event->summary) . '</span>';
                $html .= '<br/><b>' . htmlspecialchars($dtstr) . '</b>';
                if (isset($who)) {
                     $html .= '<br/>' . htmlspecialchars($who);
                }
                $html .= '</div>';
                $html .= '</div>';
                array_push($content, $html);

                // Optional description block
                if ($rcmail->config->get('banner_ics_description')) {
                     $html = '<div class="info ics-event-description">';
                     $description = htmlspecialchars_decode($event->description);
                     $html .= (new rcube_text2html($description))->get_html();
                     $html .= '</div>';
                     array_push($content, $html);
                }
            }
        }
    }

