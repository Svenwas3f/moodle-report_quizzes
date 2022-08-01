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
 * @package   Report_quizzes
 * @copyright 2022, Sven Waser <sven.waser@brogrammer.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Require config file.
require_once('../../config.php');

// Require admin.
require_admin();

// Set context.
$PAGE->set_context(context_system::instance());

// Prepare Page.
$PAGE->set_url('/report/quizzes/index.php');
$PAGE->set_title(get_string('header_title', 'report_quizzes'));
$PAGE->set_heading(get_string('header_heading', 'report_quizzes'));

// Display Heading.
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string(get_string('header_heading', 'report_quizzes')));

// Data for table.
$tabledata = array();

// Get users who submited a quiz.
$sqlusers = "SELECT * FROM {user} WHERE
    id IN (SELECT DISTINCT userid FROM {quiz_attempts})";


// Loop users.
foreach ($DB->get_records_sql($sqlusers) as $user) {
    // Check quizzes.
    $sqlquizzes = "SELECT * FROM {quiz}";
    $quizzes = $DB->get_records_sql($sqlquizzes);

    // Create array for passed quizzes.
    $quizzespassed = array();

    foreach ($quizzes as $quiz) { // Loop quizzes.
        // Check quiz.
        switch ($quiz->grademethod) {
            case 1: // Best attempt.
                $sqlquizpassed = "SELECT COUNT(id) FROM {quiz_attempts} WHERE
                    userid=:userid AND
                    quiz=:quiz AND
                    sumgrades >= (SELECT gradepass FROM {grade_items} WHERE
                        iteminstance=:iteminstance AND
                        itemmodule='quiz'
                    )
                    ORDER BY sumgrades DESC";
            break;
            case 2: // Average.
                $sqlquizpassed = "SELECT COUNT(id) FROM {grade_items} WHERE
                    iteminstance=:iteminstance AND
                    itemmodule='quiz' AND
                    gradepass >= (SELECT AVG(sumgrades) FROM {quiz_attempts} WHERE
                        userid=:userid AND
                        quiz=:quiz
                    )";
            break;
            case 3: // First attempt.
                $sqlquizpassed = "SELECT COUNT(id) FROM {quiz_attempts} WHERE
                    userid=:userid AND
                    quiz=:quiz AND
                    sumgrades >= (SELECT gradepass FROM {grade_items} WHERE
                        iteminstance=:iteminstance AND
                        itemmodule='quiz'
                    )
                    ORDER BY id ASC";
            break;
            case 4: // Last attempt.
                $sqlquizpassed = "SELECT COUNT(id) FROM {quiz_attempts} WHERE
                    userid=:userid AND
                    quiz=:quiz AND
                    sumgrades >= (SELECT gradepass FROM {grade_items} WHERE
                        iteminstance=:iteminstance AND
                        itemmodule='quiz'
                    )
                    ORDER BY id DESC";
            break;
        }

        // Parameters for sql request.
        $paramsquizuser = array(
            "userid" => $user->id,
            "quiz" => $quiz->id,
            "iteminstance" => $quiz->id,
        );

        // Check if passed.
        if ($DB->count_records_sql($sqlquizpassed, $paramsquizuser) > 0) {
            $quizzespassed[] = $quiz->name;
        }
    }

    // Prepare row data.
    $novalue = get_string('td_no_result', 'report_quizzes'); // String if no value found.
    $quizzespassednames = implode(', ', $quizzespassed); // Concat names.
    $quizzespassednumber = (count($quizzespassed) . '/' . count($quizzes)); // Count quizzes.

    // Check if user should be displayed.
    if (count($quizzespassed) > 0) {
        // Create row data array.
        $rowdata = array(
            empty($user->username) ? $novalue : $user->username,
            empty($user->firstname . ' ' . $user->lastname) ? $novalue : ($user->firstname  . ' ' . $user->lastname),
            empty($user->email) ? $novalue : $user->email,
            empty($user->phone1) ? $novalue : $user->phone1,
            empty($quizzespassednames) ? $novalue : $quizzespassednames,
            empty($quizzespassednumber) ? $novalue : $quizzespassednumber,
        );

        // Add row to table.
        array_push($tabledata, $rowdata);
    }
}

// Create table.
$table = new html_table();
$table->head = array( // Fill headline.
    get_string('th_userid', 'report_quizzes'),
    get_string('th_fullname', 'report_quizzes'),
    get_string('th_email', 'report_quizzes'),
    get_string('th_phone', 'report_quizzes'),
    get_string('th_quizzes_completed', 'report_quizzes'),
    get_string('th_quizzes_completed_number', 'report_quizzes')
);
$table->data = $tabledata; // Fill data/content.

// Display table.
echo html_writer::table($table);

// Display footer.
echo $OUTPUT->footer();
