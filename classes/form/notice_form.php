<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_notice\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Create / edit notice form.
 *
 * @package    local_notice
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notice_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];
        $isnew = empty($this->_customdata['id']);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'title', get_string('noticetitle', 'local_notice'), ['size' => 60]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('editor', 'content_editor', get_string('noticecontent', 'local_notice'), ['rows' => 18],
            $editoroptions);
        $mform->setType('content_editor', PARAM_RAW);
        $mform->addRule('content_editor', null, 'required', null, 'client');
        $mform->addHelpButton('content_editor', 'noticecontent', 'local_notice');

        $mform->addElement('text', 'buttonlabel', get_string('buttonlabel', 'local_notice'), ['size' => 30]);
        $mform->setType('buttonlabel', PARAM_TEXT);
        $mform->addRule('buttonlabel', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        $mform->addHelpButton('buttonlabel', 'buttonlabel', 'local_notice');

        $mform->addElement('advcheckbox', 'enabled', get_string('noticeenabled', 'local_notice'));
        $mform->setDefault('enabled', 1);

        $mform->addElement('date_time_selector', 'timestart', get_string('timestart', 'local_notice'),
            ['optional' => true]);
        $mform->addHelpButton('timestart', 'timestart', 'local_notice');
        $mform->addElement('date_time_selector', 'timeend', get_string('timeend', 'local_notice'),
            ['optional' => true]);
        $mform->addHelpButton('timeend', 'timeend', 'local_notice');

        if (!$isnew) {
            $mform->addElement('advcheckbox', 'resetacks', get_string('resetacksonsave', 'local_notice'));
            $mform->addHelpButton('resetacks', 'resetacksonsave', 'local_notice');
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (trim($data['title']) === '') {
            $errors['title'] = get_string('required');
        }
        if (!empty($data['timestart']) && !empty($data['timeend']) && $data['timeend'] <= $data['timestart']) {
            $errors['timeend'] = get_string('errorendbeforestart', 'local_notice');
        }
        return $errors;
    }
}
