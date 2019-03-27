<?php

namespace Bolt\Extension\Tompato\TwitterFeed;

use Bolt\Extension\SimpleExtension;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TwitterFeed extension class.
 *
 * @author Tom Bennett <tomtompato@gmail.com>
 */
class TwitterFeedExtension extends SimpleExtension
{
    protected function registerTwigFunctions() {
        return [
          'twitter_user_timeline' => ['twitterUserTimeline', ['is_variadic' => true]],
          'twitter_friends_list' => ['twitterFriendsList', ['is_variadic' => true]],
          'twitter_followers_list' => ['twitterFollowersList', ['is_variadic' => true]]
        ];
    }

    /**
     * Connect to Twitter API for user timelines and return results
     *
     * @return array
     */
    public function twitterUserTimeline(array $args = array()) {
        $app = $this->getContainer();

        // Set our request method
        $requestMethod = 'GET';

        // Create our API URL
        $baseUrl = "https://api.twitter.com/1.1/statuses/user_timeline.json";
        $fullUrl = $this->constructFullUrl($baseUrl, $args);

        // Setup our Oauth values array with values as per docs
        $oauth = $this->setOauthValues($requestMethod, $baseUrl, $args);

        // Create header string to set in our request
        $header = array($this->buildHeaderString($oauth));

        $key = 'usertimeline-'.md5($fullUrl);
        if ($app['cache']->contains($key)) {
          // If this request has been cached, retrieve it
          $result = $app['cache']->fetch($key);
        } else {
          // If not in cache then send our request to the Twitter API with appropriate Authorization header as per docs
          try {
              $result = $app['guzzle.client']->get($fullUrl, ['headers' => ['Authorization' => $header]])->getBody(true);
          } catch(\Exception $e) {
              return ['error' =>  $e->getMessage()];
          }
          // Decode the JSON that is returned
          $result = json_decode($result, true);
          $app['cache']->save($key, $result, 60);
        }

        return $result;
    }

    /**
     * Connect to Twitter API for user friends lists and return results
     *
     * @return array
     */
    public function twitterFriendsList(array $args = array()) {
        $app = $this->getContainer();

        // Set our request method
        $requestMethod = 'GET';

        // Create our API URL
        $baseUrl = "https://api.twitter.com/1.1/friends/list.json";
        $fullUrl = $this->constructFullUrl($baseUrl, $args);

        // Setup our Oauth values array with values as per docs
        $oauth = $this->setOauthValues($requestMethod, $baseUrl, $args);

        // Create header string to set in our request
        $header = array($this->buildHeaderString($oauth));

        $key = 'friendslist-'.md5($fullUrl);
        if ($app['cache']->contains($key)) {
          // If this request has been cached, retrieve it
          $result = $app['cache']->fetch($key);
        } else {
          // If not in cache then send our request to the Twitter API with appropriate Authorization header as per docs
          try {
              $result = $app['guzzle.client']->get($fullUrl, ['headers' => ['Authorization' => $header]])->getBody(true);
          } catch(\Exception $e) {
              return ['error' =>  $e->getMessage()];
          }
          // Decode the JSON that is returned
          $result = json_decode($result, true);
          $app['cache']->save($key, $result, 60);
        }

        return $result;
    }

    /**
     * Connect to Twitter API for user followers lists and return results
     *
     * @return array
     */
    public function twitterFollowersList(array $args = array()) {
        $app = $this->getContainer();

        // Set our request method
        $requestMethod = 'GET';

        // Create our API URL
        $baseUrl = "https://api.twitter.com/1.1/followers/list.json";
        $fullUrl = $this->constructFullUrl($baseUrl, $args);

        // Setup our Oauth values array with values as per docs
        $oauth = $this->setOauthValues($requestMethod, $baseUrl, $args);

        // Create header string to set in our request
        $header = array($this->buildHeaderString($oauth));

        $key = 'followerslist-'.md5($fullUrl);
        if ($app['cache']->contains($key)) {
          // If this request has been cached, retrieve it
          $result = $app['cache']->fetch($key);
        } else {
          // If not in cache then send our request to the Twitter API with appropriate Authorization header as per docs
          try {
              $result = $app['guzzle.client']->get($fullUrl, ['headers' => ['Authorization' => $header]])->getBody(true);
          } catch(\Exception $e) {
              return ['error' =>  $e->getMessage()];
          }
          // Decode the JSON that is returned
          $result = json_decode($result, true);
          $app['cache']->save($key, $result, 60);
        }

        return $result;
    }

    public function constructFullUrl($baseUrl, $args) {
        $fullUrl = $baseUrl . '?';
        foreach($args as $key => $value) {
            $fullUrl .= $key . '=' . $value;
            if(next($args)) {
                $fullUrl .= '&';
            }
        }
        return $fullUrl;
    }

    public function setOauthValues($requestMethod, $baseUrl, $args) {

        $app = $this->getContainer();

        // Get values from config
        $config = $this->getConfig();

        // Create a nonce for use with Oauth
        $nonce = base64_encode(random_bytes(16));

        // Setup our Oauth values array with values as per docs
        $oauth = array(
            'oauth_consumer_key' => $config['consumer_key'],
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $config['oauth_access_token'],
            'oauth_version' => '1.0'
        );

        // Create our signature string and key
        $signatureString = $this->buildSignatureString($requestMethod, $args, $oauth, $baseUrl);
        $signingKey = rawurlencode($config['consumer_secret']) . '&' . rawurlencode($config['oauth_access_token_secret']);

        // Create our oauth signature as per docs
        $signatureEncoded = base64_encode(hash_hmac('sha1', $signatureString, $signingKey, true));

        // Append to the Oauth values array
        $oauth['oauth_signature'] = $signatureEncoded;

        // Sort Oauth values array alphabeticaly by key
        ksort($oauth);

        return $oauth;
    }

    public function buildSignatureString($httpmethod, $params, $oauth, $baseUrl) {

        // Setup array for encoded params
        $encodedParams = array();

        // Merge this query parameters array with the Oauth values array
        $mergedParams = array_merge($params, $oauth);

        // Sort alphabetically by key
        ksort($mergedParams);

        // Percent encode all these values
        foreach($mergedParams as $key => $value) {
            $encodedParams[] = rawurlencode($key) . '=' . rawurlencode($value);
        }

        // Append method with base URL and then split out all the key/values from array and percent encode them all as per docs
        return $signature = strtoupper($httpmethod) . '&' . rawurlencode($baseUrl) . '&' . rawurlencode(implode('&', $encodedParams));
    }

    public function buildHeaderString($oauth) {
        // Header string needs 'Oauth' followed by single space
        $headerString = 'OAuth ';

        // Then we then percent encoded the key and values from Oauth values array
        foreach($oauth as $key => $value) {
            $headerString .= rawurlencode($key) . '="' . rawurlencode($value) . '"';
            // If another key/value pair in array then add comma and space character
            if(next($oauth)) {
                $headerString .= ', ';
            }
        }

        return $headerString;
    }

}
