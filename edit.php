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

/**
 * Create / edit a notice.
 *
 * @package    local_notice
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_notice\local\manager;

$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('local_notice_manage');
$context = context_system::instance();
$url = new moodle_url('/local/notice/edit.php', $id ? ['id' => $id] : []);
$manageurl = new moodle_url('/local/notice/manage.php');
$PAGE->set_url($url);

$editoroptions = manager::editor_options();

if ($id) {
    $notice = $DB->get_record(manager::TABLE, ['id' => $id], '*', MUST_EXIST);
    $pagetitle = get_string('editnotice', 'local_notice');
} else {
    $notice = (object) [
        'id' => null,
        'title' => '',
        'content' => '',
        'contentformat' => FORMAT_HTML,
        'buttonlabel' => '',
        'enabled' => 1,
        'timestart' => 0,
        'timeend' => 0,
    ];
    $pagetitle = get_string('addnotice', 'local_notice');
}

$notice = file_prepare_standard_editor($notice, 'content', $editoroptions, $context,
    'local_notice', manager::FILEAREA, $notice->id);

$form = new \local_notice\form\notice_form($url, ['editoroptions' => $editoroptions, 'id' => $id]);
$form->set_data($notice);

if ($form->is_cancelled()) {
    redirect($manageurl);
} else if ($data = $form->get_data()) {
    $now = time();
    $record = (object) [
        'title' => trim($data->title),
        'buttonlabel' => trim($data->buttonlabel),
        'enabled' => empty($data->enabled) ? 0 : 1,
        'timestart' => empty($data->timestart) ? 0 : (int) $data->timestart,
        'timeend' => empty($data->timeend) ? 0 : (int) $data->timeend,
        'usermodified' => $USER->id,
        'timemodified' => $now,
    ];
    if ($id) {
        $record->id = $id;
        $DB->update_record(manager::TABLE, $record);
    } else {
        $record->content = '';
        $record->contentformat = FORMAT_HTML;
        $record->timecreated = $now;
        $id = $DB->insert_record(manager::TABLE, $record);
        $record->id = $id;
    }

    // Move uploaded media from the draft area into the notice file area.
    $data->id = $id;
    $data = file_postupdate_standard_editor($data, 'content', $editoroptions, $context,
        'local_notice', manager::FILEAREA, $id);
    $DB->update_record(manager::TABLE, (object) [
        'id' => $id,
        'content' => $data->content,
        'contentformat' => $data->contentformat,
    ]);

    if (!empty($data->resetacks)) {
        manager::reset_acknowledgements($id);
    } else {
        manager::bump_revision();
    }

    redirect($manageurl, get_string('noticesaved', 'local_notice'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->set_title($pagetitle);
$PAGE->set_heading(get_string('pluginname', 'local_notice'));
$PAGE->navbar->add($pagetitle);

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
$form->display();
echo $OUTPUT->footer();
