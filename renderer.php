<?php
// This file is made for Moodle - http://moodle.org/
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
 * Prints a list of all checkmarkreport instances in the given course (via id)
 *
 * @package       local_checkmarkreport
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2013 onwards TSC TU Vienna
 * @since         Moodle 2.5.3
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot.'/local/checkmarkreport/checkmarkreport.class.php';
require_once $CFG->dirroot.'/local/checkmarkreport/reportfilterform.class.php';

class checkmarkreport_overview extends checkmarkreport implements renderable {
    function __construct($id) {
        parent::__construct($id);
    }
}

class checkmarkreport_useroverview extends checkmarkreport implements renderable {
    function __construct() {
        parent::__construct();
    }
}

class checkmarkreport_userview extends checkmarkreport implements renderable {
    function __construct() {
        parent::__construct();
    }
}

class local_checkmarkreport_renderer extends plugin_renderer_base {
 
    protected function render_checkmarkreport_overview(checkmarkreport_overview $report) {
        $data = $report->get_coursedata();

        /*$out  = $this->output->heading(format_string($submission->title), 2);
        $out .= $this->output->container(format_string($submission->authorname), 'author');
        $out .= $this->output->container(format_text($submission->content, FORMAT_HTML), 'content');*/
        
        
        // Render the table!
        
        $out = 'is nothing';

        return $this->output->container($out, 'submission');
    }
    
    protected function render_checkmarkreport_useroverview(checkmarkreport_useroverview $report) {
        $data = $report->get_coursedata();

        /*$out  = $this->output->heading(format_string($submission->title), 2);
        $out .= $this->output->container(format_string($submission->authorname), 'author');
        $out .= $this->output->container(format_text($submission->content, FORMAT_HTML), 'content');*/
        
        
        // Render the table!
        
        $out = 'is nothing';
        return $this->output->container($out, 'submission');
    }
    
    protected function render_checkmarkreport_userview(checkmarkreport_userview $report) {
        $data = $report->get_coursedata();

        /*$out  = $this->output->heading(format_string($submission->title), 2);
        $out .= $this->output->container(format_string($submission->authorname), 'author');
        $out .= $this->output->container(format_text($submission->content, FORMAT_HTML), 'content');*/
        
        
        // Render the table!
        
        $out = 'is nothing';
        return $this->output->container($out, 'submission');
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
    protected function table(html_table $table, checkmarkreport $report = null) {

        if ($report == null) {
            $nohide = true;
        } else {
            $nohide = false;
        }
    
        // prepare table data and populate missing properties with reasonable defaults
        if (!empty($table->align)) {
            foreach ($table->align as $key => $aa) {
                if ($aa) {
                    $table->align[$key] = 'text-align:'. fix_align_rtl($aa) .';';  // Fix for RTL languages
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

        // explicitly assigned properties override those defined via $table->attributes
        $table->attributes['class'] = trim($table->attributes['class']);
        $attributes = array_merge($table->attributes, array(
                'id'            => $table->id,
                'width'         => $table->width,
                'summary'       => $table->summary,
                'cellpadding'   => $table->cellpadding,
                'cellspacing'   => $table->cellspacing,
            ));
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
            foreach($table->head as $headrow) {
                $keys = array_keys($headrow->cells);
                $lastkey = end($keys);
                $countcols = count($headrow->cells);
                $idx = 0;
                foreach ($headrow->cells as $key => $heading) {
                    // Convert plain string headings into html_table_cell objects
                    if (!($heading instanceof html_table_cell)) {
                        $headingtext = $heading;
                        $heading = new html_table_cell();
                        $heading->text = $headingtext;
                        $heading->header = true;
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
                    $classes = explode(' ', $table->colclasses[$key]);
                    if (!$nohide && ($report->column_is_hidden($key) || $report->column_is_hidden($classes))) {
                        $attributes['class'] .= ' hidden';
                    }
                    $content = html_writer::tag('div', $heading->text,
                                                array('class'=>'content')).
                               $this->get_toggle_links($key, $heading->text, $report);
                    $output .= html_writer::tag($tagtype, $content, $attributes) . "\n";
                    $idx++;
                }
                $output .= html_writer::end_tag('tr') . "\n";
            }
            $output .= html_writer::end_tag('thead') . "\n";

            if (empty($table->data)) {
                // For valid XHTML strict every table must contain either a valid tr
                // or a valid tbody... both of which must contain a valid td
                $output .= html_writer::start_tag('tbody', array('class' => 'empty'));
                $output .= html_writer::tag('tr', html_writer::tag('td', '', array('colspan'=>count($table->head))));
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
                    $output .= html_writer::tag('td', html_writer::tag('div', '', array('class' => 'tabledivider')), array('colspan' => $countcols));
                } else {
                    $idx = 0;
                    // Convert array rows to html_table_rows and cell strings to html_table_cell objects
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

                    $output .= html_writer::start_tag('tr', array('class' => trim($row->attributes['class']), 'style' => $row->style, 'id' => $row->id)) . "\n";
                    $keys2 = array_keys($row->cells);
                    $lastkey = end($keys2);

                    $gotlastkey = false; //flag for sanity checking
                    foreach ($row->cells as $key => $cell) {
                        if ($gotlastkey) {
                            //This should never happen. Why do we have a cell after the last cell?
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
                        $tdattributes = array_merge($cell->attributes, array(
                                'style' => $tdstyle . $cell->style,
                                'colspan' => $cell->colspan,
                                'rowspan' => $cell->rowspan,
                                'id' => $cell->id,
                                'abbr' => $cell->abbr,
                                'scope' => $cell->scope,
                            ));
                        $tagtype = 'td';
                        if ($cell->header === true) {
                            $tagtype = 'th';
                        }
                        $classes = explode(' ', $table->colclasses[$key]);
                        if (!$nohide && ($report->column_is_hidden($key) || $report->column_is_hidden($classes))) {
                            $tdattributes['class'] .= ' hidden';
                        }
                        $content = html_writer::tag('div', $cell->text, array('class'=>'content'));
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
    
    protected function get_toggle_links($column = '', $columnstring = '', checkmarkreport $report = null) {
        $html = '';
        if (empty($report)) {
            return '';
        }
        $url = new moodle_url('/local/checkmarkreport/index.php', array('id' => $report->get_courseid()));
        $showicon = html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/switch_plus'),
                                                        'alt' => get_string('show')));
        $hideicon = html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/switch_minus'),
                                                        'alt' => get_string('hide')));
        if ($report->column_is_hidden($column)) {
            //show link
            $html = html_writer::link(new moodle_url($url, array('tshow' => $column)),
                                      $showicon,
                                      array('class' => $column.' showcol',
                                            'title' => get_string('show').
                                                       ' '.$columnstring));
        } else {
            //hide link
            $html = html_writer::link(new moodle_url($url, array('thide' => $column)),
                                      $hideicon,
                                      array('class' => $column.' hidecol',
                                            'title' => get_string('hide').
                                                       ' '.$columnstring));
        }
        return $html;
    }

}