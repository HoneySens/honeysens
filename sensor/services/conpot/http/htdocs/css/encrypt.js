////////////////////////////////////////////////////////////////////////////////
//security supports:
//CRC 32:
var g_u32CRC32Table = [0x00000000, 0x77073096, 0xEE0E612C, 0x990951BA, 0x076DC419, 0x706AF48F, 0xE963A535, 0x9E6495A3, 0x0EDB8832, 0x79DCB8A4, 0xE0D5E91E, 0x97D2D988, 0x09B64C2B, 0x7EB17CBD, 0xE7B82D07, 0x90BF1D91, 0x1DB71064, 0x6AB020F2, 0xF3B97148, 0x84BE41DE, 0x1ADAD47D, 0x6DDDE4EB, 0xF4D4B551, 0x83D385C7, 0x136C9856, 0x646BA8C0, 0xFD62F97A, 0x8A65C9EC, 0x14015C4F, 0x63066CD9, 0xFA0F3D63, 0x8D080DF5, 0x3B6E20C8, 0x4C69105E, 0xD56041E4, 0xA2677172, 0x3C03E4D1, 0x4B04D447, 0xD20D85FD, 0xA50AB56B, 0x35B5A8FA, 0x42B2986C, 0xDBBBC9D6, 0xACBCF940, 0x32D86CE3, 0x45DF5C75, 0xDCD60DCF, 0xABD13D59, 0x26D930AC, 0x51DE003A, 0xC8D75180, 0xBFD06116, 0x21B4F4B5, 0x56B3C423, 0xCFBA9599, 0xB8BDA50F, 0x2802B89E, 0x5F058808, 0xC60CD9B2, 0xB10BE924, 0x2F6F7C87, 0x58684C11, 0xC1611DAB, 0xB6662D3D, 0x76DC4190, 0x01DB7106, 0x98D220BC, 0xEFD5102A, 0x71B18589, 0x06B6B51F, 0x9FBFE4A5, 0xE8B8D433, 0x7807C9A2, 0x0F00F934, 0x9609A88E, 0xE10E9818, 0x7F6A0DBB, 0x086D3D2D, 0x91646C97, 0xE6635C01, 0x6B6B51F4, 0x1C6C6162, 0x856530D8, 0xF262004E, 0x6C0695ED, 0x1B01A57B, 0x8208F4C1, 0xF50FC457, 0x65B0D9C6, 0x12B7E950, 0x8BBEB8EA, 0xFCB9887C, 0x62DD1DDF, 0x15DA2D49, 0x8CD37CF3, 0xFBD44C65, 0x4DB26158, 0x3AB551CE, 0xA3BC0074, 0xD4BB30E2, 0x4ADFA541, 0x3DD895D7, 0xA4D1C46D, 0xD3D6F4FB, 0x4369E96A, 0x346ED9FC, 0xAD678846, 0xDA60B8D0, 0x44042D73, 0x33031DE5, 0xAA0A4C5F, 0xDD0D7CC9, 0x5005713C, 0x270241AA, 0xBE0B1010, 0xC90C2086, 0x5768B525, 0x206F85B3, 0xB966D409, 0xCE61E49F, 0x5EDEF90E, 0x29D9C998, 0xB0D09822, 0xC7D7A8B4, 0x59B33D17, 0x2EB40D81, 0xB7BD5C3B, 0xC0BA6CAD, 0xEDB88320, 0x9ABFB3B6, 0x03B6E20C, 0x74B1D29A, 0xEAD54739, 0x9DD277AF, 0x04DB2615, 0x73DC1683, 0xE3630B12, 0x94643B84, 0x0D6D6A3E, 0x7A6A5AA8, 0xE40ECF0B, 0x9309FF9D, 0x0A00AE27, 0x7D079EB1, 0xF00F9344, 0x8708A3D2, 0x1E01F268, 0x6906C2FE, 0xF762575D, 0x806567CB, 0x196C3671, 0x6E6B06E7, 0xFED41B76, 0x89D32BE0, 0x10DA7A5A, 0x67DD4ACC, 0xF9B9DF6F, 0x8EBEEFF9, 0x17B7BE43, 0x60B08ED5, 0xD6D6A3E8, 0xA1D1937E, 0x38D8C2C4, 0x4FDFF252, 0xD1BB67F1, 0xA6BC5767, 0x3FB506DD, 0x48B2364B, 0xD80D2BDA, 0xAF0A1B4C, 0x36034AF6, 0x41047A60, 0xDF60EFC3, 0xA867DF55, 0x316E8EEF, 0x4669BE79, 0xCB61B38C, 0xBC66831A, 0x256FD2A0, 0x5268E236, 0xCC0C7795, 0xBB0B4703, 0x220216B9, 0x5505262F, 0xC5BA3BBE, 0xB2BD0B28, 0x2BB45A92, 0x5CB36A04, 0xC2D7FFA7, 0xB5D0CF31, 0x2CD99E8B, 0x5BDEAE1D, 0x9B64C2B0, 0xEC63F226, 0x756AA39C, 0x026D930A, 0x9C0906A9, 0xEB0E363F, 0x72076785, 0x05005713, 0x95BF4A82, 0xE2B87A14, 0x7BB12BAE, 0x0CB61B38, 0x92D28E9B, 0xE5D5BE0D, 0x7CDCEFB7, 0x0BDBDF21, 0x86D3D2D4, 0xF1D4E242, 0x68DDB3F8, 0x1FDA836E, 0x81BE16CD, 0xF6B9265B, 0x6FB077E1, 0x18B74777, 0x88085AE6, 0xFF0F6A70, 0x66063BCA, 0x11010B5C, 0x8F659EFF, 0xF862AE69, 0x616BFFD3, 0x166CCF45, 0xA00AE278, 0xD70DD2EE, 0x4E048354, 0x3903B3C2, 0xA7672661, 0xD06016F7, 0x4969474D, 0x3E6E77DB, 0xAED16A4A, 0xD9D65ADC, 0x40DF0B66, 0x37D83BF0, 0xA9BCAE53, 0xDEBB9EC5, 0x47B2CF7F, 0x30B5FFE9, 0xBDBDF21C, 0xCABAC28A, 0x53B39330, 0x24B4A3A6, 0xBAD03605, 0xCDD70693, 0x54DE5729, 0x23D967BF, 0xB3667A2E, 0xC4614AB8, 0x5D681B02, 0x2A6F2B94, 0xB40BBE37, 0xC30C8EA1, 0x5A05DF1B, 0x2D02EF8D];
function MakeCRC32(oUINT8Array) {
    var u32CRC = ~0 >>> 0;
    var u8TableUnitLocation;
    for (var iIndex = 0; iIndex < oUINT8Array.length; iIndex++) {
        u8TableUnitLocation = (u32CRC & 0xFF) ^ oUINT8Array[iIndex];
        u32CRC = (u32CRC >>> 8) ^ g_u32CRC32Table[u8TableUnitLocation];
    }
    //done:
    return ~u32CRC;
}

//RSA:
var N = biFromString("6E0D55867F39E77B87D8E86E916F3F0270B0A4026B49BB7EDF620AA9736FF84EC1EB77E5134C667B0D432AA55F97968CA46F93D1D9BFFDD898A2B77A8290BBECBE08B99D34E7A57A8FB324F7347D39EF699EAB1DFA88575352933A5BCEF1A09CBA43814D11F85B06505DE6B72486AD5C19DCAB1628F0B02AB5C5066642800629", 16);
var E = biFromString("25FBB", 16);
function RSACalc(oNum) {
    return biPowMod(oNum, E, N);
}
function biFromHexArray(oUINT8DataArray) {
    var i32Cursor = 0;
    var oNum = new BigInt();
    for (var i32DigitIndex = 0; i32DigitIndex < 64; ++i32DigitIndex) {
        if (i32Cursor < oUINT8DataArray.length) {
            oNum.digits[i32DigitIndex] = oUINT8DataArray[i32Cursor++];
            if (i32Cursor < oUINT8DataArray.length) {
                oNum.digits[i32DigitIndex] |= (oUINT8DataArray[i32Cursor++] << 8);
            }
            else {
                break;
            }
        }
        else {
            break;
        }
    }
    return oNum;
}
function biToHexArray(oNum) {
    var oUINT8DataArray = new Array();
    for (var i32DigitIndex = 0; i32DigitIndex < 64; ++i32DigitIndex) {
        oUINT8DataArray.push(oNum.digits[i32DigitIndex] & 0xFF);
        oUINT8DataArray.push((oNum.digits[i32DigitIndex] >>> 8) & 0xFF);
    }
    return oUINT8DataArray;
}

//DES:
//permuted choice table(PC1):
var _st_PCTable1 = [56, 48, 40, 32, 24, 16, 8, 0, 57, 49, 41, 33, 25, 17, 9, 1, 58, 50, 42, 34, 26, 18, 10, 2, 59, 51, 43, 35, 62, 54, 46, 38, 30, 22, 14, 6, 61, 53, 45, 37, 29, 21, 13, 5, 60, 52, 44, 36, 28, 20, 12, 4, 27, 19, 11, 3];
//permuted choice key(PC2):
var _st_PCTable2 = [13, 16, 10, 23, 0, 4, 2, 27, 14, 5, 20, 9, 22, 18, 11, 3, 25, 7, 15, 6, 26, 19, 12, 1, 40, 51, 30, 36, 46, 54, 29, 39, 50, 44, 32, 47, 43, 48, 38, 55, 33, 52, 45, 41, 49, 35, 28, 31];
//left rotations of PC1:
var _st_LeftShiftTable = [1, 1, 2, 2, 2, 2, 2, 2, 1, 2, 2, 2, 2, 2, 2, 1];
//initial permutation(IP):
var _st_IPTable = [57, 49, 41, 33, 25, 17, 9, 1, 59, 51, 43, 35, 27, 19, 11, 3, 61, 53, 45, 37, 29, 21, 13, 5, 63, 55, 47, 39, 31, 23, 15, 7, 56, 48, 40, 32, 24, 16, 8, 0, 58, 50, 42, 34, 26, 18, 10, 2, 60, 52, 44, 36, 28, 20, 12, 4, 62, 54, 46, 38, 30, 22, 14, 6];
//inverse intial permutation(IIP):
var _st_IIPTable = [39, 7, 47, 15, 55, 23, 63, 31, 38, 6, 46, 14, 54, 22, 62, 30, 37, 5, 45, 13, 53, 21, 61, 29, 36, 4, 44, 12, 52, 20, 60, 28, 35, 3, 43, 11, 51, 19, 59, 27, 34, 2, 42, 10, 50, 18, 58, 26, 33, 1, 41, 9, 49, 17, 57, 25, 32, 0, 40, 8, 48, 16, 56, 24];
//e-perm table:
var _st_EPTable = [31, 0, 1, 2, 3, 4, 3, 4, 5, 6, 7, 8, 7, 8, 9, 10, 11, 12, 11, 12, 13, 14, 15, 16, 15, 16, 17, 18, 19, 20, 19, 20, 21, 22, 23, 24, 23, 24, 25, 26, 27, 28, 27, 28, 29, 30, 31, 0];
//p-perm table:
var _st_PPTable = [15, 6, 19, 20, 28, 11, 27, 16, 0, 14, 22, 25, 4, 17, 30, 9, 1, 7, 23, 13, 31, 26, 2, 8, 18, 12, 29, 5, 21, 10, 3, 24];
//sand box table:
var _st_SandBox1 = [14, 4, 13, 1, 2, 15, 11, 8, 3, 10, 6, 12, 5, 9, 0, 7, 0, 15, 7, 4, 14, 2, 13, 1, 10, 6, 12, 11, 9, 5, 3, 8, 4, 1, 14, 8, 13, 6, 2, 11, 15, 12, 9, 7, 3, 10, 5, 0, 15, 12, 8, 2, 4, 9, 1, 7, 5, 11, 3, 14, 10, 0, 6, 13];
var _st_SandBox2 = [15, 1, 8, 14, 6, 11, 3, 4, 9, 7, 2, 13, 12, 0, 5, 10, 3, 13, 4, 7, 15, 2, 8, 14, 12, 0, 1, 10, 6, 9, 11, 5, 0, 14, 7, 11, 10, 4, 13, 1, 5, 8, 12, 6, 9, 3, 2, 15, 13, 8, 10, 1, 3, 15, 4, 2, 11, 6, 7, 12, 0, 5, 14, 9];
var _st_SandBox3 = [10, 0, 9, 14, 6, 3, 15, 5, 1, 13, 12, 7, 11, 4, 2, 8, 13, 7, 0, 9, 3, 4, 6, 10, 2, 8, 5, 14, 12, 11, 15, 1, 13, 6, 4, 9, 8, 15, 3, 0, 11, 1, 2, 12, 5, 10, 14, 7, 1, 10, 13, 0, 6, 9, 8, 7, 4, 15, 14, 3, 11, 5, 2, 12];
var _st_SandBox4 = [7, 13, 14, 3, 0, 6, 9, 10, 1, 2, 8, 5, 11, 12, 4, 15, 13, 8, 11, 5, 6, 15, 0, 3, 4, 7, 2, 12, 1, 10, 14, 9, 10, 6, 9, 0, 12, 11, 7, 13, 15, 1, 3, 14, 5, 2, 8, 4, 3, 15, 0, 6, 10, 1, 13, 8, 9, 4, 5, 11, 12, 7, 2, 14];
var _st_SandBox5 = [2, 12, 4, 1, 7, 10, 11, 6, 8, 5, 3, 15, 13, 0, 14, 9, 14, 11, 2, 12, 4, 7, 13, 1, 5, 0, 15, 10, 3, 9, 8, 6, 4, 2, 1, 11, 10, 13, 7, 8, 15, 9, 12, 5, 6, 3, 0, 14, 11, 8, 12, 7, 1, 14, 2, 13, 6, 15, 0, 9, 10, 4, 5, 3];
var _st_SandBox6 = [12, 1, 10, 15, 9, 2, 6, 8, 0, 13, 3, 4, 14, 7, 5, 11, 10, 15, 4, 2, 7, 12, 9, 5, 6, 1, 13, 14, 0, 11, 3, 8, 9, 14, 15, 5, 2, 8, 12, 3, 7, 0, 4, 10, 1, 13, 11, 6, 4, 3, 2, 12, 9, 5, 15, 10, 11, 14, 1, 7, 6, 0, 8, 13];
var _st_SandBox7 = [4, 11, 2, 14, 15, 0, 8, 13, 3, 12, 9, 7, 5, 10, 6, 1, 13, 0, 11, 7, 4, 9, 1, 10, 14, 3, 5, 12, 2, 15, 8, 6, 1, 4, 11, 13, 12, 3, 7, 14, 10, 15, 6, 8, 0, 5, 9, 2, 6, 11, 13, 8, 1, 4, 10, 7, 9, 5, 0, 15, 14, 2, 3, 12];
var _st_SandBox8 = [13, 2, 8, 4, 6, 15, 11, 1, 10, 9, 3, 14, 5, 0, 12, 7, 1, 15, 13, 8, 10, 3, 7, 4, 12, 5, 6, 11, 0, 14, 9, 2, 7, 11, 4, 1, 9, 12, 14, 2, 0, 6, 10, 13, 15, 3, 5, 8, 2, 1, 14, 7, 4, 10, 8, 13, 15, 12, 9, 0, 3, 5, 6, 11];
function BytesToBits(pBytesArray, pBitsArray, u32BitsCount) {
    var u8Mask = 0x80;
    var u32BitsIndex = 0;
    for (var u32Index = 0; u32Index < u32BitsCount; u32Index++) {
        pBytesArray[u32Index] = (pBitsArray[u32BitsIndex] & u8Mask) ? 1 : 0;
        u8Mask >>>= 1;
        if (0 == u8Mask) {
            u32BitsIndex++;
            u8Mask = 0x80;
        }
    }
}
function BitsToBytes(pBitsArray, pBytesArray, u32BitsCount) {
    var u8Mask = 0;
    var u32BitsIndex = -1;
    for (var u32Index = 0; u32Index < u32BitsCount; u32Index++) {
        if (0 == u8Mask) {
            u32BitsIndex++;
            pBitsArray[u32BitsIndex] = 0;
            u8Mask = 0x80;
        }
        pBitsArray[u32BitsIndex] |= pBytesArray[u32Index] ? u8Mask : 0;
        u8Mask >>>= 1;
    }
}
function DESKey56LeftShift1(pKey56) {
    var u8Temp0 = pKey56[0];
    for (var iIndex = 0; iIndex < 55; iIndex++) {
        pKey56[iIndex] = pKey56[iIndex + 1];
    }
    pKey56[55] = pKey56[27];
    pKey56[27] = u8Temp0;
}
function DESKey56LeftShift2(pKey56) {
    var u8Temp0 = pKey56[0];
    var u8Temp1 = pKey56[1];
    for (var iIndex = 0; iIndex < 54; iIndex++) {
        pKey56[iIndex] = pKey56[iIndex + 2];
    }
    pKey56[54] = pKey56[26];
    pKey56[55] = pKey56[27];
    pKey56[26] = u8Temp0;
    pKey56[27] = u8Temp1;
}
function DESSandBoxSearchHelper(pu8SandBox, u8Index, pBitsArray) {
    return pu8SandBox[(pBitsArray[u8Index] << 5) | (pBitsArray[u8Index + 5] << 4) | (pBitsArray[u8Index + 1] << 3) | (pBitsArray[u8Index + 2] << 2) | (pBitsArray[u8Index + 3] << 1) | pBitsArray[u8Index + 4]];
}
function DESSandBoxFiltHelper(pu8BytesArray, u8StartIndex, u8Count, u8Value) {
    for (var u8Index = u8Count - 1; u8Index >= 0; u8Index--) {
        //isolate low-order bit:
        pu8BytesArray[u8StartIndex + u8Index] = u8Value & 0x01;
        //remove that bit:
        u8Value >>= 1;
    }
}
function DESCalcRound(pu8LPart, pu8RPart, pu8Key) {
    //prepare:
    var u8Index;
    var au8TempLPart = new Array(32);
    for (u8Index = 0; u8Index < au8TempLPart.length; u8Index++) {
        au8TempLPart[u8Index] = pu8LPart[u8Index];
        pu8LPart[u8Index] = pu8RPart[u8Index];
    }
    //e-perm:
    var au8EPermBytesArray = new Array(48);
    for (u8Index = 0; u8Index < au8EPermBytesArray.length; u8Index++) {
        au8EPermBytesArray[u8Index] = pu8RPart[_st_EPTable[u8Index]];
    }
    //xor:
    for (u8Index = 0; u8Index < au8EPermBytesArray.length; u8Index++) {
        au8EPermBytesArray[u8Index] ^= pu8Key[u8Index];
    }
    //sand-box process:
    var au8SandOutput = new Array(32);
    DESSandBoxFiltHelper(au8SandOutput, 0, 4, DESSandBoxSearchHelper(_st_SandBox1, 0, au8EPermBytesArray));
    DESSandBoxFiltHelper(au8SandOutput, 4, 4, DESSandBoxSearchHelper(_st_SandBox2, 6, au8EPermBytesArray));
    DESSandBoxFiltHelper(au8SandOutput, 8, 4, DESSandBoxSearchHelper(_st_SandBox3, 12, au8EPermBytesArray));
    DESSandBoxFiltHelper(au8SandOutput, 12, 4, DESSandBoxSearchHelper(_st_SandBox4, 18, au8EPermBytesArray));
    DESSandBoxFiltHelper(au8SandOutput, 16, 4, DESSandBoxSearchHelper(_st_SandBox5, 24, au8EPermBytesArray));
    DESSandBoxFiltHelper(au8SandOutput, 20, 4, DESSandBoxSearchHelper(_st_SandBox6, 30, au8EPermBytesArray));
    DESSandBoxFiltHelper(au8SandOutput, 24, 4, DESSandBoxSearchHelper(_st_SandBox7, 36, au8EPermBytesArray));
    DESSandBoxFiltHelper(au8SandOutput, 28, 4, DESSandBoxSearchHelper(_st_SandBox8, 42, au8EPermBytesArray));
    //p-perm:
    for (u8Index = 0; u8Index < au8SandOutput.length; u8Index++) {
        pu8RPart[u8Index] = au8SandOutput[_st_PPTable[u8Index]];
    }
    //last xor:
    for (u8Index = 0; u8Index < au8SandOutput.length; u8Index++) {
        pu8RPart[u8Index] ^= au8TempLPart[u8Index];
    }
}
function DESInitializeKey(pKey, pSubKeyGroup) {
    //make 64 bits key:
    var au8KeyBytesArray64 = new Array(64);
    BytesToBits(au8KeyBytesArray64, pKey, au8KeyBytesArray64.length);
    //make 56 bits key:
    var u8Index;
    var au8KeyBytesArray56 = new Array(56);
    for (u8Index = 0; u8Index < au8KeyBytesArray56.length; u8Index++) {
        au8KeyBytesArray56[u8Index] = au8KeyBytesArray64[_st_PCTable1[u8Index]];
    }
    //make sub-key group:
    for (u8Index = 0; u8Index < 16; u8Index++) {
        if (1 == _st_LeftShiftTable[u8Index]) {
            //shift 1 bit:
            DESKey56LeftShift1(au8KeyBytesArray56);
        }
        else {
            //shift 2 bits:
            DESKey56LeftShift2(au8KeyBytesArray56);
        }
        for (var u8SubKeyIndex = 0; u8SubKeyIndex < 48; u8SubKeyIndex++) {
            pSubKeyGroup[u8Index][u8SubKeyIndex] = au8KeyBytesArray56[_st_PCTable2[u8SubKeyIndex]];
        }
    }
}
function DESCalc(pData, pSubKeyGroup, bEncrypt) {
    //make 64 bits data:
    var au8DataBytesArray64 = new Array(64);
    BytesToBits(au8DataBytesArray64, pData, au8DataBytesArray64.length);
    //initial perm:
    var u8Index;
    var au8DataBytesArrayIP = new Array(64);
    for (u8Index = 0; u8Index < au8DataBytesArrayIP.length; u8Index++) {
        au8DataBytesArrayIP[u8Index] = au8DataBytesArray64[_st_IPTable[u8Index]];
    }
    //make L and R part:
    var LPart = new Array(32);
    var RPart = new Array(32);
    for (u8Index = 0; u8Index < LPart.length; u8Index++) {
        LPart[u8Index] = au8DataBytesArrayIP[u8Index];
        RPart[u8Index] = au8DataBytesArrayIP[u8Index + 32];
    }
    //round:
    if (bEncrypt) {
        for (u8Index = 0; u8Index < 16; u8Index++) {
            DESCalcRound(LPart, RPart, pSubKeyGroup[u8Index]);
        }
    }
    else {
        for (u8Index = 15; u8Index >= 0; u8Index--) {
            DESCalcRound(LPart, RPart, pSubKeyGroup[u8Index]);
        }
    }
    //change back the L and R:
    for (u8Index = 0; u8Index < LPart.length; u8Index++) {
        au8DataBytesArrayIP[u8Index] = RPart[u8Index];
        au8DataBytesArrayIP[u8Index + 32] = LPart[u8Index];
    }
    //inverse initial permunate:
    for (u8Index = 0; u8Index < au8DataBytesArray64.length; u8Index++) {
        au8DataBytesArray64[u8Index] = au8DataBytesArrayIP[_st_IIPTable[u8Index]];
    }
    //make data bytes array:
    BitsToBytes(pData, au8DataBytesArray64, au8DataBytesArray64.length);
}

//security arithmetics:
function DESMakeKey(iKey1, iKey2) {
    if (iKey1 && iKey2) {
        var oKeyByteArray = new Array(8);
        oKeyByteArray[0] = (iKey1 & 0xFF000000) >>> 24;
        oKeyByteArray[1] = (iKey1 & 0x00FF0000) >>> 16;
        oKeyByteArray[2] = (iKey1 & 0x0000FF00) >>> 8;
        oKeyByteArray[3] = (iKey1 & 0x000000FF) >>> 0;
        oKeyByteArray[4] = (iKey2 & 0xFF000000) >>> 24;
        oKeyByteArray[5] = (iKey2 & 0x00FF0000) >>> 16;
        oKeyByteArray[6] = (iKey2 & 0x0000FF00) >>> 8;
        oKeyByteArray[7] = (iKey2 & 0x000000FF) >>> 0;
        return oKeyByteArray;
    }
    //invalid key:
    return null;
}
function RSAEncrypt(oUINT8DataArray) {
    /*
    var iDataSeg;
    var iIndex = 0;
    var iResultIndex = 0;
    var oResultArray = new Array();
    while(iIndex < oUINT8DataArray.length)
    {
    //prepare data segment:
    iDataSeg = oUINT8DataArray[iIndex ++ ] << 8;
    if(iIndex < oUINT8DataArray.length)
    iDataSeg |= oUINT8DataArray[iIndex ++ ];
    else
    iDataSeg |= 0;
    iDataSeg = parseInt(RSACalc(iDataSeg, u32Key, u32Mod));
    oResultArray[iResultIndex ++ ] = (iDataSeg & 0xFF000000) >>> 24;
    oResultArray[iResultIndex ++ ] = (iDataSeg & 0x00FF0000) >>> 16;
    oResultArray[iResultIndex ++ ] = (iDataSeg & 0x0000FF00) >>> 8;
    oResultArray[iResultIndex ++ ] = (iDataSeg & 0x000000FF) >>> 0;
    }
    return oResultArray;
    */
    return biToHexArray(RSACalc(biFromHexArray(oUINT8DataArray)));
}
function RSADecrypt(oUINT8DataArray) {
    //todo: need implementation
    return oUINT8DataArray;
}
function DESEncrypt(oUINT8DataArray, oUINT8KeyArray) {
    var iIndex;
    var oSubKeyGroup = new Array(16);
    for (iIndex = 0; iIndex < 16; iIndex++)
        oSubKeyGroup[iIndex] = new Array(48);
    DESInitializeKey(oUINT8KeyArray, oSubKeyGroup);
    var oDataSegment = new Array(8);
    iIndex = 0;
    var iSegIndex;
    var iStartIndex = 0;
    while (iIndex < oUINT8DataArray.length) {
        //prepare data segment:
        iStartIndex = iIndex;
        for (iSegIndex = 0; iSegIndex < 8; iSegIndex++) {
            if (iIndex < oUINT8DataArray.length)
                oDataSegment[iSegIndex] = oUINT8DataArray[iIndex++];
            else
                oDataSegment[iSegIndex] = 0;
        }
        DESCalc(oDataSegment, oSubKeyGroup, true);
        for (iSegIndex = 0; iSegIndex < 8; iSegIndex++, iStartIndex++) {
            //restore back the encrypt data:
            oUINT8DataArray[iStartIndex] = oDataSegment[iSegIndex];
        }
        if (iStartIndex > iIndex) {
            break;
        }
    }
    //reset the data array length:
    oUINT8DataArray.length = iStartIndex;
    return oUINT8DataArray;
}
function DESDecrypt(oUINT8DataArray, oUINT8KeyArray) {
    var iIndex;
    var oSubKeyGroup = new Array(16);
    for (iIndex = 0; iIndex < 16; iIndex++)
        oSubKeyGroup[iIndex] = new Array(48);
    DESInitializeKey(oUINT8KeyArray, oSubKeyGroup);
    var oDataSegment = new Array(8);
    iIndex = 0;
    var iSegIndex;
    var iStartIndex = 0;
    while (iIndex < oUINT8DataArray.length) {
        //prepare data segment:
        iStartIndex = iIndex;
        for (iSegIndex = 0; iSegIndex < 8; iSegIndex++) {
            if (iIndex < oUINT8DataArray.length)
                oDataSegment[iSegIndex] = oUINT8DataArray[iIndex++];
            else
                break;
        }
        if (iSegIndex >= 8) {
            //integrated data segment:
            DESCalc(oDataSegment, oSubKeyGroup, false);
            for (iSegIndex = 0; iSegIndex < 8; iSegIndex++, iStartIndex++) {
                //restore back the encrypt data:
                oUINT8DataArray[iStartIndex] = oDataSegment[iSegIndex];
            }
        }
        else
            break;
    }
    //reset the data array length:
    oUINT8DataArray.length = iStartIndex;
    return oUINT8DataArray;
}
function TDESEncrypt(oUINT8DataArray, oUINT8KeyArrayA, oUINT8KeyArrayB) {
    oUINT8DataArray = DESEncrypt(oUINT8DataArray, oUINT8KeyArrayA);
    oUINT8DataArray = DESDecrypt(oUINT8DataArray, oUINT8KeyArrayB);
    oUINT8DataArray = DESEncrypt(oUINT8DataArray, oUINT8KeyArrayA);
    return oUINT8DataArray;
}
function TDESDecrypt(oUINT8DataArray, oUINT8KeyArrayA, oUINT8KeyArrayB) {
    oUINT8DataArray = DESDecrypt(oUINT8DataArray, oUINT8KeyArrayA);
    oUINT8DataArray = DESEncrypt(oUINT8DataArray, oUINT8KeyArrayB);
    oUINT8DataArray = DESDecrypt(oUINT8DataArray, oUINT8KeyArrayA);
    return oUINT8DataArray;
}
function TDESEncryptByStep(oUINT8DataArray, oUINT8KeyArrayA, oUINT8KeyArrayB, iStep) {
    switch (iStep) {
        case 0:
            return DESEncrypt(oUINT8DataArray, oUINT8KeyArrayA);
            break;
        case 1:
            return DESDecrypt(oUINT8DataArray, oUINT8KeyArrayB);
            break;
        case 2:
            return DESEncrypt(oUINT8DataArray, oUINT8KeyArrayA);
            break
        default:
            break;
    }
    //fail:
    return null;
}
function TDESDecryptByStep(oUINT8DataArray, oUINT8KeyArrayA, oUINT8KeyArrayB, iStep) {
    switch (iStep) {
        case 0:
            return DESDecrypt(oUINT8DataArray, oUINT8KeyArrayA);
            break;
        case 1:
            return DESEncrypt(oUINT8DataArray, oUINT8KeyArrayB);
            break;
        case 2:
            return DESDecrypt(oUINT8DataArray, oUINT8KeyArrayA);
            break
        default:
            break;
    }
    //fail:
    return null;
}