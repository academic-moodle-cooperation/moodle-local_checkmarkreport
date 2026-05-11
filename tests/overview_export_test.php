<?php
// This file is part of local_checkmarkreport for Moodle - http://moodle.org/
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
 * Unit tests for overview export.
 *
 * @package     local_checkmarkreport
 * @author      Clemens Marx
 * @copyright   2026 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_checkmarkreport_overview::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_checkmarkreport\privacy\provider::class)]
final class overview_export_test extends \advanced_testcase {
    /**
     * Load the plugin classes under test.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;

        $plugindir = dirname(__DIR__);
        require_once($plugindir . '/classes/html_table_colgroups.php');
        require_once($plugindir . '/classes/base.php');
        require_once($plugindir . '/classes/overview.php');
        require_once($plugindir . '/classes/privacy/provider.php');

        parent::setUpBeforeClass();
    }

    /**
     * Percentage values must be serialized as spreadsheet-scale numbers.
     */
    public function test_percentage_export_value_uses_numeric_spreadsheet_scale(): void {
        $this->assertSame('0', $this->call_overview_method('format_percentage_export_value', [0]));
        $this->assertSame('0.0235', $this->call_overview_method('format_percentage_export_value', [2.35]));
        $this->assertSame('0.0256', $this->call_overview_method('format_percentage_export_value', [2.56]));
        $this->assertSame('0.6026', $this->call_overview_method('format_percentage_export_value', [60.26]));
        $this->assertSame('1', $this->call_overview_method('format_percentage_export_value', [100]));
    }

    /**
     * Export cells keep the display text but mark the raw value for spreadsheet writers.
     */
    public function test_percentage_cell_marks_export_values_as_numeric_percentages(): void {
        $cell = $this->call_overview_method('create_percentage_cell', [2.56, true]);

        $this->assertSame('2.56%', $cell->text);
        $this->assertArrayHasKey('percentage', $cell->attributes);
        $this->assertTrue($cell->attributes['percentage']);
        $this->assertSame('0.0256', $cell->attributes['percentage-value']);

        $htmlcell = $this->call_overview_method('create_percentage_cell', [2.56, false]);

        $this->assertSame('2.56%', $htmlcell->text);
        $this->assertArrayNotHasKey('percentage', $htmlcell->attributes);
        $this->assertArrayNotHasKey('percentage-value', $htmlcell->attributes);
    }

    /**
     * Course-level grade percentages are calculated from the course sum and maximum grade.
     */
    public function test_course_percentgrade_uses_course_sum_and_maxgrade(): void {
        $this->assertSame(2.81, $this->call_overview_method('get_course_percentgrade', [
            (object) ['coursesum' => 20, 'maxgrade' => 712],
        ]));
        $this->assertSame(0.0, $this->call_overview_method('get_course_percentgrade', [
            (object) ['coursesum' => 0, 'maxgrade' => 712],
        ]));
        $this->assertSame(0.0, $this->call_overview_method('get_course_percentgrade', [
            (object) ['coursesum' => -1, 'maxgrade' => 712],
        ]));
        $this->assertSame(0.0, $this->call_overview_method('get_course_percentgrade', [
            (object) ['coursesum' => 20, 'maxgrade' => 0],
        ]));
    }

    /**
     * Instance-level grade percentages use final grade values when they override calculated grades.
     */
    public function test_instance_percentgrade_uses_finalgrade_for_overwritten_grades(): void {
        $this->assertSame(44.94, $this->call_overview_method('get_instance_percentgrade', [
            $this->make_instancedata(44.94, 44.94, 100, 44.94, false, false),
        ]));
        $this->assertSame(88.0, $this->call_overview_method('get_instance_percentgrade', [
            $this->make_instancedata(20, 20, 100, 88, true, false),
        ]));
        $this->assertSame(55.0, $this->call_overview_method('get_instance_percentgrade', [
            $this->make_instancedata(20, 20, 100, 55, false, true),
        ]));
        $this->assertSame(0.0, $this->call_overview_method('get_instance_percentgrade', [
            $this->make_instancedata(0, 0, 100, 0, false, false),
        ]));
        $this->assertSame(0.0, $this->call_overview_method('get_instance_percentgrade', [
            $this->make_instancedata(20, 20, 0, 88, true, false),
        ]));
    }

    /**
     * The new percentage columns are independent from the legacy combined percentage column.
     */
    public function test_export_table_contains_separate_percentage_columns_before_combined_column(): void {
        global $PAGE;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $PAGE->set_url('/local/checkmarkreport/index.php', ['courseid' => $course->id]);

        $this->enable_percentage_export_preferences();

        $instance = (object) [
            'id' => 7,
            'name' => 'Kreuzerluebung 1',
            'coursemodule' => 42,
        ];
        $row = $this->make_course_row($user, $instance->id);
        $report = $this->make_overview_report($course->id, [$user->id => $row], [$instance]);

        $table = $report->get_table(true);

        $headkeys = array_keys($table->head[0]->cells);
        $this->assertLessThan(
            array_search('percentgrade', $headkeys, true),
            array_search('percentchecked', $headkeys, true)
        );
        $this->assertLessThan(
            array_search('percentex', $headkeys, true),
            array_search('percentgrade', $headkeys, true)
        );

        $subheadkeys = array_keys($table->head[1]->cells);
        $this->assertLessThan(
            array_search('percentgrade' . $instance->id, $subheadkeys, true),
            array_search('percentchecked' . $instance->id, $subheadkeys, true)
        );
        $this->assertLessThan(
            array_search('percentex' . $instance->id, $subheadkeys, true),
            array_search('percentgrade' . $instance->id, $subheadkeys, true)
        );

        $cells = $table->data[$user->id]->cells;
        $this->assertSame('2.56%', $cells['percentchecked']->text);
        $this->assertSame('0.0256', $cells['percentchecked']->attributes['percentage-value']);
        $this->assertSame('2.81%', $cells['percentgrade']->text);
        $this->assertSame('0.0281', $cells['percentgrade']->attributes['percentage-value']);
        $this->assertSame('2.56% (2.81 %)', $cells['percentex']->text);

        $this->assertSame('60.26%', $cells['percentchecked' . $instance->id]->text);
        $this->assertSame('0.6026', $cells['percentchecked' . $instance->id]->attributes['percentage-value']);
        $this->assertSame('44.94%', $cells['percentgrade' . $instance->id]->text);
        $this->assertSame('0.4494', $cells['percentgrade' . $instance->id]->attributes['percentage-value']);
        $this->assertSame('60.26% (44.94%)', $cells['percentex' . $instance->id]->text);
    }

    /**
     * Privacy exports include the two new user preferences.
     */
    public function test_percentage_preferences_are_exported_by_privacy_api(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        set_user_preference('checkmarkreport_sumrelchecked', 1, $user);
        set_user_preference('checkmarkreport_sumrelgrade', 0, $user);

        \local_checkmarkreport\privacy\provider::export_user_preferences($user->id);

        $writer = \core_privacy\local\request\writer::with_context(\context_system::instance());
        $preferences = $writer->get_user_preferences('local_checkmarkreport');

        $this->assertSame(
            get_string('privacy:metadata:sumrelchecked', 'local_checkmarkreport'),
            $preferences->checkmarkreport_sumrelchecked->description
        );
        $this->assertSame(get_string('yes'), $preferences->checkmarkreport_sumrelchecked->value);
        $this->assertSame(
            get_string('privacy:metadata:sumrelgrade', 'local_checkmarkreport'),
            $preferences->checkmarkreport_sumrelgrade->description
        );
        $this->assertSame(get_string('no'), $preferences->checkmarkreport_sumrelgrade->value);
    }

    /**
     * Invoke a private method on the overview report.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    private function call_overview_method(string $method, array $arguments = []) {
        $reflection = new \ReflectionClass(\local_checkmarkreport_overview::class);
        $report = $reflection->newInstanceWithoutConstructor();
        $reflectedmethod = $reflection->getMethod($method);
        $reflectedmethod->setAccessible(true);

        return $reflectedmethod->invokeArgs($report, $arguments);
    }

    /**
     * Create instance data with grade fields relevant for percentage calculations.
     *
     * @param float $grade
     * @param float $percentgrade
     * @param float $maxgrade
     * @param float|null $finalgrade
     * @param bool $overridden
     * @param bool $locked
     * @return stdClass
     */
    private function make_instancedata(
        float $grade,
        float $percentgrade,
        float $maxgrade,
        ?float $finalgrade,
        bool $overridden,
        bool $locked
    ): stdClass {
        return (object) [
            'grade' => $grade,
            'percentgrade' => $percentgrade,
            'maxgrade' => $maxgrade,
            'checked' => 3,
            'maxchecked' => 5,
            'percentchecked' => 60.26,
            'examples' => [],
            'finalgrade' => (object) [
                'grade' => $finalgrade,
                'overridden' => $overridden,
                'locked' => $locked,
                'dategraded' => time(),
                'usermodified' => 2,
            ],
        ];
    }

    /**
     * Enable only the relevant percentage columns for a compact export table.
     */
    private function enable_percentage_export_preferences(): void {
        set_user_preference('checkmarkreport_showexamples', 0);
        set_user_preference('checkmarkreport_showgrade', 0);
        set_user_preference('checkmarkreport_sumabs', 0);
        set_user_preference('checkmarkreport_sumrelchecked', 1);
        set_user_preference('checkmarkreport_sumrelgrade', 1);
        set_user_preference('checkmarkreport_sumrel', 1);
        set_user_preference('checkmarkreport_showpoints', 0);
        set_user_preference('checkmarkreport_showattendances', 0);
        set_user_preference('checkmarkreport_showpresentationgrades', 0);
        set_user_preference('checkmarkreport_showpresentationcount', 0);
        set_user_preference('checkmarkreport_signature', 0);
        set_user_preference('checkmarkreport_seperatenamecolumns', 0);
    }

    /**
     * Create a course report row with one checkmark instance.
     *
     * @param stdClass $user
     * @param int $instanceid
     * @return stdClass
     */
    private function make_course_row(stdClass $user, int $instanceid): stdClass {
        $row = clone $user;
        $row->checks = 2;
        $row->maxchecks = 85;
        $row->percentchecked = 2.56;
        $row->coursesum = 20;
        $row->maxgrade = 712;
        $row->overridden = false;
        $row->instancedata = [
            $instanceid => $this->make_instancedata(44.94, 44.94, 100, 44.94, false, false),
        ];

        return $row;
    }

    /**
     * Build a lightweight overview report using deterministic test data.
     *
     * @param int $courseid
     * @param array $coursedata
     * @param array $instances
     * @return local_checkmarkreport_overview
     */
    private function make_overview_report(int $courseid, array $coursedata, array $instances): local_checkmarkreport_overview {
        return new class ($courseid, $coursedata, $instances) extends \local_checkmarkreport_overview {
            /** @var array */
            private $testcoursedata;

            /** @var array */
            private $testinstances;

            /**
             * Constructor.
             *
             * @param int $courseid
             * @param array $coursedata
             * @param array $instances
             */
            public function __construct(int $courseid, array $coursedata, array $instances) {
                $this->testcoursedata = $coursedata;
                $this->testinstances = $instances;
                parent::__construct($courseid);
            }

            /**
             * Return deterministic course data.
             *
             * @return array
             */
            public function get_coursedata() {
                return $this->testcoursedata;
            }

            /**
             * Return deterministic checkmark instances.
             *
             * @return array
             */
            public function get_courseinstances_formatted_name() {
                return $this->testinstances;
            }

            /**
             * Keep sort headers plain in unit tests.
             *
             * @param string $column
             * @param string $text
             * @param moodle_url|string $url
             * @return string
             */
            public function get_sortlink($column, $text, $url) {
                return $text;
            }

            /**
             * No attendance columns in this test report.
             *
             * @return bool
             */
            public function attendancestracked() {
                return false;
            }

            /**
             * No tracked attendance columns in this test report.
             *
             * @param int $chkmkid
             * @return bool
             */
            public function tracksattendance($chkmkid = 0) {
                return false;
            }

            /**
             * No presentation grade columns in this test report.
             *
             * @return bool
             */
            public function presentationsgraded() {
                return false;
            }

            /**
             * No per-instance presentation grade columns in this test report.
             *
             * @param int $chkmkid
             * @return bool
             */
            public function gradepresentations($chkmkid = 0) {
                return false;
            }

            /**
             * No presentation grade columns in this test report.
             *
             * @return int
             */
            public function countgradingpresentations() {
                return 0;
            }

            /**
             * No presentation points in this test report.
             *
             * @param int $chkmkid
             * @return array|int
             */
            public function pointsforpresentations($chkmkid = 0) {
                return empty($chkmkid) ? [] : 0;
            }
        };
    }
}
