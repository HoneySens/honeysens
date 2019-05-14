////////////////////////////////////////////////////////////////////////////////
//local storage(cross browers):
function LocalStorage(iExpireDays) {
    //vaiables:
    this.m_sHostName = location.hostname || "myLocalStorage";
    this.m_bSupportLocalStorage = window.localStorage ? true : false;
    this.m_oDomUserData = null;
    if (iExpireDays) {
        this.m_iExpireDays = iExpireDays;
    }
    else {
        //use 30 days as default:
        this.m_iExpireDays = 30;
    }

    //methods:
    if (typeof LocalStorage.m_iInitialized_ == "undefined") {
        //create the prototype:
        //initialize user data(for IE7 and before):
        LocalStorage.prototype.InitUserData = function () {
            if (!this.m_oDomUserData) {
                try {
                    this.m_oDomUserData = document.createElement('INPUT');
                    this.m_oDomUserData.type = "hidden";
                    this.m_oDomUserData.style.display = "none";
                    this.m_oDomUserData.addBehavior("#default#userData");
                    document.body.appendChild(this.m_oDomUserData);
                    var oExpire = new Date();
                    oExpire.setDate(oExpire.getDate() + this.m_iExpireDays);
                    this.m_oDomUserData.expires = oExpire.toUTCString();
                }
                catch (e) {
                    return false;
                }
            }
            return true;
        }
        LocalStorage.prototype.Get = function (sKey) {
            var sValue;
            if (this.m_bSupportLocalStorage) {
                sValue = window.localStorage.getItem(this.m_sHostName + "_" + sKey);
            }
            else {
                if (this.InitUserData()) {
                    this.m_oDomUserData.load(this.m_sHostName);
                    sValue = this.m_oDomUserData.getAttribute(sKey);
                }
            }
            //done:
            return sValue;
        }
        LocalStorage.prototype.Set = function (sKey, sValue) {
            if (this.m_bSupportLocalStorage) {
                var sRealKey = this.m_sHostName + "_" + sKey;
                window.localStorage.removeItem(sRealKey);
                window.localStorage.setItem(sRealKey, sValue);
            }
            else {
                if (this.InitUserData()) {
                    this.m_oDomUserData.load(this.m_sHostName);
                    this.m_oDomUserData.setAttribute(sKey, sValue);
                    this.m_oDomUserData.save(this.m_sHostName);
                }
                else {
                    //init user data fail:
                    return false;
                }
            }
            //done successfully:
            return true;
        }
        LocalStorage.prototype.Remove = function (sKey) {
            if (this.m_bSupportLocalStorage) {
                window.localStorage.removeItem(this.m_sHostName + "_" + sKey);
            }
            else {
                if (this.InitUserData()) {
                    this.m_oDomUserData.load(this.m_sHostName);
                    this.m_oDomUserData.removeAttribute(sKey);
                    this.m_oDomUserData.save(this.m_sHostName);
                }
            }
        }

        //set the prototype initialize flag:
        LocalStorage.m_iInitialized_ = 1;
    }
}
LocalStorage.Instance = function (iExpireDays) {
    if (typeof LocalStorage.m_oSingleInstance_ == "undefined") {
        //singleton semantics
        LocalStorage.m_oSingleInstance_ = new LocalStorage(iExpireDays);
    }
    return LocalStorage.m_oSingleInstance_;
}