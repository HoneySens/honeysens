////////////////////////////////////////////////////////////////////////////////
//page utilities:
function LocalLoginTrace(oLoginTracer) {
    clearTimeout(oLoginTracer.m_iTimeoutID);
    if (oLoginTracer.m_iFinishFlag) {
        if (oLoginTracer.m_iResult == 700) {
            //login successfully:
            LocalStorage.Instance().Set("logo_current_login_key1A1", oLoginTracer.m_iKey1A1);
            LocalStorage.Instance().Set("logo_current_login_key1A2", oLoginTracer.m_iKey1A2);
            LocalStorage.Instance().Set("logo_current_login_key1B1", oLoginTracer.m_iKey1B1);
            LocalStorage.Instance().Set("logo_current_login_key1B2", oLoginTracer.m_iKey1B2);
            LocalStorage.Instance().Set("logo_current_login_ref", oLoginTracer.m_iRef);
            if (oLoginTracer.m_bKeeySignIn) {
                //auto login is enabled, record the related info:
                LocalStorage.Instance().Set("logo_current_password", oLoginTracer.m_sPassword);
                LocalStorage.Instance().Set("logo_current_autologin", 1);
            }
            else {
                //auto login is disabled, remove the related info:
                LocalStorage.Instance().Remove("logo_current_password");
                LocalStorage.Instance().Remove("logo_current_autologin");
            }
            //navigate to next page:
            var sLastPage = LocalStorage.Instance().Get("logo_current_page");
            if (oLoginTracer.m_bLogToCustomizedSite) {
                LocalStorage.Instance().Set("logo_current_logto_customized_site", 1);
                if (!sLastPage || 0 != sLastPage.indexOf("/lfs/dev/sdcard/")) {
                    sLastPage = "/lfs/dev/sdcard/webroot/main.htm";
                }
            }
            else {
                LocalStorage.Instance().Remove("logo_current_logto_customized_site");
                if (!sLastPage || 0 == sLastPage.indexOf("/lfs/dev/sdcard/")) {
                    sLastPage = "/logo_system_01.shtm";
                }
            }
            window.location.replace(sLastPage + "?!App-Language=" + LocalStorage.Instance().Get("logo_current_language") + "&Security-Hint=" + LocalStorage.Instance().Get("logo_current_login_ref"));
        }
        else {
            //login fail:
            if (oLoginTracer.m_oButtonLogin) {
                var sShowText;
                switch (oLoginTracer.m_iResult) {
                    case 702:
                        {
                            sShowText = oLoginTracer.m_oButtonLogin.getAttribute("failed_by_password");
                            if (!sShowText) {
                                sShowText = "Log on fail: 702";
                            }
                            break;
                        }
                    case 707:
                        {
                            sShowText = oLoginTracer.m_oButtonLogin.getAttribute("webserver_disabled");
                            if (!sShowText) {
                                sShowText = "Log on fail: 707";
                            }
                            break;
                        }
                    default:
                        sShowText = "Log on fail: " + oLoginTracer.m_iResult;
                        break;
                }
                if (oLoginTracer.m_oInputPassword)
                    oLoginTracer.m_oInputPassword.focus();
                alert(sShowText);
            }
            else {
                //navigate to login page:
                window.location.replace("/logo_login.shtm?!App-Language=" + LocalStorage.Instance().Get("logo_current_language"));
            }
        }
        if (oLoginTracer.m_oButtonLogin) {
            //enable login button:
            var sValue = oLoginTracer.m_oButtonLogin.innerHTML;
            var iIndex = sValue.indexOf(".");
            if (iIndex >= 0) {
                sValue = sValue.substr(0, iIndex);
                oLoginTracer.m_oButtonLogin.innerHTML = sValue;
            }
            oLoginTracer.m_oButtonLogin.disabled = "";
        }
    }
    else {
        //the login is in process, continue wait:
        oLoginTracer.m_iTimeoutID = setTimeout(LocalLoginTraceHelper(oLoginTracer), 500);
        if (oLoginTracer.m_oButtonLogin) {
            var sValue = oLoginTracer.m_oButtonLogin.innerHTML;
            var iIndex = sValue.indexOf("...");
            if (iIndex >= 0)
                sValue = sValue.substr(0, iIndex + 1);
            else
                sValue += ".";
            oLoginTracer.m_oButtonLogin.innerHTML = sValue;
        }
    }
}
function LocalLoginTraceHelper(oLoginTracer) {
    return function () {
        LocalLoginTrace(oLoginTracer);
    }
}
function LocalLogin(sPublicKey1, sPublicKey2, sPassword, bKeepSignIn, bLogToCustomizedSite, oButtonLogin, oInputPassword) {
    //validate successfully, try to login:
    var oLoginTracer = new Object();
    oLoginTracer.m_sPublicKey1 = sPublicKey1;
    oLoginTracer.m_sPublicKey2 = sPublicKey2;
    oLoginTracer.m_sUserName = "Web User";
    oLoginTracer.m_sPassword = sPassword;
    oLoginTracer.m_iLang = LocalStorage.Instance().Get("logo_current_language");
    oLoginTracer.m_bKeeySignIn = bKeepSignIn;
    oLoginTracer.m_bLogToCustomizedSite = bLogToCustomizedSite;
    oLoginTracer.m_oButtonLogin = oButtonLogin;
    oLoginTracer.m_oInputPassword = oInputPassword;
    Login(oLoginTracer);
    oLoginTracer.m_iTimeoutID = setTimeout(LocalLoginTraceHelper(oLoginTracer), 500);
    if (oButtonLogin) {
        //disable login button when user is loging in
        oButtonLogin.disabled = "disabled";
    }
}
function LocalLogoutTrace(oLogoutTracer) {
    clearTimeout(oLogoutTracer.m_iTimeoutID);
    if (oLogoutTracer.m_iFinishFlag) {
        //remove auto login flag:
        LocalStorage.Instance().Remove("logo_current_password");
        LocalStorage.Instance().Remove("logo_current_autologin");
        //navigate to login page:
        window.location.replace("/logo_login.shtm?!App-Language=" + LocalStorage.Instance().Get("logo_current_language"));
    }
    else {
        //the logout process is not end, continue wait:
        oLogoutTracer.m_iTimeoutID = setTimeout(LocalLogoutTraceHelper(oLogoutTracer), 500);
    }
}
function LocalLogoutTraceHelper(oLogoutTracer) {
    return function () {
        LocalLogoutTrace(oLogoutTracer);
    }
}
function LocalLogout() {
    //remove all data sync interval:
    if (typeof g_DataSyncTimerID != "undefined") {
        while (g_DataSyncTimerID.length > 0) {
            clearInterval(g_DataSyncTimerID[0]);
            g_DataSyncTimerID.shift();
        }
    }

    //perform logout:
    var oLogoutTracer = new Object();
    oLogoutTracer.m_oPrivateKey1A = DESMakeKey(LocalStorage.Instance().Get("logo_current_login_key1A1"), LocalStorage.Instance().Get("logo_current_login_key1A2"));
    oLogoutTracer.m_oPrivateKey1B = DESMakeKey(LocalStorage.Instance().Get("logo_current_login_key1B1"), LocalStorage.Instance().Get("logo_current_login_key1B2"));
    oLogoutTracer.m_iRef = LocalStorage.Instance().Get("logo_current_login_ref");
    oLogoutTracer.m_iLang = LocalStorage.Instance().Get("logo_current_language");
    Logout(oLogoutTracer);
    oLogoutTracer.m_iTimeoutID = setTimeout(LocalLogoutTraceHelper(oLogoutTracer), 500);
    return false;
}
function LocalMenuNavigate(oTarget) {
    if (oTarget) {
        var sTargetString = oTarget.getAttribute("target_page");
        if (sTargetString) {
            //navigate to target page:
            window.location.replace(sTargetString + "?!App-Language=" + LocalStorage.Instance().Get("logo_current_language") + "&Security-Hint=" + LocalStorage.Instance().Get("logo_current_login_ref"));
        }
    }
    return false;
}
function LocalMobileMenuNavigate(oSelectMobileMenu) {
    if (oSelectMobileMenu) {
        if (oSelectMobileMenu.selectedIndex == 0) {
            //user request logout:
            LocalLogout();
        }
        else {
            var sTargetString = oSelectMobileMenu.options[oSelectMobileMenu.selectedIndex].getAttribute("target_page");
            if (sTargetString) {
                //navigate to target page:
                window.location.replace(sTargetString + "?!App-Language=" + LocalStorage.Instance().Get("logo_current_language") + "&Security-Hint=" + LocalStorage.Instance().Get("logo_current_login_ref"));
            }
        }
    }
}