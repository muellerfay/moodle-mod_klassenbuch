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
 * Define all the restore steps that will be used by the restore_klassenbuch_activity_task
 *
 * @package    mod_klassenbuch
 * @copyright  2012 Synergy Learning / Davo Smith (based on book module)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one klassenbuch activity
 */
class restore_klassenbuch_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('klassenbuch', '/activity/klassenbuch');
        $chapter = new restore_path_element('klassenbuch_chapter', '/activity/klassenbuch/chapters/chapter');
        $paths[] = $chapter;
        
        // Apply for 'klassenbuchtool' subplugins optional paths at chapter level
        $this->add_subplugin_structure('klassenbuchtool', $chapter);
        
        $paths[] = new restore_path_element('klassenbuch_chapterfield',
                                            '/activity/klassenbuch/chapters/chapter/chapterfields/chapterfield');
        if ($userinfo) {
            $paths[] = new restore_path_element('klassenbuch_subscription', '/activity/klassenbuch/subscriptions/subscription');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_klassenbuch($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('klassenbuch', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_klassenbuch_chapter($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->klassenbuchid = $this->get_new_parentid('klassenbuch');

        // Restore / import should always use the chapter fields, instead
        // of the 'content' data - see GILMTWO-117.
        $data->content = '';

        $newitemid = $DB->insert_record('klassenbuch_chapters', $data);
        $this->set_mapping('klassenbuch_chapter', $oldid, $newitemid, true);
    }

    protected function process_klassenbuch_chapterfield($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->chapterid = $this->get_new_parentid('klassenbuch_chapter');

        $newitemid = $DB->insert_record('klassenbuch_chapter_fields', $data);
        $this->set_mapping('klassenbuch_chapterfield', $oldid, $newitemid, true);
    }

    protected function process_klassenbuch_subscription($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->klassenbuch = $this->get_new_parentid('klassenbuch');
        $data->userid = $this->get_mappingid('userid', $data->userid);

        if ($data->userid) {
            $newitemid = $DB->insert_record('klassenbuch_subscriptions', $data);
            $this->set_mapping('klassenbuch_subscription', $oldid, $newitemid, true);
        }
    }

    protected function after_execute() {
        global $DB;

        // Add klassenbuch related files.
        $this->add_related_files('mod_klassenbuch', 'intro', null);
        $this->add_related_files('mod_klassenbuch', 'chapter', 'klassenbuch_chapter');
        $this->add_related_files('mod_klassenbuch', 'attachment', 'klassenbuch_chapter');
        $this->add_related_files('mod_klassenbuch', 'chapterfield', 'klassenbuch_chapter');

        $fieldids = $DB->get_fieldset_select('klassenbuch_globalfields', 'id', '');
        foreach ($fieldids as $fieldid) {
            $this->add_related_files('mod_klassenbuch', 'customcontent_'.$fieldid, 'klassenbuch_chapter');
        }
    }
}
