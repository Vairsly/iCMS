<?php
/**
* iPHP - i PHP Framework
* Copyright (c) 2012 iiiphp.com. All rights reserved.
*
* @author coolmoo <iiiphp@qq.com>
* @site http://www.iiiphp.com
* @licence http://www.iiiphp.com/license
* @version 1.0.1
* @package iSession
* @$Id: iSession.class.php 2290 2013-11-21 03:49:19Z coolmoo $
* CREATE TABLE `sessions` (
*   `session_id` varchar(255) NOT NULL DEFAULT '',
*   `expires` int(10) unsigned NOT NULL DEFAULT '0',
*   `data` text,
*   PRIMARY KEY (`session_id`)
* ) ENGINE=MyISAM DEFAULT CHARSET=utf8
*/
class iSession {
    // session-lifetime
    public static $lifeTime = null;

    public static function open($savePath, $sessName) {
        // get session-lifetime
        if(iSession::$lifeTime === null){
            iSession::$lifeTime = get_cfg_var("session.gc_maxlifetime");
        }
        if(defined('iDB')){
            return true;
        }
        return false;
    }
    public static function close() {
        iSession::gc(ini_get('session.gc_maxlifetime'));
        // close database-connection
        return iDB::flush();
    }
    public static function read($session_id) {
        $data = iDB::value("
            SELECT data FROM ".iPHP_DB_PREFIX_TAG."sessions
            WHERE session_id = '$session_id'
            AND expires > ".time()
        );
        // return data or an empty string at failure
        if($data){
            return $data;
        }
        return '';
    }
    public static function write($session_id,$data) {
        // new session-expire-time
        $expires = time() + iSession::$lifeTime;
        // is a session with this id in the database?
        $res = iDB::row("
            SELECT * FROM ".iPHP_DB_PREFIX_TAG."sessions
            WHERE session_id = '$session_id'"
        );
        // if yes,
        if($res) {
            // ...update session-data
            $return = iDB::query("
                UPDATE ".iPHP_DB_PREFIX_TAG."sessions
                SET expires = '$expires',
                data = '$data'
                WHERE session_id = '$session_id'"
            );
            // if something happened, return true
            if($return){
                return true;
            }
        }
        // if no session-data was found,
        else {
            // create a new row
            iDB::query("
                INSERT INTO ".iPHP_DB_PREFIX_TAG."sessions
                (session_id,expires,data)
                VALUES('$session_id','$expires','$data')"
            );
            // if row was created, return true
            if(iDB::$insert_id){
                return true;
            }
        }
        // an unknown error occured
        return false;
    }
    public static function destroy($session_id) {
        // delete session-data
        $return = iDB::query("
            DELETE FROM ".iPHP_DB_PREFIX_TAG."sessions
            WHERE session_id = '$session_id'"
        );
        // if session was deleted, return true,
        if($return){
            return true;
        }
        // ...else return false
        return false;
    }
    public static function gc($sessMaxLifeTime) {
        // delete old sessions
        $return = iDB::query("
            DELETE FROM ".iPHP_DB_PREFIX_TAG."sessions
            WHERE expires < ".time()
        );
        // return affected rows
        if($return){
            return true;
        }
        // ...else return false
        return false;
    }
}
//memcached
// ini_set("session.save_handler", "memcached");
// ini_set("session.save_path", "127.0.0.1:11211");

// redis
// ini_set("session.save_handler", "redis");
// ini_set("session.save_path", "tcp://127.0.0.1:6379");
// ini_set("session.save_path", "tcp://IPADDRESS:PORT?auth=REDISPASSWORD");
// ini_set("session.save_path", "tcp://host1:6379?weight=1, tcp://host2:6379?weight=2&timeout=2.5, tcp://host3:6379?weight=2");
// ini_set("session.save_path", "unix:///var/run/redis/redis.sock?persistent=1&weight=1&database=0");
//
session_set_save_handler(
    array('iSession','open'),
    array('iSession','close'),
    array('iSession','read'),
    array('iSession','write'),
    array('iSession','destroy'),
    array('iSession','gc')
);
register_shutdown_function('session_write_close');
@session_start();
