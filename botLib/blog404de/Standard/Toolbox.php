<?php
/**
 * Standard-Klassen für neuthardwetter.de by Jens Dutzi - Toolbox.php
 *
 * @package    blog404de\WetterWarnung
 * @author     Jens Dutzi <jens.dutzi@tf-network.de>
 * @copyright  Copyright (c) 2012-2018 Jens Dutzi (http://www.neuthardwetter.de)
 * @license    https://github.com/Blog404DE/WetterwarnungDownloader/blob/master/LICENSE.md
 * @version    v3.0.2
 * @link       https://github.com/Blog404DE/WetterwarnungDownloader
 */

namespace blog404de\Standard;

use ZipArchive;
use Exception;

/**
 * Generische Toolbox Klasse für NeuthardWetter.de-Scripte
 *
 * @package blog404de\WetterScripts
 */
class Toolbox
{
    /**
     * Methode zum extrahieren aller ZIP-Dateien die sich in einem bestimmten Ordner befinden
     *
     * @param string $source Ordner mit den beinhalteten XML Dateien
     * @param string $destination Ordner in den der Inhalt der ZIP-Dateien entpackt werden soll
     * @throws
     */
    public function extractZipFile(string $source, string $destination)
    {
        // Eigentlich darf aktuell nur eine ZIP Datei vorhanden sein (vorbereitet aber für >1 ZIP Datei)
        if (!is_readable($source)) {
            throw new \Exception("Die ZIP-Datei (" . $source. ") kann nicht lesend geöffnet werden");
        }

        // Existiert der Ordner?
        if (!is_writeable($destination)) {
            throw new Exception(
                "Der Ziel-Ordner " . $destination .
                " für die entpackten ZIP-Dateien existiert nicht oder hat keine Schreib-Rechte"
            );
        }

        // Entpacke ZIP-Datei
        $zip = new ZipArchive();
        $res = $zip->open($source, ZipArchive::CHECKCONS);
        if ($res === true) {
            echo(
                "\tEntpacke ZIP-Datei: " .
                basename($source) . " (" . $zip->numFiles . " Datei" .
                ($zip->numFiles > 1 ? "en" : "") . ")" .
                PHP_EOL
            );

            $zip->extractTo($destination);
            $zip->close();
        } else {
            throw new Exception(
                "Fehler beim öffnen der ZIP Datei '" .
                basename($source) .
                "'. Fehlercode: " . $res . " / " . $this->getZipErrorMessage($res)
            );
        }
    }

    /**
     * Creates a random unique temporary directory, with specified parameters,
     * that does not already exist (like tempnam(), but for dirs).
     *
     * Created dir will begin with the specified prefix, followed by random
     * numbers.
     *
     * @link https://php.net/manual/en/function.tempnam.php
     * @link http://stackoverflow.com/questions/1707801/making-a-temporary-dir-for-unpacking-a-zipfile-into
     *
     * @param string|null $dir directory under which to create temp dir. If null, default system temp dir will be used
     * @param string $prefix String with which to prefix created dirs.
     * @param int $mode Octal file permission mask for the newly-created dir. Should begin with a 0.
     * @param int $maxAttempts Maximum attempts before giving up (to prevent endless loops).
     * @return string|bool Full path to newly-created dir, or false on failure.
     */
    public function tempdir(string $dir = null, string $prefix = 'tmp_', int $mode = 0700, int $maxAttempts = 1000)
    {
        // Use the system temp dir by default.
        if (is_null($dir)) {
            $dir = sys_get_temp_dir();
        }

        /// Trim trailing slashes from $dir.
        $dir = rtrim($dir, '/');

        // If we don't have permission to create a directory, fail, otherwise we will be stuck in an endless loop.
        if (!is_dir($dir) || !is_writable($dir)) {
            return false;
        }

        // Make sure characters in prefix are safe.
        if (strpbrk($prefix, '\\/:*?"<>|') !== false) {
            return false;
        }

        // Tries to create a random directory until it works. Abort if we reach $maxAttempts.
        // Something screwy could be happening with the filesystem and our loop could otherwise become endless.
        $attempts = 0;
        do {
            $path = sprintf('%s/%s%s', $dir, $prefix, mt_rand(100000, mt_getrandmax()));
        } while (!mkdir($path, $mode) &&$attempts++ < $maxAttempts);

        return $path;
    }

    /**
     * Löschen des Temporär-Verzeichnisses
     *
     * @param string $dir Temporär-Verezichnis
     * @return bool
     */
    public function removeTempDir(string $dir)
    {
        // TMP Ordner löschen (sofern möglich)
        if ($dir !== false && $dir !== null) {
            // Prüfe ob Verzeichnis existiert
            if (is_dir($dir)) {
                // Lösche Inhalt des Verzeichnis und Verzeichnis selber
                array_map('unlink', glob($dir. DIRECTORY_SEPARATOR . "*.xml"));
                if (@rmdir($dir)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Methode zum generieren der Klartext-Fehlermeldung beim Zugriff auf ZIP-Dateien
     *
     * @param $errCode ZIP-Archive Fehler-Konstante
     * @return string
     */
    public function getZipErrorMessage($errCode)
    {
        $errorCodeTable = [
            ZipArchive::ER_EXISTS => "Datei existiert bereits",
            ZipArchive::ER_INCONS => "Zip-Archiv ist nicht konsistent",
            ZipArchive::ER_INVAL => "Ungültiges Argument",
            ZipArchive::ER_MEMORY => "Malloc Fehler",
            ZipArchive::ER_NOENT => "Datei nicht vorhanden",
            ZipArchive::ER_NOZIP => "Kein Zip-Archiv",
            ZipArchive::ER_OPEN => "Datei kann nicht geöffnet werden",
            ZipArchive::ER_READ => "Lesefehler",
            ZipArchive::ER_SEEK => "Seek Fehler",
        ];

        if (array_key_exists($errCode, $errorCodeTable)) {
            return $errorCodeTable[$errCode];
        } else {
            return "Unbekannter Fehler";
        }
    }

    /** Methode zum generieren von Klartext-Fehlermeldung während der JSON Kodierung
     * @param $errCode JSON-Fehlerkonstante
     * @return string
     */
    public function getJsonErrorMessage($errCode)
    {
        $errorCodeTable = [
            JSON_ERROR_NONE => "Keine Fehler",
            JSON_ERROR_DEPTH => "Maximale Stacktiefe überschritten",
            JSON_ERROR_STATE_MISMATCH => "Ungültiges oder missgestaltetes JSON",
            JSON_ERROR_CTRL_CHAR => "Steuerzeichenfehler, möglicherweise unkorrekt kodiert.",
            JSON_ERROR_SYNTAX => "Syntaxfehler, ungültiges JSON",
            JSON_ERROR_UTF8 => "Missgestaltete UTF-8 Zeichen, möglicherweise fehlerhaft kodiert",
            JSON_ERROR_RECURSION => "Eine oder mehrere rekursive Referenzen im zu kodierenden Wert",
            JSON_ERROR_INF_OR_NAN => "Eine oder mehrere NAN oder INF Werte im zu kodierenden Wert",
            JSON_ERROR_UNSUPPORTED_TYPE => "Ein Wert eines Typs, der nicht kodiert werden kann, wurde übergeben",
            JSON_ERROR_UNSUPPORTED_TYPE => "Ein Wert eines Typs, der nicht kodiert werden kann, wurde übergeben"
        ];

        if (array_key_exists($errCode, $errorCodeTable)) {
            return $errorCodeTable[$errCode];
        } else {
            return "Unbekannter Fehler";
        }
    }
}
