////////////////////////////////////////////////////////////////////////////////
//local helper:
//get elements by class name:
function LocalGetElementsByClassName(oBaseElement, sClassName) {
    var arrResult = new Array();
    var regClassNameTester = new RegExp("\\b" + sClassName + "\\b", "i");
    var allElements = oBaseElement.getElementsByTagName("*");
    var iIndex;
    for (iIndex = 0; iIndex < allElements.length; ++iIndex) {
        if (regClassNameTester.test(allElements[iIndex].className)) {
            //match:
            arrResult.push(allElements[iIndex]);
        }
    }
    //done:
    return arrResult;
}
//system timer:
function LocalGetSysTickCount() {
    var dateNow = new Date()
    return dateNow.getTime();
}
//browser type and version:
function LocalBrowserTypeAndVersion() {
    var sVersion;
    var Info = new Object();
    var sUserAgent = navigator.userAgent.toLowerCase();
    if (sVersion = sUserAgent.match(/msie ([\d.]+)/)) {
        Info.type = "ie";
        Info.version = sVersion[1];
    }
    else if (sVersion = sUserAgent.match(/firefox\/([\d.]+)/)) {
        Info.type = "firefox";
        Info.version = sVersion[1];
    }
    else if (sVersion = sUserAgent.match(/chrome\/([\d.]+)/)) {
        Info.type = "chrome";
        Info.version = sVersion[1];
    }
    else if (sVersion = sUserAgent.match(/opera.([\d.]+)/)) {
        Info.type = "opera";
        Info.version = sVersion[1];
    }
    else if (sVersion = sUserAgent.match(/version\/([\d.]+).*safari/)) {
        Info.type = "safari";
        Info.version = sVersion[1];
    }
    else {
        Info.type = "unknown";
        Info.version = "0";
    }
    //done:
    return Info;
}
//parse XML:
function LocalParseXML(sXML) {
    var xmlDoc;
    var iParseFlag = 0;
    if (sXML) {
        try {
            //for IE:
            xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
            xmlDoc.async = "false";
            xmlDoc.loadXML(sXML);
            if (typeof xmlDoc.parseError == "undefined") {
                iParseFlag = 1;
            }
            else if (0 == xmlDoc.parseError.errorCode) {
                iParseFlag = 1;
            }
            else {
                //parse error, ignored
                //...
            }
        }
        catch (e) {
            try {
                //for FF, Mozilla, Opera, etc...
                var Parser = new DOMParser();
                var errDocRootNS = Parser.parseFromString("<", "text/xml").getElementsByTagName("parsererror")[0].namespaceURI;
                xmlDoc = Parser.parseFromString(sXML, "text/xml");
                if (errDocRootNS != xmlDoc.documentElement.namespaceURI) {
                    //parse ssuccessfully:
                    iParseFlag = 1;
                }
            }
            catch (e) {
                //parse error, ignored
                //...
            }
        }
    }
    if (iParseFlag) {
        //parse successfully:
        return xmlDoc;
    }
    else {
        //prase fail:
        return null;
    }
}
//get realtime style(from external CSS):
function LocalQueryStyle(oTarget, sStyleName) {
    if (oTarget.currentStyle) {
        //IE:
        return oTarget.currentStyle[sStyleName];
    }
    else if (window.getComputedStyle) {
        //non-IE:
        //sStyleName = sStyleName.replace(/([A-Z])/g, "-$1");
        //sStyleName = sStyleName.toLowerCase();
        return getComputedStyle(oTarget, null)[sStyleName];
    }
    else {
        //fail:
        return null;
    }
}
//check default button:
function LocalCheckSubmit(e, oTargetButton) {
    if (!e)
        e = window.event;
    if (e && e.keyCode == 13) {
        var eleSource = e.srcElement || e.target;
        if (eleSource && oTargetButton) {
            oTargetButton.click();
        }
    }
}
//add event hooker:
function LocalAddEvent(eleTarget, sIEEvent, sOtherEvent, fnHandler, bUseCapture) {
    if (eleTarget && fnHandler) {
        if (window.attachEvent) {
            //for IE:
            eleTarget.attachEvent(sIEEvent, fnHandler);
        }
        else {
            //others:
            eleTarget.addEventListener(sOtherEvent, fnHandler, bUseCapture);
        }
    }
}
//remove event hooker:
function LocalRemoveEvent(eleTarget, sIEEvent, sOtherEvent, fnHandler) {
    if (eleTarget && fnHandler) {
        if (window.attachEvent) {
            //for IE:
            eleTarget.detachEvent(sIEEvent, fnHandler);
        }
        else {
            //others:
            eleTarget.removeEventListener(sOtherEvent, fnHandler, bUseCapture);
        }
    }
}
//add a timer function for ctrl:
function __LocalCtrlTimerFuncHandler(oTarget, hHandle) {
    return function () {
        hHandle(oTarget);
    }
}
function __LocalCtrlTimerFuncBegin(oTarget, hHandle, iPeriod) {
    return function (e) {
        oTarget.m_iCtrlTimerHandler = setInterval(__LocalCtrlTimerFuncHandler(oTarget, hHandle), iPeriod);
    }
}
function __LocalCtrlTimerFuncEnd(oTarget) {
    return function (e) {
        if (typeof oTarget.m_iCtrlTimerHandler != "undefined") {
            clearInterval(oTarget.m_iCtrlTimerHandler);
        }
    }
}
function LocalAddCtrlTimerFunc(oTarget, hHandle, iPeriod, bOnlyWhenFocus) {
    if (oTarget && typeof hHandle == "function" && iPeriod > 0) {
        if (bOnlyWhenFocus) {
            //add focus/blur handler for add/remove the event handler:
            LocalAddEvent(oTarget, "onfocus", "focus", __LocalCtrlTimerFuncBegin(oTarget, hHandle, iPeriod), false);
            LocalAddEvent(oTarget, "onblur", "blur", __LocalCtrlTimerFuncEnd(oTarget), false);
        }
        else {
            //set interval directly:
            oTarget.m_iCtrlTimerHandler = setInterval(__LocalCtrlTimerFuncHandler(oTarget, hHandle), iPeriod);
        }
    }
}
function LocalRemoveAllChildren(oTarget) {
    while (oTarget.childNodes.length > 0) {
        oTarget.removeChild(oTarget.childNodes[0]);
    }
}

////////////////////////////////////////////////////////////////////////////////
//encode/decode helper:
//string to utf8:
function String2UTF8(sSrc) {
    var iCharCode;
    var oUTF8BytesArray = new Array();
    for (var iIndex = 0; iIndex < sSrc.length; iIndex++) {
        iCharCode = sSrc.charCodeAt(iIndex) >>> 0;
        if (iCharCode) {
            if (iCharCode < 0x80) {
                oUTF8BytesArray.push(iCharCode);
            }
            else if (iCharCode < 0x800) {
                //2 bytes char:
                oUTF8BytesArray.push((iCharCode >> 6) & 31 | 192);
                oUTF8BytesArray.push(iCharCode & 63 | 128);
            }
            else if (iCharCode < 0x10000) {
                //3 bytes char:
                oUTF8BytesArray.push((iCharCode >> 12) & 15 | 224);
                oUTF8BytesArray.push((iCharCode >> 6) & 63 | 128);
                oUTF8BytesArray.push(iCharCode & 63 | 128);
            }
            else if (iCharCode < 0x110000) {
                //4 bytes char:
                oUTF8BytesArray.push((iCharCode >> 18) & 7 | 240);
                oUTF8BytesArray.push((iCharCode >> 12) & 63 | 128);
                oUTF8BytesArray.push((iCharCode >> 6) & 63 | 128);
                oUTF8BytesArray.push(iCharCode & 63 | 128);
            }
            else {
                //invalid uncode, ignored.
                //...
            }
        }
        else {
            break;
        }
    }
    return oUTF8BytesArray;
}
//utf8 to unicode:
function UTF82String(oUTF8BytesArray) {
    var iCharCode;
    var sString = "";
    for (var iIndex = 0; iIndex < oUTF8BytesArray.length; iIndex++) {
        iCharCode = oUTF8BytesArray[iIndex];
        if (iCharCode) {
            if (iCharCode < 0x80) {
                //1 byte char:
                sString += String.fromCharCode(iCharCode);
            }
            else if (iCharCode >= 192 && iCharCode < 224) {
                //2 bytes char:
                if ((iIndex + 1) < oUTF8BytesArray.length) {
                    sString += String.fromCharCode(((iCharCode & 31) << 6) | (oUTF8BytesArray[iIndex + 1] & 63));
                    iIndex++;
                }
                else
                    break;
            }
            else if (iCharCode >= 224 && iCharCode < 240) {
                //3 bytes char:
                if ((iIndex + 2) < oUTF8BytesArray.length) {
                    sString += String.fromCharCode(((iCharCode & 15) << 12) | ((oUTF8BytesArray[iIndex + 1] & 63) << 6) | (oUTF8BytesArray[iIndex + 2] & 63));
                    iIndex += 2;
                }
                else
                    break;
            }
            else {
                //4 bytes char:
                if ((iIndex + 3) < oUTF8BytesArray.length) {
                    sString += String.fromCharCode(((iCharCode & 7) << 18) | ((oUTF8BytesArray[iIndex + 1] & 63) << 12) | ((oUTF8BytesArray[iIndex + 2] & 63) << 6) | (oUTF8BytesArray[iIndex + 3] & 63));
                    iIndex += 3;
                }
                else
                    break;
            }
        }
        else {
            break;
        }
    }
    return sString;
}
//BCD encode:
function BCDEncode(oUINT8Array) {
    var u8Temp;
    var sString = "";
    for (var iIndex = 0; iIndex < oUINT8Array.length; iIndex++) {
        u8Temp = (oUINT8Array[iIndex] & 0xF0) >>> 4;
        if (u8Temp < 10)
            sString += String.fromCharCode(0x30 + u8Temp);
        else
            sString += String.fromCharCode(0x41 + u8Temp - 10);
        u8Temp = (oUINT8Array[iIndex] & 0x0F) >>> 0;
        if (u8Temp < 10)
            sString += String.fromCharCode(0x30 + u8Temp);
        else
            sString += String.fromCharCode(0x41 + u8Temp - 10);
    }
    return sString;
}
//BCD decode:
function BCDDecode(sSrc) {
    var u8Temp;
    var iIndex = 0;
    var iResultIndex = 0;
    var oUINT8Array = new Array();
    while (iIndex < sSrc.length) {
        iCharCode = sSrc.charCodeAt(iIndex++) >>> 0;
        if (iCharCode >= 0x30 && iCharCode <= 0x39) {
            //0-9:
            u8Temp = iCharCode - 0x30;
        }
        else if (iCharCode >= 0x41 && iCharCode <= 0x46) {
            //A-F:
            u8Temp = iCharCode - 0x41 + 10;
        }
        else
            break;
        u8Temp <<= 4;
        if (iIndex < sSrc.length) {
            iCharCode = sSrc.charCodeAt(iIndex++) >>> 0;
            if (iCharCode >= 0x30 && iCharCode <= 0x39) {
                //0-9:
                u8Temp |= iCharCode - 0x30;
            }
            else if (iCharCode >= 0x41 && iCharCode <= 0x46) {
                //A-F:
                u8Temp |= iCharCode - 0x41 + 10;
            }
            else
                break;
            oUINT8Array[iResultIndex++] = u8Temp;
        }
    }
    return oUINT8Array;
}
//base64 encode:
var g_Base64Table = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
function find_character_pos(chCharacter) {
    for (var u8Index = 0; u8Index < g_Base64Table.length; ++u8Index) {
        if (g_Base64Table.charAt(u8Index) == chCharacter)
            return u8Index;
    }
    //not found:
    return 0;
}
function Base64Encode(oUINT8Array) {
    var i32Index;
    var i32TransformUnitBytes;
    var u8TransformPos;
    var u32TransformUnit;
    var u32SrcIndex = 0;
    var sString = "";
    u32TransformUnit >>>= 0;
    while (u32SrcIndex < oUINT8Array.length) {
        //prepare unit:
        i32Index = 0;
        i32TransformUnitBytes = 1;
        u32TransformUnit = 0;
        while (++i32Index <= 3) {
            u32TransformUnit <<= 8;
            if (u32SrcIndex < oUINT8Array.length) {
                u32TransformUnit |= (oUINT8Array[u32SrcIndex++] & 0xFF); ++i32TransformUnitBytes;
            }
        }

        //transform unit:
        i32Index = 18;
        while (i32Index >= 0) {
            if (--i32TransformUnitBytes >= 0) {
                u8TransformPos = (u32TransformUnit >>> i32Index) & 0x3F;
            }
            else {
                u8TransformPos = 0x40;
            }
            sString += g_Base64Table.substring(u8TransformPos, u8TransformPos + 1);
            i32Index -= 6;
        }
    }

    //done:
    return sString;
}
//base64 decode:
function Base64Decode(sSrc) {
    var i32Index;
    var i32TransformUnitBytes;
    var u8TransformPos;
    var u32TransformUnit;
    var u32SrcIndex = 0;
    var u32DstIndex = 0;
    var oUINT8Array = new Array();
    u32TransformUnit >>>= 0;
    while ((u32SrcIndex + 3) < sSrc.length) {
        //prepare unit:
        i32Index = 0;
        i32TransformUnitBytes = 0;
        u32TransformUnit = 0;
        while (++i32Index <= 4) {
            u32TransformUnit <<= 6;
            if (sSrc.charAt(u32SrcIndex) != g_Base64Table.charAt(0x40)) {
                u32TransformUnit |= (find_character_pos(sSrc.charAt(u32SrcIndex)) & 0x3F); ++u32SrcIndex; ++i32TransformUnitBytes;
            }
        }

        //transform unit:
        i32Index = 16;
        while (i32Index >= 0) {
            if (--i32TransformUnitBytes > 0) {
                oUINT8Array[u32DstIndex++] = (u32TransformUnit >>> i32Index) & 0xFF;
            }
            i32Index -= 8;
        }
    }

    //done:
    return oUINT8Array;
}

////////////////////////////////////////////////////////////////////////////////
//signed/unsigned convertor helper:
function Unsigned2Signed(iValue, sType) {
    switch (sType) {
        case "2":
            {
                iValue &= 0xFF;
                if (iValue >= 0x80) {
                    iValue = iValue - 0xFF - 1;
                }
                break;
            }
        case "4":
            {
                iValue &= 0xFFFF;
                if (iValue >= 0x8000) {
                    iValue = iValue - 0xFFFF - 1;
                }
                break;
            }
        case "6":
        default:
            {
                iValue &= 0xFFFFFFFF;
                if (iValue >= 0x80000000) {
                    iValue = iValue - 0xFFFFFFFF - 1;
                }
                break;
            }
    }
    //done:
    return iValue;
}
function Signed2Unsinged(iValue, sType) {
    switch (sType) {
        case "2":
            {
                iValue &= 0xFF;
                if (iValue < 0) {
                    iValue = iValue + 0xFF + 1;
                }
                break;
            }
        case "4":
            {
                iValue &= 0xFFFF;
                if (iValue < 0) {
                    iValue = iValue + 0xFFFF + 1;
                }
                break;
            }
        case "6":
        default:
            {
                iValue &= 0xFFFFFFFF;
                if (iValue < 0) {
                    iValue = iValue + 0xFFFFFFFF + 1;
                }
                break;
            }
    }
    //done:
    return iValue;
}