<div class="row addForm">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-plus"></span>&nbsp;Sensor <% if(isNew()) { %>hinzuf&uuml;gen<% } else { %>bearbeiten<% } %></h1>
        <form>
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="sensorName" class="control-label">Name</label>
                        <input type="text" name="sensorName" class="form-control" placeholder="Sensorname" value="<%- name %>" />
                    </div>
                    <div class="form-group">
                        <label for="location" class="control-label">Standort</label>
                        <input type="text" name="location" class="form-control" placeholder="z.B. Raum 312" value="<%- location %>" />
                    </div>
                    <div class="form-group">
                        <label for="division" class="control-label">Gruppe</label>
                        <select class="form-control" name="division">
                            <% _(divisions).each(function(d) { %>
                                <option value="<%- d.id %>"><%- d.name %></option>
                            <% }); %>
                        </select>
                    </div>
                    <fieldset>
                        <legend>Erreichbarkeit HoneySens-Server</legend>
                        <div class="form-group">
                            <label for="updateInterval" class="control-label">Update-Interval (frei lassen f&uuml;r systemweiten Standardwert)</label>
                            <input type="text" name="updateInterval" class="form-control" value="<%- update_interval %>" />
                        </div>
                        <div class="form-group">
                            <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-default">
                                    <input type="radio" name="serverEndpoint" value="0">Standard</input>
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="serverEndpoint" value="1">Individuell</input>
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="serverHost" class="control-label">Host</label>
                            <input type="text" name="serverHost" class="form-control" placeholder="IP-Adresse des Servers" />
                        </div>
                        <div class="form-group">
                            <label for="serverPortHTTPS" class="control-label">HTTPS-Port (API)</label>
                            <input type="text" name="serverPortHTTPS" class="form-control" placeholder="Standard: 443" />
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend>Firmware</legend>
                        <div class="form-group">
                            <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-default <% if(!firmwareExists()) { %>disabled<% } %>">
                                    <input type="radio" name="firmwarePreference" value="0">Standard</label>
                                </label>
                                <label class="btn btn-default <% if(!firmwareExists()) { %>disabled<% } %>">
                                    <input type="radio" name="firmwarePreference" value="1">Spezifische Revision</label>
                                </label>
                            </div>
                        </div>
                        <div class="form-group firmwarePreferenceDisabled">
                            <p class="form-control-static">Der Sensor nutzt die plattformabhängige Standardfirmware.</p>
                            <% if(!firmwareExists()) { %>
                            <p class="form-control-static firmwareMissing"><strong>Achtung: Es ist derzeit keine Firmware registriert!</strong></p>
                            <% } %>
                        </div>
                        <div class="form-group firmwarePreferenceEnabled">
                            <label for="firmwarePlatform" class="control-label">Plattform</label>
                            <select class="form-control" name="firmwarePlatform">
                                <% _(platforms).each(function(p) { %>
                                    <option value="<%- p.id %>"><%- p.title %></option>
                                <% }); %>
                            </select>
                        </div>
                        <div class="form-group firmwarePreferenceEnabled">
                            <label for="firmwareRevision" class="control-label">Revision</label>
                            <select class="form-control" name="firmwareRevision"></select>
                        </div>
                    </fieldset>
                </div>
                <div class="col-sm-6">
                    <fieldset>
                        <legend>Netzwerkschnittstelle</legend>
                        <div class="form-group">
                            <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-default">
                                    <input type="radio" name="networkMode" value="0">DHCP</input>
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="networkMode" value="1">Statisch</input>
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="networkMode" value="2">Unkonfiguriert</input>
                                </label>
                            </div>
                        </div>
                        <div class="form-group networkModeDHCP">
                            <p class="form-control-static">IP-Adresse und Subnetzmaske werden automatisch vom DHCP-Server bezogen.</p>
                        </div>
                        <div class="form-group networkModeNone">
                            <p class="form-control-static">Das Netzwerkinterface bleibt unkonfiguriert. Dies ist erforderlich, wenn virtuelle Sensoren manuell verwaltet werden sollen.</p>
                        </div>
                        <div class="form-group networkModeStatic">
                            <label for="networkIP" class="control-label">IP-Adresse</label>
                            <input type="text" name="networkIP" class="form-control" placeholder="z.B. 192.168.1.13" />
                        </div>
                        <div class="form-group networkModeStatic">
                            <label for="networkNetmask" class="control-label">Subnetzmaske</label>
                            <input type="text" name="networkNetmask" class="form-control" placeholder="z.B. 255.255.255.0" />
                        </div>
                        <div class="form-group networkModeStatic">
                            <label for="networkGateway" class="control-label">Gateway</label>
                            <input type="text" name="networkGateway" class="form-control" placeholder="optional" />
                        </div>
                        <div class="form-group networkModeStatic">
                            <label for="networkDNS" class="control-label">DNS-Server</label>
                            <input type="text" name="networkDNS" class="form-control" placeholder="optional" />
                        </div>
                        <div class="form-group">
                            <label for="networkMACMode" class="control-label">MAC-Adresse</label>
                            <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-default">
                                    <input type="radio" name="networkMACMode" value="0">Standard</input>
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="networkMACMode" value="1">Individuell</input>
                                </label>
                            </div>
                        </div>
                        <div class="form-group networkMACOriginal">
                            <p class="form-control-static">Es wird die originale MAC-Adresse des verbauten Netzwerkinterfaces genutzt.</p>
                        </div>
                        <div class="form-group networkMACCustom">
                            <label for="customMAC" class="control-label">Individuelle MAC-Adresse</label>
                            <input type="text" name="customMAC" class="form-control" placeholder="00:11:22:33:44:55" />
                        </div>
                        <div class="form-group networkServiceNetwork">
                            <label for="serviceNetwork" class="control-label">Interner Netzbereich f&uuml;r Honeypot-Services (frei lassen f&uuml;r systemweiten Standardwert)</label>
                            <div class="input-group">
                                <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Spezifiziert den IP-Adressbereich, den Sensordienste zur internen Adressierung nutzen. Falls dieser mit lokalen Adressbereichen im Konflikt steht, ist hier ein freier und ungenutzter Adessraum anzugeben. ">
                                    <span class="glyphicon glyphicon-question-sign"></span>
                                </div>
                                <input type="text" name="serviceNetwork" class="form-control" value="<%- service_network %>" />
                            </div>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend>HTTP(S)-Proxy</legend>
                        <div class="form-group">
                            <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-default">
                                    <input type="radio" name="proxyType" value="0">Inaktiv</input>
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="proxyType" value="1">Aktiv</input>
                                </label>
                            </div>
                        </div>
                        <div class="form-group proxyTypeDisabled">
                            <p class="form-control-static">Es kommt kein Proxy-Server zum Einsatz.</p>
                        </div>
                        <div class="form-group proxyTypeEnabled">
                            <label for="proxyHost" class="control-label">Proxy-Server</label>
                            <input type="text" name="proxyHost" class="form-control" placeholder="z.B. 10.0.0.3" />
                        </div>
                        <div class="form-group proxyTypeEnabled">
                            <label for="proxyPort" class="control-label">Port</label>
                            <input type="text" name="proxyPort" class="form-control" placeholder="z.B. 3128" />
                        </div>
                        <div class="form-group proxyTypeEnabled">
                            <label for="proxyUser" class="control-label">Benutzer</label>
                            <input type="text" name="proxyUser" class="form-control" placeholder="optional" />
                        </div>
                        <div class="form-group proxyTypeEnabled">
                            <label for="proxyPassword" class="control-label">Passwort</label>
                            <input type="text" name="proxyPassword" class="form-control" placeholder="optional" />
                        </div>
                    </fieldset>
                </div>
            </div>
            <hr />
            <div class="form-group">
                <div class="btn-group btn-group-justified">
                    <div class="btn-group">
                        <button type="button" class="cancel btn btn-default">&nbsp;&nbsp;Abbrechen</button>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="row addBusy hide">
    <div class="col-sm-12">
        <p class="text-center">Daten werden verarbeitet</p>
        <div class="loading center-block"></div>
    </div>
</div>
<div class="row addResult hide">
    <div class="col-sm-12">
        <div class="resultSuccess">
            <div class="alert alert-success">Der neue Sensor wurde erfolgreich auf dem Server registriert.</div>
            <p>Bitte laden Sie die Sensor-Konfiguration mit einem Klick auf den nachfolgenden Button herunter. Sie wird für die Einrichtung eines
                neuen Sensors unabhängig von der verwendeten Plattform zwingend benötigt.</p>
            <div class="configArchive">
                <h5 class="text-center"><strong>Bitte warten, Konfiguration wird erzeugt...</strong></h5>
                <a class="btn btn-primary btn-block hide"><span class="glyphicon glyphicon-download"></span>&nbsp;&nbsp;Sensor-Konfiguration</a>
            </div>
            <hr />
            <% if(firmwareExists()) { %>
            <p>Die weiteren Schritte für die Inbetriebnahme des Sensors hängen davon ab, auf welcher Plattform dieser zum Einsatz kommen soll.
                Für eine kurze Zusammenfassung der erforderlichen Schritte, klicken Sie bitte auf die nachfolgenden Links.</p>
            <div class="panel-group" id="instructions">
                <% if(firmwareExists(1)) { %>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a class="collapsed" data-toggle="collapse" data-parent="#instructions" href="#instBBB">BeagleBone Black</a>
                        </h4>
                    </div>
                    <div id="instBBB" class="panel-collapse collapse">
                        <div class="panel-body">
                            <p>Laden Sie nun im nächsten Schritt die Sensor-Firmware und die individuelle Sensor-Konfiguration herunter.
                            Schreiben Sie anschließend die Firmware auf eine SD-Karte und kopieren das Konfigurationsarchiv auf deren
                            erste Partition. Schließen Sie zuletzt den Sensor mit eingesteckter SD-Karte an das Netzwerk an.</p>
                            <a class="btn btn-primary btn-block" href="api/platforms/1/firmware/current"><span class="glyphicon glyphicon-download"></span>&nbsp;&nbsp;Download Firmware</a>
                        </div>
                    </div>
                </div>
                <% } %>
                <% if(firmwareExists(2)) { %>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a class="collapsed" data-toggle="collapse" data-parent="#instructions" href="#instDocker">Docker (x86)</a>
                        </h4>
                    </div>
                    <div id="instDocker" class="panel-collapse collapse">
                        <div class="panel-body">
                            <p>Für die Einrichtung eines virtuellen Sensors auf Basis von Docker kann an dieser Stelle ein Archiv
                                heruntergeladen werden, das sowohl das aktuelle Image als auch ein zugehöriges Startskript beinhaltet.</p>
                            <a class="btn btn-primary btn-block" href="api/platforms/2/firmware/current"><span class="glyphicon glyphicon-download"></span>&nbsp;&nbsp;Download Docker-Image</a>
                        </div>
                    </div>
                </div>
                <% } %>
            </div>
            <% } %>
        </div>
        <div class="resultError hide">
            <div class="alert alert-danger">Es ist ein Fehler aufgetreten.</div>
        </div>
        <hr />
        <button type="button" class="cancel btn btn-default btn-block">Schlie&szlig;en</button>
    </div>
</div>
