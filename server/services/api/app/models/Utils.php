<?php
namespace HoneySens\app\models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ObjectRepository;
use Respect\Validation\Validator;
use Respect\Validation\Validator as V;

class Utils {

    /**
     * Shortens the given base64-encoded string to the limit given by $characters.
     * Returns a base64-encoded string.
     *
     * @param int $characters Maximum length
     * @param string $input The base64-encoded input string to shorten
     */
    static function shortenBase64(int $characters, string $input): string {
        if(strlen($input) > $characters) {
            $maxSourceLength = floor($characters / 4) * 3;
            return base64_encode(substr(base64_decode($input), 0, $maxSourceLength));
        } else return $input;
    }

    /**
     * Turns a doctrine ORM Query into a SQL statement.
     *
     * @param Query $query ORM Query to process
     * @source https://stackoverflow.com/questions/2095394/doctrine-how-to-print-out-the-real-sql-not-just-the-prepared-statement
     */
    static function getFullSQL(Query $query): string {
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
                        if(strlen($sqlArr) > 0)
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

    static function getParamsArray($paramObj): array {
        $parameters=array();
        foreach ($paramObj as $val){
            /* @var $val Doctrine\ORM\Query\Parameter */
            $parameters[$val->getName()]=$val->getValue();
        }
        return $parameters;
    }

    static function getListParamsByDql($dql): array {
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

    static function emailValidator(): Validator {
        // TLDs are optional in E-Mail addresses
        return V::regex('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+$/');
    }

    /**
     * Computes an ArrayCollection update with a list of entity IDs. Returns an array of the form
     * array('add' => array(), 'update' => array(), 'remove' => array())
     * that specifies the required tasks to complete the operation.
     *
     * @param $collection ArrayCollection of the entities to be updated
     * @param $ids array of entity IDs to update the collection with
     * @param $repository ObjectRepository to fetch entities from
     * @return array Specifies tasks to perform to perform the operation
     */
    static function updateCollection(Collection $collection, array &$ids, ObjectRepository $repository): array {
        $tasks = array('add' => array(), 'update' => array(), 'remove' => array());
        foreach($collection as $entity) {
            if(in_array($entity->getId(), $ids)) {
                $tasks['update'][] = $entity;
                if(($key = array_search($entity->getId(), $ids)) !== false) {
                    unset($ids[$key]);
                    $ids = array_values($ids);
                }
            } else {
                $tasks['remove'][] = $entity;
            }
        }
        foreach($ids as $entityId) {
            $entity = $repository->find($entityId);
            if($entity) $tasks['add'][] = $entity;
        }
        return $tasks;
    }

    /**
     * Recursively deletes all local files and directories at a given path.
     *
     * @param string $str The path to delete.
     */
    static function recursiveDelete(string $str): bool {
        if (is_file($str)) return unlink($str);
        if (is_dir($str)) {
            $scan = glob(rtrim($str, '/').'/*');
            foreach($scan as $index=>$path) self::recursiveDelete($path);
            return @rmdir($str);
        }
        return false;
    }
}
