<?php
//---------------------------
// programmer:	S.Soltani 
// create Date:	97.05
//---------------------------

/**
 * List of keys that would appear in the header of http request/response
 *
 * @author Samaneh Soltani
 *
 */
class HeaderKey {

    const PERSON_ID = "PERSON-ID";
    const IP_ADDRESS = "IP-ADDRESS";
    const SYS_KEY = "SYS-KEY";
    const USER_ROLES = "USER-ROLES";
    const USER_ID = "USER-ID";
    const H_TOKEN = "H-TOKEN";
    const API_KEY = "API-KEY";



    /**
     * @return array
     * @throws ReflectionException
     * @author Samira Ehsani
     */
    static function getConstants() {
        $oClass = new ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }

}
