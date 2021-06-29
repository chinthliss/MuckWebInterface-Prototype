## Core website
 Pages with no or minimal muck communication  
 ! Need to look at log_hosts !

### Accounts
* ~~JavaScript check~~
* ~~Terms of Service agreement~~
* ~~Log on as character with username/password~~
* (Started) Actual styling/theming
* ~~Responsive layout~~
* ~~Notices (Account-based notices)~~
  * Browser notifications (Opt-in rather than forced)
* ~~Re-add webNoAvatars option and provide access to on account page~~
* ~~Re-add webUseFullWidth option and provide access to on account page~~
* Implement Socialite for Facebook/Google login?

### AccountCurrency (Formally ECommerce)
* ~~Manage Cards~~
* ~~Single payment - card~~
* ~~Single payment - paypal~~
* ~~Subscription - card~~
* ~~Subscription - paypal~~
* ~~Card subscription processing (payment)~~
* ~~Manage Subscriptions - Admin view all~~
* ~~Manage Subscriptions - View transactions from subscription~~
* ~~Flag when card has expired and trying to make a payment~~
* ~~Notification of payment - Email~~
* ~~Notification of payment - Account notification~~

### Patreon integration
* ~~Automatically update from Patreon~~ 
* ~~Save claims in new format~~
* ~~Automatic claiming~~
* ~~Status browser~~
* ~~Badges need to be rewarded~~

## Multiplayer pages

### Character Based starting pages
* ~~Change Active Character~~
    * ~~Buy character slot~~
    * Character Order
* Character Creation
    * Referral during such (looks like this is account)
    * ~~Initial Creation (Name approval)~~
    * Initial setup
* Character Dashboard
* Character Profile 
* Avatars (Loading) (Done with placeholder for now)
* Avatars (Editing)
 
### Telnet Replacement
* Write server-based websocket process to allow telnet control
* Maybe move all websocket functionality into such
* This goal neds to be reworked due to upcoming muck WS integration

### Stuff to revisit
* Account passwords don't need to have MUCK text limitations
* When someone is over their character slot limit, characters got locked out. Could highlight/show on web.

### Stuff to do once system goes live
* Remove tos-hash-viewed from account_properties
* drop billing_sessions
* drop patreon_claims
* Rewrite muck notifications to account notifications
* Remove fullwidth preference setting and code
