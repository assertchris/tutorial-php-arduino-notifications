<?php

namespace Notifier\Service;

use Notifier\Service;
use Endroid\Twitter\Twitter as TwitterClient;

class Twitter implements Service
{
    /**
     * @var bool
     */
    private $new = false;

    /**
     * @var TwitterClient
     */
    private $client;

    /**
     * @var int
     */
    private $since;

    /**
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param string $accessToken
     * @param string $accessTokenSecret
     */
    public function __construct($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret)
    {
        $this->client = new TwitterClient(
            getenv("SERVICE_TWITTER_CONSUMER_KEY"),
            getenv("SERVICE_TWITTER_CONSUMER_SECRET"),
            getenv("SERVICE_TWITTER_ACCESS_TOKEN"),
            getenv("SERVICE_TWITTER_ACCESS_TOKEN_SECRET")
        );
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function query()
    {
        if ($this->new) {
            return true;
        }

        $parameters = [
            "count" => 1,
        ];

        if ($this->since) {
            $parameters["since_id"] = $this->since;
        }

        $response = $this->client->query(
            "statuses/mentions_timeline",
            "GET",
            "json",
            $parameters
        );

        $tweets = json_decode($response->getContent());

        // print_r($parameters);
        // print_r($tweets);
        // exit;

        if (count($tweets) > 0) {
            $this->new = true;
            $this->since = (int) $tweets[0]->id;
        }

        return $this->new;
    }

    /**
     * @inheritdoc
     */
    public function dismiss()
    {
        $this->new = false;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function name()
    {
        return "twitter";
    }

    /**
     * @inheritdoc
     */
    public function colors()
    {
        return [1 - 0.11, 1 - 0.62, 1 - 0.94];
    }
}
