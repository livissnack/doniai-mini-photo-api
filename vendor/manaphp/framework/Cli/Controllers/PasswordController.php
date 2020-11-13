<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

/**
 * Class PasswordController
 *
 * @package ManaPHP\Cli\Controllers
 */
class PasswordController extends Controller
{
    /**
     * @param int    $length
     * @param string $password
     * @param int    $base
     * @param int    $cost
     */
    public function generateCommand($length = 32, $password = '', $base = 62, $cost = 0)
    {
        if ($password === '') {
            $password = $this->random->getBase($length, $base);
        }

        $this->console->writeLn('password: ' . $password);

        if ($cost) {
            $this->console->writeLn('hash: ' . password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]));
        } else {
            for ($i = 7; $i < 14; $i++) {
                $start = microtime(true);
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $i]);
                $this->console->writeLn([':time: :hash', 'time' => sprintf('%.3f', microtime(true) - $start), 'hash' => $hash]);
            }
        }
    }

    public function costCommand()
    {
        for ($i = 7; $i < 14; $i++) {
            $start = microtime(true);
            password_hash('password', PASSWORD_BCRYPT, ['cost' => $i]);
            $this->console->writeLn(sprintf('%2d: %.3f', $i, microtime(true) - $start));
        }
    }
}