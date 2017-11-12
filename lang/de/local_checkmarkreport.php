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
 * Strings for component 'local_checkmarkreport', language 'en'
 *
 * @package   local_checkmarkreport
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$string['checkmarkreport:view'] = 'Zeige Kreuzerlübungsübersicht';
$string['checkmarkreport:view_courseoverview'] = 'Zeige Kreuzerlübung Kursübersicht';
$string['checkmarkreport:view_students_overview'] = 'Zeige Kreuzerlübung Teilnehmer/innenübersicht';
$string['checkmarkreport:view_own_overview'] = 'Zeige Kreuzerlübungsbericht';

$string['pluginname'] = 'Kreuzerlübung Bericht';
$string['pluginname_help'] = 'Kreuzerlübung Bericht erweitert die Funktionalität der Kreuzerlübung (mod_checkmark), indem es praktische Übersichten über alle Kreuzerlübungen für einen Kurs anbietet.';
$string['pluginnameplural'] = 'Kreuzerlübung Berichte';

$string['additional_information'] = 'Zusätzliche Informationen';
$string['additional_columns'] = 'Zusätzliche Spalten';
$string['additional_settings'] = 'Zusätzliche Einstellungen';
$string['attendances'] = 'Anwesenheiten';
$string['by'] = 'durch';
$string['error_retriefing_members'] = 'Fehler beim Laden der Gruppenmitglieder';
$string['eventexported'] = 'Kreuzerlübungsbericht exportiert';
$string['eventoverviewexported'] = 'Kreuzerlübungsbericht Übersicht exportiert';
$string['eventoverviewviewed'] = 'Kreuzerlübungsbericht Übersicht angezeigt';
$string['eventuseroverviewexported'] = 'Kreuzerlübungsbericht Teilnehmer/innenübersicht exportiert';
$string['eventuseroverviewviewed'] = 'Kreuzerlübungsbericht Teilnehmer/innenübersicht angezeigt';
$string['eventuserviewexported'] = 'Kreuzerlübungsbericht Teilnehmer/innenansicht exportiert';
$string['eventuserviewviewed'] = 'Kreuzerlübungsbericht Teilnehmer/innenansicht angezeigt';
$string['eventviewed'] = 'Kreuzerlübungsbericht angezeigt';
$string['examples'] = 'Beispiele';
$string['example'] = 'Beispiel';
$string['exportas'] = 'Exportiere als';
$string['filter'] = 'Filter';
$string['grade'] = 'Bewertung';
$string['grade_help'] = 'Zeigt die Bewertungen und deren Summe der angezeigten Kreuzerlübungen sowie die theoretisch für das Kreuzen der Beispiele vergebbaren Punkte.';
$string['grade_useroverview'] = 'Bewertung';
$string['grade_useroverview_help'] = 'Die Bewertung der Kreuze spiegelt den aktuellen Stand der Bewertung durch die Lehrenden in den einzelnen Kreuzerlübungen wider.<br />Die bei den einzelnen Kreuzen angezeigten Werte entsprechen nur der theoretisch möglichen Bewertung durch das Kreuzen des jeweiligen Beispiels.<br />Die Gesamte Bewertung der Kreuzerlübung sowie die Kurssumme kann davon abweichen, wenn Lehrende andere Bewertungen vergeben (z.B. weil das Beispiel nicht korrekt vorgezeigt wurde, oder in der Einheit noch Beispiele bei den Lehrenden an-/abgekreuzt wurden).<br />Die Summe der Kreuzerlübung (Zeile "S Kreuzerlübungsname") zeigt die Bewertungen der Lehrenden. Ein "-" dabei signalisiert, dass keine Bewertungen der Kreuze vorliegen.<br />Die Summe aller Kreuzerlübungen (Zeile S Insgesamt) summiert alle vorliegenden Bewertungen der Lehrenden über die angezeigten Kreuzerlübungen hinweg auf.<br />Beachten Sie, dass sich dieser Wert je nach Bewertungs- bzw. Bearbeitungsstand der Lehrenden aktualisiert.';
$string['groups'] = 'Gruppen';
$string['groups_help'] = 'Selektieren Sie die anzuzeigenden Gruppen. Gruppen ohne Mitglieder werden ausgegraut dargestellt und können nicht ausgewählt werden.';
$string['groupings'] = 'Gruppierungen';
$string['loading'] = 'Lade...';
$string['noaccess'] = 'Sie haben keinen Zugriff auf dieses Modul. Sie haben nicht die benötigten Berechtigungen, um diesen Inhalt zu sehen.';
$string['overview'] = 'Überblick';
$string['overview_alt'] = 'Zeige Kreuzerlübungs Kursreport';
$string['overwritten'] = 'Überschrieben';
$string['showattendances'] = 'Zeige Anwesenheit';
$string['showattendances_help'] = 'Wenn aktiviert, und zumindest 1 Kreuzerlübungsinstanz die Anwesenheiten aufzeichnet, werden die Anwesenheiten der Teilnehmer/innen in den Berichten angezeigt. Wenn einzelne Kreuzerlübungsinstanzen die Anwesenheit nicht aufzeichnen, wird dort keine Information angezeigt!';
$string['showexamples'] = 'Zeige Beispiele';
$string['showexamples_help'] = 'Wenn aktiviert werden die Beispiele der einzelnen Kreuzerlübungsinstanzen mit Detailinformationen in den Berichten inkludiert.';
$string['showgrade'] = 'Zeige Bewertung';
$string['showpoints'] = 'Zeige Punkte';
$string['showpoints_help'] = 'Zeige die entsprechenden Punkte für jedes Beispiel anstelle der gekreuzt (☒) oder nicht gekreuzt (☐) Symbole.';
$string['showpresentationcount'] = 'Zeige Anzahl von bewerteten Tafelleistungen';
$string['showpresentationcount_help'] = 'Wenn aktiviert, zeigt die Spalte "# Tafelleistungen" für alle Kursteilnehmer/innen die Anzahl der eingetragenen Bewertungen von Tafelleistungen - über alle Kreuzerlübungen des Kurses hinweg - an. Dabei werden leere Bewertungen ignoriert und nicht gezählt.';
$string['showpresentationgrades'] = 'Zeige Tafelleistung';
$string['showpresentationgrades_help'] = 'Zeige die Bewertungen der Tafelleistungen der Teilnehmer/innen';
$string['showsignature'] = 'inkludiere Unterschriftenfelder in XLSX und ODS Dateien';
$string['showsignature_help'] = 'Wenn aktiviert, werden in XLSX und ODS Exporten Bereiche reserviert, um Teilnehmer/innen Platz für Unterschriften zu sichern. Dies ist für TXT und XML Exporte nicht möglich.';
$string['signature'] = 'Unterschrift';
$string['sumabs'] = 'Zeige x/y Beispielen';
$string['sumabs_help'] = 'Zeige wie viele Beispiele im Kurs gesamt bzw. in jeder Kreuzerlübung gekreuzt wurden.';
$string['sumrel'] = 'Zeige % von Beispielen/Bewertung';
$string['sumrel_help'] = 'Zeige relative Werte ( XX % ) der gekreuzten Beispiele bzw. der daraus berechneten Noten sowohl für den gesamten Kurs als auch für jede Kreuzerlübung.';
$string['status'] = 'Status';
$string['update'] = 'Aktualisieren';
$string['useroverview'] = 'Teilnehmer/innenübersicht';
$string['useroverview_alt'] = 'Zeige Teilnehmer/innenübersicht';
$string['userview'] = 'Kreuzerlübungsübersicht';
$string['userview_alt'] = 'Zeige Kreuzerlübungsübersicht';

// Deprecated since Moodle 2.8!
$string['xlsover256'] = 'Das XLS Dateiformat unterstützt nur maximal 256 Spalten. Die zu erstellende Datei überschreitet diese Grenze. Bitte wählen sie nur bestimmte Instanzen aus, oder vermeiden sie das XLS Dateiformat.';
