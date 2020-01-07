<?php

namespace Leochenftw;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version2X;
use SilverStripe\Core\Environment;
use Exception;

class SocketEmitter
{
    public static function emit($channel, $data = [])
    {
        if ($socket_server = Environment::getEnv('SOCKET_SERVER')) {
            $client = new Client(new Version2X($socket_server));
            // open connection
            $client->initialize();
            // send for server (listen) the any array
            $client->emit($channel, $data);
            // close connection
            $client->close();

            return true;
        }

        throw new Exception('Please define SOCKET_SERVER in .env file first!');
    }
}
