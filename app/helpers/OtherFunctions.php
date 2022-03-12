<?php
function ipExternal(){
    return trim(preg_replace('/\s\s+/', ' ',file_get_contents('http://icanhazip.com/') ));

}
function ipLocal(){
    return getHostByName(getHostName());
}
function ip2l($ip){
    return ip2long($ip);;

}
function l2ip($long){
    return long2ip($long);
}
