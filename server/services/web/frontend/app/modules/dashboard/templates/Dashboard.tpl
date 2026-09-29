<div class="row">
    <div class="col-sm-12">
        <form class="form-inline filters">
            <div class="form-group">
                <select class="divisionFilter form-control">
                    <option value=""><%= t("allDivisions") %></option>
                </select>
                <select class="monthFilter form-control">
                    <option value=""><%= t("allMonths") %></option>
                    <option value="1"><%= t("january") %></option>
                    <option value="2"><%= t("february") %></option>
                    <option value="3"><%= t("march") %></option>
                    <option value="4"><%= t("april") %></option>
                    <option value="5"><%= t("may") %></option>
                    <option value="6"><%= t("june") %></option>
                    <option value="7"><%= t("july") %></option>
                    <option value="8"><%= t("august") %></option>
                    <option value="9"><%= t("september") %></option>
                    <option value="10"><%= t("october") %></option>
                    <option value="11"><%= t("november") %></option>
                    <option value="12"><%= t("december") %></option>
                </select>
                <button type="button" class="yearDec btn btn-default">&laquo;</button>
                <input type="number" class="form-control yearFilter" style="width: 5em;" />
                <button type="button" class="yearInc btn btn-default">&raquo;</button>

            </div>
        </form>
    </div>
</div>
<div class="row"><div class="eventsTimeline col-sm-12"></div></div>
<div class="row">
    <div class="classificationBreakdown col-sm-5"></div>
    <div class="summary col-sm-7"></div>
</div>
