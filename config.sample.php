<?php
/**
 * Wetterwarnung-Downloader für neuthardwetter.de by Jens Dutzi
 *
 * @package    blog404de\WetterScripts
 * @subpackage ConfigFile
 * @author     Jens Dutzi <jens.dutzi@tf-network.de>
 * @copyright  2012-2017 Jens Dutzi
 * @version    2.5.1-dev
 * @license    MIT
 *
 * Stand: 07.07.2017
 *
 * Lizenzinformationen (MIT License):
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this
 * software and associated documentation files (the "Software"), to deal in the Software
 * without restriction, including without limitation the rights to use, copy, modify,
 * merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies
 * or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/*
 *Konfiguration für die Unwetter-Informationen
 */

// FTP Zugangsdaten:
$unwetterConfig["ftp"] = [
	"host"		=>	"ftp-outgoing2.dwd.de",
	"username"	=>  "************",
	"password"	=>	"************"
];

// Für passive FTP Verbindung aktivieren (falls FTP Transfer fehlschlägt)
$unwetterConfig["ftp"]["passiv"]		= true;

// Archivierungs-FUnktion in MySQL Datenbank aktivieren
$unwetterConfig["Archive"] = false;
$unwetterConfig["MySQL"] = [
	"host"      => "",
	"username"  => "",
	"password"  => "",
	"database"  => ""
];

// Array mit den zu verarbeiteten Landkreisen
// - siehe Erklärung in: README.md
// - Beispiel: Landkreis und Stadt Karlsruhe
$unwetterConfig["WarnCellIds"]      = 908215999;

// Speicherpfad für JSON Datei mit den aktuellen Wetterwarnungen
$unwetterConfig["localJsonWarnfile"]= "/pfad/zur/wetterwarnung.json";

// Speicherordner für die Orginal Wetterwarnungen vom DWD (localDebugFolder = optionaler Sicherungsordner für Wetterwarnungen mit unbekanntem Nachrichten-Typ wie z.B. "update")
$unwetterConfig["localFolder"]		= "/pfad/zum/speicherordner/fuer/wetterWarnungen";

/* Sonstige Konfigurationsparameter für Fehler-Behandlung */

/* Konfiguration für das zusenden von E-Mails bzw. das loggen in eine Datei (optional) */

// Fehler per E-Mail melden
// $optFehlerMail		= [ "empfaenger" => "deine.email@example.org", "absender" => "deine.email@example.org" ];

// Fehler in Log-Datei schreiben
// $optFehlerLogfile	= "/pfad/zur/log/error_log";