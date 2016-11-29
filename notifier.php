<?php

require __DIR__ . "/vendor/autoload.php";

use Dotenv\Dotenv;

(new Dotenv(__DIR__))->load();

use Carica\Io;
use Carica\Firmata;

$loop = Io\Event\Loop\Factory::get();

$board = new Firmata\Board(
    Io\Stream\Serial\Factory::create(
        "/dev/cu.usbmodem1421", 57600
    )
);

print "connecting to arduino...";

$board
    ->activate()
    ->done(function () use ($loop, $board) {
        print "done" . PHP_EOL;

        // diode pins

        $board->pins[10]->mode = Firmata\Pin::MODE_PWM;
        $board->pins[10]->analog = 1;

        $board->pins[11]->mode = Firmata\Pin::MODE_PWM;
        $board->pins[11]->analog = 1;

        $board->pins[9]->mode = Firmata\Pin::MODE_PWM;
        $board->pins[9]->analog = 1;

        // sensor pins

        $board->pins[12]->mode = Firmata\Pin::MODE_OUTPUT;
        $board->pins[12]->digital = 1;

        $board->pins[14]->mode = Firmata\Pin::MODE_ANALOG;

        print "connecting to services...";

        $services = new SplQueue();

        $services->enqueue([
            new Notifier\Service\Twitter(
                getenv("SERVICE_TWITTER_CONSUMER_KEY"),
                getenv("SERVICE_TWITTER_CONSUMER_SECRET"),
                getenv("SERVICE_TWITTER_ACCESS_TOKEN"),
                getenv("SERVICE_TWITTER_ACCESS_TOKEN_SECRET")
            ), false
        ]);

        $services->enqueue([
            new Notifier\Service\Gmail(
                getenv("SERVICE_GMAIL_USERNAME"),
                getenv("SERVICE_GMAIL_PASSWORD")
            ), false
        ]);

        print "done" . PHP_EOL;

        $loop->setInterval(function () use (&$services) {
            $remaining = count($services);

            while ($remaining--) {
                $next = $services->dequeue();
                $next[1] = $next[0]->query();
                $services->enqueue($next);
            }
        }, 1000 * 5);

        $service = null;

        $next = function () use ($loop, $board, &$next, &$services, &$service) {
            $remaining = count($services);

            while ($remaining--) {
                $next = $services->dequeue();
                $services->enqueue($next);

                if ($next[1]) {
                    print "showing {$next[0]->name()}" . PHP_EOL;

                    $service = $next;
                    break;
                }
            }

            if (!$service) {
                print "no notifications" . PHP_EOL;
                return;
            }

            $colors = $service[0]->colors();

            $board->pins[10]->analog = $colors[0];
            $board->pins[11]->analog = $colors[1];
            $board->pins[9]->analog = $colors[2];

            $loop->setTimeout(function () use ($board, &$service) {
                $board->pins[10]->analog = 1;
                $board->pins[11]->analog = 1;
                $board->pins[9]->analog = 1;

                $service = null;
            }, 1000 * 1.5);
        };

        $loop->setInterval($next, 1000 * 4);

        $loop->setInterval(function() use ($board, &$services, &$service) {
            if ($service !== null && $board->pins[14]->analog < 0.1) {
                $remaining = count($services);

                while ($remaining--) {
                    print "dismissing {$service[0]->name()}" . PHP_EOL;

                    $next = $services->dequeue();

                    if ($next[0]->name() === $service[0]->name()) {
                        $service = null;
                        $next[0]->dismiss();
                        $next[1] = false;
                    }

                    $services->enqueue($next);
                }
            }
        }, 50);
    });

$loop->run();
