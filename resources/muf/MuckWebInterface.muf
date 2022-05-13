!!@program muckwebinterface-gateway.muf
!!q
!!@reg muckwebinterface-gateway.muf=www/mwi
!!@set $www/mwi=W4
!!@set $www/mwi=L
!!@set $www/mwi=_type:noheader
!!@action mwi=#0,$www/mwi
!!@propset $www=dbref:_/www/mwi/gateway:$www/mwi

@program $www/mwi
1 999999 d
i
( Program to handle both incoming and outgoing requests between the MWI website and the muck.)
( This program is only intended to be used on the same machine as the server, if for some reason this ever needs to be changed a couple of things need revisiting: )
(   Requests to the muck from the server aren't encrypted. )
(   The requests are signed by SHA1 which is considered compromised. If they were exposed externally it's assumed an attacker could get the key. )
( Outgoing requests to the website are presently disabled due to networking issues. )
$pubdef :
$libdef objectToString (for testing)
$libdef sendRequestToWebpage

$def salt prog "@salt" getpropstr (Stored on prop to avoid being in source. This program is committed to a public respository so do not copy into program!)
$def allowCrossDomain 0           (Whether to allow cross-domain connections. This should only really be on during testing/development.)

$def PROP_lastConnect    "/@/ConnectTime"
$def PROP_lastDisconnect "/@/DisconnTime"

(The base url for where the muck can talk to the webpage)
$def webBaseUrl "https://beta.flexiblesurvival.com/api/muck/"
$ifdef is_dev
   $def webBaseUrl "http://mwi.flexiblesurvival.com/api/muck/"
$endif

$ifdef is_dev
   $def allowCrossDomain 1
$endif

$def parseFloatOrInt dup string? if dup "." instring if strtof else atoi then then
$include $lib/account
$include $lib/kta/proto
$include $lib/kta/json
$include $lib/kta/misc
$include $lib/kta/strings
$include $lib/httpclient
$include $lib/rp
$include $lib/accountpurchases
$include $lib/notifications
$include $lib/chargen
$include $lib/avatar

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
        "Content-Type: application/x-www-form-urlencoded; charset=windows-1252"
        allowCrossDomain if "Access-Control-Allow-Origin: *" then
    }list "\r\n" array_join
    descr swap descrnotify 
    descr "\r\n" descrnotify    
;

(Turns a muck object into a string representation in the form: dbref,creationTimestamp,name,typeFlag,metadata)
(Name and Metadata are sent enclosed in double-quotes )
(Metadata depends on the type of object and is encased in double-quotes so it can be identified easier. Present combinations:)
(   Player - aid|level|avatar|colonSeparatedFlags )
(   Zombie - level|avatar )
: objectToString[ dbref:object -- str:representation ]
    object @ intostr "," strcat (Shared start - just the dbref)
    object @ timestamps pop pop pop intostr strcat ",\"" strcat
    object @ name "\\\"" "\"" subst strcat "\"," strcat
    "" (Typeflag and metadata)
    object @ player? if
        pop "p,\""
        object @ acct_any2aid intostr strcat "|" strcat
        object @ truelevel intostr strcat "|" strcat
        object @ getAvatarInstanceStringFor "\\\"" "\"" subst strcat "|" strcat
        { }list
        object @ mlevel 5 > if (W3 and above are admin to the site)
            "admin" swap array_appenditem 
        else 
            object @ mlevel 3 > if (W1 and W2 are staff to the site)
                "staff" swap array_appenditem
            then 
        then
        object @ "approved?" getstatint not if "unapproved" swap array_appenditem then
        ":" array_join strcat
        "\"" strcat
    else (Because players can have the zombie flag)
        object @ "zombie" flag? if 
            pop "z,\""
            object @ truelevel intostr strcat "|" strcat
            object @ getAvatarInstanceStringFor "\\\"" "\"" subst strcat
            "\"" strcat
        then
    then
    ?dup not if "t," then
    strcat
; PUBLIC objectToString

( -------------------------------------------------- )
( Handlers - Nonspecific )
( -------------------------------------------------- )

: handleRequest_test[ arr:webcall -- ]
    startAcceptedResponse
    descr "TEST" descrnotify
; selfcall handleRequest_test

(Expects 'aid' set, returns objectToString separated by lines)
: handleRequest_getCharacters[ arr:webcall -- ]
    webcall @ "aid" array_getitem ?dup if
        startAcceptedResponse
        acct_getalts
        foreach nip
            objectToString descr swap descrnotify
        repeat
    else response400 then
; selfcall handleRequest_getCharacters

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

(Expects an array containing aid,dbref,password )
(Returns 'OK' if successful)
: handleRequest_changeCharacterPassword[ arr:webcall -- ]
    #-1 var! character
    webcall @ "dbref" array_getitem ?dup if
        atoi dbref character !
    then
    character @ player? not if response400 exit then
    
    0 var! aid
    webcall @ "aid" array_getitem ?dup if
        acct_any2aid aid !
    then
    aid @ not if response400 exit then
    
    webcall @ "password" array_getitem ?dup not if response400 exit then
    var! password
    
    (Does account own character?)
    character @ acct_any2aid aid @ = not if response401 exit then
    
    (Not going to allow wizard passwords to be reset by an external interface for now)
    character @ mlevel 3 > if response401 exit then
    
    startAcceptedResponse
    "[MWI Gateway] Changed password of " character @ unparseobj strcat " due to request by account " strcat aid @ intostr strcat logStatus
    character @ password @ newpassword
   
    "OK" descr swap descrnotify
; selfcall handleRequest_changeCharacterPassword

(Passes on a notification to a character to the muck if they're connected.)
(Takes an array with: aid, [character], message)
(Returns a count of notifications produced muckside)
: handleRequest_externalNotification[ arr:webcall -- ]
    0 var! aid
    webcall @ "aid" array_getitem ?dup if
        acct_any2aid aid !
    then
    aid @ not if response400 exit then
    
    0 var! character
    webcall @ "character" array_getitem ?dup if
        atoi dbref dup ok? if character ! else pop then
    then 
    
    webcall @ "message" array_getitem var! message
    message @ not if response400 exit then
    
    startAcceptedResponse
    aid @ character @ message @ deliverNotificationOnMuck
    intostr descr swap descrnotify
; selfcall handleRequest_externalNotification

( -------------------------------------------------- )
( Avatars )
( -------------------------------------------------- )

(Returns a list of what a given character owns and can access for Avatars. Because avatar items are external now, this gets passed items and their requirements so that the muck can validate them. )
(Expects 'character' and 'items' set, with items being an object of {itemId: requirementString} )
(Returns {gradients: [gradientName:[part..]..], items: {itemId: itemStatus }} with itemStatus being 1 for met requirements, 2 for owned and 3 for both)
: handleRequest_getAvatarOptionsFor[ arr:webcall -- ]
    #-1 var! character
    webcall @ "character" array_getitem ?dup if
        atoi dbref character !
    then
    character @ player? not if response400 exit then
    
    (Gradients are easy)
    character @ getOwnedGradientsFor var! gradients
    
    (Items, less so!)
    { }dict
    (Pass 1 - requirements)
    webcall @ "items" array_getitem ?dup if
        decodeJson
        foreach (S: Items Item Requirement)
            swap var! item
            character @ swap meetsRequirement? if 1 else 0 then
            ?dup if swap item @ array_setitem then
        repeat
    then
    (Pass 2 - owned items)
    character @ getOwnedItemsFor foreach 
        nip var! item
        dup item @ array_getitem ?dup not if 0 then 2 +
        swap item @ array_setitem
    repeat
    var! items
    
    character @ player? not if response400 exit then

    startAcceptedResponse
    { 
        "gradients" gradients @ 
        "items" items @
    }dict
    encodejson descr swap descrnotify
; selfcall handleRequest_getAvatarOptionsFor

(Returns an array of which infections use which avatar dolls, in the form: { dollName: [infection1.. infectionN] } )
: handleRequest_avatarDollUsage[ arr:webcall -- ]
    getAvatarDollUsage
    startAcceptedResponse
    encodejson descr swap descrnotify
; selfcall handleRequest_avatarDollUsage

(Expects an array with 'character', 'items' and 'colors' which is what to save. Returns 'OK'. )
: handleRequest_saveAvatarCustomizations[ arr:webcall -- ]
    #-1 var! character
    webcall @ "character" array_getitem ?dup if
        atoi dbref character !
    then
    character @ player? not if response400 exit then
    
    webcall @ "colors" array_getitem ?dup if decodeJson character @ swap setAvatarColors then
    webcall @ "items" array_getitem ?dup if decodeJson character @ swap setAvatarItems then
    
    startAcceptedResponse
    "OK" descr swap descrnotify
; selfcall handleRequest_saveAvatarCustomizations

(Expects {character, gradient, slot, [owner]}. Returns 'OK' or an error )
: handleRequest_buyAvatarGradient[ arr:webcall -- ]
    #-1 var! character
    webcall @ "character" array_getitem ?dup if
        atoi dbref character !
    then
    character @ player? not if response400 exit then
    
    webcall @ "gradient" array_getitem ?dup not if
        response400 exit
    then var! gradient

    webcall @ "slot" array_getitem ?dup not if
        response400 exit
    then var! slot

    webcall @ "owner" array_getitem ?dup not if 0 else atoi then var! owner
    
    startAcceptedResponse
    
    slot @ "all" stringcmp not if 10 else 5 then var! cost
    character @ cost @ "Purchased avatar gradient '" gradient @ strcat "' for " strcat 
    slot @ "all" stringcmp not if "all slots" strcat else "slot '" strcat slot @ strcat "'" strcat then 
    ", for character " strcat character @ name strcat
    makospend not if
        "Purchase failed - possibly from insufficient mako?" descr swap descrnotify exit
    then
    cost @ -1 * "Avatar" "Avatar Gradient" makolog
    character @ gradient @ slot @ addGradientTo
    
    owner @ ?dup if
        slot @ "all" stringcmp not if 2 else 1 then var! reward
        character @ reward @ -1 * "Royalties for purchase of avatar gradient '" gradient @ strcat "'" strcat makospend
        reward @ "Avatar" "Avatar Gradient" makolog
    then

    "OK" descr swap descrnotify
; selfcall handleRequest_buyAvatarGradient

(Expects {character, id, name, cost, [owner]}. Returns 'OK' or an error )
: handleRequest_buyAvatarItem[ arr:webcall -- ]
    #-1 var! character
    webcall @ "character" array_getitem ?dup if
        atoi dbref character !
    then
    character @ player? not if response400 exit then

    webcall @ "itemId" array_getitem ?dup not if
        response400 exit
    then var! itemId

    webcall @ "itemName" array_getitem ?dup not if
        response400 exit
    then var! itemName
    
    webcall @ "itemCost" array_getitem ?dup not if
        response400 exit
    then atoi var! itemCost
    
    webcall @ "owner" array_getitem ?dup not if 0 else atoi then var! owner
    
    startAcceptedResponse
    
    character @ itemCost @ "Purchased avatar item '" itemName @ strcat "' for character " strcat character @ name strcat makospend not if
        "Purchase failed - possibly from insufficient mako?" descr swap descrnotify exit
    then
    itemCost @ -1 * "Avatar" "Avatar Item" makolog
    character @ itemId @ addItemTo
    
    owner @ ?dup if
        character @ -1 "Royalties for purchase of avatar item '" itemName @ strcat "'" strcat makospend
        1 "Avatar" "Avatar Gradient" makolog
    then


    "OK" descr swap descrnotify
; selfcall handleRequest_buyAvatarItem


( -------------------------------------------------- )
( Handlers - Character Selection and Chargen )
( -------------------------------------------------- )

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

: handleRequest_buyCharacterSlot[ arr:webcall -- ]
    webcall @ "aid" array_getitem ?dup if
        startAcceptedResponse
        acct_any2aid var! account
        account @ acct_characterSlotCost var! cost

        cost @ account @ "mako" getAccountStat toint > if
            "ERROR,Insufficient " "mako" lex capital strcat " to purchase a new character slot." strcat
            descr swap descrnotify exit
        then
        
        account @ cost @ "Character Slot Purchase" makospend not if 
            "ERROR: Failed to deduct " cost @ intostr strcat " mako for purchase of character slot on account " strcat account @ intostr strcat logStatus 
            "ERROR,Something went wrong with the purchase." strcat
            descr swap descrnotify exit
        then
        
        account @ "Character Slots" getaccountstat toint 1 + account @ "Character Slots" rot setaccountstat
        cost @ -1 * "Account" "Character Slot" makolog
        
        "OK,"
        account @ acct_characterSlots intostr strcat "," strcat
        account @ acct_characterSlotCost intostr strcat
        descr swap descrnotify
    else response400 then
; selfcall handleRequest_buyCharacterSlot

(Expects 'name' set, returns a blank string if okay or a string containing an issue)
: handleRequest_findProblemsWithCharacterName[ arr:webcall -- ]
    webcall @ "name" array_getitem ?dup if var! newName
        startAcceptedResponse
        newName @ "*" swap strcat match player? if
            descr "That name is already taken." descrnotify exit
        then
        newName @ findProblemsWithCharacterName
        descr swap descrnotify
    else response400 then
; selfcall handleRequest_findProblemsWithCharacterName

(Expects 'password' set, returns a blank string if okay or a string containing an issue)
: handleRequest_findProblemsWithCharacterPassword[ arr:webcall -- ]
    webcall @ "password" array_getitem ?dup if
        startAcceptedResponse
        findProblemsWithCharacterPassword
        descr swap descrnotify
    else response400 then
; selfcall handleRequest_findProblemsWithCharacterPassword

(Expects 'name' and 'aid' set, returns OK|<InitialPassword>|<character> if successful or ERROR|<error> if there was an issue. )
: handleRequest_createCharacterForAccount[ arr:webcall -- ]
    webcall @ "aid" array_getitem ?dup if acct_any2aid else response400 exit then var! account
    webcall @ "name" array_getitem ?dup if capital else response400 exit then var! newName
    startAcceptedResponse
    
    (Ensure the account can definitely have the character at this point)
    account @ acct_CharacterSlots account @ acct_getalts array_count > not if
        descr "ERROR|No free character slots for a new character." descrnotify exit
    then
    
    8 randomPassword var! newPassword

    0 try
        newName @ newPassword @ newplayer var! newCharacter
    catch
        descr "ERROR|Something went wrong with creating the character. If this persists, please notify staff." descrnotify exit
    endcatch
    
    (Initial properties)
    newCharacter @ "player account" account @ intostr setstat
    newCharacter @ "Resources" 10 setstat
    newCharacter @ "@/initial_password" newPassword @ setprop
    newCharacter @ "@/created_by" prog setprop
    
    "OK|" newPassword @ intostr strcat "|" strcat newCharacter @ objectToString strcat
    descr swap descrnotify
; selfcall handleRequest_createCharacterForAccount

(Expects an array containing dbref,gender, birthday, faction, perks and flaws. )
(Returns 'OK' or line separated errors.)
: handleRequest_finalizeNewCharacter[ arr:webcall -- ]
    webcall @ "characterData" array_getitem ?dup if
        decodeJson
    else
        response400 exit
    then
    
    startAcceptedResponse

    approveAndApplyNewCharacterConfiguration
    
    ?dup if
        foreach nip descr swap descrnotify repeat exit
    then
   
    "OK" descr swap descrnotify
; selfcall handleRequest_finalizeNewCharacter

(Presently doesn't expect anything but as of writing the web passeses 'aid' just in case. Returns {factions, perks, flaws} with each being a dictionary of relevant objects)
(Faction: {description} )
(Perk: {description, excludes} )
(Flaw: {description, excludes} )
: handleRequest_getCharacterInitialSetupConfiguration[ arr:webcall -- ]
    startAcceptedResponse
    var workingDir
    var present
    { }dict (Result)
    
    (Factions)
    { }dict
    rpSys "/faction/" array_get_propdirs foreach nip var! present
        "/faction/" present @ strcat "/" strcat workingDir !
        rpSys workingDir @ "no chargen" strcat getpropstr "Y" instring if continue then
        { }dict
        rpSys workingdir @ "desc" strcat getpropstr swap "description" array_setitem
        swap present @ array_setitem
    repeat
    swap "factions" array_setitem
    
    (Perks)
    { }dict
    rpSys "/merit/" array_get_propdirs foreach nip var! present
        "/merit/" present @ strcat "/" strcat workingDir !
        rpSys workingDir @ "chargen" strcat getpropstr ?dup not if continue then
        { }dict
        "category" array_setitem
        rpSys workingdir @ "desc" strcat getpropstr swap "description" array_setitem
        rpSys workingdir @ "exclude" strcat getpropstr ?dup if ":" explode_array else { }list then swap "excludes" array_setitem        
        swap present @ array_setitem
    repeat
    swap "perks" array_setitem
    
    (Flaws)
    { }dict
    rpSys "/flaw/" array_get_propdirs foreach nip var! present
        "/flaw/" present @ strcat "/" strcat workingDir !
        { }dict
        rpSys workingdir @ "desc" strcat getpropstr swap "description" array_setitem
        rpSys workingdir @ "exclude" strcat getpropstr ?dup if ":" explode_array else { }list then swap "excludes" array_setitem        
        swap present @ array_setitem
    repeat
    swap "flaws" array_setitem

    
    descr swap encodeJson descrnotify
; public handleRequest_getCharacterInitialSetupConfiguration

( -------------------------------------------------- )
( Handlers - Muck object retrieval / verification    )
( -------------------------------------------------- )

(Expects 'dbref' set, returns objectToString or nothing)
: handleRequest_getByDbref[ arr:webcall -- ]
    webcall @ "dbref" array_getitem ?dup if
        startAcceptedResponse
        atoi dbref dup ok? if
            objectToString descr swap descrnotify
        else pop then
    else response400 then
; selfcall handleRequest_getByDbref

(Expects 'name' set, returns objectToString or nothing)    
: handleRequest_getByPlayerName[ arr:webcall -- ]
    webcall @ "name" array_getitem ?dup if
        startAcceptedResponse
        pmatch dup ok? if
            objectToString descr swap descrnotify
        else pop then
    else response400 then
; selfcall handleRequest_getByPlayerName

(Expects 'api_token' set, returns objectToString or nothing)    
: handleRequest_getByApiToken[ arr:webcall -- ]
    webcall @ "api_token" array_getitem ?dup if
        startAcceptedResponse
        pop (Not Implemented Yet)
    else response400 then
; selfcall handleRequest_getByApiToken

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

( -------------------------------------------------- )
( Handlers - Payment related                         )
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
( Outgoing Handling )
( -------------------------------------------------- )

(Body passed should be the data object, including mwi_request and mwi_timestamp set)
: parseBodyAndCreateSignature[ arr:body -- str:parsedBody str:signature ]
    { body @ foreach "=" swap dup string? not if intostr then strcat strcat repeat }list "&" array_join
    dup salt strcat sha1hash
;

: sendRequestToWebpage[ str:endPoint int|dbref:aidOrCharacter dict:data -- str:response int:statusCode ]
    $ifndef is_dev
        "Functionality disabled." abort
    $endif
    data @ dictionary? not if "Data must be a dictionary" abort then
    aidOrCharacter @ ?dup if
        dbref? if
            aidOrCharacter @ data @ "mwi_dbref" array_setitem data !
        then
        aidOrCharacter @ acct_any2aid ?dup if data @ "mw_user" array_setitem  data ! then
    then
    systime data @ "mwi_timestamp" array_setitem data !
    data @ parseBodyAndCreateSignature var! signature var! bodyParsed
    {
        "postdata" bodyParsed @
        "headerData" { "Signature: " signature @ strcat }list
    }dict 
    "" swap webBaseUrl endPoint @ strcat "POST" 5 httprequest_ch rot pop
; PUBLIC sendRequestToWebpage 

( -------------------------------------------------- )
( Incoming Routing )
( -------------------------------------------------- )

: verifySignatureForQuery[ arr:webcall -- bool:authenticated? ]
    webcall @ { "data" "BODY" }list array_nested_get ?dup not if "" then
    webcall @ { "data" "HeaderData" "Signature" }list array_nested_get ?dup not if 0 exit then
    swap salt strcat sha1hash
    stringcmp not
;

: queryRouter[ arr:webcall -- ]
    webcall @ verifySignatureForQuery if
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
            prog "debug" getpropstr "y" instring var! debug
            debug @ if
                "[MWI Gateway] Request: " over strcat ", Data: " strcat parsedBody @ 
                (Redact certain fields and remove internal ones)
                dup "password" array_getitem if "[Redacted]" swap "password" array_setitem then
                "mwi_timestamp" array_delitem
                encodeJson strcat logStatus
                systime_precise var! benchmarkStart
            then
            prog "handleRequest_" rot strcat
            over over cancall? if 
                parsedBody @ rot rot call
                debug @ if
                    "[MWI Gateway] Response took " systime_precise benchmarkStart @ - 1000 * "%.5f" fmtstring strcat "ms" strcat logStatus
                then
            else
                "[MWI Gateway] [WARN] Request came in for function " over strcat " but such is missing or not callable." strcat logStatus
                pop pop response404 exit
            then        
        else 
            response400 exit
        then
    else
        "[MWI Gateway] [WARN] Rejected a call because it didn't authenticate correctly. Possibly check configuration. Call was: " webCall @ encodeJson strcat logStatus
        response401 exit
    then
;

: main
    command @ "(WWW)" stringcmp not if pop
        prog "disabled" getpropstr "y" instring if
            response503
        else
            event_wait pop
            $ifdef is_dev
                dup arraydump
            $endif
            queryRouter 
        then
        exit
    then
    me @ mlevel 5 > not if "Wiz-only command." .tell exit then
    dup "down" stringcmp not if pop
        prog "disabled" "y" setprop
        "WebInterface Disabled." .tell
        "Ideally you should log onto the server, goto the folder with the webpage in and do 'php artisan down' too." .tell
        exit
    then
    dup "up" stringcmp not if pop
        prog "disabled" remove_prop
        "WebInterface Enabled." .tell
        "If the webpage was taken down on the server, make sure to log into it, goto the folder with the webpage in and do 'php artisan up' too." .tell
        exit
    then
    dup "debug" stringcmp not if pop
        prog "debug" getpropstr "y" instring if
            prog "debug" remove_prop
            "Debugging disabled." .tell
        else
            prog "debug" "y" setprop
            "Debugging enabled - requests will be logged to logwall." .tell
        then
        exit
    then
    dup "internaltest" stringcmp not if pop
        "Sending test request to ourselves." .tell
        { "mwi_request" "test" "mwi_timestamp" systime }dict var! body
        body @ parseBodyAndCreateSignature var! signature var! bodyParsed
        
        { "Signature" signature @ }dict var! header
        "http://localhost:" "wwwport" sysparm strcat "/mwi/gateway" strcat var! url

        "^CYAN^Request URL: ^WHITE^" url @ strcat .tell
        "^CYAN^Request Header" .tell header @ encodeJson .tell
        "^CYAN^Request Body" .tell body @ encodeJson .tell
        
        {
            "postdata" bodyParsed @
            "headerData" { header @ foreach ": " swap strcat strcat repeat }list
        }dict 
        "" swap url @ "POST" 5 httprequest_ch
        "__________________" .tell 
        "^CYAN^Response Status: ^WHITE^" swap intostr strcat .tell
        "^CYAN^Response Header " .tell swap .tell
        "^CYAN^Response Body " .tell .tell
        exit
    then
    dup "externaltest" stringcmp not if pop
        "Sending test to website." .tell
        "test" #21 { }dict sendRequestToWebpage
        "^CYAN^Response Code: ^WHITE^" swap intostr strcat .tell
        "^CYAN^Response: " .tell .tell
        exit
    then
    "This program only handles webcalls." .tell
    
;
.
c
q

!! @qmuf $include $www/mwi "test" #21 { }dict sendRequestToWebpage