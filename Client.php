<?php
namespace phpcent2;

class Client
{
    protected $apikey;
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

}
class Transport implements ITransport
{
    const SAFE = 1;
    const UNSAFE = 2;

    protected static $safety = self::SAFE;

    /**
     * @var string Certificate file name
     * @since 1.0.5
     */
    private $cert;
    /**
     * @var string Directory containing CA certificates
     * @since 1.0.5
     */
    private $caPath;

    /**
     * @var int|null
     */
    private $connectTimeoutOption;

    /**
     * @var int|null
     */
    private $timeoutOption;

    /**
     * @param mixed $safety
     */
    public static function setSafety($safety)
    {
        self::$safety = $safety;
    }

    /**
     *
     * @param string $host
     * @param mixed $data
     * @return mixed
     * @throws TransportException
     */
    public function communicate($host, $data, $apikey)
    {
		$headers = array(
			'Authorization: apikey '.$apikey,
			'Content-Type: application/json'
		);
        $ch = curl_init("$host/api");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


        if ($this->connectTimeoutOption !== null) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeoutOption);
        }
        if ($this->timeoutOption !== null) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutOption);
        }

        if (self::$safety === self::UNSAFE) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        } elseif (self::$safety === self::SAFE) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            if (null !== $this->cert) {
                curl_setopt($ch, CURLOPT_CAINFO, $this->cert);
            }
            if (null !== $this->caPath) {
                curl_setopt($ch, CURLOPT_CAPATH, $this->caPath);
            }
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $headers = curl_getinfo($ch);
        curl_close($ch);

        if (empty($headers["http_code"]) || ($headers["http_code"] != 200)) {
            throw new TransportException ("Response code: "
                . $headers["http_code"]
                . PHP_EOL
                . "cURL error: " . $error . PHP_EOL
                . "Body: "
                . $response
            );
        }

        return json_decode($response, true);
    }

    /**
     * @return string|null
     * @since 1.0.5
     */
    public function getCert()
    {
        return $this->cert;
    }

    /**
     * @param string|null $cert
     * @since 1.0.5
     */
    public function setCert($cert)
    {
        $this->cert = $cert;
    }

    /**
     * @return string|null
     * @since 1.0.5
     */
    public function getCAPath()
    {
        return $this->caPath;
    }

    /**
     * @param string|null $caPath
     * @since 1.0.5
     */
    public function setCAPath($caPath)
    {
        $this->caPath = $caPath;
    }

    /**
     * @return int|null
     */
    public function getConnectTimeoutOption()
    {
        return $this->connectTimeoutOption;
    }

    /**
     * @return int|null
     */
    public function getTimeoutOption()
    {
        return $this->timeoutOption;
    }

    /**
     * @param int|null $connectTimeoutOption
     * @return Transport
     */
    public function setConnectTimeoutOption($connectTimeoutOption)
    {
        $this->connectTimeoutOption = $connectTimeoutOption;

        return $this;
    }

    /**
     * @param int|null $timeoutOption
     * @return Transport
     */
    public function setTimeoutOption($timeoutOption)
    {
        $this->timeoutOption = $timeoutOption;

        return $this;
    }
}
interface ITransport
{

    /**
     * @param $host
     * @param $data
     * @return mixed
     */
    public function communicate($host, $data, $apikey);

} 
class TransportException extends Exception
{
    
}
