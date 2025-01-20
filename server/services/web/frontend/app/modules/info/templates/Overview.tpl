<div class="col-sm-12">
    <img class="pull-right" src="/assets/images/honeysens-logo-small.svg" height="96" />
    <dl class="dl-horizontal label-left nooverflow">
        <dt>Plattform</dt>
        <dd>HoneySens Server</dd>
        <dt>Revision</dt>
        <dd>2.8.0</dd>
        <dt>Build-ID</dt>
        <dd><%- showBuildID() %></dd>
        <dt>Lizenz</dt>
        <dd><a href="https://www.apache.org/licenses/LICENSE-2.0.html" target="_blank">Apache 2.0 Software License</a></dd>
        <dt>Entwicklung</dt>
        <!--<dd>Pascal Br&uuml;ckner</dd>-->
        <dd>T-Systems Multimedia Solutions</dd>
        <dt>Website</dt>
        <dd><a href="https://honeysens.org/" target="_blank">honeysens.org</a></dd>
    </dl>
    <div class="well">
        Die HoneySens-Plattform ist ein auf der Idee von Honeypots basierendes Integrationswerkzeug zur Absicherung
        von IT-Landschaften, das speziell auf Angriffe aus dem Inneren eines Netzwerkes hin optimiert ist und
        Administratoren dabei unterst&uuml;tzt, die Bedrohungslage innerhalb einer komplexen Netzarchitektur in kurzer Zeit
        und mit geringem Ressourcenaufwand zu analysieren.
    </div>
    <div class="panel-group" id="infoTopics">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a class="collapsed" data-toggle="collapse" data-parent="#infoTopics" href="#documentation">Dokumentation</a>
                </h4>
            </div>
            <div id="documentation" class="panel-collapse collapse">
                <div class="panel-body">
                    <div class="panelIcon pull-left"><span class="glyphicon glyphicon-globe"></span></div>
                    <p><a href="https://honeysens.org/docs" target="_blank">Website</a>
                        <br />Up-to-date documentation on design goals, architecture, setup procedure and operation.</p>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a class="collapsed" data-toggle="collapse" data-parent="#infoTopics" href="#releasenotesServer">Release Notes: Server</a>
                </h4>
            </div>
            <div id="releasenotesServer" class="panel-collapse collapse">
                <div class="panel-body">
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.8.0</strong> - Dezember 2024<br/>
                    <p>
                    <ul>
                        <li>Backend-Migration auf PHP8</li>
                        <li>Überarbeitung aller API-Berechtigungsprüfungen</li>
                    </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.7.0</strong> - Januar 2024<br/>
                    <p>
                    <ul>
                        <li>Rudimentäres System-Last-Monitoring zur Vorbeugung von Problemen bei hoher Auslastung</li>
                        <li>Versand von E-Mail-Benachrichtigungen bei kritischer Auslastung</li>
                        <li>Gesteigerte Performance beim Abrufen von Ereignisdaten</li>
                        <li>Verschiedene Bugs im Kontext der Rechteverwaltung behoben</li>
                        <li>Probleme bei der Verlängerung von selbstsignierten TLS-Zertifikaten behoben</li>
                    </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.6.1</strong> - Mai 2023<br/>
                    <p>
                        <ul>
                            <li>Konfigurationsoption zur Limitierung von Task-Worker-Prozessen hinzugefügt</li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.6.0</strong> - Dezember 2022<br/>
                    <p>
                        <ul>
                            <li>Download der Sensorkonfiguration aus der Sensorübersicht heraus</li>
                            <li>Automatische Detektion von abgelaufenen Nutzer-Sessions oder Verlust der Verbindung zum Sever</li>
                            <li>Optionale Rechte-Einschränkungen der Manager-Rolle überarbeitet</li>
                            <li>Benutzerdetails um Gruppenzugehörigkeiten und E-Mail-Adressen ergänzt</li>
                            <li>Zahlreiche UI-Bugfixes, insbesondere bei der Darstellung von Tooltips und Dienste-Labels</li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.5.0</strong> - Mai 2022<br/>
                    <p>
                        <ul>
                            <li>Unprivilegierte Server-Container für den sicheren Betrieb als orchestrierter Microservice</li>
                            <li>TLS-Client-Authentifizierung für Sensoren entfernt und durch HMAC als Standardverfahren ersetzt</li>
                            <li>Unterstützung für TLS 1.3</li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.4.0</strong> - April 2022<br/>
                    <p>
                        <ul>
                            <li><strong>Brücken-Release</strong>: Mindestvoraussetzung für Updates auf spätere Revisionen</li>
                            <li>Ereignisarchiv für die Langzeitaufbewahrung von Ereignisdaten</li>
                            <li>Individualisierbare E-Mail-Templates für alle automatisch versandten Systemnachrichten</li>
                            <li>Ereignisfilter können nun gezielt aktiviert/deaktiviert werden</li>
                            <li>Visualisierung neu eintreffender Ereignisse mittels Zähler in der Sidebar</li>
                            <li>Status-Filter der Ereignisliste um häufig benötigte Kombinationen erweitert</li>
                            <li>Spalte mit Gruppenzuordnung zur Ereignis-, Filter- und Sensor-Übersichten hinzugefügt</li>
                            <li>Übersichts-Statusanzeige in Dienste-Verzeichnis integriert</li>
                            <li>Der zum DHCP-Server gesendete Hostname ist nun optional und frei wählbar</li>
                            <li>Dialoge überarbeitet, bspw. listet der "<em>Firmware Entfernen</em>"-Dialog jetzt betroffene Sensoren auf, die nicht den Systemstandard nutzen</li>
                            <li>E-Mail-Benachrichtigungen über Verbindungsversuch-Ereignisse beinhalten nun eine Paketübersicht</li>
                            <li>Passwortänderung bei nächstem oder erstmaligen Login erzwingbar</li>
                            <li>Administrative E-Mail-Adresse als Pflichtfeld für Neuinstallationen eingefügt</li>
                            <li>Prozess zum Einbinden eigener TLS-Zertifikate für die Kompatibilität mit alternativen Container-Runtimes überarbeitet</li>
                            <li>Fehlerkorrekturen in Front- und Backend, speziell in den Bereichen Session-Handling, Caching und serverseitive Validierung</li>
                            <li>Sensor-Authentifikation via HMAC</li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.3.0</strong> - Juni 2021<br/>
                    <p>
                        <ul>
                            <li>Serverseitiges Tracking des Sensor-Zustands, Darstellung von Up-/Downtime entsprechend angepasst</li>
                            <li>Ereignisbenachrichtigungen um Notifikationen bei Sensor-Timeouts und CA-Zertifikatsablauf erweitert</li>
                            <li>Ereignisliste um Status-Filter und Zähler für neue Ereignisse (pro Sensor) ergänzt</li>
                            <li>Funktionen zum simultanen Bearbeiten und Entfernen aller Ereignisse der Ereignisliste</li>
                            <li>Optional aktivierbares API-Aktitätslog für Administratoren</li>
                            <li>Beschreibungs-Freitextfeld für Whitelist-Einträge hinzugefügt</li>
                            <li>Sonderbrechtigungen um zusätzliche Pflichtfelder erweitert</li>
                            <li>Fehlerkorrekturen im Front- und Backend, insbesondere im Zusammenhang mit Privilegien und Filterkriterien</li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.0</strong> - August 2020<br/>
                    <p>
                        <ul>
                            <li>Umfassendes und bei Bedarf vollautomatisches Backupkonzept integriert</li>
                            <li>Unterstützung von EAPOL/IEEE802.1X-Authentifizierung für Sensoren (Beta-Status)</li>
                            <li>Unterstützung der automatischen Ereignisweiterleitung an externe Syslog-Server implementiert</li>
                            <li>Ereignisbearbeitung in separaten Dialog ausgelagert</li>
                            <li>Komponentenaufteilung überarbeitet: Datenbank, Hintergrundprozesse und Backups separiert</li>
                            <li>Zahlreiche Härtungsmaßnahmen innerhalb der Webanwendung umgesetzt</li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.1.0</strong> - August 2019<br/>
                    <p>
                        <ul>
                            <li>Ereignisse k&ouml;nnen im CSV-Format exportiert werden</li>
                            <li>Nutzerauthentifikation &uuml;ber einen externen LDAP-Verzeichnisdienst ist nun m&ouml;glich</li>
                            <li>Prozessverwaltung zur Visualisierung von Hintergrundprozessen f&uuml; Benutzer integriert</li>
                            <li>Hashverfahren f&uuml;r Nutzerpassw&ouml;rter aktualisiert</li>
                            <li>Manuelles Aktualisieren der Konfiguration nach Updates ist nicht mehr notwendig</li>
                            <li>Sidebar kann nun auf Wunsch dauerhaft ausgeklappt werden</li>
                            <li>Option zur Restriktion von Benutzerrollen hinzugef&uuml;gt</li>
                            <li>Verhalten zahlreicher Formulare im Web-Frontend vereinheitlich</li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.0.0</strong> - Mai 2019<br/>
                    <p>
                        <ul>
                            <li>Zus&auml;tzliche Such-, Filter und Sortierfunktionen f&uuml;r die Ereignis&uuml;bersicht</li>
                            <li>Interner Netzbereich f&uuml;r Honeypot-Services ist nun frei definierbar</li>
                            <li>&Uuml;berarbeitung der clientseitigen Formularvalidierung</li>
                            <li>Status von Diensten wird nun in der Sensor&uuml;bersicht dargestellt</li>
                            <li>Wartungs-Kurzdoku ist nun Teil der Server-Distribution</li>
                            <li>Firmware-Release-Notes im Frontend hinterlegt</li>
                            <li>Unz&auml;hlige Fehlerkorrekturen in Front- und Backend</li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.4</strong> - M&auml;rz 2019<br/>
                    <p>
                        <ul>
                            <li>Dienste-Refkonfiguration in der Sensor&uuml;bersicht ist jetzt global sperrbar</li>
                            <li>Fehlerkorrektur im Zusammenhang mit dem automatischen Mailversand</li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.3</strong> - Februar 2019<br/>
                    <p>Anpassung des Impressums</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.2</strong> - Januar 2019<br/>
                    <p>
                        <ul>
                            <li>Verfahren zur Verl&auml;ngerung der Zertifikatinfrastruktur hinzugef&uuml;gt</li>
                            <li>Mail-Konfiguration erlaubt nun die freie Bestimmung des zu nutzenden SMTP-Ports</li>
                            <li>Impressum und Datenschutzerkl&auml;rung eingebunden</li>
                            <li>Dokumentation aktualisiert</li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.1</strong><br/>
                    <p>
                        <ul>
                            <li>Umstellung der Zertifikate auf SHA-256</li>
                            <li>Erweiterung der Ansicht f&uuml;r Beobachter um Ereigniskommentare und Sensorkonfiguration</li>
                            <li>verschiedene Darstellungsfehler im Frontend sowie mehrere kleinere Fehler behoben</li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.0</strong><br/>
                    <p>Unterst&uuml;tzung f&uuml;r Orchestrierungsdienste, unz&auml;hlige Bugfixes und Detailverbesserungen.</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 0.9.0</strong><br/>
                    <p>Umsetzung der Multi-Plattform- und Multi-Service-Konzepte.</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 0.2.5</strong><br/>
                    <p>UI-Modul hinzugef&uuml;gt mit allgemeinen Daten &uuml;ber das HoneySens-Projekt, Dokumentation und Changelogs.
                        Au&szlig;erdem eine Reihe von Bugfixes im Frontend.</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 0.2.4</strong><br/>
                    <p>Simultanes Bearbeiten oder Entfernen mehrerer Ereignisse erm&ouml;glicht, au&szlig;erdem zahlreiche Bugfixes.</p>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a class="collapsed" data-toggle="collapse" data-parent="#infoTopics" href="#releasenotesBBB">Release Notes: Plattform BeagleBone Black</a>
                </h4>
            </div>
            <div id="releasenotesBBB" class="panel-collapse collapse">
                <div class="panel-body">
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.6.0</strong> - Mai 2023<br/>
                    <p>Base system updated</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.5.0</strong> - Mai 2022<br/>
                    <p>TLS authentication removed</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.4.0</strong> - April 2022<br/>
                    <p>HMAC authentication and custom DHCP hostname support</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.3.0</strong> - Juni 2021<br/>
                    <p>Fixed rare network address conflicts</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.2</strong> - Oktober 2020<br/>
                    <p>Networking fixes</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.1</strong> - Oktober 2020<br/>
                    <p>Enforcement of HTTP/1.1 communication</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.0</strong> - August 2020<br/>
                    <p>EAPOL support</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.0.0</strong> - Mai 2019<br/>
                    <p>Support for event caching, an adjustable service network range, new LED notification modes and USB auditing</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.5</strong><br/>
                    <p>Fixed a bug that prevented service downloads through proxies</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.4</strong> - März 2019<br/>
                    <p>Support for remote server certificate updates</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.3</strong> - Februar 2019<br/>
                    <p>Support for remote certificate updates</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.2</strong> - Januar 2019<br/>
                    <p>Static DNS servers are now properly recognized, local USB dnsmasq disabled to close port 53</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.1</strong><br/>
                    <p>NTLM proxy support via cntlm and disk usage reporting added. Requires server 1.0.0</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.0</strong><br/>
                    <p>Rebuilt image based on Debian 9 for compatibility with HoneySens Server 1.0.x</p>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a class="collapsed" data-toggle="collapse" data-parent="#infoTopics" href="#releasenotesDocker">Release Notes: Plattform Docker (x86)</a>
                </h4>
            </div>
            <div id="releasenotesDocker" class="panel-collapse collapse">
                <div class="panel-body">
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.6.0</strong> - Mai 2023<br/>
                    <p>Base system updated</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.5.0</strong> - Mai 2022<br/>
                    <p>TLS authentication removed</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.4.0</strong> - April 2022<br/>
                    <p>HMAC authentication and custom DHCP hostname support</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.3.0</strong> - Juni 2021<br/>
                    <p>Fixed rare network address conflicts</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.2</strong> - Oktober 2020<br/>
                    <p>Internal dependency update</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.1</strong> - Oktober 2020<br/>
                    <p>Enforcement of HTTP/1.1 communication</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.0</strong> - August 2020<br/>
                    <p>EAPOL support</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.0.0</strong> - Mai 2019<br/>
                    <p>Fully reworked networking and logging as well as support for event caching, an adjustable service network range and deployment/update with Docker Compose</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.1.0</strong> - März 2019<br/>
                    <p>Deployment is now done with Docker Compose, also supports custom service network ranges</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.4</strong> - März 2019<br/>
                    <p>Fixed a bug that prevented service download through proxies</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.3</strong> - Februar 2019<br/>
                    <p>Support for remote server certificate updates</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.2</strong> - Januar 2019<br/>
                    <p>Support for remote certificate updates</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.1</strong><br/>
                    <p>TLM proxy support via cntlm and disk usage reporting added. Requires server 1.0.0.</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.0</strong><br/>
                    <p>Initial release compatible with HoneySens server 1.0.x</p>
                </div>
            </div>
        </div>
    </div>
</div>
