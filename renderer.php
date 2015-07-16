<?php
// This file is part of local_checkmarkreport for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a list of all checkmarkreport instances in the given course (via id)
 *
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class local_checkmarkreport_renderer extends plugin_renderer_base {

    protected function render_local_checkmarkreport_overview(local_checkmarkreport_overview $report) {
        global $CFG, $DB;

        $context = context_course::instance($report->get_courseid());

        // Render download links!
        $data = array('id'         => $report->get_courseid(),
                      'tab'        => optional_param('tab', null, PARAM_ALPHANUM),
                      'showgrade'  => false,
                      'showabs'    => false,
                      'showrel'    => false,
                      'showpoints' => false,
                      'sesskey'    => sesskey(),
                      'format'     => local_checkmarkreport_base::FORMAT_XLSX);
        $groups = $report->get_groups();
        $checkmarks = $report->get_instances();
        $arrays = http_build_query(array('groups'     => $groups,
                                         'checkmarks' => $checkmarks));
        $uri = new moodle_url('/local/checkmarkreport/download.php?'.$arrays, $data);
        $downloadlinks = get_string('exportas', 'local_checkmarkreport');
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.XLSX'),
                                           array('class' => 'downloadlink'));
        $uri = new moodle_url($uri, array('format' => local_checkmarkreport_base::FORMAT_ODS));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.ODS'),
                                           array('class' => 'downloadlink'));
        $uri = new moodle_url($uri, array('format' => local_checkmarkreport_base::FORMAT_XML));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.XML'),
                                           array('class' => 'downloadlink'));
        $uri = new moodle_url($uri, array('format' => local_checkmarkreport_base::FORMAT_TXT));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.TXT'),
                                           array('class' => 'downloadlink'));

        $out = html_writer::tag('div', $downloadlinks, array('class' => 'download'));

        // Render the table!
        $table = $report->get_table();

        $out .= html_writer::tag('div', $this->table($table, $report),
                                array('class' => 'scrollforced course-content'));

        return $this->output->container($out, 'submission', 'checkmarkreporttable');
    }

    protected function render_local_checkmarkreport_useroverview(local_checkmarkreport_useroverview $report, $hidefilter = false) {
        global $CFG, $DB, $PAGE, $COURSE;

        $out = '';
        $grade      = get_user_preferences('checkmarkreport_showgrade');
        $sumabs     = get_user_preferences('checkmarkreport_sumabs');
        $sumrel     = get_user_preferences('checkmarkreport_sumrel');
        $showpoints = get_user_preferences('checkmarkreport_showpoints');

        // Render download links!
        $data = array('id'         => $report->get_courseid(),
                      'tab'        => optional_param('tab', null, PARAM_ALPHANUM),
                      'showgrade'  => false,
                      'showabs'    => false,
                      'showrel'    => false,
                      'showpoints' => false,
                      'sesskey'    => sesskey(),
                      'format'     => local_checkmarkreport_base::FORMAT_XLSX);
        $groups = $report->get_groups();
        $users = $report->get_user();
        $arrays = http_build_query(array('groups' => $groups,
                                         'users'  => $users));
        $uri = new moodle_url('/local/checkmarkreport/download.php?'.$arrays, $data);
        $downloadlinks = get_string('exportas', 'local_checkmarkreport');
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.XLSX'),
                                           array('class' => 'downloadlink'));
        $uri = new moodle_url($uri, array('format' => local_checkmarkreport_base::FORMAT_ODS));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.ODS'),
                                           array('class' => 'downloadlink'));
        $uri = new moodle_url($uri, array('format' => local_checkmarkreport_base::FORMAT_XML));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.XML'),
                                           array('class' => 'downloadlink'));
        $uri = new moodle_url($uri, array('format' => local_checkmarkreport_base::FORMAT_TXT));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.TXT'),
                                           array('class' => 'downloadlink'));
        $out .= html_writer::tag('div', $downloadlinks, array('class' => 'download'));

        // Render the tables!
        $data = $report->get_coursedata();
        $users = $report->get_user();
        if (!empty($data) && !empty($users)) {
            foreach ($data as $userdata) {
                if (!in_array($userdata->id, $users) && !in_array(0, $users)) {
                    continue;
                }
                $table = $report->get_table($userdata);
                $url = new moodle_url('/user/view.php', array('id'     => $userdata->id,
                                                              'course' => $report->get_courseid()));
                $userlink = html_writer::link($url, fullname($userdata));
                $headingtext = get_string('overview', 'local_checkmarkreport').' - '.$userlink;
                $out .= $this->output->heading($headingtext, 1, strip_tags($headingtext));
                $out .= html_writer::tag('div', $this->table($table, $report), array('class' => 'collapsible'));
            }
        } else {
            $out .= $this->output->notification(get_string('nousers', 'checkmark'), 'notifyproblem');
        }

        if ($hidefilter == true) {
            // Skip wrapper for userview. It has its own wrapper!
            return $out;
        }
        return $this->output->container($out, 'report course-content', 'checkmarkreporttable');
    }

    protected function render_local_checkmarkreport_userview(local_checkmarkreport_userview $report) {
        // Render the table!
        $out = $this->render_local_checkmarkreport_useroverview($report, true);

        return $this->output->container($out, 'submission', 'checkmarkreporttable');
    }

    /**
     * Renders HTML table
     *
     * This method may modify the passed instance by adding some default properties if they are not set yet.
     * If this is not what you want, you should make a full clone of your data before passing them to this
     * method. In most cases this is not an issue at all so we do not clone by default for performance
     * and memory consumption reasons.
     *
     * @param html_table $table data to be rendered
     * @param checkmarkreport $report optional if given table can hide columns
     * @return string HTML code
     */
    protected function table(html_table $table, local_checkmarkreport_base $report = null) {

        if ($report == null) {
            $nohide = true;
        } else {
            $nohide = false;
        }

        // Prepare table data and populate missing properties with reasonable defaults!
        if (!empty($table->align)) {
            foreach ($table->align as $key => $aa) {
                if ($aa) {
                    $table->align[$key] = 'text-align:'. fix_align_rtl($aa) .';';  // Fix for RTL languages!
                } else {
                    $table->align[$key] = null;
                }
            }
        }
        if (!empty($table->size)) {
            foreach ($table->size as $key => $ss) {
                if ($ss) {
                    $table->size[$key] = 'width:'. $ss .';';
                } else {
                    $table->size[$key] = null;
                }
            }
        }
        if (!empty($table->wrap)) {
            foreach ($table->wrap as $key => $ww) {
                if ($ww) {
                    $table->wrap[$key] = 'white-space:nowrap;';
                } else {
                    $table->wrap[$key] = '';
                }
            }
        }
        if (!empty($table->head)) {
            foreach ($table->head as $key => $val) {
                if (!isset($table->align[$key])) {
                    $table->align[$key] = null;
                }
                if (!isset($table->size[$key])) {
                    $table->size[$key] = null;
                }
                if (!isset($table->wrap[$key])) {
                    $table->wrap[$key] = null;
                }
            }
        }
        if (empty($table->attributes['class'])) {
            $table->attributes['class'] = 'generaltable';
        }
        if (!empty($table->tablealign)) {
            $table->attributes['class'] .= ' boxalign' . $table->tablealign;
        }

        // Explicitly assigned properties override those defined via $table->attributes!
        $table->attributes['class'] = trim($table->attributes['class']);
        $attributes = array_merge($table->attributes, array('id'            => $table->id,
                                                            'width'         => $table->width,
                                                            'summary'       => $table->summary,
                                                            'cellpadding'   => $table->cellpadding,
                                                            'cellspacing'   => $table->cellspacing));
        $output = html_writer::start_tag('table', $attributes) . "\n";

        $countcols = 0;

        if (!empty($table->colgrps)) {
            $output .= html_writer::start_tag('colgroup');
            foreach ($table->colgrps as $colgrp) {
                $output .= html_writer::empty_tag('col', $colgrp);
            }
            $output .= html_writer::end_tag('colgroup');
        }

        if (!empty($table->head)) {
            $countrows = count($table->head);

            $output .= html_writer::start_tag('thead', array()) . "\n";

            $output .= html_writer::start_tag('tr', array()) . "\n";
            foreach ($table->head as $headrow) {
                $keys = array_keys($headrow->cells);
                $lastkey = end($keys);
                $countcols = count($headrow->cells);
                $idx = 0;
                foreach ($headrow->cells as $key => $heading) {
                    // Convert plain string headings into html_table_cell objects!
                    if (!($heading instanceof html_table_cell)) {
                        $headingtext = $heading;
                        $heading = new html_table_cell();
                        $heading->text = $headingtext;
                        $heading->header = true;
                    }
                    if ($heading->text == null) {
                        $idx++;
                        continue;
                    }
                    if ($heading->header !== false) {
                        $heading->header = true;
                    }

                    if ($heading->header && empty($heading->scope)) {
                        $heading->scope = 'col';
                    }

                    $heading->attributes['class'] .= ' header c' . $idx;
                    if (isset($heading->colspan) && $heading->colspan > 1) {
                        $countcols += $heading->colspan - 1;
                    }

                    if ($key == $lastkey) {
                        $heading->attributes['class'] .= ' lastcol';
                    }
                    if (isset($table->colclasses[$key])) {
                        $heading->attributes['class'] .= ' ' . $table->colclasses[$key];
                        $classes = explode(' ', $table->colclasses[$key]);
                    } else {
                        $classes = '';
                    }
                    $heading->attributes['class'] = trim($heading->attributes['class']);
                    $attributes = array_merge($heading->attributes, array(
                            'style'     => $heading->style,
                            'scope'     => $heading->scope,
                            'colspan'   => $heading->colspan,
                            'rowspan'   => $heading->rowspan
                        ));

                    $tagtype = 'td';
                    if ($heading->header === true) {
                        $tagtype = 'th';
                    }

                    if (!$nohide && ($report->column_is_hidden($key) || $report->column_is_hidden($classes))) {
                        $attributes['class'] .= ' hiddencol';
                    }
                    $content = html_writer::tag('div', $heading->text,
                                                array('class' => 'content')).
                               $this->get_toggle_links($key, $heading->text, $report);
                    $output .= html_writer::tag($tagtype, $content, $attributes) . "\n";
                    $idx++;
                }
                $output .= html_writer::end_tag('tr') . "\n";
            }
            $output .= html_writer::end_tag('thead') . "\n";

            if (empty($table->data)) {
                /*
                 * For valid XHTML strict every table must contain either a valid tr
                 * or a valid tbody... both of which must contain a valid td
                 */
                $output .= html_writer::start_tag('tbody', array('class' => 'empty'));
                $output .= html_writer::tag('tr', html_writer::tag('td', '', array('colspan' => count($table->head))));
                $output .= html_writer::end_tag('tbody');
            }
        }

        if (!empty($table->data)) {
            $oddeven    = 1;
            $keys       = array_keys($table->data);
            $lastrowkey = end($keys);
            $output .= html_writer::start_tag('tbody', array());

            foreach ($table->data as $key => $row) {
                if (($row === 'hr') && ($countcols)) {
                    $output .= html_writer::tag('td', html_writer::tag('div', '',
                                                                       array('class' => 'tabledivider')),
                                                array('colspan' => $countcols));
                } else {
                    $idx = 0;
                    // Convert array rows to html_table_rows and cell strings to html_table_cell objects!
                    if (!($row instanceof html_table_row)) {
                        $newrow = new html_table_row();

                        foreach ($row as $cell) {
                            if (!($cell instanceof html_table_cell)) {
                                $cell = new html_table_cell($cell);
                            }
                            $newrow->cells[] = $cell;
                        }
                        $row = $newrow;
                    }

                    $oddeven = $oddeven ? 0 : 1;
                    if (isset($table->rowclasses[$key])) {
                        $row->attributes['class'] .= ' ' . $table->rowclasses[$key];
                    }

                    $row->attributes['class'] .= ' r' . $oddeven;
                    if ($key == $lastrowkey) {
                        $row->attributes['class'] .= ' lastrow';
                    }

                    if ($heading->text == null) {
                        $idx++;
                        continue;
                    }

                    $output .= html_writer::start_tag('tr', array('class' => trim($row->attributes['class']),
                                                                  'style' => $row->style,
                                                                  'id' => $row->id))."\n";
                    $keys2 = array_keys($row->cells);
                    $lastkey = end($keys2);

                    $gotlastkey = false; // Flag for sanity checking!
                    foreach ($row->cells as $key => $cell) {
                        if ($gotlastkey) {
                            // This should never happen. Why do we have a cell after the last cell?
                            mtrace("A cell with key ($key) was found after the last key ($lastkey)");
                        }

                        if ($cell == null) {
                            $idx++;
                            continue;
                        }

                        if (!($cell instanceof html_table_cell)) {
                            $mycell = new html_table_cell();
                            $mycell->text = $cell;
                            $cell = $mycell;
                        }

                        if (($cell->header === true) && empty($cell->scope)) {
                            $cell->scope = 'row';
                        }

                        if (isset($table->colclasses[$key])) {
                            $cell->attributes['class'] .= ' ' . $table->colclasses[$key];
                        }

                        $cell->attributes['class'] .= ' cell c' . $idx;
                        if ($key == $lastkey) {
                            $cell->attributes['class'] .= ' lastcol';
                            $gotlastkey = true;
                        }
                        $tdstyle = '';
                        $tdstyle .= isset($table->align[$key]) ? $table->align[$key] : '';
                        $tdstyle .= isset($table->size[$key]) ? $table->size[$key] : '';
                        $tdstyle .= isset($table->wrap[$key]) ? $table->wrap[$key] : '';
                        $cell->attributes['class'] = trim($cell->attributes['class']);
                        $tdattributes = array_merge($cell->attributes,
                                                    array('style'   => $tdstyle . $cell->style,
                                                          'colspan' => $cell->colspan,
                                                          'rowspan' => $cell->rowspan,
                                                          'id'      => $cell->id,
                                                          'abbr'    => $cell->abbr,
                                                          'scope'   => $cell->scope));
                        $tagtype = 'td';
                        if ($cell->header === true) {
                            $tagtype = 'th';
                        }
                        if (isset($table->colclasses[$key])) {
                            $classes = explode(' ', $table->colclasses[$key]);
                        } else {
                            $clases = '';
                        }
                        if (!$nohide && ($report->column_is_hidden($key) || $report->column_is_hidden($classes))) {
                            $tdattributes['class'] .= ' hiddencol';
                        }
                        $content = html_writer::tag('div', $cell->text, array('class' => 'content'));
                        $output .= html_writer::tag($tagtype, $content, $tdattributes) . "\n";
                        $idx++;
                    }
                }
                $output .= html_writer::end_tag('tr') . "\n";
            }
            $output .= html_writer::end_tag('tbody') . "\n";
        }
        $output .= html_writer::end_tag('table') . "\n";

        return $output;
    }

    protected function get_toggle_links($column = '', $columnstring = '', local_checkmarkreport_base $report = null) {
        global $PAGE;
        $html = '';
        if (empty($report)) {
            return '';
        }
        $showicon = html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/switch_plus'),
                                                        'alt' => get_string('show')));
        $hideicon = html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/switch_minus'),
                                                        'alt' => get_string('hide')));
        if ($report->column_is_hidden($column)) {
            // Show link!
            $html = html_writer::link(new moodle_url($PAGE->url, array('tshow' => $column)),
                                      $showicon,
                                      array('class' => $column.' showcol',
                                            'title' => get_string('show').
                                                       ' '.clean_param($columnstring, PARAM_NOTAGS)));
        } else {
            // Hide link!
            $html = html_writer::link(new moodle_url($PAGE->url, array('thide' => $column)),
                                      $hideicon,
                                      array('class' => $column.' hidecol',
                                            'title' => get_string('hide').
                                                       ' '.clean_param($columnstring, PARAM_NOTAGS)));
        }
        return $html;
    }

}