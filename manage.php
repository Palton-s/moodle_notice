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
 * List and manage notices.
 *
 * @package    local_notice
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_notice\local\manager;

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('local_notice_manage');
$baseurl = new moodle_url('/local/notice/manage.php');
$PAGE->set_url($baseurl);
$strmanage = get_string('managenotices', 'local_notice');
$PAGE->set_title($strmanage);
$PAGE->set_heading(get_string('pluginname', 'local_notice'));

// Actions.
if ($action && $id) {
    $notice = $DB->get_record(manager::TABLE, ['id' => $id], '*', MUST_EXIST);
    $title = format_string($notice->title);

    switch ($action) {
        case 'enable':
        case 'disable':
            require_sesskey();
            $DB->set_field(manager::TABLE, 'enabled', $action === 'enable' ? 1 : 0, ['id' => $id]);
            manager::bump_revision();
            redirect($baseurl);

        case 'delete':
        case 'reset':
            if ($confirm && confirm_sesskey()) {
                if ($action === 'delete') {
                    manager::delete($id);
                    $msg = get_string('noticedeleted', 'local_notice');
                } else {
                    manager::reset_acknowledgements($id);
                    $msg = get_string('acksreset', 'local_notice');
                }
                redirect($baseurl, $msg, null, \core\output\notification::NOTIFY_SUCCESS);
            }
            echo $OUTPUT->header();
            echo $OUTPUT->confirm(
                get_string($action === 'delete' ? 'confirmdelete' : 'confirmreset', 'local_notice', $title),
                new moodle_url($baseurl, ['action' => $action, 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
                $baseurl
            );
            echo $OUTPUT->footer();
            exit;

        case 'preview':
            // Shows the popup on this page without recording that it was seen.
            $PAGE->requires->js_call_amd('local_notice/notice', 'init');
            $previewhtml = $OUTPUT->render_from_template('local_notice/notices', [
                'preview' => true,
                'notices' => manager::export_for_template([$notice]),
            ]);
            break;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($strmanage);

if (!manager::is_enabled()) {
    $settingsurl = new moodle_url('/admin/settings.php', ['section' => 'local_notice_settings']);
    echo $OUTPUT->notification(get_string('pluginisdisabled', 'local_notice', $settingsurl->out()),
        \core\output\notification::NOTIFY_WARNING);
}

echo html_writer::div(
    $OUTPUT->single_button(new moodle_url('/local/notice/edit.php'), get_string('addnotice', 'local_notice'), 'get',
        ['type' => \single_button::BUTTON_PRIMARY]),
    'mb-3'
);

$notices = $DB->get_records_sql(
    "SELECT n.*, (SELECT COUNT(1) FROM {" . manager::TABLE_ACK . "} a WHERE a.noticeid = n.id) AS ackcount
       FROM {" . manager::TABLE . "} n
   ORDER BY n.timecreated DESC, n.id DESC"
);

if (!$notices) {
    echo $OUTPUT->notification(get_string('nonotices', 'local_notice'), \core\output\notification::NOTIFY_INFO);
} else {
    $now = time();
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table-hover';
    $table->head = [
        get_string('noticetitle', 'local_notice'),
        get_string('status', 'local_notice'),
        get_string('period', 'local_notice'),
        get_string('seenby', 'local_notice'),
        get_string('lastmodified', 'local_notice'),
        get_string('actions'),
    ];
    $dateformat = get_string('strftimedatetimeshort', 'langconfig');

    foreach ($notices as $n) {
        // Status badge.
        if (!$n->enabled) {
            $status = html_writer::span(get_string('statusdisabled', 'local_notice'), 'badge bg-secondary text-white');
        } else if ($n->timestart > $now) {
            $status = html_writer::span(get_string('statusscheduled', 'local_notice'), 'badge bg-info text-white');
        } else if ($n->timeend && $n->timeend <= $now) {
            $status = html_writer::span(get_string('statusexpired', 'local_notice'), 'badge bg-warning text-dark');
        } else {
            $status = html_writer::span(get_string('statusactive', 'local_notice'), 'badge bg-success text-white');
        }

        $period = ($n->timestart ? userdate($n->timestart, $dateformat) : get_string('anytime', 'local_notice'))
            . ' &rarr; ' . ($n->timeend ? userdate($n->timeend, $dateformat) : get_string('noend', 'local_notice'));

        $actions = [];
        $actions[] = $OUTPUT->action_icon(new moodle_url('/local/notice/edit.php', ['id' => $n->id]),
            new pix_icon('t/edit', get_string('edit')));
        $actions[] = $OUTPUT->action_icon(new moodle_url($baseurl, ['action' => 'preview', 'id' => $n->id]),
            new pix_icon('t/preview', get_string('preview', 'local_notice')));
        $toggle = $n->enabled ? 'disable' : 'enable';
        $actions[] = $OUTPUT->action_icon(
            new moodle_url($baseurl, ['action' => $toggle, 'id' => $n->id, 'sesskey' => sesskey()]),
            new pix_icon($n->enabled ? 't/hide' : 't/show', get_string($toggle)));
        $actions[] = $OUTPUT->action_icon(new moodle_url($baseurl, ['action' => 'reset', 'id' => $n->id]),
            new pix_icon('t/reset', get_string('resetacks', 'local_notice')));
        $actions[] = $OUTPUT->action_icon(new moodle_url($baseurl, ['action' => 'delete', 'id' => $n->id]),
            new pix_icon('t/delete', get_string('delete')));

        $table->data[] = [
            format_string($n->title),
            $status,
            $period,
            (int) $n->ackcount,
            userdate($n->timemodified, $dateformat),
            implode(' ', $actions),
        ];
    }
    echo html_writer::table($table);
}

if (!empty($previewhtml)) {
    echo $previewhtml;
}

echo $OUTPUT->footer();
