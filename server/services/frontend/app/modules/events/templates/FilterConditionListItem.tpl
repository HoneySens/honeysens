<td>
    <select name="attribute" class="form-control input-sm">
        <option value="<%- _.templateHelpers.getModels().EventFilterCondition.field.CLASSIFICATION %>" <%- field === _.templateHelpers.getModels().EventFilterCondition.field.CLASSIFICATION ? 'selected' : void 0 %>>Klassifikation</option>
        <option value="<%- _.templateHelpers.getModels().EventFilterCondition.field.SOURCE %>" <%- field === _.templateHelpers.getModels().EventFilterCondition.field.SOURCE ? 'selected' : void 0 %>>Quelle</option>
        <option value="<%- _.templateHelpers.getModels().EventFilterCondition.field.TARGET %>" <%- field === _.templateHelpers.getModels().EventFilterCondition.field.TARGET ? 'selected' : void 0 %>>Ziel</option>
        <option value="<%- _.templateHelpers.getModels().EventFilterCondition.field.PROTOCOL %>" <%- field === _.templateHelpers.getModels().EventFilterCondition.field.PROTOCOL ? 'selected' : void 0 %>>Protokoll</option>
    </select>
</td>
<td>
    <select name="type" class="form-control input-sm" disabled></select>
</td>
<td> 
    <form class="conditionData form-horizontal">
        <div class="form-group has-feedback">
            <select name="classification" class="form-control input-sm">
                <option value="<%- _.templateHelpers.getModels().Event.classification.UNKNOWN %>">Unbekannt</option>
                <option value="<%- _.templateHelpers.getModels().Event.classification.ICMP %>">ICMP</option>
                <option value="<%- _.templateHelpers.getModels().Event.classification.CONN_ATTEMPT %>">Verbindungsversuch</option>
                <option value="<%- _.templateHelpers.getModels().Event.classification.LOW_HP %>">Honeypot</option>
                <option value="<%- _.templateHelpers.getModels().Event.classification.PORTSCAN %>">Portscan</option>
            </select>
            <select name="protocol" class="form-control input-sm">
                <option value="<%- _.templateHelpers.getModels().EventPacket.protocol.TCP %>">TCP</option>
                <option value="<%- _.templateHelpers.getModels().EventPacket.protocol.UDP %>">UDP</option>
            </select>
            <input type="number" name="port_value" class="form-control input-sm" placeholder="Port" min="1" max="65535" data-max-error="Der Port muss zwischen 1 und 65535 liegen" required />
            <input type="text" name="ip_value" class="form-control input-sm hide" placeholder="IP-Adresse" pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" data-pattern-error="Bitte geben Sie eine valide IP-Adresse ein" />
            <input type="text" name="ip_range_value" class="form-control input-sm hide" placeholder="IP-Bereich" pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)-(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$" data-pattern-error="IP-Bereich muss das Muster a.b.c.d-e.f.g.h aufweisen" />
            <div class="form-feedback">
                <span class="form-control-feedback glyphicon" aria-hidden="true"></span>
                <div class="help-block with-errors"></div>
            </div>
        </div>
    </form>
</td>
<td>
    <button type="button" class="remove btn btn-default btn-sm" data-toggle="tooltip" title="Entfernen">
        <span class="glyphicon glyphicon-remove"></span>
    </button>
</td>