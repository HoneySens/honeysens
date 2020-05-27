<div class="row addForm">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-plus"></span>&nbsp;Sensor <% if(isNew()) { %>hinzuf&uuml;gen<% } else { %>bearbeiten<% } %></h1>
        <form>
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group has-feedback">
                        <label for="sensorName" class="control-label">Name</label>
                        <input pattern="^[a-zA-Z0-9._\- ]+$" data-pattern-error="Nur Gro&szlig;-, Kleinbuchstaben, Zahlen und Sonderzeichen (_, -, .) sind erlaubt" data-maxlength-error="Der Sensorname muss zwischen 1 und 50 Zeichen lang sein" maxlength="50" minlength="1" type="text" class="form-control" name="sensorName" placeholder="Sensorname" value="<%- name %>" required />
                        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                        <div class="help-block with-errors"></div>
                    </div>
                    <div class="form-group has-feedback">
                        <label for="location" class="control-label">Standort</label>
                        <input data-maxlength-error="Der Standort darf maximal 255 Zeichen lang sein" maxlength="255" minlength="1" type="text" class="form-control" name="location" placeholder="z.B. Raum 312" value="<%- location %>" required />
                        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                        <div class="help-block with-errors"></div>
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
                        <div class="form-group has-feedback">
                            <label for="updateInterval" class="control-label">Update-Interval</label>
                            <div class="input-group">
                                <span class="input-group-btn" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Zwischen systemweitem Standard und individuellem Wert umschalten">
                                    <button type="button" class="useCustomUpdateInterval btn btn-default <% if(hasCustomUpdateInterval()) { %>active<% } %>">
                                        <span class="glyphicon glyphicon-cog"></span>
                                    </button>
                                </span>
                                <input type="number" name="updateInterval" class="form-control" value="<%- getUpdateInterval() %>" min="1" max="60" data-max-error="Das Intervall muss minimal 1 und maximal 60 Minuten betragen" <% if(!hasCustomUpdateInterval()) { %>disabled<% } %>/>
                                <div class="input-group-addon">
                                    <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                                </div>
                                <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Intervall in Minuten, in dem dieser Sensor den Server kontaktiert.">
                                    <span class="glyphicon glyphicon-question-sign"></span>
                                </div>
                            </div>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group has-feedback">
                            <label for="serverHost" class="control-label">Host</label>
                            <div class="input-group">
                                <span class="input-group-btn" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Zwischen systemweitem Standard und individuellem Wert umschalten">
                                        <button type="button" class="useCustomServerEndpoint btn btn-default <% if(hasCustomServerHost()) { %>active<% } %>">
                                            <span class="glyphicon glyphicon-cog"></span>
                                        </button>
                                    </span>
                                <input type="text" name="serverHost" class="form-control" pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" data-pattern-error="Bitte geben Sie eine valide IP-Adresse ein" placeholder="IP-Adresse des Servers" value="<%- getServerHost() %>" <% if(!hasCustomServerHost()) { %>disabled<% } %>/>
                                <div class="input-group-addon">
                                    <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                                </div>
                                <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="IP-Adresse, mit welcher dieser Sensor den Server kontaktiert.">
                                    <span class="glyphicon glyphicon-question-sign"></span>
                                </div>
                            </div>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group has-feedback">
                            <label for="serverPortHTTPS" class="control-label">HTTPS-Port (API)</label>
                            <div class="input-group">
                                <span class="input-group-btn" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Zwischen systemweitem Standard und individuellem Wert umschalten">
                                        <button type="button" class="useCustomServerEndpoint btn btn-default <% if(hasCustomServerPort()) { %>active<% } %>">
                                            <span class="glyphicon glyphicon-cog"></span>
                                        </button>
                                    </span>
                                <input type="number" name="serverPortHTTPS" class="form-control" placeholder="Standard: 443" required min="1" max="65535" data-max-error="Der Port muss zwischen 1 und 65535 liegen" value="<%- getServerPortHTTPS() %>" <% if(!hasCustomServerPort()) { %>disabled<% } %>/>
                                <div class="input-group-addon">
                                    <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                                </div>
                                <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="HTTPS-Port, mit welchem dieser Sensor den Server kontaktiert.">
                                    <span class="glyphicon glyphicon-question-sign"></span>
                                </div>
                            </div>
                            <div class="help-block with-errors"></div>
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
                        <p>Spezifizieren Sie hier, ob der Sensor eine statische IP-Adresse besitzen oder diese von einem DHCP-Server beziehen soll.
                            Falls dieser Sensor <strong>virtuell</strong> (d.h. als Docker-Container) betrieben werden soll, ist an dieser Stelle <em>"Unkonfiguriert"</em> die richtige Wahl.</p>
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
                            <p class="form-control-static">Das Netzwerkinterface bleibt unkonfiguriert. Dies ist erforderlich, wenn die IP-Adresse des Sensors andersweitig verwaltet wird, beispielsweise durch das Hostsystem bei virtuellen, in Docker-Containern betriebenen Sensoren.</p>
                        </div>
                        <div class="form-group networkModeStatic has-feedback">
                            <label for="networkIP" class="control-label">IP-Adresse</label>                            
                            <input pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" data-pattern-error="Bitte geben Sie eine valide IP-Adresse ein" type="text" class="form-control" name="networkIP" placeholder="z.B. 192.168.1.13" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkModeStatic has-feedback">
                            <label for="networkNetmask" class="control-label">Subnetzmaske</label>
                            <input pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" data-pattern-error="Bitte geben Sie eine valide IP-Adresse ein" type="text" class="form-control" name="networkNetmask" placeholder="z.B. 255.255.255.0" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkModeStatic has-feedback">
                            <label for="networkGateway" class="control-label">Gateway</label>
                            <input pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" data-pattern-error="Bitte geben Sie eine valide IP-Adresse ein" type="text" class="form-control" name="networkGateway" placeholder="optional" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkModeStatic has-feedback">
                            <label for="networkDNS" class="control-label">DNS-Server</label>
                            <input pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" data-pattern-error="Bitte geben Sie eine valide IP-Adresse ein" type="text" class="form-control" name="networkDNS" placeholder="optional" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group">
                            <span class="label label-info">BETA</span>
                            <label for="networkEAPOLMode" class="control-label">EAPOL/IEEE802.1X-Authentifizierung</label>
                            <select class="form-control" name="networkEAPOLMode">
                                <option value="0">Deaktiviert</option>
                                <option value="1">MD5</option>
                                <option value="2">TLS</option>
                                <option value="3">PEAP</option>
                                <option value="4">TTLS</option>
                            </select>
                        </div>
                        <div class="form-group networkEAPOLIdentity has-feedback">
                            <label for="networkEAPOLIdentity" class="control-label">Identit&auml;t</label>
                            <input type="text" class="form-control" name="networkEAPOLIdentity" minlength="1" maxlength="512" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkEAPOLPassword has-feedback">
                            <label for="networkEAPOLPassword" class="control-label">Passwort</label>
                            <input type="password" class="form-control" name="networkEAPOLPassword" minlength="1" maxlength="512" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkEAPOLAnonIdentity has-feedback">
                            <label for="networkEAPOLAnonIdentity" class="control-label">Anonyme Identit&auml;t</label>
                            <input type="text" class="form-control" name="networkEAPOLAnonIdentity" minlength="1" maxlength="512" placeholder="optional" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkEAPOLCA has-feedback">
                            <label for="networkEAPOLCA" class="control-label">CA-Zertifikat (optional)</label>
                            <input type="file" class="hide" />
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <button class="removeUpload btn btn-default" type="button">
                                        <span class="glyphicon glyphicon-remove"></span>
                                    </button>
                                </span>
                                <input type="text" class="form-control uploadMetadata" name="networkEAPOLCA" pattern="^.+ \(\d+ Bytes\)$|^[0-9A-Fa-f]+$" disabled />
                                <span class="input-group-btn">
                                    <button type="button" class="upload btn btn-default">Upload...</button>
                                </span>
                            </div>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkEAPOLClientCert has-feedback">
                            <label for="networkEAPOLClientCert" class="control-label">Client-Zertifikat</label>
                            <input type="file" class="hide" />
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <button class="removeUpload btn btn-default" type="button">
                                        <span class="glyphicon glyphicon-remove"></span>
                                    </button>
                                </span>
                                <input type="text" class="form-control uploadMetadata" name="networkEAPOLClientCert" pattern="^.+ \(\d+ Bytes\)$|^[0-9A-Fa-f]+$" disabled />
                                <span class="input-group-btn">
                                    <button type="button" class="upload btn btn-default">Upload...</button>
                                </span>
                            </div>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkEAPOLClientKey has-feedback">
                            <label for="networkEAPOLClientKey" class="control-label">Client-Schl&uuml;ssel</label>
                            <input type="file" class="hidden" />
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <button class="removeUpload btn btn-default" type="button">
                                        <span class="glyphicon glyphicon-remove"></span>
                                    </button>
                                </span>
                                <input type="text" class="form-control uploadMetadata" name="networkEAPOLClientKey" pattern="^.+ \(\d+ Bytes\)$|^[0-9A-Fa-f]+$" disabled />
                                <span class="input-group-btn">
                                    <button type="button" class="upload btn btn-default">Upload...</button>
                                </span>
                            </div>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkEAPOLClientPassphrase has-feedback">
                            <label for="networkEAPOLClientPassphrase" class="control-label">Passphrase des privaten Schl&uuml;sels</label>
                            <input type="text" class="form-control" name="networkEAPOLClientPassphrase" minlength="1" maxlength="512" placeholder="optional" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
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
                        <div class="form-group networkMACCustom has-feedback">
                            <label for="customMAC" class="control-label">Individuelle MAC-Adresse</label>
                            <input pattern="^(([A-Fa-f0-9]{2}[:]){5}[A-Fa-f0-9]{2}[,]?)+$" data-pattern-error="Bitte geben Sie eine valide MAC-Adresse ein" type="text" class="form-control" name="customMAC" placeholder="z.B. 00:11:22:33:44:55" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkServiceNetwork has-feedback">
                            <label for="serviceNetwork" class="control-label">Interner Netzbereich f&uuml;r Honeypot-Services</label>
                            <div class="input-group">
                                <span class="input-group-btn" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Zwischen systemweitem Standard und individuellem Wert umschalten">
                                    <button type="button" class="useCustomServiceNetwork btn btn-default <% if(hasCustomServiceNetwork()) { %>active<% } %>">
                                        <span class="glyphicon glyphicon-cog"></span>
                                    </button>
                                </span>
                                <input pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(?:30|2[0-9]|1[0-9]|[1-9])$" data-pattern-error="Netzbereich als IP-Adresse mit Netzmaske (z.B. 192.168.1.0/24) spezifizieren" type="text" class="form-control" name="serviceNetwork" value="<%- getServiceNetwork() %>" <% if(!hasCustomServiceNetwork()) { %>disabled<% } %> />
                                <div class="input-group-addon">
                                    <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                                </div>
                                <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Spezifiziert den IP-Adressbereich, den Sensordienste zur internen Adressierung nutzen. Falls dieser mit lokalen Adressbereichen im Konflikt steht, ist hier ein freier und ungenutzter Adessraum anzugeben.">
                                    <span class="glyphicon glyphicon-question-sign"></span>
                                </div>
                            </div>
                            <div class="help-block with-errors"></div>
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
                        <div class="form-group proxyTypeEnabled has-feedback">
                            <label for="proxyHost" class="control-label">Proxy-Server</label>
                            <input type="text" class="form-control" name="proxyHost" placeholder="z.B. 10.0.0.3" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group proxyTypeEnabled has-feedback">
                            <label for="proxyPort" class="control-label">Port</label>
                            <input type="number" name="proxyPort" class="form-control" placeholder="z.B. 3128" min="0" max="65535" data-max-error="Der Port muss zwischen 0 und 65535 liegen"/>
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group proxyTypeEnabled">
                            <label for="proxyUser" class="control-label">Benutzer</label>
                            <input type="text" name="proxyUser" class="form-control" placeholder="optional" />
                        </div>
                        <div class="form-group proxyTypeEnabled">
                            <label for="proxyPassword" class="control-label">Passwort</label>
                            <input type="password" name="proxyPassword" class="form-control" placeholder="optional" autocomplete="new-password" />
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
                <h5 class="text-center hide"><strong>Bitte warten, Konfiguration wird erzeugt...</strong></h5>
                <div class="alert alert-danger hide">Es ist ein Fehler beim Erzeugen der Sensorkonfiguration aufgetreten.</div>
                <button type="button" class="btn btn-primary btn-block reqConfig"><span class="glyphicon glyphicon-download"></span>&nbsp;&nbsp;Sensor-Konfiguration</button>
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
                            <a class="btn btn-primary btn-block" href="#"><span class="glyphicon glyphicon-download"></span>&nbsp;&nbsp;Download Firmware</a>
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
                            <a class="btn btn-primary btn-block" href="#"><span class="glyphicon glyphicon-download"></span>&nbsp;&nbsp;Download Docker-Image</a>
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
