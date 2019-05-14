////////////////////////////////////////////////////////////////////////////////
//utility function:
function OnAjaxRequestStateChange() {
    if (4 == this.readyState) {
        this.m_iLocalResult = this.status;
        if (typeof this.m_iTimeoutID != "undefined" && this.m_iTimeoutID != null) {
            //clear the timeout handler
            clearTimeout(this.m_iTimeoutID);
            delete this.m_iTimeoutID;
        }
        if (typeof this.m_hResultHandler == "function") {
            //handler does exists, call it:
            this.m_hResultHandler.call(this);
        }
        this.m_oPool_.Reclaim(this);
    }
}
function OnAjaxRequestTimeout(oRequest) {
    if (oRequest) {
        //client timeout:
        oRequest.abort();
        oRequest.m_iLocalResult = 601;
        if (typeof oRequest.m_iTimeoutID != "undefined" && oRequest.m_iTimeoutID != null) {
            //clear the timeout handler
            clearTimeout(oRequest.m_iTimeoutID);
            delete oRequest.m_iTimeoutID;
        }
        if (typeof oRequest.m_hResultHandler == "function") {
            //handler does exists, call it:
            oRequest.m_hResultHandler.call(oRequest);
        }
        oRequest.m_oPool_.Reclaim(oRequest);
    }
}
function OnAjaxRequestTimeoutHelper(oRequest) {
    return function () {
        //try to call real handler:
        OnAjaxRequestTimeout(oRequest);
    }
}

////////////////////////////////////////////////////////////////////////////////
//AJAX request object cache:
function AjaxRequest(iCacheSize, iRequestTimeout) {
    //vaiables:
    this.m_aObjPool_ = new Array();
    if (!iCacheSize) {
        //user not offer a pool size, use default:
        this.m_iCacheSize_ = 32;
    }
    else {
        this.m_iCacheSize_ = iCacheSize;
    }
    if (!iRequestTimeout) {
        //user not offer a request timeout, use default:
        this.m_iRequestTimeout_ = 12000;
    }
    else {
        this.m_iRequestTimeout_ = iRequestTimeout;
    }

    //methods:
    if (typeof AjaxRequest.m_iInitialized_ == "undefined") {
        //create the prototype:
        //request interface:
        AjaxRequest.prototype.Request = function (strFunction, strParameter, hResultHandler, oContext, iLanguage, iTimeout, sEncryptType, oKey1, oKey2, iRef) {
            //validate parameter:
            if (!strFunction) {
                //invalid function name:
                return;
            }

            //prepare request object:
            var oRequest = null;
            if (this.m_aObjPool_.length <= 0) {
                //pool is empty, we will create one:
                if (window.XMLHttpRequest) {
                    //Mozilla, Netscape, Safari:
                    oRequest = new XMLHttpRequest();
                    if (oRequest.overrideMimeType) {
                        //support other response different to "text/xml":
                        oRequest.overrideMimeType('text/xml');
                    }
                }
                else if (window.ActiveXObject) {
                    //IE:
                    try {
                        oRequest = new ActiveXObject("Msxml2.XMLHTTP");
                    }
                    catch (errResult) {
                        try {
                            oRequest = new ActiveXObject("Microsoft.XMLHTTP");
                        }
                        catch (errResult) {
                            //create fail finally, can not go on:
                            //...
                        }
                    }
                }
                else {
                    //explorer does not support AJAX:
                    //...
                }
            }
            else {
                //we use cached request object:
                oRequest = this.m_aObjPool_.pop();
            }
            if (!oRequest) {
                //create(request) request object fail
                return;
            }
            else {
                //create(request) request object successfully, initialize:
                oRequest.open("POST", "/AJAX", true);
                oRequest.m_oPool_ = this;
                oRequest.m_iEncryptFlag = parseInt(LocalStorage.Instance().Get("logo_comm_channel_encrypt_flag"));
                oRequest.onreadystatechange = OnAjaxRequestStateChange;
                oRequest.m_iLocalResult = 600;
                if (typeof hResultHandler == "function")
                    oRequest.m_hResultHandler = hResultHandler;
                else
                    oRequest.m_hResultHandler = null;
                if (oContext)
                    oRequest.m_oContext = oContext;
                else
                    oRequest.m_oContext = null;
                if (typeof iLanguage != "undefined" && iLanguage != null) {
                    //language id:
                    oRequest.setRequestHeader("App-Language", iLanguage);
                }
            }

            //build request string:
            var strRequst = strFunction;
            if (strParameter) {
                strRequst += ":";
                strRequst += strParameter;
            }

            //encrypt request string:
            switch (sEncryptType) {
                case "ASYM":
                    {
                        //asymmetric encryption:
                        if (oRequest.m_iEncryptFlag)
                            strRequst = Base64Encode(RSAEncrypt(String2UTF8(strRequst)));
                        oRequest.setRequestHeader("Security-Hint", "p");
                        break;
                    }
                case "SYM":
                    {
                        if (oKey1) {
                            //symmetric encryption:
                            if (oRequest.m_iEncryptFlag)
                                strRequst = Base64Encode(TDESEncrypt(String2UTF8(strRequst), oKey1, oKey2));
                            if (iRef)
                                oRequest.setRequestHeader("Security-Hint", iRef);
                        }
                        break;
                    }
                default:
                    break;
            }

            //schedule the timeout handler:
            oRequest.m_iTimeoutID = setTimeout(OnAjaxRequestTimeoutHelper(oRequest), iTimeout ? iTimeout : this.m_iRequestTimeout_);

            //release request:
            oRequest.send(strRequst);
        }

        //reclaim interface:
        AjaxRequest.prototype.Reclaim = function (oRequest) {
            if (oRequest) {
                //request object does exists:
                if (this.m_aObjPool_.length < this.m_iCacheSize_) {
                    //there is space in cache:
                    //delete oRequest.m_hResultHandler;
                    //delete oRequest.m_oContext;
                    this.m_aObjPool_.push(oRequest);
                }
                else {
                    //cache is full, let the request object go.
                    //(it will be reclaimed by GC after all reference to it are released)
                    //...
                }
            }
        }

        //set the prototype initialize flag:
        AjaxRequest.m_iInitialized_ = 1;
    }
}
AjaxRequest.Instance = function (iCacheSize, iRequestTimeout) {
    if (typeof AjaxRequest.m_oSingleInstance_ == "undefined") {
        //singleton semantics
        AjaxRequest.m_oSingleInstance_ = new AjaxRequest(iCacheSize, iRequestTimeout);
    }
    return AjaxRequest.m_oSingleInstance_;
}