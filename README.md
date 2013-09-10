# FeedAPI

FeedAPI is a [Fever](http://feedafever.com/) compatible Feed API supported by many readers such as [ReadKit](http://readkitapp.com/) or [Reeder](http://reederapp.com/).

It was built to be 100% compatible with Fever API basing on its [API Docs](http://feedafever.com/api). Sparks not included.

This tool can be considered as _stable enough_, it was tested in living environments and no bigger issues occured. Works perfectly with [ReadKit](http://readkitapp.com/).

## Requirements

* Web server daemon (nginx, apache, etc.) with PHP module
* PHP 5.3 or later
    * curl enabled
    * DOMDocument enabled
    * sqlite3 enabled
    * php-cli enabled
* sqlite3

## Installation

Clone this repository:

    git clone https://github.com/ajgon/feed-api.git feed-api

Your webserver should point to the repos `public/` directory. Also your `db/data.db` file should be writable both by the scripts and webserver. 

Install dependencies:

    php composer.phar install

Run migrations:

    vendor/bin/phpmig migrate

Next, add first user to the system:

    ./feedapi user addsuper user@email.com

The last thing is adding `feedapi feed fetch` task to your crontab. 5 minutes is a suggested time window:

    */5 * * * * /path/to/feed-api/feedapi feed fetch

Run curl to check if everything operates smoothly:

    curl --data api_key=`echo -n "user@email.com:password" | md5sum | cut -f1 -d' '` http://address.to.api/\?api

Your response should look like this:

    {"api_version":3,"auth":1,"last_refreshed_on_time":"0"}

## API usage

Since it's a one on one port of Fever API, all docs can be found here: [http://feedafever.com/api](http://feedafever.com/api).

## CLI usage

### Feeds

* `feedapi feed add http://url.to.webpage.or.feed/` - will add feed to database. If URL to page is provided, feedapi will determine all the feeds in that page and offer a choice of the feed if multiple found. If only one feed is found, it will be added automatically.
* `feedapi feed fetch` - fetches all new items for the feeds.
* `feedapi feed show` - lists all feeds in database.
* `feedapi feed remove` - allows user to delete feeds.

### Groups

* `feedapi group add group_name` - will add group to database.
* `feedapi group attach` - displays list of groups, then list of feeds. If both group and feed are chosen, selected feed will be attached to selected group.
* `feedapi group detach` - removes given feed from given group.
* `feedapi group show` - lists all groups with corresponding feeds.
* `feedapi group remove` - allows user to delete groups.

### Users

* `feedapi user add [email] <password>` - password is optional, if not provided, system will prompt for it (useful if you don't want to leave your password in `*_history` logs).
* `feedapi user addsuper [email] <password>` - same as add, but added user is a super user (he can see all the groups and feeds).
* `feedapi user attach` - displays list of users, then list of feeds. If both user and feed are chosen, selected feed will be attached to selected user (he will be able to fetch it).
* `feedapi user detach` - removes given feed from given user.
* `feedapi user show` - lists all users.
* `feedapi user remove` - removes user chosen by user.
