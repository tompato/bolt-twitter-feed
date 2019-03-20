Bolt Twitter Extension
======================

This extension adds some twig functions which you can use to get data back from Twitter's API and then display this as you see fit in your templates.

To use you need to create an application in your Twitter dev account and then put your app's keys in the extensions config file. This process is explained at the Twitter docs linked below.

https://developer.twitter.com/en/docs/basics/apps/overview.html

Once you have a Twitter developer account setup and have created your application, take the following from your 'Keys and Tokens' tab on your Twitter app page.

* Consumer API Key
* Consumer API Secret Key
* Access Token
* Access Token Secret

Place these in the extensions config file in the matching fields.

You can then call the following functions in your Twig templates.

### twitter_user_timeline(args) ###

Possible arguments are described in the Twitter docs found at the following link below and should be entered as per the example.

https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-user_timeline

The example below will return the last 10 tweets from the user 'SCREEN_NAME' and can be used in your templates however you wish.

```
{% set tweets = twitter_user_timeline(screen_name = 'SCREEN_NAME', count = 10) %}
{% for tweet in tweets %}
    <ul>
        <li>
            <a target="_blank" href="https://twitter.com/{{ tweet.user.screen_name }}/status/{{ tweet.id_str }}">
            {{ tweet.text }} on {{ tweet.created_at|date(jS F Y) }}
        </li>
    </ul>
{% endfor %}
```

### twitter_friends_list(args) ###

Possible arguments are described in the Twitter docs found at the following link below and should be entered as per the example.

https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-friends-list

The example below will return up to 100 user objects which the supplied user 'SCREEN_NAME' is following and can be used in your templates as you wish.

```
{% set friends = twitter_friends_list(screen_name = 'SCREEN_NAME', count = 100) %}
{% for friend in friends.users %}
<article>
    <ul>
        <li><img src="{{ friend.profile_image_url }} ">{{ friend.name }} - <i>{{ friend.description }}</i></li>
    </ul>
</article>
{% endfor %}
```

### twitter_followers_list(args) ###

Possible arguments are described in the Twitter docs found at the following link below and should be entered as per the example.

https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-followers-list

The example below will return up to 100 user objects which the supplied user 'SCREEN_NAME' is being followed by and can be used in your templates as you wish.

```
{% set followers = twitter_followers_list(screen_name = 'SCREEN_NAME', count = 100) %}
{% for follower in followers.users %}
<article>
    <ul>
        <li><img src="{{ follower.profile_image_url }} ">{{ follower.name }} - <i>{{ follower.description }}</i></li>
    </ul>
</article>
{% endfor %}
```

If you want to see all of the available data, just run a `{{ dump(tweets) }}` in your templates and have a look through the output.

### Todos ###

* Add caching of API calls
* Add more API functions
