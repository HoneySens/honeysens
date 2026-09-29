<h2><%= t("setup:landingHeader") %></h2>
<hr />
<p><%= t("setup:landingIntro") %></p>
<button type="button" class="btn btn-primary btn-block install" <% if(!setup) { %>disabled<% } %>><%= t("continue") %></button>