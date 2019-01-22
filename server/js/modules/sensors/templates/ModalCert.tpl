<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4>Zertifikatdetails</h4>
        </div>
        <div class="modal-body">
            <div>
                <p class="col-sm-4"><strong>MD5-Fingerabdruck</strong></p>
                <p class="col-sm-8"><%- fingerprint %></p>
            </div>
            <div class="col-sm-4"><p><strong>Zertifikat</strong></div>
            <div class="col-sm-12 form-group certData">
                <textarea id="certContent" class="form-control"><%- content %></textarea>
            </div>
        </div>
        <div class="modal-footer" style="clear: both">
            <button type="button" class="btn btn-primary" data-dismiss="modal"><span class="glyphicon glyphicon-ok"></span>&nbsp;&nbsp;Schlie&szlig;en</button>
        </div>
    </div>
</div>