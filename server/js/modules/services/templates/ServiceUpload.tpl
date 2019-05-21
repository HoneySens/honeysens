<div class="row">
    <div class="col-sm-12">
        <h1 class="page-header"><span class="glyphicon glyphicon-plus"></span>&nbsp;Service hinzuf&uuml;gen</h1>
        <form>
            <div class="form-group">
                <label for="serviceUpload" class="control-label">Upload&nbsp;
                    <span class="progress-text">
                        (<span class="progress-loaded"></span> / <span class="progress-total"></span> MB)
                    </span>
                </label>
                <span class="btn btn-primary form-control fileinput-button">
                    <span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;Datei w&auml;hlen
                    <input type="file" id="serviceUpload" name="service" />
                </span>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        0%
                    </div>
                </div>
            </div>
            <div class="form-group serviceVerify">
                <span class="imageValidating"><div class="pull-left loadingInline"></div>&nbsp;&Uuml;berpr&uuml;fe Archiv</span>
                <div class="imageValid alert alert-success"><span class="glyphicon glyphicon-ok"></span>&nbsp;&nbsp;Das Service-Archiv <span class="archiveName"></span> wurde erfolgreich auf dem Server registriert</div>
                <div class="imageInvalid alert alert-danger"><span class="glyphicon glyphicon-remove"></span>&nbsp;&nbsp;<span class="errorMsg"></span></div>
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
