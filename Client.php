<?php
namespace phpcent2;

class Client
{
    protected $apikey;
    protected $secret;
    private $host;
    /**
     * @var ITransport $transport
     */
    private $transport;

    public function __construct($host = "http://localhost:8000")
    {
        $this->host = $host;

    }

    public function getHost()
    {
        return $this->host;
    }

    public function setSecret($secret)
    {
        $this->secret = $secret;
        return $this;
    }

	/**
	 * https://centrifugal.github.io/centrifugo/server/api/
	 * set api key for Server HTTP API
	 */
    public function setApiKey($apikey)
    {
        $this->apikey= $apikey;
        return $this;
    }
    /**
     * send message into channel of namespace. data is an actual information you want to send into channel
     *
     * @param       $channel
     * @param array $data
     * @return mixed
     */
    public function publish($channel, $data = [])
    {
        return $this->send("publish", ["channel" => $channel, "data" => $data]);
    }

    /**
     * send message into multiple channels. data is an actual information you want to send into channel
     * @param array $channels
     * @param array $data
     * @return mixed.
     */
    public function broadcast($channels, $data)
    {
        return
            $this->send(
                "broadcast",
                ["channels" => $channels, "data" => $data]
            );
    }

    /**
     * unsubscribe user with certain ID from channel.
     *
     * @param $channel
     * @param $userId
     * @return mixed
     */
    public function unsubscribe($channel, $userId)
    {
        return $this->send("unsubscribe", ["channel" => $channel, "user" => $userId]);
    }

    /**
     * disconnect user by user ID.
     *
     * @param $userId
     * @return mixed
     */
    public function disconnect($userId)
    {
        return $this->send("disconnect", ["user" => $userId]);
    }

    /**
     * get channel presence information (all clients currently subscribed on this channel).
     *
     * @param $channel
     * @return mixed
     */
    public function presence($channel)
    {
        return $this->send("presence", ["channel" => $channel]);
    }

    /**
     * get channel presence_stats information
     *
     * @param $channel
     * @return mixed
     */
    public function presence_stats($channel)
    {
        return $this->send("presence_stats", ["channel" => $channel]);
    }

    /**
     * get channel history information (list of last messages sent into channel).
     *
     * @param $channel
     * @return mixed
     */
    public function history($channel)
    {
        return $this->send("history", ["channel" => $channel]);
    }

    /**
     * get channels information (list of currently active channels).
     *
     * @return mixed
     */
    public function channels()
    {
        return $this->send("channels", []);
    }

    /**
     * get information about running server nodes.
     *
     * @return mixed
     */
    public function info()
    {
        return $this->send("info", []);
    }

    /**
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public function send($method, $params = [])
    {
        $this->checkApiKey();
        if (empty($params)) {
            $params = new \StdClass();
        }
        $data = json_encode(["method" => $method, "params" => $params]);

        return
            $this->getTransport()
                ->communicate(
                    $this->host,
					$data, 
					$this->apikey
                );
    }

    /**
     * Check that api key set
     * @throws \Exception
     */
    private function checkApiKey()
    {
        if ($this->apikey == null)
            throw new \Exception("Apikey must be set");
    }

    /**
     * @param ITransport $transport
     */
    public function setTransport(ITransport $transport)
    {
        $this->transport = $transport;
    }

    private function checkSecret()
    {
        if ($this->secret== null)
            throw new \Exception("Secret must be set");
    }
	/*
	 * @param object/array $data
	 */
	public function getToken($data){
		$this->checkSecret();
		return \Firebase\JWT\JWT::encode($data, $this->secret, 'HS256');
	}

    /**
     * @return ITransport
     */
    private function getTransport()
    {
        if ($this->transport == null) {
            $this->setTransport(new Transport());
        }

        return $this->transport;
    }


}
