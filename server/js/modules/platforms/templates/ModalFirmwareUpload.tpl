<div id="modal-firmware-upload" class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Firmware hinzuf&uuml;gen</h4>
        </div>
        <div class="modal-body">
            <form class="form-horizontal" role="form">
                <div class="row">
                    <label for="imageUpload" class="col-sm-3 control-label">Upload</label>
                    <div class="col-sm-6">
                        <span class="btn btn-primary fileinput-button">
                            <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;Datei w&auml;hlen
                            <input type="file" id="imageUpload" name="image" />
                        </span>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                0%
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3 progress-text">
                        <span class="progress-loaded"></span> / <span class="progress-total"></span> MB
                    </div>
                </div>
                <div class="form-group imageVerify">
                    <div class="col-sm-offset-3 col-sm-8">
                        <span class="imageValidating"><div class="pull-left loadingInline"></div>&nbsp;&Uuml;berpr&uuml;fe Firmware</span>
                        <span class="imageValid"><span class="glyphicon glyphicon-ok"></span>&nbsp;&nbsp;G&uuml;ltige Firmware erkannt</span>
                        <span class="imageInvalid"><span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;<span class="errorMsg"></span></span>
                    </div>
                </div>
                <div id="imageInfo">
                    <div class="form-group">
                        <label for="imageName" class="col-sm-3 control-label">Name</label>
                        <div class="col-sm-5">
                            <p class="form-control-static imageName"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="imageVersion" class="col-sm-3 control-label">Version</label>
                        <div class="col-sm-5">
                            <p class="form-control-static imageVersion"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="imageDescription" class="col-sm-3 control-label">Beschreibung</label>
                        <div class="col-sm-5">
                            <p class="form-control-static imageDescription"></p>
                        </div>
                    </div>
                    <div class="alert alert-success">Das Hinzuf&uuml;gen der Firmware war erfolgreich.</div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
        </div>
    </div>
</div>