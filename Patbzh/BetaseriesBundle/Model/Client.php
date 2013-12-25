<?php
namespace Patbzh\BetaseriesBundle\Model;

use Buzz\Message\Request;
use Buzz\Message\Response;
use Patbzh\BetaseriesBundle\Exception\PatbzhBetaseriesException;

/**
 * Client API to make requests to Betaseries
 *
 * @author Patrick Coustans <patrick.coustans@gmail.com>
 */
class Client
{
    /**********************
     Constants definition
    **********************/ 
    const BETA_SERIES_BASE_URL = 'https://api.betaseries.com/';

    /**********************
     Attributes definition
    **********************/ 
    private $httpClient;
    private $apiVersion;
    private $apiKey;
    private $oauthKey;
    private $oauthUserToken;
    private $userAgent;

    /**********************
     Getters & setters
    **********************/ 
    public function getHttpClient() {
        return $this->httpClient;
    }

    public function setHttpClient($httpClient) {
        $this->httpClient = $httpClient;
        return $this;
    }

    public function getApiVersion() {
        return $this->apiVersion;
    }

    public function setApiVersion($apiVersion) {
        $this->apiVersion = $apiVersion;
        return $this;
    }

    public function getApiKey() {
        return $this->apiKey;
    }

    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getOauthKey() {
        return $this->oauthKey;
    }

    public function setOauthKey($oauthKey) {
        $this->oauthKey = $oauthKey;
        return $this;
    }

    public function getOauthUserToken() {
        return $this->oauthUserToken;
    }

    public function setOauthUserToken($oauthUserToken) {
        $this->oauthUserToken = $oauthUserToken;
        return $this;
    }

    public function getUserAgent() {
        return $this->userAgent;
    }

    public function setUserAgent($userAgent) {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * Generic request manager to betaseries API
     *
     * @param string $queryString Query string of the request
     * @param string $method Http method of the request
     * @param array $params Array containing param list
     *
     * @return array Response of the request ("json_decoded")
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     */
    protected function request($queryString, $method, $params=null) {
        $queryStringComplement = '';
        if($params !== null) {
            $queryStringComplement = '?'.http_build_query($params);
        }
        $request = new Request($method, $queryString.$queryStringComplement, self::BETA_SERIES_BASE_URL);
        $request->setHeaders(array(
            'Accept'=>'text/json',
            'X-BetaSeries-Version'=>$this->getApiVersion(),
            'X-BetaSeries-Key'=>$this->getApiKey(),
            'X-BetaSeries-Token'=>$this->getOauthUserToken(),
            'User-agent'=>$this->getUserAgent(),
            ));
	$response = new Response();

        $this->httpClient->send($request, $response);
        $parsedResponse = json_decode($response->getContent(), true);
        if($response->getStatusCode()==400) {
            throw new PatbzhBetaseriesException($parsedResponse['errors'][0]['text'].' ('.$parsedResponse['errors'][0]['code'].')', $parsedResponse['errors'][0]['code']);
        }

        return $parsedResponse;
    }

    public function test() {
        //$this->request('shows/display', 'GET', array('id'=>1));
        //return $this->request('members/auth', 'POST', array('login'=>'patbzh', 'password'=>md5('rohp9178'),));
        return $this->request('members/search', 'GET', array('login'=>'patbzh',));
    }

    /**********************
     Comment part: http://www.betaseries.com/api/methodes/comments
    **********************/ 

    /**
     * UNTESTED - Send a comment
     *
     * @param string $type Element type in list: episode|show|member|movie
     * @param integer $id Element id
     * @param string $text Comment
     * @param integer $in_reply_to (Optionnal) Initial comment id in case of reply
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function sendComment($type, $id, $text, $in_reply_to = null) {
        // Parameters validation
        if(!in_array($type, array('episode','show','member','movie'))) throw new \InvalidArgumentException('$type parameter should be one of this value episode|show|member|movie');
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_string($text)) throw new \InvalidArgumentException('$text should be a string');
        if(!is_null($in_reply_to) && !is_integer($in_reply_to)) throw new \InvalidArgumentException('$in_reply_to should be an integer');

        // Setting parameters
        $params = array(
            'type'=>$type,
            'id'=>$id,
            'text'=>$text,
            );
        if(isset($in_reply_to)) $params['in_reply_to'] = $in_reply_to;

        // Making Betaseries request
        return $this->request('comments/comment', 'POST', $params);
    }

    /**
     * UNTESTED - Delete a comment
     *
     * @param integer $id Element id
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function deleteComment($id) {
        // Parameters validation
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array(
            'id'=>$id,
            );

        // Making Betaseries request
        return $this->request('comments/comment', 'DELETE', $params);
    }

    /**
     * UNTESTED - Get comments
     *
     * @param string $type Element type in list: episode|show|member|movie
     * @param integer $id Element id
     * @param integer $nbpp Number of comments by page
     * @param integer $sinceId (Optionnal) Last received comment id
     * @param string $order (Optionnal - Default asc) Chronological order of the reponse: asc|desc
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getComments($type, $id, $nbpp, $sinceId=null, $order=null) {
        // Parameters validation
        if(!in_array($type, array('episode','show','member','movie'))) throw new \InvalidArgumentException('$type parameter should be one of this value episode|show|member|movie');
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_integer($nbpp)) throw new \InvalidArgumentException('$nbpp should be an integer');
        if(!is_null($sinceId) && !is_integer($sinceId)) throw new \InvalidArgumentException('$sinceId should be an integer');
        if(!is_null($order) && !in_array($order, array('asc','desc'))) throw new \InvalidArgumentException('$order parameter should be one of this value asc|desc');

        // Setting parameters
        $params = array(
            'type'=>$type,
            'id'=>$id,
            'nbpp'=>$nbpp,
            );
        if(isset($sinceId)) $params['since_id'] = $sinceId;
        if(isset($order)) $params['order'] = $order;

        // Making Betaseries request
        return $this->request('comments/comments', 'GET', $params);
    }

    /**
     * UNTESTED - Comments subscription
     *
     * @param string $type Element type in list: episode|show|member|movie
     * @param integer $id Element id
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function subscribeComment($type, $id) {
        // Parameters validation
        if(!in_array($type, array('episode','show','member','movie'))) throw new \InvalidArgumentException('$type parameter should be one of this value episode|show|member|movie');
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array(
            'type'=>$type,
            'id'=>$id,
            );

        // Making Betaseries request
        return $this->request('comments/subscription', 'POST', $params);
    }

    /**
     * UNTESTED - Comments unsubscription
     *
     * @param string $type Element type in list: episode|show|member|movie
     * @param integer $id Element id
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function unsubscribeComment($type, $id) {
        // Parameters validation
        if(!in_array($type, array('episode','show','member','movie'))) throw new \InvalidArgumentException('$type parameter should be one of this value episode|show|member|movie');
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array(
            'type'=>$type,
            'id'=>$id,
            );

        // Making Betaseries request
        return $this->request('comments/subscription', 'DELETE', $params);
    }

    /**********************
     Episode part: http://www.betaseries.com/api/methodes/episodes
    **********************/ 

    /**
     * UNTESTED - Get episode information
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     * @param ?? $subtitles (Optionnal) Display subtitles
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getEpisode($id = null, $tvdbId = null, $subtitles = null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;
        if(isset($subtitles)) $params['subtitles'] = $subtitles;

        // Making Betaseries request
        return $this->request('episodes/display', 'GET', $params);
    }

    /**
     * UNTESTED - Set an episode as downloaded
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function downloadedEpisode($id = null, $tvdbId = null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('episodes/downloaded', 'POST', $params);
    }

    /**
     * UNTESTED - Unset an episode as downloaded
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function undownloadedEpisode($id = null, $tvdbId = null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('episodes/downloaded', 'DELETE', $params);
    }

    /**
     * UNTESTED - List episodes to see
     *
     * @param string $subtitles (Optionnal) Filter list based on subtitles type: all|vovf|vo|vf
     * @param integer $limit (Optionnal) Number of listed episodes by shows
     * @param integer $showId (Optionnal) Filter list based on show
     * @param integer $userId (Optionnal) Filter list based on specific member (by default, oauth user is taken)
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function listEpisodes($subtitles=null, $limit=null, $showId=null, $userId=null) {
        // Parameters validation
        if(!is_null($subtitles) && !in_array($subtitles, array('all', 'vovf', 'vo', 'vf'))) throw new \InvalidArgumentException('$subtitles parameter should be one of this value all|vovf|vo|vf');
        if(!is_null($limit) && !is_integer($limit)) throw new \InvalidArgumentException('$limit should be an integer');
        if(!is_null($showId) && !is_integer($showId)) throw new \InvalidArgumentException('$showId should be an integer');
        if(!is_null($userId) && !is_integer($userId)) throw new \InvalidArgumentException('$userId should be an integer');

        // Setting parameters
        $params = array();
        if(isset($subtitles)) $params['subtitles'] = $subtitles;
        if(isset($limit)) $params['limit'] = $limit;
        if(isset($showId)) $params['showId'] = $showId;
        if(isset($userId)) $params['userId'] = $userId;

        // Making Betaseries request
        return $this->request('episodes/list', 'GET', $params);
    }

    /**
     * UNTESTED - Note an episode
     *
     * @param integer $note Note to give (1 to 5)
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function noteEpisode($note, $id = null, $tvdbId = null) {
        // Parameters validation
        if(!is_integer($note) || $note < 1 || $note > 5) throw new \InvalidArgumentException('$note should be an integer between 1 and 5');
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        $params['note'] = $note;
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('episodes/note', 'POST', $params);
    }

    /**
     * UNTESTED - Remove episode note
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function unnoteEpisode($id = null, $tvdbId = null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('episodes/note', 'DELETE', $params);
    }

    /**
     * UNTESTED - Get episode information based on a filename
     *
     * @param string $filename Filename to check
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function scrapeEpisode($filename) {
        // Parameters validation
        if(!is_string($filename)) throw new \InvalidArgumentException('$filename should be a string');

        // Setting parameters
        $params = array();
        $params['file'] = $filename;

        // Making Betaseries request
        return $this->request('episodes/scraper', 'GET', $params);
    }

    /**
     * UNTESTED - Get episode information
     *
     * @param integer $showId Show id
     * @param integer|string $number Episode number (integer or string format SxxExx)
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function searchEpisode($showId, $number) {
        // Parameters validation
        if(!is_integer($showId)) throw new \InvalidArgumentException('$showId should be an integer');
        if(!is_integer($number)) {
            if(!preg_match('/S[0-9]{2}E[0-9]{2}/', $number)) {
                throw new \InvalidArgumentException('$number should be an integer or looks like SxxExx');
            }
        }

        // Setting parameters
        $params = array(
            'showId'=>$showId,
            'number'=>$number,
            );

        // Making Betaseries request
        return $this->request('episodes/search', 'GET', $params);
    }

    /**
     * UNTESTED - Set an episode as seen
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     * @param boolean $bulk (Optionnal) Set all previous episodes as seen
     * @param boolean $delete (Optionnal) Set all next episodes as unseen
     * @param integer $note (Optionnal) Set a note to the episode
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function watchedEpisode($id=null, $tvdbId=null, $bulk=null, $delete=null, $note=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');
        if(!is_null($bulk) && !is_bool($bulk)) throw new \InvalidArgumentException('$bulk should be a boolean');
        if(!is_null($delete) && !is_bool($delete)) throw new \InvalidArgumentException('$delete should be a boolean');
        if(!is_integer($note) || $note < 1 || $note > 5) throw new \InvalidArgumentException('$note should be an integer between 1 and 5');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;
        if(isset($bulk)) $params['bulk'] = $bulk;
        if(isset($delete)) $params['delete'] = $delete;
        if(isset($note)) $params['note'] = $note;

        // Making Betaseries request
        return $this->request('episodes/watched', 'POST', $params);
    }

    /**
     * UNTESTED - Set an episode as unseen
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function unwatchedEpisode($id = null, $tvdbId = null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('episodes/watched', 'POST', $params);
    }

    /**********************
     Friends part: http://www.betaseries.com/api/methodes/friends
    **********************/ 

    /**
     * UNTESTED - Block a user
     *
     * @param integer $id User id to block
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function blockUser($id) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;

        // Making Betaseries request
        return $this->request('friends/block', 'POST', $params);
    }

    /**
     * UNTESTED - Unblock a user
     *
     * @param integer $id User id to unblock
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function unblockUser($id) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;

        // Making Betaseries request
        return $this->request('friends/block', 'DELETE', $params);
    }

    /**
     * UNTESTED - Add a user as a friend
     *
     * @param integer $id User id to add
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function addFriend($id) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;

        // Making Betaseries request
        return $this->request('friends/friend', 'POST', $params);
    }

    /**
     * UNTESTED - Remove a user as a friend
     *
     * @param integer $id User id to block
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function removeFriend($id) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;

        // Making Betaseries request
        return $this->request('friends/friend', 'DELETE', $params);
    }

    /**
     * UNTESTED - Get user friend list
     *
     * @param blocked $blocked (Optionnal) Indicates if blocked users list should be sent
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getFriendList($blocked) {
        // Parameters validation
        if(!is_null($blocked) && !is_bool($blocked)) throw new \InvalidArgumentException('$blocked should be a boolean');

        // Setting parameters
        $params = array();
        $params['blocked'] = $blocked;

        // Making Betaseries request
        return $this->request('friends/list', 'GET', $params);
    }

    /**
     * UNTESTED - Get user friend requests
     *
     * @param received $received (Optionnal) Indicates if received users list should be sent
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getFriendRequests($received) {
        // Parameters validation
        if(!is_null($received) && !is_bool($received)) throw new \InvalidArgumentException('$received should be a boolean');

        // Setting parameters
        $params = array();
        $params['received'] = $received;

        // Making Betaseries request
        return $this->request('friends/requests', 'GET', $params);
    }

    /**********************
     Timeline part: http://www.betaseries.com/api/methodes/timeline
    **********************/ 

    /**
     * UNTESTED - Get friends timeline
     *
     * @param integer $nbpp Number of elements
     * @param integer $sinceId (Optionnal) Last received id
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function friendsTimeline($nbpp, $sinceId=null) {
        // Parameters validation
        if(!is_integer($nbpp)) throw new \InvalidArgumentException('$nbpp should be an integer');
        if(!is_null($sinceId) && !is_integer($sinceId)) throw new \InvalidArgumentException('$sinceId should be an integer');

        // Setting parameters
        $params = array();
        $params['nbpp'] = $nbpp;
        if(isset($sinceId)) $params['since_id'] = $sinceId;

        // Making Betaseries request
        return $this->request('timeline/friends', 'GET', $params);
    }

    /**
     * UNTESTED - Get home timeline
     *
     * @param integer $nbpp Number of elements
     * @param integer $sinceId (Optionnal) Last received id
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function homeTimeline($nbpp, $sinceId=null) {
        // Parameters validation
        if(!is_integer($nbpp)) throw new \InvalidArgumentException('$nbpp should be an integer');
        if(!is_null($sinceId) && !is_integer($sinceId)) throw new \InvalidArgumentException('$sinceId should be an integer');

        // Setting parameters
        $params = array();
        $params['nbpp'] = $nbpp;
        if(isset($sinceId)) $params['since_id'] = $sinceId;

        // Making Betaseries request
        return $this->request('timeline/home', 'GET', $params);
    }

    /**
     * UNTESTED - Get member timeline
     *
     * @param integer $id Member id
     * @param integer $nbpp Number of elements
     * @param integer $sinceId (Optionnal) Last received id
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function memberTimeline($id, $nbpp, $sinceId=null) {
        // Parameters validation
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_integer($nbpp)) throw new \InvalidArgumentException('$nbpp should be an integer');
        if(!is_null($sinceId) && !is_integer($sinceId)) throw new \InvalidArgumentException('$sinceId should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;
        $params['nbpp'] = $nbpp;
        if(isset($sinceId)) $params['since_id'] = $sinceId;

        // Making Betaseries request
        return $this->request('timeline/member', 'GET', $params);
    }

    /**********************
     Planning part: http://www.betaseries.com/api/methodes/planning
    **********************/ 

    /**
     * UNTESTED - Get user planning
     *
     * @param \DateTime $date (Optionnal - default "now") Base date
     * @param integer $before (Optionnal - default 8) Number of days to display before the base date
     * @param integer $after (Optionnal - default 8) Number of days to display after the base date
     * @param string $type (Optionnal - default all) Type of episode to be displayed (all|premieres)
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function generalPlanning($date=null, $before=null, $after=null, $type=null) {
        // Parameters validation
        if(!is_null($before) && !is_integer($before)) throw new \InvalidArgumentException('$before should be an integer');
        if(!is_null($after) && !is_integer($after)) throw new \InvalidArgumentException('$after should be an integer');
        if(!is_null($date) && !($date instanceof \DateTime)) throw new \InvalidArgumentException('$date should be a \DateTime');
        if(!is_null($type) && !in_array($type, array('all', 'premieres'))) throw new \InvalidArgumentException('$type parameter should be one of this value all|premieres');

        // Setting parameters
        $params = array();
        if(isset($before)) $params['before'] = $before;
        if(isset($after)) $params['after'] = $after;
        if(isset($type)) $params['type'] = $type;
        if(isset($date)) $params['date'] = $date->format('Y-m-d');

        // Making Betaseries request
        return $this->request('planning/general', 'GET', $params);
    }

    /**
     * UNTESTED - Get next "only first" episodes of each show
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function incomingPlanning() {
        // Parameters validation

        // Setting parameters

        // Making Betaseries request
        return $this->request('planning/incoming', 'GET');
    }

    /**
     * UNTESTED - Get member planning
     *
     * @param boolean $unseen Display only unseen episodes
     * @param integer $id (Optionnal - default identified user) Member id to check
     * @param \DateTime $month (Optionnal) Month to be displayed
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function memberPlanning($unseen=null, $id=null, $month=null) {
        // Parameters validation
        if(!is_null($unseen) && !is_bool($unseen)) throw new \InvalidArgumentException('$unseen should be a boolean');
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($month) && !($month instanceof \DateTime)) throw new \InvalidArgumentException('$date should be a \DateTime');

        // Setting parameters
        $params = array();
        if(isset($unseen)) $params['unseen'] = $unseen;
        if(isset($id)) $params['id'] = $id;
        if(isset($month)) $params['month'] = $month->format('Y-m');

        // Making Betaseries request
        return $this->request('planning/member', 'GET', $params);
    }

    /**********************
     Subtitle part: http://www.betaseries.com/api/methodes/subtitles
    **********************/ 

    /**
     * UNTESTED - Get subtitles for a specified episode
     *
     * @param integer $id Episode id
     * @param string $language (Optionnal - default all) Filter list based on subtitles type: all|vovf|vo|vf
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getEpisodeSubtitles($id, $language=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($language) && !in_array($language, array('all', 'vovf', 'vo', 'vf'))) throw new \InvalidArgumentException('$language parameter should be one of this value all|vovf|vo|vf');

        // Setting parameters
        $params = array();
        $params['id'] = $id;
        if(isset($language)) $params['language'] = $language;

        // Making Betaseries request
        return $this->request('subtitles/episode', 'GET', $params);
    }

    /**
     * UNTESTED - Get last subtitles
     *
     * @param integer $number (Optionnal - default 100) Number of subtitles to find
     * @param string $language (Optionnal - default all) Filter list based on subtitles type: all|vovf|vo|vf
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getLastSubtitles($number, $language=null) {
        // Parameters validation
        if(!is_null($number) && !is_integer($number)) throw new \InvalidArgumentException('$number should be an integer');
        if(!is_null($language) && !in_array($language, array('all', 'vovf', 'vo', 'vf'))) throw new \InvalidArgumentException('$language parameter should be one of this value all|vovf|vo|vf');

        // Setting parameters
        $params = array();
        if(isset($number)) $params['number'] = $number;
        if(isset($language)) $params['language'] = $language;

        // Making Betaseries request
        return $this->request('subtitles/last', 'GET', $params);
    }

    /**
     * UNTESTED - Report incorrect subtitle
     *
     * @param integer $id Subtitle identifier
     * @param string $reason Reason why the subtitle is considered incorrect
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function reportSubtitle($id, $reason) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($reason) && !is_string($reason)) throw new \InvalidArgumentException('$reason parameter should be a string');

        // Setting parameters
        $params = array();
        $params['id'] = $id;
        $params['reason'] = $reason;

        // Making Betaseries request
        return $this->request('subtitles/report', 'POST', $params);
    }

    /**
     * UNTESTED - Get a show subtitles
     *
     * @param integer $id Show id
     * @param string $language (Optionnal - default all) Filter list based on subtitles type: all|vovf|vo|vf
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getShowSubtitles($id, $language=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($language) && !in_array($language, array('all', 'vovf', 'vo', 'vf'))) throw new \InvalidArgumentException('$language parameter should be one of this value all|vovf|vo|vf');

        // Setting parameters
        $params = array();
        $params['id'] = $id;
        if(isset($language)) $params['language'] = $language;

        // Making Betaseries request
        return $this->request('subtitles/show', 'GET', $params);
    }

    /**********************
     Message part: http://www.betaseries.com/api/methodes/messages
    **********************/ 

    /**
     * UNTESTED - Get discussion messages
     *
     * @param integer $id First message id
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getDiscussionMessages($id) {
        // Parameters validation
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;

        // Making Betaseries request
        return $this->request('messages/discussion', 'GET', $params);
    }

    /**
     * UNTESTED - Get inbox message
     *
     * @param integer $page Get inbox messages (20 messages by page)
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getInboxMessages($page) {
        // Parameters validation
        if(!is_integer($page)) throw new \InvalidArgumentException('$page should be an integer');

        // Setting parameters
        $params = array();
        $params['page'] = $page;

        // Making Betaseries request
        return $this->request('messages/inbox', 'GET', $params);
    }

    /**
     * UNTESTED - Remove message
     *
     * @param integer $id Message id
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function removeMessage($id) {
        // Parameters validation
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;

        // Making Betaseries request
        return $this->request('messages/message', 'GET', $params);
    }

    /**
     * UNTESTED - Set a message as read
     *
     * @param integer $id Message id
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function readMessage($id) {
        // Parameters validation
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;

        // Making Betaseries request
        return $this->request('messages/read', 'GET', $params);
    }

    /**
     * UNTESTED - Write a new message
     *
     * @param integer $to Target user id
     * @param string $text Message text
     * @param string $title (Optionnal - Mandatory if first message) Message subject
     * @param integer $id (Optionnal) First message id
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function writeMessage($to, $text, $title=null, $id=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($to) && !is_string($to)) throw new \InvalidArgumentException('$to should be a string');
        if(!is_null($text) && !is_integer($text)) throw new \InvalidArgumentException('$text should be a string');
        if(!is_null($title) && !is_integer($title)) throw new \InvalidArgumentException('$title should be a string');

        if(is_null($id) && is_null($title)) throw new \InvalidArgumentException('At least $id or $title should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($to)) $params['to'] = $to;
        if(isset($text)) $params['text'] = $text;
        if(isset($title)) $params['title'] = $title;

        // Making Betaseries request
        return $this->request('messages/message', 'POST', $params);
    }

    /**********************
     Picture part: http://www.betaseries.com/api/methodes/pictures
    **********************/ 

    /**
     * UNTESTED - Get badge picture
     *
     * @param integer $id Badge id
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getBadgePicture($id) {
        // Parameters validation
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;

        // Making Betaseries request
        return $this->request('pictures/badges', 'GET', $params);
    }

    /**
     * UNTESTED - Get character picture
     *
     * @param integer $id Character id
     * @param integer $width (Optionnal) Image width
     * @param integer $height (Optionnal) Image height
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getCharacterPicture($id, $width=null, $height=null) {
        // Parameters validation
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($width) && !is_integer($width)) throw new \InvalidArgumentException('$width should be an integer');
        if(!is_null($height) && !is_integer($height)) throw new \InvalidArgumentException('$height should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;
        if(isset($width)) $params['width'] = $width;
        if(isset($height)) $params['height'] = $height;

        // Making Betaseries request
        return $this->request('pictures/characters', 'GET', $params);
    }

    /**
     * UNTESTED - Get episode picture
     *
     * @param integer $id Episode id
     * @param integer $width (Optionnal) Image width
     * @param integer $height (Optionnal) Image height
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getEpisodePicture($id, $width=null, $height=null) {
        // Parameters validation
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($width) && !is_integer($width)) throw new \InvalidArgumentException('$width should be an integer');
        if(!is_null($height) && !is_integer($height)) throw new \InvalidArgumentException('$height should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;
        if(isset($width)) $params['width'] = $width;
        if(isset($height)) $params['height'] = $height;

        // Making Betaseries request
        return $this->request('pictures/episodes', 'GET', $params);
    }

    /**
     * UNTESTED - Get episode picture
     *
     * @param integer $id Member id
     * @param integer $width (Optionnal) Image width
     * @param integer $height (Optionnal) Image height
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getMemberPicture($id, $width=null, $height=null) {
        // Parameters validation
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($width) && !is_integer($width)) throw new \InvalidArgumentException('$width should be an integer');
        if(!is_null($height) && !is_integer($height)) throw new \InvalidArgumentException('$height should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;
        if(isset($width)) $params['width'] = $width;
        if(isset($height)) $params['height'] = $height;

        // Making Betaseries request
        return $this->request('pictures/members', 'GET', $params);
    }

    /**
     * UNTESTED - Get show picture
     *
     * @param integer $id Show id
     * @param integer $width (Optionnal) Image width
     * @param integer $height (Optionnal) Image height
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getShowPicture($id, $width=null, $height=null) {
        // Parameters validation
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($width) && !is_integer($width)) throw new \InvalidArgumentException('$width should be an integer');
        if(!is_null($height) && !is_integer($height)) throw new \InvalidArgumentException('$height should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;
        if(isset($width)) $params['width'] = $width;
        if(isset($height)) $params['height'] = $height;

        // Making Betaseries request
        return $this->request('pictures/shows', 'GET', $params);
    }

    /**********************
     Movie part: http://www.betaseries.com/api/methodes/movies
    **********************/ 

    /**
     * UNTESTED - Get movie list
     *
     * @param integer $start (Optionnal - default 0) Starting offset
     * @param integer $limit (optionnal - default 1000) Number of movies
     * @param string $order (Optionnal) Displayed order in list alphabetical|popularity
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getMoviesList($start=null,$limit=null,$order=null) {
        // Parameters validation
        if(!is_null($start) && !is_integer($start)) throw new \InvalidArgumentException('$start should be an integer');
        if(!is_null($limit) && !is_integer($limit)) throw new \InvalidArgumentException('$limit should be an integer');
        if(!in_array($order, array('alphabetical','popularity'))) throw new \InvalidArgumentException('$order parameter should be one of this value alphabetical|popularity');

        // Setting parameters
        $params = array();
        if(isset($start)) $params['start'] = $start;
        if(isset($limit)) $params['limit'] = $limit;
        if(isset($order)) $params['order'] = $order;

        // Making Betaseries request
        return $this->request('movies/list', 'GET', $params);
    }

    /**
     * UNTESTED - Get movie detail
     *
     * @param integer $id Movie identifier
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getMovieDetail($id) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;

        // Making Betaseries request
        return $this->request('movies/movie', 'GET', $params);
    }

    /**
     * UNTESTED - Add movie to member list
     *
     * @param integer $id Movie identifier
     * @param boolean $mail (Optionnal - default true) Activate email alerts 
     * @param boolean $twitter (Optionnal - default true) Activate twitter alerts
     * @param integer $state (Optionnal - default 0) 0=Not seen, 1=Seen, 2=Don't want to see
     * @param boolean $profile (Optionnal - default true) Display movie on profile
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function addMovie($id, $mail=null, $twitter=null, $state=null, $profile=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($state) && (!is_integer($state) || $state < 0 || $state > 2)) throw new \InvalidArgumentException('$state should be an integer between O and 2');
        if(!is_null($mail) && !is_bool($mail)) throw new \InvalidArgumentException('$mail should be an boolean');
        if(!is_null($twitter) && !is_bool($twitter)) throw new \InvalidArgumentException('$twitter should be an boolean');
        if(!is_null($profile) && !is_bool($profile)) throw new \InvalidArgumentException('$profile should be an boolean');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($state)) $params['state'] = $state;
        if(isset($mail)) $params['mail'] = $mail;
        if(isset($twitter)) $params['twitter'] = $twitter;
        if(isset($profile)) $params['profile'] = $profile;

        // Making Betaseries request
        return $this->request('movies/movie', 'POST', $params);
    }

    /**
     * UNTESTED - Remove movie from member list
     *
     * @param integer $id Movie identifier
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function removeMovie($id) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;

        // Making Betaseries request
        return $this->request('movies/movie', 'DELETE', $params);
    }

    /**
     * UNTESTED - Get random movies
     *
     * @param integer $number (Optionnal - default 1) Number of movies to retrieve
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getRandomMovie($number) {
        // Parameters validation
        if(!is_null($number) && !is_integer($number)) throw new \InvalidArgumentException('$number should be an integer');

        // Setting parameters
        $params = array();
        if(isset($number)) $params['number'] = $number;

        // Making Betaseries request
        return $this->request('movies/random', 'GET', $params);
    }

    /**
     * UNTESTED - Get movie information based on a filename
     *
     * @param string $filename Filename to check
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function scrapeMovie($filename) {
        // Parameters validation
        if(!is_string($filename)) throw new \InvalidArgumentException('$filename should be a string');

        // Setting parameters
        $params = array();
        $params['file'] = $filename;

        // Making Betaseries request
        return $this->request('movies/scraper', 'GET', $params);
    }

    /**
     * UNTESTED - Search movie
     *
     * @param string $title Movie title
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function searchMovie($title) {
        // Parameters validation
        if(!is_string($title)) throw new \InvalidArgumentException('$title should be a string');

        // Setting parameters
        $params = array(
            'title'=>$title,
            );

        // Making Betaseries request
        return $this->request('movies/search', 'GET', $params);
    }

    /**********************
     Members part: http://www.betaseries.com/api/methodes/members
    **********************/ 

    /**
     * UNTESTED - Get member access token
     *
     * @param integer $clientId Member id
     * @param string $clientSecret Secret string member
     * @param string $redirectUri URL to redirect after authentication
     * @param string $code Code get from Oauth API
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getMemberAccessToken($clientId, $clientSecret, $redirectUri, $code) {
        // Parameters validation
        if(!is_integer($clientId)) throw new \InvalidArgumentException('$clientId should be an integer');
        if(!is_string($clientSecret)) throw new \InvalidArgumentException('$clientSecret should be a string');
        if(!is_string($redirectUri)) throw new \InvalidArgumentException('$redirectUri should be a string');
        if(!is_string($code)) throw new \InvalidArgumentException('$code should be a string');

        // Setting parameters
        $params = array();
        $params['clientId'] = $clientId;
        $params['clientSecret'] = $clientSecret;
        $params['redirectUri'] = $redirectUri;
        $params['code'] = $code;

        // Making Betaseries request
        return $this->request('members/access_token', 'POST', $params);
    }

    /**
     * UNTESTED - Get member authentication
     *
     * @param string $login Login
     * @param string $password "Clear" password
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getMemberAuthentication($login, $password) {
        // Parameters validation
        if(!is_string($login)) throw new \InvalidArgumentException('$login should be a string');
        if(!is_string($password)) throw new \InvalidArgumentException('$password should be a string');

        // Setting parameters
        $params = array();
        $params['login'] = $login;
        $params['password'] = md5($password);

        // Making Betaseries request
        return $this->request('members/auth', 'POST', $params);
    }

    /**
     * UNTESTED - Get member badge
     *
     * @param integer $clientId Member id
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getMemberBadge($clientId) {
        // Parameters validation
        if(!is_integer($clientId)) throw new \InvalidArgumentException('$clientId should be an integer');

        // Setting parameters
        $params = array();
        $params['clientId'] = $clientId;

        // Making Betaseries request
        return $this->request('members/badges', 'GET', $params);
    }

    /**
     * UNTESTED - Destroy active token
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function destroyActiveToken() {
        // Parameters validation

        // Setting parameters

        // Making Betaseries request
        return $this->request('members/destroy', 'DELETE');
    }

    /**
     * UNTESTED - Get member info
     *
     * @param integer $clientId Member id
     * @param boolean $summary (Optionnal - default false) Does not include shows and movies information
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getMemberInfos($clientId, $summary=null) {
        // Parameters validation
        if(!is_integer($clientId)) throw new \InvalidArgumentException('$clientId should be an integer');
        if(!is_null($summary) && !is_bool($summary)) throw new \InvalidArgumentException('$summary should be a boolean');

        // Setting parameters
        $params = array();
        $params['clientId'] = $clientId;
        if(isset($summary) && $summary) $params['summary'] = 'true';
        if(isset($summary) && !$summary) $params['summary'] = 'false';

        // Making Betaseries request
        return $this->request('members/infos', 'GET', $params);
    }

    /**
     * UNTESTED - Check if token is active
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function checkActiveToken() {
        // Parameters validation

        // Setting parameters

        // Making Betaseries request
        return $this->request('members/is_active', 'GET');
    }

    /**
     * UNTESTED - Reinitialize password request
     *
     * @param string $credential Login or email account
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function reinitializePasswordRequest($credential) {
        // Parameters validation
        if(!is_string($credential)) throw new \InvalidArgumentException('$credential should be a string');

        // Setting parameters
        $params = array();
        $params['find'] = $credential;

        // Making Betaseries request
        return $this->request('members/lost', 'POST', $params);
    }

    /**
     * UNTESTED - Oauth authentication
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function oauthAuthentication() {
        // Parameters validation

        // Setting parameters

        // Making Betaseries request
        return $this->request('members/oauth', 'GET');
    }

    /**
     * UNTESTED - Oauth authentication - Send to account callBackUrl
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function oauthAuthenticationCallback() {
        // Parameters validation

        // Setting parameters

        // Making Betaseries request
        return $this->request('members/oauth', 'POST');
    }

    /**
     * UNTESTED - Get member options
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getMemberOptions() {
        // Parameters validation

        // Setting parameters

        // Making Betaseries request
        return $this->request('members/options', 'GET');
    }

    /**
     * UNTESTED - Set member options
     *
     * @param string $name Option label
     * @param string $value Option value
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function setMemberOption($name, $value) {
        // Parameters validation
        if(!is_string($name)) throw new \InvalidArgumentException('$name should be a string');
        if(!is_string($value)) throw new \InvalidArgumentException('$value should be a string');

        // Setting parameters
        $params = array();
        $params['name'] = $name;
        $params['value'] = $value;

        // Making Betaseries request
        return $this->request('members/option', 'POST', $params);
    }

    /**
     * UNTESTED - Show notifications
     *
     * @param integer $sinceId (Optionnal) Last id
     * @param integer $number (Optionnal - Default 10) Number of notifications
     * @param string $order (Optionnal - Default desc) Sort notifications asc|desc
     * @param array $types (Optionnal) Filter type notification (multiple choice possible). Possible choices : badge, banner, bugs, character, commentaire, dons, episode, facebook, film, forum, friend, message, quizz, recommend, site, subtitles, video
     * @param boolean $autoDelete (Optionnal - Default false) Automatic removal of notifications
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function showNotifications($sinceId=null, $number=null, $order=null, $types=null, $autoDelete=null) {
        // Parameters validation
        if(!is_null($sinceId) && !is_integer($sinceId)) throw new \InvalidArgumentException('$sinceId should be an integer');
        if(!is_null($number) && (!is_integer($number) || $number < 1 || $number > 100)) throw new \InvalidArgumentException('$number should be an integer between 1 and 100');
        if(!is_null($order) && !in_array($order, array('asc','desc'))) throw new \InvalidArgumentException('$order parameter should be one of this value asc|desc');
        if(!is_null($autoDelete) && !is_bool($autoDelete)) throw new \InvalidArgumentException('$autoDelete should be a boolean');
        if(!is_null($types) && !is_array($types)) throw new \InvalidArgumentException('$types should be an array');
        foreach($types as $type) {
            if(!is_string($type) && !in_array($type,array('badge','banner','bugs','character','commentaire','dons','episode','facebook','film','forum','friend','message','quizz','recommend','site','subtitles','video'))) throw new \InvalidArgumentException($type.' should be one of this value badge|banner|bugs|character|commentaire|dons|episode|facebook|film|forum|friend|message|quizz|recommend|site|subtitles|video');
        }


        // Setting parameters
        $params = array();
        if(isset($sinceId)) $params['since_id'] = $sinceId;
        if(isset($number)) $params['number'] = $number;
        if(isset($order)) $params['order'] = $order;
        if(isset($types)) $params['types'] = implode(',',$types);

        if(isset($autoDelete) && $autoDelete) $params['auto_delete'] = 'true';
        if(isset($autoDelete) && !$autoDelete) $params['auto_delete'] = 'false';

        // Making Betaseries request
        return $this->request('members/notifications', 'GET', $params);
    }

    /**
     * UNTESTED - Search member
     *
     * @param string $credential Login account
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function searchMember($credential) {
        // Parameters validation
        if(!is_string($credential)) throw new \InvalidArgumentException('$credential should be a string');
        if(strlen($credential)<2) throw new \InvalidArgumentException('$credential should be at least 2 characters');

        // Setting parameters
        $params = array();
        $params['login'] = $credential;

        // Making Betaseries request
        return $this->request('members/search', 'GET', $params);
    }

    /**
     * UNTESTED - Get a free username
     *
     * @param string $credential Username
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getFreeUsername($credential) {
        // Parameters validation
        if(!is_string($credential)) throw new \InvalidArgumentException('$credential should be a string');

        // Setting parameters
        $params = array();
        $params['username'] = $credential;

        // Making Betaseries request
        return $this->request('members/username', 'GET', $params);
    }

    /**
     * UNTESTED - Search friends member
     *
     * @param array $credentials List of emails addresses
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function syncUsers($credentials) {
        // Parameters validation
        if(!is_array($credentials)) throw new \InvalidArgumentException('$credentials should be an array');
        foreach($credentials as $credential) {
            if(filter_var($credential, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException($credential.' should be a valid email');
        }

        // Setting parameters
        $params = array();
        $params['mails'] = $credentials;

        // Making Betaseries request
        return $this->request('members/sync', 'POST', $params);
    }

    /**
     * UNTESTED - Create a new account
     *
     * @param string $login Username
     * @param string $email Email
     * @param string $password (Optionnal) "Clear" password of account. If not set, it will be generated by the sites
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function createAccount($login, $email, $password=null) {
        // Parameters validation
        if(!is_string($login)) throw new \InvalidArgumentException('$login should be a string');
        if(filter_var($email, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException($email.' should be a valid email');
        if(!is_null($password) && !is_string($password)) throw new \InvalidArgumentException('$password should be a string');

        // Setting parameters
        $params = array();
        $params['login'] = $login;
        $params['email'] = $email;
        $params['password'] = md5($password);

        // Making Betaseries request
        return $this->request('members/signup', 'POST', $params);
    }

    /**********************
     Show part: http://www.betaseries.com/api/methodes/shows
    **********************/ 

    /**
     * UNTESTED - Archive a show
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function archiveShow($id = null, $tvdbId = null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('shows/archive', 'POST', $params);
    }

    /**
     * UNTESTED - Unarchive a show
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function unarchiveShow($id = null, $tvdbId = null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('shows/archive', 'DELETE', $params);
    }

    /**
     * UNTESTED - Get show characters
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getShowCharacters($id = null, $tvdbId = null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('shows/characters', 'GET', $params);
    }

    /**
     * UNTESTED - Get show informations
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     * @param integer $userId (Optionnal) User information related to the show
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getShowInformations($id=null, $tvdbId=null, $userId=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');
        if(!is_null($userId) && !is_integer($userId)) throw new \InvalidArgumentException('$userId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;
        if(isset($userId)) $params['user'] = $userId;

        // Making Betaseries request
        return $this->request('shows/display', 'GET', $params);
    }

    /**
     * UNTESTED - Get show episodes
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     * @param integer $season (Optionnal) Season number
     * @param integer $episode (Optionnal) Episode number
     * @param boolean $subtitles (Optionnal) Show episode subtitles 
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getShowEpisodes($id=null, $tvdbId=null, $season=null, $episode=null, $subtitles=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');
        if(!is_null($season) && !is_integer($season)) throw new \InvalidArgumentException('$season should be an integer');
        if(!is_null($episode) && !is_integer($episode)) throw new \InvalidArgumentException('$episode should be an integer');
        if(!is_null($subtitles) && !is_bool($subtitles)) throw new \InvalidArgumentException('$subtitles should be a boolean');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;
        if(isset($season)) $params['season'] = $season;
        if(isset($episode)) $params['episode'] = $episode;
        if(isset($subtitles)) $params['subtitles'] = $subtitles;

        // Making Betaseries request
        return $this->request('shows/episodes', 'GET', $params);
    }

    /**
     * UNTESTED - List shows
     *
     * @param string $order (Optionnal) Order of shows alphabetical|popularity
     * @param \DateTime $since (Optionnal) Modification date
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function listShows($order=null, $since=null) {
        // Parameters validation
        if(!is_null($since) && !($since instanceof \DateTime)) throw new \InvalidArgumentException('$since should be a \DateTime');
        if(!is_null($order) && !in_array($order, array('alphabetical','popularity'))) throw new \InvalidArgumentException('$order parameter should be one of this value alphabetical|popularity');

        // Setting parameters
        $params = array();
        if(isset($since)) $params['since'] = $since->getTimestamp();
        if(isset($order)) $params['order'] = $order;

        // Making Betaseries request
        return $this->request('shows/list', 'GET', $params);
    }

    /**
     * UNTESTED - Note a show
     *
     * @param integer $note Show note between 1 and 5
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function setNoteShow($note, $id=null, $tvdbId=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');
        if(!is_integer($note) || $note < 1 || $note > 5) throw new \InvalidArgumentException('$note should be an integer between 1 and 5');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;
        $params['note'] = $note;

        // Making Betaseries request
        return $this->request('shows/note', 'POST', $params);
    }

    /**
     * UNTESTED - Unnote a show
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function unsetNoteShow($id=null, $tvdbId=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('shows/note', 'DELETE', $params);
    }

    /**
     * UNTESTED - Get pictures' show
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getShowPictures($id=null, $tvdbId=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('shows/pictures', 'GET', $params);
    }

    /**
     * UNTESTED - Get random show
     *
     * @param integer $number (Optionnal - Default 1) Number of shows
     * @param boolean $summary (Optionnal - Default false) Display only limited information
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getRandomShow($number=null, $summary=null) {
        // Parameters validation
        if(!is_null($number) && !is_integer($number)) throw new \InvalidArgumentException('$number should be an integer');
        if(!is_null($summary) && !is_bool($summary)) throw new \InvalidArgumentException('$summary should be a boolean');

        // Setting parameters
        $params = array();
        if(isset($number)) $params['number'] = $number;
        if(isset($summary) && $summary) $params['summary'] = 'true';
        if(isset($summary) && !$summary) $params['summary'] = 'false';

        // Making Betaseries request
        return $this->request('shows/random', 'GET', $params);
    }

    /**
     * UNTESTED - Send a recommendation to a friend
     *
     * @param integer $to User id to send recommendation
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     * @param string $comment (Optionnal) Comment to send with recommendation
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function sendRecommendation($to, $id=null, $tvdbId=null, $comment=null) {
        // Parameters validation
        if(!is_integer($to)) throw new \InvalidArgumentException('$to should be an integer');
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');
        if(!is_null($comment) && !is_string($comment)) throw new \InvalidArgumentException('$comment should be a string');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;
        if(isset($to)) $params['to'] = $to;
        if(isset($comment)) $params['comment'] = $comment;

        // Making Betaseries request
        return $this->request('shows/recommendation', 'POST', $params);
    }

    /**
     * UNTESTED - Delete a recommendation
     *
     * @param integer $id Recommendation id to delete
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function deleteRecommendation($id) {
        // Parameters validation
        if(!is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');

        // Setting parameters
        $params = array();
        $params['id'] = $id;

        // Making Betaseries request
        return $this->request('shows/recommendation', 'DELETE', $params);
    }

    /**
     * UNTESTED - Get user recommendation
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getRecommendations() {
        // Parameters validation

        // Setting parameters

        // Making Betaseries request
        return $this->request('shows/recommendations', 'GET', $params);
    }

    /**
     * UNTESTED - Search show
     *
     * @param string $title Show title
     * @param boolean $summary (Optionnal - Default false) Show only limited show information
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function searchShow($title, $summary=null) {
        // Parameters validation
        if(!is_string($title)) throw new \InvalidArgumentException('$title should be a string');
        if(!is_null($summary) && !is_bool($summary)) throw new \InvalidArgumentException('$summary should be a boolean');

        // Setting parameters
        $params = array();
        if(isset($title)) $params['title'] = $title;

        if(isset($summary) && $summary) $params['summary'] = 'true';
        if(isset($summary) && !$summary) $params['summary'] = 'false';

        // Making Betaseries request
        return $this->request('shows/search', 'GET', $params);
    }

    /**
     * UNTESTED - Add show to user list
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function addShow($id=null, $tvdbId=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('shows/show', 'POST', $params);
    }

    /**
     * UNTESTED - Remove show to user list
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function removeShow($id=null, $tvdbId=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('shows/show', 'DELETE', $params);
    }

    /**
     * UNTESTED - Get similar shows
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getSimilarShows($id=null, $tvdbId=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('shows/similars', 'GET', $params);
    }

    /**
     * UNTESTED - Get show videos
     *
     * @param integer $id (Optionnal) Episode id - Needed one of id or tvdbId
     * @param integer $tvdbId (Optionnal) Tvdb id - Needed one of id or tvdbId
     *
     * @return array...
     *
     * @throws PatbzhBetaseriesException In case betaseries api sends an error response
     * @throws \\InvalidArgumentException
     */
    public function getShowVideos($id=null, $tvdbId=null) {
        // Parameters validation
        if(!is_null($id) && !is_integer($id)) throw new \InvalidArgumentException('$id should be an integer');
        if(!is_null($tvdbId) && !is_integer($tvdbId)) throw new \InvalidArgumentException('$tvdbId should be an integer');

        if(is_null($id) && is_null($tvdbId)) throw new \InvalidArgumentException('At least $id or $tvdbId should be set');

        // Setting parameters
        $params = array();
        if(isset($id)) $params['id'] = $id;
        if(isset($tvdbId)) $params['thetvdb_id'] = $tvdbId;

        // Making Betaseries request
        return $this->request('shows/videos', 'GET', $params);
    }
}
