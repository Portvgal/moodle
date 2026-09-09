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
 * The testable assign class.
 *
 * @package   mod_assign
 * @copyright 2014 Adrian Greeve <adrian@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Test subclass that makes all the protected methods we want to test public.
 */
class mod_assign_testable_assign extends assign {

    /** @var int Number of calls made to the single-user override resolver. */
    public int $overrideexistscalls = 0;

    /** @var int Number of calls made to the bulk attempt-limit resolver. */
    public int $bulkresolvercalls = 0;

    /**
     * Count calls to the single-user override resolver.
     *
     * @param int $userid The user ID.
     * @return stdClass The effective override record.
     */
    public function override_exists($userid) {
        $this->overrideexistscalls++;
        return parent::override_exists($userid);
    }

    public function testable_show_intro() {
        return parent::show_intro();
    }

    public function testable_delete_grades() {
        return parent::delete_grades();
    }

    public function testable_apply_grade_to_user($formdata, $userid, $attemptnumber) {
        return parent::apply_grade_to_user($formdata, $userid, $attemptnumber);
    }

    public function testable_get_grading_userid_list() {
        return parent::get_grading_userid_list();
    }

    public function testable_is_graded($userid) {
        return parent::is_graded($userid);
    }

    public function testable_update_submission(stdClass $submission, $userid, $updatetime, $teamsubmission) {
        return parent::update_submission($submission, $userid, $updatetime, $teamsubmission);
    }

    /**
     * Process adding an attempt.
     *
     * @param int $userid The user ID.
     * @param int|null $maxattempts Effective maximum attempts, or null to resolve it.
     * @return bool Whether the attempt was added.
     */
    public function testable_process_add_attempt($userid = 0, ?int $maxattempts = null) {
        return parent::process_add_attempt($userid, $maxattempts);
    }

    /**
     * Test the reopen decision and attempt limit resolution.
     *
     * @param int $userid The user ID.
     * @param stdClass $submission The submission record.
     * @param bool $addattempt Whether manual reopening was requested.
     * @return bool Whether an attempt was added.
     */
    public function testable_reopen_submission_if_required($userid, $submission, $addattempt): bool {
        return parent::reopen_submission_if_required($userid, $submission, $addattempt);
    }

    /**
     * Resolve effective maximum attempts in bulk.
     *
     * @param array $useridlist User IDs to resolve.
     * @return array Effective maximum attempts keyed by user ID.
     */
    public function testable_get_effective_maxattempts(array $useridlist): array {
        return parent::get_effective_maxattempts($useridlist);
    }

    /**
     * Count use of the bulk attempt-limit resolver.
     *
     * @param array $useridlist User IDs to resolve.
     * @return array Effective maximum attempts keyed by user ID.
     */
    protected function get_effective_maxattempts(array $useridlist): array {
        $this->bulkresolvercalls++;
        return parent::get_effective_maxattempts($useridlist);
    }

    /**
     * Run a grading batch operation with simulated request data.
     *
     * @param string $operation The operation name.
     * @param array $useridlist Selected user IDs.
     * @return string The next action.
     */
    public function testable_process_grading_batch_operation(string $operation, array $useridlist): string {
        global $_POST;

        $_POST['operation'] = $operation;
        $_POST['selectedusers'] = implode(',', $useridlist);
        return parent::process_grading_batch_operation();
    }

    /**
     * Check whether the bulk grant attempt action should be available for the listed users.
     *
     * @param array $useridlist Array of userids visible in the grading table.
     * @return bool
     */
    public function testable_has_bulk_grant_attempt_available(array $useridlist): bool {
        return parent::has_bulk_grant_attempt_available($useridlist);
    }

    public function testable_process_save_quick_grades($postdata) {
        // Ugly hack to get something into the method.
        global $_POST;
        $_POST = $postdata;
        return parent::process_save_quick_grades();
    }

    public function testable_process_set_batch_marking_allocation($selectedusers, $markerid) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/batchsetallocatedmarkerform.php');

        // Simulate the form submission.
        $data = array();
        $data['id'] = $this->get_course_module()->id;
        $data['selectedusers'] = $selectedusers;
        $data['allocatedmarker[1]'] = $markerid;
        $data['action'] = 'setbatchmarkingallocation';
        mod_assign_batch_set_allocatedmarker_form::mock_submit($data);

        return parent::process_set_batch_marking_allocation();
    }

    public function testable_process_set_batch_marking_workflow_state($selectedusers, $state) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/batchsetmarkingworkflowstateform.php');

        // Simulate the form submission.
        $data = array();
        $data['id'] = $this->get_course_module()->id;
        $data['selectedusers'] = $selectedusers;
        $data['markingworkflowstate'] = $state;
        $data['action'] = 'setbatchmarkingworkflowstate';
        mod_assign_batch_set_marking_workflow_state_form::mock_submit($data);

        return parent::process_set_batch_marking_workflow_state();
    }

    public function testable_submissions_open($userid = 0) {
        return parent::submissions_open($userid);
    }

    public function testable_save_user_extension($userid, $extensionduedate) {
        return parent::save_user_extension($userid, $extensionduedate);
    }

    public function testable_get_graders($userid) {
        // Changed method from protected to public.
        return parent::get_graders($userid);
    }

    public function testable_get_notifiable_users($userid) {
        return parent::get_notifiable_users($userid);
    }

    public function testable_view_batch_set_workflow_state($selectedusers) {
        global $PAGE;
        $PAGE->set_url('/mod/assign/view.php');
        $_POST['selectedusers'] = $selectedusers;
        return parent::view_batch_set_workflow_state();
    }

    public function testable_view_batch_markingallocation($selectedusers) {
        global $PAGE;
        $PAGE->set_url('/mod/assign/view.php');
        $_POST['selectedusers'] = $selectedusers;
        return parent::view_batch_markingallocation();
    }

    public function testable_update_activity_completion_records($teamsubmission,
                                                          $requireallteammemberssubmit,
                                                          $submission,
                                                          $userid,
                                                          $complete,
                                                          $completion) {
        return parent::update_activity_completion_records($teamsubmission,
                                                          $requireallteammemberssubmit,
                                                          $submission,
                                                          $userid,
                                                          $complete,
                                                          $completion);
    }
}
