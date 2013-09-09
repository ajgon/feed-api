# Feed-API

Feed-API is a [Fever](http://feedafever.com/) compatible Feed API supported by many readers such as [ReadKit](http://readkitapp.com/) or [Reeder](http://reederapp.com/).

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

    git clone https://github.com/ajgon/rss-api.git rss

Your webserver should point to the repos `public/` directory. Also your `db/data.db` file should be writable both by the scripts and webserver. 

Run migrations:

    vendor/bin/phpmig migrate

Next, add first user to the system:

    ./rssapi user add user@email.com

The last thing is adding `rssapi feed fetch` task to your crontab. 5 minutes is a suggested time window:

    */5 * * * * cat /home/htdocs/rzegocki.pl/rss/rssapi feed fetch

Run curl to check if everything operates smoothly:

    curl --data api_key=`echo -n "user@email.com:password" | md5sum | cut -f1 -d' '` http://address.to.api/\?api

Your response should look like this:

    {"api_version":3,"auth":1,"last_refreshed_on_time":"0"}

## API usage

Since it's a one on one port of Fever API, all docs can be found here: [http://feedafever.com/api](http://feedafever.com/api).

## CLI usage

### Feeds

* `rssapi feed add http://url.to.webpage.or.feed/` - will add feed to database. If URL to page is provided, rssapi will determine all the feeds in that page and offer a choice of the feed if multiple found. If only one feed is found, it will be added automatically.
* `rssapi feed fetch` - fetches all new items for the feeds.
* `rssapi feed show` - lists all feeds in database.
* `rssapi feed remove` - allows user to delete feeds.

### Groups

* `rssapi group add group_name` - will add group to database.
* `rssapi group attach` - displays list of groups, then list of feeds. If both group and feed are chosen, selected feed will be attached to selected group.
* `rssapi group show` - lists all groups with corresponding feeds.
* `rssapi group remove` - allows user to delete groups.
