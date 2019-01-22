<h2>Server-Endpunkt</h2>
<hr />
<form>
    <p>Spezifizieren Sie hier den Namen, unter dem der Server aus Sicht der Sensoren im Netz erreichbar ist. </p>
    <p>Falls für diesen Server ein TLS-Zertifikat hinterlegt wurde, ist hier dessen Common Name (CN) einzutragen.
        Das ist typischerweise der DNS-Name des Servers. Falls hingegen ein selbstsigniertes Zertifikat genutzt wird,
        ist hier dessen Common Name zu verwenden. Es wurde versucht, den korrekten Wert automatisch zu ermitteln.</p>
    <div class="form-group">
        <label for="serverEndpoint">Server-Endpunkt</label>
        <input type="text" class="form-control" id="serverEndpoint" name="serverEndpoint" value="<%- showCertCN() %>" />
    </div>
    <button type="submit" class="btn btn-primary btn-block">Weiter</button>
</form>
