<div class="col-sm-12">
    <img class="pull-right" src="/assets/images/honeysens-logo-small.svg" height="96" />
    <dl class="dl-horizontal label-left nooverflow">
        <dt><%= _.t("info:infoPlatform") %></dt>
        <dd>HoneySens Server</dd>
        <dt><%= _.t("info:infoRevision") %></dt>
        <dd>2.8.0</dd>
        <dt><%= _.t("info:infoBuild") %></dt>
        <dd><%- showBuildID() %></dd>
        <dt><%= _.t("info:infoLicense") %></dt>
        <dd><a href="https://www.apache.org/licenses/LICENSE-2.0.html" target="_blank">Apache 2.0 Software License</a></dd>
        <dt><%= _.t("info:infoDevelopment") %></dt>
        <!--<dd>Pascal Br&uuml;ckner</dd>-->
        <dd>T-Systems Multimedia Solutions</dd>
        <dt><%= _.t("info:infoWebsite") %></dt>
        <dd><a href="https://honeysens.org/" target="_blank">honeysens.org</a></dd>
    </dl>
    <div class="well"><%= _.t("info:infoDescription") %></div>
    <div class="panel-group" id="infoTopics">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a class="collapsed" data-toggle="collapse" data-parent="#infoTopics" href="#documentation"><%= _.t("info:infoDocumentation") %></a>
                </h4>
            </div>
            <div id="documentation" class="panel-collapse collapse">
                <div class="panel-body">
                    <div class="panelIcon pull-left"><span class="glyphicon glyphicon-globe"></span></div>
                    <p><a href="https://honeysens.org/docs" target="_blank"><%= _.t("info:infoDocumentationWebsite") %></a>
                        <br /><%= _.t("info:infoDocumentationDetails") %></p>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a class="collapsed" data-toggle="collapse" data-parent="#infoTopics" href="#releasenotesServer"><%= _.t("info:infoServerNotesHeader") %></a>
                </h4>
            </div>
            <div id="releasenotesServer" class="panel-collapse collapse">
                <div class="panel-body">
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.8.0</strong> - <%= _.t("december") %> 2024<br/>
                    <p>
                    <ul>
                        <li><%= _.t("info:infoServerNotes280p1") %></li>
                        <li><%= _.t("info:infoServerNotes280p2") %></li>
                    </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.7.0</strong> - <%= _.t("january") %> 2024<br/>
                    <p>
                    <ul>
                        <li><%= _.t("info:infoServerNotes270p1") %></li>
                        <li><%= _.t("info:infoServerNotes270p2") %></li>
                        <li><%= _.t("info:infoServerNotes270p3") %></li>
                        <li><%= _.t("info:infoServerNotes270p4") %></li>
                        <li><%= _.t("info:infoServerNotes270p5") %></li>
                    </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.6.1</strong> - <%= _.t("may") %> 2023<br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes261p1") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.6.0</strong> - <%= _.t("december") %> 2022<br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes260p1") %></li>
                            <li><%= _.t("info:infoServerNotes260p2") %></li>
                            <li><%= _.t("info:infoServerNotes260p3") %></li>
                            <li><%= _.t("info:infoServerNotes260p4") %></li>
                            <li><%= _.t("info:infoServerNotes260p5") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.5.0</strong> - <%= _.t("may") %> 2022<br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes250p1") %></li>
                            <li><%= _.t("info:infoServerNotes250p2") %></li>
                            <li><%= _.t("info:infoServerNotes250p3") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.4.0</strong> - <%= _.t("april") %> 2022<br/>
                    <p>
                        <ul>
                            <li><strong><%= _.t("info:infoServerNotes240p1Highlight") %></strong>: <%= _.t("info:infoServerNotes240p1") %></li>
                            <li><%= _.t("info:infoServerNotes240p2") %></li>
                            <li><%= _.t("info:infoServerNotes240p3") %></li>
                            <li><%= _.t("info:infoServerNotes240p4") %></li>
                            <li><%= _.t("info:infoServerNotes240p5") %></li>
                            <li><%= _.t("info:infoServerNotes240p6") %></li>
                            <li><%= _.t("info:infoServerNotes240p7") %></li>
                            <li><%= _.t("info:infoServerNotes240p8") %></li>
                            <li><%= _.t("info:infoServerNotes240p9") %></li>
                            <li><%= _.t("info:infoServerNotes240p10") %></li>
                            <li><%= _.t("info:infoServerNotes240p11") %></li>
                            <li><%= _.t("info:infoServerNotes240p12") %></li>
                            <li><%= _.t("info:infoServerNotes240p13") %></li>
                            <li><%= _.t("info:infoServerNotes240p14") %></li>
                            <li><%= _.t("info:infoServerNotes240p15") %></li>
                            <li><%= _.t("info:infoServerNotes240p16") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.3.0</strong> - <%= _.t("june") %> 2021<br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes230p1") %></li>
                            <li><%= _.t("info:infoServerNotes230p2") %></li>
                            <li><%= _.t("info:infoServerNotes230p3") %></li>
                            <li><%= _.t("info:infoServerNotes230p4") %></li>
                            <li><%= _.t("info:infoServerNotes230p5") %></li>
                            <li><%= _.t("info:infoServerNotes230p6") %></li>
                            <li><%= _.t("info:infoServerNotes230p7") %></li>
                            <li><%= _.t("info:infoServerNotes230p8") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.2.0</strong> - <%= _.t("august") %> 2020<br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes220p1") %></li>
                            <li><%= _.t("info:infoServerNotes220p2") %></li>
                            <li><%= _.t("info:infoServerNotes220p3") %></li>
                            <li><%= _.t("info:infoServerNotes220p4") %></li>
                            <li><%= _.t("info:infoServerNotes220p5") %></li>
                            <li><%= _.t("info:infoServerNotes220p6") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.1.0</strong> - <%= _.t("august") %> 2019<br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes210p1") %></li>
                            <li><%= _.t("info:infoServerNotes210p2") %></li>
                            <li><%= _.t("info:infoServerNotes210p3") %></li>
                            <li><%= _.t("info:infoServerNotes210p4") %></li>
                            <li><%= _.t("info:infoServerNotes210p5") %></li>
                            <li><%= _.t("info:infoServerNotes210p6") %></li>
                            <li><%= _.t("info:infoServerNotes210p7") %></li>
                            <li><%= _.t("info:infoServerNotes210p8") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.0.0</strong> - <%= _.t("may") %> 2019<br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes200p1") %></li>
                            <li><%= _.t("info:infoServerNotes200p2") %></li>
                            <li><%= _.t("info:infoServerNotes200p3") %></li>
                            <li><%= _.t("info:infoServerNotes200p4") %></li>
                            <li><%= _.t("info:infoServerNotes200p5") %></li>
                            <li><%= _.t("info:infoServerNotes200p6") %></li>
                            <li><%= _.t("info:infoServerNotes200p7") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.4</strong> - <%= _.t("march") %> 2019<br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes104p1") %></li>
                            <li><%= _.t("info:infoServerNotes104p2") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.3</strong> - <%= _.t("february") %> 2019<br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes103p1") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.2</strong> - <%= _.t("january") %> 2019<br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes102p1") %></li>
                            <li><%= _.t("info:infoServerNotes102p2") %></li>
                            <li><%= _.t("info:infoServerNotes102p3") %></li>
                            <li><%= _.t("info:infoServerNotes102p4") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.1</strong><br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes101p1") %></li>
                            <li><%= _.t("info:infoServerNotes101p2") %></li>
                            <li><%= _.t("info:infoServerNotes101p3") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.0</strong><br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes100p1") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 0.9.0</strong><br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes090p1") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 0.2.5</strong><br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes025p1") %></li>
                            <li><%= _.t("info:infoServerNotes025p2") %></li>
                        </ul>
                    </p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 0.2.4</strong><br/>
                    <p>
                        <ul>
                            <li><%= _.t("info:infoServerNotes024p1") %></li>
                            <li><%= _.t("info:infoServerNotes024p2") %></li>
                        </ul>
                    </p>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a class="collapsed" data-toggle="collapse" data-parent="#infoTopics" href="#releasenotesBBB"><%= _.t("info:infoBBBNotesHeader") %></a>
                </h4>
            </div>
            <div id="releasenotesBBB" class="panel-collapse collapse">
                <div class="panel-body">
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.6.0</strong> - <%= _.t("may") %> 2023<br/>
                    <p><%= _.t("info:infoBBBNotes260") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.5.0</strong> - <%= _.t("may") %> 2022<br/>
                    <p><%= _.t("info:infoBBBNotes250") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.4.0</strong> - <%= _.t("april") %> 2022<br/>
                    <p><%= _.t("info:infoBBBNotes240") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.3.0</strong> - <%= _.t("june") %> 2021<br/>
                    <p><%= _.t("info:infoBBBNotes230") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.2.2</strong> - <%= _.t("october") %> 2020<br/>
                    <p><%= _.t("info:infoBBBNotes222") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.2.1</strong> - <%= _.t("october") %> 2020<br/>
                    <p><%= _.t("info:infoBBBNotes221") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.2.0</strong> - <%= _.t("august") %> 2020<br/>
                    <p><%= _.t("info:infoBBBNotes222") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.0.0</strong> - <%= _.t("may") %> 2019<br/>
                    <p><%= _.t("info:infoBBBNotes200") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.5</strong><br/>
                    <p><%= _.t("info:infoBBBNotes105") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.4</strong> - <%= _.t("march") %> 2019<br/>
                    <p><%= _.t("info:infoBBBNotes104") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.3</strong> - <%= _.t("february") %> 2019<br/>
                    <p><%= _.t("info:infoBBBNotes103") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.2</strong> - <%= _.t("january") %> 2019<br/>
                    <p><%= _.t("info:infoBBBNotes102") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.1</strong><br/>
                    <p><%= _.t("info:infoBBBNotes101") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.0</strong><br/>
                    <p><%= _.t("info:infoBBBNotes100") %></p>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a class="collapsed" data-toggle="collapse" data-parent="#infoTopics" href="#releasenotesDocker"><%= _.t("info:infoDockerNotesHeader") %></a>
                </h4>
            </div>
            <div id="releasenotesDocker" class="panel-collapse collapse">
                <div class="panel-body">
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.6.0</strong> - <%= _.t("may") %> 2023<br/>
                    <p><%= _.t("info:infoDockerNotes260") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.5.0</strong> - <%= _.t("may") %> 2022<br/>
                    <p><%= _.t("info:infoDockerNotes250") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.4.0</strong> - <%= _.t("april") %> 2022<br/>
                    <p><%= _.t("info:infoDockerNotes240") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.3.0</strong> - <%= _.t("june") %> 2021<br/>
                    <p><%= _.t("info:infoDockerNotes230") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.2.2</strong> - <%= _.t("october") %> 2020<br/>
                    <p><%= _.t("info:infoDockerNotes222") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.2.1</strong> - <%= _.t("october") %> 2020<br/>
                    <p><%= _.t("info:infoDockerNotes221") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.2.0</strong> - <%= _.t("august") %> 2020<br/>
                    <p><%= _.t("info:infoDockerNotes220") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 2.0.0</strong> - <%= _.t("may") %> 2019<br/>
                    <p><%= _.t("info:infoDockerNotes200") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.1.0</strong> - <%= _.t("march") %> 2019<br/>
                    <p><%= _.t("info:infoDockerNotes110") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.4</strong> - <%= _.t("march") %> 2019<br/>
                    <p><%= _.t("info:infoDockerNotes104") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.3</strong> - <%= _.t("february") %> 2019<br/>
                    <p><%= _.t("info:infoDockerNotes103") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.2</strong> - <%= _.t("january") %> 2019<br/>
                    <p><%= _.t("info:infoDockerNotes102") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.1</strong><br/>
                    <p><%= _.t("info:infoDockerNotes101") %></p>
                    <hr />
                    <span class="glyphicon glyphicon-record"></span>&nbsp;&nbsp;<strong><%= _.t("info:revision") %> 1.0.0</strong><br/>
                    <p><%= _.t("info:infoDockerNotes100") %></p>
                </div>
            </div>
        </div>
    </div>
</div>
