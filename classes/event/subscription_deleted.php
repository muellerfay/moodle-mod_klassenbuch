<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The mod_klassenbuch subscription deleted event.
 *
 * @package    mod_klassenbuch
 * @copyright  2014 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_klassenbuch\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_klassenbuch subscription deleted event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int bookid: The id of the klassenbuch which has been unsusbcribed from.
 * }
 *
 * @package    mod_klassenbuch
 * @since      Moodle 2.7
 * @copyright  2014 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subscription_deleted extends \core\event\base {
    /**
     * Create instance of event.
     *
     * @param \stdClass $klassenbuch
     * @param \context_module $context
     * @param int $userid
     * @param int $subscribeid
     * @return subscription_deleted
     */
    public static function create_from_userid(\stdClass $klassenbuch, \context_module $context, $userid, $subscribeid) {
        $data = array(
            'context' => $context,
            'relateduserid' => $userid,
            'objectid' => $subscribeid,
            'other' => array('bookid' => $klassenbuch->id),
        );
        /** @var chapter_created $event */
        $event = self::create($data);
        $event->add_record_snapshot('klassenbuch', $klassenbuch);
        return $event;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'klassenbuch_subscriptions';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' unsubscribed the user with id '$this->relateduserid' to the klassenbuch with " .
            "course module id '$this->contextinstanceid'.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventsubscriptiondeleted', 'mod_klassenbuch');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/klassenbuch/subscribers.php', array('id' => $this->other['bookid']));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'klassenbuch', 'unsubscribe', 'subscribers.php?id='.$this->other['bookid'],
                     $this->objectid, $this->contextinstanceid);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->objectid)) {
            throw new \coding_exception('The \'objectid\' must be set.');
        }

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }

        if (!isset($this->other['bookid'])) {
            throw new \coding_exception('The \'bookid\' value must be set in other.');
        }

        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }
}
