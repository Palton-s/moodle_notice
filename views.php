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
 * Users who saw a notice: search, filters, pagination and download.
 *
 * @package    local_notice
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_notice\local\manager;
use local_notice\table\views_table;

$id = required_param('id', PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = trim(optional_param('search', '', PARAM_TEXT));
$status = optional_param('status', 'seen', PARAM_ALPHA);
$datefrom = optional_param('datefrom', '', PARAM_RAW_TRIMMED);
$dateto = optional_param('dateto', '', PARAM_RAW_TRIMMED);
$closemethod = optional_param('closemethod', '', PARAM_ALPHA);
$perpage = optional_param('perpage', 50, PARAM_INT);

$pageurl = new moodle_url('/local/notice/views.php', ['id' => $id]);
admin_externalpage_setup('local_notice_manage', '', null, $pageurl);
$context = context_system::instance();
require_capability('local/notice:viewreport', $context);

$notice = $DB->get_record(manager::TABLE, ['id' => $id], '*', MUST_EXIST);
$title = format_string($notice->title, true, ['context' => $context]);

// Normalise filters.
$perpageoptions = [20, 50, 100, 250];
if (!in_array($perpage, $perpageoptions)) {
    $perpage = 50;
}
if (!in_array($status, views_table::STATUSES)) {
    $status = 'seen';
}
if (!in_array($closemethod, manager::CLOSE_METHODS, true)) {
    $closemethod = '';
}
$timefrom = manager::parse_date_input($datefrom);
$timeto = manager::parse_date_input($dateto, true);
$datefrom = $timefrom ? $datefrom : '';
$dateto = $timeto ? $dateto : '';

$filterparams = array_filter([
    'search' => $search,
    'status' => $status === 'seen' ? '' : $status,
    'datefrom' => $datefrom,
    'dateto' => $dateto,
    'closemethod' => $closemethod,
    'perpage' => $perpage == 50 ? '' : $perpage,
], fn($v) => $v !== '');
$baseurl = new moodle_url($pageurl, $filterparams);
$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('viewsreport', 'local_notice', $title));
$PAGE->navbar->add($title);

$table = new views_table($notice, [
    'search' => $search,
    'status' => $status,
    'timefrom' => $timefrom,
    'timeto' => $timeto,
    'closemethod' => $closemethod,
], $baseurl);

$filename = clean_filename(get_string('viewsfilename', 'local_notice') . '-' . shorten_text($notice->title, 40, true, '')
    . '-' . userdate(time(), '%Y-%m-%d'));
$table->is_downloading($download, $filename, get_string('seenby', 'local_notice'));
if ($table->is_downloading()) {
    $table->out($perpage, false);
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('viewsreport', 'local_notice', $title));
echo html_writer::div(html_writer::link(new moodle_url('/local/notice/manage.php'),
    $OUTPUT->pix_icon('t/left', '') . get_string('backtonotices', 'local_notice')), 'mb-3');

// Summary: how many active users saw it.
$guestid = $CFG->siteguest;
$activewhere = 'u.deleted = 0 AND u.suspended = 0 AND u.confirmed = 1 AND u.id <> :guestid';
$totalactive = $DB->count_records_select('user', $activewhere, ['guestid' => $guestid]);
$seenactive = $DB->count_records_sql(
    "SELECT COUNT(1)
       FROM {" . manager::TABLE_ACK . "} a
       JOIN {user} u ON u.id = a.userid
      WHERE a.noticeid = :noticeid AND $activewhere",
    ['noticeid' => $notice->id, 'guestid' => $guestid]
);
$percent = $totalactive ? round($seenactive * 100 / $totalactive, 1) : 0;
echo $OUTPUT->render_from_template('local_notice/views_summary', [
    'text' => get_string('viewssummary', 'local_notice', (object) [
        'seen' => $seenactive,
        'total' => $totalactive,
        'notseen' => $totalactive - $seenactive,
        'percent' => format_float($percent, 1, true, true),
    ]),
    'percent' => $percent,
    'percentcss' => str_replace(',', '.', (string) $percent),
]);

// Filters form.
$option = fn($value, $label, $current) => ['value' => $value, 'label' => $label, 'selected' => (string) $value === (string) $current];
echo $OUTPUT->render_from_template('local_notice/views_filters', [
    'action' => (new moodle_url('/local/notice/views.php'))->out(false),
    'id' => $notice->id,
    'search' => $search,
    'statuses' => array_map(fn($s) => $option($s, get_string('status' . $s, 'local_notice'), $status),
        views_table::STATUSES),
    'datefrom' => $datefrom,
    'dateto' => $dateto,
    'accessfiltersdisabled' => $status === 'notseen',
    'closemethods' => array_merge(
        [$option('', get_string('closemethod_any', 'local_notice'), $closemethod)],
        array_map(fn($m) => $option($m, get_string('closemethod_' . $m, 'local_notice'), $closemethod),
            manager::CLOSE_METHODS)
    ),
    'perpageoptions' => array_map(fn($n) => $option($n, $n, $perpage), $perpageoptions),
    'hasfilters' => (bool) array_diff_key($filterparams, ['perpage' => 1]),
    'reseturl' => $pageurl->out(false),
]);

$table->out($perpage, false);

echo $OUTPUT->footer();
