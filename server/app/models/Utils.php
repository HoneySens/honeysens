<?php
namespace HoneySens\app\models;

use Doctrine\ORM\Query;
use Respect\Validation\Validator as V;

class Utils {

    /**
     * Shortens the given base64 string into the limit given by $characters.
     *
     * @param int $characters
     * @param string $input
     * @return string
     */
    static function shortenBase64($characters, $input) {
        if(strlen($input) > $characters) {
            $maxSourceLength = floor($characters / 4) * 3;
            return base64_encode(substr(base64_decode($input), 0, $maxSourceLength));
        } else return $input;
    }

    /**
     * Get SQL from query
     *
     * @param Query $query
     * @return int
     * @source https://stackoverflow.com/questions/2095394/doctrine-how-to-print-out-the-real-sql-not-just-the-prepared-statement
     */
    static function getFullSQL($query) {
        $sql = $query->getSql();
        $paramsList = Utils::getListParamsByDql($query->getDql());
        $paramsArr = Utils::getParamsArray($query->getParameters());
        $fullSql='';
        for($i=0;$i<strlen($sql);$i++){
            if($sql[$i]=='?'){
                $nameParam=array_shift($paramsList);

                if(is_string ($paramsArr[$nameParam])){
                    $fullSql.= '"'.addslashes($paramsArr[$nameParam]).'"';
                 }
                elseif(is_array($paramsArr[$nameParam])){
                    $sqlArr='';
                    foreach ($paramsArr[$nameParam] as $var){
                        if(!empty($sqlArr))
                            $sqlArr.=',';

                        if(is_string($var)){
                            $sqlArr.='"'.addslashes($var).'"';
                        }else
                            $sqlArr.=$var;
                    }
                    $fullSql.=$sqlArr;
                }elseif(is_object($paramsArr[$nameParam])){
                    switch(get_class($paramsArr[$nameParam])){
                        case 'DateTime':
                                 $fullSql.= "'".$paramsArr[$nameParam]->format('Y-m-d H:i:s')."'";
                              break;
                        default:
                            $fullSql.= $paramsArr[$nameParam]->getId();
                    }

                }
                else
                    $fullSql.= $paramsArr[$nameParam];

            }  else {
                $fullSql.=$sql[$i];
            }
        }
        return $fullSql;
    }

    static function getParamsArray($paramObj) {
        $parameters=array();
        foreach ($paramObj as $val){
            /* @var $val Doctrine\ORM\Query\Parameter */
            $parameters[$val->getName()]=$val->getValue();
        }
        return $parameters;
    }

    static function getListParamsByDql($dql) {
        $parsedDql = preg_split("/:/", $dql);
        $length = count($parsedDql);
        $parmeters = array();
        for($i=1;$i<$length;$i++){
            if(ctype_alpha($parsedDql[$i][0])){
                $param = (preg_split("/[' ' )]/", $parsedDql[$i]));
                $parmeters[] = $param[0];
            }
        }
        return $parmeters;
    }

    static function emailValidator() {
        // TLDs are optional in E-Mail addresses
        return V::regex('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+$/');
    }
}
