<div class="col-sm-12">
    <img class="pull-right" src="images/honeysens-logo-small.svg" height="96" />
    <dl class="dl-horizontal label-left nooverflow">
        <dt>Plattform</dt>
        <dd>HoneySens Server</dd>
        <dt>Revision</dt>
        <dd>2.3.0</dd>
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
                    <div class="panelIcon pull-left"><span class="glyphicon glyphicon-book"></span></div>
                    <p><a href="docs/user_manual.pdf" target="_blank">Benutzerhandbuch</a>
                        <br />f&uuml;r die HoneySens Version 2.x</p>
                    <hr />
                    <div class="panelIcon pull-left"><span class="glyphicon glyphicon-book"></span></div>
                    <p><a href="docs/admin_manual.pdf" target="_blank">Administrationshandbuch</a>
                        <br />Beschreibt Installation und Updates f&uuml;r Server</p>
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
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.3.0</strong><br/>
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
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.0</strong><br/>
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
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.1.0</strong><br/>
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
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.0.0</strong><br/>
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
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.4</strong><br/>
                    <p>
                        <ul>
                            <li>Dienste-Refkonfiguration in der Sensor&uuml;bersicht ist jetzt global sperrbar</li>
                            <li>Fehlerkorrektur im Zusammenhang mit dem automatischen Mailversand</li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.3</strong><br/>
                    <p>Anpassung des Impressums</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.2</strong><br/>
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
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.3.0</strong><br/>
                    <p>Fixed rare network address conflicts</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.2</strong><br/>
                    <p>Networking fixes</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.1</strong><br/>
                    <p>Enforcement of HTTP/1.1 communication</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.0</strong><br/>
                    <p>EAPOL support</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.0.0</strong><br/>
                    <p>Support for event caching, an adjustable service network range, new LED notification modes and USB auditing</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.5</strong><br/>
                    <p>Fixed a bug that prevented service downloads through proxies</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.4</strong><br/>
                    <p>Support for remote server certificate updates</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.3</strong><br/>
                    <p>Support for remote certificate updates</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.2</strong><br/>
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
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.3.0</strong><br/>
                    <p>Fixed rare network address conflicts</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.2</strong><br/>
                    <p>Internal dependency update</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.1</strong><br/>
                    <p>Enforcement of HTTP/1.1 communication</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.2.0</strong><br/>
                    <p>EAPOL support</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 2.0.0</strong><br/>
                    <p>Fully reworked networking and logging as well as support for event caching, an adjustable service network range and deployment/update with Docker Compose</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.1.0</strong><br/>
                    <p>Deployment is now done with Docker Compose, also supports custom service network ranges</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.4</strong><br/>
                    <p>Fixed a bug that prevented service download through proxies</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.3</strong><br/>
                    <p>Support for remote server certificate updates</p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong>Revision 1.0.2</strong><br/>
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
