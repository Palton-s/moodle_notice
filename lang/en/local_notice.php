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
 * English strings for local_notice.
 *
 * @package    local_notice
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['acksreset'] = 'The notice will be shown again to all users.';
$string['addnotice'] = 'Add notice';
$string['anytime'] = 'Immediately';
$string['buttonlabel'] = 'Button text';
$string['buttonlabel_help'] = 'Text of the button that closes the notice. Leave empty to use "Got it".';
$string['confirmdelete'] = 'Delete the notice "{$a}"? This cannot be undone.';
$string['confirmreset'] = 'Show the notice "{$a}" again to every user, including those who already closed it?';
$string['defaultbutton'] = 'Got it';
$string['editnotice'] = 'Edit notice';
$string['enabled'] = 'Show notices';
$string['enabled_desc'] = 'When disabled, no notice is shown to users, regardless of each notice\'s own status.';
$string['errorendbeforestart'] = 'The end date must be after the start date.';
$string['lastmodified'] = 'Last modified';
$string['managenotices'] = 'Manage notices';
$string['noend'] = 'No end';
$string['nonotices'] = 'No notices yet. Click "Add notice" to create the first one.';
$string['notice:manage'] = 'Manage login notices';
$string['noticecontent'] = 'Content';
$string['noticecontent_help'] = 'Build the notice with text, images, videos, links and files. It is shown in a popup the next time each user opens a page after logging in.';
$string['noticedeleted'] = 'Notice deleted.';
$string['noticeenabled'] = 'Enabled';
$string['noticesaved'] = 'Notice saved.';
$string['noticetitle'] = 'Title';
$string['period'] = 'Period';
$string['pluginisdisabled'] = 'Notices are globally disabled, so users won\'t see any of them. <a href="{$a}">Enable them in the settings</a>.';
$string['pluginname'] = 'Login notices';
$string['preview'] = 'Preview';
$string['privacy:metadata:local_notice'] = 'Notices created by administrators.';
$string['privacy:metadata:local_notice:usermodified'] = 'The user who last modified the notice.';
$string['privacy:metadata:local_notice_ack'] = 'Records which notices each user has closed, so they are not shown again.';
$string['privacy:metadata:local_notice_ack:noticeid'] = 'The notice that was closed.';
$string['privacy:metadata:local_notice_ack:timecreated'] = 'When the notice was closed.';
$string['privacy:metadata:local_notice_ack:userid'] = 'The user who closed the notice.';
$string['resetacks'] = 'Show again to everyone';
$string['resetacksonsave'] = 'Show again to everyone';
$string['resetacksonsave_help'] = 'If checked, users who already closed this notice will see it again (useful after important changes).';
$string['seenby'] = 'Seen by';
$string['settings'] = 'Settings';
$string['status'] = 'Status';
$string['statusactive'] = 'Active';
$string['statusdisabled'] = 'Disabled';
$string['statusexpired'] = 'Expired';
$string['statusscheduled'] = 'Scheduled';
$string['timeend'] = 'Show until';
$string['timeend_help'] = 'Optional. After this date the notice is no longer shown.';
$string['timestart'] = 'Show from';
$string['timestart_help'] = 'Optional. The notice is only shown from this date on.';
