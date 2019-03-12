<div class="row">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-plus"></span>&nbsp;Firmware hinzuf&uuml;gen</h1>
        <form>
            <div class="form-group">
                <label for="imageUpload" class="control-label">Upload&nbsp;
                    <span class="progress-text">
                        (<span class="progress-loaded"></span> / <span class="progress-total"></span> MB)
                    </span>
                </label>             
                <span class="btn btn-primary form-control fileinput-button">
                    <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;Datei w&auml;hlen
                    <input type="file" id="imageUpload" name="image" />
                </span>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        0%
                    </div>
                </div>
            </div>
            <div class="form-group imageVerify">          
                <span class="imageValidating"><div class="pull-left loadingInline"></div>&nbsp;&Uuml;berpr&uuml;fe Archiv</span>
                <div class="imageValid alert alert-success"><span class="glyphicon glyphicon-ok"></span>&nbsp;&nbsp;G&uuml;ltige Firmware erkannt</div>
                <div class="imageInvalid alert alert-danger"><span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;<span class="errorMsg"></span></div>
            </div>
            <div id="imageInfo">
                <div class="form-group">
                    <strong>Name</strong>
                    <p class="form-control-static imageName"></p>
                </div>
                <div class="form-group">
                    <strong>Version</strong>
                    <p class="form-control-static imageVersion"></p>
                </div>
                <div class="form-group">
                    <strong>Beschreibung</strong>
                    <p class="form-control-static imageDescription"></p>
                </div>
                <div class="alert alert-success">Das Hinzuf&uuml;gen der Firmware war erfolgreich.</div>
            </div>
        </form>
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
