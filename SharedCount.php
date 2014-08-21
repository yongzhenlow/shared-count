<?php
/**
 * Looks up the number of times a given URL has been shared on major social networks.
 *
 * Current supported social networks are:
 * - Twitter
 * - Pinterest
 * - LinkedIn
 * - Facebook
 * - GooglePlus
 *
 * @author  Yong Zhen <yz@stargate.io>
 * @version  1.0.0
 */
class SharedCount
{
  private $url;
  private $api = array(
    'twitter'     => 'http://urls.api.twitter.com/1/urls/count.json?url=<url>',
    'pinterest'   => 'https://api.pinterest.com/v1/urls/count.json?callback=receiveCount&format=json&url=<url>',
    'linkedin'    => 'http://www.linkedin.com/countserv/count/share?format=json&url=<url>',
    'facebook'    => 'https://graph.facebook.com/fql?q=SELECT%20like_count,%20share_count,%20click_count,%20comment_count,%20total_count%20FROM%20link_stat%20WHERE%20url%20=%20%22<url>%22',
    'googleplus'  => 'https://clients6.google.com/rpc'
  );
  private $social_networks = array('pinterest', 'twitter', 'facebook_share', 'facebook_like', 'linkedin', 'googleplus');

  /**
   * Creates an instance
   * @param string $url URL to look up
   */
  function __construct($url)
  {
    $this->set_url($url);
  }

  /**
   * Updates the current URL in the instance
   * @param string $url URL to look up
   */
  public function set_url($url)
  {
    $this->url = $url;
    $this->update_api();
  }

  /**
   * Get the shared count of the URL for a social_network
   * Available values are 'all', 'pinterest', 'twitter', 'facebook_share', 'facebook_like', 'linkedin', 'googleplus'
   * @param  string $social_media The name of the social network
   * @return int               The shared count of the URL
   */
  public function get_count($social_network = 'all')
  {
    $count = 0;
    $method = 'get_' . $social_network;
    if(method_exists($this, $method)){
      $args = func_get_args();
      $count = isset($args[1]) ? $this->$method($args[1]) : $this->$method();
    }
    return $count;
  }

  /**
   * Get the total shared counts of the URL for a given array of social networks
   * Available values are 'pinterest', 'twitter', 'facebook_share', 'facebook_like', 'linkedin', 'googleplus'
   * If you want to get ALL available social networks, use get_count('all') instead.
   * @param  array  $arr An array of social network names.
   * @return int      The total shared count of the URL for the given social networks
   */
  public function get_sum_of($arr = array())
  {
    $sum = 0;
    if(!empty($arr)){
      foreach ($arr as $social_network) {
        if($social_network !== 'all'){
          $sum += $this->get_count($social_network);
        }
      }
    }
    return $sum;
  }

  /**
   * Updates the social network API URLs with the given URL.
   * @return null
   */
  private function update_api()
  {
    foreach($this->api as &$api){
      $api = str_replace('<url>', $this->url, $api);
    }
  }

  /**
   * Get sum of all social networks
   * @return int Shared count
   */
  private function get_all()
  {
    return $this->get_sum_of($this->social_networks);
  }

  /**
   * Get shared count for Pinterest
   * @return int Shared count
   */
  private function get_pinterest()
  {
    $response = $this->http_get($this->api['pinterest'], false);
    $response = preg_replace('/^receiveCount\((.*)\)$/', "\\1", $response);
    $response = $this->parse_response($response);
    return $response['count'];
  }

  /**
   * Get shared count for LinkedIn
   * @return int Shared Count
   */
  private function get_linkedin()
  {
    $response = $this->http_get($this->api['linkedin']);
    return $response['count'];
  }

  /**
   * Get shared count for Facebook Shares
   * @return int Shared count
   */
  private function get_facebook_share()
  {
    return $this->get_facebook('share_count');
  }

  /**
   * Get shared count for Facebook Likes
   * @return int Shared count
   */
  private function get_facebook_like()
  {
    return $this->get_facebook('like_count');
  }

  /**
   * Get a specific count for Facebook
   * Available values 'like_count', 'share_count', 'click_count', 'comment_count', 'total_count'
   * As of now, only 'like_count' and 'share_count' are exposed through 'get_facebook_share' and 'get_facebook_like'
   * @param  string $count_type Value to retrieve from the graph response
   * @return int             Shared count
   */
  private function get_facebook($count_type)
  {
    $response = $this->http_get($this->api['facebook']);
    return $response['data'][0][$count_type];
  }

  /**
   * Get shared count for Tweets
   * @return int Shared count
   */
  private function get_twitter()
  {
    $response = $this->http_get($this->api['twitter']);
    return $response['count'];
  }

  /**
   * Get shared count for Google Plus
   * @return int Shared count
   */
  private function get_googleplus()
  {
    $response = $this->http_post(array(
      'CURLOPT_URL'             => $this->api['googleplus'],
      'CURLOPT_POST'            => true,
      'CURLOPT_SSL_VERIFYPEER'  => false,
      'CURLOPT_RETURNTRANSFER'  => true,
      'CURLOPT_HTTPHEADER'      => array('Content-type: application/json'),
      'CURLOPT_POSTFIELDS'      => '[{
                                      "apiVersion":"v1",
                                      "jsonrpc":"2.0",
                                      "method":"pos.plusones.get",
                                      "id":"p",
                                      "key":"p",
                                      "params":{
                                        "nolog":true,
                                        "id":"'. $this->url .'",
                                        "source":"widget",
                                        "userId":"@viewer",
                                        "groupId":"@self"
                                      }
                                    }]'
    ));
    return intval($response[0]['result']['metadata']['globalCounts']['count']);
  }

  /**
   * Perform a HTTP GET request
   * @param  string  $url   The URL for the request
   * @param  boolean $parse Whether to parse the request using json_decode
   * @return mixed         An associative array of the response, or false if the response is invalid
   */
  private function http_get($url, $parse = true)
  {
    $response = file_get_contents($url);
    return $parse ? $this->parse_response($response) : $response;
  }

  /**
   * Perform a HTTP POST request using curl.
   * @param  array  $curlopts_data   An array containing curl options to set
   * @param  boolean $parse Whether to parse the request using json_decode
   * @return mixed         An associative array of the response, or false if the response is invalid
   */
  private function http_post($curlopts_data, $parse = true)
  {
    $ch = curl_init();
    foreach($curlopts_data as $opt_name => $opt_args){
      call_user_func_array('curl_setopt', array($ch, constant($opt_name), $opt_args));
    }
    $response = curl_exec($ch);
    curl_close($ch);

    return $parse ? $this->parse_response($response) : $response;
  }

  /**
   * Parse a given response using json_decode
   * @param  string $response Plain text response from the API
   * @return mixed         An associative array of the response, or false if the response is invalid
   */
  private function parse_response($response)
  {
    return $response ? json_decode($response, true) : false;
  }
}