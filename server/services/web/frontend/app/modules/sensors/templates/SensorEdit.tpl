<div class="row addForm">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-plus"></span>&nbsp;<% if(isNew()) { %><%= _.t("sensors:sensorAddHeader") %><% } else { %><%= _.t("sensors:sensorUpdateHeader") %><% } %></h1>
        <form>
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group has-feedback">
                        <label for="sensorName" class="control-label"><%= _.t("name") %></label>
                        <input pattern="^[a-zA-Z0-9._\- ]+$" data-pattern-error="<%= _.t('nameAltValidationError') %>" data-maxlength-error="<%= _.t('lengthValidationError', {min: 1, max: 50}) %>" maxlength="50" minlength="1" type="text" class="form-control" name="sensorName" placeholder="<%= _.t('sensors:sensorNamePlaceholder') %>" value="<%- name %>" required autofocus />
                        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                        <div class="help-block with-errors"></div>
                    </div>
                    <div class="form-group has-feedback">
                        <label for="location" class="control-label"><%= _.t("sensors:sensorLocation") %></label>
                        <input data-maxlength-error="<%= _.t('lengthValidationError', {min: 1, max: 255}) %>" maxlength="255" minlength="1" type="text" class="form-control" name="location" placeholder="<%= _.t('sensors:sensorLocationPlaceholder') %>" value="<%- location %>" required />
                        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                        <div class="help-block with-errors"></div>
                    </div>
                    <div class="form-group">
                        <label for="division" class="control-label"><%= _.t("division") %></label>
                        <select class="form-control" name="division">
                            <% _(divisions).each(function(d) { %>
                                <option value="<%- d.id %>"><%- d.name %></option>
                            <% }); %>
                        </select>
                    </div>
                    <fieldset>
                        <legend><%= _.t("sensors:sensorServerConnection") %></legend>
                        <div class="form-group has-feedback">
                            <label for="updateInterval" class="control-label"><%= _.t("sensors:sensorUpdateInterval") %></label>
                            <div class="input-group">
                                <span class="input-group-btn" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('sensors:sensorToggleSystemCustom') %>">
                                    <button type="button" class="useCustomUpdateInterval btn btn-default <% if(hasCustomUpdateInterval()) { %>active<% } %>">
                                        <span class="glyphicon glyphicon-cog"></span>
                                    </button>
                                </span>
                                <input type="number" name="updateInterval" class="form-control" value="<%- getUpdateInterval() %>" min="1" max="60" data-max-error="<%= _.t('lengthValidationError', {min: 1, max: 60}) %>" <% if(!hasCustomUpdateInterval()) { %>disabled<% } %>/>
                                <div class="input-group-addon">
                                    <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                                </div>
                                <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('sensors:sensorUpdateIntervalInfo') %>">
                                    <span class="glyphicon glyphicon-question-sign"></span>
                                </div>
                            </div>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group has-feedback">
                            <label for="serverHost" class="control-label"><%= _.t("host") %></label>
                            <div class="input-group">
                                <span class="input-group-btn" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('sensors:sensorToggleSystemCustom') %>">
                                        <button type="button" class="useCustomServerEndpoint btn btn-default <% if(hasCustomServerHost()) { %>active<% } %>">
                                            <span class="glyphicon glyphicon-cog"></span>
                                        </button>
                                    </span>
                                <input type="text" name="serverHost" class="form-control" pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" data-pattern-error="<%= _.t('ipAddrValidationError') %>" placeholder="<%= _.t('sensors:sensorServerHostPlaceholder') %>" value="<%- getServerHost() %>" <% if(!hasCustomServerHost()) { %>disabled<% } %>/>
                                <div class="input-group-addon">
                                    <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                                </div>
                                <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('sensors:sensorServerHostInfo')%>">
                                    <span class="glyphicon glyphicon-question-sign"></span>
                                </div>
                            </div>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group has-feedback">
                            <label for="serverPortHTTPS" class="control-label"><%= _.t("sensors:sensorServerPort") %></label>
                            <div class="input-group">
                                <span class="input-group-btn" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('sensors:sensorToggleSystemCustom') %>">
                                        <button type="button" class="useCustomServerEndpoint btn btn-default <% if(hasCustomServerPort()) { %>active<% } %>">
                                            <span class="glyphicon glyphicon-cog"></span>
                                        </button>
                                    </span>
                                <input type="number" name="serverPortHTTPS" class="form-control" placeholder="<%= _.t('sensors:sensorServerPortPlaceholder') %>" required min="1" max="65535" data-max-error="<%= _.t('lengthValidationError', {min: 1, max: 65535}) %>" value="<%- getServerPortHTTPS() %>" <% if(!hasCustomServerPort()) { %>disabled<% } %>/>
                                <div class="input-group-addon">
                                    <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                                </div>
                                <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('sensors:sensorServerPortInfo') %>">
                                    <span class="glyphicon glyphicon-question-sign"></span>
                                </div>
                            </div>
                            <div class="help-block with-errors"></div>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend><%= _.t("sensors:sensorFirmwareHeader") %></legend>
                        <div class="form-group">
                            <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-default <% if(!firmwareExists()) { %>disabled<% } %>">
                                    <input type="radio" name="firmwarePreference" value="0"><%= _.t('sensors:sensorFirmwareStandard') %></label>
                                </label>
                                <label class="btn btn-default <% if(!firmwareExists()) { %>disabled<% } %>">
                                    <input type="radio" name="firmwarePreference" value="1"><%= _.t('sensors:sensorFirmwareSpecificRevision') %></label>
                                </label>
                            </div>
                        </div>
                        <div class="form-group firmwarePreferenceDisabled">
                            <p class="form-control-static"><%= _.t('sensors:sensorFirmwareStandardInfo') %></p>
                            <% if(!firmwareExists()) { %>
                            <p class="form-control-static firmwareMissing"><strong><%= _.t("sensors:sensorFirmwareWarning") %></strong></p>
                            <% } %>
                        </div>
                        <div class="form-group firmwarePreferenceEnabled">
                            <label for="firmwarePlatform" class="control-label"><%= _.t("sensors:sensorFirmwarePlatform") %></label>
                            <select class="form-control" name="firmwarePlatform">
                                <% _(platforms).each(function(p) { %>
                                    <option value="<%- p.id %>"><%- p.title %></option>
                                <% }); %>
                            </select>
                        </div>
                        <div class="form-group firmwarePreferenceEnabled">
                            <label for="firmwareRevision" class="control-label"><%= _.t("sensors:sensorFirmwareRevision") %></label>
                            <select class="form-control" name="firmwareRevision"></select>
                        </div>
                    </fieldset>
                </div>
                <div class="col-sm-6">
                    <fieldset>
                        <legend><%= _.t("sensors:sensorNetworkHeader") %></legend>
                        <p><%= _.t("sensors:sensorNetworkAddressPrompt") %></p>
                        <div class="form-group">
                            <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-default">
                                    <input type="radio" name="networkMode" value="0"><%= _.t("sensors:sensorNetworkAddressDHCP") %></input>
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="networkMode" value="1"><%= _.t("sensors:sensorNetworkAddressStatic") %></input>
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="networkMode" value="2"><%= _.t("sensors:sensorNetworkAddressUnconfigured") %></input>
                                </label>
                            </div>
                        </div>
                        <div class="form-group networkModeDHCP has-feedback">
                            <label for="networkDHCPHostname" class="control-label"><%= _.t("sensors:sensorNetworkHostname") %></label>
                            <div class="input-group">
                                <input pattern="^[a-z0-9.\-]+$" data-pattern-error="<%= _.t('nameAlt2ValidationError') %>" data-maxlength-error="<%= _.t('lengthValidationError', {min: 1, max: 253}) %>" maxlength="253" minlength="1" type="text" class="form-control" name="networkDHCPHostname" placeholder="<%= _.t('optional') %>" />
                                <div class="input-group-addon">
                                    <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                                </div>
                                <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('sensors:sensorNetworkHostnameInfo') %>">
                                    <span class="glyphicon glyphicon-question-sign"></span>
                                </div>
                            </div>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkModeDHCP">
                            <p class="form-control-static"><%= _.t("sensors:sensorNetworkAddressDHCPInfo") %></p>
                        </div>
                        <div class="form-group networkModeNone">
                            <p class="form-control-static"><%= _.t("sensors:sensorNetworkAddressUnconfiguredInfo") %></p>
                        </div>
                        <div class="form-group networkModeStatic has-feedback">
                            <label for="networkIP" class="control-label"><%= _.t("sensors:sensorNetworkAddressStaticIP") %></label>
                            <input pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" data-pattern-error="<%= _.t('ipAddrValidationError') %>" type="text" class="form-control" name="networkIP" placeholder="<%= _.t('sensors:sensorNetworkAddressStaticIPPlaceholder') %>" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkModeStatic has-feedback">
                            <label for="networkNetmask" class="control-label"><%= _.t("sensors:sensorNetworkAddressStaticSubnet") %></label>
                            <input pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" data-pattern-error="<%= _.t('ipAddrValidationError') %>" type="text" class="form-control" name="networkNetmask" placeholder="<%= _.t('sensors:sensorNetworkAddressStaticSubnetPlaceholder') %>" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkModeStatic has-feedback">
                            <label for="networkGateway" class="control-label"><%= _.t("sensors:sensorNetworkAddressStaticGateway") %></label>
                            <input pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" data-pattern-error="<%= _.t('ipAddrValidationError') %>" type="text" class="form-control" name="networkGateway" placeholder="<%= _.t('optional') %>" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkModeStatic has-feedback">
                            <label for="networkDNS" class="control-label"><%= _.t("sensors:sensorNetworkAddressStaticDNS") %></label>
                            <input pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" data-pattern-error="<%= _.t('ipAddrValidationError') %>" type="text" class="form-control" name="networkDNS" placeholder="<%= _.t('optional') %>" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group">
                            <span class="label label-info"><%= _.t("sensors:sensorNetworkEAPOLBeta") %></span>
                            <label for="networkEAPOLMode" class="control-label"><%= _.t("sensors:sensorNetworkEAPOL") %></label>
                            <select class="form-control" name="networkEAPOLMode">
                                <option value="0"><%= _.t("sensors:sensorNetworkEAPOLDisabled") %></option>
                                <option value="1">MD5</option>
                                <option value="2">TLS</option>
                                <option value="3">PEAP</option>
                                <option value="4">TTLS</option>
                            </select>
                        </div>
                        <div class="form-group networkEAPOLIdentity has-feedback">
                            <label for="networkEAPOLIdentity" class="control-label"><%= _.t("sensors:sensorNetworkEAPOLIdentity") %></label>
                            <input type="text" class="form-control" name="networkEAPOLIdentity" minlength="1" maxlength="512" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkEAPOLPassword has-feedback">
                            <label for="networkEAPOLPassword" class="control-label"><%= _.t("sensors:sensorNetworkEAPOLPassword") %></label>
                            <input type="password" class="form-control" name="networkEAPOLPassword" minlength="1" maxlength="512" placeholder="<%= _.t('sensors:sensorNetworkEAPOLPasswordPlaceholder') %>" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkEAPOLAnonIdentity has-feedback">
                            <label for="networkEAPOLAnonIdentity" class="control-label"><%= _.t("sensors:sensorNetworkEAPOLAnonIdentity") %></label>
                            <input type="text" class="form-control" name="networkEAPOLAnonIdentity" minlength="1" maxlength="512" placeholder="<%= _.t('optional') %>" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkEAPOLCA has-feedback">
                            <label for="networkEAPOLCA" class="control-label"><%= _.t("sensors:sensorNetworkEAPOLCA") %></label>
                            <input type="file" class="hide" />
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <button class="removeUpload btn btn-default" type="button">
                                        <span class="glyphicon glyphicon-remove"></span>
                                    </button>
                                </span>
                                <input type="text" class="form-control uploadMetadata" name="networkEAPOLCA" pattern="^.+ \(\d+ Bytes\)$|^[0-9A-Fa-f]+$" disabled />
                                <span class="input-group-btn">
                                    <button type="button" class="upload btn btn-default"><%= _.t("upload") %></button>
                                </span>
                            </div>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkEAPOLClientCert has-feedback">
                            <label for="networkEAPOLClientCert" class="control-label"><%= _.t("sensors:sensorNetworkEAPOLClientCert") %></label>
                            <input type="file" class="hide" />
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <button class="removeUpload btn btn-default" type="button">
                                        <span class="glyphicon glyphicon-remove"></span>
                                    </button>
                                </span>
                                <input type="text" class="form-control uploadMetadata" name="networkEAPOLClientCert" pattern="^.+ \(\d+ Bytes\)$|^[0-9A-Fa-f]+$" disabled />
                                <span class="input-group-btn">
                                    <button type="button" class="upload btn btn-default"><%= _.t("upload") %></button>
                                </span>
                            </div>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkEAPOLClientKey has-feedback">
                            <label for="networkEAPOLClientKey" class="control-label"><%= _.t("sensors:sensorNetworkEAPOLClientKey") %></label>
                            <input type="file" class="hidden" />
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <button class="removeUpload btn btn-default" type="button">
                                        <span class="glyphicon glyphicon-remove"></span>
                                    </button>
                                </span>
                                <input type="text" class="form-control uploadMetadata" name="networkEAPOLClientKey" pattern="^.+ \(\d+ Bytes\)$|^[0-9A-Fa-f]+$" disabled />
                                <span class="input-group-btn">
                                    <button type="button" class="upload btn btn-default"><%= _.t("upload") %></button>
                                </span>
                            </div>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkEAPOLClientPassphrase has-feedback">
                            <label for="networkEAPOLClientPassphrase" class="control-label"><%= _.t("sensors:sensorNetworkEAPOLClientPassphrase") %></label>
                            <input type="password" class="form-control" name="networkEAPOLClientPassphrase" minlength="1" maxlength="512" placeholder="<%= _.t('optional') %>" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group">
                            <label for="networkMACMode" class="control-label"><%= _.t("sensors:sensorNetworkMACMode") %></label>
                            <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-default">
                                    <input type="radio" name="networkMACMode" value="0"><%= _.t("sensors:sensorFirmwareStandard") %></input>
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="networkMACMode" value="1"><%= _.t("sensors:sensorNetworkMACModeCustom") %></input>
                                </label>
                            </div>
                        </div>
                        <div class="form-group networkMACOriginal">
                            <p class="form-control-static"><%= _.t("sensors:sensorNetworkMACModeStandardInfo") %></p>
                        </div>
                        <div class="form-group networkMACCustom has-feedback">
                            <label for="customMAC" class="control-label"><%= _.t("sensors:sensorNetworkMACCustom") %></label>
                            <input pattern="^(([A-Fa-f0-9]{2}[:]){5}[A-Fa-f0-9]{2}[,]?)+$" data-pattern-error="<%= _.t('macValidationError') %>" type="text" class="form-control" name="customMAC" placeholder="<%= _.t('sensors:sensorNetworkMACPlaceholder') %>" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group networkServiceNetwork has-feedback">
                            <label for="serviceNetwork" class="control-label"><%= _.t("sensors:sensorServiceNetwork") %></label>
                            <div class="input-group">
                                <span class="input-group-btn" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('sensors:sensorToggleSystemCustom') %>">
                                    <button type="button" class="useCustomServiceNetwork btn btn-default <% if(hasCustomServiceNetwork()) { %>active<% } %>">
                                        <span class="glyphicon glyphicon-cog"></span>
                                    </button>
                                </span>
                                <input pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(?:30|2[0-9]|1[0-9]|[1-9])$" data-pattern-error="<%= _.t('sensors:sensorServiceNetworkValidationError') %>" type="text" class="form-control" name="serviceNetwork" value="<%- getServiceNetwork() %>" <% if(!hasCustomServiceNetwork()) { %>disabled<% } %> />
                                <div class="input-group-addon">
                                    <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                                </div>
                                <div class="input-group-addon" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="<%= _.t('sensors:sensorServiceNetworkInfo') %>">
                                    <span class="glyphicon glyphicon-question-sign"></span>
                                </div>
                            </div>
                            <div class="help-block with-errors"></div>
                        </div>
                    </fieldset>
                    <fieldset>
                        <legend><%= _.t("sensors:sensorProxy") %></legend>
                        <div class="form-group">
                            <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-default">
                                    <input type="radio" name="proxyType" value="0"><%= _.t("sensors:sensorProxyDisabled") %></input>
                                </label>
                                <label class="btn btn-default">
                                    <input type="radio" name="proxyType" value="1"><%= _.t("sensors:sensorProxyEnabled") %></input>
                                </label>
                            </div>
                        </div>
                        <div class="form-group proxyTypeDisabled">
                            <p class="form-control-static"><%= _.t("sensors:sensorProxyDisabledInfo") %></p>
                        </div>
                        <div class="form-group proxyTypeEnabled has-feedback">
                            <label for="proxyHost" class="control-label"><%= _.t("sensors:sensorProxyHost") %></label>
                            <input type="text" class="form-control" name="proxyHost" placeholder="<%= _.t('sensors:sensorProxyHostPlaceholder') %>" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group proxyTypeEnabled has-feedback">
                            <label for="proxyPort" class="control-label"><%= _.t("port") %></label>
                            <input type="number" name="proxyPort" class="form-control" placeholder="<%= _.t('sensors:sensorProxyPortPlaceholder') %>" min="0" max="65535" data-max-error="<% _.t('intValidationError', {min: 0, max: 65535}) %>" />
                            <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                            <div class="help-block with-errors"></div>
                        </div>
                        <div class="form-group proxyTypeEnabled">
                            <label for="proxyUser" class="control-label"><%= _.t("sensors:sensorProxyUser") %></label>
                            <input type="text" name="proxyUser" class="form-control" placeholder="<%= _.t('optional') %>" />
                        </div>
                        <div class="form-group proxyTypeEnabled">
                            <label for="proxyPassword" class="control-label"><%= _.t("sensors:sensorProxyPassword") %></label>
                            <input type="password" name="proxyPassword" class="form-control" placeholder="<%= _.t('optional') %>" autocomplete="new-password" />
                        </div>
                    </fieldset>
                </div>
            </div>
            <hr />
            <div class="form-group">
                <div class="btn-group btn-group-justified">
                    <div class="btn-group">
                        <button type="button" class="cancel btn btn-default">&nbsp;&nbsp;<%= _.t("cancel") %></button>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;<%= _.t("save") %></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="row addBusy hide">
    <div class="col-sm-12">
        <p class="text-center"><%= _.t("sensors:sensorProcessing") %></p>
        <div class="loading center-block"></div>
    </div>
</div>
<div class="row addResult hide">
    <div class="col-sm-12">
        <div class="resultSuccess">
            <div class="alert alert-success"><%= _.t("sensors:sensorSaveSuccess") %></div>
            <p><%= _.t("sensors:sensorSaveSuccessInstructions") %></p>
            <div class="configArchive">
                <h5 class="text-center hide"><strong><%= _.t("sensors:sensorConfigWait") %></strong></h5>
                <div class="alert alert-danger hide"><%= _.t("sensors:sensorConfigError") %></div>
                <button type="button" class="btn btn-primary btn-block reqConfig"><span class="glyphicon glyphicon-download"></span>&nbsp;&nbsp;<%= _.t("sensors:sensorConfigDownloadButton") %></button>
            </div>
            <hr />
            <% if(firmwareExists()) { %>
            <p><%= _.t("sensors:sensorSaveFirmwareInstructions") %></p>
            <div class="panel-group" id="instructions">
                <% if(firmwareExists(1)) { %>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a class="collapsed" data-toggle="collapse" data-parent="#instructions" href="#instBBB"><%= _.t("BBB") %></a>
                        </h4>
                    </div>
                    <div id="instBBB" class="panel-collapse collapse">
                        <div class="panel-body">
                            <p><%= _.t("sensors:BBBInstructions") %></p>
                            <a class="btn btn-primary btn-block" href="#"><span class="glyphicon glyphicon-download"></span>&nbsp;&nbsp;<%= _.t("sensors:BBBDownload") %></a>
                        </div>
                    </div>
                </div>
                <% } %>
                <% if(firmwareExists(2)) { %>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a class="collapsed" data-toggle="collapse" data-parent="#instructions" href="#instDocker"><%= _.t("sensors:docker") %></a>
                        </h4>
                    </div>
                    <div id="instDocker" class="panel-collapse collapse">
                        <div class="panel-body">
                            <p><%= _.t("sensors:dockerInstructions") %></p>
                            <a class="btn btn-primary btn-block" href="#"><span class="glyphicon glyphicon-download"></span>&nbsp;&nbsp;<%= _.t("sensors:dockerDownload") %></a>
                        </div>
                    </div>
                </div>
                <% } %>
            </div>
            <% } %>
        </div>
        <div class="resultError hide">
            <div class="alert alert-danger"><%= _.t("sensors:sensorSaveError") %></div>
        </div>
        <hr />
        <button type="button" class="cancel btn btn-default btn-block"><%= _.t("close") %></button>
    </div>
</div>
