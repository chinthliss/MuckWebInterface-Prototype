### Account backbone (Core pages that don't require heavy muck communication)
* ~~JavaScript check~~
* ~~Terms of Service agreement~~
* ~~Log on as character with username/password~~
* (Started) Actual styling/theming
* (Started) Responsive layout
* Notices (Account-based notices)
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
* Manage Subscriptions - Admin view all
  * Exists, need further work (filters and/or pagination)
* Manage Subscriptions - View transactions from subscription
* ~~Flag when card has expired and trying to make a payment~~
* ~~Notification of payment - Email~~
* Notification of payment - Account notification

### Patreon integration
* ~~Automatically update from Patreon~~ 
* Link to Patreon account
* Save claims in new format
* Automatic claiming

### Character Based starting pages
* Change Active Character
* Avatars (Loading)
* Character Order
* Character Creation
    * Referral during such
* Character Dashboard
* Character Profile 
* Avatars (Editing)
 
### Telnet Replacement
* Write server-based websocket process to allow telnet control
* Maybe move all websocket functionality into such

### Onto individual pages..

### Stuff to do once system goes live
* Remove tos-hash-viewed from account_properties
* drop billing_sessions
* drop patreon_claims
