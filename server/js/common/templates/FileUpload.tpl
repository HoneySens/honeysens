<div class="row">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-plus"></span>&nbsp;Archiv-Upload</h1>
        <% if(!hasTask()) { %>
            <form>
                <div class="form-group">
                    <label for="fileUpload" class="control-label">Upload&nbsp;
                        <span class="progress-text">
                            (<span class="progress-loaded"></span> / <span class="progress-total"></span> MB)
                        </span>
                    </label>
                    <span class="btn btn-primary form-control fileinput-button">
                        <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;Datei w&auml;hlen
                        <input type="file" id="fileUpload" name="upload" />
                    </span>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            0%
                        </div>
                    </div>
                </div>
                <div class="uploadInvalid alert alert-danger hide">
                    <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;<span class="errorMsg"></span>
                </div>
            </form>
        <% } else { %>
            <div class="well text-center"><strong><%= params.path %></strong></div>
            <% if(status == _.templateHelpers.getModels().Task.status.SCHEDULED || status == _.templateHelpers.getModels().Task.status.RUNNING) { %>
                <div class="alert alert-info">
                    <div class="pull-left loadingInline"></div>&nbsp;&Uuml;berpr&uuml;fung l&auml;uft</span>
                </div>
            <% } else if(status == _.templateHelpers.getModels().Task.status.DONE) { %>
                <% if(result.valid) { %>
                    <% if(isServiceArchive()) { %>
                        <div class="form-group">
                            <strong>Typ</strong>
                            <p class="form-control-static">Dienst</p>
                        </div>
                        <div class="form-group">
                            <strong>Name</strong>
                            <p class="form-control-static"><%= result.name %></p>
                        </div>
                        <div class="form-group">
                            <strong>Architektur</strong>
                            <p class="form-control-static"><%= result.architecture %></p>
                        </div>
                        <div class="form-group">
                            <strong>Revision</strong>
                            <p class="form-control-static"><%= result.revision %></p>
                        </div>
                        <div class="form-group">
                            <div class="serviceMgrRunning alert alert-info hide">
                                <div class="pull-left loadingInline"></div>&nbsp;Dienst wird registriert</span>
                            </div>
                            <div class="serviceMgrSuccess alert alert-success hide">
                                Der Dienst wurde erfolgreich registriert
                            </div>
                            <div class="serviceMgrError alert alert-danger hide">
                                Der Dienst konnte nicht registriert werden <span class="reason"></span>
                            </div>
                            <div class="btn-group btn-group-justified">
                                <div class="btn-group">
                                    <button type="button" class="createService btn btn-primary">&nbsp;&nbsp;Dienst auf dem Server registrieren</button>
                                    <button type="button" class="removeTask btn btn-primary hide">&nbsp;&nbsp;Ok</button>
                                </div>
                            </div>
                        </div>
                    <% } else if(isPlatformArchive()) { %>
                        <div class="form-group">
                            <strong>Typ</strong>
                            <p class="form-control-static">Plattform-Firmware</p>
                        </div>
                        <div class="form-group">
                            <strong>Name</strong>
                            <p class="form-control-static"><%= result.name %></p>
                        </div>
                        <div class="form-group">
                            <strong>Plattform</strong>
                            <p class="form-control-static"><%= result.platform %></p>
                        </div>
                        <div class="form-group">
                            <strong>Version</strong>
                            <p class="form-control-static"><%= result.version %></p>
                        </div>
                        <div class="form-group">
                            <div class="firmwareSuccess alert alert-success hide">
                                Die Firmware wurde erfolgreich registriert
                            </div>
                            <div class="firmwareError alert alert-danger hide">
                                Die Firmware konnte nicht registriert werden <span class="reason"></span>
                            </div>
                            <div class="btn-group btn-group-justified">
                                <div class="btn-group">
                                    <button type="button" class="createFirmware btn btn-primary">&nbsp;&nbsp;Firmware auf dem Server registrieren</button>
                                    <button type="button" class="removeTask btn btn-primary hide">&nbsp;&nbsp;Ok</button>
                                </div>
                            </div>
                        </div>
                    <% } %>
                <% } else { %>
                    <div class="alert alert-danger">
                        <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;Die hochgeladene Datei konnte nicht verarbeitet werden.
                    </div>
                    <div class="form-group">
                        <div class="btn-group btn-group-justified">
                            <div class="btn-group">
                                <button type="button" class="removeTask btn btn-primary">&nbsp;&nbsp;Ok</button>
                            </div>
                        </div>
                    </div>
                <% } %>
            <% } else { %>
                <div class="alert alert-danger">
                    <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;Der Upload-Vorgang wurde unerwartet beendet.
                </div>
                <div class="form-group">
                    <div class="btn-group btn-group-justified">
                        <div class="btn-group">
                            <button type="button" class="removeTask btn btn-primary">&nbsp;&nbsp;Ok</button>
                        </div>
                    </div>
                </div>
            <% } %>
        <% } %>
        <hr />
        <div class="form-group">
            <div class="btn-group btn-group-justified">
                <div class="btn-group">
                    <button type="button" class="cancel btn btn-default">&nbsp;&nbsp;Schlie&szlig;en</button>
                </div>
            </div>
        </div>
    </div>
</div>