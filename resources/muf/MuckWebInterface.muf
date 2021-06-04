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

$def PROP_lastConnect    "/@/ConnectTime"
$def PROP_lastDisconnect "/@/DisconnTime"

$ifdef is_dev
   $def allowCrossDomain 1
$endif

$def parseFloatOrInt dup string? if dup "." instring if strtof else atoi then then
$include $lib/account
$include $lib/kta/proto
$include $lib/kta/json
$include $lib/kta/misc
$include $lib/rp
$include $lib/accountpurchases

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

( -------------------------------------------------- )
( Handlers - Nonspecific )
( -------------------------------------------------- )

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

(Expects 'aid' set, returns characterSlotCount,characterSlotCost )
: handleRequest_getCharacterSlotState[ arr:webcall -- ]
    webcall @ "aid" array_getitem ?dup if
        startAcceptedResponse
        acct_any2aid var! account
        account @ acct_characterSlots intostr "," strcat
        account @ acct_characterSlotCost intostr strcat
        descr swap descrnotify
    else response400 then
; selfcall handleRequest_getCharacterSlotState

(Expects 'aid' set, returns lastConnected or 0 for never connected)
: handleRequest_getLastConnect[ arr:webcall -- ]
    webcall @ "aid" array_getitem ?dup if
        startAcceptedResponse
        0 swap
        acct_getalts
        foreach nip
            dup PROP_lastConnect getprop
            swap PROP_lastDisconnect getprop
            math.max math.max
        repeat
        intostr descr swap descrnotify
    else response400 then
; selfcall handleRequest_getLastConnect

: handleRequest_findAccountsByCharacterName[ arr:webcall -- ]
    webcall @ "name" array_getitem ?dup if
        "*" swap "*" strcat strcat var! target
        startAcceptedResponse
        { }list
        #-1 target @ "P" find_array foreach nip
            acct_any2aid ?dup if intostr swap array_appenditem then
        repeat
        1 array_nunion "," array_join
        descr swap descrnotify
    else response400 then
; selfcall handleRequest_findAccountsByCharacterName

( -------------------------------------------------- )
( Handlers - Auth )
( -------------------------------------------------- )



(Expects either 'email' or 'api_token', returns 'account,[playerToString]' if one is matched or returns empty response)
(Because this is passed directly from the login form, email will actually be the character name)
: handleRequest_retrieveByCredentials[ arr:webcall -- ]
    webcall @ "email" array_getitem ?dup if
        startAcceptedResponse
        pmatch dup ok? if
            dup acct_any2aid intostr "," strcat swap playerToString strcat
            descr swap descrnotify 
        else pop then
        exit
    then
    webcall @ "api_token" array_getitem ?dup if
        startAcceptedResponse
        (TODO: Not implemented yet)
        pop
        exit
    then
    response400
; selfcall handleRequest_retrieveByCredentials

(Expects 'dbref' and 'password' set, returns either 'true' or 'false')
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

(Expects 'dbref' and 'account', returns a playerToString response if the dbref is a valid player on that account, otherwise returns empty response)
: handleRequest_verifyAccountHasCharacter[ arr:webcall -- ]
    webcall @ "account" array_getitem ?dup if acct_any2aid else response400 exit then var! account
    webcall @ "dbref" array_getitem ?dup if atoi dbref else response400 exit then var! character
    startAcceptedResponse
    character @ player? if
        character @ acct_any2aid
        account @ = if
            (Check character isn't banned)
            character @ "@banned" getpropstr not if
                character @ playerToString
                descr swap descrnotify
            then
        then
    then
; selfcall handleRequest_verifyAccountHasCharacter

( -------------------------------------------------- )
( Payment related )
( -------------------------------------------------- )

(Expects 'amount' and 'account', returns value in account currency)
: handleRequest_usdToAccountCurrencyFor[ arr:webcall -- ]
    webcall @ "amount" array_getitem ?dup if parseFloatOrInt else response400 exit then
    webcall @ "account" array_getitem ?dup if acct_any2aid else pop response400 exit then
    startAcceptedResponse
    usd2MakoFor intostr 
    descr swap descrnotify
; selfcall handleRequest_usdToAccountCurrencyFor

(Expects {account, usdAmount, accountCurrency, [subscriptionId]} returns amount actually rewarded)
: handleRequest_fulfillAccountCurrencyPurchase[ arr:webcall -- ]
    webcall @ "account" array_getitem ?dup if acct_any2aid else pop response400 exit then
    acct_aid2email (makoadjust wants such for stack order)
    webcall @ "usdAmount" array_getitem parseFloatOrInt
    webcall @ "accountCurrency" array_getitem atoi
    webcall @ "subscriptionId" array_getitem 
    makoadjust var! accountCurrencyAmount
    depth popn (Other code claims Makoadjust sometimes leaves a 1 on the stack)
    startAcceptedResponse
    accountCurrencyAmount @ intostr 
    descr swap descrnotify
; selfcall handleRequest_fulfillAccountCurrencyPurchase

(Expects {account, usdAmount, accountCurrency, itemCode}, returns currency rewarded as part of such)
: handleRequest_rewardItem[ arr:webcall -- ]
    webcall @ "account" array_getitem ?dup if acct_any2aid else pop response400 exit then var! account
    webcall @ "usdAmount" array_getitem parseFloatOrInt var! usdAmount
    webcall @ "accountCurrency" array_getitem atoi var! accountCurrency
    webcall @ "itemCode" array_getitem
    account @ swap usdAmount @ rewardItem var! free (Whether mako is awarded, still need to call makoadjust for other things)
    account @ acct_aid2email usdAmount @ accountCurrency @ 
    0 (Item purchases aren't part of a subscription)
    free @ makoAdjust var! accountCurrencyAmount
    depth popn (Other code claims Makoadjust sometimes leaves a 1 on the stack)
    startAcceptedResponse
    accountCurrencyAmount @ intostr 
    descr swap descrnotify
; selfcall handleRequest_rewardItem

(Expects {account, accountCurrency} returns amount rewarded)
: handleRequest_fulfillPatreonSupport[ arr:webcall -- ]
    webcall @ "account" array_getitem ?dup if acct_any2aid else pop response400 exit then var! account
    webcall @ "accountCurrency" array_getitem atoi dup 0 <= if pop response400 exit then var! accountCurrency
   
    account @ acct_getalts foreach nip
      "Loyal Patreon" "Thanks for supporting development through patreon!" addbadge
    repeat
    
    account @ accountCurrency @ -1 * "Patreon contributions." makospend 
    if accountCurrency @ else 0 then var! accountCurrencyRewarded
    
    depth popn (Other code claims Makoadjust sometimes leaves a 1 on the stack, duplicating here just in case)
    startAcceptedResponse
    accountCurrencyRewarded @ intostr
    descr swap descrnotify
; selfcall handleRequest_fulfillPatreonSupport

(Takes no arguments, returns stretchgoals as an array of [progress:int, goals:[amount:description]].)
: handleRequest_stretchGoals[ arr:webcall -- ]
    startAcceptedResponse
    {
        "progress" #0 "Monthly Mako" getStatInt
    }dict
    { }dict (goals)
    rpsys "stretch" array_get_propvals
    foreach
        rot rot array_setitem
    repeat
    swap "goals" array_setitem
    encodeJson
    descr swap descrnotify
; selfcall handleRequest_stretchGoals

( -------------------------------------------------- )
( Routing )
( -------------------------------------------------- )


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
        "" var! request
        webcall @ { "data" "POSTData" }list array_nested_get ?dup not if "" then
        foreach (key valueArray)
            "\n" array_join
            over "mwi_request" stringcmp not if 
                request ! pop 
            else
                parsedBody @ rot array_setitem parsedBody !
            then
        repeat
        (Request should have a 'mwi_request' value)
        request @ ?dup if
            $ifdef is_dev
                "[MWI Gateway] Request: " over strcat ", Data: " strcat parsedBody @ 
                (Redact certain fields)
                dup "password" array_getitem if "[Redacted]" swap "password" array_setitem then
                encodeJson strcat logStatus
            $endif 
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
!! Testing from tinker on server:
!! App::make(App\Muck\MuckConnection::class)->fulfillAccountCurrencyPurchase(3989,1,2,null)
!! App::make(App\Muck\MuckConnection::class)->fulfillPatreonSupport(3989,1)