# Snapmed

Digital tjeneste for effektiv fjerndiagnostisering av hudsykdommer.

## Domain setup

| subdomain       | domain  (inc. tld) | folder       | tech                   | server                                | note                                                                        |
| --------------- | ------------------ | ------------ | ---------------------- | ------------------------------------- | --------------------------------------------------------------------------- |
| Website         |
| ( www \| . )    | snapmed.no         | N/A          | Craft                  | Tornado (220392.web.tornado-node.net) | Project: https://gitlab.com/lastfriday/lf_snapmed_web \| **Norsk**          |
| ( www \| . )    | snapmed.co         | N/A          | Craft                  | Tornado (220392.web.tornado-node.net) | Project: https://gitlab.com/lastfriday/lf_snapmed_web \| **Internasjonal**  |
|                 |
| ( www \| . )    | snapmed.co.uk      | N/A          | Craft                  | Tornado (220392.web.tornado-node.net) | Project: https://gitlab.com/lastfriday/lf_snapmed_web \| **UK**          |
| ( www \| . )    | snapmed.se         | N/A          | Craft                  | Tornado (220392.web.tornado-node.net) | Project: https://gitlab.com/lastfriday/lf_snapmed_web \| **Sweden**  |
|                 |
| Web application |                    |              |                        |                                       | Project: https://gitlab.com/lastfriday/lf_snapmed                           |
| app             | snapmed.no         | **frontend** | Vue.js - static html   | Amazon (snapmed-02.lastfriday.no)     | Builds with static html created for both no and co TLD \| **Norsk**         |
| app             | snapmed.co         | **frontend** | Vue.js - static html   | Amazon (snapmed-02.lastfriday.no)     | Builds with static html created for both no and co TLD \| **Internasjonal** |
| app             | snapmed.co.uk      | **frontend** | Vue.js - static html   | Amazon (snapmed-02.lastfriday.no)     | Builds with static html created for both no and co TLD \| **UK** |
| app             | snapmed.se         | **frontend** | Vue.js - static html   | Amazon (snapmed-02.lastfriday.no)     | Builds with static html created for both no and co TLD \| **Sweden** |
| med             | snapmed.no         | **admin**    | Vue.js & Vuetify - SPA | Amazon (snapmed-02.lastfriday.no)     | Doctors admin page for answering cases submitted by the clients             |
| backend         | snapmed.no         | **backend**  | Lumen REST API         | Amazon (snapmed-02.lastfriday.no)     | Only available from localhost (proxied requests only)                       |
|                 |
| QA - Testing    |
| qa.app          | snapmed.no         | **frontend** | Vue.js - static html   | Amazon (snapmed-02.lastfriday.no)     | Builds with static html created for both no and co TLD \| **Norsk**         |
| qa.app          | snapmed.co         | **frontend** | Vue.js - static html   | Amazon (snapmed-02.lastfriday.no)     | Builds with static html created for both no and co TLD \| **Internasjonal** |
| qa.app          | snapmed.co.uk      | **frontend** | Vue.js - static html   | Amazon (snapmed-02.lastfriday.no)     | Builds with static html created for both no and co TLD \| **UK**         |
| qa.app          | snapmed.se         | **frontend** | Vue.js - static html   | Amazon (snapmed-02.lastfriday.no)     | Builds with static html created for both no and co TLD \| **Sweden** |
| qa.med          | snapmed.no         | **admin**    | Vue.js & Vuetify - SPA | Amazon (snapmed-02.lastfriday.no)     | Doctors admin page for answering cases submitted by the clients             |
| qa.backend      | snapmed.no         | **backend**  | Lumen REST API         | Amazon (snapmed-02.lastfriday.no)     | Only available from localhost (proxied requests only)                       |
|                 |


## Development

### Backend API

The API is divided into 2 main groups: Frontend and Admin. Below is short documentation on how to use them.

#### Frontend

##### Setup

* yarn
* Edit .env.development if needed (API_URL=http://localhost:9080 points to the API endpoint created by yarn serve in `backend/`)
* Generate keys like stated in .env file (APP_KEY= JWT_SECRET=)
* yarn serve

Navigate to http://localhost:8080/ will give 404, http://localhost:8080/check should give the chat screen.

Localhost SMS "engangskode" is 1234

You can check response in http://localhost:8080/response

http://localhost:8080/video

If you make a booking here it will go to production on snapmed.makeplans.no

Stripe test card is 42

##### User API flow

Initial flow of the user is as follows:

* `/upload` is used to upload images to the backend. UUID of the image is returned.
* `/check` is used to send in complete check along with phonenumber and password. Auth token and examination UUID is returned.
* `/verify/{TOKEN}` user is sent an sms with 6 char verification token.
* `/charge` once verified, user selects timelimit and use stripe to pay. Here we need timelimit stripe token and examination UUID to know which check the user is actually paying for.

Secondary flow for the user is as follows:

* `/login` enter phonenumber and password.
* `/verify/{TOKEN}` user is sent an sms with 6 char verification token.
* `/diagnoses` user receives all their diagnoses.

#### Cross Origin issue when service is used as an iframe

Safari has tightened their security policy when it comes to cross origin iframes. This should not represent an issue for
us, because we do not do any manipulation across the domains, all we do is send a message to the parent frame. Even though
we do not do this, we have had to utilize `document.domain` to ensure we did not get an issue in Safari. For more
information on document.domain see [here](https://developer.mozilla.org/en-US/docs/Web/API/Document/domain).

This will most likely become an issue when the iframe is to be embedded in other websites. At that point one will have
to dig deeper into the issue to find out what to do about this. Included 2 links for a little more information:

https://developer.mozilla.org/en-US/docs/Web/Security/Same-origin_policy - explanation of the same origin policy.
https://github.com/facebook/react/issues/14002 - bug in react related to the issue.

#### Admin

##### Setup admin

`nvm use`
`cd admin`
`yarn`
`cp .env.example .env`
`yarn serve`

Admin login (with the doctor you created with ´php artisan user:create` and OTP 1234)

##### Running admin

Startup: `yarn serve` in `/admin` to run it. Login with a user you create with `php artisan user:create` in `/backend` folder. OTP code is always 1234.

* `/examinations` get all examinations
* `/diagnosis` get the next available examination (or the one you are currently working on)
* `/examinations/{UUID}` get information about one examination
* `/diagnosis/{UUID}` give diagnosis for one examination (UUID)

### Backend

#### Database setup

When you need to create a new database in a new environment use the following statements. Remember to change db name,
username and password for the user.

```sql
create database lm_snapmed;
grant all on lm_snapmed.* to lm_snapmed@localhost identified by 'hemmelig';
```

`cp dev.env .env`

Edit .env file.

Run `php artisan migrate` inside backend/ folder.

Run `yarn serve` it will create server on http://127.0.0.1:9080



## CLI commands

`cd backend` -> `php artisan` for a overview.

e.g. to create a new doctor use `php artisan user:create` (minimum 6 chars password)



## Testing

Stripe test card can be found here: https://stripe.com/docs/testing#cards

### Ulike test kort som gir litt ulike feilhåndtering i backend og burde derfor brukes til testing av feil:

* 4000000000000341	Attaching this card to a Customer object succeeds, but attempts to charge the customer fail.

* 4000000000009995	Charge is declined with a card_declined code. The decline_code attribute is insufficient_funds.

* 4000000000000127	Charge is declined with an incorrect_cvc code.

* 4000000000000069	Charge is declined with an expired_card code.


Stripe checkouts on:

http://localhost:8080/video/

### BankID

On local dev environment, BANKID_TEST_SSN needs to be specified in backend/.env file (suggested value 24056619166)

For testing with this user use:

SSN: 24056619166
OTP: otp
Password: qwer1234


## Deploy on QA

$ yarn build-qa 
$ ssh snapmed
$ cd /export/www/qa.snapmed
$ git pull

## Deploy on production

**Note** there seems to be an error in the cache busting of the cms.xxx.js file -- a change needs to be done in iframe.js to be able to build a new filename. Issue here: https://snapmed.atlassian.net/browse/SNAP-149

$ yarn build-production + release into production branch
$ ssh snapmed
$ cd /export/www/snapmed_app
$ git pull

Then log into snapmed.com/admin and edit the "Entries" -> "Sevices" -> For each of the entires here change the "Target script variable" to the new script generated by webpack, ie. https://app.snapmed.no/js/cms.9e74ed3f.js
Do this for each language.

## Postdeployment

* Run `php artisan migrate` in `/backend` to perform any database changes
* Update `backend/.env` and `frontend/.env` and `admin/.env` with any changes made during development.


# Support

## Changing users's passwords

First, find the user in mysql:

`select*from users where phonenumber = '+47 xxx xx xxx';`

Then, in `backend/` run `php artisan tinker`

```
$user = App\User::where('uuid', 'e9320272-0856-4242-a291-9c63e658955a')->first();
$user->password = Illuminate\Support\Facades\Hash::make('password');
```

Make sure the correct user information is output above before saving like this:

```
$user->save();
```

Send an SMS to the user with the new password.

## Create user for oslo partner

```
php artisan create:oslo "+47 999 55 333" "123456”
```