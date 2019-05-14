//////////////////////////////////////////////////////////////////////////
//login APIs:
function LoginHandlerStep2() {
    this.m_oContext.m_iFinishFlag = 1;
    if (this.m_iLocalResult == 200) {
        var sResponseText;
        if (this.m_iEncryptFlag)
            sResponseText = UTF82String(TDESDecrypt(Base64Decode(this.responseText), this.m_oContext.m_oPrivateKey1A, this.m_oContext.m_oPrivateKey1B));
        else
            sResponseText = this.responseText;
        if (sResponseText) {
            var arrResult = sResponseText.split(",");
            if (parseInt(arrResult[0]) == 700) {
                if (arrResult.length == 2) {
                    this.m_oContext.m_iRef = parseInt(arrResult[1]) >>> 0;
                }
            }
            else {
                if (isNaN(parseInt(arrResult[0]))) {
                    //logical error:
                    arrResult[0] = "603";
                }
            }
            this.m_oContext.m_iResult = parseInt(arrResult[0]);
        }
        else {
            //decrypt fail:
            this.m_oContext.m_iResult = 602;
        }
    }
    else {
        //invalid return:
        this.m_oContext.m_iResult = this.m_iLocalResult;
    }
}
function LoginHandlerStep1() {
    if (this.m_iLocalResult == 200) {
        var sResponseText = this.responseText;
        /*
        var sAlert = "text: " + sResponseText;
        sAlert += "\nkey1A1: ";
        sAlert += (((this.m_oContext.m_oPrivateKey1A[0] << 24) | (this.m_oContext.m_oPrivateKey1A[1] << 16) | (this.m_oContext.m_oPrivateKey1A[2] << 8) | this.m_oContext.m_oPrivateKey1A[3]) >>> 0).toString();
        sAlert += "\nkey1A2: ";
        sAlert += (((this.m_oContext.m_oPrivateKey1A[4] << 24) | (this.m_oContext.m_oPrivateKey1A[5] << 16) | (this.m_oContext.m_oPrivateKey1A[6] << 8) | this.m_oContext.m_oPrivateKey1A[7]) >>> 0).toString();
        sAlert += "\nkey1B1: ";
        sAlert += (((this.m_oContext.m_oPrivateKey1B[0] << 24) | (this.m_oContext.m_oPrivateKey1B[1] << 16) | (this.m_oContext.m_oPrivateKey1B[2] << 8) | this.m_oContext.m_oPrivateKey1B[3]) >>> 0).toString();
        sAlert += "\nkey1B2: ";
        sAlert += (((this.m_oContext.m_oPrivateKey1B[4] << 24) | (this.m_oContext.m_oPrivateKey1B[5] << 16) | (this.m_oContext.m_oPrivateKey1B[6] << 8) | this.m_oContext.m_oPrivateKey1B[7]) >>> 0).toString();
        alert(sAlert);
        */
        if (this.m_iEncryptFlag)
            sResponseText = UTF82String(TDESDecrypt(Base64Decode(sResponseText), this.m_oContext.m_oPrivateKey1A, this.m_oContext.m_oPrivateKey1B));
        else
            sResponseText = this.responseText;
        if (sResponseText) {
            var bSuccessfully = false;
            var arrResult = sResponseText.split(",");
            if (parseInt(arrResult[0]) == 700) {
                if (arrResult.length == 3) {
                    this.m_oContext.m_iRef = parseInt(arrResult[1]) >>> 0;
                    this.m_oContext.m_iKey2 = parseInt(arrResult[2]) >>> 0;
                    bSuccessfully = true;
                }
            }
            else {
                if (isNaN(parseInt(arrResult[0]))) {
                    //logical error:
                    arrResult[0] = "603";
                }
            }
            if (bSuccessfully) {
                var sPasswordToken = this.m_oContext.m_sPassword + "+" + this.m_oContext.m_iKey2;
                sPasswordToken = sPasswordToken.substr(0, 32);
                var iPasswordToken = (MakeCRC32(String2UTF8(sPasswordToken)) ^ this.m_oContext.m_iKey2) >>> 0;
                var iServerChallengeToken = (this.m_oContext.m_iKey1A1 ^ this.m_oContext.m_iKey1A2 ^ this.m_oContext.m_iKey1B1 ^ this.m_oContext.m_iKey1B2 ^ this.m_oContext.m_iKey2) >>> 0;
                AjaxRequest.Instance().Request("UAMLOGIN", this.m_oContext.m_sUserName + "," + iPasswordToken + "," + iServerChallengeToken, LoginHandlerStep2, this.m_oContext, this.m_oContext.m_iLang, null, "SYM", this.m_oContext.m_oPrivateKey1A, this.m_oContext.m_oPrivateKey1B, this.m_oContext.m_iRef);
            }
            else {
                this.m_oContext.m_iFinishFlag = 1;
                this.m_oContext.m_iResult = parseInt(arrResult[0]);
            }
        }
        else {
            //decrypt fail:
            this.m_oContext.m_iFinishFlag = 1;
            this.m_oContext.m_iResult = 602;
        }
    }
    else {
        //invalid return:
        this.m_oContext.m_iFinishFlag = 1;
        this.m_oContext.m_iResult = this.m_iLocalResult;
    }
}
function Login(oTracer) {
    oTracer.m_iFinishFlag = 0;
    oTracer.m_iKey1A1 = Math.floor(Math.random() * 4294967296) >>> 0;
    oTracer.m_iKey1A2 = Math.floor(Math.random() * 4294967296) >>> 0;
    oTracer.m_iKey1B1 = Math.floor(Math.random() * 4294967296) >>> 0;
    oTracer.m_iKey1B2 = Math.floor(Math.random() * 4294967296) >>> 0;
    oTracer.m_oPrivateKey1A = DESMakeKey(oTracer.m_iKey1A1, oTracer.m_iKey1A2);
    oTracer.m_oPrivateKey1B = DESMakeKey(oTracer.m_iKey1B1, oTracer.m_iKey1B2);
    /*
    var sAlert = "key1A1: ";
    sAlert += (((oTracer.m_oPrivateKey1A[0] << 24) | (oTracer.m_oPrivateKey1A[1] << 16) | (oTracer.m_oPrivateKey1A[2] << 8) | oTracer.m_oPrivateKey1A[3]) >>> 0).toString();
    sAlert += "\nkey1A2: ";
    sAlert += (((oTracer.m_oPrivateKey1A[4] << 24) | (oTracer.m_oPrivateKey1A[5] << 16) | (oTracer.m_oPrivateKey1A[6] << 8) | oTracer.m_oPrivateKey1A[7]) >>> 0).toString();
    sAlert += "\nkey1B1: ";
    sAlert += (((oTracer.m_oPrivateKey1B[0] << 24) | (oTracer.m_oPrivateKey1B[1] << 16) | (oTracer.m_oPrivateKey1B[2] << 8) | oTracer.m_oPrivateKey1B[3]) >>> 0).toString();
    sAlert += "\nkey1B2: ";
    sAlert += (((oTracer.m_oPrivateKey1B[4] << 24) | (oTracer.m_oPrivateKey1B[5] << 16) | (oTracer.m_oPrivateKey1B[6] << 8) | oTracer.m_oPrivateKey1B[7]) >>> 0).toString();
    alert(sAlert);
    */
    AjaxRequest.Instance().Request("UAMCHAL", "3,4," + oTracer.m_iKey1A1 + "," + oTracer.m_iKey1A2 + "," + oTracer.m_iKey1B1 + "," + oTracer.m_iKey1B2, LoginHandlerStep1, oTracer, oTracer.m_iLang, 10000, "ASYM", oTracer.m_sPublicKey1, oTracer.m_sPublicKey2);
}
function LogoutHandler() {
    this.m_oContext.m_iFinishFlag = 1;
    if (this.m_iLocalResult == 200) {
        var sResponseText;
        if (this.m_iEncryptFlag)
            sResponseText = UTF82String(TDESDecrypt(Base64Decode(this.responseText), this.m_oContext.m_oPrivateKey1A, this.m_oContext.m_oPrivateKey1B));
        else
            sResponseText = this.responseText;
        if (sResponseText) {
            //logout finished:
            this.m_oContext.m_iResult = parseInt(sResponseText);
        }
        else {
            //decrypt fail:
            this.m_oContext.m_iResult = 602;
        }
    }
    else {
        //invalid return:
        this.m_oContext.m_iResult = this.m_iLocalResult;
    }
}
function Logout(oTracer) {
    oTracer.m_iFinishFlag = 0;
    AjaxRequest.Instance().Request("UAMLOGOUT", oTracer.m_iRef, LogoutHandler, oTracer, oTracer.m_iLang, null, "SYM", oTracer.m_oPrivateKey1A, oTracer.m_oPrivateKey1B, oTracer.m_iRef);
}