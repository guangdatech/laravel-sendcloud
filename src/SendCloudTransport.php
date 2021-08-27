<?php

/**
 * SendCloudTransport.php
 *
 * @copyright  2021 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2021-08-27 15:46:09
 * @modified   2021-08-27 15:46:09
 */

namespace Guangda\LaravelSendCloud;

use Illuminate\Support\Arr;
use Swift_Mime_SimpleMessage;
use GuzzleHttp\ClientInterface;
use Guangda\SendCloud\SendCloud;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Mail\Transport\Transport;
use GuzzleHttp\Exception\GuzzleException;

class SendCloudTransport extends Transport
{
    /**
     * Guzzle client instance.
     *
     * @var ClientInterface
     */
    protected $client;

    /**
     * The SendCloud API key.
     *
     * @var string
     */
    protected $apiUser;

    /**
     * The SendCloud email domain.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Create a new SendCloud transport instance.
     *
     * @param $apiUser
     * @param $apiKey
     */
    public function __construct($apiUser, $apiKey)
    {
        $this->apiUser = $apiUser;
        $this->apiKey = $apiKey;
    }

    /**
     * {@inheritdoc}
     * @throws GuzzleException
     * @throws SendCloudException
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null): int
    {
        $this->beforeSendPerformed($message);
        $to = $this->getTo($message);
        $bcc = $message->getBcc();
        $message->setBcc([]);

        $response = SendCloud::getInstance($this->apiUser, $this->apiKey)
            ->setFrom($this->getAddress($message->getFrom()))
            ->setFromName($this->getFromName($message))
            ->sendMail(
                [
                    'to' => $to,
                    'subject' => $message->getSubject(),
                    'html' => $message->getBody()
                ]
            );

        $result = json_decode($response->getBody()->getContents(), true);
        if (!$result['result']) {
            throw new SendCloudException("Code: {$result['statusCode']}, message: {$result['message']}");
        }

        $message->setBcc($bcc);
        $this->sendPerformed($message);
        return $this->numberOfRecipients($message);
    }

    /**
     * Get the "to" payload field for the API request.
     *
     * @param \Swift_Mime_SimpleMessage $message
     * @return string
     */
    protected function getTo(Swift_Mime_SimpleMessage $message): string
    {
        return collect($this->allContacts($message))->map(function ($display, $address) {
            return $display ? $display . " <{$address}>" : $address;
        })->values()->implode(',');
    }

    /**
     * Get all the contacts for the message.
     *
     * @param \Swift_Mime_SimpleMessage $message
     * @return array
     */
    protected function allContacts(Swift_Mime_SimpleMessage $message): array
    {
        return array_merge(
            (array)$message->getTo(), (array)$message->getCc(), (array)$message->getBcc()
        );
    }

    /**
     * Get the message ID from the response.
     *
     * @param ResponseInterface $response
     * @return string
     */
    protected function getMessageId(ResponseInterface $response): string
    {
        return object_get(
            json_decode($response->getBody()->getContents()), 'id'
        );
    }

    /**
     * 获取地址.
     *
     * @param $data
     *
     * @return mixed
     */
    protected function getAddress($data)
    {
        if (!$data) {
            return;
        }
        return Arr::get(array_keys($data), 0, null);
    }

    /**
     * 获取发件人名.
     *
     * @param Swift_Mime_SimpleMessage $message
     *
     * @return mixed
     */
    protected function getFromName(Swift_Mime_SimpleMessage $message)
    {
        return Arr::get(array_values($message->getFrom()), 0);
    }
}
