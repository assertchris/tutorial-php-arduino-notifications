<?php

namespace Notifier\Service;

use Notifier\Service;

class Gmail implements Service
{
    /**
     * @var bool
     */
    private $new = false;

    /**
     * @var array
     */
    private $emails = [];

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
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

        $inbox = imap_open(
            "{imap.gmail.com:993/imap/ssl}INBOX",
            $this->username, $this->password
        );

        if (!$inbox) {
            return false;
        }

        $emails = imap_search($inbox, "ALL", SE_UID);

        if ($emails) {
            foreach ($emails as $email) {
                if (!in_array($email, $this->emails)) {
                    $this->new = true;
                    break;
                }
            }

            $this->emails = array_values($emails);
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
        return "gmail";
    }

    /**
     * @inheritdoc
     *
     * @return int[]
     */
    public function colors()
    {
        return [1 - 0.89, 1 - 0.15, 1 - 0.15];
    }
}
