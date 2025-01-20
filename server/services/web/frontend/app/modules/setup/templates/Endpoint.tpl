<h2>Server-Endpunkt</h2>
<hr />
<form>
    <p>Spezifizieren Sie hier den Namen, unter dem der Server aus Sicht der Sensoren im Netz erreichbar ist. </p>
    <p>Falls f&uumlr diesen Server ein TLS-Zertifikat hinterlegt wurde, ist hier dessen Common Name (CN) einzutragen.
        Das ist typischerweise der DNS-Name des Servers. Falls hingegen ein selbstsigniertes Zertifikat genutzt wird,
        ist hier dessen Common Name zu verwenden. Es wurde versucht, den korrekten Wert automatisch zu ermitteln.</p>
    <div class="form-group has-feedback">
        <label for="serverEndpoint">Server-Endpunkt</label>
        <input type="text" name="serverEndpoint" id="serverEndpoint" class="form-control" value="<%- showCertCN() %>" required minlength="1" maxlength="255" data-maxlength-error="Der Endpunkt muss zwischen 1 und 255 Zeichen lang sein"/>
        <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
        <div class="help-block with-errors"></div>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Weiter</button>
</form>