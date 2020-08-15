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
                if ($a->mimetype === 'text/calendar') {
                    $format = 'D M d, Y h:ia';

                    // Parse event
                    $ics = $message->get_part_body($a->mime_id);
                    $ical = new \ICal\ICal();
                    $ical->initString($ics);

                    // Make sure we have events
                    if (!$ical->hasEvents()) continue;

                    // Get first event
                    foreach ($ical->events() as &$event) {
                        $dtstart = $ical->iCalDateToDateTime($event->dtstart);
                        $dtend = $ical->iCalDateToDateTime($event->dtend);
                        $dtstr = date_format($dtstart, $format) . ' - ';

                        // Dont double date if same
                        $df = 'Y-m-d';
                        if (date_format($dtstart, $df) === date_format($dtend, $df)) {
                            $dtstr = $dtstr . date_format($dtend, 'h:ia');
                        } else {
                            $dtstr = $dtstr . date_format($dtend, $format);
                        }

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
                        $html = '<div class="ics-event">';
                        $html .= '<span class="title">' . htmlspecialchars($event->summary) . '</span>';
                        $html .= '<br/>' . htmlspecialchars($dtstr);
                        if (isset($who)) {
                             $html .= '<br/>' . htmlspecialchars($who);
                        }
                        $html .= '</div>';
                        $html .= '</div>';
                        array_push($content, $html);
                    }
                }
            }

            return array('content' => $content);
        }
    }

