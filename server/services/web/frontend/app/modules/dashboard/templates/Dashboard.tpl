<div class="row">
    <div class="col-sm-12">
        <form class="form-inline filters">
            <div class="form-group">
                <select class="divisionFilter form-control">
                    <option value=""><%= _.t("allDivisions") %></option>
                </select>
                <select class="monthFilter form-control">
                    <option value=""><%= _.t("allMonths") %></option>
                    <option value="1"><%= _.t("january") %></option>
                    <option value="2"><%= _.t("february") %></option>
                    <option value="3"><%= _.t("march") %></option>
                    <option value="4"><%= _.t("april") %></option>
                    <option value="5"><%= _.t("may") %></option>
                    <option value="6"><%= _.t("june") %></option>
                    <option value="7"><%= _.t("july") %></option>
                    <option value="8"><%= _.t("august") %></option>
                    <option value="9"><%= _.t("september") %></option>
                    <option value="10"><%= _.t("october") %></option>
                    <option value="11"><%= _.t("november") %></option>
                    <option value="12"><%= _.t("december") %></option>
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
