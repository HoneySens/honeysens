<div class="row">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-plus"></span>&nbsp;<%= _.t("uploadHeader") %></h1>
        <% if(!hasTask()) { %>
            <form>
                <div class="form-group">
                    <input type="file" id="fileUpload" />
                </div>
                <div class="uploadInvalid alert alert-danger hide">
                    <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;<span class="errorMsg"></span>
                </div>
            </form>
        <% } else { %>
            <div class="well text-center"><strong><%= params.path %></strong></div>
            <% if(status == _.templateHelpers.getModels().Task.status.SCHEDULED || status == _.templateHelpers.getModels().Task.status.RUNNING) { %>
                <div class="alert alert-info">
                    <div class="pull-left loadingInline"></div>&nbsp;<%= _.t("uploadVerifying") %></span>
                </div>
            <% } else if(status == _.templateHelpers.getModels().Task.status.DONE) { %>
                <% if(result.valid) { %>
                    <% if(isServiceArchive()) { %>
                        <div class="form-group">
                            <strong>Typ</strong>
                            <p class="form-control-static"><%= _.t("uploadService") %></p>
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
                                <div class="pull-left loadingInline"></div>&nbsp;<%= _.t("uploadServiceRegistering") %>
                            </div>
                            <div class="serviceMgrSuccess alert alert-success hide">
                                <%= _.t("uploadServiceSuccess") %>
                            </div>
                            <div class="serviceMgrError alert alert-danger hide">
                                <%= _.t("uploadServiceError") %> <span class="reason"></span>
                            </div>
                            <div class="btn-group btn-group-justified">
                                <div class="btn-group">
                                    <button type="button" class="createService btn btn-primary">&nbsp;&nbsp;<%= _.t("uploadServiceRegister") %></button>
                                </div>
                            </div>
                        </div>
                    <% } else if(isPlatformArchive()) { %>
                        <div class="form-group">
                            <strong><%= _.t("type") %></strong>
                            <p class="form-control-static"><%= _.t("uploadFirmware") %></p>
                        </div>
                        <div class="form-group">
                            <strong><%= _.t("name") %></strong>
                            <p class="form-control-static"><%= result.name %></p>
                        </div>
                        <div class="form-group">
                            <strong><%= _.t("sensors:sensorFirmwarePlatform") %></strong>
                            <p class="form-control-static"><%= result.platform %></p>
                        </div>
                        <div class="form-group">
                            <strong><%= _.t("platforms:firmwareVersion") %></strong>
                            <p class="form-control-static"><%= result.version %></p>
                        </div>
                        <div class="form-group">
                            <div class="firmwareSuccess alert alert-success hide">
                                <%= _.t("uploadFirmwareSuccess") %>
                            </div>
                            <div class="firmwareError alert alert-danger hide">
                                <%= _.t("uploadFirmwareError") %> <span class="reason"></span>
                            </div>
                            <div class="btn-group btn-group-justified">
                                <div class="btn-group">
                                    <button type="button" class="createFirmware btn btn-primary">&nbsp;&nbsp;<%= _.t("uploadFirmwareRegister") %></button>
                                </div>
                            </div>
                        </div>
                    <% } %>
                <% } else { %>
                    <div class="alert alert-danger">
                        <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;<%= _.t("uploadVerifyingError") %>
                    </div>
                <% } %>
            <% } else { %>
                <div class="alert alert-danger">
                    <span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;<%= _.t("uploadAbort") %>
                </div>
            <% } %>
        <% } %>
        <hr />
        <div class="form-group">
            <div class="btn-group btn-group-justified">
                <div class="btn-group">
                    <button type="button" class="cancel btn btn-default">&nbsp;&nbsp;<%= _.t("close") %></button>
                </div>
            </div>
        </div>
    </div>
</div>
