<form class="form-group">
    <p>Ereignisse k&ouml;nnen zus&auml;tzlich bei ihrem Auftreten an einen externen Syslog-Server weitergeleitet werden,
        um bspw. SIEM-Systeme &uuml;ber Vorf&auml;lle zu informieren.</p>
    <div class="form-group has-feedback">
        <label for="syslogServer" class="control-label">Server</label>
        <input type="text" name="syslogServer" class="form-control" value="<%- syslogServer %>" placeholder="Hostname oder IP-Adresse" <% if(syslogEnabled) { %>required<% } %> />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group has-feedback">
        <label for="syslogPort" class="control-label">Port</label>
        <input type="number" name="syslogPort" class="form-control" placeholder="514" value="<%- syslogPort %>" required min="0" max="65535" data-max-error="Der Port muss zwischen 0 und 65535 liegen" />
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <div class="form-group">
        <label for="syslogTransport" class="control-label">Transportprotokoll</label>
        <select name="syslogTransport" class="form-control">
            <option value="<%- _.templateHelpers.getModels().Settings.transport.UDP %>">UDP</option>
            <option value="<%- _.templateHelpers.getModels().Settings.transport.TCP %>">TCP</option>
        </select>
    </div>
    <div class="form-group">
        <label for="syslogFacility" class="control-label">Dienst (Syslog-Facility)</label>
        <select name="syslogFacility" class="form-control">
            <option value="0">kern (0)</option>
            <option value="1">user (1)</option>
            <option value="2">mail (2)</option>
            <option value="3">daemon (3)</option>
            <option value="4">auth (4)</option>
            <option value="5">syslog (5)</option>
            <option value="6">lpr (6)</option>
            <option value="7">news (7)</option>
            <option value="8">uucp (8)</option>
            <option value="9">cron (9)</option>
            <option value="10">authpriv (10)</option>
            <option value="11">ftp (11)</option>
            <option value="16">local0 (16)</option>
            <option value="17">local1 (17)</option>
            <option value="18">local2 (18)</option>
            <option value="19">local3 (19)</option>
            <option value="20">local4 (20)</option>
            <option value="21">local5 (21)</option>
            <option value="22">local6 (22)</option>
            <option value="23">local7 (23)</option>
        </select>
    </div>
    <div class="form-group">
        <label for="syslogPriority" class="control-label">Priorit&auml;t</label>
        <select name="syslogPriority" class="form-control">
            <option value="2">crit (2)</option>
            <option value="3">err (3)</option>
            <option value="4">warning (4)</option>
            <option value="6">info (6)</option>
            <option value="7">debug (7)</option>
        </select>
    </div>
    <div class="row">
        <div class="col-sm-4">
            <button type="submit" class="saveSettings btn btn-block btn-primary btn-sm">
                <span class="glyphicon glyphicon-save"></span>&nbsp;&nbsp;Speichern
            </button>
        </div>
        <div class="col-sm-4">
            <button type="button" class="sendTestEvent btn btn-block col-lg-6 btn-default btn-sm"><span class="glyphicon glyphicon-send"></span>&nbsp;&nbsp;Testereignis versenden</button>
        </div>
        <div class="col-sm-4">
            <button type="button" class="reset btn btn-block btn-default btn-sm">
                <span class="glyphicon glyphicon-repeat"></span>&nbsp;&nbsp;Zur&uuml;cksetzen
            </button>
        </div>
    </div>
</form>