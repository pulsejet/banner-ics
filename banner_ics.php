<?php
    // Require composer autoload for direct installs
    include __DIR__ . '/vendor/autoload.php';

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
            $this->include_stylesheet('banner_ics.css');
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
            $format = 'D M d, Y h:ia';

            // Parse event
            $ics = $message->get_part_body($a->mime_id);
            $ical = new \ICal\ICal();
            $ical->initString($ics);

            // Make sure we have events
            if (!$ical->hasEvents()) return;

            // Get first event
            foreach ($ical->events() as &$event) {
                $dtstart = $event->dtstart_array[2];
                $dtend = $event->dtend_array[2];
                $dtstr = date($format, $dtstart) . ' - ';

                // Dont double date if same
                $df = 'Y-m-d';
                if (date_format($dtstart, $df) === date_format($dtend, $df)) {
                    $dtstr .= date('h:ia', $dtend);
                } else {
                    $dtstr .= date($format, $dtend);
                }

                // Put timezone in date string
                $dtstr .= ' (' . date('T', $dtstart) . ')';

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
                $html = '<div class="notice info">';
                $html .= '<div class="ics-icon">';
                $html .= '<div class="m">' . date('M', $dtstart) . '</div>';
                $html .= '<div class="d">' . date('d', $dtstart) . '</div>';
                $html .= '<div class="day">' . date('D', $dtstart) . '</div>';
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
            }
        }
    }

