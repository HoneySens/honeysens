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
        <div class="form-group">
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
            <input name="value" type="text" class="form-control input-sm" placeholder="Wert" />
        </div>
    </form>
</td>
<td>
    <button type="button" class="remove btn btn-default btn-sm" data-toggle="tooltip" title="Entfernen">
        <span class="glyphicon glyphicon-remove"></span>
    </button>
</td>