<?php
/**
 * WetterWarnung für neuthardwetter.de by Jens Dutzi - SendToITFFF.php
 *
 * @package    blog404de\WetterWarnung
 * @author     Jens Dutzi <jens.dutzi@tf-network.de>
 * @copyright  Copyright (c) 2012-2018 Jens Dutzi (http://www.neuthardwetter.de)
 * @license    https://github.com/Blog404DE/WetterwarnungDownloader/blob/master/LICENSE.md
 * @version    3.0.0-dev
 * @link       https://github.com/Blog404DE/WetterwarnungDownloader
 */

namespace blog404de\WetterWarnung\Action;

use Exception;

/**
 * Action-Klasse für WetterWarnung Downloader zum senden eines Tweets bei einer neuen Nachricht
 *
 * @package blog404de\WetterWarnung\Action
 */
class SendToITFFF implements SendToInterface
{
    /** @var array Konfigurationsdaten für die Action */
    private $config = [];

    /**
     * Prüfe System-Vorraussetzungen
     *
     * @throws Exception
     */
    public function __construct()
    {
        try {
            // Prüfe ob libCurl vorhanden ist
            if (!extension_loaded('curl')) {
                throw new Exception(
                    "libCurl bzw. die das libCurl-PHP Modul steht nicht zur Verfügung."
                );
            }
        } catch (Exception $e) {
            // Fehler an Hauptklasse weitergeben
            throw $e;
        }
    }

    /**
     * Action Ausführung starten (Tweet versenden)
     *
     * @param array $parsedWarnInfo
     * @param bool $warnExists Wetterwarnung existiert bereits
     * @return int
     * @throws Exception
     */
    public function startAction(array $parsedWarnInfo, bool $warnExists): int
    {
        try {
            // Prüfe ob alles konfiguriert ist
            if ($this->getConfig()) {
                if (!is_array($parsedWarnInfo)) {
                    // Keine Warnwetter-Daten zum twittern vorhanden -> harter Fehler
                    throw new Exception("Die im Archiv zu speicherenden Wetter-Informationen sind ungültig");
                }

                // Status-Ausgabe der aktuellen Wetterwarnung
                echo("\t* Wetterwarnung über " . $parsedWarnInfo["event"] . " für " .
                    $parsedWarnInfo["area"] . PHP_EOL);

                if (!$warnExists) {
                    // Stelle Parameter zusammen die an IFTTT gesendet werden
                    $message = $this->composeMessage($parsedWarnInfo);
                    $jsonMessage = json_encode($message, JSON_UNESCAPED_UNICODE);
                    if ($jsonMessage === false) {
                        // Nachricht konnte nicht konvertiert werden
                        throw new Exception(
                            "Konvertieren der WetterWarnung als JSON Nachricht für IFFFT ist fehlgeschlagen"
                        );
                    }

                    // URL zusammensetzen
                    $url = "https://maker.ifttt.com/trigger/" . $this->config["eventName"] .
                           "/with/key/" . $this->config["apiKey"] . "/";

                    // Sende Nachricht an IFTTT ab
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_setopt($curl, CURLOPT_FILETIME, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_HEADER, false);
                    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
                    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

                    // POST Request
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonMessage);
                    curl_setopt(
                        $curl,
                        CURLOPT_HTTPHEADER,
                        ["Content-Type: application/json",  "Content-Length: " . strlen($jsonMessage)]
                    );

                    // NAchricht absetzen
                    echo("\t\t -> Wetter-Warnung an IFTTT Webhook API senden: ");
                    $result = curl_exec($curl);
                    if ($result === false) {
                        // Befehl wurde nicht abgesetzt
                        // Nachricht konnte nicht konvertiert werden
                        throw new Exception(
                            "Verbindung zur IFTTT Webhook API Fehlgeschlagen (" . curl_error($curl) . ")"
                        );
                    }

                    // Prüfe ob Befehl erfolgreich abgesetzt wurde
                    if (curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) {
                        throw new Exception(
                            "IFFT Webhook API Liefert ein Fehler zurück (" .
                            curl_getinfo($curl, CURLINFO_HTTP_CODE) . " / " . $result . ")"
                        );
                    }

                    echo("erfolgreich (Dauer: " . curl_getinfo($curl, CURLINFO_TOTAL_TIME) . " sek.)" . PHP_EOL);
                } else {
                    echo("\t\t-> Wetter-Warnung existierte beim letzten Durchlauf bereits" . PHP_EOL);
                }

                return 0;
            } else {
                // Konfiguration ist nicht gsetzt
                throw new Exception(
                    "Die Action-Funktion wurde nicht erfolgreich konfiguriert"
                );
            }
        } catch (Exception $e) {
            // Fehler an Hauptklasse weitergeben
            throw $e;
        }
    }

    /**
     * Nachricht zusammenstellen
     *
     * @param array $parsedWarnInfo
     * @return array
     * @throws Exception
     */
    private function composeMessage(array $parsedWarnInfo): array
    {
        try {
            // Typ der Warnung ermitteln,Leerzeichen entfernen und daraus ein Hashtag erzeugen
            $message =  $parsedWarnInfo["severity"];

            // Gebiet einfügen
            $message = $message . " des DWD für " . $parsedWarnInfo["area"];

            // Uhrzeit einfügen
            $startZeit = unserialize($parsedWarnInfo["startzeit"]);
            $endzeit = unserialize($parsedWarnInfo["endzeit"]);
            $message = $message . " (" . $startZeit->format("H:i") . " bis " . $endzeit->format("H:i") . "):";

            // Haedline hinzufügen
            $message = $message . " " . $parsedWarnInfo["headline"] . ".";

            // Prä-/Postfix anfügen
            if (!empty($this->config["MessagePrefix"]) && $this->config["MessagePrefix"] !== false) {
                $message = $this->config["MessagePrefix"] . " " . $message;
            }
            if (!empty($this->config["MessagePostfix"]) && $this->config["MessagePostfix"] !== false) {
                // Postfix erst einmal für spätere Verwendung zwischenspeichernc
                $message = $message . " " . $this->config["MessagePostfix"];
            }

            // Header zusammenstellen
            $header = $parsedWarnInfo["severity"] . " des DWD vor " . $parsedWarnInfo["event"];

            return ["value1" => $header, "value2" => $message];
        } catch (Exception $e) {
            // Fehler an Hauptklasse weitergeben
            throw $e;
        }
    }

    /**
     * Setter für Twitter OAuth Zugangsschlüssel
     *
     * @param array $config
     * @throws Exception
     */
    public function setConfig(array $config)
    {
        try {
            // Alle Paramter verfügbar?
            if (!array_key_exists("apiKey", $config)) {
                throw new Exception(
                    "Der Konfigurationsparamter [\"ActionConfig\"][\"apiKey\"] wurde nicht gesetzt."
                );
            }
            if (!preg_match('/^[\dA-Za-z]+$/m', $config["apiKey"])) {
                throw new Exception(
                    "Der Konfigurationsparamter [\"ActionConfig\"][\"apiKey\"] darf " .
                    "nur aus Buchstaben oder Zahlen bestehen."
                );
            }

            if (!array_key_exists("eventName", $config)) {
                throw new Exception(
                    "Der Konfigurationsparamter [\"ActionConfig\"][\"eventName\"] wurde nicht gesetzt."
                );
            }
            if (!preg_match('/^[\dA-Za-z_]+$/m', $config["eventName"])) {
                var_dump($config["eventName"]);
                throw new Exception(
                    "Der Konfigurationsparamter [\"ActionConfig\"][\"eventName\"] sollte nur aus " .
                    "Buchstaben, Zahlen oder einem '_' bestehen."
                );
            }

            if (!array_key_exists("MessagePrefix", $config)) {
                throw new Exception(
                    "Der Konfigurationsparamter [\"ActionConfig\"][\"MessagePrefix\"] wurde nicht gesetzt."
                );
            }
            if (!array_key_exists("MessagePostfix", $config)) {
                throw new Exception(
                    "Der Konfigurationsparamter [\"ActionConfig\"][\"MessagePostfix\"] wurde nicht gesetzt."
                );
            }

            // Werte setzen
            $this->config = $config;
        } catch (Exception $e) {
            // Fehler an Hauptklasse weitergeben
            throw $e;
        }
    }

    /**
     * Getter-Methode für das Konfigurations-Array
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
