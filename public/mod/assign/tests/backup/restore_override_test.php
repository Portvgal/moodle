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

namespace mod_assign\backup;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");

/**
 * Restore override tests.
 *
 * @package     mod_assign
 * @author      Alexander Van der Bellen <alexandervanderbellen@catalyst-au.net>
 * @copyright   2025 Catalyst IT Australia Pty Ltd
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_assign_activity_structure_step::class)]
final class restore_override_test extends \restore_date_testcase {
    /**
     * Allowed attempts must survive backup and restore without date offsets.
     *
     * @param bool $groupmode Whether to save a group override
     * @param int|null $maxattempts Limit to restore
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('maxattempts_provider')]
    public function test_restore_maxattempts(bool $groupmode, ?int $maxattempts): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $assign = $generator->create_module('assign', ['course' => $course->id, 'maxattempts' => 1]);
        $manager = new \mod_assign\override_manager($assign, \context_module::instance($assign->cmid));
        $override = ['maxattempts' => $maxattempts];
        if ($groupmode) {
            $group = $generator->create_group(['courseid' => $course->id]);
            groups_add_member($group, $student);
            $override['groupid'] = $group->id;
        } else {
            $override['userid'] = $student->id;
        }
        $manager->save_overrides([$override]);
        $newcourseid = $this->backup_and_restore($course);
        $newassign = $DB->get_record('assign', ['course' => $newcourseid], '*', MUST_EXIST);
        $restored = $DB->get_record('assign_overrides', ['assignid' => $newassign->id], '*', MUST_EXIST);
        if ($maxattempts === null) {
            $this->assertNull($restored->maxattempts);
        } else {
            $this->assertEquals($maxattempts, $restored->maxattempts);
        }
        $this->assertEquals($groupmode, !empty($restored->groupid));
    }

    /**
     * User and group limits, including inheritance.
     *
     * @return array
     */
    public static function maxattempts_provider(): array {
        return [
            'user finite' => [false, 4],
            'user unlimited' => [false, -1],
            'user inherited' => [false, null],
            'group finite' => [true, 4],
            'group unlimited' => [true, -1],
            'group inherited' => [true, null],
        ];
    }

    /**
     * A backup made before allowed-attempt overrides existed must still restore.
     */
    public function test_restore_override_without_maxattempts(): void {
        global $DB, $CFG, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $assign = $generator->create_module('assign', ['course' => $course->id, 'maxattempts' => 1]);
        $manager = new \mod_assign\override_manager($assign, \context_module::instance($assign->cmid));
        $manager->save_overrides([['userid' => $student->id, 'maxattempts' => 4]]);

        $CFG->backup_file_logger_level = \backup::LOG_NONE;
        set_config('backup_general_users', 1, 'backup');
        $backup = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $backup->execute_plan();
        $file = $backup->get_results()['backup_destination'];
        $directory = 'test-restore-legacy-attempts';
        $path = $CFG->dataroot . '/temp/backup/' . $directory;
        $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $path);
        $backup->destroy();

        // Remove only the new override field, preserving the assignment's own attempt limit.
        $xmlpath = $path . '/activities/assign_' . $assign->cmid . '/assign.xml';
        $document = new \DOMDocument();
        $this->assertTrue($document->load($xmlpath));
        $xpath = new \DOMXPath($document);
        $fields = $xpath->query('//override/maxattempts');
        $this->assertCount(1, $fields);
        foreach ($fields as $field) {
            $field->parentNode->removeChild($field);
        }
        $document->save($xmlpath);

        $newcourseid = \restore_dbops::create_new_course($course->fullname, $course->shortname . '_legacy', $course->category);
        $restore = new \restore_controller(
            $directory,
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($restore->execute_precheck());
        $restore->execute_plan();
        $restore->destroy();
        $newassign = $DB->get_record('assign', ['course' => $newcourseid], '*', MUST_EXIST);
        $override = $DB->get_record('assign_overrides', ['assignid' => $newassign->id], '*', MUST_EXIST);
        $this->assertNull($override->maxattempts);
        $this->assertEquals(1, $newassign->maxattempts);
    }

    /**
     * Test restore overrides with reason.
     */
    public function test_restore_overrides_with_reason(): void {
        global $DB, $USER;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);

        $useroverride = (object) [
            'assignid' => $assign->id,
            'userid' => $USER->id,
            'sortorder' => 1,
            'allowsubmissionsfromdate' => 100,
            'reason' => 'This is a reason',
            'reasonformat' => FORMAT_HTML,
        ];
        $DB->insert_record('assign_overrides', $useroverride);

        // Back up and restore.
        $newcourseid = $this->backup_and_restore($course);
        $newassign = $DB->get_record('assign', ['course' => $newcourseid]);
        $overrides = $DB->get_records('assign_overrides', ['assignid' => $newassign->id]);

        $this->assertEquals(1, count($overrides));
        $restoredoverride = reset($overrides);
        $this->assertEquals($useroverride->reason, $restoredoverride->reason);
        $this->assertEquals($useroverride->reasonformat, $restoredoverride->reasonformat);
    }
}
