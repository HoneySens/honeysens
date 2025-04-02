<h2><%= _.t("setup:landingHeader") %></h2>
<hr />
<p><%= _.t("setup:landingIntro") %></p>
<button type="button" class="btn btn-primary btn-block install" <% if(!setup) { %>disabled<% } %>><%= _.t("continue") %></button>