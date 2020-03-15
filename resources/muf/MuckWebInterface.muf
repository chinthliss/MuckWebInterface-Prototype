!!@program muckwebinterface.muf
!!q
!!@reg muckwebinterface.muf=www/mwi
!!@set $www/mwi=W4
!!@set $www/mwi=L
!!@set $www/mwi=_type:noheader
!!@action mwi=#0,$www/mwi
!!@propset $www=dbref:_/www/mwi/gateway:$www/mwi

@program $www/mwi
1 999999 d
i
( Program to handle core functionality from the web interface. )
( The webendpoints within this programs are intended to be called by the web interface only so need to be signed. )
( NOTE: The content is not encrypted because of the fact both sides live on the same server. If ever that changes, then this will need to be revisted. )
( Since it's used for authentication from the webpage, it also caches session information for other programs to use. )
( Requests coming in are provided as form data with a POST request. Requests going out are plain text. This is to ensure the easiest workload on the muck.)

$def salt prog "@salt" getpropstr (Stored on prop to avoid being in source. This program is committed to a public respository so do not copy into program!)
$def allowCrossDomain 0           (Whether to allow cross-domain connections. This should only really be on during testing/development.)

$ifdef is_dev
   $def allowCrossDomain 1
$endif

$include $lib/account
$include $lib/kta/proto
$include $lib/rp

$def response400 descr "HTTP/1.1 400 Bad Request\r\n" descrnotify descr "\r\n" descrnotify
$def response401 descr "HTTP/1.1 401 Unauthorized\r\n" descrnotify descr "\r\n" descrnotify
$def response404 descr "HTTP/1.1 404 Not Found\r\n" descrnotify descr "\r\n" descrnotify
$def response503 descr "HTTP/1.1 503 Service Unavailable\r\n" descrnotify descr "\r\n" descrnotify

(Outputs the http header for an accepted response, should only be used at the point there's no chance on returning errors!)
: startAcceptedResponse
    {
        "HTTP/1.1 200 OK"
        "Server: " version strcat "" strcat
        "Connection: Close"
        "Content-Type: application/x-www-form-urlencoded"
        allowCrossDomain if "Access-Control-Allow-Origin: *" then
    }list "\r\n" array_join
    descr swap descrnotify 
    descr "\r\n" descrnotify    
;

(Default representation of a player. Presently Dbref,Name,Level,avatar,ColonSeparatedFlags)
: playerToString[ dbref:player -- str:representation ]
    player @ intostr "," strcat
    player @ name "" "," subst strcat "," strcat
    player @ truelevel intostr strcat "," strcat
    "" strcat "," strcat
    { }list
    player @ mlevel 3 > if "wizard" swap array_appenditem then
    ":" array_join strcat
;

: handleRequest_test[ arr:webcall -- ]
    startAcceptedResponse
    descr "TEST" descrnotify
; selfcall handleRequest_test

(Expects 'aid' set, returns playerToString separated by lines)
: handleRequest_getCharacters[ arr:webcall -- ]
    webcall @ "aid" array_getitem ?dup if
        startAcceptedResponse
        acct_getalts
        foreach nip
            playerToString descr swap descrnotify
        repeat
    else response400 then
; selfcall handleRequest_getCharacters

(Excepts 'name', returns 'account,[playerToString]' if one is matched or returns empty response)
: handleRequest_retrieveByCredentials[ arr:webcall -- ]
    webcall @ "name" array_getitem ?dup not if response400 exit then
    startAcceptedResponse
    pmatch dup ok? if
        dup acct_any2aid intostr "," strcat swap playerToString strcat
        descr swap descrnotify 
    else pop then
; selfcall handleRequest_retrieveByCredentials

(Excepts 'dbref' and 'password' set, returns either 'true' or 'false')
: handleRequest_validateCredentials[ arr:webcall -- ]
    webcall @ "dbref" array_getitem ?dup if atoi dbref else #-1 then var! dbref
    webcall @ "password" array_getitem ?dup not if "" then var! password
    (Since a player might have been deleted, requests with a positive valid requests are ok)
    dbref @ #-1 dbcmp not password @ and if 
        startAcceptedResponse
        dbref @ player? not if "false" else
            dbref @ password @ checkpassword if "true" else "false" then
        then
        descr swap descrnotify 
    else response400 then
; selfcall handleRequest_validateCredentials

(Expects 'amount' and 'account', returns value in account currency)
: handleRequest_usdToAccountCurrencyFor[ arr:webcall -- ]
    webcall @ "amount" array_getitem ?dup if atoi else response400 exit then
    webcall @ "account" array_getitem ?dup if acct_any2aid else pop response400 exit then
    "$www/ecommerce" match "usdToGameCurrencyFor" call
; selfcall handleRequest_usdToAccountCurrencyFor

: authenticateQuery[ arr:webcall -- bool:authenticated? ]
    webcall @ { "data" "BODY" }list array_nested_get ?dup not if "" then
    webcall @ { "data" "HeaderData" "Signature" }list array_nested_get ?dup not if 0 exit then
    swap salt strcat sha1hash
    stringcmp not
;

: queryRouter[ arr:webcall -- ]
    webcall @ authenticateQuery if
        (Convert request body to dict)
        { }dict var! parsedBody
        webcall @ { "data" "POSTData" }list array_nested_get ?dup not if "" then
        foreach (key valueArray)
            "\n" array_join
            parsedBody @ rot array_setitem parsedBody !
        repeat
        (Request should have a 'mwi_request' value)
        parsedBody @ "mwi_request" array_getitem ?dup if
            prog "handleRequest_" rot strcat
            over over cancall? if parsedBody @ rot rot call 
            else pop pop response404 exit
            then
        else 
            response400 exit
        then
    else
        response401 exit
    then
;

: main
    command @ "(WWW)" stringcmp not if
        pop
        prog "disabled" getpropstr "y" instring if
            response503
        else
            event_wait pop
            dup arraydump
            queryRouter 
        then
        exit
    then
    me @ mlevel 5 > not if "Wiz-only command." .tell exit then
    dup "down" stringcmp not if
        prog "disabled" "y" setprop
        "WebInterface Disabled." .tell
        "Ideally you should log onto the server, goto the folder with the webpage in and do 'artisan down' too." .tell
        exit
    then
    dup "up" stringcmp not if
        prog "disabled" remove_prop
        "WebInterface Enabled." .tell
        "If the webpage was taken down on the server, make sure to log into it, goto the folder with the webpage in and do 'artisan up' too." .tell
        exit
    then
    "This program only handles webcalls." .tell
    
;
.
c
q
